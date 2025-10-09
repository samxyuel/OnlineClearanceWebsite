<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/UserManager.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status'=>'error','message'=>'Method not allowed']);
        exit;
    }

    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status'=>'error','message'=>'Authentication required']);
        exit;
    }
    if (!$auth->hasPermission('edit_users')) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Insufficient permissions']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $employeeId = $input['employeeId'] ?? null;

    if (empty($employeeId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();
    $userManager = new UserManager();

    // Find user_id from employeeId (which is the user's username)
    $user = $userManager->getUserByUsername($employeeId);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Staff member not found.']);
        exit;
    }
    $userId = (int)$user['user_id'];

    // Fields to update in the users table
    $userData = [];
    $allowedUserFields = ['first_name', 'last_name', 'middle_name'];
    foreach ($allowedUserFields as $field) {
        if (isset($input[$field])) {
            $userData[$field] = $input[$field];
        }
    }
    // Map frontend names to backend names
    if (isset($input['staffEmail'])) $userData['email'] = $input['staffEmail'];
    if (isset($input['staffContact'])) {
        // Pass the contact number to the user update payload
        $userData['contact_number'] = $input['staffContact'];
    }
    if (isset($input['staffStatus'])) {
        $userData['account_status'] = ($input['staffStatus'] === 'essential' || $input['staffStatus'] === 'active') ? 'active' : 'inactive';
    }

    // Handle role change if staffPosition is provided
    if (isset($input['staffPosition'])) {
        $designation = trim($input['staffPosition']);
        $designationLower = strtolower($designation);
        $roleName = 'Regular Staff';
        if ($designationLower === 'program head') { $roleName = 'Program Head'; }
        else if ($designationLower === 'school administrator') { $roleName = 'School Administrator'; }

        $r = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1");
        $r->execute([$roleName]);
        $row = $r->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $userData['role_id'] = (int)$row['role_id'];
        }
    }

    // Update user data
    if (!empty($userData)) {
        $updateRes = $userManager->updateUser($userId, $userData);
        if (!$updateRes['success']) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $updateRes['message'] ?? 'Failed to update user']);
            exit;
        }
    }

    // Update staff table data
    $staffData = [];
    if (isset($input['staffPosition'])) {
        $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$input['staffPosition']]);
        $drow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($drow) {
            $staffData['designation_id'] = (int)$drow['designation_id'];
        }
        $staffData['staff_category'] = 'Regular Staff';
        if (strcasecmp($input['staffPosition'], 'Program Head') === 0) { $staffData['staff_category'] = 'Program Head'; }
        else if (strcasecmp($input['staffPosition'], 'School Administrator') === 0) { $staffData['staff_category'] = 'School Administrator'; }
    }

    // Handle department_id for non-Program Head staff if provided
    // This logic needs to run even if staffPosition is not being changed.
    $isProgramHead = false;
    if (isset($input['staffPosition'])) {
        $isProgramHead = (strcasecmp($input['staffPosition'], 'Program Head') === 0);
    }
    if (isset($input['department_id']) && !$isProgramHead) {
        $staffData['department_id'] = !empty($input['department_id']) ? (int)$input['department_id'] : null;
    }

    // Handle employment_status for faculty
    if (isset($input['facultyEmploymentStatus'])) {
        $staffData['employment_status'] = $input['facultyEmploymentStatus'] ?: null;
    }

    if (!empty($staffData)) {
        $updateFields = [];
        $params = [];
        foreach ($staffData as $key => $value) {
            $updateFields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $userId;
        $sql = "UPDATE staff SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Handle "Is also a faculty" logic
    $isAlsoFaculty = isset($input['isAlsoFaculty']) && $input['isAlsoFaculty'] === 'on';
    if ($isAlsoFaculty) {
        // Check if a faculty record already exists
        $stmt = $pdo->prepare("SELECT 1 FROM faculty WHERE user_id = ?");
        $stmt->execute([$userId]);
        $facultyExists = $stmt->fetchColumn();

        $employmentStatus = $input['facultyEmploymentStatus'] ?? null;

        if ($facultyExists) {
            // Update existing faculty record
            if ($employmentStatus) {
                $upd = $pdo->prepare("UPDATE faculty SET employment_status = ? WHERE user_id = ?");
                $upd->execute([$employmentStatus, $userId]);
            }
        } else {
            // Create new faculty record
            if ($employmentStatus) {
                $ins = $pdo->prepare("INSERT INTO faculty (employee_number, user_id, employment_status, created_at) VALUES (?, ?, ?, NOW())");
                $ins->execute([$employeeId, $userId, $employmentStatus]);
            }
        }
    } else {
        // If checkbox is unchecked, we might want to remove the faculty record (optional, can be destructive)
        // For now, we'll leave it. Deleting can be a separate, explicit action.
    }


    // Audit
    require_once __DIR__ . '/../includes/functions/audit_functions.php';
    logActivity($auth->getUserId(), 'Staff Updated', [
        'target_user_id' => $userId,
        'updated_fields' => array_keys($input)
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Staff updated successfully',
        'user_id' => $userId // Return user_id for client-side logic
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Server error: '.$e->getMessage()]);
}
?>
