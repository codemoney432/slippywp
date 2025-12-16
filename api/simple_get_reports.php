<?php
// Simplified version to test basic query
header('Content-Type: application/json');
ob_start();

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

$conn = getDBConnection();
if (!$conn) {
    ob_clean();
    echo json_encode(['error' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Get parameters
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$longitude = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 0.05;

// Extended filter for testing
$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

// Simple query without vote/comment counts first
if ($latitude !== null && $longitude !== null) {
    $query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, created_at,
              (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
              cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
              sin(radians(latitude)))) AS distance
              FROM `{$table_prefix}reports`
              WHERE created_at >= ?
              HAVING distance < ?
              ORDER BY created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        ob_clean();
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt->bind_param("dddsdi", $latitude, $longitude, $latitude, $seven_days_ago, $radius, $limit);
} else {
    $query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, created_at
              FROM `{$table_prefix}reports`
              WHERE created_at >= ?
              ORDER BY created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        ob_clean();
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt->bind_param("si", $seven_days_ago, $limit);
}

if (!$stmt->execute()) {
    ob_clean();
    echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    ob_end_flush();
    exit;
}

$result = $stmt->get_result();
$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = [
        'id' => intval($row['id']),
        'latitude' => floatval($row['latitude']),
        'longitude' => floatval($row['longitude']),
        'condition_type' => $row['condition_type'],
        'location_type' => isset($row['location_type']) ? $row['location_type'] : 'road',
        'submitter_name' => $row['submitter_name'],
        'created_at' => $row['created_at'],
        'upvotes' => 0,
        'downvotes' => 0,
        'comment_count' => 0
    ];
}

ob_clean();
echo json_encode(['reports' => $reports, 'count' => count($reports)]);
$stmt->close();
$conn->close();
ob_end_flush();
?>






