<?php
/**
 * Helper function to fetch intersection/address from coordinates using Nominatim
 * Returns the intersection string or null on failure
 */
function fetchIntersection($lat, $lng) {
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
        $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
                    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
        
        if ($is_local) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($result === false) {
            error_log("Intersection fetch cURL error for lat={$lat}, lng={$lng}: {$curl_error}");
            return null;
        } elseif ($http_code !== 200) {
            error_log("Intersection fetch HTTP error for lat={$lat}, lng={$lng}: {$http_code}");
            return null;
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
            error_log("Intersection fetch file_get_contents failed for lat={$lat}, lng={$lng}");
            return null;
        }
    }
    
    $data = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Intersection fetch JSON decode error for lat={$lat}, lng={$lng}: " . json_last_error_msg());
        return null;
    }
    
    // Check if Nominatim returned an error
    if (isset($data['error'])) {
        error_log("Nominatim API error for lat={$lat}, lng={$lng}: " . $data['error']);
        return null;
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
    
    return $intersection ? $intersection : null;
}
?>


