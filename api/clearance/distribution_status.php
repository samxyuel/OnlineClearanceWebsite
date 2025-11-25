<?php
/**
 * API: Check Form Distribution Job Status
 *
 * Returns the current status and progress of a form distribution job
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

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
    $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        exit;
    }

    $stmt = $connection->prepare("SELECT * FROM form_distribution_jobs WHERE job_id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }

    $percentage = $job['total_users'] > 0
        ? round(($job['processed_users'] / $job['total_users']) * 100, 2)
        : 0;

    $estimatedRemaining = null;
    if ($job['status'] === 'processing' && $job['processed_users'] > 0) {
        // Estimate: if we processed X users so far, remaining = (total - processed) / 50 batches
        // Each batch processes 50 users, cron runs every 1 minute
        $batchesRemaining = ceil(($job['total_users'] - $job['processed_users']) / 50);
        $estimatedRemaining = $batchesRemaining; // in minutes (since cron runs every minute)
    }

    // Get department name if department_id is set
    $departmentName = null;
    if ($job['department_id'] !== null) {
        $deptStmt = $connection->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $deptStmt->execute([$job['department_id']]);
        $departmentName = $deptStmt->fetchColumn();
    } else if ($job['clearance_type'] === 'Faculty') {
        $departmentName = 'Unassigned Faculty';
    }

    echo json_encode([
        'success' => true,
        'job' => [
            'job_id' => $job['job_id'],
            'clearance_type' => $job['clearance_type'],
            'academic_year_id' => $job['academic_year_id'],
            'semester_id' => $job['semester_id'],
            'department_id' => $job['department_id'],
            'department_name' => $departmentName,
            'status' => $job['status'],
            'progress' => [
                'processed' => $job['processed_users'],
                'total' => $job['total_users'],
                'percentage' => $percentage,
                'remaining' => $job['total_users'] - $job['processed_users']
            ],
            'results' => [
                'forms_created' => $job['forms_created'],
                'forms_skipped' => $job['forms_skipped'],
                'signatories_assigned' => $job['signatories_assigned']
            ],
            'estimated_remaining_minutes' => $estimatedRemaining,
            'created_at' => $job['created_at'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
            'error_message' => $job['error_message']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

