<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!$auth->isLoggedIn()) {
    respond(401, ['success' => false, 'message' => 'Authentication required.']);
}

$pdo = Database::getInstance()->getConnection();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        if (!$auth->hasPermission('view_users')) {
            respond(403, ['success' => false, 'message' => 'Forbidden']);
        }

        $scope = isset($_GET['scope']) ? strtolower(trim($_GET['scope'])) : 'resigned';
        $departmentId = isset($_GET['department_id']) ? trim($_GET['department_id']) : '';
        $employmentStatus = isset($_GET['employment_status']) ? trim($_GET['employment_status']) : '';
        $accountStatus = isset($_GET['account_status']) ? trim(strtolower($_GET['account_status'])) : '';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $includeFilters = isset($_GET['include_filters']);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = ($scope === 'resigned')
            ? min(100, max(5, (int) ($_GET['limit'] ?? 10)))
            : 500;
        $offset = ($page - 1) * $limit;

        $facultyRoleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE LOWER(role_name) = :roleName LIMIT 1");
        $facultyRoleStmt->execute([':roleName' => 'faculty']);
        $facultyRoleId = $facultyRoleStmt->fetchColumn();

        if (!$facultyRoleId) {
            respond(500, ['success' => false, 'message' => 'Faculty role not configured.']);
        }

        $params = [];
        $wheres = [];

        $params[':facultyRoleId'] = (int) $facultyRoleId;

        if ($scope === 'resigned') {
            $wheres[] = 'u.account_status = :resignedStatus';
            $params[':resignedStatus'] = 'resigned';
        }

        if ($departmentId !== '') {
            $wheres[] = 'f.department_id = :departmentId';
            $params[':departmentId'] = $departmentId;
        }

        if ($employmentStatus !== '') {
            $wheres[] = 'f.employment_status = :employmentStatus';
            $params[':employmentStatus'] = $employmentStatus;
        }

        if ($accountStatus !== '') {
            $wheres[] = 'u.account_status = :accountStatus';
            $params[':accountStatus'] = $accountStatus;
        }

        if ($search !== '') {
            $wheres[] = '(u.first_name LIKE :search OR u.last_name LIKE :search OR u.middle_name LIKE :search OR f.employee_number LIKE :search)';
            $params[':search'] = "%{$search}%";
        }

        $whereSql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

        $sql = "SELECT 
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    u.email,
                    u.contact_number,
                    u.account_status AS account_status,
                    u.account_status AS status,
                    f.employee_number,
                    f.employment_status,
                    f.department_id,
                    COALESCE(d.department_name, 'Unassigned Department') AS department_name
                FROM faculty f
                INNER JOIN users u ON u.user_id = f.user_id
                INNER JOIN user_roles ur ON ur.user_id = u.user_id AND ur.role_id = :facultyRoleId
                LEFT JOIN departments d ON d.department_id = f.department_id
                {$whereSql}
                ORDER BY u.last_name, u.first_name";

        if ($scope === 'resigned') {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($scope === 'resigned') {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        $facultyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $faculty = array_map(function ($row) {
            $row['user_id'] = (int) $row['user_id'];
            $row['department_id'] = $row['department_id'] !== null ? (int) $row['department_id'] : null;
            $row['full_name'] = trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? ''));
            return $row;
        }, $facultyRows);

        $total = count($faculty);
        $totalPages = 1;
        if ($scope === 'resigned') {
            $countSql = "SELECT COUNT(*)
                FROM faculty f
                INNER JOIN users u ON u.user_id = f.user_id
                INNER JOIN user_roles ur ON ur.user_id = u.user_id AND ur.role_id = :facultyRoleId
                LEFT JOIN departments d ON d.department_id = f.department_id
                {$whereSql}";

            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();
            $totalPages = max(1, (int) ceil($total / $limit));

        }

        $responseData = [
            'faculty' => $faculty,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages
            ]
        ];

        // Fetch full set of currently resigned user IDs (account_status resigned)
        $resignedIdsStmt = $pdo->prepare("
            SELECT u.user_id
            FROM faculty f
            INNER JOIN users u ON u.user_id = f.user_id
            INNER JOIN user_roles ur ON ur.user_id = u.user_id AND ur.role_id = :facultyRoleId
            WHERE u.account_status = 'resigned'
        ");
        $resignedIdsStmt->execute([':facultyRoleId' => (int) $facultyRoleId]);
        $currentResignedIds = array_map('intval', $resignedIdsStmt->fetchAll(PDO::FETCH_COLUMN));
        $responseData['current_resigned_ids'] = $currentResignedIds;

        if ($includeFilters) {
            $filters = [];

            // Departments
            $deptStmt = $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
            $filters['departments'] = array_map(function ($dept) {
                return [
                    'value' => (string) $dept['department_id'],
                    'label' => $dept['department_name']
                ];
            }, $deptStmt->fetchAll(PDO::FETCH_ASSOC));

            // Employment statuses (distinct)
            $employmentStmt = $pdo->query("SELECT DISTINCT employment_status FROM faculty ORDER BY employment_status");
            $filters['employment_statuses'] = array_map(function ($row) {
                return $row['employment_status'];
            }, $employmentStmt->fetchAll(PDO::FETCH_ASSOC));

            // Account statuses (from users table)
            $filters['account_statuses'] = ['active', 'inactive'];

            $responseData['filters'] = $filters;
        }

        respond(200, ['success' => true, 'data' => $responseData]);
    }

    if ($method === 'POST') {
        if (!$auth->hasPermission('edit_users')) {
            respond(403, ['success' => false, 'message' => 'Forbidden']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            respond(400, ['success' => false, 'message' => 'Invalid JSON payload.']);
        }

        $resignIds = isset($input['resign_ids']) && is_array($input['resign_ids']) ? array_values($input['resign_ids']) : [];
        $restoreIds = isset($input['restore_ids']) && is_array($input['restore_ids']) ? array_values($input['restore_ids']) : [];

        $resignIds = array_values(array_filter(array_map('intval', $resignIds), fn($id) => $id > 0));
        $restoreIds = array_values(array_filter(array_map('intval', $restoreIds), fn($id) => $id > 0));

        if (empty($resignIds) && empty($restoreIds)) {
            respond(200, ['success' => true, 'data' => ['resigned_count' => 0, 'restored_count' => 0]]);
        }

        $pdo->beginTransaction();

        if (!empty($resignIds)) {
            $placeholders = implode(',', array_fill(0, count($resignIds), '?'));
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'resigned', updated_at = NOW() WHERE user_id IN ($placeholders)");
            $stmt->execute($resignIds);
        }

        if (!empty($restoreIds)) {
            $placeholders = implode(',', array_fill(0, count($restoreIds), '?'));
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'active', updated_at = NOW() WHERE user_id IN ($placeholders)");
            $stmt->execute($restoreIds);
        }

        $pdo->commit();

        respond(200, ['success' => true, 'data' => [
            'resigned_count' => count($resignIds),
            'restored_count' => count($restoreIds)
        ]]);
    }

    respond(405, ['success' => false, 'message' => 'Method not allowed']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, ['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
