<?php

/**
 * Test SP Search API with Token Validation
 * This script tests all SP Search API endpoints with proper token authentication
 */

// Base URL for API
$baseUrl = 'http://127.0.0.1:8000/api/v1';

// Test customer credentials (you'll need to create a customer and get token first)
$testPhone = '9876543210';
$testToken = 'test_token_123'; // This should be a real token from customer login

echo "🔍 Testing SP Search API with Token Validation\n";
echo "=" . str_repeat("=", 50) . "\n\n";

/**
 * Helper function to make API requests
 */
function makeRequest($url, $method = 'GET', $data = null, $phone = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            // Add phone and token to data if provided
            if ($phone && $token) {
                $data['phone'] = $phone;
                $data['token'] = $token;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            // Add phone and token to data if provided
            if ($phone && $token) {
                $data['phone'] = $phone;
                $data['token'] = $token;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'GET' && ($phone && $token)) {
        // For GET requests, add phone and token as query parameters
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url .= $separator . "phone=" . urlencode($phone) . "&token=" . urlencode($token);
        curl_setopt($ch, CURLOPT_URL, $url);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => json_decode($response, true),
        'http_code' => $httpCode
    ];
}

echo "📋 Note: You need to first create a customer and get a valid token\n";
echo "You can do this by:\n";
echo "1. POST /api/v1/customer/send-otp with phone number\n";
echo "2. POST /api/v1/customer/verify-otp with phone and OTP\n";
echo "3. Use the returned token for these tests\n\n";

// Test 1: Search Options (without token - should fail)
echo "1️⃣  Testing Search Options without token (should fail)\n";
$result = makeRequest("$baseUrl/sp-search/options");
echo "Status: " . $result['http_code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Search Options (with token - should work)
echo "2️⃣  Testing Search Options with token\n";
$result = makeRequest("$baseUrl/sp-search/options", 'GET', null, $testPhone, $testToken);
echo "Status: " . $result['http_code'] . "\n";
if ($result['response']['success'] ?? false) {
    echo "✅ Success! Found " . count($result['response']['data']['categories']) . " categories\n";
} else {
    echo "❌ Failed: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 3: SP Search (without token - should fail)
echo "3️⃣  Testing SP Search without token (should fail)\n";
$searchData = [
    'latitude' => 22.5726,
    'longitude' => 88.3639,
    'radius_km' => 10,
    'category_id' => 1,
    'subcategory_id' => 1,
    'scheduled_at' => date('Y-m-d H:i:s', strtotime('+3 hours'))
];
$result = makeRequest("$baseUrl/sp-search/", 'POST', $searchData);
echo "Status: " . $result['http_code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 4: SP Search (with token - should work)
echo "4️⃣  Testing SP Search with token\n";
$result = makeRequest("$baseUrl/sp-search/", 'POST', $searchData, $testPhone, $testToken);
echo "Status: " . $result['http_code'] . "\n";
if ($result['response']['success'] ?? false) {
    $spCount = count($result['response']['data']['sp_ids'] ?? []);
    echo "✅ Success! Found $spCount service providers\n";
    if ($spCount > 0) {
        echo "First SP ID: " . ($result['response']['data']['sp_ids'][0]['sp_id'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ Failed: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 5: SP Status (without token - should fail)
echo "5️⃣  Testing SP Status without token (should fail)\n";
$result = makeRequest("$baseUrl/sp-search/1/status");
echo "Status: " . $result['http_code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 6: SP Status (with token - should work)
echo "6️⃣  Testing SP Status with token\n";
$result = makeRequest("$baseUrl/sp-search/1/status", 'GET', null, $testPhone, $testToken);
echo "Status: " . $result['http_code'] . "\n";
if ($result['response']['success'] ?? false) {
    echo "✅ Success! SP Status retrieved\n";
    echo "SP Name: " . ($result['response']['data']['name'] ?? 'N/A') . "\n";
    echo "Is Online: " . (($result['response']['data']['is_online'] ?? false) ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Failed: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 7: Update SP Status (without token - should fail)
echo "7️⃣  Testing Update SP Status without token (should fail)\n";
$statusData = ['is_online' => true];
$result = makeRequest("$baseUrl/sp-search/1/status", 'PUT', $statusData);
echo "Status: " . $result['http_code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 8: Update SP Status (with token - should work)
echo "8️⃣  Testing Update SP Status with token\n";
$result = makeRequest("$baseUrl/sp-search/1/status", 'PUT', $statusData, $testPhone, $testToken);
echo "Status: " . $result['http_code'] . "\n";
if ($result['response']['success'] ?? false) {
    echo "✅ Success! SP Status updated\n";
} else {
    echo "❌ Failed: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 9: SP Location (without token - should fail)
echo "9️⃣  Testing SP Location without token (should fail)\n";
$result = makeRequest("$baseUrl/sp-search/1/location");
echo "Status: " . $result['http_code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 10: SP Location (with token - should work)
echo "🔟 Testing SP Location with token\n";
$result = makeRequest("$baseUrl/sp-search/1/location", 'GET', null, $testPhone, $testToken);
echo "Status: " . $result['http_code'] . "\n";
if ($result['response']['success'] ?? false) {
    echo "✅ Success! SP Location retrieved\n";
    echo "Latitude: " . ($result['response']['data']['latitude'] ?? 'N/A') . "\n";
    echo "Longitude: " . ($result['response']['data']['longitude'] ?? 'N/A') . "\n";
} else {
    echo "❌ Failed: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 11: Update SP Location (with token - should work)
echo "1️⃣1️⃣ Testing Update SP Location with token\n";
$locationData = [
    'latitude' => 22.5726,
    'longitude' => 88.3639,
    'address' => 'Test Address, Kolkata',
    'coverage_radius' => 15
];
$result = makeRequest("$baseUrl/sp-search/1/location", 'PUT', $locationData, $testPhone, $testToken);
echo "Status: " . $result['http_code'] . "\n";
if ($result['response']['success'] ?? false) {
    echo "✅ Success! SP Location updated\n";
} else {
    echo "❌ Failed: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}
echo "\n";

echo "🎯 Test Summary:\n";
echo "- All endpoints now require phone and token parameters\n";
echo "- Requests without valid tokens will return 400/401/404 errors\n";
echo "- Token validation uses AuthHelper::validateToken() method\n";
echo "- Tokens are validated against 'customers' table using 'phone' field\n\n";

echo "📝 To get a valid token for testing:\n";
echo "1. Use customer registration/login endpoints\n";
echo "2. Update \$testPhone and \$testToken variables in this script\n";
echo "3. Run this script again with valid credentials\n";

?>