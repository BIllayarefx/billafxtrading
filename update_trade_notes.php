<?php
// update_trade_notes.php - Update trade notes
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$trade_id = $data['trade_id'] ?? 0;
$notes = $data['notes'] ?? '';
$user_id = $_SESSION['user_id'];

$query = "UPDATE trades SET notes = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $notes, $trade_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>