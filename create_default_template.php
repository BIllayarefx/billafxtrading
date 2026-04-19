<?php
require_once 'config.php';
require_once 'auth.php';

// Only run this once
if (!$auth->isLoggedIn()) {
    die('Please login first');
}

// Get all users
$users = $conn->query("SELECT id FROM users");
while ($user = $users->fetch_assoc()) {
    $user_id = $user['id'];
    
    // Check if user already has a default template
    $check = $conn->prepare("SELECT id FROM templates WHERE user_id = ? AND name = 'System Default (A+)'");
    $check->bind_param("i", $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) continue;
    
    // Create template
    $insert = $conn->prepare("INSERT INTO templates (user_id, name, description, direction_scope, session_scope) VALUES (?, 'System Default (A+)', 'Default ICT A+ trade checklist', 'Both', 'Both')");
    $insert->bind_param("i", $user_id);
    $insert->execute();
    $template_id = $conn->insert_id;
    
    // Add rules (from your current hardcoded arrays)
    $htf_rules = [
        ['label' => 'Daily Order-flow bearish + DOL', 'type' => 'HTF'],
        ['label' => 'Daily Order-flow + ICR (Either/Or)', 'type' => 'HTF'],
        ['label' => 'Daily Order-flow + CRT (Either/Or)', 'type' => 'HTF'],
        ['label' => '4H A to B + POI + ERL (Either/Or)', 'type' => 'HTF'],
        ['label' => 'A to B + LQ:Engineering + POI + ERL (Either/Or)', 'type' => 'HTF'],
        ['label' => '4H Order-flow + CRT', 'type' => 'HTF']
    ];
    
    $ltf_rules = [
        ['label' => 'A to B Premium joogta', 'type' => 'LTF'],
        ['label' => 'Time window', 'type' => 'LTF'],
        ['label' => 'PDH/CRT H + Asian H', 'type' => 'LTF'],
        ['label' => 'SMT/3D', 'type' => 'LTF'],
        ['label' => 'Above the OP', 'type' => 'LTF'],
        ['label' => '5 minute SHIFT + FVG/BB (Either/Or)', 'type' => 'LTF'],
        ['label' => '15m model #1 (Either/Or)', 'type' => 'LTF'],
        ['label' => 'SL Swing ka dabadiisa dhigo', 'type' => 'LTF'],
        ['label' => 'SSLQ target', 'type' => 'LTF']
    ];
    
    $position = 0;
    foreach (array_merge($htf_rules, $ltf_rules) as $rule) {
        $group_type = (strpos($rule['label'], '(Either/Or)') !== false) ? 'either_or' : 'single';
        $stmt = $conn->prepare("INSERT INTO template_rules (template_id, label, rule_type, required, group_type, position) VALUES (?, ?, ?, 1, ?, ?)");
        $stmt->bind_param("issii", $template_id, $rule['label'], $rule['type'], $group_type, $position);
        $stmt->execute();
        $position++;
    }
    
    echo "Default template created for user $user_id<br>";
}
echo "Done.";