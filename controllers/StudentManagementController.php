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
        if (!empty($userDesignations)) {
            // 3. Check which of these designations are assigned to sign for the specific sector
            $placeholders = implode(',', array_fill(0, count($userDesignations), '?'));
            $signatoryCheck = $pdo->prepare("
                SELECT DISTINCT designation_id 
                FROM sector_signatory_assignments 
                WHERE designation_id IN ($placeholders) AND clearance_type = ? AND is_active = 1
            ");
            
            $params = array_column($userDesignations, 'designation_id');
            $params[] = $sector;
            
            $signatoryCheck->execute($params);
            $validSignatoryIds = $signatoryCheck->fetchAll(PDO::FETCH_COLUMN);

            // Filter the user's designations to only those valid for this sector
            foreach ($userDesignations as $designation) {
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
        $isProgramHead = false;
        foreach ($userSignatoryDesignations as $designation) {
            if ($designation['designation_name'] === 'Program Head') {
                $isProgramHead = true;
                break;
            }
        }

        if ($isProgramHead) {
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