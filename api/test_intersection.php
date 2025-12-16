<?php
// Test endpoint to debug intersection API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    echo json_encode(['error' => 'Missing lat/lng parameters']);
    exit;
}

// Use Nominatim reverse geocoding
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

$options = [
    'http' => [
        'header' => "User-Agent: Slippy Road Conditions App\r\n",
        'timeout' => 10
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === false) {
    echo json_encode([
        'error' => 'Failed to fetch location data',
        'url' => $url
    ]);
    exit;
}

$data = json_decode($result, true);

echo json_encode([
    'raw_response' => $data,
    'has_address' => isset($data['address']),
    'address_keys' => isset($data['address']) ? array_keys($data['address']) : [],
    'display_name' => $data['display_name'] ?? null
], JSON_PRETTY_PRINT);
?>

