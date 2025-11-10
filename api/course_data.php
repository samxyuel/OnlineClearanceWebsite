<?php
/**
 * API: Course Management Data
 *
 * Provides department and program data for the Course Management page.
 * Returns structured lists grouped by sector along with summary statistics.
 *
 * Optional Query Parameters (GET):
 *   - include_inactive (bool): When true, include inactive departments/programs. Defaults to false.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

try {
    $auth = new Auth();

    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    $allowedRoles = ['Admin', 'Program Head'];
    $userRole = $auth->getRoleName();

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $includeInactive = filter_var($_GET['include_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($includeInactive === null) {
        $includeInactive = false;
    }

    $pdo = Database::getInstance()->getConnection();

    $departments = fetchDepartments($pdo);
    $departmentIds = array_map(static fn($dept) => (int)$dept['department_id'], $departments);

    $programs = fetchProgramsByDepartment($pdo, $departmentIds);
    $programsByDepartment = groupProgramsByDepartment($programs);

    $studentCounts = fetchStudentCounts($pdo, $departmentIds);

    $responseData = buildResponseData(
        $departments,
        $programsByDepartment,
        $studentCounts,
        $includeInactive
    );

    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Fetch all departments with their sector information.
 *
 * @param PDO $pdo
 * @return array<int, array<string, mixed>>
 */
function fetchDepartments(PDO $pdo): array
{
    $sql = "
        SELECT
            d.department_id,
            d.department_name,
            d.department_code,
            d.department_type,
            d.is_active,
            COALESCE(s.sector_name, d.department_type) AS sector_name
        FROM departments d
        LEFT JOIN sectors s ON d.sector_id = s.sector_id
        ORDER BY
            COALESCE(s.sector_name, d.department_type) ASC,
            d.department_name ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch programs for the provided department IDs.
 *
 * @param PDO $pdo
 * @param array<int> $departmentIds
 * @return array<int, array<string, mixed>>
 */
function fetchProgramsByDepartment(PDO $pdo, array $departmentIds): array
{
    if (empty($departmentIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

    $sql = "
        SELECT
            program_id,
            program_name,
            program_code,
            department_id,
            is_active
        FROM programs
        WHERE department_id IN ($placeholders)
        ORDER BY program_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($departmentIds);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Group program records by their department ID.
 *
 * @param array<int, array<string, mixed>> $programs
 * @return array<int, array<int, array<string, mixed>>>
 */
function groupProgramsByDepartment(array $programs): array
{
    $grouped = [];

    foreach ($programs as $program) {
        $deptId = (int)$program['department_id'];
        if (!isset($grouped[$deptId])) {
            $grouped[$deptId] = [];
        }
        $grouped[$deptId][] = $program;
    }

    return $grouped;
}

/**
 * Fetch active student counts per department (active user accounts only).
 *
 * @param PDO $pdo
 * @param array<int> $departmentIds
 * @return array<int, int>
 */
function fetchStudentCounts(PDO $pdo, array $departmentIds): array
{
    if (empty($departmentIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

    $sql = "
        SELECT
            s.department_id,
            COUNT(*) AS student_count
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.department_id IN ($placeholders)
          AND u.account_status = 'active'
        GROUP BY s.department_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($departmentIds);

    $counts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[(int)$row['department_id']] = (int)$row['student_count'];
    }

    return $counts;
}

/**
 * Build the structured response payload.
 *
 * @param array<int, array<string, mixed>> $departments
 * @param array<int, array<int, array<string, mixed>>> $programsByDepartment
 * @param array<int, int> $studentCounts
 * @param bool $includeInactive
 * @return array<string, mixed>
 */
function buildResponseData(
    array $departments,
    array $programsByDepartment,
    array $studentCounts,
    bool $includeInactive
): array {
    $sectorKeys = [
        'college' => [
            'label' => 'College',
            'departments' => []
        ],
        'senior_high' => [
            'label' => 'Senior High School',
            'departments' => []
        ],
        'faculty' => [
            'label' => 'Faculty',
            'departments' => []
        ],
        'uncategorized' => [
            'label' => 'Uncategorized',
            'departments' => []
        ]
    ];

    $summary = [
        'total_departments' => 0,
        'active_departments' => 0,
        'inactive_departments' => 0,
        'total_programs' => 0,
        'active_programs' => 0,
        'inactive_programs' => 0,
        'total_students' => 0,
        'generated_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM)
    ];

    $sectorSummary = [
        'college' => ['total_departments' => 0, 'total_programs' => 0, 'total_students' => 0],
        'senior_high' => ['total_departments' => 0, 'total_programs' => 0, 'total_students' => 0],
        'faculty' => ['total_departments' => 0, 'total_programs' => 0, 'total_students' => 0],
        'uncategorized' => ['total_departments' => 0, 'total_programs' => 0, 'total_students' => 0]
    ];

    foreach ($departments as $department) {
        $deptId = (int)$department['department_id'];
        $isActive = (bool)$department['is_active'];

        if (!$isActive) {
            $summary['inactive_departments']++;
            if (!$includeInactive) {
                continue;
            }
        } else {
            $summary['active_departments']++;
        }

        $summary['total_departments']++;

        $sectorInfo = mapSectorKey($department['sector_name'] ?? $department['department_type']);
        $sectorKey = $sectorInfo['key'];
        $sectorLabel = $sectorInfo['label'];

        if (!isset($sectorKeys[$sectorKey])) {
            // Initialize unexpected sectors under 'uncategorized'
            $sectorKey = 'uncategorized';
            $sectorLabel = $sectorInfo['label'];
        }

        $departmentPrograms = $programsByDepartment[$deptId] ?? [];
        $programEntries = [];
        $activePrograms = 0;
        $inactivePrograms = 0;

        foreach ($departmentPrograms as $program) {
            $programActive = (bool)$program['is_active'];

            if (!$programActive) {
                $inactivePrograms++;
                if (!$includeInactive) {
                    continue;
                }
            } else {
                $activePrograms++;
            }

            $summary['total_programs']++;
            if ($programActive) {
                $summary['active_programs']++;
            } else {
                $summary['inactive_programs']++;
            }

            $programEntries[] = [
                'program_id' => (int)$program['program_id'],
                'program_name' => $program['program_name'],
                'program_code' => $program['program_code'],
                'is_active' => $programActive,
                'status' => $programActive ? 'Active' : 'Inactive'
            ];
        }

        $studentsForDepartment = $studentCounts[$deptId] ?? 0;
        $summary['total_students'] += $studentsForDepartment;

        $sectorSummary[$sectorKey]['total_departments']++;
        $sectorSummary[$sectorKey]['total_programs'] += $activePrograms + ($includeInactive ? $inactivePrograms : 0);
        $sectorSummary[$sectorKey]['total_students'] += $studentsForDepartment;

        $sectorKeys[$sectorKey]['departments'][] = [
            'department_id' => $deptId,
            'department_name' => $department['department_name'],
            'department_code' => $department['department_code'],
            'sector_label' => $sectorLabel,
            'is_active' => $isActive,
            'status' => $isActive ? 'Active' : 'Inactive',
            'total_programs' => $activePrograms + ($includeInactive ? $inactivePrograms : 0),
            'active_programs' => $activePrograms,
            'inactive_programs' => $inactivePrograms,
            'total_students' => $studentsForDepartment,
            'programs' => $programEntries
        ];
    }

    if (empty($sectorKeys['uncategorized']['departments'])) {
        unset($sectorKeys['uncategorized'], $sectorSummary['uncategorized']);
    }

    $sectors = [];
    $departmentsBySector = [];

    foreach ($sectorKeys as $key => $meta) {
        $sectors[$key] = [
            'key' => $key,
            'label' => $meta['label'],
            'total_departments' => $sectorSummary[$key]['total_departments'] ?? 0,
            'total_programs' => $sectorSummary[$key]['total_programs'] ?? 0,
            'total_students' => $sectorSummary[$key]['total_students'] ?? 0
        ];

        $departmentsBySector[$key] = $meta['departments'];
    }

    return [
        'summary' => $summary,
        'sectors' => $sectors,
        'departments' => $departmentsBySector
    ];
}

/**
 * Map a sector/department type string to an internal key and label.
 *
 * @param string|null $sectorName
 * @return array{key: string, label: string}
 */
function mapSectorKey(?string $sectorName): array
{
    $normalized = strtolower(trim((string)$sectorName));

    switch ($normalized) {
        case 'college':
            return ['key' => 'college', 'label' => 'College'];
        case 'senior high school':
        case 'senior high':
        case 'senior highschool':
        case 'shs':
            return ['key' => 'senior_high', 'label' => 'Senior High School'];
        case 'faculty':
            return ['key' => 'faculty', 'label' => 'Faculty'];
        default:
            return [
                'key' => 'uncategorized',
                'label' => $sectorName ? ucwords($sectorName) : 'Uncategorized'
            ];
    }
}

