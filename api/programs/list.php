<?php
/**
 * API to list programs, optionally filtered by department.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../includes/config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();

    $department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;

    $sql = "
        SELECT 
            p.program_id,
            p.program_name,
            p.program_code,
            p.department_id
        FROM programs p
        WHERE p.is_active = 1
    ";

    $params = [];
    if ($department_id) {
        $sql .= " AND p.department_id = ?";
        $params[] = $department_id;
    }

    $sql .= " ORDER BY p.program_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For now, we'll return a static list of year levels.
    // This could be made dynamic in the future if needed.
    $year_levels = [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year'
    ];

    echo json_encode([
        'success' => true,
        'programs' => $programs,
        'year_levels' => $year_levels,
        'total' => count($programs)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

?>