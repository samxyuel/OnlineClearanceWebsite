<?php
// Online Clearance Website - Clearance Report Exporter

// Disable error display to prevent corruption of binary files
ini_set('display_errors', 0);
error_reporting(E_ALL);

// NEW: Use programmatic PDF generator (similar to ReportGenerator approach)
require_once __DIR__ . '/../../includes/classes/ClearanceFormPDFGenerator.php';
require_once __DIR__ . '/../../includes/config/database.php';

// OLD: DOCX template approach (commented out for backup)
// require_once __DIR__ . '/../../includes/helpers/generateClearanceTemplatePDF.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Authentication and Authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'student';
$form_id = $_GET['form_id'] ?? null;

if (!$form_id) {
    http_response_code(400);
    echo "Error: Clearance Form ID is required.";
    exit;
}

// 2. Fetch User and Clearance Data
try {
    $pdo = Database::getInstance()->getConnection();

    // Fetch user details
    $stmt = $pdo->prepare("SELECT username, first_name, last_name, middle_name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User with ID {$user_id} not found.");
    }

    // Fetch clearance form details and verify ownership
    $formStmt = $pdo->prepare("
        SELECT cf.clearance_form_progress, cf.completed_at, cf.clearance_type, ay.year, s.semester_name
        FROM clearance_forms cf
        JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
        JOIN semesters s ON cf.semester_id = s.semester_id
        WHERE cf.clearance_form_id = ? AND cf.user_id = ?
    ");
    $formStmt->execute([$form_id, $user_id]);
    $formDetails = $formStmt->fetch(PDO::FETCH_ASSOC);

    if (!$formDetails) {
        throw new Exception("Clearance form not found or you do not have permission to access it.");
    }

    // Fetch signatories for the form
    $sigStmt = $pdo->prepare("
        SELECT 
            d.designation_name,
            cs.action,
            cs.remarks,
            cs.date_signed,
            CONCAT(u_sig.first_name, ' ', u_sig.last_name) as signatory_name
        FROM clearance_signatories cs
        JOIN designations d ON cs.designation_id = d.designation_id
        LEFT JOIN users u_sig ON cs.actual_user_id = u_sig.user_id
        WHERE cs.clearance_form_id = ?
        ORDER BY d.designation_name
    ");
    $sigStmt->execute([$form_id]);
    $signatories = $sigStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error preparing report: " . $e->getMessage());
    echo "Error preparing report: " . $e->getMessage();
    exit;
}

// ============================================================================
// NEW APPROACH: Programmatic PDF Generation (similar to ReportGenerator)
// ============================================================================
// Start output buffering to catch any PHP warnings/errors
ob_start();

try {
    // Set time limit for PDF generation
    set_time_limit(120); // 2 minutes
    
    // Prepare output file
    $middleInitial = !empty($user['middle_name']) ? substr($user['middle_name'], 0, 1) . '. ' : '';
    $fullName = trim($user['first_name'] . ' ' . $middleInitial . $user['last_name']);
    $outputFile = sys_get_temp_dir() . '/clearance_report_' . $user_id . '_' . time() . '.pdf';
    
    // Generate PDF using new ClearanceFormPDFGenerator
    error_log("--- [NEW] Calling ClearanceFormPDFGenerator ---");
    error_log("User Type: $user_type, Form ID: $form_id");
    
    $generator = new ClearanceFormPDFGenerator($pdo);
    $generatedFile = $generator->generateClearancePDF($user_type, $form_id, $outputFile);
    
    // Check for any output that was generated (this would corrupt binary files)
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("[export_report.php] ERROR: Unexpected output generated during PDF generation: " . substr($output, 0, 500));
        if (file_exists($generatedFile)) {
            @unlink($generatedFile);
        }
        throw new Exception('PDF generation produced unexpected output. Check error logs for details.');
    }
    
    if (!file_exists($generatedFile) || filesize($generatedFile) === 0) {
        throw new Exception("Generated PDF file is empty or missing");
    }
    
    error_log("PDF generated successfully: $generatedFile (" . filesize($generatedFile) . " bytes)");
    
} catch (Exception $e) {
    // Clean up output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    error_log("PDF Generation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    if (isset($outputFile) && file_exists($outputFile)) {
        @unlink($outputFile);
    }
    exit;
}

// ============================================================================
// OLD APPROACH: DOCX Template Method (commented out for backup)
// ============================================================================
/*
$clearance_status = ucfirst(str_replace('-', ' ', $formDetails['clearance_form_progress']));

// Fetch student-specific details (program, department) if user is a student
$studentDetails = null;
if ($user_type === 'student') {
    $studentStmt = $pdo->prepare("
        SELECT s.year_level, s.section, p.program_name, d.department_name
        FROM students s
        LEFT JOIN programs p ON s.program_id = p.program_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $studentStmt->execute([$user_id]);
    $studentDetails = $studentStmt->fetch(PDO::FETCH_ASSOC);
}

// Extract Registrar from signatories
$registrarName = 'Registrar'; // default fallback
$registrarAction = 'Pending'; // default fallback
foreach ($signatories as $s) {
    if (isset($s['designation_name']) && stripos($s['designation_name'], 'Registrar') !== false) {
        if (!empty($s['signatory_name'])) {
            $registrarName = $s['signatory_name'];
        }
        $registrarAction = $s['action'] ?? 'Pending';
        break; // Found the registrar, stop looping
    }
}

// 3. Prepare Data for the DOCX Template
$fullName = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'][0] . '. ' : '') . $user['last_name']));

$templateData = [
    'FULL_NAME' => $fullName,
    'STUDENT_ID' => $user['username'],
    'USER_TYPE' => ucfirst($user_type),
    'PROGRAM' => $studentDetails['program_name'] ?? 'N/A',
    'DEPARTMENT' => $studentDetails['department_name'] ?? 'N/A',
    'YEAR_LEVEL' => $studentDetails['year_level'] ?? 'N/A',
    'SECTION' => $studentDetails['section'] ?? 'N/A',
    'ACADEMIC_YEAR' => $formDetails['year'],
    'TERM' => $formDetails['semester_name'],
    'CLEARANCE_STATUS' => $clearance_status,
    'COMPLETION_DATE' => $formDetails['completed_at'] ? date('F j, Y', strtotime($formDetails['completed_at'])) : 'N/A',
    'DATE_GENERATED' => date('F j, Y, g:i a'),
    'REGISTRAR_NAME' => $registrarName,
    'REGISTRAR_ACTION' => $registrarAction,
    'CLEARANCE_FORM_ID' => $form_id,
];
// 4. Define Template Paths and Output File
$schoolTemplatePath = __DIR__ . '/../../includes/templates/StudentClearance_format_ver2_SchoolCopy.docx';
$studentTemplatePath = __DIR__ . '/../../includes/templates/StudentClearance_format_ver2_StudentCopy.docx';
$outputFile = sys_get_temp_dir() . '/clearance_report_' . $user_id . '.pdf';

// Validate that template files exist before proceeding
if (!file_exists($schoolTemplatePath) || !file_exists($studentTemplatePath)) {
    http_response_code(500);
    $missing = [];
    if (!file_exists($schoolTemplatePath)) $missing[] = basename($schoolTemplatePath);
    if (!file_exists($studentTemplatePath)) $missing[] = basename($studentTemplatePath);
    echo "Error: The following required template files are missing: " . implode(', ', $missing);
    exit;
}

// 5. Generate the PDF using the helper function
try {
    // Set a higher time limit for the potentially slow DOCX to PDF conversion
    set_time_limit(120); // 2 minutes

    // --- DEBUG LOGGING ---
    error_log("--- Calling generateClearanceTemplatePDF ---");
    error_log("Template Data: " . json_encode($templateData, JSON_PRETTY_PRINT));
    error_log("Signatories Data (" . count($signatories) . " items): " . json_encode($signatories, JSON_PRETTY_PRINT));
    generateClearanceTemplatePDF($templateData, $signatories, $schoolTemplatePath, $studentTemplatePath, $outputFile);
} catch (Exception $e) {
    http_response_code(500);
    error_log("PDF Generation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "Error generating report: " . $e->getMessage();
    if (file_exists($outputFile)) {
        unlink($outputFile);
    }
    exit;
}
*/

// 6. Send the generated PDF for download
if (!isset($generatedFile) || !file_exists($generatedFile) || filesize($generatedFile) === 0) {
    // Clean any output buffers
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

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Clearance_Report_' . str_replace(' ', '_', $fullName) . '.pdf"');
header('Content-Length: ' . filesize($generatedFile));
readfile($generatedFile);
@unlink($generatedFile); // Clean up the temporary file
exit;