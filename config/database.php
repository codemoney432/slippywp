<?php
// Suppress warnings/notices that might output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'wpdb23668u27577');
define('DB_PASS', 'wpdbwfOeB7PjGt3TOu6RYlop26450');
define('DB_NAME', 'wp2420631196db_23668');
define('DB_TABLE_PREFIX', 'slippy_');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}
?>

