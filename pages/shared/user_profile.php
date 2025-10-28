<?php
// User Profile Page - Mobile-First Stacked Layout
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/auth/login.php');
    exit();
}

// Include database connection
require_once '../../includes/config/database.php';

// Fetch user details from session and database
$userId = (int)$_SESSION['user_id'];
$firstName = isset($_SESSION['first_name']) ? trim($_SESSION['first_name']) : '';
$middleName = isset($_SESSION['middle_name']) ? trim($_SESSION['middle_name']) : '';
$lastName = isset($_SESSION['last_name']) ? trim($_SESSION['last_name']) : '';
$email = isset($_SESSION['email']) ? trim($_SESSION['email']) : '';
$phone = isset($_SESSION['phone']) ? trim($_SESSION['phone']) : '';
$roleId = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

// Construct display name
$displayName = "User";
$nameParts = [];
if (!empty($lastName)) {
    $nameParts[] = htmlspecialchars($lastName);
}
if (!empty($firstName)) {
    $nameParts[] = htmlspecialchars($firstName);
}

if (count($nameParts) == 2) {
    $displayName = $nameParts[0] . ", " . $nameParts[1];
    if (!empty($middleName)) {
        $displayName .= " " . htmlspecialchars($middleName);
    }
} elseif (!empty($nameParts)) {
    $displayName = implode(" ", $nameParts);
    if (count($nameParts) < 2 && !empty($middleName) && !in_array(htmlspecialchars($middleName), $nameParts)) {
        $displayName .= " " . htmlspecialchars($middleName);
    }
}

// Map role_id to role name
$roleMap = [
    1 => 'admin',
    2 => 'staff',
    3 => 'student',
    4 => 'faculty',
    5 => 'school_admin',
    6 => 'program_head'
];

// Get current role
$currentRole = null;
if (!empty($_SESSION['role_name'])) {
    $rn = strtolower(trim($_SESSION['role_name']));
    if ($rn === 'program head') $currentRole = 'program_head';
    elseif ($rn === 'school administrator') $currentRole = 'school_admin';
    elseif ($rn === 'admin') $currentRole = 'admin';
    elseif ($rn === 'faculty') $currentRole = 'faculty';
    elseif ($rn === 'student') $currentRole = 'student';
    elseif ($rn === 'staff' || $rn === 'regular staff') $currentRole = 'staff';
    else $currentRole = 'staff';
} else {
    $currentRole = isset($roleMap[$roleId]) ? $roleMap[$roleId] : null;
}

if ($currentRole === null) { $currentRole = 'student'; }

// Fetch additional user data based on role
$userData = [];
$accountStatus = 'Active'; // Default
$lastLogin = 'Never'; // Default

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get account status and last login
    $stmt = $pdo->prepare("SELECT is_active, last_login FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $isActive = $userInfo['is_active'];
    $accountStatus = $isActive ? 'Active' : 'Inactive';
    $lastLogin = $userInfo['last_login'] ? date('M d, Y H:i', strtotime($userInfo['last_login'])) : 'Never';
    
    // Role-specific data fetching
    switch ($currentRole) {
        case 'admin':
            // Admin doesn't need additional data from other tables
            // Admin data comes from users table and session
            break;
            
        case 'student':
            // Fetch student data
            $stmt = $pdo->prepare("
                SELECT s.student_number, s.year_level, s.section_number, 
                       sec.sector_name, d.department_name, p.program_name
                FROM students s 
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                WHERE s.user_id = ?
            ");
            $stmt->execute([$userId]);
            $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($studentData) {
                $userData = array_merge($userData, $studentData);
            }
            break;
            
        case 'faculty':
            // Fetch faculty data
            $stmt = $pdo->prepare("
                SELECT f.employee_number, f.employment_status, f.employment_date,
                       sec.sector_name, d.department_name
                FROM faculty f 
                LEFT JOIN departments d ON f.department_id = d.department_id
                LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE f.user_id = ?
            ");
            $stmt->execute([$userId]);
            $facultyData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($facultyData) {
                $userData = array_merge($userData, $facultyData);
            }
            break;
            
        case 'staff':
        case 'school_admin':
        case 'program_head':
            // Fetch staff data
            $stmt = $pdo->prepare("
                SELECT s.employee_number, s.designation, s.employment_date,
                       sec.sector_name, d.department_name
                FROM staff s 
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE s.user_id = ?
            ");
            $stmt->execute([$userId]);
            $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($staffData) {
                $userData = array_merge($userData, $staffData);
            }
            break;
    }
} catch (Exception $e) {
    // Handle database errors gracefully
    error_log("Profile data fetch error: " . $e->getMessage());
}

// Role display names
$roleDisplayNames = [
    'admin' => 'Administrator',
    'staff' => 'Staff',
    'student' => 'Student',
    'faculty' => 'Faculty',
    'school_admin' => 'School Administrator',
    'program_head' => 'Program Head'
];

$currentRoleDisplay = isset($roleDisplayNames[$currentRole]) ? $roleDisplayNames[$currentRole] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Profile - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/user_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    // Include the header component
    require_once '../../includes/components/header.php';
    
    // Include the sidebar component
    require_once '../../includes/components/sidebar.php';
    ?>
    
    <div class="main-content">
        <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <h1 class="user-name"><?php echo htmlspecialchars($displayName); ?></h1>
            </div>
            
            <div class="header-actions">
                <div class="status-info">
                <span class="account-status-badge status-<?php echo strtolower($accountStatus); ?>">
                        <i class="fas fa-shield-alt"></i>
                        <?php echo htmlspecialchars($accountStatus); ?>
                    </span>
                    <span class="role-badge role-<?php echo $currentRole; ?>">
                        <?php echo htmlspecialchars($currentRoleDisplay); ?>
                    </span>  
                </div>
                <!--
                <button class="edit-btn" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i>
                </button>
                -->
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" onclick="switchTab('profile')" id="profile-tab">
                <i class="fas fa-user"></i>
                Profile
            </button>
            <button class="tab-btn" onclick="switchTab('account')" id="account-tab">
                <i class="fas fa-cog"></i>
                Account Settings
            </button>
        </div>

        <!-- Profile Tab Content -->
        <div class="tab-content active" id="profile-content">
            <!-- Profile Edit Button -->
            <div class="profile-edit-section">
                <button class="edit-btn profile-edit-btn" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
            </div>
            <!-- Personal Information Card -->
            <div class="info-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-user"></i>
                        <h3>Personal Information</h3>
                    </div>
                    <div class="card-header-right">
                        <!-- <span class="role-badge role-< ?php echo $currentRole; ?>">
                            < ?php echo htmlspecialchars($currentRoleDisplay); ?>
                        </span> -->
                    </div>
                </div>
                <div class="card-content">
                    <!-- Full Name - Only editable for Admin -->
                    <div class="info-item">
                        <span class="label">Full Name:</span>
                        <?php if ($currentRole === 'admin'): ?>
                            <span class="value editable" data-field="full_name" contenteditable="false"><?php echo htmlspecialchars($displayName); ?></span>
                        <?php else: ?>
                            <span class="value"><?php echo htmlspecialchars($displayName); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Student/Employee Number - Not editable -->
                    <?php if ($currentRole === 'student'): ?>
                        <div class="info-item">
                            <span class="label">Student Number:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['student_number'] ?? 'N/A'); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <span class="label">Employee Number:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['employee_number'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Email - Editable for all users -->
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value editable" data-field="email" contenteditable="false"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    
                    <!-- Phone Number - Editable for all users -->
                    <div class="info-item">
                        <span class="label">Phone Number:</span>
                        <span class="value editable" data-field="phone" contenteditable="false"><?php echo htmlspecialchars($phone); ?></span>
                    </div>
                    
                    <!-- Last Login - Only for Admin -->
                    <?php if ($currentRole === 'admin'): ?>
                        <div class="info-item">
                            <span class="label">Last Login:</span>
                            <span class="value"><?php echo htmlspecialchars($lastLogin); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($currentRole === 'faculty'): ?>
                        <div class="info-item">
                            <span class="label">Employment Status:</span>
                            <span class="value status-badge <?php echo strtolower(str_replace(' ', '-', $userData['employment_status'] ?? 'N/A')); ?>">
                                <?php echo htmlspecialchars($userData['employment_status'] ?? 'N/A'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (in_array($currentRole, ['staff', 'school_admin', 'program_head'])): ?>
                        <div class="info-item">
                            <span class="label">Designation:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['designation'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (in_array($currentRole, ['admin', 'faculty', 'staff', 'school_admin', 'program_head'])): ?>
                        <div class="info-item">
                            <span class="label">Employment Date:</span>
                            <?php if (in_array($currentRole, ['admin', 'staff', 'school_admin', 'program_head'])): ?>
                                <span class="value editable" data-field="employment_date" contenteditable="false"><?php echo isset($userData['employment_date']) ? date('M d, Y', strtotime($userData['employment_date'])) : 'N/A'; ?></span>
                            <?php else: ?>
                                <span class="value"><?php echo isset($userData['employment_date']) ? date('M d, Y', strtotime($userData['employment_date'])) : 'N/A'; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Academic/Work Information Card -->
            <?php if (in_array($currentRole, ['student', 'faculty'])): ?>
            <div class="info-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-<?php echo $currentRole === 'student' ? 'graduation-cap' : 'briefcase'; ?>"></i>
                        <h3><?php echo $currentRole === 'student' ? 'Academic Information' : 'Work Information'; ?></h3>
                    </div>
                    <div class="card-header-right">
                    </div>
                </div>
                <div class="card-content">
                    <?php if ($currentRole === 'student'): ?>
                        <div class="info-item">
                            <span class="label">Year Level:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['year_level'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Section:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['section_number'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="label">Sector:</span>
                        <span class="value"><?php echo htmlspecialchars($userData['sector_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Department:</span>
                        <span class="value"><?php echo htmlspecialchars($userData['department_name'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if ($currentRole === 'student'): ?>
                        <div class="info-item">
                            <span class="label">Program:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['program_name'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($currentRole === 'program_head'): ?>
                        <div class="info-item">
                            <span class="label">Department Handled:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['department_name'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>


        </div>

        <!-- Account Settings Tab Content -->
        <div class="tab-content" id="account-content">
            <!-- Security Questions Card -->
            <div class="info-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-question-circle"></i>
                        <h3>Security Questions</h3>
                    </div>
                    <div class="card-header-right">
                    </div>
                </div>
                <div class="card-content">
                    <div class="security-questions-section">
                        <div class="section-description">
                            <p><i class="fas fa-info-circle"></i> Set up security questions to help recover your account if you forget your password. These questions will be used for password recovery.</p>
                        </div>
                        
                        <form class="security-questions-form" id="security-questions-form">
                            <!-- Question 1 -->
                            <div class="question-group">
                                <div class="form-group">
                                    <label for="question1">Security Question 1</label>
                                    <select id="question1" name="question1" required>
                                        <option value="">Select a security question...</option>
                                        <option value="mother_maiden_name">What is your mother's maiden name?</option>
                                        <option value="birth_city">What city were you born in?</option>
                                        <option value="first_pet">What was the name of your first pet?</option>
                                        <option value="elementary_school">What was the name of your elementary school?</option>
                                        <option value="childhood_nickname">What was your childhood nickname?</option>
                                        <option value="favorite_teacher">What was the name of your favorite teacher?</option>
                                        <option value="first_car">What was the make and model of your first car?</option>
                                        <option value="childhood_friend">What was the name of your childhood best friend?</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="answer1">Answer</label>
                                    <input type="text" id="answer1" name="answer1" placeholder="Enter your answer..." required>
                                </div>
                            </div>

                            <!-- Question 2 -->
                            <div class="question-group">
                                <div class="form-group">
                                    <label for="question2">Security Question 2</label>
                                    <select id="question2" name="question2" required>
                                        <option value="">Select a security question...</option>
                                        <option value="father_middle_name">What is your father's middle name?</option>
                                        <option value="birth_hospital">What hospital were you born in?</option>
                                        <option value="favorite_food">What was your favorite food as a child?</option>
                                        <option value="high_school">What was the name of your high school?</option>
                                        <option value="street_grew_up">What street did you grow up on?</option>
                                        <option value="favorite_sport">What was your favorite sport in high school?</option>
                                        <option value="first_job">What was your first job?</option>
                                        <option value="wedding_anniversary">What is your wedding anniversary date? (MM/DD/YYYY)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="answer2">Answer</label>
                                    <input type="text" id="answer2" name="answer2" placeholder="Enter your answer..." required>
                                </div>
                            </div>

                            <!-- Question 3 -->
                            <div class="question-group">
                                <div class="form-group">
                                    <label for="question3">Security Question 3</label>
                                    <select id="question3" name="question3" required>
                                        <option value="">Select a security question...</option>
                                        <option value="sibling_name">What is your oldest sibling's middle name?</option>
                                        <option value="grandmother_name">What is your maternal grandmother's first name?</option>
                                        <option value="favorite_movie">What was your favorite movie as a child?</option>
                                        <option value="college_name">What college did you attend?</option>
                                        <option value="first_concert">What was the first concert you attended?</option>
                                        <option value="favorite_place">What is your favorite place to visit?</option>
                                        <option value="childhood_hero">Who was your childhood hero?</option>
                                        <option value="favorite_book">What was your favorite book as a child?</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="answer3">Answer</label>
                                    <input type="text" id="answer3" name="answer3" placeholder="Enter your answer..." required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="clearSecurityQuestions()">
                                    <i class="fas fa-eraser"></i> Clear All
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Security Questions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="info-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-lock"></i>
                        <h3>Change Password</h3>
                    </div>
                    <div class="card-header-right">
                    </div>
                </div>
                <div class="card-content">
                    <div class="password-change-section">
                        <div class="section-description">
                            <p><i class="fas fa-shield-alt"></i> Change your password to keep your account secure. You'll need your current password to make changes.</p>
                        </div>
                        
                        <form class="password-form" id="password-form">
                            <div class="form-group">
                                <label for="current-password">Current Password</label>
                                <input type="password" id="current-password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <input type="password" id="new-password" name="new_password" required>
                                <div class="password-requirements">
                                    <small>Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script>
        // Tab switching functionality - Global scope
        window.switchTab = function(tabName) {
            console.log('Switching to tab:', tabName); // Debug log
            
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and content
            const tabElement = document.getElementById(tabName + '-tab');
            const contentElement = document.getElementById(tabName + '-content');
            
            if (tabElement && contentElement) {
                tabElement.classList.add('active');
                contentElement.classList.add('active');
                console.log('Tab switched successfully'); // Debug log
            } else {
                console.error('Tab elements not found:', tabName + '-tab', tabName + '-content');
            }
        }

        // Edit mode toggle - Global scope
        window.toggleEditMode = function() {
            console.log('Toggle edit mode called'); // Debug log
            
            const editBtn = document.querySelector('.profile-edit-btn');
            if (!editBtn) {
                console.error('Edit button not found');
                return;
            }
            
            const isEditing = editBtn.classList.contains('editing');
            console.log('Current editing state:', isEditing); // Debug log
            
            if (isEditing) {
                editBtn.classList.remove('editing');
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit Profile';
                // Disable editing mode for editable fields only
                document.querySelectorAll('.info-item .value.editable').forEach(item => {
                    item.contentEditable = false;
                    item.classList.remove('editing');
                });
                // Save changes
                saveProfileChanges();
                console.log('Edit mode disabled'); // Debug log
            } else {
                editBtn.classList.add('editing');
                editBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                // Enable editing mode for editable fields only
                document.querySelectorAll('.info-item .value.editable').forEach(item => {
                    item.contentEditable = true;
                    item.classList.add('editing');
                });
                console.log('Edit mode enabled'); // Debug log
            }
        }

        // Admin field editing functionality
        function initAdminEditing() {
            const editableFields = document.querySelectorAll('.admin-editable .value.editable');
            
            editableFields.forEach(field => {
                let originalValue = field.textContent.trim();
                
                field.addEventListener('input', function() {
                    if (this.textContent.trim() !== originalValue) {
                        this.classList.add('changed');
                    } else {
                        this.classList.remove('changed');
                    }
                });
                
                field.addEventListener('blur', function() {
                    if (this.classList.contains('changed')) {
                        saveAdminField(this);
                    }
                });
                
                field.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }
                });
            });
        }
        
        // Save admin field changes
        function saveAdminField(field) {
            const fieldName = field.getAttribute('data-field');
            const newValue = field.textContent.trim();
            
            // Show loading state
            field.style.opacity = '0.6';
            
            // Create save indicator
            const indicator = document.createElement('div');
            indicator.className = 'admin-save-indicator';
            indicator.innerHTML = '<i class="fas fa-check"></i> Saving...';
            document.body.appendChild(indicator);
            
            // Simulate save (replace with actual AJAX call)
            setTimeout(() => {
                // Update session data (in real implementation, this would be an AJAX call)
                console.log(`Saving ${fieldName}: ${newValue}`);
                
                // Show success
                indicator.innerHTML = '<i class="fas fa-check"></i> Saved!';
                indicator.classList.add('show');
                
                // Remove changed class
                field.classList.remove('changed');
                field.style.opacity = '1';
                
                // Hide indicator after 2 seconds
                setTimeout(() => {
                    indicator.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(indicator);
                    }, 300);
                }, 2000);
            }, 500);
        }

        // Save profile changes
        function saveProfileChanges() {
            const changedFields = document.querySelectorAll('.info-item .value.editable.changed');
            
            if (changedFields.length > 0) {
                // Show save indicator
                showSaveIndicator('Profile updated successfully!');
                
                // Reset changed state
                changedFields.forEach(field => {
                    field.classList.remove('changed');
                });
            }
        }

        // Show save indicator
        function showSaveIndicator(message) {
            // Create save indicator
            const indicator = document.createElement('div');
            indicator.className = 'admin-save-indicator';
            indicator.innerHTML = '<i class="fas fa-check"></i> ' + message;
            document.body.appendChild(indicator);
            
            // Show indicator
            setTimeout(() => {
                indicator.classList.add('show');
            }, 100);
            
            // Hide indicator after 3 seconds
            setTimeout(() => {
                indicator.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(indicator)) {
                        document.body.removeChild(indicator);
                    }
                }, 300);
            }, 3000);
        }

        // Mobile-specific enhancements
        function initMobileEnhancements() {
            // Prevent double-tap zoom on buttons
            const buttons = document.querySelectorAll('.tab-btn, .btn, .edit-btn');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                }, { passive: false });
            });
            
            // Improve touch scrolling
            document.body.style.webkitOverflowScrolling = 'touch';
            
            // Handle orientation changes
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    window.scrollTo(0, 0);
                }, 100);
            });
        }

        // Form submission handlers
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...'); // Debug log
            
            initMobileEnhancements();
            initAdminEditing();
            initEditableFields();
            
            // Security questions form
            const securityForm = document.querySelector('.security-questions-form');
            if (securityForm) {
                securityForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleSecurityQuestionsSubmit();
                });
            }

            // Password form
            const passwordForm = document.querySelector('.password-form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handlePasswordChange();
                });
            }
        });

        // Initialize editable fields
        function initEditableFields() {
            const editableFields = document.querySelectorAll('.info-item .value.editable');
            console.log('Found editable fields:', editableFields.length); // Debug log
            
            editableFields.forEach(field => {
                let originalValue = field.textContent.trim();
                
                field.addEventListener('input', function() {
                    if (this.textContent.trim() !== originalValue) {
                        this.classList.add('changed');
                        console.log('Field changed:', this.getAttribute('data-field')); // Debug log
                    } else {
                        this.classList.remove('changed');
                    }
                });
                
                field.addEventListener('blur', function() {
                    if (this.classList.contains('changed')) {
                        console.log('Field blurred with changes:', this.getAttribute('data-field')); // Debug log
                    }
                });
            });
        }

        // Security questions functions - Global scope
        window.handleSecurityQuestionsSubmit = function() {
            const form = document.getElementById('security-questions-form');
            const formData = new FormData(form);
            
            // Validate that all questions are different
            const questions = [
                formData.get('question1'),
                formData.get('question2'),
                formData.get('question3')
            ];
            
            if (new Set(questions).size !== questions.length) {
                alert('Please select different security questions for each question.');
                return;
            }
            
            // Validate that all answers are provided
            const answers = [
                formData.get('answer1'),
                formData.get('answer2'),
                formData.get('answer3')
            ];
            
            if (answers.some(answer => !answer.trim())) {
                alert('Please provide answers for all security questions.');
                return;
            }
            
            // Simulate saving (replace with actual AJAX call)
            console.log('Saving security questions:', {
                question1: formData.get('question1'),
                answer1: formData.get('answer1'),
                question2: formData.get('question2'),
                answer2: formData.get('answer2'),
                question3: formData.get('question3'),
                answer3: formData.get('answer3')
            });
            
            showSaveIndicator('Security questions saved successfully!');
        }

        window.clearSecurityQuestions = function() {
            if (confirm('Are you sure you want to clear all security questions?')) {
                document.getElementById('security-questions-form').reset();
                showSaveIndicator('Security questions cleared.');
            }
        }

        window.handlePasswordChange = function() {
            const form = document.getElementById('password-form');
            const formData = new FormData(form);
            
            const currentPassword = formData.get('current_password');
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            // Validate password match
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match.');
                return;
            }
            
            // Validate password strength
            if (!validatePasswordStrength(newPassword)) {
                alert('Password does not meet the requirements.');
                return;
            }
            
            // Simulate password change (replace with actual AJAX call)
            console.log('Changing password...');
            showSaveIndicator('Password changed successfully!');
            form.reset();
        }

        window.validatePasswordStrength = function(password) {
            // At least 8 characters, one uppercase, one lowercase, one number
            const minLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            
            return minLength && hasUpperCase && hasLowerCase && hasNumber;
        }

        // Sidebar toggle function - Global scope
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (sidebar && backdrop) {
                if (window.innerWidth <= 768) {
                    // Mobile behavior
                    sidebar.classList.toggle('active');
                    backdrop.classList.toggle('active');
                    backdrop.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                } else {
                    // Desktop behavior - toggle sidebar visibility
                    sidebar.classList.toggle('collapsed');
                }
            }
        }
    </script>
</body>
</html>



