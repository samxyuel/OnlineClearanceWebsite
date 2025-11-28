<?php
/**
 * Admin Clearance Report Exporter
 * 
 * Allows authorized staff (Admin, School Administrator, Regular Staff, Program Head)
 * to export any user's clearance form with its current state, even if:
 * - The clearance period is ongoing or paused
 * - The form is not complete
 * - The period has not ended yet
 */

// Disable error display to prevent corruption of binary files
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/classes/ClearanceFormPDFGenerator.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Authentication Check
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

// 2. Authorization Check - Only allow specific roles
$userRole = $auth->getRoleName();
$allowedRoles = ['Admin', 'School Administrator', 'Regular Staff', 'Program Head'];

if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Forbidden: You do not have permission to export clearance forms for other users.'
    ]);
    exit;
}

// 3. Get form_id parameter
$form_id = $_GET['form_id'] ?? null;

if (!$form_id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: Clearance Form ID is required.']);
    exit;
}

// 4. Fetch Clearance Form Data (NO ownership check - admin can access any form)
try {
    $pdo = Database::getInstance()->getConnection();

    // Fetch clearance form details (no user_id ownership check)
    $formStmt = $pdo->prepare("
        SELECT 
            cf.clearance_form_id,
            cf.user_id,
            cf.clearance_form_progress, 
            cf.completed_at, 
            cf.clearance_type, 
            ay.year, 
            s.semester_name,
            u.first_name,
            u.middle_name,
            u.last_name
        FROM clearance_forms cf
        JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
        JOIN semesters s ON cf.semester_id = s.semester_id
        JOIN users u ON cf.user_id = u.user_id
        WHERE cf.clearance_form_id = ?
    ");
    $formStmt->execute([$form_id]);
    $formDetails = $formStmt->fetch(PDO::FETCH_ASSOC);

    if (!$formDetails) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Clearance form not found.']);
        exit;
    }

    // Determine user type (student or faculty)
    $targetUserId = $formDetails['user_id'];
    $userType = 'student'; // default
    
    $checkStudent = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ? LIMIT 1");
    $checkStudent->execute([$targetUserId]);
    if (!$checkStudent->fetch()) {
        $checkFaculty = $pdo->prepare("SELECT faculty_id FROM faculty WHERE user_id = ? LIMIT 1");
        $checkFaculty->execute([$targetUserId]);
        if ($checkFaculty->fetch()) {
            $userType = 'faculty';
        }
    }

    // Log admin export action for audit
    error_log("[ADMIN_EXPORT] User {$auth->getUserId()} ({$userRole}) exported clearance form {$form_id} for user {$targetUserId}");

} catch (Exception $e) {
    http_response_code(500);
    error_log("Admin Export Error (fetch): " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error preparing report: ' . $e->getMessage()]);
    exit;
}

// 5. Generate PDF using ClearanceFormPDFGenerator
ob_start();

try {
    set_time_limit(120); // 2 minutes
    
    // Prepare output filename
    $middleInitial = !empty($formDetails['middle_name']) ? substr($formDetails['middle_name'], 0, 1) . '. ' : '';
    $fullName = trim($formDetails['first_name'] . ' ' . $middleInitial . $formDetails['last_name']);
    $outputFile = sys_get_temp_dir() . '/clearance_report_admin_' . $form_id . '_' . time() . '.pdf';
    
    error_log("[ADMIN_EXPORT] Generating PDF for form_id={$form_id}, userType={$userType}, outputFile={$outputFile}");
    
    $generator = new ClearanceFormPDFGenerator($pdo);
    $generatedFile = $generator->generateClearancePDF($userType, $form_id, $outputFile);
    
    // Check for any output that was generated (this would corrupt binary files)
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("[ADMIN_EXPORT] ERROR: Unexpected output during PDF generation: " . substr($output, 0, 500));
        if (file_exists($generatedFile)) {
            @unlink($generatedFile);
        }
        throw new Exception('PDF generation produced unexpected output. Check error logs for details.');
    }
    
    if (!file_exists($generatedFile) || filesize($generatedFile) === 0) {
        throw new Exception("Generated PDF file is empty or missing");
    }
    
    error_log("[ADMIN_EXPORT] PDF generated successfully: {$generatedFile} (" . filesize($generatedFile) . " bytes)");
    
} catch (Exception $e) {
    // Clean up output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log("[ADMIN_EXPORT] PDF Generation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
    
    if (isset($outputFile) && file_exists($outputFile)) {
        @unlink($outputFile);
    }
    exit;
}

// 6. Send the generated PDF for download
if (!isset($generatedFile) || !file_exists($generatedFile) || filesize($generatedFile) === 0) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'The generated PDF is empty or could not be created.']);
    exit;
}

// Clear any output buffers before sending file to prevent corruption
while (ob_get_level()) {
    ob_end_clean();
}

// Send PDF headers and file
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Clearance_Report_' . str_replace(' ', '_', $fullName) . '.pdf"');
header('Content-Length: ' . filesize($generatedFile));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($generatedFile);
@unlink($generatedFile); // Clean up the temporary file
exit;

