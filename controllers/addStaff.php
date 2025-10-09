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
    if (!$auth->hasPermission('create_users')) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Insufficient permissions']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    // Expected keys: employeeId, staffPosition (designation), first_name, last_name, (middle_name?), staffEmail?, staffContact?, role_id?
    $employeeId   = trim($input['employeeId'] ?? '');
    $designation  = trim($input['staffPosition'] ?? '');
    $firstName    = trim($input['first_name'] ?? '');
    $lastName     = trim($input['last_name'] ?? '');
    $middleName   = trim($input['middle_name'] ?? '');
    $emailInput   = trim($input['staffEmail'] ?? '');
    $employmentStatus = trim($input['facultyEmploymentStatus'] ?? '');
    $contactInput = trim($input['staffContact'] ?? '');

    if ($firstName === '' || $lastName === '') {
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>'first_name and last_name are required']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // Resolve role from designation (Program Head => Program Head role, School Administrator => School Administrator role, else Staff)
    $designationLower = strtolower($designation);
    $roleName = 'Regular Staff';
    if ($designationLower === 'program head') { $roleName = 'Program Head'; }
    else if ($designationLower === 'school administrator') { $roleName = 'School Administrator'; }

    $r = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1");
    $r->execute([$roleName]);
    $row = $r->fetch(PDO::FETCH_ASSOC);
    $roleId = $row ? (int)$row['role_id'] : 7;

    // Build user payload
    // Normalize and validate employee ID format (LLLDDDDL)
    if ($employeeId !== '') { $employeeId = strtoupper($employeeId); }
    if ($employeeId === '' || !preg_match('/^[A-Z]{3}[0-9]{4}[A-Z]$/', $employeeId)) {
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>'Employee ID must match LCA1234P format']);
        exit;
    }

    // Username equals employee number
    $username = $employeeId;
    // Initial password policy: LastName + EmployeeID
    $plainPassword = $lastName . $employeeId;
    $email    = $emailInput !== '' ? $emailInput : ($username.'@placeholder.local');

    $userManager = new UserManager();
    $createRes = $userManager->createUser([
        'username'   => $username,
        'email'      => $email,
        'password'   => $plainPassword,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name'  => $lastName,
        'role_id'    => $roleId,
        'account_status'     => 'active'
    ]);

    if (!$createRes['success']) {
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>$createRes['message'] ?? 'Create failed']);
        exit;
    }

    $newUserId = (int)$createRes['user_id'];
    $departmentIdToSet = null;

    // A Program Head is assigned to a single department.
    if (isset($input['assignedDepartments']) && is_array($input['assignedDepartments']) && !empty($input['assignedDepartments'][0])) {
        $departmentIdToSet = (int)$input['assignedDepartments'][0];
    }

    $employeeOut = null;

    // Always create or update a staff row using the provided employeeId (LCA123P format)
    // Resolve designation if provided
    $designationId = null;
    if ($designation !== '') {
        $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$designation]);
        $drow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($drow) { $designationId = (int)$drow['designation_id']; }
    }

    $staffCategory = 'Regular Staff';
    if (strcasecmp($designation, 'Program Head') === 0) { $staffCategory = 'Program Head'; }
    else if (strcasecmp($designation, 'School Administrator') === 0) { $staffCategory = 'School Administrator'; }

    // Existing staff row?
    $s = $pdo->prepare("SELECT employee_number FROM staff WHERE user_id = ? LIMIT 1");
    $s->execute([$newUserId]);
    $staffRow = $s->fetch(PDO::FETCH_ASSOC);
    if ($staffRow) {
        $employeeOut = $staffRow['employee_number'];
        // Update header fields and ensure employee_number is the provided one if present
        if ($employeeId !== '' && $employeeId !== $employeeOut) {
            $employeeOut = $employeeId;
        }
        $upd = $pdo->prepare("UPDATE staff SET employee_number = ?, designation_id = ?, staff_category = ?, employment_status = ?, department_id = ?, is_active = 1, updated_at = NOW() WHERE user_id = ?");
        $upd->execute([$employeeOut, $designationId, $staffCategory, $employmentStatus ?: null, $departmentIdToSet, $newUserId]);
    } else {
        // Insert new staff row with provided employeeId (validated by client pattern)
        $employeeOut = $employeeId !== '' ? $employeeId : null;
        if ($employeeOut === null) {
            // Fallback: derive from username if it matches expected pattern; otherwise keep a simple placeholder
            if (preg_match('/^[A-Z]{3}[0-9]{4}[A-Z]$/', $username)) { $employeeOut = $username; }
            else { $employeeOut = 'LCA0000A'; }
        }
        $ins = $pdo->prepare("INSERT INTO staff (employee_number, user_id, designation_id, staff_category, employment_status, department_id, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,1,NOW(),NOW())");
        $ins->execute([$employeeOut, $newUserId, $designationId, $staffCategory, $employmentStatus ?: null, $departmentIdToSet]);
    }

    // Handle "Is also a faculty" logic
    $isAlsoFaculty = isset($input['isAlsoFaculty']) && $input['isAlsoFaculty'] === 'on';
    if ($isAlsoFaculty) {
        // Check if a faculty record already exists
        $stmt = $pdo->prepare("SELECT 1 FROM faculty WHERE user_id = ?");
        $stmt->execute([$newUserId]);
        $facultyExists = $stmt->fetchColumn();

        if (!$facultyExists) {
            // Create new faculty record
            if ($employmentStatus) {
                $ins = $pdo->prepare("INSERT INTO faculty (employee_number, user_id, employment_status, department_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$employeeOut, $newUserId, $employmentStatus, $departmentIdToSet]); // Use the same department ID
            }
        }
    }



    // Audit
    require_once __DIR__ . '/../includes/functions/audit_functions.php';
    logActivity($auth->getUserId(), 'Staff Registered', [
        'target_user_id'=>$newUserId,
        'employee_id'=>$employeeOut,
        'designation'=>$designation
    ]);

    echo json_encode([
        'status'       => 'success',
        'message'      => 'Staff registered',
        'user_id'      => $newUserId,
        'employee_id'  => $employeeOut,
        'username'     => $username,
        'temporary_pw' => $plainPassword
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Server error: '.$e->getMessage()]);
}
?>