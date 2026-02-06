<?php
// Test login API with detailed output
$url = 'http://localhost:8000/api/users/login';
$data = [
    'email' => 'admin@auction.com',
    'password' => 'admin123'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== LOGIN API TEST ===\n\n";
echo "HTTP Code: $httpCode\n\n";

$decoded = json_decode($response, true);
if ($decoded) {
    echo "Response Data:\n";
    echo "- Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
    
    if (isset($decoded['data'])) {
        echo "- User ID: " . ($decoded['data']['userId'] ?? 'N/A') . "\n";
        echo "- Email: " . ($decoded['data']['email'] ?? 'N/A') . "\n";
        echo "- Name: " . ($decoded['data']['name'] ?? 'N/A') . "\n";
        echo "- Role: " . ($decoded['data']['role'] ?? 'N/A') . "\n";
        echo "- Status: " . ($decoded['data']['status'] ?? 'N/A') . "\n";
        echo "- Token: " . (isset($decoded['data']['token']) ? 'Present' : 'Missing') . "\n";
    }
} else {
    echo "Raw Response:\n$response\n";
}
