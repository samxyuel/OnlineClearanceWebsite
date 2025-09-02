<?php
// -----------------------------------------------------------------------------
// Scope Settings Endpoint: manage per-scope toggles (e.g., include Program Head)
// Methods:
//   GET  /?clearance_type=student|faculty (optional) → returns settings
//   PUT  JSON { clearance_type: 'student'|'faculty', include_program_head: true|false, required_first_enabled: true|false, required_first_designation_id: int|null, required_last_enabled: true|false, required_last_designation_id: int|null }
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();

    // Ensure table exists (idempotent)
    $pdo->exec("CREATE TABLE IF NOT EXISTS scope_settings (
        clearance_type VARCHAR(16) PRIMARY KEY,
        include_program_head TINYINT(1) NOT NULL DEFAULT 0,
        required_first_enabled TINYINT(1) NOT NULL DEFAULT 0,
        required_first_designation_id INT NULL,
        required_last_enabled TINYINT(1) NOT NULL DEFAULT 0,
        required_last_designation_id INT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        $type = isset($_GET['clearance_type']) ? strtolower(trim($_GET['clearance_type'])) : '';
        if ($type && $type !== 'student' && $type !== 'faculty') {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Invalid clearance_type']);
            exit;
        }

        if ($type) {
            $stmt = $pdo->prepare("SELECT clearance_type, include_program_head, required_first_enabled, required_first_designation_id, required_last_enabled, required_last_designation_id FROM scope_settings WHERE clearance_type = ? LIMIT 1");
            $stmt->execute([$type]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'clearance_type'=>$type, 
                'include_program_head'=>0,
                'required_first_enabled'=>0,
                'required_first_designation_id'=>null,
                'required_last_enabled'=>0,
                'required_last_designation_id'=>null
            ];
            echo json_encode(['success'=>true,'settings'=>$row]);
            exit;
        } else {
            $stmt = $pdo->query("SELECT clearance_type, include_program_head, required_first_enabled, required_first_designation_id, required_last_enabled, required_last_designation_id FROM scope_settings");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Ensure both keys present with defaults
            $map = [ 'student'=>0, 'faculty'=>0 ];
            $first_map = [ 'student'=>0, 'faculty'=>0 ];
            $last_map = [ 'student'=>0, 'faculty'=>0 ];
            $first_desig_map = [ 'student'=>null, 'faculty'=>null ];
            $last_desig_map = [ 'student'=>null, 'faculty'=>null ];
            foreach ($rows as $r) { 
                $map[$r['clearance_type']] = (int)$r['include_program_head'];
                $first_map[$r['clearance_type']] = (int)$r['required_first_enabled'];
                $last_map[$r['clearance_type']] = (int)$r['required_last_enabled'];
                $first_desig_map[$r['clearance_type']] = $r['required_first_designation_id'];
                $last_desig_map[$r['clearance_type']] = $r['required_last_designation_id'];
            }
            echo json_encode(['success'=>true,'settings'=>[
                [
                    'clearance_type'=>'student', 
                    'include_program_head'=>$map['student'],
                    'required_first_enabled'=>$first_map['student'],
                    'required_first_designation_id'=>$first_desig_map['student'],
                    'required_last_enabled'=>$last_map['student'],
                    'required_last_designation_id'=>$last_desig_map['student']
                ],
                [
                    'clearance_type'=>'faculty', 
                    'include_program_head'=>$map['faculty'],
                    'required_first_enabled'=>$first_map['faculty'],
                    'required_first_designation_id'=>$first_desig_map['faculty'],
                    'required_last_enabled'=>$last_map['faculty'],
                    'required_last_designation_id'=>$last_desig_map['faculty']
                ]
            ]]);
            exit;
        }
    }

    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit; }
        $type = isset($input['clearance_type']) ? strtolower(trim($input['clearance_type'])) : '';
        $include = isset($input['include_program_head']) ? (bool)$input['include_program_head'] : null;
        $first_enabled = isset($input['required_first_enabled']) ? (bool)$input['required_first_enabled'] : false;
        $first_designation = isset($input['required_first_designation_id']) ? (int)$input['required_first_designation_id'] : null;
        $last_enabled = isset($input['required_last_enabled']) ? (bool)$input['required_last_enabled'] : false;
        $last_designation = isset($input['required_last_designation_id']) ? (int)$input['required_last_designation_id'] : null;
        
        if ($type !== 'student' && $type !== 'faculty') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'clearance_type must be student or faculty']); exit; }
        if ($include === null) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'include_program_head is required']); exit; }
        
        // Validate that first and last designations are different if both enabled
        if ($first_enabled && $last_enabled && $first_designation && $last_designation && $first_designation === $last_designation) {
            http_response_code(400); 
            echo json_encode(['success'=>false,'message'=>'Required First and Required Last cannot be the same designation']); 
            exit;
        }

        // Upsert
        $stmt = $pdo->prepare("INSERT INTO scope_settings (clearance_type, include_program_head, required_first_enabled, required_first_designation_id, required_last_enabled, required_last_designation_id) VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE 
                include_program_head=VALUES(include_program_head), 
                required_first_enabled=VALUES(required_first_enabled),
                required_first_designation_id=VALUES(required_first_designation_id),
                required_last_enabled=VALUES(required_last_enabled),
                required_last_designation_id=VALUES(required_last_designation_id),
                updated_at=CURRENT_TIMESTAMP");
        $stmt->execute([$type, $include ? 1 : 0, $first_enabled ? 1 : 0, $first_designation, $last_enabled ? 1 : 0, $last_designation]);

        require_once '../../includes/functions/audit_functions.php';
        logActivity($auth->getUserId(), 'Scope Settings Update', [
            'clearance_type'=>$type,
            'include_program_head'=>$include?1:0,
            'required_first_enabled'=>$first_enabled?1:0,
            'required_first_designation_id'=>$first_designation,
            'required_last_enabled'=>$last_enabled?1:0,
            'required_last_designation_id'=>$last_designation
        ]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}
// -----------------------------------------------------------------------------
// End of file
// -----------------------------------------------------------------------------
