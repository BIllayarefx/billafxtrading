<?php
// auth.php - Authentication class
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function register($username, $email, $password, $full_name) {
        // Check if user exists
        $check = $this->conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, full_name, profile_badge) VALUES (?, ?, ?, ?, 'Student')");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Create default trader profile
            $profile = $this->conn->prepare("INSERT INTO trader_profile (user_id, avatar_color, primary_session) VALUES (?, 'blue', 'New York')");
            $profile->bind_param("i", $user_id);
            $profile->execute();
            
            // Create default trading account
            $account = $this->conn->prepare("INSERT INTO trading_accounts (user_id, account_name, starting_balance, current_balance, risk_mode, risk_percent) VALUES (?, 'Main Account', 1000.00, 1000.00, 'percent', 1.00)");
            $account->bind_param("i", $user_id);
            $account->execute();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['profile_badge'] = 'Student';
            
            return ['success' => true, 'message' => 'Registration successful'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password, full_name, profile_badge FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['profile_badge'] = $user['profile_badge'];
                return ['success' => true, 'message' => 'Login successful'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

$auth = new Auth($conn);

// Handle login/register requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'login') {
        echo json_encode($auth->login($_POST['email'], $_POST['password']));
        exit;
    }
    
    if ($_POST['action'] === 'register') {
        echo json_encode($auth->register(
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['full_name']
        ));
        exit;
    }
}
?>