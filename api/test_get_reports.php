<?php
// Test script for get_reports API
header('Content-Type: application/json');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$results = [];

// Check total reports in database
$total_query = $conn->query("SELECT COUNT(*) as total FROM `{$table_prefix}reports`");
$total_row = $total_query->fetch_assoc();
$results['total_reports_in_db'] = intval($total_row['total']);

// Check reports from last 24 hours
$twenty_four_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
$recent_query = $conn->query("SELECT COUNT(*) as recent FROM `{$table_prefix}reports` WHERE created_at >= '$twenty_four_hours_ago'");
$recent_row = $recent_query->fetch_assoc();
$results['reports_last_24h'] = intval($recent_row['recent']);

// Get sample of all reports
$all_reports = $conn->query("SELECT id, latitude, longitude, condition_type, location_type, created_at FROM `{$table_prefix}reports` ORDER BY created_at DESC LIMIT 5");
$sample_reports = [];
while ($row = $all_reports->fetch_assoc()) {
    $sample_reports[] = [
        'id' => $row['id'],
        'created_at' => $row['created_at'],
        'age_hours' => round((time() - strtotime($row['created_at'])) / 3600, 1),
        'condition' => $row['condition_type'],
        'location' => $row['location_type']
    ];
}
$results['sample_reports'] = $sample_reports;

// Test the actual get_reports query
$test_query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, created_at
              FROM `{$table_prefix}reports`
              WHERE created_at >= ?
              ORDER BY created_at DESC
              LIMIT ?";
$test_stmt = $conn->prepare($test_query);
if ($test_stmt) {
    $test_stmt->bind_param("si", $twenty_four_hours_ago, $limit = 20);
    if ($test_stmt->execute()) {
        $test_result = $test_stmt->get_result();
        $test_reports = [];
        while ($row = $test_result->fetch_assoc()) {
            $test_reports[] = $row;
        }
        $results['test_query_success'] = true;
        $results['test_query_count'] = count($test_reports);
    } else {
        $results['test_query_success'] = false;
        $results['test_query_error'] = $test_stmt->error;
    }
    $test_stmt->close();
} else {
    $results['test_query_success'] = false;
    $results['test_query_error'] = $conn->error;
}

$conn->close();

echo json_encode($results, JSON_PRETTY_PRINT);
?>






