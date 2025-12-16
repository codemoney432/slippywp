<?php
// Test distance calculation
header('Content-Type: application/json');

// Report location from database
$report_lat = 40.44368109;
$report_lng = -79.92802620;

// User location from the error log
$user_lat = 40.4455424;
$user_lng = -79.9080448;

// Calculate distance using Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return $distance;
}

$distance = calculateDistance($user_lat, $user_lng, $report_lat, $report_lng);

$results = [
    'user_location' => ['lat' => $user_lat, 'lng' => $user_lng],
    'report_location' => ['lat' => $report_lat, 'lng' => $report_lng],
    'distance_km' => round($distance, 2),
    'distance_meters' => round($distance * 1000, 2),
    'current_radius' => 0.05,
    'radius_km' => 0.05,
    'radius_meters' => 50,
    'is_within_radius' => $distance < 0.05,
    'note' => 'Current radius of 0.05 means only 50 meters! That is VERY small.'
];

echo json_encode($results, JSON_PRETTY_PRINT);
?>






