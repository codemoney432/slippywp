<?php
/**
 * Test script to debug address fetching for specific coordinates
 * Usage: api/test_address.php?lat=40.49627651&lng=-79.91410017
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../api/helpers/intersection_helper.php';

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    echo json_encode(['error' => 'Missing lat/lng parameters']);
    exit;
}

// Fetch the raw Nominatim response
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Slippy Road Conditions App');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
if ($is_local) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
}

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false || $http_code !== 200) {
    echo json_encode([
        'error' => 'Failed to fetch from Nominatim',
        'http_code' => $http_code
    ]);
    exit;
}

$data = json_decode($result, true);

// Test the fetchIntersection function
$address = fetchIntersection($lat, $lng);

echo json_encode([
    'coordinates' => ['lat' => $lat, 'lng' => $lng],
    'raw_nominatim_response' => $data,
    'address_components' => $data['address'] ?? null,
    'display_name' => $data['display_name'] ?? null,
    'fetched_address' => $address,
    'success' => $address !== null
], JSON_PRETTY_PRINT);
?>

