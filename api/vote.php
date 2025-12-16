<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['report_id']) || !isset($input['vote_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $report_id = intval($input['report_id']);
    $vote_type = $input['vote_type'];
    
    // Validate vote type
    if (!in_array($vote_type, ['up', 'down'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid vote type']);
        exit;
    }
    
    // Get IP address
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip_address && strpos($ip_address, ',') !== false) {
        $ip_address = trim(explode(',', $ip_address)[0]);
    }
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $conn = getDBConnection();
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Check if report exists
    $check_stmt = $conn->prepare("SELECT id FROM `{$table_prefix}reports` WHERE id = ?");
    $check_stmt->bind_param("i", $report_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    $check_stmt->close();
    
    // Try to insert vote (will fail if duplicate IP for same report)
    $stmt = $conn->prepare("INSERT INTO `{$table_prefix}votes` (report_id, vote_type, ip_address, user_agent) VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type), created_at = CURRENT_TIMESTAMP");
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database prepare failed: ' . $conn->error]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("isss", $report_id, $vote_type, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        // Get updated vote counts
        $count_stmt = $conn->prepare("SELECT 
            SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END) as upvotes,
            SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END) as downvotes
            FROM `{$table_prefix}votes` WHERE report_id = ?");
        $count_stmt->bind_param("i", $report_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $counts = $count_result->fetch_assoc();
        $count_stmt->close();
        
        echo json_encode([
            'success' => true,
            'upvotes' => intval($counts['upvotes'] ?? 0),
            'downvotes' => intval($counts['downvotes'] ?? 0)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save vote: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>






