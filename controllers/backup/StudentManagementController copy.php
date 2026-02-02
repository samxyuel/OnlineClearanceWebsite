<?php
/**
 * Student Management Controller
 * Handles authorization and data fetching for student management pages.
 */

// Include necessary files
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

/**
 * Handle student management page request
 * @param string $sector The sector to filter by ('College' or 'Senior High School')
 */
function handleStudentManagementPageRequest($sector) {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../../pages/auth/login.php');
        exit;
    }

    $userId = (int)$auth->getUserId();

    try {
        $pdo = Database::getInstance()->getConnection();
        
        // 1. Check if there is an active clearance period for the specific sector
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_periods WHERE status = 'Ongoing' AND sector = ?");
        $stmt->execute([$sector]);
        $hasActivePeriod = (int)$stmt->fetchColumn() > 0;

        // 2. Get all of the staff member's active designations
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

        $userSignatoryDesignations = [];
        $isProgramHeadDesignation = null;
        
        // First, check if user has Program Head designation
        foreach ($userDesignations as $designation) {
            if (strcasecmp($designation['designation_name'], 'Program Head') === 0) {
                $isProgramHeadDesignation = $designation;
                break;
            }
        }
        
        // 3. Handle Program Head separately - they use sector_clearance_settings, not sector_signatory_assignments
        if ($isProgramHeadDesignation) {
            $settingStmt = $pdo->prepare("
                SELECT include_program_head 
                FROM sector_clearance_settings 
                WHERE clearance_type = ? AND include_program_head = 1
            ");
            $settingStmt->execute([$sector]);
            if ($settingStmt->fetchColumn()) {
                // Program Head is enabled for this sector
                $userSignatoryDesignations[] = $isProgramHeadDesignation;
            }
        }
        
        // 4. Check sector_signatory_assignments for other designations (not Program Head)
        $otherDesignations = array_filter($userDesignations, function($d) {
            return strcasecmp($d['designation_name'], 'Program Head') !== 0;
        });
        
        if (!empty($otherDesignations)) {
            $placeholders = implode(',', array_fill(0, count($otherDesignations), '?'));
            $signatoryCheck = $pdo->prepare("
                SELECT DISTINCT designation_id 
                FROM sector_signatory_assignments 
                WHERE designation_id IN ($placeholders) AND clearance_type = ? AND is_active = 1
            ");
            
            $params = array_column($otherDesignations, 'designation_id');
            $params[] = $sector;
            
            $signatoryCheck->execute($params);
            $validSignatoryIds = $signatoryCheck->fetchAll(PDO::FETCH_COLUMN);

            // Add other valid designations
            foreach ($otherDesignations as $designation) {
                if (in_array($designation['designation_id'], $validSignatoryIds)) {
                    $userSignatoryDesignations[] = $designation;
                }
            }
        }

        $hasStudentSignatoryAccess = !empty($userSignatoryDesignations);
        $canPerformSignatoryActions = $hasActivePeriod && $hasStudentSignatoryAccess;

        // Store permission flags for use in the page
        $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
        $GLOBALS['hasStudentSignatoryAccess'] = $hasStudentSignatoryAccess;
        $GLOBALS['canPerformSignatoryActions'] = $canPerformSignatoryActions;
        $GLOBALS['userSignatoryDesignations'] = $userSignatoryDesignations;

        // 4. Get all department assignments for the user, checking both the primary staff record and multi-assignments.
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

        // b) Get all departments from user_department_assignments for Program Heads
        // Use $isProgramHeadDesignation already determined above
        if ($isProgramHeadDesignation) {
            $multiDeptStmt = $pdo->prepare("
                SELECT uda.department_id
                FROM user_department_assignments uda
                JOIN departments d ON uda.department_id = d.department_id
                WHERE uda.user_id = ? AND uda.is_active = 1 AND d.sector_id = (SELECT sector_id FROM sectors WHERE sector_name = ?)
            ");
            $multiDeptStmt->execute([$userId, $sector]);
            $multiDepartmentIds = $multiDeptStmt->fetchAll(PDO::FETCH_COLUMN);
            $departmentIds = array_merge($departmentIds, $multiDepartmentIds);
        }
        
        // Store the unique list of department IDs in the global scope
        $GLOBALS['userDepartmentIds'] = array_unique($departmentIds);

    } catch (Throwable $e) {
        http_response_code(500);
        // In a real app, you would log the error: error_log($e->getMessage());
        die('A server error occurred. Please try again later.');
    }
}