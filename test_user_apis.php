<?php
// Test User Management APIs
echo "<h1>🧪 User Management API Testing</h1>";

// Test configuration
$baseUrl = 'http://localhost/OnlineClearanceWebsite/api/users';
$testData = [
    'username' => 'testuser_' . time(),
    'email' => 'testuser_' . time() . '@gosti.edu.ph',
    'password' => 'testpass123',
    'first_name' => 'Test',
    'last_name' => 'User',
    'role_id' => 1
];

echo "<h2>📋 Test Data</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'decoded' => json_decode($response, true)
    ];
}

// Test 1: Get all users (should work without authentication for demo)
echo "<h2>🔍 Test 1: Get All Users</h2>";
$result = makeRequest($baseUrl . '/read.php');
echo "<strong>HTTP Code:</strong> " . $result['http_code'] . "<br>";
echo "<strong>Response:</strong> <pre>" . $result['response'] . "</pre><br>";

// Test 2: Get all roles
echo "<h2>🔍 Test 2: Get All Roles</h2>";
$result = makeRequest($baseUrl . '/roles.php');
echo "<strong>HTTP Code:</strong> " . $result['http_code'] . "<br>";
echo "<strong>Response:</strong> <pre>" . $result['response'] . "</pre><br>";

// Test 3: Create user (will fail without authentication)
echo "<h2>🔍 Test 3: Create User (Expected to fail - no auth)</h2>";
$result = makeRequest($baseUrl . '/create.php', 'POST', $testData);
echo "<strong>HTTP Code:</strong> " . $result['http_code'] . "<br>";
echo "<strong>Response:</strong> <pre>" . $result['response'] . "</pre><br>";

// Test 4: Update user (will fail without authentication)
echo "<h2>🔍 Test 4: Update User (Expected to fail - no auth)</h2>";
$updateData = ['user_id' => 1, 'first_name' => 'Updated Name'];
$result = makeRequest($baseUrl . '/update.php', 'PUT', $updateData);
echo "<strong>HTTP Code:</strong> " . $result['http_code'] . "<br>";
echo "<strong>Response:</strong> <pre>" . $result['response'] . "</pre><br>";

// Test 5: Delete user (will fail without authentication)
echo "<h2>🔍 Test 5: Delete User (Expected to fail - no auth)</h2>";
$result = makeRequest($baseUrl . '/delete.php?user_id=1', 'DELETE');
echo "<strong>HTTP Code:</strong> " . $result['http_code'] . "<br>";
echo "<strong>Response:</strong> <pre>" . $result['response'] . "</pre><br>";

// Test 6: Password operations (will fail without authentication)
echo "<h2>🔍 Test 6: Password Operations (Expected to fail - no auth)</h2>";
$passwordData = ['current_password' => 'oldpass', 'new_password' => 'newpass123'];
$result = makeRequest($baseUrl . '/password.php', 'POST', $passwordData);
echo "<strong>HTTP Code:</strong> " . $result['http_code'] . "<br>";
echo "<strong>Response:</strong> <pre>" . $result['response'] . "</pre><br>";

echo "<hr>";
echo "<h2>📊 Test Summary</h2>";
echo "<p><strong>✅ Expected Results:</strong></p>";
echo "<ul>";
echo "<li>Test 1 & 2: Should work (GET requests)</li>";
echo "<li>Test 3-6: Should fail with 401 (Authentication required)</li>";
echo "</ul>";

echo "<p><strong>🔧 Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Login to get authentication session</li>";
echo "<li>Test APIs with proper authentication</li>";
echo "<li>Create real users in the system</li>";
echo "</ul>";

echo "<p><strong>🌐 API Endpoints Created:</strong></p>";
echo "<ul>";
echo "<li><code>POST /api/users/create.php</code> - Create new user</li>";
echo "<li><code>GET /api/users/read.php</code> - Get users (with pagination/filtering)</li>";
echo "<li><code>PUT /api/users/update.php</code> - Update user</li>";
echo "<li><code>DELETE /api/users/delete.php</code> - Delete user</li>";
echo "<li><code>GET/POST/PUT /api/users/roles.php</code> - Manage user roles</li>";
echo "<li><code>POST/PUT /api/users/password.php</code> - Password management</li>";
echo "</ul>";
?>
