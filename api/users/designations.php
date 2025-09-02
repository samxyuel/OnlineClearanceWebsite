<?php
// API: Designations (list + create)
// Methods:
// - GET  /api/users/designations.php?q=term&limit=20
// - POST /api/users/designations.php  { designation_name, description? }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

function json_response($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    json_response(500, [ 'success' => false, 'message' => 'Database connection failed' ]);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper: normalize designation per agreed rules
function normalize_designation($name) {
    // trim and collapse multiple spaces
    $name = trim($name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

// Helper: validate designation per agreed rules
function is_valid_designation($name) {
    if (mb_strlen($name) < 2 || mb_strlen($name) > 50) return false;
    // Allowed: letters, numbers, space, hyphen, slash, ampersand, apostrophe, period
    return (bool)preg_match("/^[A-Za-z0-9 \-\/&'.\.]+$/u", $name);
}

if ($method === 'GET') {
    // Read-only list for autocomplete
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    if ($limit <= 0 || $limit > 100) $limit = 20;

    try {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $stmt = $db->prepare("SELECT designation_id, designation_name FROM designations WHERE is_active = 1 AND designation_name LIKE ? ORDER BY designation_name ASC LIMIT ?");
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT designation_id, designation_name FROM designations WHERE is_active = 1 ORDER BY designation_name ASC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        json_response(200, [ 'success' => true, 'designations' => $rows ]);
    } catch (PDOException $e) {
        json_response(500, [ 'success' => false, 'message' => 'Database error' ]);
    }
}

if ($method === 'POST') {
    // Admin-only create (idempotent)
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        json_response(401, [ 'success' => false, 'message' => 'Unauthorized' ]);
    }
    // Prefer permission; fallback to Admin role
    $hasPermission = $auth->hasPermission('edit_users');
    $user = $auth->getCurrentUser();
    $isAdmin = isset($user['role_name']) && strtolower($user['role_name']) === 'admin';
    if (!$hasPermission && !$isAdmin) {
        json_response(403, [ 'success' => false, 'message' => 'Forbidden' ]);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $name = isset($data['designation_name']) ? $data['designation_name'] : '';
    $desc = isset($data['description']) ? $data['description'] : null;

    $name = normalize_designation($name);
    if (!is_valid_designation($name)) {
        json_response(400, [ 'success' => false, 'message' => 'Invalid designation name' ]);
    }

    try {
        // Check existing (case-insensitive)
        $stmt = $db->prepare("SELECT designation_id, designation_name FROM designations WHERE LOWER(designation_name) = LOWER(?) LIMIT 1");
        $stmt->execute([$name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            json_response(200, [ 'success' => true, 'designation' => $existing, 'existing' => true ]);
        }

        // Insert new designation
        $ins = $db->prepare("INSERT INTO designations (designation_name, description, is_active, created_at) VALUES (?, ?, 1, NOW())");
        $ins->execute([$name, $desc]);
        $id = $db->lastInsertId();

        json_response(201, [ 'success' => true, 'designation' => [ 'designation_id' => (int)$id, 'designation_name' => $name ] ]);
    } catch (PDOException $e) {
        json_response(500, [ 'success' => false, 'message' => 'Database error' ]);
    }
}

// Method not allowed
json_response(405, [ 'success' => false, 'message' => 'Method not allowed' ]);
?>


