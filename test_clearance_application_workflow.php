<?php
/**
 * Comprehensive Clearance Application Workflow Test
 * Tests the complete clearance application logic with period status and button states
 */

require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h1>🧪 Clearance Application Workflow Test</h1>\n";
    echo "<p><strong>Started:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    
    // Test 1: Check Period Status API
    echo "<h2>📊 Test 1: Period Status API</h2>\n";
    testPeriodStatusAPI();
    
    // Test 2: Check Button Status API
    echo "<h2>🔘 Test 2: Button Status API</h2>\n";
    testButtonStatusAPI();
    
    // Test 3: Test Grace Period Logic
    echo "<h2>⏰ Test 3: Grace Period Logic</h2>\n";
    testGracePeriodLogic();
    
    // Test 4: Test Button State Matrix
    echo "<h2>🎯 Test 4: Button State Matrix</h2>\n";
    testButtonStateMatrix();
    
    // Test 5: Test Real-time Updates
    echo "<h2>🔄 Test 5: Real-time Updates</h2>\n";
    testRealTimeUpdates();
    
    echo "<h2>✅ All Tests Completed</h2>\n";
    echo "<p><strong>Finished:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>\n";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
}

function testPeriodStatusAPI() {
    echo "<h3>Testing Period Status API Endpoints</h3>\n";
    
    // Test period status API
    $url = 'http://localhost/OnlineClearanceWebsite/api/clearance/period_status.php';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p>❌ <strong>Period Status API:</strong> Failed to connect</p>\n";
        return;
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['success'])) {
        echo "<p>✅ <strong>Period Status API:</strong> Connected successfully</p>\n";
        echo "<p>📋 <strong>Period Status:</strong> " . ($data['period_status'] ?? 'Unknown') . "</p>\n";
        echo "<p>📋 <strong>Can Apply:</strong> " . ($data['can_apply'] ? 'Yes' : 'No') . "</p>\n";
        
        if (isset($data['grace_period']) && $data['grace_period']) {
            echo "<p>⏰ <strong>Grace Period:</strong> Active (" . $data['grace_period']['remaining_seconds'] . " seconds remaining)</p>\n";
        } else {
            echo "<p>⏰ <strong>Grace Period:</strong> Not active</p>\n";
        }
    } else {
        echo "<p>❌ <strong>Period Status API:</strong> Invalid response</p>\n";
    }
}

function testButtonStatusAPI() {
    echo "<h3>Testing Button Status API Endpoints</h3>\n";
    
    // Test button status API
    $url = 'http://localhost/OnlineClearanceWebsite/api/clearance/button_status.php';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p>❌ <strong>Button Status API:</strong> Failed to connect</p>\n";
        return;
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['success'])) {
        echo "<p>✅ <strong>Button Status API:</strong> Connected successfully</p>\n";
        echo "<p>📋 <strong>Period Status:</strong> " . ($data['period_status'] ?? 'Unknown') . "</p>\n";
        echo "<p>📋 <strong>Can Apply:</strong> " . ($data['can_apply'] ? 'Yes' : 'No') . "</p>\n";
        
        if (isset($data['button_states']) && is_array($data['button_states'])) {
            echo "<p>🔘 <strong>Button States:</strong> " . count($data['button_states']) . " signatories</p>\n";
            
            $enabledCount = 0;
            foreach ($data['button_states'] as $state) {
                if ($state['button_state']['enabled']) {
                    $enabledCount++;
                }
            }
            echo "<p>🔘 <strong>Enabled Buttons:</strong> $enabledCount</p>\n";
        }
        
        if (isset($data['summary'])) {
            $summary = $data['summary'];
            echo "<p>📊 <strong>Summary:</strong> " . 
                 "Total: " . $summary['total_signatories'] . 
                 ", Approved: " . $summary['approved'] . 
                 ", Rejected: " . $summary['rejected'] . 
                 ", Pending: " . $summary['pending'] . 
                 ", Unapplied: " . $summary['unapplied'] . "</p>\n";
        }
    } else {
        echo "<p>❌ <strong>Button Status API:</strong> Invalid response</p>\n";
    }
}

function testGracePeriodLogic() {
    echo "<h3>Testing Grace Period Logic</h3>\n";
    
    // Test grace period scenarios
    $scenarios = [
        [
            'name' => 'Period Not Started',
            'period_status' => 'Not Started',
            'expected_button_state' => 'disabled',
            'expected_message' => 'Clearance period has not been started yet'
        ],
        [
            'name' => 'Period Ongoing (No Grace)',
            'period_status' => 'Ongoing',
            'grace_period' => null,
            'expected_button_state' => 'enabled',
            'expected_message' => 'You can apply to signatories'
        ],
        [
            'name' => 'Period Ongoing (With Grace)',
            'period_status' => 'Ongoing',
            'grace_period' => ['is_active' => true, 'remaining_seconds' => 120],
            'expected_button_state' => 'disabled',
            'expected_message' => 'Clearance period is in grace period'
        ],
        [
            'name' => 'Period Paused',
            'period_status' => 'Paused',
            'expected_button_state' => 'disabled',
            'expected_message' => 'Applications are disabled'
        ],
        [
            'name' => 'Period Closed',
            'period_status' => 'Closed',
            'expected_button_state' => 'disabled',
            'expected_message' => 'Applications are no longer accepted'
        ]
    ];
    
    foreach ($scenarios as $scenario) {
        echo "<p>🧪 <strong>Testing:</strong> " . $scenario['name'] . "</p>\n";
        
        // Simulate button state determination
        $buttonState = determineButtonStateForTest($scenario);
        
        if ($buttonState['enabled'] === ($scenario['expected_button_state'] === 'enabled')) {
            echo "<p>✅ <strong>Button State:</strong> Correct (" . ($buttonState['enabled'] ? 'Enabled' : 'Disabled') . ")</p>\n";
        } else {
            echo "<p>❌ <strong>Button State:</strong> Incorrect (Expected: " . $scenario['expected_button_state'] . ", Got: " . ($buttonState['enabled'] ? 'Enabled' : 'Disabled') . ")</p>\n";
        }
        
        echo "<p>📝 <strong>Message:</strong> " . $buttonState['tooltip'] . "</p>\n";
        echo "<hr>\n";
    }
}

function determineButtonStateForTest($scenario) {
    $periodStatus = $scenario['period_status'];
    $gracePeriod = $scenario['grace_period'] ?? null;
    
    $buttonState = [
        'enabled' => false,
        'text' => '',
        'class' => 'btn-secondary',
        'tooltip' => '',
        'reason' => ''
    ];
    
    switch ($periodStatus) {
        case 'Not Started':
            $buttonState['enabled'] = false;
            $buttonState['text'] = 'Not Available';
            $buttonState['class'] = 'btn-secondary';
            $buttonState['tooltip'] = 'Clearance period has not been started yet';
            $buttonState['reason'] = 'period_not_started';
            break;
            
        case 'Ongoing':
            if ($gracePeriod && $gracePeriod['is_active']) {
                $buttonState['enabled'] = false;
                $buttonState['text'] = 'Grace Period';
                $buttonState['class'] = 'btn-warning';
                $buttonState['tooltip'] = 'Clearance period is in grace period. Please wait.';
                $buttonState['reason'] = 'grace_period';
            } else {
                $buttonState['enabled'] = true;
                $buttonState['text'] = 'Apply';
                $buttonState['class'] = 'btn-primary';
                $buttonState['tooltip'] = 'Click to apply to this signatory';
                $buttonState['reason'] = 'can_apply';
            }
            break;
            
        case 'Paused':
            $buttonState['enabled'] = false;
            $buttonState['text'] = 'Apply';
            $buttonState['class'] = 'btn-secondary';
            $buttonState['tooltip'] = 'Clearance period is paused. Applications are disabled.';
            $buttonState['reason'] = 'period_paused';
            break;
            
        case 'Closed':
            $buttonState['enabled'] = false;
            $buttonState['text'] = 'Not Applied';
            $buttonState['class'] = 'btn-secondary';
            $buttonState['tooltip'] = 'Clearance period has ended. Applications are no longer accepted.';
            $buttonState['reason'] = 'period_closed';
            break;
    }
    
    return $buttonState;
}

function testButtonStateMatrix() {
    echo "<h3>Testing Button State Matrix</h3>\n";
    
    $testMatrix = [
        // [Period Status, Signatory Action, Expected Button State, Expected Text]
        ['Not Started', 'Unapplied', false, 'Not Available'],
        ['Not Started', 'Pending', false, 'Not Available'],
        ['Not Started', 'Approved', false, 'Not Available'],
        ['Not Started', 'Rejected', false, 'Not Available'],
        
        ['Ongoing', 'Unapplied', true, 'Apply'],
        ['Ongoing', 'Pending', false, 'Pending'],
        ['Ongoing', 'Approved', false, 'Approved'],
        ['Ongoing', 'Rejected', true, 'Reapply'],
        
        ['Paused', 'Unapplied', false, 'Apply'],
        ['Paused', 'Pending', false, 'Pending'],
        ['Paused', 'Approved', false, 'Approved'],
        ['Paused', 'Rejected', false, 'Reapply'],
        
        ['Closed', 'Unapplied', false, 'Not Applied'],
        ['Closed', 'Pending', false, 'Pending'],
        ['Closed', 'Approved', false, 'Approved'],
        ['Closed', 'Rejected', false, 'Rejected'],
    ];
    
    $passed = 0;
    $total = count($testMatrix);
    
    foreach ($testMatrix as $test) {
        $periodStatus = $test[0];
        $signatoryAction = $test[1];
        $expectedEnabled = $test[2];
        $expectedText = $test[3];
        
        $buttonState = determineButtonStateForMatrixTest($periodStatus, $signatoryAction);
        
        $enabledMatch = $buttonState['enabled'] === $expectedEnabled;
        $textMatch = $buttonState['text'] === $expectedText;
        
        if ($enabledMatch && $textMatch) {
            echo "<p>✅ <strong>$periodStatus + $signatoryAction:</strong> " . $buttonState['text'] . " (" . ($buttonState['enabled'] ? 'Enabled' : 'Disabled') . ")</p>\n";
            $passed++;
        } else {
            echo "<p>❌ <strong>$periodStatus + $signatoryAction:</strong> Expected '$expectedText' (" . ($expectedEnabled ? 'Enabled' : 'Disabled') . "), Got '" . $buttonState['text'] . "' (" . ($buttonState['enabled'] ? 'Enabled' : 'Disabled') . ")</p>\n";
        }
    }
    
    echo "<p><strong>Matrix Test Results:</strong> $passed/$total passed</p>\n";
}

function determineButtonStateForMatrixTest($periodStatus, $signatoryAction) {
    $buttonState = [
        'enabled' => false,
        'text' => '',
        'class' => 'btn-secondary',
        'tooltip' => '',
        'reason' => ''
    ];
    
    switch ($periodStatus) {
        case 'Not Started':
            $buttonState['enabled'] = false;
            $buttonState['text'] = 'Not Available';
            $buttonState['class'] = 'btn-secondary';
            $buttonState['tooltip'] = 'Clearance period has not been started yet';
            $buttonState['reason'] = 'period_not_started';
            break;
            
        case 'Ongoing':
            switch ($signatoryAction) {
                case 'Unapplied':
                    $buttonState['enabled'] = true;
                    $buttonState['text'] = 'Apply';
                    $buttonState['class'] = 'btn-primary';
                    $buttonState['tooltip'] = 'Click to apply to this signatory';
                    $buttonState['reason'] = 'can_apply';
                    break;
                    
                case 'Pending':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Pending';
                    $buttonState['class'] = 'btn-warning';
                    $buttonState['tooltip'] = 'Application is pending approval';
                    $buttonState['reason'] = 'pending_approval';
                    break;
                    
                case 'Approved':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Approved';
                    $buttonState['class'] = 'btn-success';
                    $buttonState['tooltip'] = 'Application has been approved';
                    $buttonState['reason'] = 'approved';
                    break;
                    
                case 'Rejected':
                    $buttonState['enabled'] = true;
                    $buttonState['text'] = 'Reapply';
                    $buttonState['class'] = 'btn-danger';
                    $buttonState['tooltip'] = 'Click to reapply after rejection';
                    $buttonState['reason'] = 'can_reapply';
                    break;
            }
            break;
            
        case 'Paused':
            switch ($signatoryAction) {
                case 'Unapplied':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Apply';
                    $buttonState['class'] = 'btn-secondary';
                    $buttonState['tooltip'] = 'Clearance period is paused. Applications are disabled.';
                    $buttonState['reason'] = 'period_paused';
                    break;
                    
                case 'Pending':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Pending';
                    $buttonState['class'] = 'btn-warning';
                    $buttonState['tooltip'] = 'Application is pending approval';
                    $buttonState['reason'] = 'pending_approval';
                    break;
                    
                case 'Approved':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Approved';
                    $buttonState['class'] = 'btn-success';
                    $buttonState['tooltip'] = 'Application has been approved';
                    $buttonState['reason'] = 'approved';
                    break;
                    
                case 'Rejected':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Reapply';
                    $buttonState['class'] = 'btn-secondary';
                    $buttonState['tooltip'] = 'Clearance period is paused. Reapplication will be enabled when period resumes.';
                    $buttonState['reason'] = 'period_paused_reapply';
                    break;
            }
            break;
            
        case 'Closed':
            $buttonState['enabled'] = false;
            switch ($signatoryAction) {
                case 'Unapplied':
                    $buttonState['text'] = 'Not Applied';
                    $buttonState['class'] = 'btn-secondary';
                    $buttonState['tooltip'] = 'Clearance period has ended. Applications are no longer accepted.';
                    break;
                    
                case 'Pending':
                    $buttonState['text'] = 'Pending';
                    $buttonState['class'] = 'btn-warning';
                    $buttonState['tooltip'] = 'Application is pending approval';
                    break;
                    
                case 'Approved':
                    $buttonState['text'] = 'Approved';
                    $buttonState['class'] = 'btn-success';
                    $buttonState['tooltip'] = 'Application has been approved';
                    break;
                    
                case 'Rejected':
                    $buttonState['text'] = 'Rejected';
                    $buttonState['class'] = 'btn-danger';
                    $buttonState['tooltip'] = 'Application was rejected';
                    break;
            }
            $buttonState['reason'] = 'period_closed';
            break;
    }
    
    return $buttonState;
}

function testRealTimeUpdates() {
    echo "<h3>Testing Real-time Update Components</h3>\n";
    
    // Test grace period manager
    echo "<p>🧪 <strong>Testing Grace Period Manager:</strong></p>\n";
    
    // Simulate grace period countdown
    $remainingSeconds = 300; // 5 minutes
    $formattedTime = formatTime($remainingSeconds);
    
    if ($formattedTime === '05:00') {
        echo "<p>✅ <strong>Time Formatting:</strong> Correct ($formattedTime)</p>\n";
    } else {
        echo "<p>❌ <strong>Time Formatting:</strong> Incorrect (Expected: 05:00, Got: $formattedTime)</p>\n";
    }
    
    // Test grace period scenarios
    $gracePeriodScenarios = [
        ['seconds' => 0, 'expected' => '00:00'],
        ['seconds' => 30, 'expected' => '00:30'],
        ['seconds' => 60, 'expected' => '01:00'],
        ['seconds' => 90, 'expected' => '01:30'],
        ['seconds' => 300, 'expected' => '05:00'],
        ['seconds' => 3661, 'expected' => '61:01'],
    ];
    
    foreach ($gracePeriodScenarios as $scenario) {
        $formatted = formatTime($scenario['seconds']);
        if ($formatted === $scenario['expected']) {
            echo "<p>✅ <strong>Time Format ($scenario[seconds]s):</strong> $formatted</p>\n";
        } else {
            echo "<p>❌ <strong>Time Format ($scenario[seconds]s):</strong> Expected $scenario[expected], Got $formatted</p>\n";
        }
    }
    
    echo "<p>🔄 <strong>Real-time Updates:</strong> Components ready for testing</p>\n";
}

function formatTime($seconds) {
    if ($seconds <= 0) return '00:00';
    
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    return sprintf('%02d:%02d', $minutes, $remainingSeconds);
}
?>
