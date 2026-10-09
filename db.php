<?php
// db.php — one-time database installer
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS laundry_qr_payment
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE laundry_qr_payment");

    // --- machines ---
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS machines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            machine_code VARCHAR(20) UNIQUE NOT NULL,
            machine_name VARCHAR(50) NOT NULL,
            location VARCHAR(100),
            machine_type ENUM('washer','dryer') DEFAULT 'washer',
            price_per_cycle DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            cycle_minutes INT DEFAULT 30,
            status ENUM('available','in_use','maintenance','offline') DEFAULT 'available',
            last_used TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    // --- qr_sessions ---
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS qr_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            machine_id INT NOT NULL,
            qr_token VARCHAR(64) UNIQUE NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('active','used','expired') DEFAULT 'active',
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
            INDEX idx_token (qr_token)
        ) ENGINE=InnoDB
    ");

    // --- transactions ---
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_ref VARCHAR(30) UNIQUE NOT NULL,
            machine_id INT NOT NULL,
            qr_token VARCHAR(64),
            customer_name VARCHAR(100),
            customer_phone VARCHAR(20),
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('qr_wallet','online_banking','credit_card','e_wallet') NOT NULL,
            payment_gateway_ref VARCHAR(100),
            status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
            cycle_started_at DATETIME NULL,
            cycle_ends_at DATETIME NULL,
            notified TINYINT(1) DEFAULT 0,
            notified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (machine_id) REFERENCES machines(id),
            INDEX idx_ref (transaction_ref),
            INDEX idx_status (status),
            INDEX idx_notified (notified, cycle_ends_at)
        ) ENGINE=InnoDB
    ");

    // --- seed machines ---
    $count = $pdo->query("SELECT COUNT(*) FROM machines")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO machines
                (machine_code, machine_name, location, machine_type, price_per_cycle, cycle_minutes)
            VALUES
                ('WM-01', 'Washer 1', 'Block A - Level 1', 'washer', 4.00, 30),
                ('WM-02', 'Washer 2', 'Block A - Level 1', 'washer', 4.00, 30),
                ('WM-03', 'Washer 3', 'Block A - Level 2', 'washer', 4.00, 30),
                ('DR-01', 'Dryer 1',  'Block A - Level 1', 'dryer',  4.00, 40),
                ('DR-02', 'Dryer 2',  'Block A - Level 1', 'dryer',  4.00, 40),
                ('DR-03','Dryer 3',  'Block A - Level 1', 'dryer',  4.00, 40) 
        ");
    }

    echo "<h2 style='color:green'>✅ Database installed successfully!</h2>";
    echo "<p>5 machines seeded. <a href='index.php'>Go to homepage →</a></p>";
    echo "<p style='color:#c00'><b>Delete db.php after this for security.</b></p>";

} catch (PDOException $e) {
    die("<h2 style='color:red'>Install failed:</h2><pre>" . $e->getMessage() . "</pre>");
}