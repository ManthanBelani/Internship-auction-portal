<?php
/**
 * BidOrbit API Integration Test
 * 
 * Run this script to verify the backend API is working correctly
 * Usage: php test_integration.php
 */

$baseUrl = 'http://localhost:8000';
$testEmail = 'test_' . time() . '@test.com';
$testPassword = 'TestPassword123!';
$testName = 'Test User';

$token = null;
$itemId = null;

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'error' => $error,
    ];
}

function printResult($test, $result) {
    $status = $result['code'] >= 200 && $result['code'] < 300 ? '✅ PASS' : '❌ FAIL';
    echo sprintf("[%s] %s (HTTP %d)\n", $status, $test, $result['code']);
    if ($result['code'] >= 400 && isset($result['body'])) {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
    if ($result['error']) {
        echo "  cURL Error: " . $result['error'] . "\n";
    }
}

echo "========================================\n";
echo "BidOrbit API Integration Test\n";
echo "========================================\n\n";

// Test 1: Health Check
echo "1. Testing Health Endpoint...\n";
$result = makeRequest("$baseUrl/health");
printResult('Health Check', $result);
echo "\n";

// Test 2: User Registration
echo "2. Testing User Registration...\n";
$result = makeRequest("$baseUrl/api/users/register", 'POST', [
    'email' => $testEmail,
    'password' => $testPassword,
    'name' => $testName,
    'role' => 'buyer',
]);
printResult('User Registration', $result);
if ($result['code'] === 201 || $result['code'] === 200) {
    $token = $result['body']['token'] ?? null;
    echo "  Token received: " . ($token ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Test 3: User Login
echo "3. Testing User Login...\n";
$result = makeRequest("$baseUrl/api/users/login", 'POST', [
    'email' => $testEmail,
    'password' => $testPassword,
]);
printResult('User Login', $result);
if ($result['code'] === 200) {
    $token = $result['body']['token'] ?? $token;
    echo "  Token received: " . ($token ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Test 4: Get Profile (requires auth)
if ($token) {
    echo "4. Testing Get Profile...\n";
    $result = makeRequest("$baseUrl/api/users/profile", 'GET', null, $token);
    printResult('Get Profile', $result);
    if ($result['code'] === 200) {
        echo "  User: " . ($result['body']['user']['name'] ?? 'Unknown') . "\n";
    }
    echo "\n";
}

// Test 5: Get Items (public)
echo "5. Testing Get Items...\n";
$result = makeRequest("$baseUrl/api/items");
printResult('Get Items', $result);
echo "\n";

// Test 6: Create Item (requires auth)
if ($token) {
    echo "6. Testing Create Item...\n";
    $result = makeRequest("$baseUrl/api/items", 'POST', [
        'title' => 'Test Auction Item',
        'description' => 'This is a test item for auction',
        'startingPrice' => 10.00,
        'endTime' => date('Y-m-d H:i:s', strtotime('+7 days')),
    ], $token);
    printResult('Create Item', $result);
    $itemId = $result['body']['itemId'] ?? $result['body']['id'] ?? null;
    if ($itemId) {
        echo "  Item ID: $itemId\n";
    }
    echo "\n";
}

// Test 7: Get Single Item
if ($itemId) {
    echo "7. Testing Get Single Item...\n";
    $result = makeRequest("$baseUrl/api/items/$itemId");
    printResult('Get Single Item', $result);
    echo "\n";
}

// Test 8: Add to Watchlist (requires auth)
if ($token && $itemId) {
    echo "8. Testing Add to Watchlist...\n";
    $result = makeRequest("$baseUrl/api/watchlist", 'POST', [
        'itemId' => $itemId,
    ], $token);
    printResult('Add to Watchlist', $result);
    echo "\n";
}

// Test 9: Get Watchlist (requires auth)
if ($token) {
    echo "9. Testing Get Watchlist...\n";
    $result = makeRequest("$baseUrl/api/watchlist", 'GET', null, $token);
    printResult('Get Watchlist', $result);
    echo "\n";
}

// Test 10: Place Bid (requires auth)
if ($token && $itemId) {
    echo "10. Testing Place Bid...\n";
    $result = makeRequest("$baseUrl/api/bids", 'POST', [
        'itemId' => $itemId,
        'amount' => 15.00,
    ], $token);
    printResult('Place Bid', $result);
    echo "\n";
}

// Test 11: Get Bid History
if ($itemId) {
    echo "11. Testing Get Bid History...\n";
    $result = makeRequest("$baseUrl/api/bids/$itemId");
    printResult('Get Bid History', $result);
    echo "\n";
}

// Test 12: Get Seller Stats (requires auth)
if ($token) {
    echo "12. Testing Get Seller Stats...\n";
    $result = makeRequest("$baseUrl/api/seller/stats", 'GET', null, $token);
    printResult('Get Seller Stats', $result);
    echo "\n";
}

// Test 13: Get Seller Listings (requires auth)
if ($token) {
    echo "13. Testing Get Seller Listings...\n";
    $result = makeRequest("$baseUrl/api/seller/listings", 'GET', null, $token);
    printResult('Get Seller Listings', $result);
    echo "\n";
}

// Test 14: Remove from Watchlist (requires auth)
if ($token && $itemId) {
    echo "14. Testing Remove from Watchlist...\n";
    $result = makeRequest("$baseUrl/api/watchlist/$itemId", 'DELETE', null, $token);
    printResult('Remove from Watchlist', $result);
    echo "\n";
}

// Summary
echo "========================================\n";
echo "Test Complete!\n";
echo "========================================\n";
echo "\n";
echo "If all tests passed, the backend API is working correctly.\n";
echo "You can now run the Flutter app to test the full integration.\n";
