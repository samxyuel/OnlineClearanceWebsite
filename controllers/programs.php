<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $action = $_GET['action'] ?? null;
    $departmentType = $_GET['department_type'] ?? null;

    // This block handles the original request from the SHS modal
    if ($departmentType) {
        // Fetch programs for the given department type
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
                'value'   => $program['program_code']
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

    } elseif ($action === 'get_departments') {
        // This part is for the more dynamic approach we tried before, kept for future use.
        $sector = $_GET['sector'] ?? null;
        if (!$sector) { /* ... */ }
        // ... (rest of the get_departments logic)

    } elseif ($action === 'get_programs') {
        // This part is for the more dynamic approach we tried before, kept for future use.
        $departmentId = $_GET['department_id'] ?? null;
        if (!$departmentId) { /* ... */ }
        // ... (rest of the get_programs logic)

    } else {
        if (!$departmentType) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'department_type or action parameter is required.']);
            exit;
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>