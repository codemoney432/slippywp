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

require_once '../config/database.php';
require_once '../config/admin.php';

$table_prefix = DB_TABLE_PREFIX;

// Check if admin is authenticated
if (!isAdmin()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Admin authentication required.']);
    ob_end_flush();
    exit;
}

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

if (!isset($input['report_id']) || !is_numeric($input['report_id'])) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Invalid report_id']);
    ob_end_flush();
    exit;
}

$report_id = intval($input['report_id']);

$conn = getDBConnection();
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Verify report exists before deleting
$check_query = "SELECT id FROM `{$table_prefix}reports` WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
if (!$check_stmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    $conn->close();
    ob_end_flush();
    exit;
}

$check_stmt->bind_param("i", $report_id);
if (!$check_stmt->execute()) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed: ' . $check_stmt->error]);
    $check_stmt->close();
    $conn->close();
    ob_end_flush();
    exit;
}

$result = $check_stmt->get_result();
if ($result->num_rows === 0) {
    ob_clean();
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    $check_stmt->close();
    $conn->close();
    ob_end_flush();
    exit;
}
$check_stmt->close();

// Delete the report (CASCADE will automatically delete votes and comments)
$delete_query = "DELETE FROM `{$table_prefix}reports` WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
if (!$delete_stmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Delete query prepare failed: ' . $conn->error]);
    $conn->close();
    ob_end_flush();
    exit;
}

$delete_stmt->bind_param("i", $report_id);
if (!$delete_stmt->execute()) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed: ' . $delete_stmt->error]);
    $delete_stmt->close();
    $conn->close();
    ob_end_flush();
    exit;
}

// Clear any unexpected output
ob_clean();

echo json_encode(['success' => true, 'message' => 'Report deleted successfully']);

$delete_stmt->close();
$conn->close();

// End output buffering
ob_end_flush();
?>



