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
            
            <form class="login-form" action="process_login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username/Email</label>
                    <input type="text" id="username" name="username" class="form-control" required>
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
                    <button type="submit" class="btn btn-primary login-btn">
                        Login
                    </button>
                </div>
                
                <div class="form-group text-center">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 