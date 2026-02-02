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
        $isProgramHeadDesignation = null;
        
        // First, check if user has Program Head designation
        foreach ($userDesignations as $designation) {
            if (strcasecmp($designation['designation_name'], 'Program Head') === 0) {
                $isProgramHeadDesignation = $designation;
                break;
            }
        }
        
        // 2. Handle Program Head separately - they use sector_clearance_settings, not sector_signatory_assignments
        if ($isProgramHeadDesignation) {
            $settingStmt = $pdo->prepare("
                SELECT include_program_head 
                FROM sector_clearance_settings 
                WHERE clearance_type = 'Faculty' AND include_program_head = 1
            ");
            $settingStmt->execute();
            if ($settingStmt->fetchColumn()) {
                // Program Head is enabled for Faculty sector
                $userSignatoryDesignations[] = $isProgramHeadDesignation;
            }
        }
        
        // 3. Check sector_signatory_assignments for other designations (not Program Head)
        $otherDesignations = array_filter($userDesignations, function($d) {
            return strcasecmp($d['designation_name'], 'Program Head') !== 0;
        });
        
        if (!empty($otherDesignations)) {
            $placeholders = implode(',', array_fill(0, count($otherDesignations), '?'));
            $facultySignatoryCheck = $pdo->prepare("
                SELECT DISTINCT designation_id 
                FROM sector_signatory_assignments 
                WHERE designation_id IN ($placeholders) AND clearance_type = 'Faculty' AND is_active = 1
            ");
            $designationIds = array_column($otherDesignations, 'designation_id');
            $facultySignatoryCheck->execute($designationIds);
            $validSignatoryIds = $facultySignatoryCheck->fetchAll(PDO::FETCH_COLUMN);

            // Add other valid designations
            foreach ($otherDesignations as $designation) {
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

        // 3. Get all department assignments for the user.
        $departmentIds = [];
        // a) Get primary department from staff table
        $primaryDeptStmt = $pdo->prepare("
            SELECT department_id FROM staff WHERE user_id = ? AND department_id IS NOT NULL AND is_active = 1
        ");
        $primaryDeptStmt->execute([$userId]);
        $primaryDeptId = $primaryDeptStmt->fetchColumn();
        if ($primaryDeptId) {
            $departmentIds[] = $primaryDeptId;
        }
        // b) Get all departments from user_department_assignments
        $multiDeptStmt = $pdo->prepare("
            SELECT department_id FROM user_department_assignments WHERE user_id = ? AND is_active = 1
        ");
        $multiDeptStmt->execute([$userId]);
        $multiDepartmentIds = $multiDeptStmt->fetchAll(PDO::FETCH_COLUMN);
        $departmentIds = array_merge($departmentIds, $multiDepartmentIds);
        
        // Store the unique list of department IDs in the global scope
        $GLOBALS['userDepartmentIds'] = array_unique(array_map('intval', $departmentIds));

    } catch (Throwable $e) {
        // In a real app, you'd log this and show a user-friendly error page.
        die('System error. Please try again later.');
    }
}
