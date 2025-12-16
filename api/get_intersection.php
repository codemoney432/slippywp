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

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Missing lat/lng parameters']);
    ob_end_flush();
    exit;
}

// Use Nominatim reverse geocoding to get address/intersection
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

// Try cURL first (more reliable than file_get_contents)
$result = false;
$error_message = '';

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Slippy Road Conditions App');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // SSL verification - disable for local development if certificate issues occur
    // For production, you should enable SSL verification and configure proper certificate bundle
    // Check if we're on localhost/development environment
    $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
                strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
    
    if ($is_local) {
        // Disable SSL verification for local development
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    } else {
        // Enable SSL verification for production
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($result === false) {
        $error_message = 'cURL error: ' . $curl_error;
    } elseif ($http_code !== 200) {
        $error_message = 'HTTP error: ' . $http_code;
        $result = false;
    }
} else {
    // Fallback to file_get_contents if cURL is not available
    $options = [
        'http' => [
            'header' => "User-Agent: Slippy Road Conditions App\r\n",
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        $error_message = 'file_get_contents failed. Check if allow_url_fopen is enabled in php.ini';
    }
}

if ($result === false) {
    ob_clean();
    error_log("Intersection API error for lat={$lat}, lng={$lng}: {$error_message}");
    echo json_encode([
        'success' => false,
        'intersection' => null,
        'error' => 'Failed to fetch location data',
        'debug' => $error_message
    ]);
    ob_end_flush();
    exit;
}

$data = json_decode($result, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    error_log("Nominatim JSON decode error for lat={$lat}, lng={$lng}: " . json_last_error_msg() . " Response: " . substr($result, 0, 500));
    echo json_encode([
        'success' => false,
        'intersection' => null,
        'error' => 'Invalid response from geocoding service'
    ]);
    ob_end_flush();
    exit;
}

// Check if Nominatim returned an error
if (isset($data['error'])) {
    ob_clean();
    error_log("Nominatim API error for lat={$lat}, lng={$lng}: " . $data['error']);
    echo json_encode([
        'success' => false,
        'intersection' => null,
        'error' => $data['error']
    ]);
    ob_end_flush();
    exit;
}

$intersection = null;

if ($data && isset($data['address'])) {
    $address = $data['address'];
    
    // Function to extract highway number from road name
    function extractHighwayNumber($roadName) {
        if (!$roadName) return null;
        
        // Patterns to match highway numbers
        // Interstate: "Interstate 95", "I-95", "I 95", "I95"
        if (preg_match('/\bI[- ]?(\d+)\b/i', $roadName, $matches)) {
            return 'I-' . $matches[1];
        }
        // US Highway: "US Highway 101", "US-101", "US 101", "US101", "U.S. Route 101"
        if (preg_match('/\bU\.?S\.?[- ]?(?:Highway|Route|Hwy|Rt)[- ]?(\d+)\b/i', $roadName, $matches) ||
            preg_match('/\bU\.?S\.?[- ]?(\d+)\b/i', $roadName, $matches)) {
            return 'US-' . $matches[1];
        }
        // State Highway: "State Route 1", "SR-1", "State Highway 1", "SH 1"
        if (preg_match('/\b(?:State|SR|SH)[- ]?(?:Route|Highway|Hwy|Rt)[- ]?(\d+)\b/i', $roadName, $matches) ||
            preg_match('/\bSR[- ]?(\d+)\b/i', $roadName, $matches)) {
            return 'SR-' . $matches[1];
        }
        // County Highway: "County Road 1", "CR-1", "County Route 1"
        if (preg_match('/\b(?:County|CR)[- ]?(?:Road|Route|Hwy|Rt)[- ]?(\d+)\b/i', $roadName, $matches) ||
            preg_match('/\bCR[- ]?(\d+)\b/i', $roadName, $matches)) {
            return 'CR-' . $matches[1];
        }
        
        return null;
    }
    
    // Check if this is a highway/expressway
    $isHighway = false;
    $highwayNumber = null;
    
    // Check for highway ref (most reliable)
    if (isset($address['ref'])) {
        $highwayNumber = $address['ref'];
        $isHighway = true;
    }
    
    // Check if road type indicates highway (check the type field from Nominatim)
    if (isset($data['type']) && in_array(strtolower($data['type']), ['motorway', 'trunk', 'primary'])) {
        $isHighway = true;
    }
    
    // Try to extract highway number from road name if we don't have ref
    if ($isHighway && !$highwayNumber && isset($address['road'])) {
        $highwayNumber = extractHighwayNumber($address['road']);
    }
    
    // If we have a highway number, use it
    if ($isHighway && $highwayNumber) {
        $intersection = $highwayNumber;
        
        // If there's a second road (intersection), add it
        if (isset($address['road2'])) {
            $intersection .= ' & ' . $address['road2'];
        }
    } else {
        // Regular road intersection logic
        if (isset($address['road']) && isset($address['road2'])) {
            $intersection = $address['road'] . ' & ' . $address['road2'];
        } elseif (isset($address['road'])) {
            $intersection = $address['road'];
            if (isset($address['house_number'])) {
                $intersection = $address['house_number'] . ' ' . $intersection;
            }
        } elseif (isset($address['pedestrian'])) {
            $intersection = $address['pedestrian'];
        }
    }
}

// Fallback: if we still don't have an intersection, try to extract from display_name
if (empty($intersection) && isset($data['display_name'])) {
    $parts = explode(',', $data['display_name']);
    // Get the first meaningful part (skip empty strings)
    foreach ($parts as $part) {
        $part = trim($part);
        if (!empty($part)) {
            $intersection = $part;
            break;
        }
    }
}

// Clear any unexpected output
ob_clean();

if ($intersection) {
    echo json_encode([
        'success' => true,
        'intersection' => $intersection,
        'full_address' => $data['display_name'] ?? null
    ]);
} else {
    // Log for debugging (but don't expose to user)
    error_log("Failed to extract intersection for lat={$lat}, lng={$lng}. Response: " . json_encode($data));
    
    echo json_encode([
        'success' => false,
        'intersection' => null,
        'error' => 'Could not determine location'
    ]);
}

// End output buffering
ob_end_flush();
?>

