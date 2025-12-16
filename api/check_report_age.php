<?php
// Quick check to see if reports are being filtered by age
header('Content-Type: application/json');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$results = [];

// Get the report from the screenshot
$report_query = $conn->query("SELECT id, created_at, TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_ago FROM `{$table_prefix}reports` WHERE id = 1");
if ($report_query && $report_query->num_rows > 0) {
    $report = $report_query->fetch_assoc();
    $results['report_1'] = [
        'id' => $report['id'],
        'created_at' => $report['created_at'],
        'hours_ago' => intval($report['hours_ago']),
        'is_within_24h' => intval($report['hours_ago']) < 24
    ];
}

// Check 24-hour filter
$twenty_four_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
$results['filter_cutoff'] = $twenty_four_hours_ago;
$results['current_time'] = date('Y-m-d H:i:s');

// Count reports
$total = $conn->query("SELECT COUNT(*) as count FROM `{$table_prefix}reports`")->fetch_assoc()['count'];
$recent = $conn->query("SELECT COUNT(*) as count FROM `{$table_prefix}reports` WHERE created_at >= '$twenty_four_hours_ago'")->fetch_assoc()['count'];

$results['total_reports'] = intval($total);
$results['reports_within_24h'] = intval($recent);

$conn->close();

echo json_encode($results, JSON_PRETTY_PRINT);
?>






