<?php
/**
 * API: Program Head - Fetch Senior High School Students
 *
 * This API fetches a list of Senior High School (SHS) students for a logged-in Program Head.
 * It determines which students to show based on the Program Head's department assignments within the SHS sector.
 *
 * Responsibilities:
 * - Authenticate the user and verify their role as 'Program Head' or 'Admin'.
 * - Find the departments the Program Head is assigned to within the 'Senior High School' sector.
 * - Fetch all SHS students belonging to those departments with pending/rejected clearances for the Program Head.
 * - Return a structured JSON response containing student data and overall statistics.
 *
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
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // 1. Verify Role (Program Head or Admin)
    $roleStmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ?");
    $roleStmt->execute([$userId]);
    $role = strtolower($roleStmt->fetchColumn() ?: '');

    if ($role !== 'program head' && $role !== 'admin') {
        send_json_response(false, [], 'Access Denied: You do not have permission to view this data.', 403);
    }

    // 2. Verify the user is a Program Head for the 'Senior High School' sector.
    // If so, get all department IDs belonging to that sector.
    $shsAuthStmt = $pdo->prepare("
        SELECT COUNT(s.user_id)
        FROM staff s
        JOIN departments d ON s.department_id = d.department_id
        JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE s.user_id = ? 
          AND s.staff_category = 'Program Head'
          AND s.is_active = 1 
          AND sec.sector_name = 'Senior High School'
    ");
    $shsAuthStmt->execute([$userId]);
    $isShsProgramHead = (int)$shsAuthStmt->fetchColumn() > 0;

    if (!$isShsProgramHead && $role !== 'admin') {
        send_json_response(true, ['students' => [], 'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0]], 'You are not assigned as a Program Head for the Senior High School sector.');
    }

    // Since one person covers all SHS, we fetch all SHS department IDs.
    $shsDeptsStmt = $pdo->query("
        SELECT d.department_id 
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE s.sector_name = 'Senior High School'
    ");
    $departmentIds = $shsDeptsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($departmentIds)) {
        send_json_response(true, ['students' => [], 'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0]], 'No Senior High School departments found in the system.');
    }

    // 3. Get the active clearance period for the SHS sector
    $periodStmt = $pdo->query("SELECT academic_year_id, semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Senior High School' LIMIT 1");
    $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activePeriod) {
        send_json_response(true, ['students' => [], 'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0]], 'No active clearance period for Senior High School.');
    }

    // 4. Get the 'Program Head' designation ID
    $designationId = $pdo->query("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1")->fetchColumn();
    if (!$designationId) {
        send_json_response(false, [], '"Program Head" designation not found in system.', 500);
    }

    // Create a placeholder string for the IN clause (e.g., ?,?,?)
    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

    // 5. Fetch Students from those departments with pending/rejected clearances for the Program Head
    $sql = "
        SELECT
            u.user_id, u.username as student_id, u.first_name, u.last_name, u.middle_name, u.account_status,
            p.program_name AS program,
            s.year_level,
            s.section,
            cs.action AS clearance_status,
            cs.remarks,
            cs.reason_id,
            cf.clearance_form_id, cs.signatory_id as clearance_signatory_id
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN programs p ON s.program_id = p.program_id
        JOIN clearance_forms cf ON u.user_id = cf.user_id
            AND cf.academic_year_id = ? AND cf.semester_id = ? AND cf.clearance_type = 'Senior High School'
        JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id = ?
        WHERE p.department_id IN ($placeholders)
        AND s.sector = 'Senior High School'
        AND cs.action IN ('Pending', 'Rejected')
        ORDER BY u.last_name, u.first_name
    ";

    $params = array_merge(
        [$activePeriod['academic_year_id'], $activePeriod['semester_id'], $designationId],
        $departmentIds
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Calculate Statistics
    $stats = [
        'total' => count($students),
        'active' => 0,
        'inactive' => 0,
        'graduated' => 0,
    ];
    foreach ($students as $student) {
        if ($student['account_status'] === 'active') $stats['active']++;
        if ($student['account_status'] === 'inactive') $stats['inactive']++;
        if ($student['account_status'] === 'graduated') $stats['graduated']++;
    }

    // 7. Return JSON Response
    send_json_response(true, ['students' => $students, 'stats' => $stats]);

} catch (Exception $e) {
    error_log("SHS Students API Error: " . $e->getMessage());
    send_json_response(false, [], 'An internal server error occurred.', 500);
}
?>
