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

<!-- Modal styles for confirmation dialogs (ensure brand-consistent styling across pages) -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<!-- Header -->
<header class="navbar">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <!-- <h1>goSTI</h1>
                    -->
                    <img src="../../assets/images/STI_Lucena_Logo.png" alt="STI College Lucena Logo">
                </div>
            </div>
            <div class="user-info">
                <span class="user-name" id="userDisplayName"><?php echo htmlspecialchars($displayName); ?></span>
                <div class="user-dropdown">
                    <button class="dropdown-toggle">▼</button>
                    <div class="dropdown-menu">
                        <a href="../../pages/shared/user_profile.php">Profile</a>
                        <a href="../../pages/shared/settings.php">Settings</a>
                        <a href="../../pages/auth/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Logout Confirmation Modal (brand-styled using modals.css) -->
<div class="modal-overlay" id="logoutConfirmModal" style="display: none;">
    <div class="modal-window">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-sign-out-alt"></i> Confirm Logout</h3>
            <button class="modal-close" id="logoutModalClose" aria-label="Close">&times;</button>
        </div>
        <div class="modal-content-area">
            <p>Are you sure you want to log out?</p>
        </div>
        <div class="modal-actions">
            <button class="modal-action-secondary" id="logoutCancelBtn">Cancel</button>
            <button class="modal-action-primary" id="logoutConfirmBtn">Logout</button>
        </div>
    </div>
    <div class="modal-backdrop"></div>
    <!-- backdrop element for consistency if used elsewhere -->
    
</div>

<script>
// Responsive User Name Display
function updateUserDisplayName() {
    const userDisplayName = document.getElementById('userDisplayName');
    if (!userDisplayName) return;
    
    const fullName = userDisplayName.textContent;
    const screenWidth = window.innerWidth;
    
    if (screenWidth <= 320) {
        // Small mobile: Very abbreviated (First initial + Last initial + Role)
        userDisplayName.textContent = abbreviateName(fullName, 'small');
    } else if (screenWidth <= 480) {
        // Mobile: Abbreviated (First + Last initial + Role)
        userDisplayName.textContent = abbreviateName(fullName, 'medium');
    } else if (screenWidth <= 768) {
        // Tablet: Slightly abbreviated (First + Last initial + Role)
        userDisplayName.textContent = abbreviateName(fullName, 'tablet');
    } else {
        // Desktop: Full name
        userDisplayName.textContent = fullName;
    }
}

function abbreviateName(fullName, size) {
    // Extract name and role from format: "John Kristoffer Tibor (Faculty)"
    const roleMatch = fullName.match(/\(([^)]+)\)$/);
    const role = roleMatch ? roleMatch[1] : '';
    const namePart = fullName.replace(/\s*\([^)]+\)$/, '').trim();
    
    const nameParts = namePart.split(' ');
    if (nameParts.length < 2) return fullName;
    
    const firstName = nameParts[0];
    const lastName = nameParts[nameParts.length - 1];
    
    switch (size) {
        case 'small':
            // Very abbreviated: J. Tibor (Faculty) -> J. T. (Faculty)
            return `${firstName.charAt(0)}. ${lastName.charAt(0)}.${role ? ` (${role})` : ''}`;
        case 'medium':
            // Mobile: John Kristoffer Tibor (Faculty) -> John T. (Faculty)
            return `${firstName} ${lastName.charAt(0)}.${role ? ` (${role})` : ''}`;
        case 'tablet':
            // Tablet: John Kristoffer Tibor (Faculty) -> John K. Tibor (Faculty)
            if (nameParts.length > 2) {
                const middleInitial = nameParts[1].charAt(0);
                return `${firstName} ${middleInitial}. ${lastName}${role ? ` (${role})` : ''}`;
            }
            return `${firstName} ${lastName}${role ? ` (${role})` : ''}`;
        default:
            return fullName;
    }
}

// Update on load and resize
document.addEventListener('DOMContentLoaded', updateUserDisplayName);
window.addEventListener('resize', updateUserDisplayName);
</script>

<script>
// Logout confirmation wiring
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('logoutConfirmModal');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    const cancelBtn = document.getElementById('logoutCancelBtn');
    const closeBtn = document.getElementById('logoutModalClose');
    let pendingLogoutHref = null;

    function openLogoutModal(href) {
        pendingLogoutHref = href;
        if (modal) {
            modal.style.display = 'flex';
            requestAnimationFrame(() => modal.classList.add('active'));
        }
    }

    function closeLogoutModal() {
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 200);
        }
        pendingLogoutHref = null;
    }

    // Attach handlers to any logout links available on the page
    function bindLogoutLinks() {
        const links = document.querySelectorAll('a.logout-link[href]');
        links.forEach(link => {
            if (link.__logoutBound) return; // prevent double-binding
            link.addEventListener('click', function(e) {
                // Only intercept left-click/enter activations
                e.preventDefault();
                openLogoutModal(this.getAttribute('href'));
            });
            link.__logoutBound = true;
        });
    }

    bindLogoutLinks();

    // Confirm action
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (pendingLogoutHref) {
                window.location.href = pendingLogoutHref;
            }
        });
    }

    // Cancel/close actions
    if (cancelBtn) cancelBtn.addEventListener('click', closeLogoutModal);
    if (closeBtn) closeBtn.addEventListener('click', closeLogoutModal);
    // Close on overlay click (outside window)
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal || (e.target && e.target.classList && e.target.classList.contains('modal-backdrop'))) {
                closeLogoutModal();
            }
        });
        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeLogoutModal();
            }
        });
    }
});
</script>

<!-- Universal Modal Handler - Handles all modal close buttons (X, Cancel, Escape, outside click) -->
<script src="../../assets/js/modal-handler.js"></script>
