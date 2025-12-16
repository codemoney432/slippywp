<?php
/**
 * Backfill script to populate intersection data for existing reports
 * 
 * This script fetches all reports with NULL intersection values and
 * populates them using the Nominatim API.
 * 
 * Usage:
 *   - Via web browser: 
 *     http://yoursite.com/api/backfill_intersections.php
 *     http://yoursite.com/api/backfill_intersections.php?batch_size=10&offset=0
 *     http://yoursite.com/api/backfill_intersections.php?dry_run=true (test mode)
 *   - Via command line: php api/backfill_intersections.php
 * 
 * Parameters:
 *   - batch_size: Number of reports to process (default: 10 for web, can be increased)
 *   - offset: Start from this report number (for resuming)
 *   - dry_run: Set to 'true' to test without updating database
 * 
 * Note: 
 *   - Nominatim requires max 1 request per second, so this script includes delays
 *   - For web requests, PHP may timeout after 30-60 seconds. Use smaller batch_size or run via CLI
 *   - The script will provide a 'next_url' to continue processing remaining reports
 */

// Suppress any output before JSON (if running via web)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers if running via web
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

// Increase timeout for web requests (if running via web)
if (php_sapi_name() !== 'cli') {
    set_time_limit(300); // 5 minutes max execution time
}

// Start output buffering
ob_start();

require_once '../config/database.php';
require_once '../api/helpers/intersection_helper.php';

$table_prefix = DB_TABLE_PREFIX;

// Configuration
$delay_between_requests = 1.1; // Seconds (slightly more than 1 to be safe)
$batch_size = isset($_GET['batch_size']) ? intval($_GET['batch_size']) : 10; // Smaller default batch for web requests (10 instead of 100)
$dry_run = isset($_GET['dry_run']) && $_GET['dry_run'] === 'true'; // Set ?dry_run=true to test without updating
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0; // Allow resuming from a specific offset

$conn = getDBConnection();
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Count reports without intersection
$count_query = "SELECT COUNT(*) as count FROM `{$table_prefix}reports` WHERE intersection IS NULL";
$count_result = $conn->query($count_query);
$total_count = 0;
if ($count_result) {
    $row = $count_result->fetch_assoc();
    $total_count = intval($row['count'] ?? 0);
}

if ($total_count === 0) {
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'All reports already have intersection data',
        'processed' => 0,
        'updated' => 0,
        'failed' => 0
    ]);
    ob_end_flush();
    exit;
}

// Get reports without intersection (limit to batch size with offset)
$query = "SELECT id, latitude, longitude FROM `{$table_prefix}reports` WHERE intersection IS NULL ORDER BY id ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    $conn->close();
    ob_end_flush();
    exit;
}

$stmt->bind_param("ii", $batch_size, $offset);

if (!$stmt->execute()) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
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
        'longitude' => floatval($row['longitude'])
    ];
}
$stmt->close();

$processed = 0;
$updated = 0;
$failed = 0;
$errors = [];
$successful_ids = [];

// Process each report
foreach ($reports as $report) {
    $processed++;
    
    // Flush output for web requests to show progress (if not CLI)
    if (php_sapi_name() !== 'cli') {
        // For web requests, we can't easily show progress, but we can ensure output isn't buffered
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    
    // Fetch address (formerly called intersection)
    try {
        $intersection = fetchIntersection($report['latitude'], $report['longitude']);
        // Log for debugging failed cases
        if (empty($intersection)) {
            error_log("Backfill: Report ID {$report['id']} returned empty address for lat={$report['latitude']}, lng={$report['longitude']}");
        }
    } catch (Exception $e) {
        $intersection = null;
        $errors[] = "Report ID {$report['id']}: Exception - " . $e->getMessage();
        error_log("Backfill: Exception for Report ID {$report['id']}: " . $e->getMessage());
    }
    
    if ($intersection !== null && $intersection !== '') {
        if (!$dry_run) {
            // Update the report with the intersection
            $update_stmt = $conn->prepare("UPDATE `{$table_prefix}reports` SET intersection = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $intersection, $report['id']);
                if ($update_stmt->execute()) {
                    $updated++;
                    $successful_ids[] = $report['id'];
                } else {
                    $failed++;
                    $errors[] = "Report ID {$report['id']}: Update failed - " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $failed++;
                $errors[] = "Report ID {$report['id']}: Prepare failed - " . $conn->error;
            }
        } else {
            // Dry run - just count what would be updated
            $updated++;
        }
    } else {
        $failed++;
        $errors[] = "Report ID {$report['id']}: Could not fetch intersection (returned null or empty)";
    }
    
    // Respect Nominatim rate limits (1 request per second)
    // Only delay if not the last item
    if ($processed < count($reports)) {
        usleep($delay_between_requests * 1000000); // Convert seconds to microseconds
    }
}

// Clear any unexpected output
ob_clean();

$response = [
    'success' => true,
    'total_without_intersection' => $total_count,
    'processed' => $processed,
    'updated' => $updated,
    'failed' => $failed,
    'dry_run' => $dry_run,
    'offset' => $offset,
    'next_offset' => $offset + $processed
];

if ($dry_run) {
    $response['message'] = 'DRY RUN: No data was actually updated. Remove ?dry_run=true to perform actual updates.';
}

if (!empty($errors) && count($errors) <= 10) {
    $response['errors'] = $errors;
} elseif (!empty($errors)) {
    $response['error_count'] = count($errors);
    $response['sample_errors'] = array_slice($errors, 0, 10);
    $response['message'] = 'Too many errors to display. Check error log for details.';
}

if ($processed < $total_count) {
    $remaining = $total_count - ($offset + $processed);
    $response['remaining'] = $remaining;
    $next_url = "?offset=" . ($offset + $processed) . "&batch_size={$batch_size}";
    if ($dry_run) {
        $next_url .= "&dry_run=true";
    }
    $response['next_url'] = $next_url;
    $response['message'] = ($response['message'] ?? '') . " Processed {$processed} reports (offset {$offset}). {$remaining} remaining. Visit: " . basename(__FILE__) . $next_url . " to continue.";
}

if ($updated > 0 && !$dry_run) {
    $response['successful_report_ids'] = $successful_ids;
}

echo json_encode($response, JSON_PRETTY_PRINT);

$conn->close();

// End output buffering
ob_end_flush();
?>

