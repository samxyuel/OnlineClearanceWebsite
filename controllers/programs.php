<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $action = $_GET['action'] ?? null;
    $departmentType = $_GET['department_type'] ?? null;
    $departmentId = $_GET['department_id'] ?? null;

    // This block handles requests for programs based on a specific department ID.
    if ($departmentId) {
        $sql = "SELECT p.program_name, p.program_code
                FROM programs p
                WHERE p.department_id = ? AND p.is_active = 1
                ORDER BY p.program_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$departmentId]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // We also need to know the department type to suggest year levels
        $deptTypeStmt = $pdo->prepare("SELECT department_type FROM departments WHERE department_id = ?");
        $deptTypeStmt->execute([$departmentId]);
        $departmentType = $deptTypeStmt->fetchColumn();

        $formattedPrograms = [];
        foreach ($programs as $program) {
            $formattedPrograms[] = [
                'display' => "{$program['program_name']} ({$program['program_code']})",
                'value'   => $program['program_code'],
                'program_name' => $program['program_name']
            ];
        }

        $yearLevels = [];
        if ($departmentType === 'Senior High School') {
            $yearLevels = ['Grade 11', 'Grade 12'];
        } else if ($departmentType === 'College') {
            $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        }

        echo json_encode([
            'success' => true,
            'programs' => $formattedPrograms,
            'year_levels' => $yearLevels
        ]);
    // This block handles the original request from the SHS modal using department_type
    } elseif ($departmentType) {
        $sql = "SELECT p.program_name, p.program_code
                FROM programs p
                JOIN departments d ON p.department_id = d.department_id
                WHERE d.department_type = ? AND p.is_active = 1
                ORDER BY p.program_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$departmentType]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedPrograms = [];
        foreach ($programs as $program) {
            $formattedPrograms[] = [
                'display' => "{$program['program_name']} ({$program['program_code']})",
                'value'   => $program['program_code'],
                'program_name' => $program['program_name']
            ];
        }

        $yearLevels = [];
        if ($departmentType === 'Senior High School') {
            $yearLevels = ['Grade 11', 'Grade 12'];
        }

        echo json_encode([
            'success' => true,
            'programs' => $formattedPrograms,
            'year_levels' => $yearLevels
        ]);

    } else {
        // Fallback or error for requests without a department_id
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A department_id or department_type is required to fetch programs.']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>