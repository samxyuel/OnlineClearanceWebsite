<?php
// Online Clearance Website - Forgot Password Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
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
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            font-weight: bold;
            position: relative;
        }
        
        .step.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .step.completed {
            background-color: var(--success-color);
            color: white;
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 30px;
            height: 2px;
            background-color: var(--light-gray);
            transform: translateY(-50%);
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .step.completed::after {
            background-color: var(--success-color);
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
        }
        
        .contact-admin-message {
            background-color: var(--yellow-light);
            border: 2px solid var(--warning-color);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .contact-admin-message h4 {
            color: var(--warning-color);
            margin-bottom: 10px;
        }
        
        .contact-admin-message p {
            color: var(--dark-primary);
            margin-bottom: 15px;
        }
        
        .rate-limit-message {
            background-color: var(--red-light);
            border: 2px solid var(--danger-color);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .rate-limit-message h4 {
            color: var(--danger-color);
            margin-bottom: 10px;
        }
        
        .rate-limit-message p {
            color: var(--dark-primary);
            margin-bottom: 15px;
        }
        
        .security-question-item {
            margin-bottom: 20px;
            padding: 15px;
            background-color: var(--very-light-off-white);
            border-radius: 5px;
        }
        
        .security-question-item label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--dark-primary);
        }
        
        .security-question-item input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .login-logo img {
                width: 50%;
                height: 15%;
            }
        }
    </style>
</head>
<body>
    <main class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="../../assets/images/STI_Lucena_Logo.png" alt="STI College Lucena Logo">
                </div>
                <h3>Forgot Password</h3>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="step1-indicator">1</div>
                <div class="step" id="step2-indicator">2</div>
                <div class="step" id="step3-indicator">3</div>
            </div>
            
            <!-- Step 1: Username Input -->
            <div class="step-content active" id="step1-content">
                <form class="login-form" id="usernameForm">
                    <div class="form-group">
                        <label for="username">Student or Employee Number</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your student or employee number" required>
                    </div>
                    
                    <div class="form-group login-button-group">
                        <button type="submit" class="btn btn-primary login-btn" id="usernameBtn">
                            <span id="usernameBtnText">Continue</span>
                            <span id="usernameBtnSpinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                    
                    <div class="form-group text-center">
                        <a href="login.php" class="forgot-link">Back to Login</a>
                    </div>
                </form>
            </div>
            
            <!-- Step 2: Security Questions -->
            <div class="step-content" id="step2-content">
                <form class="login-form" id="answersForm">
                    <div class="form-group">
                        <h4 style="margin-bottom: 20px; text-align: center;">Please answer your security questions:</h4>
                    </div>
                    
                    <div id="securityQuestionsContainer">
                        <!-- Questions will be dynamically inserted here -->
                    </div>
                    
                    <div class="form-group login-button-group">
                        <button type="submit" class="btn btn-primary login-btn" id="answersBtn">
                            <span id="answersBtnText">Verify Answers</span>
                            <span id="answersBtnSpinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                    
                    <div class="form-group text-center">
                        <a href="login.php" class="forgot-link">Back to Login</a>
                    </div>
                </form>
            </div>
            
            <!-- Step 3: New Password -->
            <div class="step-content" id="step3-content">
                <form class="login-form" id="passwordForm">
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password" class="form-control" required>
                        <div class="password-requirements">
                            <small>Password must be at least 6 characters long.</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group login-button-group">
                        <button type="submit" class="btn btn-primary login-btn" id="passwordBtn">
                            <span id="passwordBtnText">Reset Password</span>
                            <span id="passwordBtnSpinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                    
                    <div class="form-group text-center">
                        <a href="login.php" class="forgot-link">Back to Login</a>
                    </div>
                </form>
            </div>
            
            <!-- Result Messages -->
            <div id="resultMessage" style="display: none; text-align: center; font-size: 1rem; padding: 0.5rem; margin: 1rem; border-radius: 5px;"></div>
            
            <!-- Footer -->
            <div class="login-footer">
                Powered by goSTI · © 2025
            </div>
        </div>
    </main>

    <script>
        const FORGOT_PASSWORD_API = '../../api/auth/forgot_password.php';
        let currentStep = 1;
        let currentUsername = '';
        let securityQuestions = [];
        
        // Step management
        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show current step
            const stepContent = document.getElementById(`step${step}-content`);
            if (stepContent) {
                stepContent.classList.add('active');
            }
            
            // Update step indicators
            for (let i = 1; i <= 3; i++) {
                const indicator = document.getElementById(`step${i}-indicator`);
                if (indicator) {
                    indicator.classList.remove('active', 'completed');
                    if (i < step) {
                        indicator.classList.add('completed');
                    } else if (i === step) {
                        indicator.classList.add('active');
                    }
                }
            }
            
            currentStep = step;
        }
        
        function showMessage(message, type = 'info') {
            const resultMessage = document.getElementById('resultMessage');
            if (!resultMessage) return;
            
            resultMessage.style.display = 'block';
            resultMessage.className = `alert alert-${type}`;
            resultMessage.innerHTML = `<p>${message}</p>`;
            
            // Scroll to message
            resultMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function hideMessage() {
            const resultMessage = document.getElementById('resultMessage');
            if (resultMessage) {
                resultMessage.style.display = 'none';
            }
        }
        
        // Step 1: Validate Username
        document.getElementById('usernameForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideMessage();
            
            const username = document.getElementById('username').value.trim();
            const usernameBtn = document.getElementById('usernameBtn');
            const usernameBtnText = document.getElementById('usernameBtnText');
            const usernameBtnSpinner = document.getElementById('usernameBtnSpinner');
            
            if (!username) {
                showMessage('Please enter your student or employee number.', 'warning');
                return;
            }
            
            // Show loading state
            usernameBtn.disabled = true;
            usernameBtnText.style.display = 'none';
            usernameBtnSpinner.style.display = 'inline';
            
            try {
                const response = await fetch(FORGOT_PASSWORD_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        step: 'validate_username',
                        username: username
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentUsername = username;
                    securityQuestions = result.data.questions;
                    
                    // Display security questions
                    displaySecurityQuestions(securityQuestions);
                    
                    // Move to step 2
                    showStep(2);
                } else {
                    if (response.status === 429) {
                        // Rate limited
                        const remainingMinutes = result.remaining_minutes || 0;
                        showMessage(
                            result.message + (remainingMinutes > 0 ? ` Please try again in ${remainingMinutes} minute(s).` : ''),
                            'danger'
                        );
                    } else {
                        showMessage(result.message || 'An error occurred. Please try again.', 'danger');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please check your connection and try again.', 'danger');
            } finally {
                usernameBtn.disabled = false;
                usernameBtnText.style.display = 'inline';
                usernameBtnSpinner.style.display = 'none';
            }
        });
        
        // Display security questions
        function displaySecurityQuestions(questions) {
            const container = document.getElementById('securityQuestionsContainer');
            if (!container) return;
            
            container.innerHTML = '';
            
            questions.forEach((question, index) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'security-question-item';
                questionDiv.innerHTML = `
                    <label for="answer${index + 1}">${question.text}</label>
                    <input type="text" id="answer${index + 1}" name="answer${index + 1}" placeholder="Enter your answer..." required>
                `;
                container.appendChild(questionDiv);
            });
        }
        
        // Step 2: Validate Answers
        document.getElementById('answersForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideMessage();
            
            if (!currentUsername || securityQuestions.length === 0) {
                showMessage('Please start from the beginning.', 'warning');
                showStep(1);
                return;
            }
            
            const answers = [];
            for (let i = 1; i <= 3; i++) {
                const answerInput = document.getElementById(`answer${i}`);
                if (answerInput) {
                    const answer = answerInput.value.trim();
                    if (!answer) {
                        showMessage('Please answer all security questions.', 'warning');
                        return;
                    }
                    answers.push(answer);
                }
            }
            
            const answersBtn = document.getElementById('answersBtn');
            const answersBtnText = document.getElementById('answersBtnText');
            const answersBtnSpinner = document.getElementById('answersBtnSpinner');
            
            // Show loading state
            answersBtn.disabled = true;
            answersBtnText.style.display = 'none';
            answersBtnSpinner.style.display = 'inline';
            
            try {
                const response = await fetch(FORGOT_PASSWORD_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        step: 'validate_answers',
                        username: currentUsername,
                        answers: answers
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.validated) {
                    // All answers correct - move to step 3
                    showStep(3);
                } else {
                    if (response.status === 429 || result.contact_admin) {
                        // Wrong answers or rate limited - show contact admin message
                        showContactAdminMessage(result.message || 'One or more answers are incorrect. Please contact the system administrator to manually reset your password.');
                    } else {
                        showMessage(result.message || 'An error occurred. Please try again.', 'danger');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please check your connection and try again.', 'danger');
            } finally {
                answersBtn.disabled = false;
                answersBtnText.style.display = 'inline';
                answersBtnSpinner.style.display = 'none';
            }
        });
        
        // Show contact admin message
        function showContactAdminMessage(message) {
            const container = document.getElementById('securityQuestionsContainer');
            if (!container) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'contact-admin-message';
            messageDiv.innerHTML = `
                <h4><i class="fas fa-exclamation-triangle"></i> Action Required</h4>
                <p>${message}</p>
                <button type="button" class="btn btn-primary" onclick="goToLogin()">
                    <i class="fas fa-check"></i> I Understand
                </button>
            `;
            
            // Insert at the top
            container.insertBefore(messageDiv, container.firstChild);
        }
        
        // Step 3: Reset Password
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideMessage();
            
            if (!currentUsername) {
                showMessage('Please start from the beginning.', 'warning');
                showStep(1);
                return;
            }
            
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                showMessage('New passwords do not match.', 'warning');
                return;
            }
            
            if (newPassword.length < 6) {
                showMessage('Password must be at least 6 characters long.', 'warning');
                return;
            }
            
            const passwordBtn = document.getElementById('passwordBtn');
            const passwordBtnText = document.getElementById('passwordBtnText');
            const passwordBtnSpinner = document.getElementById('passwordBtnSpinner');
            
            // Show loading state
            passwordBtn.disabled = true;
            passwordBtnText.style.display = 'none';
            passwordBtnSpinner.style.display = 'inline';
            
            try {
                const response = await fetch(FORGOT_PASSWORD_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        step: 'reset_password',
                        username: currentUsername,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Password reset successfully! Redirecting to login page...', 'success');
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php?password_reset=success';
                    }, 2000);
                } else {
                    if (response.status === 429) {
                        showMessage(result.message || 'Too many attempts. Please contact the system administrator.', 'danger');
                    } else {
                        showMessage(result.message || 'Failed to reset password. Please try again.', 'danger');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please check your connection and try again.', 'danger');
            } finally {
                passwordBtn.disabled = false;
                passwordBtnText.style.display = 'inline';
                passwordBtnSpinner.style.display = 'none';
            }
        });
        
        // Go to login page
        function goToLogin() {
            window.location.href = 'login.php';
        }
        
        // Check for password reset success message in URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('password_reset') === 'success') {
                showMessage('Your password has been reset successfully. You can now log in with your new password.', 'success');
            }
        });
    </script>
</body>
</html>

