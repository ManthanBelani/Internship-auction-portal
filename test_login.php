<?php
// Test login API
echo "Testing login API...\n\n";

$url = 'http://localhost:8000/api/users/login';
$data = json_encode([
    'email' => 'admin@auction.com',
    'password' => 'admin123'
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
print_r($response);
echo "\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if (isset($result['token'])) {
        echo "\n✓ Login successful!\n";
        echo "Token: " . substr($result['token'], 0, 20) . "...\n";
        echo "User: {$result['user']['name']}\n";
        echo "Role: {$result['user']['role']}\n";
    }
} else {
    echo "\n✗ Login failed!\n";
}
