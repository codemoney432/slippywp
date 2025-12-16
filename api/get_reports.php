<?php
// Suppress any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first, before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Start output buffering to catch any unexpected output
ob_start();

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

// Set timezone to UTC to ensure consistent timestamp handling
date_default_timezone_set('UTC');

// Get parameters
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$longitude = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 96.56; // Default 60 miles radius (in kilometers, 60 miles = 96.56 km)
$strict_24h = isset($_GET['strict_24h']) && $_GET['strict_24h'] === 'true'; // For map: always enforce 24-hour filter

$conn = getDBConnection();
if (!$conn) {
    ob_clean(); 
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Filter: Check if there are at least 20 reports in the last 24 hours
// Use MySQL's DATE_SUB to avoid timezone issues
$use_24_hour_filter = $strict_24h; // If strict_24h is true, always use filter

// First, count reports in the last 24 hours (only if not strict mode)
if (!$strict_24h) {
if ($latitude !== null && $longitude !== null) {
    // Count reports within radius from last 24 hours (using MySQL DATE_SUB)
    $count_query = "SELECT COUNT(*) as count
                    FROM (
                        SELECT id,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                        sin(radians(latitude)))) AS distance
                        FROM `{$table_prefix}reports`
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        HAVING distance < ?
                    ) AS filtered_reports";
    
    $count_stmt = $conn->prepare($count_query);
    if ($count_stmt) {
        $count_stmt->bind_param("dddd", $latitude, $longitude, $latitude, $radius);
        if ($count_stmt->execute()) {
            $count_result = $count_stmt->get_result();
            if ($count_result) {
                $count_row = $count_result->fetch_assoc();
                $recent_count = intval($count_row['count'] ?? 0);
                $use_24_hour_filter = ($recent_count >= $limit);
            }
        }
        $count_stmt->close();
    }
} else {
    // Count reports from last 24 hours globally (using MySQL DATE_SUB)
    $count_query = "SELECT COUNT(*) as count FROM `{$table_prefix}reports` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $count_stmt = $conn->prepare($count_query);
    if ($count_stmt) {
        if ($count_stmt->execute()) {
            $count_result = $count_stmt->get_result();
            if ($count_result) {
                $count_row = $count_result->fetch_assoc();
                $recent_count = intval($count_row['count'] ?? 0);
                $use_24_hour_filter = ($recent_count >= $limit);
            }
        }
        $count_stmt->close();
    }
}
} // End of if (!$strict_24h)

// If coordinates provided, get reports within radius, otherwise get most recent
if ($latitude !== null && $longitude !== null) {
    // Use Haversine formula for distance calculation (simplified for small distances)
    if ($use_24_hour_filter) {
        // Show only reports from last 24 hours (using MySQL DATE_SUB)
        // Exclude reports older than 24 hours (created_at must be >= 24 hours ago)
        $query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, intersection, 
                  UNIX_TIMESTAMP(created_at) as created_at_utc, created_at,
                  (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                  cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                  sin(radians(latitude)))) AS distance
                  FROM `{$table_prefix}reports`
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  HAVING distance < ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
            $conn->close();
            ob_end_flush();
            exit;
        }
        // Parameters: latitude (1st ?), longitude (2nd ?), latitude (3rd ?), radius (4th ?), limit (5th ?)
        $stmt->bind_param("ddddi", $latitude, $longitude, $latitude, $radius, $limit);
    } else {
        // Show most recent reports regardless of age
        $query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, intersection, 
                  UNIX_TIMESTAMP(created_at) as created_at_utc, created_at,
                  (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                  cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                  sin(radians(latitude)))) AS distance
                  FROM `{$table_prefix}reports`
                  HAVING distance < ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
            $conn->close();
            ob_end_flush();
            exit;
        }
        // Parameters: latitude (1st ?), longitude (2nd ?), latitude (3rd ?), radius (4th ?), limit (5th ?)
        $stmt->bind_param("ddddi", $latitude, $longitude, $latitude, $radius, $limit);
    }
} else {
    // Get most recent reports
    if ($use_24_hour_filter) {
        // Show only reports from last 24 hours (using MySQL DATE_SUB)
        // Exclude reports older than 24 hours (created_at must be >= 24 hours ago)
        $query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, intersection, 
                  UNIX_TIMESTAMP(created_at) as created_at_utc, created_at
                  FROM `{$table_prefix}reports`
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  ORDER BY created_at DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
            $conn->close();
            ob_end_flush();
            exit;
        }
        $stmt->bind_param("i", $limit);
    } else {
        // Show most recent reports regardless of age
        $query = "SELECT id, latitude, longitude, condition_type, location_type, submitter_name, intersection, 
                  UNIX_TIMESTAMP(created_at) as created_at_utc, created_at
                  FROM `{$table_prefix}reports`
                  ORDER BY created_at DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
            $conn->close();
            ob_end_flush();
            exit;
        }
        $stmt->bind_param("i", $limit);
    }
}

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

if (!$result) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get query result: ' . $conn->error]);
    $stmt->close();
    $conn->close();
    ob_end_flush();
    exit;
}

$reports = [];
$twenty_four_hours_ago_timestamp = time() - (24 * 60 * 60); // 24 hours ago in Unix timestamp

// Check if status column exists (for backward compatibility) - check once before the loop
$check_status_column = $conn->query("SHOW COLUMNS FROM `{$table_prefix}comments` LIKE 'status'");
$has_status_column = ($check_status_column && $check_status_column->num_rows > 0);

while ($row = $result->fetch_assoc()) {
    // If strict_24h is enabled, filter out reports older than 24 hours in PHP as well (double-check)
    if ($strict_24h) {
        $report_timestamp = strtotime($row['created_at']);
        // Exclude reports that are more than 24 hours old (strictly greater than 24 hours)
        if ($report_timestamp <= $twenty_four_hours_ago_timestamp) {
            // Skip reports that are 24 hours old or older
            continue;
        }
    }
    
    $report_id = intval($row['id']);
    
    // Get vote counts (with error handling in case votes table doesn't exist)
    $upvotes = 0;
    $downvotes = 0;
    $vote_query = "SELECT 
        SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END) as upvotes,
        SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END) as downvotes
        FROM `{$table_prefix}votes` WHERE report_id = ?";
    $vote_stmt = $conn->prepare($vote_query);
    if ($vote_stmt) {
        $vote_stmt->bind_param("i", $report_id);
        if ($vote_stmt->execute()) {
            $vote_result = $vote_stmt->get_result();
            if ($vote_result) {
                $vote_counts = $vote_result->fetch_assoc();
                $upvotes = intval($vote_counts['upvotes'] ?? 0);
                $downvotes = intval($vote_counts['downvotes'] ?? 0);
            }
        }
        $vote_stmt->close();
    }
    
    // Get comment count (only approved comments, with error handling in case comments table doesn't exist)
    $comment_count = 0;
    if ($has_status_column) {
        $comment_query = "SELECT COUNT(*) as comment_count FROM `{$table_prefix}comments` WHERE report_id = ? AND status = 'approved'";
    } else {
        // Fallback for databases without status column (count all comments)
        $comment_query = "SELECT COUNT(*) as comment_count FROM `{$table_prefix}comments` WHERE report_id = ?";
    }
    
    $comment_stmt = $conn->prepare($comment_query);
    if ($comment_stmt) {
        $comment_stmt->bind_param("i", $report_id);
        if ($comment_stmt->execute()) {
            $comment_result = $comment_stmt->get_result();
            if ($comment_result) {
                $comment_row = $comment_result->fetch_assoc();
                $comment_count = intval($comment_row['comment_count'] ?? 0);
            }
        }
        $comment_stmt->close();
    }
    
    // Convert MySQL timestamp to UTC ISO 8601 format using UNIX_TIMESTAMP (always returns UTC)
    // UNIX_TIMESTAMP returns UTC seconds regardless of MySQL timezone settings
    $createdAt = gmdate('Y-m-d\TH:i:s\Z', intval($row['created_at_utc']));
    
    $reports[] = [
        'id' => $report_id,
        'latitude' => floatval($row['latitude']),
        'longitude' => floatval($row['longitude']),
        'condition_type' => $row['condition_type'],
        'location_type' => isset($row['location_type']) ? $row['location_type'] : 'road',
        'submitter_name' => $row['submitter_name'],
        'intersection' => isset($row['intersection']) ? $row['intersection'] : null,
        'created_at' => $createdAt, // ISO 8601 UTC format: 'YYYY-MM-DDTHH:MM:SSZ'
        'upvotes' => $upvotes,
        'downvotes' => $downvotes,
        'comment_count' => $comment_count
    ];
}

// Clear any unexpected output
ob_clean();

echo json_encode(['reports' => $reports]);

$stmt->close();
$conn->close();

// End output buffering
ob_end_flush();
?>

