<?php
// update_trade_pnl.php - Complete working version
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$trade_id = $data['trade_id'] ?? 0;
$account_id = $data['account_id'] ?? 0;
$pnl = floatval($data['pnl'] ?? 0);
$user_id = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // First, get the trade's current PnL if any (to reverse it)
    $old_pnl_query = "SELECT profit_loss, account_id FROM trades WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($old_pnl_query);
    $stmt->bind_param("ii", $trade_id, $user_id);
    $stmt->execute();
    $old_trade = $stmt->get_result()->fetch_assoc();
    
    $old_pnl = $old_trade['profit_loss'] ?? 0;
    $old_account_id = $old_trade['account_id'] ?? $account_id;
    
    // If account changed, we need to update both accounts
    if ($old_account_id != $account_id && $old_account_id > 0) {
        // Reverse old PnL from old account
        $reverse_balance = "UPDATE trading_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($reverse_balance);
        $stmt->bind_param("dii", $old_pnl, $old_account_id, $user_id);
        $stmt->execute();
        
        // Apply new PnL to new account
        $new_balance = "UPDATE trading_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($new_balance);
        $stmt->bind_param("dii", $pnl, $account_id, $user_id);
        $stmt->execute();
    } else if ($account_id > 0) {
        // Same account - just adjust balance by the difference
        $difference = $pnl - $old_pnl;
        
        if ($difference != 0) {
            $update_balance = "UPDATE trading_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_balance);
            $stmt->bind_param("dii", $difference, $account_id, $user_id);
            $stmt->execute();
        }
    }
    
    // Get account's current balance to calculate R multiple correctly
    $account_query = "SELECT current_balance, risk_percent FROM trading_accounts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($account_query);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    
    // Calculate R multiple based on risk percent of current balance
    $risk_amount = 0;
    $r_multiple = 0;
    
    if ($account && $account['current_balance'] > 0) {
        $risk_amount = $account['current_balance'] * ($account['risk_percent'] / 100);
        $r_multiple = $risk_amount > 0 ? $pnl / $risk_amount : 0;
    }
    
    // Update trade with new PnL, R multiple, and account_id
    $update_trade = "UPDATE trades SET profit_loss = ?, r_multiple = ?, account_id = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_trade);
    $stmt->bind_param("ddiii", $pnl, $r_multiple, $account_id, $trade_id, $user_id);
    $stmt->execute();
    
    // Get updated account balance
    $new_balance_query = "SELECT current_balance FROM trading_accounts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($new_balance_query);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    $updated_account = $stmt->get_result()->fetch_assoc();
    $new_balance = $updated_account['current_balance'] ?? 0;
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'new_balance' => $new_balance,
        'pnl' => $pnl,
        'r_multiple' => $r_multiple,
        'message' => 'Trade and balance updated successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Update PnL error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>