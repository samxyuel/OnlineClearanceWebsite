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
    <script src="../../assets/js/base-path.js"></script>
    <style>
        .login-logo img {
            width: 50%;
            height: 15%;
            border-radius: 10%;
            border: 3px var(--very-light-off-white);
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }
        
        /*
        .login-logo img:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
        }
        */
        
        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            .login-logo img {
                width: 50%;
                height: 15%;
                border-width: 2px;
            }
        }
        
        @media (max-width: 480px) {
            .login-logo img {
                width: 50%;
                height: 15%;
                border-width: 2px;
            }
        }
    </style>
</head>
<body>
    <!-- Header --
    <header class="navbar">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>goSTI</h1>
                </div>
            </div>
        </div>
    </header> -->

    <!-- Main Content -->
    <main class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="../../assets/images/STI_Lucena_Logo.png" alt="STI College Lucena Logo">
                </div>
                <h3>Online Clearance Website</h3>
            </div>
            
            <form class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Student or Employee Number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
            <!--    <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Remember Me
                    </label>
                </div>
                -->
                <div class="form-group login-button-group">
                    <button type="submit" class="btn btn-primary login-btn" id="loginBtn">
                        <span id="loginBtnText">Login</span>
                        <span id="loginBtnSpinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
                
            <!-- Forgot Password Link -->
                <div class="form-group text-center">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            
            </form>

            <!-- Login Result Messages -->
            <div id="loginResult" style="display: none; text-align: left; font-size: 1rem; background-color: var(--yellow-light);
color: var(--deep-navy-blue); padding: 0.5rem; margin: 1rem; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);"></div>

            <!-- Footer -->
            <div class="login-footer">
                Powered by goSTI · © 2025 <br>
                This is a test version of the Online Clearance System as a thesis project for the degree of Bachelor of Science in Information Technology. <br> All rights reserved.<br>
            </div>
        </div>
    </main>

    <script>
        // Wait for DOM to be ready and ensure all elements exist
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginBtnSpinner = document.getElementById('loginBtnSpinner');
            const loginResult = document.getElementById('loginResult');

            // Validate all required elements exist
            if (!loginForm || !usernameInput || !passwordInput || !loginBtn || 
                !loginBtnText || !loginBtnSpinner || !loginResult) {
                // Silently fail - elements missing, page may not be fully loaded
                return;
            }

            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const username = usernameInput.value.trim();
                const password = passwordInput.value;
                
                // Validate inputs exist
                if (!username || !password) {
                    if (loginResult) {
                        loginResult.className = 'alert alert-warning';
                        loginResult.innerHTML = `
                            <h4>Validation Error</h4>
                            <p>Please enter both username and password.</p>
                        `;
                        loginResult.style.display = 'block';
                    }
                    return;
                }
                
                // Show loading state safely
                if (loginBtn) loginBtn.disabled = true;
                if (loginBtnText) loginBtnText.style.display = 'none';
                if (loginBtnSpinner) loginBtnSpinner.style.display = 'inline';
                if (loginResult) loginResult.style.display = 'none';
                
                try {
                    // Use dynamic base path based on protocol (HTTP includes /OnlineClearanceWebsite/, HTTPS excludes it)
                    const apiUrl = window.location.origin + getApiUrl('api/auth/login.php');
                    
                    // Fetch with proper error handling - handle all HTTP status codes gracefully
                    let response;
                    try {
                        response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ username, password })
                        });
                    } catch (fetchError) {
                        // Network error (no connection, CORS, etc.) - handle silently
                        if (loginResult) {
                            loginResult.className = 'alert alert-danger';
                            loginResult.innerHTML = `
                                <h4>Connection Error</h4>
                                <p>Unable to connect to the server. Please check your internet connection and try again.</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        return;
                    }
                    
                    // Get response text safely - handle all HTTP status codes (200, 400, 401, 500, etc.)
                    let responseText = '';
                    try {
                        responseText = await response.text();
                    } catch (textError) {
                        // Response body read error - handle silently
                        if (loginResult) {
                            loginResult.className = 'alert alert-danger';
                            loginResult.innerHTML = `
                                <h4>Response Error</h4>
                                <p>Unable to read server response. Please try again.</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        return;
                    }
                    
                    // Handle empty response
                    if (!responseText || responseText.trim() === '') {
                        if (loginResult) {
                            loginResult.className = 'alert alert-danger';
                            loginResult.innerHTML = `
                                <h4>Server Error</h4>
                                <p>Server returned an empty response. Please try again.</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        return;
                    }
                    
                    // Parse JSON safely - handle all possible JSON errors
                    let result = null;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        // Invalid JSON response (HTML error page, malformed JSON, etc.) - handle silently
                        if (loginResult) {
                            loginResult.className = 'alert alert-danger';
                            loginResult.innerHTML = `
                                <h4>Server Error</h4>
                                <p>Invalid response from server. Please try again later.</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        return;
                    }
                    
                    // Validate result structure
                    if (!result || typeof result !== 'object') {
                        if (loginResult) {
                            loginResult.className = 'alert alert-danger';
                            loginResult.innerHTML = `
                                <h4>Server Error</h4>
                                <p>Unexpected response format. Please try again.</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        return;
                    }
                    
                    // Handle successful login
                    if (result.success === true) {
                        // Extract user data safely
                        const user = result.data?.user || result.user || {};
                        
                        if (loginResult) {
                            loginResult.className = 'alert alert-success';
                            loginResult.innerHTML = `
                                <h4>Login Successful!</h4>
                                <p>Welcome back, ${user.first_name || ''} ${user.last_name || ''}!</p>
                                <p>Redirecting to dashboard...</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        
                        // Redirect based on user role - handle safely
                        setTimeout(function() {
                            const role = (user.role_name || '').toLowerCase();
                            let redirectUrl = '../../pages/admin/dashboard.php'; // Default fallback
                            
                            if (role === 'admin') {
                                redirectUrl = '../../pages/admin/dashboard.php';
                            } else if (role === 'school administrator') {
                                redirectUrl = '../../pages/school-administrator/dashboard.php';
                            } else if (role === 'program head') {
                                redirectUrl = '../../pages/program-head/dashboard.php';
                            } else if (role === 'student') {
                                redirectUrl = '../../pages/end-user/dashboard.php';
                            } else if (role === 'faculty') {
                                redirectUrl = '../../pages/end-user/dashboard.php';
                            } else if (role === 'regular staff') {
                                redirectUrl = '../../pages/regular-staff/dashboard.php';
                            }
                            
                            // Redirect safely
                            try {
                                window.location.href = redirectUrl;
                            } catch (redirectError) {
                                // Redirect failed - show message
                                if (loginResult) {
                                    loginResult.className = 'alert alert-warning';
                                    loginResult.innerHTML = `
                                        <h4>Redirect Error</h4>
                                        <p>Please <a href="${redirectUrl}">click here</a> to continue.</p>
                                    `;
                                    loginResult.style.display = 'block';
                                }
                            }
                        }, 2000);
                        
                    } else {
                        // Login failed - handle all failure scenarios gracefully
                        // The backend should return specific error codes/messages for:
                        // 1. Account does not exist
                        // 2. Account exists but is inactive
                        // 3. Invalid password (when account exists and is active)
                        
                        const errorMessage = result.message || 'Login failed. Please try again.';
                        
                        if (loginResult) {
                            loginResult.className = 'alert alert-danger';
                            loginResult.innerHTML = `
                                <h4>Login Failed</h4>
                                <p>${errorMessage}</p>
                            `;
                            loginResult.style.display = 'block';
                        }
                        
                        // Reset form safely
                        if (passwordInput) {
                            passwordInput.value = '';
                            try {
                                passwordInput.focus();
                            } catch (focusError) {
                                // Focus failed - ignore silently
                            }
                        }
                    }
                    
                } catch (error) {
                    // Catch any unexpected errors - handle silently
                    // This should rarely happen due to all the try-catch blocks above
                    if (loginResult) {
                        loginResult.className = 'alert alert-danger';
                        loginResult.innerHTML = `
                            <h4>Unexpected Error</h4>
                            <p>An unexpected error occurred. Please try again.</p>
                        `;
                        loginResult.style.display = 'block';
                    }
                } finally {
                    // Reset button state safely
                    if (loginBtn) loginBtn.disabled = false;
                    if (loginBtnText) loginBtnText.style.display = 'inline';
                    if (loginBtnSpinner) loginBtnSpinner.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 