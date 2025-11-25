<?php
/**
 * Cronjob Script: Process Form Distribution Jobs
 *
 * This script runs via cron-job.org every 1 minute
 * Processes one batch (50 users) from the oldest pending/processing job
 *
 * Usage: https://your-app.herokuapp.com/api/cron/process_form_distribution.php?token=YOUR_TOKEN
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../clearance/form_distribution.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Increase execution time for cronjob (Heroku allows up to 10 minutes)
set_time_limit(600); // 10 minutes
ini_set('max_execution_time', 600);

header('Content-Type: application/json');

// Handle CORS preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(204); // No Content
    exit;
}

// Security: Check authentication token
$authToken = $_GET['token'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
$expectedToken = getenv('CRON_AUTH_TOKEN');

if (empty($expectedToken)) {
    error_log("❌ CRON ERROR: CRON_AUTH_TOKEN not set in environment variables");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

if (!hash_equals($expectedToken, $authToken)) {
    error_log("❌ CRON SECURITY: Invalid token attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$connection = Database::getInstance()->getConnection();

try {
    // Step 1: Find oldest pending or processing job
    $stmt = $connection->query("
        SELECT * FROM form_distribution_jobs
        WHERE status IN ('pending', 'processing')
        ORDER BY created_at ASC, job_id ASC
        LIMIT 1
    ");
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo json_encode([
            'success' => true,
            'message' => 'No jobs to process',
            'processed' => 0
        ]);
        exit(0);
    }

    $jobId = $job['job_id'];
    $batchSize = 50; // Process 50 users per run

    // Step 2: Mark job as processing (if it was pending)
    if ($job['status'] === 'pending') {
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'processing', started_at = NOW()
            WHERE job_id = ?
        ")->execute([$jobId]);
        error_log("📋 Started processing job #$jobId");
    }

    // Step 3: Get signatory assignments for this clearance type (once per job)
    $signatoryAssignments = getSectorSignatoryAssignments($connection, $job['clearance_type']);
    
    // Check if Program Head should be dynamically added
    $settingsStmt = $connection->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ?");
    $settingsStmt->execute([$job['clearance_type']]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

    if ($settings && $settings['include_program_head'] == 1) {
        $phStmt = $connection->prepare("SELECT designation_id FROM designations WHERE designation_name = 'Program Head'");
        $phStmt->execute();
        $programHeadDesignationId = $phStmt->fetchColumn();

        if ($programHeadDesignationId) {
            $signatoryAssignments[] = [
                'designation_id' => $programHeadDesignationId,
                'designation_name' => 'Program Head',
                'is_program_head' => true
            ];
        }
    }

    // De-duplicate signatory assignments
    $uniqueSignatoryAssignments = [];
    $seenDesignations = [];
    foreach ($signatoryAssignments as $assignment) {
        if (!in_array($assignment['designation_id'], $seenDesignations)) {
            $uniqueSignatoryAssignments[] = $assignment;
            $seenDesignations[] = $assignment['designation_id'];
        }
    }
    $signatoryAssignments = $uniqueSignatoryAssignments;

    if (empty($signatoryAssignments)) {
        // Mark job as failed
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'failed',
                error_message = ?,
                updated_at = NOW()
            WHERE job_id = ?
        ")->execute(["No signatory assignments found for {$job['clearance_type']}", $jobId]);
        
        echo json_encode([
            'success' => false,
            'message' => "No signatory assignments found for {$job['clearance_type']}",
            'job_id' => $jobId
        ]);
        exit(1);
    }

    // Step 4: Get users for this batch
    $startFrom = $job['current_batch_start'];
    $users = [];

    if ($job['clearance_type'] === 'Faculty') {
        // For faculty, department_id can be NULL
        $users = getEligibleFacultyBatch(
            $connection,
            $job['department_id'], // Can be NULL for unassigned faculty
            $startFrom,
            $batchSize
        );
    } else {
        // For students (College or Senior High School)
        if ($job['department_id'] === null) {
            // This shouldn't happen for students, but handle it
            error_log("⚠️ WARNING: Job #$jobId has NULL department_id for {$job['clearance_type']}");
            $users = [];
        } else {
            $users = getEligibleStudentsBatch(
                $connection,
                $job['clearance_type'],
                $job['department_id'],
                $startFrom,
                $batchSize
            );
        }
    }

    if (empty($users)) {
        // No more users, mark as completed
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'completed', completed_at = NOW()
            WHERE job_id = ?
        ")->execute([$jobId]);
        
        $departmentName = $job['department_id'] ? "Department ID {$job['department_id']}" : "Unassigned Faculty";
        error_log("✅ Job #$jobId completed! Processed {$job['processed_users']} users for $departmentName");
        
        echo json_encode([
            'success' => true,
            'message' => "Job #$jobId completed",
            'job_id' => $jobId,
            'processed' => $job['processed_users'],
            'total' => $job['total_users']
        ]);
        exit(0);
    }

    // Step 5: Process this batch
    $batchStartTime = microtime(true);
    $batchFormsCreated = 0;
    $batchFormsSkipped = 0;
    $batchSignatoriesAssigned = 0;
    $usersProcessed = [];

    $departmentName = $job['department_id'] ? "Department ID {$job['department_id']}" : "Unassigned Faculty";
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    error_log("🚀 BATCH START: Processing " . count($users) . " users for Job #{$jobId}");
    error_log("   Sector: {$job['clearance_type']}");
    error_log("   Department: {$departmentName}");
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    foreach ($users as $user) {
        // Process user (create form, assign signatories)
        $result = processSingleUser(
            $connection,
            $user,
            $job['academic_year_id'],
            $job['semester_id'],
            $job['clearance_type'],
            $signatoryAssignments
        );

        if ($result['form_created']) {
            $batchFormsCreated++;
            $usersProcessed[] = [
                'name' => $result['user_name'] ?? 'Unknown',
                'user_id' => $result['user_id'] ?? 'N/A',
                'form_id' => $result['form_id'] ?? 'N/A',
                'signatories' => $result['signatories_assigned'] ?? 0
            ];
        } else {
            $batchFormsSkipped++;
        }
        $batchSignatoriesAssigned += $result['signatories_assigned'];
    }

    // Batch summary log
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    error_log("📊 BATCH SUMMARY (Job #{$jobId}):");
    error_log("   ✅ Forms Created: {$batchFormsCreated} users received clearance forms");
    error_log("   ⏭️  Forms Skipped: {$batchFormsSkipped} users (forms already exist)");
    error_log("   👥 Signatories Assigned: {$batchSignatoriesAssigned} total");
    
    if ($batchFormsCreated > 0) {
        error_log("   📋 Users who received forms:");
        foreach ($usersProcessed as $processedUser) {
            error_log("      • {$processedUser['name']} (ID: {$processedUser['user_id']}) - Form: {$processedUser['form_id']} - {$processedUser['signatories']} signatories");
        }
    }
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    // Step 6: Update job progress
    $newProcessed = $job['processed_users'] + count($users);
    $newStart = $startFrom + count($users);
    $newFormsCreated = $job['forms_created'] + $batchFormsCreated;
    $newFormsSkipped = $job['forms_skipped'] + $batchFormsSkipped;
    $newSignatoriesAssigned = $job['signatories_assigned'] + $batchSignatoriesAssigned;

    $connection->prepare("
        UPDATE form_distribution_jobs
        SET processed_users = ?,
            current_batch_start = ?,
            forms_created = ?,
            forms_skipped = ?,
            signatories_assigned = ?,
            updated_at = NOW()
        WHERE job_id = ?
    ")->execute([
        $newProcessed,
        $newStart,
        $newFormsCreated,
        $newFormsSkipped,
        $newSignatoriesAssigned,
        $jobId
    ]);

    // Step 7: Check if done
    if ($newProcessed >= $job['total_users']) {
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'completed', completed_at = NOW()
            WHERE job_id = ?
        ")->execute([$jobId]);

        $duration = round((microtime(true) - $batchStartTime), 2);
        $departmentName = $job['department_id'] ? "Department ID {$job['department_id']}" : "Unassigned Faculty";
        
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        error_log("🎉 Job #$jobId COMPLETED!");
        error_log("   Department: $departmentName");
        error_log("   ✅ Total Users: {$job['total_users']} users");
        error_log("   📋 Forms Created: {$newFormsCreated} users received clearance forms");
        error_log("   ⏭️  Forms Skipped: {$newFormsSkipped} users (forms already existed)");
        error_log("   👥 Signatories Assigned: {$newSignatoriesAssigned} total");
        error_log("   ⏱️  Final batch took: {$duration}s");
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        echo json_encode([
            'success' => true,
            'message' => "Job #$jobId completed",
            'job_id' => $jobId,
            'processed' => $newProcessed,
            'total' => $job['total_users'],
            'forms_created' => $newFormsCreated,
            'forms_skipped' => $newFormsSkipped,
            'signatories_assigned' => $newSignatoriesAssigned
        ]);
    } else {
        $duration = round((microtime(true) - $batchStartTime), 2);
        $remaining = $job['total_users'] - $newProcessed;
        $percentage = round(($newProcessed / $job['total_users']) * 100, 1);
        $departmentName = $job['department_id'] ? "Department ID {$job['department_id']}" : "Unassigned Faculty";
        $estimatedMinutes = ceil($remaining / 50); // 50 users per batch, 1 batch per minute

        error_log("📊 Job #$jobId Progress:");
        error_log("   Department: $departmentName");
        error_log("   ✅ Total Forms Created So Far: {$newFormsCreated}");
        error_log("   👥 Total Users Processed: {$newProcessed} of {$job['total_users']} users ($percentage%)");
        error_log("   ⏭️  Forms Skipped: {$newFormsSkipped}");
        error_log("   📝 Total Signatories Assigned: {$newSignatoriesAssigned}");
        error_log("   ⏱️  Remaining: ~{$remaining} users (~{$estimatedMinutes} minutes)");
        error_log("   ⚡ This batch: " . count($users) . " users processed in {$duration}s");
        error_log("   🔄 Will continue next minute...");

        echo json_encode([
            'success' => true,
            'message' => "Job #$jobId in progress",
            'job_id' => $jobId,
            'processed' => $newProcessed,
            'total' => $job['total_users'],
            'percentage' => $percentage,
            'remaining' => $remaining,
            'batch_size' => count($users),
            'duration' => $duration,
            'forms_created' => $newFormsCreated,
            'forms_skipped' => $newFormsSkipped,
            'signatories_assigned' => $newSignatoriesAssigned
        ]);
    }

} catch (Exception $e) {
    // Mark job as failed
    if (isset($jobId)) {
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'failed',
                error_message = ?,
                updated_at = NOW()
            WHERE job_id = ?
        ")->execute([$e->getMessage(), $jobId]);
    }

    error_log("❌ CRONJOB ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'job_id' => $jobId ?? null
    ]);
    exit(1);
}
?>

