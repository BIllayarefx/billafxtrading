<?php
// save_ritual.php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$pre_market = isset($_POST['pre_market']) ? intval($_POST['pre_market']) : 0;
$slept_well = isset($_POST['slept_well']) ? intval($_POST['slept_well']) : 0;
$mentally_ready = isset($_POST['mentally_ready']) ? intval($_POST['mentally_ready']) : 0;
$accepted_risk = isset($_POST['accepted_risk']) ? intval($_POST['accepted_risk']) : 0;
$readiness = isset($_POST['readiness']) ? intval($_POST['readiness']) : 0;
$completed = isset($_POST['completed']) ? intval($_POST['completed']) : 0;

$query = "INSERT INTO daily_rituals 
          (user_id, ritual_date, readiness_score, pre_market_completed, slept_well, mentally_ready, accepted_risk, completed) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE 
          readiness_score = VALUES(readiness_score),
          pre_market_completed = VALUES(pre_market_completed),
          slept_well = VALUES(slept_well),
          mentally_ready = VALUES(mentally_ready),
          accepted_risk = VALUES(accepted_risk),
          completed = VALUES(completed)";

$stmt = $conn->prepare($query);
$stmt->bind_param("isiiiiii", $user_id, $today, $readiness, $pre_market, $slept_well, $mentally_ready, $accepted_risk, $completed);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save ritual']);
}
?>