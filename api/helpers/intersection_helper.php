<?php
/**
 * Helper function to fetch full address from Nominatim API
 * Returns a formatted address string like "500 Fifth Avenue, New York, NY 10017" or null on failure
 * Note: The "near" prefix is added in the frontend JavaScript, not here
 */
function fetchIntersection($lat, $lng) {
    if ($lat === null || $lng === null) {
        return null;
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
        error_log("Intersection fetch error for lat={$lat}, lng={$lng}: {$error_message}");
        // Return coordinates as fallback instead of null
        return sprintf('%.6f, %.6f', $lat, $lng);
    }
    
    $data = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Nominatim JSON decode error for lat={$lat}, lng={$lng}: " . json_last_error_msg());
        // Return coordinates as fallback instead of null
        return sprintf('%.6f, %.6f', $lat, $lng);
    }
    
    // Check if Nominatim returned an error
    if (isset($data['error'])) {
        error_log("Nominatim API error for lat={$lat}, lng={$lng}: " . $data['error']);
        // Return coordinates as fallback instead of null
        return sprintf('%.6f, %.6f', $lat, $lng);
    }
    
    $addressString = null;
    
    // Always try to use display_name first as it's usually the most complete
    // Then try to build from components for better formatting
    if (isset($data['display_name']) && !empty(trim($data['display_name']))) {
        $displayName = trim($data['display_name']);
        // Remove country if it's at the end (to keep it shorter)
        $displayName = preg_replace('/,\s*(United States|USA|US)$/i', '', $displayName);
        if (!empty($displayName)) {
            $addressString = $displayName;
        }
    }
    
    // Try to build a better formatted address from components if available
    if ($data && isset($data['address'])) {
        $address = $data['address'];
        
        // Build address components
        $parts = [];
        
        // House number (if available)
        $houseNumber = isset($address['house_number']) ? trim($address['house_number']) : null;
        
        // Street/Road name
        $street = null;
        if (isset($address['road'])) {
            $street = trim($address['road']);
        } elseif (isset($address['pedestrian'])) {
            $street = trim($address['pedestrian']);
        } elseif (isset($address['path'])) {
            $street = trim($address['path']);
        }
        
        // For highways, use ref if available
        if (isset($address['ref']) && !empty($address['ref'])) {
            $highwayRef = trim($address['ref']);
            // Format highway reference (e.g., "I-95", "US-101")
            if (preg_match('/^I[- ]?(\d+)$/i', $highwayRef, $matches)) {
                $street = 'I-' . $matches[1];
            } elseif (preg_match('/^US[- ]?(\d+)$/i', $highwayRef, $matches)) {
                $street = 'US-' . $matches[1];
            } else {
                $street = $highwayRef;
            }
        }
        
        // Build street address
        if ($street) {
            if ($houseNumber) {
                // Check if house_number might be a range (e.g., "500-510")
                $parts[] = $houseNumber . ' ' . $street;
            } else {
                $parts[] = $street;
            }
        } elseif ($houseNumber) {
            // If we have house number but no street, use house number alone
            $parts[] = $houseNumber;
        }
        
        // City/Town - try multiple fields
        $city = null;
        if (isset($address['city'])) {
            $city = trim($address['city']);
        } elseif (isset($address['town'])) {
            $city = trim($address['town']);
        } elseif (isset($address['village'])) {
            $city = trim($address['village']);
        } elseif (isset($address['municipality'])) {
            $city = trim($address['municipality']);
        } elseif (isset($address['suburb'])) {
            $city = trim($address['suburb']);
        } elseif (isset($address['neighbourhood'])) {
            $city = trim($address['neighbourhood']);
        }
        
        // State/Province
        $state = null;
        if (isset($address['state'])) {
            $state = trim($address['state']);
        } elseif (isset($address['province'])) {
            $state = trim($address['province']);
        }
        
        // Postal code
        $postcode = null;
        if (isset($address['postcode'])) {
            $postcode = trim($address['postcode']);
        }
        
        // Build full address string
        $addressParts = [];
        
        // Add street address if we have it
        if (!empty($parts)) {
            $addressParts[] = implode(' ', $parts);
        }
        
        // Add city (even if we don't have a street, city is useful)
        if ($city) {
            $addressParts[] = $city;
        }
        
        // Add state and zip together if both available
        if ($state && $postcode) {
            $addressParts[] = $state . ' ' . $postcode;
        } elseif ($state) {
            $addressParts[] = $state;
        } elseif ($postcode) {
            $addressParts[] = $postcode;
        }
        
        // If we have any address parts, build the address string
        // Accept even if we only have city/state (no street)
        if (!empty($addressParts)) {
            $formattedAddress = implode(', ', $addressParts);
            // Use formatted address if it's more specific than display_name, or if display_name wasn't available
            if (empty($addressString) || strlen($formattedAddress) < strlen($addressString)) {
                $addressString = $formattedAddress;
            }
        }
    }
    
    // Final fallback: if we still don't have anything, try to extract location from other fields
    if (empty($addressString) && isset($data['address'])) {
        $address = $data['address'];
        $fallbackParts = [];
        
        // Try various location fields
        $locationFields = ['county', 'state_district', 'region', 'state', 'country'];
        foreach ($locationFields as $field) {
            if (isset($address[$field]) && !empty(trim($address[$field]))) {
                $fallbackParts[] = trim($address[$field]);
                break; // Use first available
            }
        }
        
        if (!empty($fallbackParts)) {
            $addressString = implode(', ', $fallbackParts);
        }
    }
    
    // Last resort: use coordinates if nothing else works (better than null)
    if (empty($addressString)) {
        // Format coordinates nicely
        $addressString = sprintf('%.6f, %.6f', $lat, $lng);
        error_log("Could not extract address for lat={$lat}, lng={$lng}. Using coordinates. Available data: " . json_encode($data));
    }
    
    // Always return something - never return null
    return trim($addressString);
}
?>
