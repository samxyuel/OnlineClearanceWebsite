<?php
// Demo Integration Page for EligibleForGraduationModal
// This page demonstrates how to integrate the graduation modal into an existing student management system

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic authentication check (you would use your actual auth system)
require_once __DIR__ . '/../../includes/classes/Auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graduation Management Demo - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/components/header.php'; ?>

    <!-- Main Content Area -->
    <main class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-layout">
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-graduation-cap"></i> Graduation Management Demo</h2>
                            <p>Demonstration of the Eligible for Graduation modal integration</p>
                        </div>

                        <!-- Integration Instructions -->
                        <div class="info-card" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-left: 4px solid #007bff;">
                            <h3><i class="fas fa-info-circle"></i> Integration Instructions</h3>
                            <p>This page demonstrates how to integrate the <code>EligibleForGraduationModal.php</code> into your existing student management pages.</p>
                            
                            <h4>Steps to integrate:</h4>
                            <ol>
                                <li>Include the modal file: <code>&lt;?php include '../../Modals/EligibleForGraduationModal.php'; ?&gt;</code></li>
                                <li>Add a button to trigger the modal: <code>&lt;button onclick="openEligibleForGraduationModal()"&gt;Manage Graduation&lt;/button&gt;</code></li>
                                <li>Listen for graduation status updates: <code>document.addEventListener('graduation-status-updated', handleGraduationUpdate);</code></li>
                            </ol>
                        </div>

                        <!-- Demo Controls -->
                        <div class="demo-controls" style="margin-bottom: 30px;">
                            <div class="action-buttons">
                                <button class="btn btn-primary" onclick="openEligibleForGraduationModal()">
                                    <i class="fas fa-graduation-cap"></i> Open Graduation Modal
                                </button>
                                <button class="btn btn-secondary" onclick="showIntegrationCode()">
                                    <i class="fas fa-code"></i> Show Integration Code
                                </button>
                                <button class="btn btn-info" onclick="testAPI()">
                                    <i class="fas fa-api"></i> Test API Endpoints
                                </button>
                            </div>
                        </div>

                        <!-- API Test Results -->
                        <div class="api-test-results" id="apiTestResults" style="display: none;">
                            <h3><i class="fas fa-flask"></i> API Test Results</h3>
                            <div id="apiTestContent"></div>
                        </div>

                        <!-- Integration Code Display -->
                        <div class="integration-code" id="integrationCode" style="display: none;">
                            <h3><i class="fas fa-code"></i> Integration Code Examples</h3>
                            
                            <div class="code-section">
                                <h4>1. Include the Modal</h4>
                                <pre><code>&lt;?php include '../../Modals/EligibleForGraduationModal.php'; ?&gt;</code></pre>
                            </div>

                            <div class="code-section">
                                <h4>2. Add Button to Your Page</h4>
                                <pre><code>&lt;button class="btn btn-primary" onclick="openEligibleForGraduationModal()"&gt;
    &lt;i class="fas fa-graduation-cap"&gt;&lt;/i&gt; Manage Graduation
&lt;/button&gt;</code></pre>
                            </div>

                            <div class="code-section">
                                <h4>3. Listen for Updates (Optional)</h4>
                                <pre><code>document.addEventListener('graduation-status-updated', function(event) {
    console.log('Graduation status updated:', event.detail);
    // Refresh your student list or show notification
    loadStudentsData(); // Your existing function
});</code></pre>
                            </div>

                            <div class="code-section">
                                <h4>4. Required API Endpoints</h4>
                                <p>The modal uses these API endpoints:</p>
                                <ul>
                                    <li><code>api/users/get_eligible_students.php</code> - Get 4th year students</li>
                                    <li><code>api/users/update_graduation_status.php</code> - Update graduation status</li>
                                    <li><code>api/departments/list.php</code> - Get departments for filtering</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Modal Status -->
                        <div class="modal-status" id="modalStatus" style="margin-top: 30px;">
                            <h3><i class="fas fa-info"></i> Modal Status</h3>
                            <p>Modal events will be logged here when you interact with the graduation modal.</p>
                            <div id="statusLog" style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 100px; font-family: monospace; font-size: 0.9em;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Include the Graduation Modal -->
    <?php include '../../Modals/EligibleForGraduationModal.php'; ?>

    <!-- Toast Notifications -->
    <div id="toast-container"></div>

    <script>
        // Log modal events
        function logEvent(message) {
            const log = document.getElementById('statusLog');
            const timestamp = new Date().toLocaleTimeString();
            log.innerHTML += `[${timestamp}] ${message}\n`;
            log.scrollTop = log.scrollHeight;
        }

        // Show integration code
        function showIntegrationCode() {
            const codeDiv = document.getElementById('integrationCode');
            codeDiv.style.display = codeDiv.style.display === 'none' ? 'block' : 'none';
        }

        // Test API endpoints
        async function testAPI() {
            const resultsDiv = document.getElementById('apiTestResults');
            const contentDiv = document.getElementById('apiTestContent');
            
            resultsDiv.style.display = 'block';
            contentDiv.innerHTML = '<p>Testing API endpoints...</p>';
            
            try {
                // Test get eligible students API
                const response = await fetch('../../api/users/get_eligible_students.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                contentDiv.innerHTML = `
                    <div class="test-result ${data.success ? 'success' : 'error'}">
                        <h4>get_eligible_students.php</h4>
                        <p>Status: ${data.success ? 'Success' : 'Error'}</p>
                        <p>Message: ${data.message}</p>
                        ${data.success ? `<p>Found ${data.data.students.length} eligible students</p>` : ''}
                    </div>
                `;
                
                logEvent(`API Test: ${data.success ? 'Success' : 'Failed'} - ${data.message}`);
                
            } catch (error) {
                contentDiv.innerHTML = `
                    <div class="test-result error">
                        <h4>API Test Failed</h4>
                        <p>Error: ${error.message}</p>
                    </div>
                `;
                logEvent(`API Test Error: ${error.message}`);
            }
        }

        // Listen for graduation status updates
        document.addEventListener('graduation-status-updated', function(event) {
            logEvent(`Graduation status updated: ${event.detail.updated_count} students processed`);
            showToast(`Graduation status updated for ${event.detail.updated_count} students`, 'success');
        });

        // Override the modal's open function to add logging
        const originalOpenModal = window.openEligibleForGraduationModal;
        window.openEligibleForGraduationModal = function() {
            logEvent('Opening Eligible for Graduation modal');
            originalOpenModal();
        };

        // Override the modal's close function to add logging
        const originalCloseModal = window.closeEligibleForGraduationModal;
        window.closeEligibleForGraduationModal = function() {
            logEvent('Closing Eligible for Graduation modal');
            originalCloseModal();
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            logEvent('Graduation Management Demo page loaded');
        });
    </script>

    <style>
        .code-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .code-section pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        
        .test-result {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .test-result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .test-result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .info-card {
            border-radius: 8px;
        }
        
        .demo-controls {
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</body>
</html>
