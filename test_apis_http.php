<?php
echo "Testing APIs with HTTP requests...\n\n";

// Test 1: Term Status API
echo "1. Testing Term Status API:\n";
$url = "http://localhost/OnlineClearanceWebsite/api/clearance/term_status.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=test"); // Add a test session

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   Raw Response: " . substr($response, 0, 200) . "\n";
    }
} else {
    echo "   No response received\n";
}

echo "\n2. Testing Button Status API:\n";
$url = "http://localhost/OnlineClearanceWebsite/api/clearance/button_status.php?faculty_id=1&clearance_type=Faculty";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=test");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   Raw Response: " . substr($response, 0, 200) . "\n";
    }
} else {
    echo "   No response received\n";
}

echo "\nDone!\n";
?>
