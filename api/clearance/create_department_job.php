<?php
/**
 * API: Create Form Distribution Job for a Department
 *
 * Creates a job in the queue for processing a specific department
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/form_distribution.php';

header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$connection = Database::getInstance()->getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    // Validate required fields
    $requiredFields = ['clearance_type', 'academic_year_id', 'semester_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }

    $clearanceType = $input['clearance_type'];
    $academicYearId = (int)$input['academic_year_id'];
    $semesterId = (int)$input['semester_id'];
    $departmentId = isset($input['department_id']) ? ($input['department_id'] === null ? null : (int)$input['department_id']) : null;

    // Validate clearance type
    $validTypes = ['College', 'Senior High School', 'Faculty'];
    if (!in_array($clearanceType, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid clearance type']);
        exit;
    }

    // Count eligible users for this department
    $totalUsers = 0;
    if ($clearanceType === 'Faculty') {
        $totalUsers = countEligibleFaculty($connection, $departmentId);
    } else {
        if ($departmentId === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'department_id is required for ' . $clearanceType]);
            exit;
        }
        $totalUsers = countEligibleStudents($connection, $clearanceType, $departmentId);
    }

    if ($totalUsers === 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No eligible users found for this department'
        ]);
        exit;
    }

    // Check if job already exists for this department
    $checkSql = "
        SELECT job_id, status 
        FROM form_distribution_jobs 
        WHERE clearance_type = ? 
          AND academic_year_id = ? 
          AND semester_id = ? 
          AND (department_id = ? OR (department_id IS NULL AND ? IS NULL))
          AND status IN ('pending', 'processing')
    ";
    $checkStmt = $connection->prepare($checkSql);
    $checkStmt->execute([$clearanceType, $academicYearId, $semesterId, $departmentId, $departmentId]);
    $existingJob = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingJob) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Job already exists for this department',
            'job_id' => $existingJob['job_id'],
            'status' => $existingJob['status']
        ]);
        exit;
    }

    // Create job in database
    $stmt = $connection->prepare("
        INSERT INTO form_distribution_jobs
        (clearance_type, academic_year_id, semester_id, department_id, status, total_users, created_at)
        VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([$clearanceType, $academicYearId, $semesterId, $departmentId, $totalUsers]);
    $jobId = $connection->lastInsertId();

    // Get department name for response
    $departmentName = null;
    if ($departmentId !== null) {
        $deptStmt = $connection->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $deptStmt->execute([$departmentId]);
        $departmentName = $deptStmt->fetchColumn();
    } else if ($clearanceType === 'Faculty') {
        $departmentName = 'Unassigned Faculty';
    }

    error_log("📋 FORM DISTRIBUTION: Job #$jobId queued for $clearanceType - " . ($departmentName ?? "Department ID $departmentId") . " ($totalUsers users)");

    echo json_encode([
        'success' => true,
        'message' => 'Form distribution job queued successfully',
        'job' => [
            'job_id' => $jobId,
            'clearance_type' => $clearanceType,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'department_id' => $departmentId,
            'department_name' => $departmentName,
            'total_users' => $totalUsers,
            'status' => 'pending',
            'queued_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    error_log("❌ FORM DISTRIBUTION JOB CREATION ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

