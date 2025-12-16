<?php
// Diagnostic script to test database connection and table structure
header('Content-Type: application/json');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

$conn = getDBConnection();
if (!$conn) {
    echo json_encode([
        'error' => 'Database connection failed',
        'details' => 'Check config/database.php for correct credentials'
    ]);
    exit;
}

$results = [];

// Check if database exists
$results['database_connected'] = true;

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE '{$table_prefix}reports'");
$results['table_exists'] = ($table_check && $table_check->num_rows > 0);

if ($results['table_exists']) {
    // Check table structure
    $columns = $conn->query("SHOW COLUMNS FROM `{$table_prefix}reports`");
    $column_names = [];
    while ($row = $columns->fetch_assoc()) {
        $column_names[] = $row['Field'];
    }
    $results['columns'] = $column_names;
    $results['has_location_type'] = in_array('location_type', $column_names);
    
    // Try a test query
    $test_query = $conn->query("SELECT COUNT(*) as count FROM `{$table_prefix}reports`");
    if ($test_query) {
        $row = $test_query->fetch_assoc();
        $results['record_count'] = $row['count'];
    }
} else {
    $results['error'] = 'Table "reports" does not exist. Run database/schema.sql to create it.';
}

$conn->close();

echo json_encode($results, JSON_PRETTY_PRINT);
?>






