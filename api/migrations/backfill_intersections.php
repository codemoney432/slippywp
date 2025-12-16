<?php
/**
 * Backfill script to populate intersection data for existing reports
 * 
 * WARNING: This script will make API calls to Nominatim for each report without intersection.
 * Nominatim has a rate limit of 1 request per second, so this script will take time.
 * 
 * Usage: Run this script from command line or via browser (not recommended for large datasets)
 * 
 * Example: php backfill_intersections.php
 */

require_once '../config/database.php';
require_once '../helpers/intersection_helper.php';

$table_prefix = DB_TABLE_PREFIX;

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed\n");
}

// Get all reports without intersection
$query = "SELECT id, latitude, longitude FROM `{$table_prefix}reports` WHERE intersection IS NULL OR intersection = ''";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error . "\n");
}

$total = $result->num_rows;
$processed = 0;
$successful = 0;
$failed = 0;

echo "Found {$total} reports without intersection data.\n";
echo "Starting backfill (this will take approximately " . ($total * 1.1) . " seconds due to rate limits)...\n\n";

while ($row = $result->fetch_assoc()) {
    $report_id = $row['id'];
    $lat = $row['latitude'];
    $lng = $row['longitude'];
    
    echo "[{$processed}/{$total}] Processing report #{$report_id}... ";
    
    // Fetch intersection
    $intersection = fetchIntersection($lat, $lng);
    
    if ($intersection) {
        // Update the report
        $update_stmt = $conn->prepare("UPDATE `{$table_prefix}reports` SET intersection = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $intersection, $report_id);
            if ($update_stmt->execute()) {
                echo "✓ Updated: {$intersection}\n";
                $successful++;
            } else {
                echo "✗ Update failed: " . $update_stmt->error . "\n";
                $failed++;
            }
            $update_stmt->close();
        } else {
            echo "✗ Prepare failed: " . $conn->error . "\n";
            $failed++;
        }
    } else {
        echo "✗ Could not fetch intersection\n";
        $failed++;
    }
    
    $processed++;
    
    // Respect Nominatim rate limit: 1 request per second
    // Wait 1.1 seconds between requests to be safe
    if ($processed < $total) {
        sleep(1);
        usleep(100000); // Additional 0.1 seconds
    }
}

echo "\n";
echo "Backfill complete!\n";
echo "Total processed: {$processed}\n";
echo "Successful: {$successful}\n";
echo "Failed: {$failed}\n";

$conn->close();
?>


