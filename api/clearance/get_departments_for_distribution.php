<?php
/**
 * API: Get Departments for Form Distribution
 *
 * Returns departments for a sector with user counts for the modal
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/form_distribution.php';

header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$connection = Database::getInstance()->getConnection();

try {
    $clearanceType = $_GET['clearance_type'] ?? null;
    $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : null;
    $semesterId = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : null;

    if (!$clearanceType) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'clearance_type is required']);
        exit;
    }

    $validTypes = ['College', 'Senior High School', 'Faculty'];
    if (!in_array($clearanceType, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid clearance type']);
        exit;
    }

    $departments = [];

    if ($clearanceType === 'Faculty') {
        // Get all departments with faculty
        $sql = "
            SELECT DISTINCT d.department_id, d.department_name
            FROM departments d
            INNER JOIN faculty f ON d.department_id = f.department_id
            WHERE d.is_active = 1
            ORDER BY d.department_name ASC
        ";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $deptList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get user counts for each department
        foreach ($deptList as $dept) {
            $userCount = countEligibleFaculty($connection, $dept['department_id']);
            if ($userCount > 0) {
                $departments[] = [
                    'department_id' => $dept['department_id'],
                    'department_name' => $dept['department_name'],
                    'user_count' => $userCount
                ];
            }
        }

        // Check for unassigned faculty
        $unassignedCount = countEligibleFaculty($connection, null);
        if ($unassignedCount > 0) {
            $departments[] = [
                'department_id' => null,
                'department_name' => 'Unassigned Faculty',
                'user_count' => $unassignedCount
            ];
        }
    } else {
        // For College and Senior High School
        $sql = "
            SELECT DISTINCT d.department_id, d.department_name
            FROM departments d
            INNER JOIN sectors s ON d.sector_id = s.sector_id
            WHERE s.sector_name = ?
              AND d.is_active = 1
            ORDER BY d.department_name ASC
        ";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$clearanceType]);
        $deptList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get user counts for each department
        foreach ($deptList as $dept) {
            $userCount = countEligibleStudents($connection, $clearanceType, $dept['department_id']);
            if ($userCount > 0) {
                $departments[] = [
                    'department_id' => $dept['department_id'],
                    'department_name' => $dept['department_name'],
                    'user_count' => $userCount
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'clearance_type' => $clearanceType,
        'departments' => $departments,
        'total_departments' => count($departments),
        'total_users' => array_sum(array_column($departments, 'user_count'))
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

