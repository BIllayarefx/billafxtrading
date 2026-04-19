<?php
// update_trade_account.php - Change trade account
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
$user_id = $_SESSION['user_id'];

$query = "UPDATE trades SET account_id = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $account_id, $trade_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>