<?php
// Debug script to check why reports aren't showing
header('Content-Type: application/json');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$results = [];

// Get all reports
$all_reports = $conn->query("SELECT id, latitude, longitude, condition_type, location_type, created_at FROM `{$table_prefix}reports` ORDER BY created_at DESC");
$all_data = [];
while ($row = $all_reports->fetch_assoc()) {
    $created = strtotime($row['created_at']);
    $now = time();
    $hours_ago = ($now - $created) / 3600;
    
    $all_data[] = [
        'id' => $row['id'],
        'created_at' => $row['created_at'],
        'hours_ago' => round($hours_ago, 2),
        'is_within_24h' => $hours_ago < 24,
        'condition' => $row['condition_type'],
        'location' => $row['location_type']
    ];
}
$results['all_reports'] = $all_data;

// Check 24-hour filter
$twenty_four_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
$results['filter_time'] = $twenty_four_hours_ago;
$results['current_time'] = date('Y-m-d H:i:s');

// Test the actual query
$test_query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, created_at
              FROM `{$table_prefix}reports`
              WHERE created_at >= ?
              ORDER BY created_at DESC
              LIMIT 20";
$test_stmt = $conn->prepare($test_query);
if ($test_stmt) {
    $test_stmt->bind_param("s", $twenty_four_hours_ago);
    if ($test_stmt->execute()) {
        $test_result = $test_stmt->get_result();
        $filtered_reports = [];
        while ($row = $test_result->fetch_assoc()) {
            $filtered_reports[] = $row;
        }
        $results['filtered_reports_count'] = count($filtered_reports);
        $results['filtered_reports'] = $filtered_reports;
    } else {
        $results['query_error'] = $test_stmt->error;
    }
    $test_stmt->close();
}

$conn->close();

echo json_encode($results, JSON_PRETTY_PRINT);
?>






