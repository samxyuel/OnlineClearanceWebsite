<?php
// Centralized Header Component for User Display
// This component automatically displays the correct logged-in user information

// Ensure session is started without conflicts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$userId = $auth->getUserId();
$userName = 'Unknown User';
$userRole = 'Unknown Role';
$userPosition = '';

try {
    $pdo = Database::getInstance()->getConnection();
    
    if ($userId) {
        // Get basic user information - try primary role first, then any role
        $userStmt = $pdo->prepare("
            SELECT u.first_name, u.last_name, u.username, r.role_name 
            FROM users u 
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_primary = 1
            LEFT JOIN roles r ON ur.role_id = r.role_id 
            WHERE u.user_id = ?
        ");
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // If no primary role found, get any role for the user
        if (!$userData || !$userData['role_name']) {
            $userStmt = $pdo->prepare("
                SELECT u.first_name, u.last_name, u.username, r.role_name 
                FROM users u 
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id 
                WHERE u.user_id = ? AND r.role_name IS NOT NULL
                LIMIT 1
            ");
            $userStmt->execute([$userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($userData) {
            $userName = trim($userData['first_name'] . ' ' . $userData['last_name']);
            $userRole = $userData['role_name'] ?? 'User';
            
            // Get staff position if user is staff
            if ($userRole === 'Staff') {
                $staffStmt = $pdo->prepare("
                    SELECT d.designation_name 
                    FROM staff s 
                    JOIN designations d ON s.designation_id = d.designation_id 
                    WHERE s.user_id = ? AND s.is_active = 1
                ");
                $staffStmt->execute([$userId]);
                $staffData = $staffStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($staffData) {
                    $userPosition = $staffData['designation_name'];
                }
            }
        }
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Header component error: " . $e->getMessage());
    
    // Fallback to session data if database query fails
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        $userName = trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
    } else {
        $userName = 'Unknown User';
    }
    $userRole = $_SESSION['role_name'] ?? 'Unknown Role';
}

// Format display name based on role and position
$displayName = $userName;
if ($userRole === 'Staff' && $userPosition) {
    $displayName .= " ({$userRole} - {$userPosition})";
} elseif ($userRole !== 'User') {
    $displayName .= " ({$userRole})";
}
?>

<!-- Header -->
<header class="navbar">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <h1>goSTI</h1>
                </div>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($displayName); ?></span>
                <div class="user-dropdown">
                    <button class="dropdown-toggle">▼</button>
                    <div class="dropdown-menu">
                        <a href="../../pages/shared/user_profile.php">Profile</a>
                        <a href="../../pages/shared/settings.php">Settings</a>
                        <a href="../../pages/auth/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
