<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user is School Administrator (typically manages departments)
$userRole = $auth->getRoleName();
if ($userRole !== 'School Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Only School Administrators can create departments.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$pdo = Database::getInstance()->getConnection();

$name = trim($input['name'] ?? '');
$code = strtoupper(trim($input['code'] ?? ''));
$type = $input['type'] ?? '';
$status = $input['status'] ?? 'active';
$isActive = ($status === 'active') ? 1 : 0;

// Validate required fields
if (empty($name) || empty($code) || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate department code format (uppercase letters and numbers only)
if (!preg_match('/^[A-Z0-9]+$/', $code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department code must contain only uppercase letters and numbers']);
    exit;
}

// Check if department code already exists across all sectors
$checkStmt = $pdo->prepare("
    SELECT department_id, department_name, sector_id, s.sector_name
    FROM departments d
    JOIN sectors s ON d.sector_id = s.sector_id
    WHERE department_code = ? AND is_active = 1
");
$checkStmt->execute([$code]);
$existing = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($existing)) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => "Department code '$code' already exists",
        'existing' => $existing
    ]);
    exit;
}

// Map type to sectors
// Auto-create College/SHS departments with Faculty sector
$sectorMap = [
    'college' => [1, 3], // College + Faculty (auto-created)
    'senior-high' => [2, 3], // SHS + Faculty (auto-created)
    'faculty' => [3], // Faculty only
    'college-faculty' => [1, 3], // College + Faculty (legacy support)
    'shs-faculty' => [2, 3], // SHS + Faculty (legacy support)
    'all-sectors' => [1, 2, 3] // All sectors (legacy support)
];

$sectors = $sectorMap[$type] ?? [];
if (empty($sectors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid department type']);
    exit;
}

// Map type to department_type enum
$deptTypeMap = [
    'college' => 'College',
    'senior-high' => 'Senior High School',
    'faculty' => 'Faculty',
    'college-faculty' => 'College', // Primary type
    'shs-faculty' => 'Senior High School', // Primary type
    'all-sectors' => 'College' // Primary type
];

$primaryDeptType = $deptTypeMap[$type] ?? 'College';

try {
    $pdo->beginTransaction();

    $createdIds = [];
    $insertStmt = $pdo->prepare("
        INSERT INTO departments (department_name, department_code, department_type, sector_id, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($sectors as $sectorId) {
        // For Faculty sector, use 'Faculty' as department_type
        $deptType = ($sectorId == 3) ? 'Faculty' : $primaryDeptType;

        $insertStmt->execute([$name, $code, $deptType, $sectorId, $isActive]);
        $createdIds[] = $pdo->lastInsertId();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Department(s) created successfully',
        'department_ids' => $createdIds,
        'sectors_created' => count($sectors)
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

