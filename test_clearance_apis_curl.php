<?php
// Test Script for Phase 3A Clearance APIs using cURL
// This script properly maintains session cookies across API calls

echo "<h1>🧪 Phase 3A Clearance APIs Test (cURL with Session)</h1>\n";
echo "<p>Testing all clearance management APIs with proper session management...</p>\n";

// Test configuration
$baseUrl = 'http://localhost/OnlineClearanceWebsite/api';
$testResults = [];
$cookieFile = tempnam(sys_get_temp_dir(), 'curl_cookies');

// Helper function to make API calls with cURL
function testApiWithCurl($endpoint, $method = 'GET', $data = null, $description = '', $cookieFile = '') {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
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
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'endpoint' => $endpoint,
        'method' => $method,
        'description' => $description,
        'http_code' => $httpCode,
        'response' => $result,
        'success' => $result && isset($result['success']) && $result['success']
    ];
}

// Step 1: Login to get session
echo "<h2>🔐 Step 1: Authentication</h2>\n";

$loginData = [
    'username' => 'admin',
    'password' => 'admin123'
];

$loginResult = testApiWithCurl('/auth/login.php', 'POST', $loginData, 'Login as admin', $cookieFile);

echo "<h3>POST /auth/login.php</h3>\n";
echo "<p><strong>Description:</strong> {$loginResult['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$loginResult['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($loginResult['success'] ? '✅' : '❌') . "</p>\n";

if ($loginResult['success']) {
    echo "<p style='color: green;'>✅ Successfully logged in as admin!</p>\n";
    if ($loginResult['response']) {
        echo "<pre>" . json_encode($loginResult['response'], JSON_PRETTY_PRINT) . "</pre>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Login failed. Cannot proceed with API tests.</p>\n";
    if ($loginResult['response']) {
        echo "<pre>" . json_encode($loginResult['response'], JSON_PRETTY_PRINT) . "</pre>\n";
    }
    exit;
}

echo "<hr>\n";

// Step 2: Test Clearance Periods API
echo "<h2>📅 Step 2: Clearance Periods API</h2>\n";

// Test GET periods
$result = testApiWithCurl('/clearance/periods.php', 'GET', null, 'Get all clearance periods', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/periods.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Step 3: Test Clearance Requirements API
echo "<h2>📋 Step 3: Clearance Requirements API</h2>\n";

// Test GET requirements
$result = testApiWithCurl('/clearance/requirements.php', 'GET', null, 'Get all clearance requirements', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/requirements.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test GET requirements with department filter
$result = testApiWithCurl('/clearance/requirements.php?department_id=1', 'GET', null, 'Get requirements for department 1', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/requirements.php?department_id=1</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Step 4: Test Clearance Applications API
echo "<h2>📝 Step 4: Clearance Applications API</h2>\n";

// Test GET applications
$result = testApiWithCurl('/clearance/applications.php', 'GET', null, 'Get all clearance applications', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/applications.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test GET applications with filters
$result = testApiWithCurl('/clearance/applications.php?status=pending&user_type=student', 'GET', null, 'Get pending student applications', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/applications.php?status=pending&user_type=student</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Step 5: Test Clearance Status API
echo "<h2>📊 Step 5: Clearance Status API</h2>\n";

// Test GET status (will fail without application_id, but that's expected)
$result = testApiWithCurl('/clearance/status.php', 'GET', null, 'Get clearance status (should fail - no application_id)', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/status.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Expected:</strong> Should fail with 400 error (missing application_id)</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test GET status with application_id (will likely fail if no applications exist)
$result = testApiWithCurl('/clearance/status.php?application_id=1', 'GET', null, 'Get clearance status for application 1', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /clearance/status.php?application_id=1</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Expected:</strong> May fail if no applications exist yet</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Step 6: Test Auth Verify
echo "<h2>🔐 Step 6: Authentication Verification</h2>\n";

// Test auth verify
$result = testApiWithCurl('/auth/verify.php', 'GET', null, 'Verify authentication status', $cookieFile);
$testResults[] = $result;
echo "<h3>GET /auth/verify.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Clean up cookie file
unlink($cookieFile);

// Summary
echo "<h2>📈 Test Summary</h2>\n";
$totalTests = count($testResults);
$successfulTests = count(array_filter($testResults, function($r) { return $r['success']; }));
$failedTests = $totalTests - $successfulTests;

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>Overall Results:</h3>\n";
echo "<p><strong>Total Tests:</strong> $totalTests</p>\n";
echo "<p><strong>Successful:</strong> <span style='color: green;'>$successfulTests ✅</span></p>\n";
echo "<p><strong>Failed:</strong> <span style='color: red;'>$failedTests ❌</span></p>\n";
echo "<p><strong>Success Rate:</strong> " . round(($successfulTests / $totalTests) * 100, 1) . "%</p>\n";
echo "</div>\n";

// Detailed results
echo "<h3>Detailed Results:</h3>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #e9ecef;'>\n";
echo "<th>Endpoint</th>\n";
echo "<th>Method</th>\n";
echo "<th>Description</th>\n";
echo "<th>HTTP Code</th>\n";
echo "<th>Status</th>\n";
echo "</tr>\n";

foreach ($testResults as $result) {
    $status = $result['success'] ? '✅ Success' : '❌ Failed';
    $statusColor = $result['success'] ? 'green' : 'red';
    
    echo "<tr>\n";
    echo "<td>{$result['endpoint']}</td>\n";
    echo "<td>{$result['method']}</td>\n";
    echo "<td>{$result['description']}</td>\n";
    echo "<td>{$result['http_code']}</td>\n";
    echo "<td style='color: $statusColor;'>{$status}</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

// Recommendations
echo "<h2>💡 Analysis & Recommendations</h2>\n";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>\n";

if ($successfulTests === $totalTests) {
    echo "<h3>🎉 All Tests Passed!</h3>\n";
    echo "<p>All Phase 3A APIs are working correctly with authentication.</p>\n";
    echo "<p><strong>Ready for Phase 3B!</strong></p>\n";
} else {
    echo "<h3>Issues Found:</h3>\n";
    echo "<ul>\n";
    
    foreach ($testResults as $result) {
        if (!$result['success']) {
            echo "<li><strong>{$result['endpoint']}</strong>: ";
            if ($result['http_code'] == 401) {
                echo "Authentication still required - session may have expired</li>\n";
            } elseif ($result['http_code'] == 403) {
                echo "Permission denied - admin may not have required permissions</li>\n";
            } elseif ($result['http_code'] == 404) {
                echo "Endpoint not found - check if the API file exists</li>\n";
            } elseif ($result['http_code'] == 500) {
                echo "Server error - check database connection and PHP errors</li>\n";
            } else {
                echo "HTTP {$result['http_code']} - check the response for details</li>\n";
            }
        }
    }
    
    echo "</ul>\n";
}

echo "</div>\n";

// Next steps
echo "<h2>🚀 Next Steps</h2>\n";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #17a2b8;'>\n";
echo "<h3>Phase 3B: Signatory Management APIs</h3>\n";
echo "<p>Once all tests pass, we can proceed to build:</p>\n";
echo "<ul>\n";
echo "<li><strong>Signatory Assignment API</strong> - Assign staff to clearance positions</li>\n";
echo "<li><strong>Bulk Operations API</strong> - Process multiple clearances efficiently</li>\n";
echo "<li><strong>Dashboard Data API</strong> - Provide clearance statistics</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
