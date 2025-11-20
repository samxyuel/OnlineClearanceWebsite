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
    // Temporarily disable auth for testing
    // if (!$auth->isLoggedIn()) {
    //     http_response_code(401);
    //     echo json_encode(['status'=>'error','message'=>'Authentication required']);
    //     exit;
    // }
    // if (!$auth->hasPermission('edit_users')) {
    //     http_response_code(403);
    //     echo json_encode(['status'=>'error','message'=>'Insufficient permissions']);
    //     exit;
    // }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $employeeId = $input['employeeId'] ?? null;
    $userId = $input['user_id'] ?? null;

    if (empty($employeeId) && empty($userId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Employee ID or User ID is required']);
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
                $userData['contact_number'] = $input['staffContact'] ?: null;
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

    // --- START: Staff, Faculty, and Assignment Table Update Logic ---

    // Determine primary designation and department for legacy tables
    $primaryDesignationId = null;
    if (isset($input['staffPosition'])) {
        $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$input['staffPosition']]);
        $drow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($drow) {
            $primaryDesignationId = (int)$drow['designation_id'];
        }
    }

    $primaryDepartmentId = null;
    if (isset($input['assignedDepartments']) && is_array($input['assignedDepartments']) && !empty($input['assignedDepartments'][0])) {
        $primaryDepartmentId = (int)$input['assignedDepartments'][0];
    }

    // Update staff table for backward compatibility
    $staffData = [];
    if ($primaryDesignationId) {
        $staffData['designation_id'] = $primaryDesignationId;
        $staffData['staff_category'] = 'Regular Staff';
        if (strcasecmp($input['staffPosition'], 'Program Head') === 0) { $staffData['staff_category'] = 'Program Head'; }
        else if (strcasecmp($input['staffPosition'], 'School Administrator') === 0) { $staffData['staff_category'] = 'School Administrator'; }
    }
    if (isset($input['facultyEmploymentStatus'])) {
        $staffData['employment_status'] = $input['facultyEmploymentStatus'] ?: null;
    }
    // Always update department_id in staff table (it might be set to null)
    $staffData['department_id'] = $primaryDepartmentId;

    if (!empty($staffData)) {
        $updateFields = [];
        $params = [];
        foreach ($staffData as $key => $value) {
            $updateFields[] = "`$key` = ?";
            $params[] = $value;
        }
        $params[] = $userId;
        $sql = "UPDATE staff SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Handle "Is also a faculty" logic for backward compatibility
    $isAlsoFaculty = !empty($input['isAlsoFaculty']);
    if ($isAlsoFaculty) {
        $stmt = $pdo->prepare("SELECT 1 FROM faculty WHERE user_id = ?");
        $stmt->execute([$userId]);
        $facultyExists = $stmt->fetchColumn();
        $employmentStatus = $input['facultyEmploymentStatus'] ?? null;

        if ($facultyExists) {
            if ($employmentStatus) {
                $upd = $pdo->prepare("UPDATE faculty SET employment_status = ?, department_id = ? WHERE user_id = ?");
                $upd->execute([$employmentStatus, $primaryDepartmentId, $userId]);
            }
        } else {
            if ($employmentStatus) {
                $ins = $pdo->prepare("INSERT INTO faculty (employee_number, user_id, employment_status, department_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$employeeId, $userId, $employmentStatus, $primaryDepartmentId]);
            }
        }
    }

    // --- NEW LOGIC FOR ASSIGNMENT TABLES ---

    // Clear existing assignments to handle updates cleanly
    $pdo->prepare("DELETE FROM user_department_assignments WHERE user_id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM user_designation_assignments WHERE user_id = ?")->execute([$userId]);

    // Handle multiple department assignments
    $assignedDepartments = $input['assignedDepartments'] ?? [];
    if (!empty($assignedDepartments)) {
        $stmt = $pdo->prepare("INSERT INTO user_department_assignments (user_id, department_id, is_primary) VALUES (?, ?, ?)");
        $isFirst = true;
        foreach ($assignedDepartments as $deptId) {
            if (!empty($deptId)) {
                $stmt->execute([$userId, (int)$deptId, $isFirst ? 1 : 0]);
                $isFirst = false;
            }
        }
    }

    // Handle multiple designation assignments
    $assignedDesignations = $input['assignedDesignations'] ?? [];
    if (!empty($assignedDesignations)) {
        // Normalize and ensure ints
        $assignedDesignations = array_values(array_filter(array_map('intval', $assignedDesignations)));

        // Ensure the primary designation (from staffPosition) is present and placed first
        if ($primaryDesignationId) {
            if (!in_array($primaryDesignationId, $assignedDesignations, true)) {
                array_unshift($assignedDesignations, $primaryDesignationId);
            } else {
                // Move primaryDesignationId to the front
                $assignedDesignations = array_values(array_unique($assignedDesignations));
                $assignedDesignations = array_merge([ $primaryDesignationId ], array_filter($assignedDesignations, function($v) use($primaryDesignationId){ return $v !== $primaryDesignationId; }));
            }
        }

        $stmt = $pdo->prepare("INSERT INTO user_designation_assignments (user_id, designation_id, is_primary) VALUES (?, ?, ?)");
        $isFirst = true;
        foreach ($assignedDesignations as $desigId) {
            if (!empty($desigId)) {
                $stmt->execute([$userId, (int)$desigId, $isFirst ? 1 : 0]);
                $isFirst = false;
            }
        }
    } elseif ($primaryDesignationId) {
        $stmt = $pdo->prepare("INSERT INTO user_designation_assignments (user_id, designation_id, is_primary) VALUES (?, ?, 1)");
        $stmt->execute([$userId, $primaryDesignationId]);
    }
    // --- END: Staff, Faculty, and Assignment Table Update Logic ---


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
