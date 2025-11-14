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
        
        // 1. Get all of the staff member's active designations from both staff table and assignments table.
        $designationsStmt = $pdo->prepare("
            (SELECT s.designation_id, d.designation_name
             FROM staff s
             JOIN designations d ON s.designation_id = d.designation_id
             WHERE s.user_id = ? AND s.is_active = 1 AND d.is_active = 1 AND s.designation_id IS NOT NULL)
            UNION
            (SELECT uda.designation_id, d.designation_name
             FROM user_designation_assignments uda
             JOIN designations d ON uda.designation_id = d.designation_id
             WHERE uda.user_id = ? AND uda.is_active = 1 AND d.is_active = 1)
        ");
        $designationsStmt->execute([$userId, $userId]);
        $userDesignations = $designationsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Check permission flags
        $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty'")->fetchColumn() > 0;

        $userSignatoryDesignations = [];
        if (!empty($userDesignations)) {
            // 2. Check which of these designations are assigned to sign for 'Faculty'
            $placeholders = implode(',', array_fill(0, count($userDesignations), '?'));
            $facultySignatoryCheck = $pdo->prepare("
                SELECT DISTINCT designation_id 
                FROM sector_signatory_assignments 
                WHERE designation_id IN ($placeholders) AND clearance_type = 'Faculty' AND is_active = 1
            ");
            $designationIds = array_column($userDesignations, 'designation_id');
            $facultySignatoryCheck->execute($designationIds);
            $validSignatoryIds = $facultySignatoryCheck->fetchAll(PDO::FETCH_COLUMN);

            // Filter the user's designations to only those valid for this sector
            foreach ($userDesignations as $designation) {
                if (in_array($designation['designation_id'], $validSignatoryIds)) {
                    $userSignatoryDesignations[] = $designation;
                }
            }
        }
        
        $hasFacultySignatoryAccess = !empty($userSignatoryDesignations);
        $canPerformSignatoryActions = $hasActivePeriod && $hasFacultySignatoryAccess;

        // Store permission flags for use in the page
        $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
        $GLOBALS['hasFacultySignatoryAccess'] = $hasFacultySignatoryAccess;
        $GLOBALS['canPerformSignatoryActions'] = $canPerformSignatoryActions;
        $GLOBALS['userSignatoryDesignations'] = $userSignatoryDesignations; // Make designations available to the page

    } catch (Throwable $e) {
        // In a real app, you'd log this and show a user-friendly error page.
        die('System error. Please try again later.');
    }
}
