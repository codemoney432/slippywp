<?php
// Test script for create_report API
// This helps debug submission issues

header('Content-Type: application/json');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

// Test database connection
$conn = getDBConnection();
if (!$conn) {
    echo json_encode([
        'error' => 'Database connection failed',
        'details' => 'Check config/database.php'
    ]);
    exit;
}

$results = [];

// Check if reports table exists
$table_check = $conn->query("SHOW TABLES LIKE '{$table_prefix}reports'");
$results['table_exists'] = ($table_check && $table_check->num_rows > 0);

if ($results['table_exists']) {
    // Check table structure
    $columns = $conn->query("SHOW COLUMNS FROM `{$table_prefix}reports`");
    $column_info = [];
    while ($row = $columns->fetch_assoc()) {
        $column_info[] = [
            'name' => $row['Field'],
            'type' => $row['Type'],
            'null' => $row['Null'],
            'default' => $row['Default']
        ];
    }
    $results['columns'] = $column_info;
    
    // Check if location_type column exists
    $has_location_type = false;
    foreach ($column_info as $col) {
        if ($col['name'] === 'location_type') {
            $has_location_type = true;
            break;
        }
    }
    $results['has_location_type'] = $has_location_type;
    
    // Check condition_type ENUM values
    foreach ($column_info as $col) {
        if ($col['name'] === 'condition_type') {
            $results['condition_type_enum'] = $col['type'];
            break;
        }
    }
    
    // Test a prepared statement
    $test_stmt = $conn->prepare("INSERT INTO `{$table_prefix}reports` (latitude, longitude, condition_type, location_type, submitter_name) VALUES (?, ?, ?, ?, ?)");
    if ($test_stmt) {
        $results['prepare_test'] = 'success';
        $test_stmt->close();
    } else {
        $results['prepare_test'] = 'failed';
        $results['prepare_error'] = $conn->error;
    }
    
    // Try a test insert (then delete it)
    $test_lat = 40.4406;
    $test_lng = -79.9959;
    $test_condition = 'ice';
    $test_location = 'road';
    $test_name = null;
    
    $test_insert = $conn->prepare("INSERT INTO `{$table_prefix}reports` (latitude, longitude, condition_type, location_type, submitter_name) VALUES (?, ?, ?, ?, ?)");
    if ($test_insert) {
        $test_insert->bind_param("ddsss", $test_lat, $test_lng, $test_condition, $test_location, $test_name);
        if ($test_insert->execute()) {
            $test_id = $conn->insert_id;
            $results['insert_test'] = 'success';
            $results['test_id'] = $test_id;
            // Delete the test record
            $conn->query("DELETE FROM `{$table_prefix}reports` WHERE id = $test_id");
        } else {
            $results['insert_test'] = 'failed';
            $results['insert_error'] = $test_insert->error;
        }
        $test_insert->close();
    } else {
        $results['insert_test'] = 'prepare_failed';
        $results['insert_error'] = $conn->error;
    }
} else {
    $results['error'] = 'Reports table does not exist. Run database/schema.sql or database/setup_complete.sql';
}

$conn->close();

echo json_encode($results, JSON_PRETTY_PRINT);
?>






