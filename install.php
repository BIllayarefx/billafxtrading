<?php
// install.php - Run this once to set up database
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install BILLA_FX</title>
    <style>
        body { font-family: Arial; background: #0a0c10; color: white; padding: 40px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        pre { background: #11161f; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <h1>Installing BILLA_FX Trading Journal</h1>
    
    <?php
    // Database connection
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'billa_fx_journal';
    
    // Create connection
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p>");
    }
    
    echo "<p class='success'>✅ Connected to MySQL</p>";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Database created or already exists</p>";
    } else {
        echo "<p class='error'>❌ Error creating database: " . $conn->error . "</p>";
    }
    
    // Select database
    $conn->select_db($dbname);
    
    // Read SQL file
    $sql = "
    -- Users table
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        profile_badge VARCHAR(50) DEFAULT 'Student',
        avatar_color VARCHAR(20) DEFAULT '#3b82f6',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Trading accounts table
    CREATE TABLE IF NOT EXISTS trading_accounts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        account_name VARCHAR(100) NOT NULL,
        starting_balance DECIMAL(15,2) NOT NULL,
        current_balance DECIMAL(15,2) NOT NULL,
        risk_mode ENUM('percent', 'fixed') DEFAULT 'percent',
        risk_percent DECIMAL(5,2) DEFAULT 1.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Trades table
    CREATE TABLE IF NOT EXISTS trades (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        account_id INT NOT NULL,
        trade_date DATE NOT NULL,
        pair VARCHAR(20) NOT NULL,
        direction ENUM('Bullish', 'Bearish') NOT NULL,
        session ENUM('London', 'New York', 'Asian') NOT NULL,
        entry_price DECIMAL(10,5),
        exit_price DECIMAL(10,5),
        stop_loss DECIMAL(10,5),
        take_profit DECIMAL(10,5),
        position_size DECIMAL(10,2),
        profit_loss DECIMAL(15,2),
        r_multiple DECIMAL(10,2) DEFAULT 0.00,
        risk_amount DECIMAL(10,2) DEFAULT 0.00,
        outcome ENUM('Win', 'Loss', 'Breakeven', 'Pending', 'Skipped') DEFAULT 'Pending',
        trade_grade VARCHAR(5) DEFAULT 'C',
        htf_rules_met INT DEFAULT 0,
        ltf_rules_met INT DEFAULT 0,
        compliance_percentage DECIMAL(5,2) DEFAULT 0.00,
        skip_reason VARCHAR(255),
        skip_notes TEXT,
        market_conditions VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (account_id) REFERENCES trading_accounts(id) ON DELETE CASCADE
    );

    -- Trade checklists table
    CREATE TABLE IF NOT EXISTS trade_checklists (
        id INT PRIMARY KEY AUTO_INCREMENT,
        trade_id INT NOT NULL,
        checklist_type ENUM('HTF', 'LTF') NOT NULL,
        item_key VARCHAR(255) NOT NULL,
        checked BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE
    );

    -- Trade psychology table
    CREATE TABLE IF NOT EXISTS trade_psychology (
        id INT PRIMARY KEY AUTO_INCREMENT,
        trade_id INT NOT NULL,
        emotion VARCHAR(50) NOT NULL,
        custom_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE
    );

    -- Chart snapshots table
    CREATE TABLE IF NOT EXISTS chart_snapshots (
        id INT PRIMARY KEY AUTO_INCREMENT,
        trade_id INT NOT NULL,
        timeframe ENUM('1W','1D','4H','15m','5m','After') NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE
    );

    -- Weekly outlooks table
    CREATE TABLE IF NOT EXISTS weekly_outlooks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        week_starting DATE NOT NULL,
        pair VARCHAR(20) NOT NULL,
        bias ENUM('Bullish', 'Bearish', 'Neutral') NOT NULL,
        analysis TEXT,
        chart_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Daily notes table
    CREATE TABLE IF NOT EXISTS daily_notes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        note_date DATE NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_daily_note (user_id, note_date)
    );

    -- Daily rituals table
    CREATE TABLE IF NOT EXISTS daily_rituals (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        ritual_date DATE NOT NULL,
        readiness_score INT DEFAULT 0,
        pre_market_completed BOOLEAN DEFAULT FALSE,
        slept_well BOOLEAN DEFAULT FALSE,
        mentally_ready BOOLEAN DEFAULT FALSE,
        accepted_risk BOOLEAN DEFAULT FALSE,
        completed BOOLEAN DEFAULT FALSE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_daily_ritual (user_id, ritual_date)
    );

    -- Trader profile table
    CREATE TABLE IF NOT EXISTS trader_profile (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        avatar_color VARCHAR(20) DEFAULT '#3b82f6',
        primary_session VARCHAR(50) DEFAULT 'New York',
        trading_style VARCHAR(50) DEFAULT 'Swing',
        experience_level VARCHAR(50) DEFAULT 'Beginner',
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    
    // Execute multiple queries
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "<p class='success'>✅ All tables created successfully!</p>";
    } else {
        echo "<p class='error'>❌ Error creating tables: " . $conn->error . "</p>";
    }
    
    // Create uploads directory
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        echo "<p class='success'>✅ Uploads directory created</p>";
    }
    
    $conn->close();
    ?>
    
    <hr>
    <h2>Installation Complete!</h2>
    <p>Now you can:</p>
    <ul>
        <li><a href="index.php" style="color: #3b82f6;">Go to Login Page</a></li>
    </ul>
    
    <pre>
Default login credentials:
Email: test@example.com
Password: password123
(You'll need to register first)
    </pre>
</body>
</html>