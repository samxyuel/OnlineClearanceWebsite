<?php
/**
 * API: Program Head - College Student List
 *
 * Fetches a list of college students for a Program Head, scoped to their
 * assigned departments. Includes clearance status specific to the Program Head.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

function send_json_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        send_json_response(false, [], 'Authentication required.', 401);
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // 1. Verify user is a Program Head and get their assigned departments
    $deptStmt = $pdo->prepare("
        SELECT d.department_id, d.department_name
        FROM staff s
        JOIN departments d ON s.department_id = d.department_id
        WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1
    ");
    $deptStmt->execute([$userId]);
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($departments)) {
        send_json_response(false, [], 'You are not assigned to any departments as a Program Head.', 403);
    }
    $departmentIds = array_column($departments, 'department_id');
    
    // Create named placeholders for the IN clause to avoid mixing with other named params
    $deptPlaceholders = [];
    $deptParams = [];
    foreach ($departmentIds as $i => $id) {
        $deptPlaceholders[] = ":dept_id_$i";
        $deptParams[":dept_id_$i"] = $id;
    }
    $inClause = implode(',', $deptPlaceholders);

    // 2. Get the active clearance period
    $periodStmt = $pdo->query("
        SELECT academic_year_id, semester_id FROM clearance_periods WHERE status = 'Ongoing' LIMIT 1
    ");
    $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activePeriod) {
        send_json_response(true, ['students' => [], 'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0]], 'No active clearance period.');
    }

    // 3. Get the 'Program Head' designation ID
    $designationId = $pdo->query("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1")->fetchColumn();
    if (!$designationId) {
        send_json_response(false, [], '"Program Head" designation not found in system.', 500);
    }

    // 4. Fetch students and their clearance status for the Program Head
    $sql = "
        SELECT
            u.user_id,
            u.username as student_id,
            u.first_name,
            u.last_name,
            u.middle_name,
            u.account_status as account_status,
            p.program_code as program,
            s.year_level,
            s.section,
            d.department_name,
            cf.clearance_form_id,
            cs.action as clearance_status,
            cs.signatory_id,
            cs.remarks,
            cs.reason_id
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN programs p ON s.program_id = p.program_id
        JOIN departments d ON p.department_id = d.department_id
        JOIN clearance_forms cf ON u.user_id = cf.user_id
            AND cf.academic_year_id = :academic_year_id
            AND cf.semester_id = :semester_id
            AND cf.clearance_type = 'College'
        JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
            AND cs.designation_id = :designation_id
        WHERE s.department_id IN ($inClause)
        AND s.sector = 'College'
        AND cs.action IN ('Pending', 'Rejected')
        ORDER BY
            CASE WHEN cs.action = 'Pending' THEN 0 ELSE 1 END,
            u.last_name, u.first_name
    ";

    $allParams = array_merge($deptParams, [
        ':academic_year_id' => $activePeriod['academic_year_id'],
        ':semester_id' => $activePeriod['semester_id'],
        ':designation_id' => $designationId
    ]);
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($allParams as $key => &$value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Calculate stats
    $stats = [
        'total' => count($students),
        'active' => 0,
        'inactive' => 0,
        'graduated' => 0 // Assuming 'graduated' is a status we can filter by
    ];

    foreach ($students as $student) {
        if ($student['account_status'] === 'active') {
            $stats['active']++;
        } else {
            $stats['inactive']++;
        }
    }

    send_json_response(true, [
        'students' => $students,
        'stats' => $stats,
        'departments' => $departments
    ]);

} catch (Exception $e) {
    send_json_response(false, [], 'Server Error: ' . $e->getMessage(), 500);
}
?>