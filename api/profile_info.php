<?php
/**
 * Profile Info API
 * Returns profile information for the currently logged-in user.
 *
 * Response format:
 * {
 *   "success": true,
 *   "data": {
 *     "user": {...},
 *     "role": {...},
 *     "profile": {...},
 *     "student": {...} // when applicable
 *   }
 * }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'success' => false,
    'data' => null,
    'error' => null,
];

try {
    // Ensure user is authenticated
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        throw new RuntimeException('User is not authenticated.');
    }

    $userId = (int)$_SESSION['user_id'];

    require_once __DIR__ . '/../includes/config/database.php';

    $pdo = Database::getInstance()->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!function_exists('profileColumnExists')) {
        /**
         * Check if a column exists on a table within the current database.
         */
        function profileColumnExists(PDO $pdo, string $table, string $column): bool
        {
            static $cache = [];
            $key = strtolower($table . '.' . $column);
            if (array_key_exists($key, $cache)) {
                return $cache[$key];
            }

            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ? 
                  AND COLUMN_NAME = ?
                LIMIT 1
            ");
            $stmt->execute([$table, $column]);
            $cache[$key] = $stmt->fetchColumn() > 0;

            return $cache[$key];
        }
    }

    // Fetch base user information
    $userStmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number,
            u.account_status,
            u.last_login,
            u.created_at,
            u.updated_at
        FROM users u
        WHERE u.user_id = ?
        LIMIT 1
    ");
    $userStmt->execute([$userId]);
    $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userInfo) {
        http_response_code(404);
        throw new RuntimeException('User not found.');
    }

    // Determine primary role
    $roleStmt = $pdo->prepare("
        SELECT 
            r.role_id,
            r.role_name,
            COALESCE(ur.is_primary, 0) AS is_primary
        FROM user_roles ur
        INNER JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ?
        ORDER BY ur.is_primary DESC, ur.assigned_at ASC
        LIMIT 1
    ");
    $roleStmt->execute([$userId]);
    $roleInfo = $roleStmt->fetch(PDO::FETCH_ASSOC);

    $roleKey = null;
    $roleLabel = 'User';
    if ($roleInfo) {
        $roleLabel = trim((string)$roleInfo['role_name']);
        $roleKey = strtolower(str_replace(' ', '_', $roleLabel));
    } elseif (!empty($_SESSION['role_name'])) {
        $roleLabel = trim((string)$_SESSION['role_name']);
        $roleKey = strtolower(str_replace(' ', '_', $roleLabel));
    }

    $sessionRoleKey = null;
    if (!empty($_SESSION['role_name'])) {
        $sessionRoleKey = strtolower(str_replace(' ', '_', trim((string)$_SESSION['role_name'])));
    } elseif (!empty($_SESSION['role_key'])) {
        $sessionRoleKey = strtolower(trim((string)$_SESSION['role_key']));
    } elseif (!empty($_SESSION['role'])) {
        $sessionRoleKey = strtolower(str_replace(' ', '_', trim((string)$_SESSION['role'])));
    }

    if ($roleKey === null && $sessionRoleKey !== null) {
        $roleKey = $sessionRoleKey;
        if ($roleLabel === 'User') {
            $roleLabel = ucwords(str_replace('_', ' ', $sessionRoleKey));
        }
    }

    if ($roleKey === null) {
        // Default to staff (regular) if still unknown
        $roleKey = 'staff';
        $roleLabel = $roleLabel === 'User' ? 'Staff' : $roleLabel;
    }

    // Normalize account status
    $accountStatus = $userInfo['account_status'] ?? 'active';
    $accountStatusDisplay = ucwords(str_replace('_', ' ', (string)$accountStatus));

    // Prepare common profile payload
    $fullName = trim(
        implode(' ', array_filter([
            $userInfo['first_name'] ?? '',
            $userInfo['middle_name'] ?? '',
            $userInfo['last_name'] ?? '',
        ]))
    );

    $profile = [
        'full_name' => $fullName !== '' ? $fullName : 'Unnamed User',
        'email' => $userInfo['email'] ?? null,
        'contact_number' => $userInfo['contact_number'] ?? null,
        'account_status' => [
            'raw' => $accountStatus,
            'label' => $accountStatusDisplay,
        ],
        'last_login' => $userInfo['last_login'] ?? null,
        'student_number' => null,
        'year_level' => null,
        'section' => null,
        'sector' => null,
        'department' => null,
        'program' => null,
        'employee_number' => null,
        'employment_status' => null,
        'employment_date' => null,
        'designation' => null,
    ];

    $roleSpecific = [];

    // Fetch student-specific data when applicable
    if ($roleKey === 'student') {
        $studentNumberExpr = profileColumnExists($pdo, 'students', 'student_number')
            ? 's.student_number'
            : 's.student_id';

        $yearLevelExpr = profileColumnExists($pdo, 'students', 'year_level')
            ? 's.year_level'
            : 'NULL';

        if (profileColumnExists($pdo, 'students', 'section')) {
            $sectionExpr = 's.section';
        } elseif (profileColumnExists($pdo, 'students', 'section_number')) {
            $sectionExpr = 's.section_number';
        } else {
            $sectionExpr = 'NULL';
        }

        $sectorCoalesceParts = ['sec.sector_name'];
        if (profileColumnExists($pdo, 'students', 'sector')) {
            $sectorCoalesceParts[] = 's.sector';
        }
        $sectorExpr = 'COALESCE(' . implode(', ', $sectorCoalesceParts) . ')';

        $studentQuery = "
            SELECT 
                {$studentNumberExpr} AS student_number,
                {$yearLevelExpr} AS year_level,
                {$sectionExpr} AS section,
                {$sectorExpr} AS sector_name,
                d.department_name,
                p.program_name,
                s.program_id,
                s.department_id" .
                (profileColumnExists($pdo, 'students', 'sector') ? ', s.sector' : '') . "
            FROM students s
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON s.department_id = d.department_id
            LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
            WHERE s.user_id = ?
            LIMIT 1
        ";

        $studentStmt = $pdo->prepare($studentQuery);
        $studentStmt->execute([$userId]);
        $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $roleSpecific['student'] = $studentInfo;

        // Blend academic information into profile structure
        $profile['student_number'] = $studentInfo['student_number'] ?? null;
        $profile['year_level'] = $studentInfo['year_level'] ?? null;
        $profile['section'] = $studentInfo['section'] ?? null;
        $profile['sector'] = $studentInfo['sector_name'] ?? $studentInfo['sector'] ?? null;
        $profile['department'] = $studentInfo['department_name'] ?? null;
        $profile['program'] = $studentInfo['program_name'] ?? null;
    } elseif ($roleKey === 'faculty') {
        $employmentDateExpr = profileColumnExists($pdo, 'faculty', 'employment_date')
            ? 'f.employment_date'
            : 'NULL';

        $facultyQuery = "
            SELECT 
                f.employee_number,
                f.employment_status,
                {$employmentDateExpr} AS employment_date,
                d.department_name,
                COALESCE(sec.sector_name, f.sector) AS sector_name
            FROM faculty f
            LEFT JOIN departments d ON f.department_id = d.department_id
            LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
            WHERE f.user_id = ?
            LIMIT 1
        ";

        $facultyStmt = $pdo->prepare($facultyQuery);
        $facultyStmt->execute([$userId]);
        $facultyInfo = $facultyStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $roleSpecific['faculty'] = $facultyInfo;

        $profile['employee_number'] = $facultyInfo['employee_number'] ?? null;
        $profile['employment_status'] = $facultyInfo['employment_status'] ?? null;
        $profile['employment_date'] = $facultyInfo['employment_date'] ?? null;
        $profile['sector'] = $facultyInfo['sector_name'] ?? $profile['sector'] ?? null;
        $profile['department'] = $facultyInfo['department_name'] ?? $profile['department'] ?? null;
    } elseif ($roleKey === 'program_head') {
        $designationJoin = profileColumnExists($pdo, 'designations', 'designation_name')
            ? 'LEFT JOIN designations des ON s.designation_id = des.designation_id'
            : '';

        $designationSelect = profileColumnExists($pdo, 'designations', 'designation_name')
            ? 'des.designation_name'
            : (profileColumnExists($pdo, 'staff', 'designation') ? 's.designation' : 'NULL');

        $programHeadQuery = "
            SELECT 
                s.employee_number,
                {$designationSelect} AS designation_name,
                s.staff_category,
                d.department_name,
                COALESCE(sec.sector_name, 'Faculty') AS sector_name
            FROM staff s
            LEFT JOIN departments d ON s.department_id = d.department_id
            LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
            {$designationJoin}
            WHERE s.user_id = ?
            LIMIT 1
        ";

        $programHeadStmt = $pdo->prepare($programHeadQuery);
        $programHeadStmt->execute([$userId]);
        $programHeadInfo = $programHeadStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $roleSpecific['program_head'] = $programHeadInfo;

        $profile['employee_number'] = $programHeadInfo['employee_number'] ?? $profile['employee_number'];
        $profile['designation'] = $programHeadInfo['designation_name'] ?? $profile['designation'];
        $profile['employment_status'] = $programHeadInfo['staff_category'] ?? $profile['employment_status'];
        $profile['sector'] = $programHeadInfo['sector_name'] ?? $profile['sector'];
        $profile['department'] = $programHeadInfo['department_name'] ?? $profile['department'];
    } elseif (in_array($roleKey, ['staff', 'regular_staff', 'school_admin', 'school_administrator', 'staff_member'], true)) {
        $employeeNumberExpr = profileColumnExists($pdo, 'staff', 'employee_number')
            ? 's.employee_number'
            : 'NULL';

        $designationJoin = profileColumnExists($pdo, 'designations', 'designation_name')
            ? 'LEFT JOIN designations des ON s.designation_id = des.designation_id'
            : '';

        $designationSelect = profileColumnExists($pdo, 'designations', 'designation_name')
            ? 'des.designation_name'
            : (profileColumnExists($pdo, 'staff', 'designation') ? 's.designation' : 'NULL');

        $employmentDateExpr = profileColumnExists($pdo, 'staff', 'employment_date')
            ? 's.employment_date'
            : 'NULL';

        $sectorParts = ['sec.sector_name'];
        if (profileColumnExists($pdo, 'staff', 'sector')) {
            $sectorParts[] = 's.sector';
        }
        $sectorExpr = count($sectorParts) > 1
            ? 'COALESCE(' . implode(', ', $sectorParts) . ')'
            : $sectorParts[0];

        $staffQuery = "
            SELECT 
                {$employeeNumberExpr} AS employee_number,
                {$designationSelect} AS designation_name,
                {$employmentDateExpr} AS employment_date,
                s.staff_category,
                d.department_name,
                {$sectorExpr} AS sector_name
            FROM staff s
            LEFT JOIN departments d ON s.department_id = d.department_id
            LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
            {$designationJoin}
            WHERE s.user_id = ?
            LIMIT 1
        ";

        $staffStmt = $pdo->prepare($staffQuery);
        $staffStmt->execute([$userId]);
        $staffInfo = $staffStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $roleSpecific['staff'] = $staffInfo;

        $profile['employee_number'] = $staffInfo['employee_number'] ?? $profile['employee_number'];
        $profile['designation'] = $staffInfo['designation_name'] ?? $staffInfo['staff_category'] ?? $profile['designation'];
        $profile['employment_date'] = $staffInfo['employment_date'] ?? $profile['employment_date'];
        $profile['employment_status'] = $staffInfo['staff_category'] ?? $profile['employment_status'];
        $profile['sector'] = $staffInfo['sector_name'] ?? $profile['sector'];
        $profile['department'] = $staffInfo['department_name'] ?? $profile['department'];
    }

    $response['success'] = true;
    $response['data'] = [
        'user' => $userInfo,
        'role' => [
            'id' => $roleInfo['role_id'] ?? null,
            'label' => $roleLabel,
            'key' => $roleKey,
        ],
        'profile' => $profile,
        'role_specific' => $roleSpecific,
    ];
} catch (Throwable $e) {
    if (empty($response['error'])) {
        $response['error'] = $e->getMessage();
    }
    if ($e instanceof RuntimeException) {
        if (http_response_code() === 200) {
            http_response_code(400);
        }
    } else {
        http_response_code(500);
    }
    error_log('[profile_info.php] ' . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

