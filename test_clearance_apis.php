<?php
// Test Script for Phase 3A Clearance APIs
// This script tests all the clearance management APIs we've built

echo "<h1>🧪 Phase 3A Clearance APIs Test</h1>\n";
echo "<p>Testing all clearance management APIs...</p>\n";

// Test configuration
$baseUrl = 'http://localhost/OnlineClearanceWebsite/api';
$testResults = [];

// Helper function to make API calls
function testApi($endpoint, $method = 'GET', $data = null, $description = '') {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/json',
            'content' => $data ? json_encode($data) : null
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    $httpCode = $http_response_header[0] ?? 'Unknown';
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

// Test 1: Clearance Periods API
echo "<h2>📅 Test 1: Clearance Periods API</h2>\n";

// Test GET periods
$result = testApi('/clearance/periods.php', 'GET', null, 'Get all clearance periods');
$testResults[] = $result;
echo "<h3>GET /clearance/periods.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test 2: Clearance Requirements API
echo "<h2>📋 Test 2: Clearance Requirements API</h2>\n";

// Test GET requirements
$result = testApi('/clearance/requirements.php', 'GET', null, 'Get all clearance requirements');
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
$result = testApi('/clearance/requirements.php?department_id=1', 'GET', null, 'Get requirements for department 1');
$testResults[] = $result;
echo "<h3>GET /clearance/requirements.php?department_id=1</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test 3: Clearance Applications API
echo "<h2>📝 Test 3: Clearance Applications API</h2>\n";

// Test GET applications
$result = testApi('/clearance/applications.php', 'GET', null, 'Get all clearance applications');
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
$result = testApi('/clearance/applications.php?status=pending&user_type=student', 'GET', null, 'Get pending student applications');
$testResults[] = $result;
echo "<h3>GET /clearance/applications.php?status=pending&user_type=student</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test 4: Clearance Status API
echo "<h2>📊 Test 4: Clearance Status API</h2>\n";

// Test GET status (will fail without application_id, but that's expected)
$result = testApi('/clearance/status.php', 'GET', null, 'Get clearance status (should fail - no application_id)');
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
$result = testApi('/clearance/status.php?application_id=1', 'GET', null, 'Get clearance status for application 1');
$testResults[] = $result;
echo "<h3>GET /clearance/status.php?application_id=1</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Expected:</strong> May fail if no applications exist yet</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

// Test 5: Authentication Check
echo "<h2>🔐 Test 5: Authentication Status</h2>\n";

// Test auth verify
$result = testApi('/auth/verify.php', 'GET', null, 'Verify authentication status');
$testResults[] = $result;
echo "<h3>GET /auth/verify.php</h3>\n";
echo "<p><strong>Description:</strong> {$result['description']}</p>\n";
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>\n";
echo "<p><strong>Success:</strong> " . ($result['success'] ? '✅' : '❌') . "</p>\n";
if ($result['response']) {
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>\n";
}
echo "<hr>\n";

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
echo "<h2>💡 Recommendations</h2>\n";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>\n";

if ($failedTests > 0) {
    echo "<h3>Issues Found:</h3>\n";
    echo "<ul>\n";
    
    foreach ($testResults as $result) {
        if (!$result['success']) {
            echo "<li><strong>{$result['endpoint']}</strong>: ";
            if (strpos($result['http_code'], '401') !== false) {
                echo "Authentication required - you may need to log in first</li>\n";
            } elseif (strpos($result['http_code'], '404') !== false) {
                echo "Endpoint not found - check if the API file exists</li>\n";
            } elseif (strpos($result['http_code'], '500') !== false) {
                echo "Server error - check database connection and PHP errors</li>\n";
            } else {
                echo "HTTP {$result['http_code']} - check the response for details</li>\n";
            }
        }
    }
    
    echo "</ul>\n";
} else {
    echo "<h3>🎉 All Tests Passed!</h3>\n";
    echo "<p>All Phase 3A APIs are working correctly. You can proceed to Phase 3B.</p>\n";
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
