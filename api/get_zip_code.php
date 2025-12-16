<?php
// API endpoint to lookup US zip codes from local database
// Returns lat/lng coordinates for a given zip code

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Start output buffering
ob_start();

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

// Get zip code parameter
$zipCode = isset($_GET['zip']) ? trim($_GET['zip']) : '';

if (empty($zipCode)) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Zip code parameter is required'
    ]);
    exit;
}

// Extract just the 5-digit zip code (handle 5+4 format)
$zipOnly = preg_replace('/[^0-9]/', '', $zipCode);
$zipOnly = substr($zipOnly, 0, 5); // Get first 5 digits

if (strlen($zipOnly) !== 5 || !ctype_digit($zipOnly)) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid zip code format. Must be 5 digits.'
    ]);
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

// Query the zip code from local database
$table_name = $table_prefix . 'zip_codes';
$stmt = $conn->prepare("SELECT zip_code, latitude, longitude, city, state, state_name, county FROM {$table_name} WHERE zip_code = ? LIMIT 1");

if (!$stmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database query preparation failed: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$stmt->bind_param("s", $zipOnly);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Build display name similar to Nominatim format
    $displayParts = [];
    if (!empty($row['city'])) {
        $displayParts[] = $row['city'];
    }
    if (!empty($row['state'])) {
        $displayParts[] = $row['state'];
    }
    if (!empty($row['zip_code'])) {
        $displayParts[] = $row['zip_code'];
    }
    $displayName = !empty($displayParts) ? implode(', ', $displayParts) : $zipOnly;
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'zip_code' => $row['zip_code'],
        'latitude' => floatval($row['latitude']),
        'longitude' => floatval($row['longitude']),
        'city' => $row['city'],
        'state' => $row['state'],
        'state_name' => $row['state_name'],
        'county' => $row['county'],
        'display_name' => $displayName
    ]);
} else {
    ob_clean();
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Zip code not found in database'
    ]);
}

$stmt->close();
$conn->close();
exit;

