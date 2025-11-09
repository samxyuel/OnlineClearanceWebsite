<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/classes/UserManager.php'; // Assuming UserManager handles user table updates

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$actingUserId = $auth->getUserId();
$actingUserRole = $auth->getRoleName();
$isAdmin = ($actingUserRole === 'Admin');
$isProgramHead = ($actingUserRole === 'Program Head');

$pdo = Database::getInstance()->getConnection();
$userManager = new UserManager();

$targetUserId = isset($_POST['studentId']) ? (int)$_POST['studentId'] : null;
$type = $_POST['type'] ?? ''; // 'student' or 'faculty'
$sector = $_POST['sector'] ?? ''; // 'college' or 'senior_high'

if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Target user ID (studentId) is required.']);
    exit;
}

// Basic authorization check
if (!$isAdmin && !$isProgramHead) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions.']);
    exit;
}

$pdo->beginTransaction();

try {
    // --- 1. Update Users Table Fields ---
    $userUpdateData = [];
    if (isset($_POST['lastName'])) $userUpdateData['last_name'] = trim($_POST['lastName']);
    if (isset($_POST['firstName'])) $userUpdateData['first_name'] = trim($_POST['firstName']);
    if (isset($_POST['middleName'])) $userUpdateData['middle_name'] = trim($_POST['middleName']);
    if (isset($_POST['email'])) $userUpdateData['email'] = trim($_POST['email']);
    if (isset($_POST['contactNumber'])) $userUpdateData['contact_number'] = trim($_POST['contactNumber']);
    if (isset($_POST['accountStatus'])) $userUpdateData['account_status'] = trim($_POST['accountStatus']);

    // Program Heads should not be able to change core user details like name, email, contact, account status.
    // These are typically admin-level changes.
    if ($isProgramHead && (
        isset($userUpdateData['last_name']) || isset($userUpdateData['first_name']) ||
        isset($userUpdateData['middle_name']) || isset($userUpdateData['email']) ||
        isset($userUpdateData['contact_number']) || isset($userUpdateData['account_status'])
    )) {
        // If a Program Head tries to update these, it's a permission violation.
        // For now, we'll just skip these updates for PH, but an error could also be thrown.
        // For this specific modal, these fields are `readonly`, so this check is mostly for API robustness.
        unset($userUpdateData['last_name'], $userUpdateData['first_name'], $userUpdateData['middle_name'],
              $userUpdateData['email'], $userUpdateData['contact_number'], $userUpdateData['account_status']);
    }

    if (!empty($userUpdateData)) {
        $updateUserResult = $userManager->updateUser($targetUserId, $userUpdateData);
        if (!$updateUserResult['success']) {
            throw new Exception("Failed to update user details: " . $updateUserResult['message']);
        }
    }

    // --- 2. Handle Password Change ---
    if (isset($_POST['editChangePassword']) && $_POST['editChangePassword'] === 'on' && isset($_POST['newPassword'])) {
        $newPassword = $_POST['newPassword'];
        $confirmNewPassword = $_POST['confirmNewPassword'];

        if ($newPassword !== $confirmNewPassword) {
            throw new Exception("New passwords do not match.");
        }
        if (strlen($newPassword) < 8) {
            throw new Exception("New password must be at least 8 characters long.");
        }

        $resetPasswordResult = $userManager->resetPassword($targetUserId, $newPassword);
        if (!$resetPasswordResult['success']) {
            throw new Exception("Failed to reset password: " . $resetPasswordResult['message']);
        }
    }

    // --- 3. Update Student/Faculty Specific Fields ---
    if ($type === 'student') {
        $studentUpdateData = [];
        $currentStudentDeptId = null;

        // Fetch current student's department for PH scope check
        $stmt = $pdo->prepare("SELECT department_id FROM students WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        $currentStudentDeptId = $stmt->fetchColumn();

        // Program Head specific checks
        if ($isProgramHead) {
            // Get departments assigned to this Program Head
            $phDeptsStmt = $pdo->prepare("
                SELECT ssa.department_id 
                FROM sector_signatory_assignments ssa
                WHERE ssa.user_id = ? AND ssa.clearance_type = ? AND ssa.is_active = 1
            ");
            $phDeptsStmt->execute([$actingUserId, ($sector === 'college' ? 'College' : 'Senior High School')]);
            $programHeadDepartments = $phDeptsStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array($currentStudentDeptId, $programHeadDepartments)) {
                throw new Exception("You do not have permission to update students outside your assigned departments.");
            }

            // Program Heads can only update year_level and section
            if (isset($_POST['yearLevel'])) $studentUpdateData['year_level'] = trim($_POST['yearLevel']);
            if (isset($_POST['generatedSection'])) $studentUpdateData['section'] = trim($_POST['generatedSection']);

            // Prevent PH from changing department or program
            if (isset($_POST['department']) || isset($_POST['program'])) {
                throw new Exception("Program Heads cannot change student's department or program.");
            }
        } else { // Admin can update all student fields
            if (isset($_POST['department'])) {
                // Resolve department_id from department_name
                $deptStmt = $pdo->prepare("SELECT department_id FROM departments WHERE department_id = ?");
                $deptStmt->execute([$_POST['department']]);
                $studentUpdateData['department_id'] = $deptStmt->fetchColumn();
                if (!$studentUpdateData['department_id']) {
                    throw new Exception("Invalid department selected.");
                }
            }
            if (isset($_POST['program'])) {
                // Resolve program_id from program_name
                $progStmt = $pdo->prepare("SELECT program_id FROM programs WHERE program_id = ?");
                $progStmt->execute([$_POST['program']]);
                $studentUpdateData['program_id'] = $progStmt->fetchColumn();
                if (!$studentUpdateData['program_id']) {
                    throw new Exception("Invalid program selected.");
                }
            }
            if (isset($_POST['yearLevel'])) $studentUpdateData['year_level'] = trim($_POST['yearLevel']);
            if (isset($_POST['generatedSection'])) $studentUpdateData['section'] = trim($_POST['generatedSection']);
        }

        if (!empty($studentUpdateData)) {
            $updateFields = [];
            $updateParams = [];
            foreach ($studentUpdateData as $field => $value) {
                $updateFields[] = "$field = ?";
                $updateParams[] = $value;
            }
            $updateParams[] = $targetUserId;

            $stmt = $pdo->prepare("UPDATE students SET " . implode(', ', $updateFields) . " WHERE user_id = ?");
            $stmt->execute($updateParams);
        }
        
        // ============================================
        // AUTO-REACTIVATION LOGIC FOR GRADUATED STUDENTS
        // ============================================
        // If a graduated student's details are being updated (sector, department, program, year_level, section),
        // automatically reactivate their account by changing account_status from 'graduated' to 'active'
        
        // Check if student is currently graduated
        $checkGraduatedStmt = $pdo->prepare("SELECT account_status FROM users WHERE user_id = ?");
        $checkGraduatedStmt->execute([$targetUserId]);
        $currentAccountStatus = $checkGraduatedStmt->fetchColumn();
        
        // Check if any student-specific fields are being updated
        $studentFieldsBeingUpdated = !empty($studentUpdateData);
        $hasRelevantUpdate = false;
        
        if ($studentFieldsBeingUpdated) {
            // Check if any of these fields are being updated: department_id, program_id, year_level, section
            // Also check if sector might change (via department change)
            $relevantFields = ['department_id', 'program_id', 'year_level', 'section'];
            foreach ($relevantFields as $field) {
                if (isset($studentUpdateData[$field])) {
                    $hasRelevantUpdate = true;
                    break;
                }
            }
        }
        
        // If student is graduated AND relevant fields are being updated, reactivate
        if ($currentAccountStatus === 'graduated' && $hasRelevantUpdate) {
            // Update account_status to 'active'
            $reactivateStmt = $pdo->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ?");
            $reactivateStmt->execute([$targetUserId]);
            
            // Log the reactivation
            $logStmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                VALUES (?, 'student_auto_reactivated', ?, ?, ?)
            ");
            $logStmt->execute([
                $targetUserId,
                json_encode([
                    'action' => 'auto_reactivated_from_graduated',
                    'student_id' => $targetUserId,
                    'updated_by' => $actingUserId,
                    'updated_fields' => array_keys($studentUpdateData),
                    'reason' => 'Student details updated (sector, department, program, year level, or section changed)',
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    } elseif ($type === 'faculty') {
        // This part would be for a FacultyEditModal.php if it existed and used this controller
        // For now, assuming only student updates are handled by this specific request context.
        // If a FacultyEditModal uses this, it would need similar logic to update the 'faculty' table.
        // Example:
        // $facultyUpdateData = [];
        // if (isset($_POST['employmentStatus'])) $facultyUpdateData['employment_status'] = trim($_POST['employmentStatus']);
        // // ... other faculty-specific fields
        // if (!empty($facultyUpdateData)) {
        //     $updateFields = [];
        //     $updateParams = [];
        //     foreach ($facultyUpdateData as $field => $value) {
        //         $updateFields[] = "$field = ?";
        //         $updateParams[] = $value;
        //     }
        //     $updateParams[] = $targetUserId;
        //     $stmt = $pdo->prepare("UPDATE faculty SET " . implode(', ', $updateFields) . " WHERE user_id = ?");
        //     $stmt->execute($updateParams);
        // }
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'User information updated successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400); // Use 400 for client-side errors/validation failures
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>