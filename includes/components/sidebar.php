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

// Map role_id to role name
$roleMap = [
    1 => 'admin',
    2 => 'faculty', 
    3 => 'staff',
    4 => 'program_head',
    5 => 'student',
    6 => 'school_admin'
];

// Get current role
$currentRole = isset($roleMap[$roleId]) ? $roleMap[$roleId] : 'student'; // Default to student for demo

// Define role-based navigation links for our current phase
$sidebarLinks = [
    // End Users
    'student' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/student/dashboard.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance', 'link' => '../../pages/student/clearance.php'],
            ['icon' => 'fas fa-list-check', 'text' => 'Requirements', 'link' => '../../pages/student/requirements.php'],
            ['icon' => 'fas fa-chart-line', 'text' => 'Progress', 'link' => '../../pages/student/progress.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    'faculty' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/faculty/dashboard.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'My Clearance', 'link' => '../../pages/faculty/clearance.php'],
            ['icon' => 'fas fa-list-check', 'text' => 'Requirements', 'link' => '../../pages/faculty/requirements.php'],
            ['icon' => 'fas fa-chart-line', 'text' => 'Progress', 'link' => '../../pages/faculty/progress.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    // System Operators
    'admin' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/admin/dashboard.php'],
            ['icon' => 'fas fa-user-graduate', 'text' => 'Manage Students', 'link' => '../../pages/admin/StudentManagement.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Manage Faculty', 'link' => '../../pages/admin/FacultyManagement.php'],
            ['icon' => 'fas fa-users', 'text' => 'Manage Staff', 'link' => '../../pages/admin/StaffManagement.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'Clearance Forms', 'link' => '../../pages/admin/ClearanceManagement.php'],
            ['icon' => 'fas fa-book', 'text' => 'Manage Courses', 'link' => '../../pages/admin/manage-courses.php'],
            ['icon' => 'fas fa-comment-dots', 'text' => 'Feedback', 'link' => '../../pages/admin/feedback.php'],
            ['icon' => 'fas fa-user-plus', 'text' => 'Create Users', 'link' => '../../pages/admin/create-users.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    'school_admin' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/admin/dashboard.php'],
            ['icon' => 'fas fa-user-graduate', 'text' => 'Manage Students', 'link' => '../../pages/admin/manage-students.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Manage Faculty', 'link' => '../../pages/admin/manage-faculty.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'Clearance Management', 'link' => '../../pages/admin/clearance-forms.php'],
            ['icon' => 'fas fa-comment-dots', 'text' => 'Feedback', 'link' => '../../pages/admin/feedback.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    'program_head' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/admin/dashboard.php'],
            ['icon' => 'fas fa-user-graduate', 'text' => 'Program Students', 'link' => '../../pages/admin/manage-students.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Program Faculty', 'link' => '../../pages/admin/manage-faculty.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'Program Clearance', 'link' => '../../pages/admin/clearance-forms.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ],
    'staff' => [
        'top' => [
            ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'link' => '../../pages/admin/dashboard.php'],
            ['icon' => 'fas fa-user-graduate', 'text' => 'Assist Students', 'link' => '../../pages/admin/manage-students.php'],
            ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Assist Faculty', 'link' => '../../pages/admin/manage-faculty.php'],
            ['icon' => 'fas fa-file-alt', 'text' => 'Clearance Support', 'link' => '../../pages/admin/clearance-forms.php'],
            ['icon' => 'fas fa-history', 'text' => 'Audit Trail', 'link' => '../../pages/admin/audit-trail.php']
        ],
        'bottom' => [
            ['icon' => 'fas fa-user', 'text' => 'Profile', 'link' => '../../pages/shared/profile.php'],
            ['icon' => 'fas fa-cog', 'text' => 'Settings', 'link' => '../../pages/shared/settings.php'],
            ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'link' => '../../pages/auth/logout.php']
        ]
    ]
];

// Get current navigation links
$currentSidebarLinks = $currentRole ? $sidebarLinks[$currentRole] : $sidebarLinks['student'];
?>

<!-- Sidebar HTML -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <!--<h3>🎓 goSTI</h3>-->
    </div>
    
    <div class="sidebar-content">
        <div class="sidebar-section">
            <h4>Main Menu</h4>
            <nav class="sidebar-nav">
                <?php foreach ($currentSidebarLinks['top'] as $link): ?>
                <a href="<?php echo $link['link']; ?>" class="sidebar-link">
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
                <a href="<?php echo $link['link']; ?>" class="sidebar-link">
                    <i class="<?php echo $link['icon']; ?>"></i>
                    <span><?php echo $link['text']; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</aside>

<!-- Sidebar Backdrop for Mobile -->
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<script>
// Close sidebar when clicking backdrop
document.getElementById('sidebar-backdrop').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('active');
    this.style.display = 'none';
});

// Close sidebar on window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    
    if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
        backdrop.style.display = 'none';
    }
});
</script> 