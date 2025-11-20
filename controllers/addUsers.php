<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

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
        throw new Exception('Authentication required.', 401);
    }

    // Simplified permission check for now. Re-enable if using a granular permission system.
    if ($auth->getRoleName() !== 'Admin') {
        throw new Exception('Insufficient permissions.', 403);
    }

    // --- Data Validation ---
    $isSeniorHigh = ($_POST['sector'] ?? '') === 'senior_high';
    // Email and section are now optional.
    $requiredFields = ['studentNumber', 'sector', 'program', 'yearLevel', 'lastName', 'firstName', 'password', 'confirmPassword'];
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

    if ($_POST['password'] !== $_POST['confirmPassword']) {
        throw new Exception('Passwords do not match.');
    }

    if (strlen($_POST['password']) < 8) {
        throw new Exception('Password must be at least 8 characters long.');
    }

    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format.');
    }

    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // 1. Check for existing user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$_POST['studentNumber']]);
    if ($stmt->fetch()) {
        throw new Exception("Student number '{$_POST['studentNumber']}' is already registered.");
    }

    // 2. Create user record
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $userSql = "INSERT INTO users (username, password, email, first_name, last_name, middle_name, contact_number, account_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([
        $_POST['studentNumber'],
        $hashedPassword,
        empty($_POST['email']) ? null : $_POST['email'],
        $_POST['firstName'],
        $_POST['lastName'],
        empty($_POST['middleName']) ? null : $_POST['middleName'],
        empty($_POST['phoneNumber']) ? null : $_POST['phoneNumber']
    ]);
    $userId = $pdo->lastInsertId();

    // 2.5 Assign the 'Student' role (assuming role_id 3 is for Student)
    $roleSql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
    $roleStmt = $pdo->prepare($roleSql);
    $roleStmt->execute([$userId, 3]); // Role ID 3 for 'Student'

    // 3. Get program_id and department_id from program_name
    $progStmt = $pdo->prepare("SELECT program_id, department_id FROM programs WHERE program_code = ? LIMIT 1");
    $progStmt->execute([$_POST['program']]);
    $programInfo = $progStmt->fetch(PDO::FETCH_ASSOC);

    if (!$programInfo) {
        throw new Exception("Program '{$_POST['program']}' not found. Please check program names.");
    }
    $programId = $programInfo['program_id'];
    $departmentId = $programInfo['department_id'];

    // 4. Create student record
    $dbSector = ($_POST['sector'] === 'senior_high') ? 'Senior High School' : 'College';

    // Map form year_level to database ENUM values
    $yearLevelMapping = [
        'Grade 11' => '1st Year',
        'Grade 12' => '2nd Year',
    ];
    $dbYearLevel = $yearLevelMapping[$_POST['yearLevel']] ?? $_POST['yearLevel'];

    $studentSql = "INSERT INTO students (student_id, user_id, program_id, department_id, sector, year_level, section) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $studentStmt = $pdo->prepare($studentSql);
    $studentStmt->execute([
        $_POST['studentNumber'],
        $userId,
        $programId,
        $departmentId,
        $dbSector,
        $dbYearLevel,
        empty($_POST['section']) ? null : $_POST['section']
    ]);

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Student registered successfully!',
        'user_id' => $userId,
        'credentials' => [
            'username' => $_POST['studentNumber'],
            'password' => $_POST['password'] // Return plain-text for the modal
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    $errorCode = $e->getCode() >= 400 ? $e->getCode() : 400;
    http_response_code($errorCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>