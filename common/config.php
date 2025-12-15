<?php
/**
 * Database Configuration & Connection
 * Centralized PDO connection with error handling
 */

// Prevent direct access
defined('APP_ACCESS') or define('APP_ACCESS', true);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'eventmanage_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Event Management');
define('APP_URL', 'http://localhost/eventmanage');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    session_start();
}

// PDO Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Check if install is needed
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        if (!file_exists(__DIR__ . '/../install/install.lock')) {
            header('Location: ' . APP_URL . '/install/install.php');
            exit;
        }
    }
    die('Database Connection Failed: ' . $e->getMessage());
}

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to get settings from database
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT config_value FROM settings WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['config_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Load dynamic app name from settings if available
if (isset($pdo)) {
    $dynamicAppName = getSetting('app_name');
    if ($dynamicAppName) {
        define('APP_NAME_DISPLAY', $dynamicAppName);
    } else {
        define('APP_NAME_DISPLAY', APP_NAME);
    }
}
