<?php
/**
 * Diagnostic Tool Interface: Program Head Data Visibility Checker
 * 
 * Provides a user-friendly interface to run diagnostics on Program Head data visibility.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../pages/auth/login.php');
    exit;
}

$userId = $auth->getUserId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Head Data Diagnostic Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .results {
            margin-top: 30px;
            display: none;
        }
        
        .check-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .check-item.passed {
            border-left: 4px solid #28a745;
        }
        
        .check-item.failed {
            border-left: 4px solid #dc3545;
        }
        
        .check-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .check-name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .check-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .check-status.passed {
            background: #d4edda;
            color: #155724;
        }
        
        .check-status.failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .check-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .check-details pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        
        .summary {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .summary h3 {
            margin-bottom: 15px;
            color: #004085;
        }
        
        .summary-item {
            margin-bottom: 10px;
        }
        
        .summary-item strong {
            color: #004085;
        }
        
        .critical-failures {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .critical-failures h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .critical-failures ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .recommendation {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .recommendation h4 {
            color: #0c5460;
            margin-bottom: 10px;
        }
        
        .recommendation p {
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Program Head Data Diagnostic Tool</h1>
        <p class="subtitle">Check why students/faculty data might not be showing on Program Head pages</p>
        
        <form id="diagnosticForm">
            <div class="form-group">
                <label for="userId">User ID:</label>
                <input type="number" id="userId" name="user_id" value="<?php echo htmlspecialchars($userId); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="type">Type:</label>
                <select id="type" name="type">
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="schoolTerm">School Term (optional, format: "2024-2025|2"):</label>
                <input type="text" id="schoolTerm" name="school_term" placeholder="e.g., 2024-2025|2">
            </div>
            
            <button type="submit">Run Diagnostics</button>
        </form>
        
        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Running diagnostics...</p>
        </div>
        
        <div id="results" class="results"></div>
    </div>
    
    <script>
        document.getElementById('diagnosticForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('userId').value;
            const type = document.getElementById('type').value;
            const schoolTerm = document.getElementById('schoolTerm').value;
            
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            
            loading.style.display = 'block';
            results.style.display = 'none';
            results.innerHTML = '';
            
            try {
                const params = new URLSearchParams({
                    user_id: userId,
                    type: type,
                    school_term: schoolTerm
                });
                
                const response = await fetch(`../../api/diagnostics/program_head_data_check.php?${params}`, {
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                loading.style.display = 'none';
                results.style.display = 'block';
                
                displayResults(data);
            } catch (error) {
                loading.style.display = 'none';
                results.style.display = 'block';
                results.innerHTML = `<div class="check-item failed">
                    <div class="check-header">
                        <span class="check-name">Error</span>
                        <span class="check-status failed">FAILED</span>
                    </div>
                    <div class="check-details">
                        <p>Error running diagnostics: ${error.message}</p>
                    </div>
                </div>`;
            }
        });
        
        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            let html = '';
            
            // Summary
            if (data.summary) {
                html += '<div class="summary">';
                html += '<h3>Summary</h3>';
                html += `<div class="summary-item"><strong>Total Checks:</strong> ${data.summary.total_checks}</div>`;
                html += `<div class="summary-item"><strong>Passed:</strong> ${data.summary.passed_checks}</div>`;
                html += `<div class="summary-item"><strong>Failed:</strong> ${data.summary.failed_checks}</div>`;
                html += `<div class="summary-item"><strong>Data Should Show:</strong> ${data.summary.data_should_show ? '✅ Yes' : '❌ No'}</div>`;
                
                if (data.summary.critical_failures && data.summary.critical_failures.length > 0) {
                    html += '<div class="critical-failures">';
                    html += '<h4>⚠️ Critical Failures:</h4>';
                    html += '<ul>';
                    data.summary.critical_failures.forEach(failure => {
                        html += `<li>${failure}</li>`;
                    });
                    html += '</ul>';
                    html += '</div>';
                }
                
                if (data.summary.recommendation) {
                    html += '<div class="recommendation">';
                    html += '<h4>💡 Recommendation:</h4>';
                    html += `<p>${data.summary.recommendation}</p>`;
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            // Individual Checks
            if (data.checks) {
                Object.keys(data.checks).forEach(key => {
                    const check = data.checks[key];
                    const status = check.passed ? 'passed' : 'failed';
                    const statusText = check.passed ? 'PASSED' : 'FAILED';
                    
                    html += `<div class="check-item ${status}">`;
                    html += `<div class="check-header">`;
                    html += `<span class="check-name">${check.name}</span>`;
                    html += `<span class="check-status ${status}">${statusText}</span>`;
                    html += `</div>`;
                    
                    if (check.details) {
                        html += '<div class="check-details">';
                        html += `<pre>${JSON.stringify(check.details, null, 2)}</pre>`;
                        html += '</div>';
                    }
                    
                    html += '</div>';
                });
            }
            
            resultsDiv.innerHTML = html;
        }
    </script>
</body>
</html>

