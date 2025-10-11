<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/UserManager.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    if (!$auth->hasPermission('create_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }

    // --- Data Validation ---
    $isSeniorHigh = ($_POST['sector'] ?? '') === 'senior_high';
    $requiredFields = ['studentNumber', 'sector', 'program', 'yearLevel', 'section', 'lastName', 'firstName', 'email', 'password'];
    // Address is not in the users table schema, removing from required fields for now.
    if (!$isSeniorHigh) {
        $requiredFields[] = 'department'; // Department is only required for College
    }
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
            exit;
        }
    }

    $pdo = Database::getInstance()->getConnection();
    $userManager = new UserManager();

    // --- User Creation ---
    $userPayload = [
        'username'       => $_POST['studentNumber'],
        'email'          => $_POST['email'],
        'password'       => $_POST['password'],
        'first_name'     => $_POST['firstName'],
        'last_name'      => $_POST['lastName'],
        'middle_name'    => $_POST['middleName'] ?? null,
        'role_id'        => 3, // Student Role ID
        'account_status' => 'active',
        'contact_number' => $_POST['phoneNumber'] ?? null,
        // 'address' is not a column in the `users` table per the schema.
    ];

    $pdo->beginTransaction();

    $userResult = $userManager->createUser($userPayload);

    if (!$userResult['success']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $userResult['message']]);
        exit;
    }

    $userId = $userResult['user_id'];

    // --- Student Record Creation ---
    // The `students` table schema has changed. It now uses `student_id` as the primary key.
    // Columns like `sector`, `department`, `program`, and `address` are no longer in the `students` table.
    // We will insert into the `users` table for general info and `students` for student-specific info.
    // The `program_id` needs to be resolved from the program name.
    
    $progStmt = $pdo->prepare("SELECT program_id FROM programs WHERE program_code = ? LIMIT 1");
    $progStmt->execute([trim($_POST['program'])]);
    $programId = $progStmt->fetchColumn();

    if (!$programId) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Program '{$_POST['program']}' not found. Please check program names."]);
        exit;
    }

    // Department ID is now sent directly from the form
    // For SHS, the department is not directly selected, so we need to find it based on the program.
    $departmentId = null;
    if ($isSeniorHigh) {
        $deptStmt = $pdo->prepare("SELECT department_id FROM programs WHERE program_id = ?");
        $deptStmt->execute([$programId]);
        $departmentId = $deptStmt->fetchColumn();
    } else {
        // For College, the department ID is sent from the form.
        if (!empty($_POST['department'])) {
            // Assuming the form sends department ID. If it sends name, a lookup is needed.
            $departmentId = (int)$_POST['department'];
        }
    }

    // Normalize year_level for the database ENUM
    // The `students` table in `database_schema.sql` has a `year_level` ENUM that includes '1st Year', '2nd Year', etc.
    $yearLevelMapping = [
        'Grade 11' => '1st Year', // Assuming SHS Grade 11 maps to 1st Year enum
        'Grade 12' => '2nd Year', // Assuming SHS Grade 12 maps to 2nd Year enum
    ];
    $dbYearLevel = $yearLevelMapping[$_POST['yearLevel']] ?? $_POST['yearLevel'];


    $studentSql = "INSERT INTO students (student_id, user_id, program_id, department_id, year_level, section) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $studentStmt = $pdo->prepare($studentSql);
    $studentStmt->execute([
        $_POST['studentNumber'],
        $userId,
        $programId,
        $departmentId,
        $dbYearLevel,
        $_POST['section']
    ]);

    $pdo->commit();

    // TODO: Implement welcome email logic if `sendWelcomeEmail` is checked.

    echo json_encode([
        'success' => true,
        'message' => 'Student registered successfully',
        'user_id' => $userId
    ]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>