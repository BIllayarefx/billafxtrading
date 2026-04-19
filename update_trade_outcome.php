<?php
// update_trade_outcome.php - Update trade outcome (Win/Loss/BE)
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$trade_id = $data['trade_id'] ?? 0;
$outcome = $data['outcome'] ?? '';
$user_id = $_SESSION['user_id'];

// Validate
if (!in_array($outcome, ['Win', 'Loss', 'Breakeven'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid outcome']);
    exit;
}

$query = "UPDATE trades SET outcome = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $outcome, $trade_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>