<?php
// Web-based login test
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .form-group { margin: 10px 0; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { padding: 8px; width: 200px; }
        .btn { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Login Process Test</h1>
    
    <div class="test-section">
        <h2>Current Session Data</h2>
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
        echo "<p>Session Data:</p>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test Login</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="LCA105P">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="password123">
            </div>
            <button type="submit" class="btn">Test Login</button>
        </form>
        <div id="loginResult"></div>
    </div>
    
    <div class="test-section">
        <h2>Test Links</h2>
        <p><a href="pages/regular-staff/dashboard.php">Regular Staff Dashboard</a></p>
        <p><a href="pages/regular-staff/FacultyManagement.php">Regular Staff Faculty Management</a></p>
        <p><a href="test_auth_web.php">Authentication Test Page</a></p>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const resultDiv = document.getElementById('loginResult');
            
            resultDiv.innerHTML = '<p>Testing login...</p>';
            
            fetch('api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">✓ Login successful! User data: ' + JSON.stringify(data.user) + '</p>';
                    // Reload page to show updated session
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    resultDiv.innerHTML = '<p class="error">✗ Login failed: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        });
    </script>
</body>
</html>
