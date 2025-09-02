<?php
// Online Clearance Website - Login Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <!-- Header -->
    <header class="navbar">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>goSTI</h1>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <h2>goSTI</h2>
                </div>
                <h3>Online Clearance System</h3>
            </div>
            
            <form class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username">Username/Email</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g., LCA123P" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Remember Me
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary login-btn" id="loginBtn">
                        <span id="loginBtnText">Login</span>
                        <span id="loginBtnSpinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
                
                <div class="form-group text-center">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </form>
            
            <!-- Login Result Messages -->
            <div id="loginResult" style="display: none;"></div>
        </div>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginBtnSpinner = document.getElementById('loginBtnSpinner');
            const loginResult = document.getElementById('loginResult');
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtnText.style.display = 'none';
            loginBtnSpinner.style.display = 'inline';
            loginResult.style.display = 'none';
            
            try {
                const response = await fetch('../../api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Login successful
                    loginResult.className = 'alert alert-success';
                    loginResult.innerHTML = `
                        <h4>Login Successful!</h4>
                        <p>Welcome back, ${result.user.first_name} ${result.user.last_name}!</p>
                        <p>Redirecting to dashboard...</p>
                    `;
                    loginResult.style.display = 'block';
                    
                    // Redirect based on user role
                    setTimeout(() => {
                        const role = result.user.role_name.toLowerCase();
                        if (role === 'admin') {
                            window.location.href = '../../pages/admin/dashboard.php';
                        } else if (role === 'school administrator') {
                            window.location.href = '../../pages/school-administrator/dashboard.php';
                        } else if (role === 'program head') {
                            window.location.href = '../../pages/program-head/dashboard.php';
                        } else if (role === 'student') {
                            window.location.href = '../../pages/student/dashboard.php';
                        } else if (role === 'faculty') {
                            window.location.href = '../../pages/faculty/dashboard.php';
                        } else if (role === 'staff') {
                            window.location.href = '../../pages/regular-staff/dashboard.php';
                        } else {
                            // Fallback for unknown roles
                            window.location.href = '../../pages/admin/dashboard.php';
                        }
                    }, 2000);
                    
                } else {
                    // Login failed
                    loginResult.className = 'alert alert-danger';
                    loginResult.innerHTML = `
                        <h4>Login Failed</h4>
                        <p>${result.message}</p>
                    `;
                    loginResult.style.display = 'block';
                    
                    // Reset form
                    document.getElementById('password').value = '';
                    document.getElementById('password').focus();
                }
                
            } catch (error) {
                // Network or other error
                loginResult.className = 'alert alert-danger';
                loginResult.innerHTML = `
                    <h4>Connection Error</h4>
                    <p>Unable to connect to the server. Please try again.</p>
                `;
                loginResult.style.display = 'block';
            } finally {
                // Reset button state
                loginBtn.disabled = false;
                loginBtnText.style.display = 'inline';
                loginBtnSpinner.style.display = 'none';
            }
        });
    </script>
</body>
</html> 