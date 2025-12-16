<?php
// Suppress any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first, before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start output buffering to catch any unexpected output
ob_start();

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

// Cloudflare Turnstile configuration
define('TURNSTILE_SITE_KEY', 'YOUR_SITE_KEY_HERE'); // Replace with your Turnstile site key
define('TURNSTILE_SECRET_KEY', 'YOUR_SECRET_KEY_HERE'); // Replace with your Turnstile secret key

function verifyTurnstile($token) {
    if (empty($token)) {
        return false;
    }
    
    $data = array(
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token
    );
    
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    $response = json_decode($result, true);
    
    return isset($response['success']) && $response['success'] === true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['latitude']) || !isset($input['longitude']) || !isset($input['condition_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $latitude = floatval($input['latitude']);
    $longitude = floatval($input['longitude']);
    $condition_type = $input['condition_type'];
    $location_type = isset($input['location_type']) ? $input['location_type'] : 'road';
    $submitter_name = isset($input['submitter_name']) ? trim($input['submitter_name']) : null;
    $turnstile_token = isset($input['turnstile_token']) ? $input['turnstile_token'] : '';
    
    // Validate condition type
    $valid_conditions = ['ice', 'slush', 'snow', 'water'];
    if (!in_array($condition_type, $valid_conditions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid condition type']);
        exit;
    }
    
    // Validate location type
    $valid_location_types = ['road', 'sidewalk'];
    if (!in_array($location_type, $valid_location_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid location type']);
        exit;
    }
    
    // Validate coordinates
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid coordinates']);
        exit;
    }
    
    // Verify CAPTCHA (optional for development - only verify if token is provided)
    if (!empty($turnstile_token) && !verifyTurnstile($turnstile_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CAPTCHA verification failed']);
        exit;
    }
    
    // Sanitize submitter name (limit to 25 characters)
    if ($submitter_name !== null) {
        $submitter_name = htmlspecialchars($submitter_name, ENT_QUOTES, 'UTF-8');
        if (strlen($submitter_name) > 25) {
            $submitter_name = substr($submitter_name, 0, 25);
        }
    }
    
    $conn = getDBConnection();
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Handle null submitter_name properly - convert empty string to null
    $submitter_name_value = ($submitter_name === null || $submitter_name === '') ? null : $submitter_name;
    
    $stmt = $conn->prepare("INSERT INTO `{$table_prefix}reports` (latitude, longitude, condition_type, location_type, submitter_name) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        http_response_code(500);
        $error_details = $conn->error ? $conn->error : 'Unknown error';
        error_log("Database prepare failed: " . $error_details);
        echo json_encode(['error' => 'Database prepare failed: ' . $error_details]);
        $conn->close();
        exit;
    }
    
    // Bind parameters - use 's' for strings even if NULL (MySQLi handles it)
    $stmt->bind_param("ddsss", $latitude, $longitude, $condition_type, $location_type, $submitter_name_value);
    
    if ($stmt->execute()) {
        $report_id = $conn->insert_id;
        
        // Clear any unexpected output
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'id' => $report_id,
            'message' => 'Report submitted successfully'
        ]);
    } else {
        http_response_code(500);
        $error_msg = 'Failed to save report';
        $error_details = $stmt->error ? $stmt->error : ($conn->error ? $conn->error : 'Unknown error');
        $error_msg .= ': ' . $error_details;
        error_log("Report save failed: " . $error_details);
        
        // Clear any unexpected output
        ob_clean();
        
        echo json_encode(['error' => $error_msg]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    
    // Clear any unexpected output
    ob_clean();
    
    echo json_encode(['error' => 'Method not allowed']);
}

// End output buffering
ob_end_flush();
?>

