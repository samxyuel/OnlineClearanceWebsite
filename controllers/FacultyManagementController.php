<?php
/**
 * Controller for the Regular Staff > Faculty Management page.
 * Handles authorization and prepares necessary data.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function handleFacultyManagementPageRequest() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../../pages/auth/login.php');
        exit;
    }

    $userId = (int)$auth->getUserId();

    try {
        $pdo = Database::getInstance()->getConnection();

        // Check permission flags (for conditional UI behavior)
        $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE status = 'Ongoing'")->fetchColumn() > 0;

        // Check if the user is a Program Head. A PH's access is based on their role, not a direct sector assignment.
        $roleCheck = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ?");
        $roleCheck->execute([$userId]);
        $userRole = $roleCheck->fetchColumn();

        $isProgramHead = (strcasecmp($userRole, 'Program Head') === 0);

        if ($isProgramHead) {
            // A Program Head inherently has access to manage faculty within their department scope.
            $hasFacultySignatoryAccess = true;
        } else {
            // For other staff roles, check for a direct assignment to the 'Faculty' sector.
            $facultySignatoryCheck = $pdo->prepare("SELECT COUNT(*) FROM sector_signatory_assignments WHERE user_id=? AND clearance_type='Faculty' AND is_active=1");
            $facultySignatoryCheck->execute([$userId]);
            $hasFacultySignatoryAccess = (int)$facultySignatoryCheck->fetchColumn() > 0;
        }
        // Store permission flags for use in the page
        $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
        $GLOBALS['hasFacultySignatoryAccess'] = $hasFacultySignatoryAccess;
        $GLOBALS['canPerformSignatoryActions'] = $hasActivePeriod && $hasFacultySignatoryAccess;

    } catch (Throwable $e) {
        // In a real app, you'd log this and show a user-friendly error page.
        die('System error. Please try again later.');
    }
}
