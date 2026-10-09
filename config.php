<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('DB_HOST', 'localhost');
// ... rest

// config.php — system configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- DATABASE ----------
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'laundrygo');

// ---------- SITE ----------
// IMPORTANT: This must be reachable from a phone for QR codes to work.
// Options:
//   Local:      http://192.168.1.100/laundry/    (your PC's LAN IP)
//   Ngrok:      https://abc123.ngrok.io/laundry/
//   Production: https://laundry.yourdomain.com/
define('BASE_URL', 'http://localhost/laundrygo/');
define('APP_NAME', 'LaundryGo');
define('CURRENCY', 'RM');

// ---------- BUSINESS ----------
define('QR_EXPIRE_MINUTES', 15);   // how long a QR payment link lives

// ---------- DB CONNECTION ----------
function getDB() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        die("Database connection failed. Check config.php credentials.");
    }
}

// ---------- HELPERS ----------
function money($amt) {
    return number_format((float)$amt, 2);
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}