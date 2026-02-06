<?php
// Test login API - show raw response
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

file_put_contents('login_response.txt', "HTTP Code: $httpCode\n\nRaw Response:\n$response");
echo "Response saved to login_response.txt\n";
