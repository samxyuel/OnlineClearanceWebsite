<?php
echo "Testing API endpoints...\n";

// Test term status API
echo "1. Testing term status API...\n";
$url = "http://localhost/OnlineClearanceWebsite/api/clearance/term_status.php";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "   ❌ Failed to connect to term status API\n";
} else {
    $data = json_decode($response, true);
    if ($data) {
        echo "   ✅ Term status API response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Invalid JSON response: $response\n";
    }
}

echo "\n2. Testing button status API...\n";
$url = "http://localhost/OnlineClearanceWebsite/api/clearance/button_status.php?faculty_id=1&clearance_type=Faculty";
$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "   ❌ Failed to connect to button status API\n";
} else {
    $data = json_decode($response, true);
    if ($data) {
        echo "   ✅ Button status API response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Invalid JSON response: $response\n";
    }
}

echo "\nDone!\n";
?>
