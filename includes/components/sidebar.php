<?php
// Dynamic Sidebar for Online Clearance System
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fetch user details from session (for future database integration)
$firstName = isset($_SESSION['first_name']) ? trim($_SESSION['first_name']) : '';
$middleName = isset($_SESSION['middle_name']) ? trim($_SESSION['middle_name']) : '';
$lastName = isset($_SESSION['last_name']) ? trim($_SESSION['last_name']) : '';
$roleId = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

$displayName = "User"; // Default display name

// Construct display name: "LastName, FirstName MiddleName"
$nameParts = [];
if (!empty($lastName)) {
    $nameParts[] = htmlspecialchars($lastName);
}
if (!empty($firstName)) {
    $nameParts[] = htmlspecialchars($firstName);
}

if (count($nameParts) == 2) { // Both Last and First name are present
    $displayName = $nameParts[0] . ", " . $nameParts[1];
    if (!empty($middleName)) {
        $displayName .= " " . htmlspecialchars($middleName);
    }
} elseif (!empty($nameParts)) { // Some name parts are present
    $displayName = implode(" ", $nameParts);
    if (count($nameParts) < 2 && !empty($middleName) && !in_array(htmlspecialchars($middleName), $nameParts)) {
        $displayName .= " " . htmlspecialchars($middleName);
    }
}

$isUserLoggedIn = isset($_SESSION['user_id']);
$userId = $isUserLoggedIn ? (int)$_SESSION['user_id'] : null;

// Map role_id to role name
$roleMap = [
    // Correct mapping based on roles table:
    1 => 'admin',
    2 => 'staff',           // Regular or special staff
    3 => 'student',
    4 => 'faculty',         // Faculty end-user
    5 => 'school_admin',    // School Administrator (special staff)
    6 => 'program_head'     // Program Head (special staff)
];

// Get current role
// Prefer role_name from session or DB to avoid mismatched numeric IDs
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
    // Fallback: numeric mapping or DB lookup
    $currentRole = isset($roleMap[$roleId]) ? $roleMap[$roleId] : null;
    if ($currentRole === null && $isUserLoggedIn) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ? LIMIT 1");
            $stmt->execute([ (int)$_SESSION['user_id'] ]);
            $rn = strtolower(trim((string)$stmt->fetchColumn()));
            if ($rn) {
                if ($rn === 'program head') $currentRole = 'program_head';
                elseif ($rn === 'school administrator') $currentRole = 'school_admin';
                elseif ($rn === 'admin') $currentRole = 'admin';
                elseif ($rn === 'faculty') $currentRole = 'faculty';
                elseif ($rn === 'student') $currentRole = 'student';
                elseif ($rn === 'regular staff') $currentRole = 'staff';
                else $currentRole = 'staff';
            }
        } catch (Throwable $e) {
            $currentRole = null;
        }
    }
}
if ($currentRole === null) { $currentRole = 'student'; }

// Define role-based navigation links for our current phase
$sidebarLinks = [
    // End Users - UNIFIED SYSTEM
    'student' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/end-user/dashboard.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance', 'link' => '../../pages/end-user/clearance.php'],
            // OLD LINKS (temporarily commented out for backup)
            // ['icon' => 'fas fa-home', 'text' => 'Dashboard (Old)', 'link' => '../../pages/student/dashboard.php'],
            // ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance (Old)', 'link' => '../../pages/student/clearance.php'],
            // ['icon' => 'fas fa-list-check', 'text' => 'Requirements', 'link' => '../../pages/student/requirements.php'],
            // ['icon' => 'fas fa-chart-line', 'text' => 'Progress', 'link' => '../../pages/student/progress.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/user_profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    'faculty' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/end-user/dashboard.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance', 'link' => '../../pages/end-user/clearance.php'],
            // OLD LINKS (temporarily commented out for backup)
            // ['icon' => 'fas fa-home', 'text' => 'Dashboard (Old)', 'link' => '../../pages/faculty/dashboard.php'],
            // ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance (Old)', 'link' => '../../pages/faculty/clearance.php'],
            // ['icon' => 'fas fa-list-check', 'text' => 'Requirements', 'link' => '../../pages/faculty/requirements.php'],
            // ['icon' => 'fas fa-chart-line', 'text' => 'Progress', 'link' => '../../pages/faculty/progress.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/user_profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    // System Operators:
    // Admin
    'admin' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/admin/dashboard.php'],
        //    ['icon' => 'fas fa-user-graduate', 'text' => 'Manage Students', 'link' => '../../pages/admin/StudentManagement.php'],
            ['icon' => 'fas fa-university', 'text' => 'College Management', 'link' => '../../pages/admin/CollegeStudentManagement.php'],
            ['icon' => 'fas fa-graduation-cap', 'text' => 'SHS Management', 'link' => '../../pages/admin/SeniorHighStudentManagement.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Manage Faculty', 'link' => '../../pages/admin/FacultyManagement.php'],
            ['icon' => 'fas fa-users', 'text' => 'Manage Staff', 'link' => '../../pages/admin/StaffManagement.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'Clearance Management', 'link' => '../../pages/admin/ClearanceManagement.php'],
            ['icon' => 'fas fa-book', 'text' => 'Manage Courses', 'link' => '../../pages/admin/CourseManagement.php'],
            ['icon' => 'fas fa-comment-dots', 'text' => 'Feedback', 'link' => '../../pages/admin/feedback.php'],
            // ['icon' => 'fas fa-user-plus', 'text' => 'Create Users', 'link' => '../../pages/admin/create-users.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/user_profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    // School Administrator
    'school_admin' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/school-administrator/dashboard.php'],
        //    ['icon' => 'fas fa-user-graduate', 'text' => 'Student Management', 'link' => '../../pages/school-administrator/StudentManagement.php'],
            ['icon' => 'fas fa-university', 'text' => 'College Student Management', 'link' => '../../pages/school-administrator/CollegeStudentManagement.php'],
            ['icon' => 'fas fa-graduation-cap', 'text' => 'Senior High School Student Management', 'link' => '../../pages/school-administrator/SeniorHighStudentManagement.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Faculty Management', 'link' => '../../pages/school-administrator/FacultyManagement.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/user_profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    // Program Head
    'program_head' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/program-head/dashboard.php'],
        //    ['icon' => 'fas fa-user-graduate', 'text' => 'Student Management', 'link' => '../../pages/program-head/StudentManagement.php'],
            ['icon' => 'fas fa-university', 'text' => 'College Student Management', 'link' => '../../pages/program-head/CollegeStudentManagement.php'],
            ['icon' => 'fas fa-graduation-cap', 'text' => 'Senior High School Student Management', 'link' => '../../pages/program-head/SeniorHighStudentManagement.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Faculty Management', 'link' => '../../pages/program-head/FacultyManagement.php'],
            
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/user_profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    // Staff or "Regular Staff"
    'staff' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/regular-staff/dashboard.php'],
        //    ['icon' => 'fas fa-user-graduate', 'text' => 'Student Management', 'link' => '../../pages/regular-staff/StudentManagement.php'],
            ['icon' => 'fas fa-university', 'text' => 'College Student Management', 'link' => '../../pages/regular-staff/CollegeStudentManagement.php'],
            ['icon' => 'fas fa-graduation-cap', 'text' => 'Senior High School Student Management', 'link' => '../../pages/regular-staff/SeniorHighStudentManagement.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Faculty Management', 'link' => '../../pages/regular-staff/FacultyManagement.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/user_profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ]
];

// Get current navigation links
// Default links by role
$currentSidebarLinks = $currentRole ? $sidebarLinks[$currentRole] : $sidebarLinks['student'];

// Add clearance link for staff/admins if they also have a faculty role
$staffRolesWithFacultyCheck = ['staff', 'program_head', 'school_admin'];
if (in_array($currentRole, $staffRolesWithFacultyCheck) && isset($_SESSION['has_faculty_role']) && $_SESSION['has_faculty_role']) {
    // Insert 'My Clearance' link after the first item (usually Dashboard)
    $myClearanceLink = ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance', 'link' => '../../pages/end-user/clearance.php'];
    array_splice($currentSidebarLinks['top'], 1, 0, [$myClearanceLink]);
}


// Context-aware enable/disable flags for operator roles
$enableStudentMgmt = true;
$enableFacultyMgmt = true;

if ($isUserLoggedIn) {
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo = Database::getInstance()->getConnection();

        if ($currentRole === 'program_head') {
            // COMMENTED OUT: Allow all Program Heads to access both Student and Faculty Management
            // Determine which sectors the PH is assigned to
            // $sql = "SELECT sec.sector_name FROM staff s JOIN departments d ON s.department_id = d.department_id JOIN sectors sec ON d.sector_id = sec.sector_id WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1";
            // $stmt = $pdo->prepare($sql);
            // $stmt->execute([$userId]);
            // $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            // $hasFaculty = in_array('Faculty', $rows, true);
            // $hasStudent = in_array('College', $rows, true) || in_array('Senior High School', $rows, true);
            // Enable per sector coverage
            // $enableStudentMgmt = $hasStudent;
            // $enableFacultyMgmt = $hasFaculty;
            
            // Allow all Program Heads to access both management pages
            $enableStudentMgmt = true;
            $enableFacultyMgmt = true;
        } elseif ($currentRole === 'staff') {
            // Disable both if no active clearance period
            $active = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE is_active = 1")->fetchColumn();
            if ($active === 0) { $enableStudentMgmt = false; $enableFacultyMgmt = false; }
            // Try to infer assignment by sector (best-effort)
            $sql = "SELECT DISTINCT COALESCE(sec.sector_name,'') AS sector FROM staff s LEFT JOIN departments d ON s.department_id = d.department_id LEFT JOIN sectors sec ON d.sector_id = sec.sector_id WHERE s.user_id = ? AND s.is_active = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $hasFaculty = in_array('Faculty', $rows, true);
            $hasStudent = in_array('College', $rows, true) || in_array('Senior High School', $rows, true);
            if ($hasFaculty && !$hasStudent) { $enableStudentMgmt = false; $enableFacultyMgmt = $enableFacultyMgmt && true; }
            if ($hasStudent && !$hasFaculty) { $enableFacultyMgmt = false; $enableStudentMgmt = $enableStudentMgmt && true; }
            // If neither detectable and period active, leave both as-is (admin will refine in Clearance Management later)
        }
    } catch (Throwable $e) {
        // On any error, fall back to default links
    }
}
?>

<!-- Sidebar HTML -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h3 class="sidebar-title">Online Clearance System</h3>
    </div>
    
    <div class="sidebar-content">
        <div class="sidebar-section">
            <h4>Main Menu</h4>
            <nav class="sidebar-nav">
                <?php foreach ($currentSidebarLinks['top'] as $link): ?>
                <?php
                    $text = $link['text'];
                    $disabled = false;
                    if ($currentRole === 'program_head') {
                        if ($text === 'Student Management' && !$enableStudentMgmt) $disabled = true;
                        if ($text === 'Faculty Management' && !$enableFacultyMgmt) $disabled = true;
                    }
                ?>
                <a href="<?php echo $disabled ? '#' : $link['link']; ?>" class="sidebar-link<?php echo $disabled ? ' disabled' : ''; ?>" <?php echo $disabled ? 'aria-disabled="true" title="Not available for your assignment" onclick="return false;"' : ''; ?>>
                    <i class="<?php echo $link['icon']; ?>"></i>
                    <span><?php echo $link['text']; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <div class="sidebar-section">
            <h4>Account</h4>
            <nav class="sidebar-nav">
                <?php foreach ($currentSidebarLinks['bottom'] as $link): ?>
                <?php $isLogout = (isset($link['text']) && strtolower(trim($link['text'])) === 'logout'); ?>
                <a href="<?php echo $link['link']; ?>" class="sidebar-link<?php echo $isLogout ? ' logout-link' : ''; ?>">
                    <i class="<?php echo $link['icon']; ?>"></i>
                    <span><?php echo $link['text']; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <div class="sidebar-credit">Online Clearance Website - A goSTI project</div>
</aside>

<!-- Sidebar Backdrop for Mobile -->
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<script>
// Sidebar component initialization - only run if not already handled by page
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    
    // Only initialize if page hasn't already set up sidebar functionality
    if (sidebar && !window.sidebarInitialized && !window.sidebarHandledByPage) {
        // Close sidebar when clicking backdrop
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    // Mobile behavior
                    sidebar.classList.remove('active');
                    this.style.display = 'none';
                    this.classList.remove('active');
                }
            });
        }
        
        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                // Desktop: ensure sidebar is not in mobile active state
                sidebar.classList.remove('active');
                if (backdrop) {
                    backdrop.style.display = 'none';
                    backdrop.classList.remove('active');
                }
            }
        });
        
        // Mark as initialized to prevent conflicts
        window.sidebarInitialized = true;
    }
});
</script> 