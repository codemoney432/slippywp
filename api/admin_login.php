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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    ob_end_flush();
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['password'])) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Password required']);
    ob_end_flush();
    exit;
}

$password = $input['password'];

if (authenticateAdmin($password)) {
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Admin authenticated successfully']);
} else {
    ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Invalid password']);
}

ob_end_flush();
?>



