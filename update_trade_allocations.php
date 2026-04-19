<?php
// update_trade_allocations.php - Update allocations and trade outcome/mode
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$trade_id = intval($data['trade_id'] ?? 0);
$allocations = $data['allocations'] ?? []; // array of {account_id, pnl}
$outcome = $data['outcome'] ?? null;
$pnl_mode = $data['pnl_mode'] ?? null;
$pnl_value = floatval($data['pnl_value'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$trade_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid trade ID']);
    exit;
}

$conn->begin_transaction();

try {
    // Get current allocations to reverse old PnL
    $old_query = "SELECT ta.*, a.current_balance FROM trade_accounts ta 
                  JOIN trading_accounts a ON ta.account_id = a.id 
                  WHERE ta.trade_id = ? AND a.user_id = ?";
    $stmt = $conn->prepare($old_query);
    $stmt->bind_param("ii", $trade_id, $user_id);
    $stmt->execute();
    $old_allocations = $stmt->get_result();

    // Reverse old PnL from each account
    while ($old = $old_allocations->fetch_assoc()) {
        $reverse = -$old['allocated_pnl'];
        $update = "UPDATE trading_accounts SET current_balance = current_balance + ? WHERE id = ?";
        $stmt2 = $conn->prepare($update);
        $stmt2->bind_param("di", $reverse, $old['account_id']);
        $stmt2->execute();
    }

    // Delete old allocations
    $delete = "DELETE FROM trade_accounts WHERE trade_id = ?";
    $stmt = $conn->prepare($delete);
    $stmt->bind_param("i", $trade_id);
    $stmt->execute();

    // Insert new allocations and update balances
    $total_pnl = 0;
    foreach ($allocations as $alloc) {
        $account_id = intval($alloc['account_id']);
        $pnl = floatval($alloc['pnl']);

        // Get account risk percent for R calculation
        $acc_query = "SELECT current_balance, risk_percent FROM trading_accounts WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($acc_query);
        $stmt->bind_param("ii", $account_id, $user_id);
        $stmt->execute();
        $acc = $stmt->get_result()->fetch_assoc();

        $risk_amount = $acc['current_balance'] * ($acc['risk_percent'] / 100);
        $r_multiple = $risk_amount > 0 ? $pnl / $risk_amount : 0;

        // Insert allocation
        $insert = "INSERT INTO trade_accounts (trade_id, account_id, allocated_pnl, allocated_r) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("iidd", $trade_id, $account_id, $pnl, $r_multiple);
        $stmt->execute();

        // Update account balance
        $update = "UPDATE trading_accounts SET current_balance = current_balance + ? WHERE id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("di", $pnl, $account_id);
        $stmt->execute();

        $total_pnl += $pnl;
    }

    // Update trade's total profit_loss, outcome, mode, value
    $update_trade = "UPDATE trades SET profit_loss = ?, outcome = ?, pnl_mode = ?, pnl_value = ? WHERE id = ?";
    $stmt = $conn->prepare($update_trade);
    $stmt->bind_param("dssdi", $total_pnl, $outcome, $pnl_mode, $pnl_value, $trade_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'total_pnl' => $total_pnl]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>