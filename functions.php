<?php
// functions.php - Helper functions

function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($input)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getGradeClass($grade) {
    switch($grade) {
        case 'A+': return 'grade-Aplus';
        case 'A': return 'grade-A';
        case 'B+': return 'grade-Bplus';
        case 'B': return 'grade-B';
        case 'C+': return 'grade-Cplus';
        default: return 'grade-C';
    }
}

function getTodayRitual($user_id) {
    global $conn;
    $today = date('Y-m-d');
    $query = "SELECT * FROM daily_rituals WHERE user_id = ? AND ritual_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>