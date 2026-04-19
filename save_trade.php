<?php
// save_trade.php - Handle trade saving with multiple accounts
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get form data
    $direction = $_POST['direction'] ?? 'Bullish';
    $session = $_POST['session'] ?? 'London';
    $pair = $_POST['pair'] ?? 'EUR/USD';
    $decision = $_POST['decision'] ?? 'TAKE';
    $trade_grade = $_POST['trade_grade'] ?? 'C';
    $htf_checked = intval($_POST['htf_checked'] ?? 0);
    $ltf_checked = intval($_POST['ltf_checked'] ?? 0);
    $custom_note = $_POST['custom_note'] ?? '';

    // Multi-account data
    $selected_accounts = isset($_POST['selected_accounts']) ? json_decode($_POST['selected_accounts'], true) : [];
    $pnl_mode = $_POST['pnl_mode'] ?? '$';
    $pnl_value = floatval($_POST['pnl_value'] ?? 0);

    // Skip reason if any
    $skip_reason = $_POST['skip_reason'] ?? null;
    $skip_notes = $_POST['skip_notes'] ?? null;

    // Checklist and psychology
    $checked_items = isset($_POST['checked_items']) ? json_decode($_POST['checked_items'], true) : [];
    $psychology = isset($_POST['psychology']) ? json_decode($_POST['psychology'], true) : [];

    // Compliance percentage
    $total_items = 15;
    $compliance = round(($htf_checked + $ltf_checked) / $total_items * 100, 2);

    // Outcome
    $outcome = ($decision === 'SKIP') ? 'Skipped' : 'Pending';

    // Begin transaction
    $conn->begin_transaction();

    // Insert trade (without account_id)
    $trade_query = "INSERT INTO trades (
        user_id, trade_date, pair, direction, session,
        outcome, trade_grade, htf_rules_met, ltf_rules_met,
        compliance_percentage, skip_reason, skip_notes, notes,
        pnl_mode, pnl_value
    ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($trade_query);
    $stmt->bind_param(
        "isssssiissssss",
        $user_id, $pair, $direction, $session,
        $outcome, $trade_grade, $htf_checked, $ltf_checked,
        $compliance, $skip_reason, $skip_notes, $custom_note,
        $pnl_mode, $pnl_value
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert trade: " . $stmt->error);
    }
    $trade_id = $conn->insert_id;

    // Process allocations if accounts were selected
    $total_pnl = 0;
    if (!empty($selected_accounts)) {
        // Fetch selected accounts' details
        $placeholders = implode(',', array_fill(0, count($selected_accounts), '?'));
        $types = str_repeat('i', count($selected_accounts));
        $acc_query = "SELECT id, current_balance, risk_percent FROM trading_accounts WHERE id IN ($placeholders) AND user_id = ?";
        $params = $selected_accounts;
        $params[] = $user_id;
        $types .= 'i';

        $stmt = $conn->prepare($acc_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $accounts = $stmt->get_result();

        $allocations = [];
        while ($acc = $accounts->fetch_assoc()) {
            $alloc_pnl = 0;
            if ($pnl_mode == '$') {
                $alloc_pnl = $pnl_value; // fixed per account
            } else { // '%' mode
                $alloc_pnl = $acc['current_balance'] * ($pnl_value / 100);
            }
            // Apply sign based on outcome
            if ($outcome == 'Loss') {
                $alloc_pnl = -abs($alloc_pnl);
            } elseif ($outcome == 'Breakeven') {
                $alloc_pnl = 0;
            } else {
                $alloc_pnl = abs($alloc_pnl);
            }

            // Calculate R multiple for this account
            $risk_amount = $acc['current_balance'] * ($acc['risk_percent'] / 100);
            $r_multiple = $risk_amount > 0 ? $alloc_pnl / $risk_amount : 0;

            $allocations[] = [
                'account_id' => $acc['id'],
                'pnl' => $alloc_pnl,
                'r' => $r_multiple
            ];
        }

        // Insert allocations and update account balances
        foreach ($allocations as $alloc) {
            // Insert into trade_accounts
            $alloc_query = "INSERT INTO trade_accounts (trade_id, account_id, allocated_pnl, allocated_r) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($alloc_query);
            $stmt->bind_param("iidd", $trade_id, $alloc['account_id'], $alloc['pnl'], $alloc['r']);
            $stmt->execute();

            // Update account balance
            $update_balance = "UPDATE trading_accounts SET current_balance = current_balance + ? WHERE id = ?";
            $stmt = $conn->prepare($update_balance);
            $stmt->bind_param("di", $alloc['pnl'], $alloc['account_id']);
            $stmt->execute();

            $total_pnl += $alloc['pnl'];
        }
    }

    // Update trade's total profit_loss
    $update_trade = "UPDATE trades SET profit_loss = ? WHERE id = ?";
    $stmt = $conn->prepare($update_trade);
    $stmt->bind_param("di", $total_pnl, $trade_id);
    $stmt->execute();

    // Insert checklist items (unchanged)
    if (!empty($checked_items)) {
        $check_query = "INSERT INTO trade_checklists (trade_id, checklist_type, item_key, checked) VALUES (?, ?, ?, 1)";
        $check_stmt = $conn->prepare($check_query);
        foreach ($checked_items as $item) {
            if (empty($item)) continue;
            $type = (strpos($item, 'Daily') !== false || strpos($item, '4H') !== false) ? 'HTF' : 'LTF';
            $check_stmt->bind_param("iss", $trade_id, $type, $item);
            $check_stmt->execute();
        }
    }

    // Insert psychology (unchanged)
    if (!empty($psychology)) {
        $psych_query = "INSERT INTO trade_psychology (trade_id, emotion, custom_note) VALUES (?, ?, ?)";
        $psych_stmt = $conn->prepare($psych_query);
        foreach ($psychology as $emotion) {
            if (empty($emotion)) continue;
            $note = ($emotion == '+ Custom') ? $custom_note : '';
            $psych_stmt->bind_param("iss", $trade_id, $emotion, $note);
            $psych_stmt->execute();
        }
    }

    // Handle file uploads (unchanged)
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $timeframes = ['weeklyProof' => '1W', 'dailyProof' => '1D', 'fourHProof' => '4H', 'entryProof' => '15m', 'afterProof' => 'After'];
    foreach ($timeframes as $field => $timeframe) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $trade_id . '_' . $timeframe . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $chart_query = "INSERT INTO chart_snapshots (trade_id, timeframe, image_path) VALUES (?, ?, ?)";
                $chart_stmt = $conn->prepare($chart_query);
                $chart_stmt->bind_param("iss", $trade_id, $timeframe, $filename);
                $chart_stmt->execute();
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $decision === 'SKIP' ? 'Trade skipped' : 'Trade saved',
        'trade_id' => $trade_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Save trade error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>