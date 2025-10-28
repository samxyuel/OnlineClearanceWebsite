<?php
/**
 * API: Get Filter Options
 *
 * A centralized utility endpoint to fetch various options for populating dropdown filters.
 *
 * Query Parameters:
 * - type: The type of options to fetch. Supported types:
 *   - 'enum': Fetches values from a database ENUM column.
 *     - Requires 'table' and 'column' parameters.
 *     - Optional 'exclude' parameter (comma-separated string) to filter out values.
 *   - 'school_terms': Fetches all unique school terms (academic year + semester).
 *   - 'departments': Fetches all active departments.
 *   - 'programs': Fetches all active programs.
 *   - 'employment_statuses': Fetches all employment statuses from the faculty table.
 *     - Optional 'department_id' to filter by department.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config/database.php';

if (!isset($_GET['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type parameter is required.']);
    exit;
}

$type = $_GET['type'];
$pdo = Database::getInstance()->getConnection();

try {
    switch ($type) {
        case 'enum':
            handleGetEnumValues($pdo);
            break;

        case 'school_terms':
            handleGetSchoolTerms($pdo);
            break;

        case 'employment_statuses':
            handleGetEnumValues($pdo, 'faculty', 'employment_status');
            break;

        // Add cases for 'departments', 'programs', etc. in the future as needed.

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid type specified.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

/**
 * Fetches and returns values from an ENUM column.
 */
function handleGetEnumValues($pdo, $tableName = null, $columnName = null) {
    $tableName = $tableName ?? $_GET['table'] ?? null;
    $columnName = $columnName ?? $_GET['column'] ?? null;

    if (!$tableName || !$columnName) {
        throw new Exception('Table and column parameters are required for type "enum".');
    }
    
    $exclude = isset($_GET['exclude']) ? explode(',', $_GET['exclude']) : [];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $columnName)) {
        throw new Exception('Invalid table or column name.');
    }

    $dbName = Database::getInstance()->getDbName();
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :dbName AND TABLE_NAME = :tableName AND COLUMN_NAME = :columnName");
    $stmt->execute([':dbName' => $dbName, ':tableName' => $tableName, ':columnName' => $columnName]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("Column '$columnName' not found in table '$tableName'.");
    }

    preg_match_all("/'([^']+)'/", $row['COLUMN_TYPE'], $matches);
    $enumValues = $matches[1] ?? [];

    if (!empty($exclude)) {
        $enumValues = array_filter($enumValues, function($value) use ($exclude) {
            return !in_array($value, $exclude);
        });
    }

    echo json_encode(['success' => true, 'options' => array_values($enumValues)]);
}

/**
 * Fetches and returns unique school terms.
 */
function handleGetSchoolTerms($pdo) {
    $sql = "SELECT DISTINCT ay.academic_year_id, ay.year as academic_year, s.semester_id, s.semester_name 
            FROM clearance_periods cp
            JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
            JOIN semesters s ON cp.semester_id = s.semester_id
            ORDER BY ay.year DESC, s.semester_id ASC";
    
    $stmt = $pdo->query($sql);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $options = array_map(function($term) {
        return [
            'value' => $term['academic_year'] . '|' . $term['semester_id'],
            'text' => $term['academic_year'] . ' - ' . $term['semester_name']
        ];
    }, $terms);

    echo json_encode(['success' => true, 'options' => $options]);
}
?>
