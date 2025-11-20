<?php
/**
 * API: Update Student Graduation Status
 * 
 * This endpoint allows administrators to update the graduation status of students.
 * It can mark students as "Graduated" or update their enrollment status.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

function send_json_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

try {
    // Authentication check
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        send_json_response(false, [], 'Authentication required.', 401);
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // Check if user has admin privileges
    $roleCheck = $pdo->prepare("
        SELECT r.role_name 
        FROM users u 
        JOIN user_roles ur ON u.user_id = ur.user_id 
        JOIN roles r ON ur.role_id = r.role_id 
        WHERE u.user_id = ? AND r.role_name IN ('Admin', 'School Administrator')
    ");
    $roleCheck->execute([$userId]);
    $hasAdminRole = $roleCheck->fetchColumn();
    
    if (!$hasAdminRole) {
        send_json_response(false, [], 'Admin access required.', 403);
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(false, [], 'Only POST method allowed.', 405);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        send_json_response(false, [], 'Invalid JSON input.', 400);
    }

    // Validate required fields
    if (!isset($input['student_ids']) || !is_array($input['student_ids']) || empty($input['student_ids'])) {
        send_json_response(false, [], 'Student IDs are required.', 400);
    }

    if (!isset($input['action']) || !in_array($input['action'], ['graduate', 'retain'])) {
        send_json_response(false, [], 'Valid action (graduate/retain) is required.', 400);
    }

    $studentIds = $input['student_ids'];
    $action = $input['action'];

    // Validate that all student IDs are valid integers
    foreach ($studentIds as $studentId) {
        if (!is_numeric($studentId) || $studentId <= 0) {
            send_json_response(false, [], 'Invalid student ID format.', 400);
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Verify all students exist and are 4th year students
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        $verifyStmt = $pdo->prepare("
            SELECT s.student_id, s.user_id, s.year_level, s.enrollment_status, 
                   u.first_name, u.last_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.user_id IN ($placeholders) AND s.year_level = '4th Year'
        ");
        $verifyStmt->execute($studentIds);
        $verifiedStudents = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($verifiedStudents) !== count($studentIds)) {
            $pdo->rollBack();
            send_json_response(false, [], 'Some students were not found or are not 4th year students.', 400);
        }

        $updatedCount = 0;
        $updatedStudents = [];

        if ($action === 'graduate') {
            // Mark students as graduated
            $updateStmt = $pdo->prepare("
                UPDATE students 
                SET enrollment_status = 'Graduated', updated_at = NOW()
                WHERE user_id IN ($placeholders) AND year_level = '4th Year'
            ");
            $updateStmt->execute($studentIds);
            $updatedCount = $updateStmt->rowCount();

            // Log the graduation for each student
            foreach ($verifiedStudents as $student) {
                $logStmt = $pdo->prepare("
                    INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                    VALUES (?, 'graduation_status_updated', ?, ?, ?)
                ");
                $logStmt->execute([
                    $student['user_id'],
                    json_encode([
                        'action' => 'graduated',
                        'student_id' => $student['student_id'],
                        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                        'updated_by' => $userId,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);

                $updatedStudents[] = [
                    'student_id' => $student['student_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'status' => 'Graduated'
                ];
            }

        } elseif ($action === 'retain') {
            // Keep students enrolled (no status change, just log the action)
            foreach ($verifiedStudents as $student) {
                $logStmt = $pdo->prepare("
                    INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                    VALUES (?, 'graduation_status_updated', ?, ?, ?)
                ");
                $logStmt->execute([
                    $student['user_id'],
                    json_encode([
                        'action' => 'retained',
                        'student_id' => $student['student_id'],
                        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                        'updated_by' => $userId,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);

                $updatedStudents[] = [
                    'student_id' => $student['student_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'status' => 'Retained'
                ];
            }
            $updatedCount = count($verifiedStudents);
        }

        // Commit transaction
        $pdo->commit();

        // Log admin activity
        $adminLogStmt = $pdo->prepare("
            INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
            VALUES (?, 'bulk_graduation_update', ?, ?, ?)
        ");
        $adminLogStmt->execute([
            $userId,
            json_encode([
                'action' => $action,
                'student_count' => $updatedCount,
                'student_ids' => $studentIds,
                'timestamp' => date('Y-m-d H:i:s')
            ]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        send_json_response(true, [
            'updated_count' => $updatedCount,
            'action' => $action,
            'students' => $updatedStudents
        ], "Successfully updated {$updatedCount} student(s) graduation status.");

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database error in update_graduation_status.php: " . $e->getMessage());
    send_json_response(false, [], 'Database error occurred.', 500);
} catch (Exception $e) {
    error_log("Error in update_graduation_status.php: " . $e->getMessage());
    send_json_response(false, [], 'An error occurred while processing the request.', 500);
}
?>
