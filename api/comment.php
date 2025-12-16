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

// OpenAI API key configuration
// Set this in your config/database.php or as environment variable
if (!defined('OPENAI_API_KEY')) {
    // Try to get from environment or config
    $openai_key = getenv('OPENAI_API_KEY');
    if ($openai_key) {
        define('OPENAI_API_KEY', $openai_key);
    }
}

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
    if (!isset($input['report_id']) || !isset($input['comment_text'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        ob_end_flush();
        exit;
    }
    
    $report_id = intval($input['report_id']);
    $comment_text = trim($input['comment_text']);
    $turnstile_token = isset($input['turnstile_token']) ? $input['turnstile_token'] : '';
    
    // Validate comment text
    if (empty($comment_text)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Comment text cannot be empty']);
        ob_end_flush();
        exit;
    }
    
    if (strlen($comment_text) > 500) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Comment text is too long (max 500 characters)']);
        ob_end_flush();
        exit;
    }
    
    // Verify CAPTCHA (optional for development - only verify if token is provided)
    // Uncomment below to require CAPTCHA verification
    /*
    if (empty($turnstile_token) || !verifyTurnstile($turnstile_token)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['error' => 'CAPTCHA verification failed']);
        ob_end_flush();
        exit;
    }
    */
    
    // Sanitize comment text (but keep original for moderation)
    $original_comment_text = $comment_text;
    $comment_text = htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');
    
    // Get IP address
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip_address && strpos($ip_address, ',') !== false) {
        $ip_address = trim(explode(',', $ip_address)[0]);
    }
    
    $conn = getDBConnection();
    if (!$conn) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        ob_end_flush();
        exit;
    }
    
    // Check if report exists
    $check_stmt = $conn->prepare("SELECT id FROM `{$table_prefix}reports` WHERE id = ?");
    $check_stmt->bind_param("i", $report_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['error' => 'Report not found']);
        $check_stmt->close();
        $conn->close();
        ob_end_flush();
        exit;
    }
    $check_stmt->close();
    
    // Insert comment with 'pending' status (will be moderated by cron job)
    $status = 'pending';
    $stmt = $conn->prepare("INSERT INTO `{$table_prefix}comments` (report_id, comment_text, status, ip_address) VALUES (?, ?, ?, ?)");
    
    if (!$stmt) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Database prepare failed: ' . $conn->error]);
        $conn->close();
        ob_end_flush();
        exit;
    }
    
    $stmt->bind_param("isss", $report_id, $comment_text, $status, $ip_address);
    
    if ($stmt->execute()) {
        $comment_id = $conn->insert_id;
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'id' => $comment_id,
            'status' => 'pending',
            'message' => 'Comment has been submitted and is pending moderation'
        ]);
    } else {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save comment: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    ob_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// End output buffering
ob_end_flush();
?>

