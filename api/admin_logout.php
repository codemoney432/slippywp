<?php
// Suppress any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first, before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Start output buffering to catch any unexpected output
ob_start();

require_once '../config/admin.php';

logoutAdmin();

ob_clean();
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
ob_end_flush();
?>



