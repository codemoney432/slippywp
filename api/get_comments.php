<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

if (!isset($_GET['report_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing report_id parameter']);
    exit;
}

$report_id = intval($_GET['report_id']);

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$query = "SELECT id, comment_text, created_at 
          FROM `{$table_prefix}comments` 
          WHERE report_id = ? 
          ORDER BY created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        'id' => intval($row['id']),
        'comment_text' => $row['comment_text'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode(['comments' => $comments]);

$stmt->close();
$conn->close();
?>






