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
$phone = '';
if (isset($_SESSION['contact_number'])) {
    $phone = trim($_SESSION['contact_number']);
} elseif (isset($_SESSION['phone'])) {
    $phone = trim($_SESSION['phone']);
}
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

// Placeholders (values will be hydrated via profile API)
$displayName = trim($displayName);
if ($displayName === '') {
    $displayName = 'Loading...';
}
$accountStatus = 'Loading...';
$lastLogin = 'Loading...';

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
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
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
                <h1 class="user-name" id="profileUserName"><?php echo htmlspecialchars($displayName); ?></h1>
                <div class="last-login-indicator">
                    <i class="fas fa-clock"></i>
                    <span class="last-login-label">Last Login:</span>
                    <span class="last-login-value" id="lastLoginValue"><?php echo htmlspecialchars($lastLogin); ?></span>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="status-info">
                <span class="account-status-badge status-loading" id="accountStatusBadge" data-status="loading">
                        <i class="fas fa-shield-alt"></i>
                        <span id="accountStatusLabel">Loading...</span>
                    </span>
                    <span class="role-badge role-<?php echo htmlspecialchars($currentRole); ?>" id="roleBadge">
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
            <!-- User Information Card -->
            <div class="info-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <i class="fas fa-user"></i>
                        <h3>User Information</h3>
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
                            <span class="value editable" data-field="profile.full_name" data-field-edit="full_name" contenteditable="false">Loading...</span>
                        <?php else: ?>
                            <span class="value" data-field="profile.full_name">Loading...</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Student/Employee Number - Not editable -->
                    <?php if ($currentRole === 'student'): ?>
                        <div class="info-item">
                            <span class="label">Student Number:</span>
                            <span class="value" data-field="profile.student_number">N/A</span>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <span class="label">Employee Number:</span>
                            <span class="value" data-field="profile.employee_number">N/A</span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Email - Editable for all users -->
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value editable" data-field="profile.email" data-field-edit="email" contenteditable="false">Loading...</span>
                    </div>
                    
                    <!-- Phone Number - Editable for all users -->
                    <div class="info-item">
                        <span class="label">Phone Number:</span>
                        <span class="value editable" data-field="profile.contact_number" data-field-edit="contact_number" contenteditable="false">N/A</span>
                    </div>
                    
                    <?php if ($currentRole === 'admin'): ?>
                        <div class="info-item">
                            <span class="label">Last Login:</span>
                            <span class="value last-login-value" data-last-login-field="profile.last_login" data-fallback="Never">Never</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($currentRole === 'faculty'): ?>
                        <div class="info-item">
                            <span class="label">Employment Status:</span>
                            <span class="value status-badge" data-field="profile.employment_status" data-fallback="N/A">N/A</span>
                        </div>
                    <?php endif; ?>
                    <?php if (in_array($currentRole, ['staff', 'school_admin', 'program_head'])): ?>
                        <div class="info-item">
                            <span class="label">Designation:</span>
                            <span class="value" data-field="profile.designation" data-fallback="N/A">N/A</span>
                        </div>
                    <?php endif; ?>
                    <?php if (in_array($currentRole, ['admin', 'faculty', 'staff', 'school_admin', 'program_head'])): ?>
                        <div class="info-item">
                            <span class="label">Employment Date:</span>
                            <?php if (in_array($currentRole, ['admin', 'staff', 'school_admin', 'program_head'])): ?>
                                <span class="value editable" data-field="profile.employment_date" data-field-edit="employment_date" data-fallback="N/A" contenteditable="false">N/A</span>
                            <?php else: ?>
                                <span class="value" data-field="profile.employment_date" data-fallback="N/A">N/A</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($currentRole === 'program_head'): ?>
                        <div class="info-item program-head-scope">
                            <span class="label">Department Handled:</span>
                            <span class="value scope-value" data-scope-field="profile.department" data-fallback="Not Assigned">
                                <i class="fas fa-shield-alt"></i>
                                <span class="scope-text">Not Assigned</span>
                            </span>
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
                            <span class="value" data-field="profile.year_level">N/A</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Section:</span>
                            <span class="value" data-field="profile.section">N/A</span>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="label">Sector:</span>
                        <span class="value" data-field="profile.sector">N/A</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Department:</span>
                        <span class="value" data-field="profile.department">N/A</span>
                    </div>
                    <?php if ($currentRole === 'student'): ?>
                        <div class="info-item">
                            <span class="label">Program:</span>
                            <span class="value" data-field="profile.program">N/A</span>
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

    <!-- Custom Confirmation Modal for Clear Security Questions -->
    <div class="modal-overlay" id="clearSecurityQuestionsModal" style="display: none;">
        <div class="modal-window modal-warning" style="max-width: 500px;">
            <button class="modal-close" onclick="closeClearSecurityQuestionsModal()">&times;</button>
            
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Clear Security Questions
                </h2>
                <div class="modal-supporting-text" id="clearSecurityQuestionsMessage">
                    Are you sure you want to clear all security questions? This will clear the form but not delete your saved questions. To delete saved questions, you need to save new ones.
                </div>
            </div>
            
            <div class="modal-content-area">
                <div class="validation-message warning">
                    <i class="fas fa-info-circle"></i>
                    <span>This action will only clear the form fields. Your saved security questions will remain in the database until you save new ones.</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="modal-action-secondary" onclick="closeClearSecurityQuestionsModal()">Cancel</button>
                <button class="modal-action-primary" onclick="confirmClearSecurityQuestions()">Clear All</button>
            </div>
        </div>
    </div>

    <script>
        const PROFILE_API_URL = '../../api/profile_info.php';
        let profileDataCache = null;

        function getValueByPath(obj, path) {
            if (!obj || !path) return undefined;
            return path.split('.').reduce((acc, key) => (acc && acc[key] !== undefined ? acc[key] : undefined), obj);
        }

        function formatDateTime(value) {
            if (!value) return 'Never';
            const parsed = value.includes('T') ? new Date(value) : new Date(value.replace(' ', 'T'));
            if (Number.isNaN(parsed.getTime())) return value;
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }).format(parsed);
        }

        function formatDate(value) {
            if (!value) return null;
            const parsed = value.includes('T') ? new Date(value) : new Date(value);
            if (Number.isNaN(parsed.getTime())) {
                return value;
            }
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            }).format(parsed);
        }

        function setTextContent(selectorOrElement, value, fallback = 'N/A') {
            const el = typeof selectorOrElement === 'string'
                ? document.querySelector(selectorOrElement)
                : selectorOrElement;
            if (!el) return;
            const finalValue = value === null || value === undefined || value === '' ? fallback : value;
            el.textContent = finalValue;
        }

        function setFieldValue(path, value) {
            document.querySelectorAll(`[data-field="${path}"]`).forEach(el => {
                const fallback = el.dataset.fallback || 'N/A';
                setTextContent(el, value, fallback);
            });
        }

        function updateAccountStatusBadge(accountStatus) {
            const badge = document.getElementById('accountStatusBadge');
            const label = document.getElementById('accountStatusLabel') || badge;
            if (!badge) return;

            const statusLabel = (accountStatus && (accountStatus.label || accountStatus.raw || accountStatus)) || 'Unknown';
            const statusKey = (accountStatus && (accountStatus.raw || accountStatus.label || accountStatus))
                ? String(accountStatus.raw || accountStatus.label || accountStatus).toLowerCase().replace(/\s+/g, '-')
                : 'unknown';

            badge.className = `account-status-badge status-${statusKey}`;
            badge.setAttribute('data-status', statusKey);
            setTextContent(label, statusLabel, 'Unknown');
        }

        function updateRoleBadge(role) {
            const badge = document.getElementById('roleBadge');
            if (!badge) return;
            const roleLabel = role?.label || 'User';
            const roleKey = role?.key || roleLabel.toLowerCase().replace(/\s+/g, '_');
            badge.className = `role-badge role-${roleKey}`;
            setTextContent(badge, roleLabel, 'User');
        }

        function buildDisplayName(profile, user) {
            if (profile?.full_name) return profile.full_name;
            const parts = [
                user?.first_name,
                user?.middle_name,
                user?.last_name
            ].filter(Boolean);
            return parts.length ? parts.join(' ') : 'User';
        }

        async function loadProfileData() {
            try {
                const response = await fetch(PROFILE_API_URL, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                if (!response.ok) {
                    throw new Error(`Failed to load profile data (HTTP ${response.status})`);
                }

                const payload = await response.json();
                if (!payload.success || !payload.data) {
                    throw new Error(payload.error || 'Profile data unavailable.');
                }

                profileDataCache = payload.data;
                populateProfile(payload.data);
            } catch (error) {
                console.error('Error loading profile data:', error);
                showProfileLoadError(error.message);
            }
        }

        function populateProfile(data) {
            const user = data.user || {};
            const profile = data.profile || {};
            const role = data.role || {};

            const displayName = buildDisplayName(profile, user);
            setTextContent('#profileUserName', displayName, 'User');
            setFieldValue('profile.full_name', displayName);

            updateAccountStatusBadge(profile.account_status);
            updateRoleBadge(role);

            setTextContent('#lastLoginValue', formatDateTime(profile.last_login));

            setFieldValue('profile.student_number', profile.student_number);
            setFieldValue('profile.year_level', profile.year_level);
            setFieldValue('profile.section', profile.section);
            setFieldValue('profile.sector', profile.sector);
            setFieldValue('profile.department', profile.department);
            setFieldValue('profile.program', profile.program);
            setFieldValue('profile.email', profile.email);
            setFieldValue('profile.contact_number', profile.contact_number);
            setFieldValue('profile.employee_number', profile.employee_number);
            setFieldValue('profile.employment_status', profile.employment_status);
            setFieldValue('profile.employment_date', formatDate(profile.employment_date));
            setFieldValue('profile.designation', profile.designation);
            const scopeElements = document.querySelectorAll('.scope-value');
            console.debug('[Profile] Program head scope elements:', scopeElements);
            document.querySelectorAll('.scope-value').forEach(el => {
                const textEl = el.querySelector('.scope-text');
                const fallback = el.dataset.fallback || 'Not Assigned';
                const department = profile.department;
                const scopeText = department ? department : fallback;
                console.debug('[Profile] Setting department handled text:', { department, scopeText, el });
                if (textEl) {
                    textEl.textContent = scopeText;
                } else {
                    setTextContent(el, scopeText, fallback);
                }
            });

            document.querySelectorAll('.last-login-value').forEach(el => {
                const fallback = el.dataset.fallback || 'Never';
                const formatted = formatDateTime(profile.last_login);
                console.debug('[Profile] Admin last login formatted:', { raw: profile.last_login, formatted });
                el.textContent = formatted || fallback;
            });

        console.group('Profile API');
        console.debug('Profile role-specific data:', data.role_specific);
        console.log('Profile data snapshot:', data);
        console.groupEnd();

            setEditableFieldBaseline();
        }

        function showProfileLoadError(message) {
            setTextContent('#profileUserName', 'Unable to load profile', 'Error');
            setTextContent('#accountStatusLabel', 'Unavailable', 'Unavailable');
            setTextContent('#lastLoginValue', 'Unavailable', 'Unavailable');
            document.querySelectorAll('[data-field]').forEach(el => {
                const fallback = el.dataset.fallback || 'N/A';
                setTextContent(el, fallback, fallback);
            });

            const errorBanner = document.createElement('div');
            errorBanner.className = 'profile-error-banner';
            errorBanner.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>${message}</span>
            `;
            document.querySelector('.profile-container')?.prepend(errorBanner);
        }

        function getEditableFieldName(field) {
            return field.dataset.fieldEdit || field.dataset.field || field.getAttribute('data-field');
        }

        function setEditableFieldBaseline() {
            document.querySelectorAll('.info-item .value.editable').forEach(field => {
                field.dataset.originalValue = field.textContent.trim();
                field.classList.remove('changed');
            });
        }

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
                const originalValue = field.textContent.trim();
                field.dataset.originalValue = originalValue;
                
                field.addEventListener('input', function() {
                    if (this.textContent.trim() !== this.dataset.originalValue) {
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
            const fieldName = getEditableFieldName(field);
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
            loadProfileData();
            initEditableFields();
            loadSecurityQuestions(); // Load existing security questions
            
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
                field.dataset.originalValue = field.textContent.trim();
                
                field.addEventListener('input', function() {
                    if (this.textContent.trim() !== this.dataset.originalValue) {
                        this.classList.add('changed');
                        console.log('Field changed:', getEditableFieldName(this)); // Debug log
                    } else {
                        this.classList.remove('changed');
                    }
                });
                
                field.addEventListener('blur', function() {
                    if (this.classList.contains('changed')) {
                        console.log('Field blurred with changes:', getEditableFieldName(this)); // Debug log
                    }
                });
            });
        }

        // Security questions functions - Global scope
        window.handleSecurityQuestionsSubmit = async function() {
            const form = document.getElementById('security-questions-form');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
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
                alert('Please provide answers for all 3 security questions. You must set up all security questions together.');
                return;
            }
            
            // Validate that all questions are selected
            if (questions.some(q => !q || !q.trim())) {
                alert('Please select all 3 security questions. You must set up all security questions together.');
                return;
            }
            
            // Disable submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            try {
                const response = await fetch('../../api/users/security_questions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        questions: questions,
                        answers: answers
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSaveIndicator('Security questions saved successfully!');
                    // Don't reset form - keep answers visible
                    // Reload questions to show updated state (this will update the selected questions)
                    loadSecurityQuestions();
                } else {
                    alert('Error: ' + (result.message || 'Failed to save security questions'));
                }
            } catch (error) {
                console.error('Error saving security questions:', error);
                alert('An error occurred while saving security questions. Please try again.');
            } finally {
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Security Questions';
                }
            }
        }
        
        // Load existing security questions
        async function loadSecurityQuestions() {
            try {
                const response = await fetch('../../api/users/security_questions.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                const form = document.getElementById('security-questions-form');
                const question1Select = document.getElementById('question1');
                const question2Select = document.getElementById('question2');
                const question3Select = document.getElementById('question3');
                const answer1Input = document.getElementById('answer1');
                const answer2Input = document.getElementById('answer2');
                const answer3Input = document.getElementById('answer3');
                const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
                
                // Check for partial setup
                if (result.success && result.has_partial) {
                    // Show warning message
                    let warningMsg = document.getElementById('partial-setup-warning');
                    if (!warningMsg) {
                        warningMsg = document.createElement('div');
                        warningMsg.id = 'partial-setup-warning';
                        warningMsg.className = 'validation-message warning';
                        warningMsg.style.marginBottom = '20px';
                        warningMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Incomplete Setup:</strong> You have ' + result.question_count + ' security question(s) saved. You must complete all 3 security questions together. Please fill in all questions and answers to continue.';
                        const formContainer = form ? form.parentElement : null;
                        if (formContainer) {
                            formContainer.insertBefore(warningMsg, form);
                        }
                    }
                    
                    // Disable submit button initially (but allow field editing)
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.6';
                        submitBtn.style.cursor = 'not-allowed';
                    }
                    
                    // Still try to load what exists
                    if (result.has_questions && result.data) {
                        const questions = result.data.questions;
                        if (question1Select && questions[0] && questions[0].key) {
                            question1Select.value = questions[0].key;
                            if (answer1Input && questions[0].answer) {
                                answer1Input.value = questions[0].answer;
                            }
                        }
                        if (question2Select && questions[1] && questions[1].key) {
                            question2Select.value = questions[1].key;
                            if (answer2Input && questions[1].answer) {
                                answer2Input.value = questions[1].answer;
                            }
                        }
                        if (question3Select && questions[2] && questions[2].key) {
                            question3Select.value = questions[2].key;
                            if (answer3Input && questions[2].answer) {
                                answer3Input.value = questions[2].answer;
                            }
                        }
                    }
                    
                    // Add event listeners to enable submit button when all fields are filled
                    function checkFormCompletion() {
                        const q1 = question1Select ? question1Select.value : '';
                        const q2 = question2Select ? question2Select.value : '';
                        const q3 = question3Select ? question3Select.value : '';
                        const a1 = answer1Input ? answer1Input.value.trim() : '';
                        const a2 = answer2Input ? answer2Input.value.trim() : '';
                        const a3 = answer3Input ? answer3Input.value.trim() : '';
                        
                        const allFilled = q1 && q2 && q3 && a1 && a2 && a3;
                        
                        if (submitBtn) {
                            if (allFilled) {
                                submitBtn.disabled = false;
                                submitBtn.style.opacity = '1';
                                submitBtn.style.cursor = 'pointer';
                                if (warningMsg) {
                                    warningMsg.style.display = 'none';
                                }
                            } else {
                                submitBtn.disabled = true;
                                submitBtn.style.opacity = '0.6';
                                submitBtn.style.cursor = 'not-allowed';
                                if (warningMsg) {
                                    warningMsg.style.display = 'block';
                                }
                            }
                        }
                    }
                    
                    // Check initial state
                    checkFormCompletion();
                    
                    // Add listeners to all form fields
                    [question1Select, question2Select, question3Select, answer1Input, answer2Input, answer3Input].forEach(field => {
                        if (field) {
                            field.addEventListener('change', checkFormCompletion);
                            field.addEventListener('input', checkFormCompletion);
                        }
                    });
                } else if (result.success && result.has_questions && result.data) {
                    // Normal case: all 3 questions are set up
                    const questions = result.data.questions;
                    
                    // Enable form
                    if (form) {
                        form.style.opacity = '1';
                        form.style.pointerEvents = 'auto';
                        
                        // Remove warning message if exists
                        const warningMsg = document.getElementById('partial-setup-warning');
                        if (warningMsg) {
                            warningMsg.remove();
                        }
                    }
                    
                    // Set selected questions and answers
                    if (question1Select && questions[0]) {
                        question1Select.value = questions[0].key;
                        if (answer1Input && questions[0].answer) {
                            answer1Input.value = questions[0].answer;
                        }
                    }
                    if (question2Select && questions[1]) {
                        question2Select.value = questions[1].key;
                        if (answer2Input && questions[1].answer) {
                            answer2Input.value = questions[1].answer;
                        }
                    }
                    if (question3Select && questions[2]) {
                        question3Select.value = questions[2].key;
                        if (answer3Input && questions[2].answer) {
                            answer3Input.value = questions[2].answer;
                        }
                    }
                } else {
                    // No questions set up - enable form
                    if (form) {
                        form.style.opacity = '1';
                        form.style.pointerEvents = 'auto';
                        
                        // Remove warning message if exists
                        const warningMsg = document.getElementById('partial-setup-warning');
                        if (warningMsg) {
                            warningMsg.remove();
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading security questions:', error);
                // Silently fail - user can still set questions
            }
        }

        window.clearSecurityQuestions = function() {
            openClearSecurityQuestionsModal();
        }
        
        function openClearSecurityQuestionsModal() {
            const modal = document.getElementById('clearSecurityQuestionsModal');
            if (modal) {
                modal.style.display = 'flex';
                requestAnimationFrame(() => modal.classList.add('active'));
            }
        }
        
        function closeClearSecurityQuestionsModal() {
            const modal = document.getElementById('clearSecurityQuestionsModal');
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 200);
            }
        }
        
        function confirmClearSecurityQuestions() {
            const form = document.getElementById('security-questions-form');
            if (form) {
                // Reset the form (clears all selections and answers)
                form.reset();
                showSaveIndicator('Security questions form cleared.');
            }
            closeClearSecurityQuestionsModal();
        }
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('clearSecurityQuestionsModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeClearSecurityQuestionsModal();
                    }
                });
            }
        });

        window.handlePasswordChange = async function() {
            const form = document.getElementById('password-form');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
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
                alert('Password does not meet the requirements. Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.');
                return;
            }
            
            // Disable submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            }
            
            try {
                const response = await fetch('../../api/users/password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSaveIndicator('Password changed successfully!');
                    form.reset();
                } else {
                    alert('Error: ' + (result.message || 'Failed to change password'));
                }
            } catch (error) {
                console.error('Error changing password:', error);
                alert('An error occurred while changing password. Please try again.');
            } finally {
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-key"></i> Update Password';
                }
            }
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



