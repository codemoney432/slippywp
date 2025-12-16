<?php
/**
 * Helper function to check comment content using OpenAI Moderation API
 * Returns array with 'flagged' (boolean) and 'categories' (array) if flagged
 * 
 * @param string $text The text to moderate
 * @return array ['flagged' => bool, 'categories' => array, 'error' => string|null]
 */
function checkOpenAIModeration($text) {
    // OpenAI API key - should be set in config or environment variable
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : getenv('OPENAI_API_KEY');
    
    if (empty($api_key)) {
        error_log('OpenAI API key not configured');
        return [
            'flagged' => false, // Default to not flagged if API key missing
            'categories' => [],
            'error' => 'OpenAI API key not configured'
        ];
    }
    
    $url = 'https://api.openai.com/v1/moderations';
    
    // Ensure text is clean and properly formatted for moderation
    $text = trim($text);
    
    $data = [
        'input' => $text
    ];
    
    // Log the text being sent for debugging (remove in production if needed)
    error_log("OpenAI Moderation: Checking text: " . substr($text, 0, 100) . (strlen($text) > 100 ? '...' : ''));
    
    // Use cURL for API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // SSL verification
    $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
                strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
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
        error_log("OpenAI Moderation API cURL error: {$curl_error}");
        return [
            'flagged' => false,
            'categories' => [],
            'error' => 'API request failed: ' . $curl_error
        ];
    }
    
    if ($http_code !== 200) {
        error_log("OpenAI Moderation API HTTP error: {$http_code} - Response: " . substr($result, 0, 500));
        return [
            'flagged' => false,
            'categories' => [],
            'error' => 'API returned HTTP ' . $http_code
        ];
    }
    
    $response = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("OpenAI Moderation API JSON decode error: " . json_last_error_msg());
        return [
            'flagged' => false,
            'categories' => [],
            'error' => 'Invalid JSON response'
        ];
    }
    
    if (!isset($response['results']) || !is_array($response['results']) || empty($response['results'])) {
        error_log("OpenAI Moderation API: Unexpected response format");
        return [
            'flagged' => false,
            'categories' => [],
            'error' => 'Unexpected response format'
        ];
    }
    
    $result_data = $response['results'][0];
    $flagged = isset($result_data['flagged']) && $result_data['flagged'] === true;
    $categories = isset($result_data['categories']) ? $result_data['categories'] : [];
    $category_scores = isset($result_data['category_scores']) ? $result_data['category_scores'] : [];
    
    // Filter to only include categories that are true
    $flagged_categories = [];
    foreach ($categories as $category => $value) {
        if ($value === true) {
            $flagged_categories[] = $category;
        }
    }
    
    // Log detailed moderation result for debugging
    error_log("OpenAI Moderation Response: " . json_encode([
        'flagged' => $flagged,
        'categories' => $categories,
        'category_scores' => $category_scores,
        'flagged_categories' => $flagged_categories
    ]));
    
    if ($flagged) {
        error_log("OpenAI Moderation: Text FLAGGED. Categories: " . implode(', ', $flagged_categories));
    } else {
        error_log("OpenAI Moderation: Text NOT flagged (approved)");
        // Log category scores even if not flagged to see what OpenAI detected
        if (!empty($category_scores)) {
            $high_scores = [];
            foreach ($category_scores as $category => $score) {
                if ($score > 0.1) { // Log any score above 0.1
                    $high_scores[$category] = round($score, 3);
                }
            }
            if (!empty($high_scores)) {
                error_log("OpenAI Moderation: Non-zero category scores (but not flagged): " . json_encode($high_scores));
            }
        }
    }
    
    return [
        'flagged' => $flagged,
        'categories' => $flagged_categories,
        'error' => null
    ];
}
?>

