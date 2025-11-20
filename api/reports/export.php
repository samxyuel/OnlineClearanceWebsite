<?php
// Reports Export Endpoint - role-aware export for xlsx/xls/pdf

// Disable error display to prevent corruption of binary files
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CORS/Headers
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/classes/ReportGenerator.php';

// Auth
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Inputs
$fileFormat  = strtolower($_POST['fileFormat'] ?? 'pdf');
$periodId    = isset($_POST['period_id']) ? (int)$_POST['period_id'] : 0;
$schoolYear  = trim($_POST['school_year'] ?? '');
$semesterName= trim($_POST['semester_name'] ?? '');
$reportType  = trim($_POST['report_type'] ?? '');
$sector      = trim($_POST['sector'] ?? '');
$departmentId= isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
$programId   = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
$fileName    = preg_replace('/[^A-Za-z0-9_\-]/', '_', ($_POST['fileName'] ?? 'report_export'));
$roleInput   = trim($_POST['role'] ?? '');

$roleName = $auth->getRoleName() ?: $roleInput; // trust session
$roleNorm = strtolower($roleName);

// Validate basic inputs: allow period_id OR (school_year + semester_name)
if (!$periodId && !($schoolYear !== '' && $semesterName !== '')) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Either period_id or school_year + semester_name is required']);
    exit;
}
if (!$reportType || !$sector || !$departmentId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'report_type, sector, and department_id are required']);
    exit;
}

// Role-based report whitelist
$allowedByRole = [
    'admin' => ['student_progress','faculty_progress'],
    'school administrator' => ['student_progress','faculty_progress','student_applicant_status','faculty_applicant_status'],
    'program head' => ['student_applicant_status','faculty_applicant_status'],
    'regular staff' => ['student_applicant_status','faculty_applicant_status']
];

$allowedReports = $allowedByRole[$roleNorm] ?? [];
if (!in_array($reportType, $allowedReports, true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Report not allowed for your role']);
    exit;
}

// Scope validation for non-admins
try {
    $pdo = Database::getInstance()->getConnection();

    // Load user signatory assignment scope
    $userId = $auth->getUserId();
    $scope = [ 'sectors' => [], 'departments' => [] ];

    $stmt = $pdo->prepare("SELECT s.sector_name, ssa.department_id
                            FROM sector_signatory_assignments ssa
                            LEFT JOIN departments d ON d.department_id = ssa.department_id
                            LEFT JOIN sectors s ON s.sector_id = d.sector_id
                            WHERE ssa.user_id = ? AND ssa.is_active = 1");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['sector_name'])) $scope['sectors'][$row['sector_name']] = true;
        if (!empty($row['department_id'])) $scope['departments'][(int)$row['department_id']] = true;
    }

    $isAdmin = ($roleNorm === 'admin');
    if (!$isAdmin) {
        if ($roleNorm === 'program head') {
            // Program Head: only student sectors (College/SHS) and assigned department
            if (!in_array($sector, ['College','Senior High School'], true)) {
                throw new Exception('Program Head: sector not allowed');
            }
            if (empty($scope['departments'][$departmentId])) {
                throw new Exception('Program Head: department not in your scope');
            }
        } elseif ($roleNorm === 'school administrator' || $roleNorm === 'regular staff') {
            // If applicant status report, enforce signatory scope
            if (strpos($reportType, 'applicant_status') !== false) {
                if (!empty($scope['sectors']) && empty($scope['sectors'][$sector])) {
                    throw new Exception('Selected sector not in your scope');
                }
                if (!empty($scope['departments']) && empty($scope['departments'][$departmentId])) {
                    throw new Exception('Selected department not in your scope');
                }
            }
        }
    }

    // Resolve school_year and semester_name if period_id was provided
    if ($periodId && (!$schoolYear || !$semesterName)) {
        $p = $pdo->prepare("SELECT ay.year AS academic_year, s.semester_name FROM clearance_periods cp
                             JOIN academic_years ay ON ay.academic_year_id = cp.academic_year_id
                             JOIN semesters s ON s.semester_id = cp.semester_id
                             WHERE cp.period_id = ?");
        $p->execute([$periodId]);
        if ($meta = $p->fetch(PDO::FETCH_ASSOC)) {
            $schoolYear = $meta['academic_year'] ?? $schoolYear;
            $semesterName = $meta['semester_name'] ?? $semesterName;
        }
    }

    if (!$schoolYear || !$semesterName) {
        throw new Exception('Could not determine school_year and semester_name from period_id');
    }

    // Prepare parameters for ReportGenerator
    $params = [
        'school_year' => $schoolYear,
        'semester_name' => $semesterName,
        'sector' => $sector,
        'department_id' => $departmentId,
        'program_id' => $programId,
        'role' => $roleName,
        'user_id' => $userId
    ];

    // Generate report using ReportGenerator
    // Start output buffering to catch any PHP warnings/errors
    ob_start();
    
    try {
        error_log("[export.php] === STARTING REPORT GENERATION ===");
        error_log("[export.php] Generating report: type=$reportType, format=$fileFormat");
        error_log("[export.php] Params: " . json_encode($params));
        
        $reportGenerator = new ReportGenerator($pdo);
        error_log("[export.php] ReportGenerator instance created");
        
        try {
            $generatedFile = $reportGenerator->generateReport($reportType, $fileFormat, $params);
            error_log("[export.php] generateReport() completed successfully");
        } catch (Exception $e) {
            $output = ob_get_clean();
            if (!empty($output)) {
                error_log("[export.php] ERROR: Output generated before exception: " . substr($output, 0, 500));
            }
            error_log("[export.php] Exception during generateReport(): " . $e->getMessage());
            error_log("[export.php] Stack trace: " . $e->getTraceAsString());
            throw $e;
        } catch (Error $e) {
            $output = ob_get_clean();
            if (!empty($output)) {
                error_log("[export.php] ERROR: Output generated before fatal error: " . substr($output, 0, 500));
            }
            error_log("[export.php] Fatal error during generateReport(): " . $e->getMessage());
            throw new Exception('Fatal error during report generation: ' . $e->getMessage());
        }
        
        // Check for any output that was generated (this would corrupt binary files)
        $output = ob_get_clean();
        if (!empty($output)) {
            error_log("[export.php] ERROR: Unexpected output generated during report generation: " . substr($output, 0, 500));
            if (isset($generatedFile) && file_exists($generatedFile)) {
                @unlink($generatedFile);
            }
            throw new Exception('Report generation produced unexpected output. Check error logs for details.');
        }
        
        error_log("[export.php] generateReport() returned: $generatedFile");
        error_log("[export.php] File exists: " . (file_exists($generatedFile) ? 'yes' : 'no'));
        
        if (!file_exists($generatedFile)) {
            error_log("[export.php] ERROR: Generated file does not exist at path: $generatedFile");
            throw new Exception('Generated file not found at: ' . $generatedFile);
        }
        
        $fileSize = filesize($generatedFile);
        error_log("[export.php] File size: $fileSize bytes");
        
        if ($fileSize === 0) {
            error_log("[export.php] ERROR: Generated file is empty (0 bytes)!");
            throw new Exception('Generated file is empty');
        }
        
        if ($fileSize < 500) {
            error_log("[export.php] WARNING: Generated file is very small ($fileSize bytes), may be corrupted");
            // Read first few bytes to see what's in there
            $sample = file_get_contents($generatedFile, false, null, 0, min(200, $fileSize));
            error_log("[export.php] File content sample (hex): " . bin2hex($sample));
            error_log("[export.php] File content sample (text): " . substr($sample, 0, 200));
            
            // Check if it's HTML/JSON error instead of PDF
            if (substr($sample, 0, 4) !== '%PDF') {
                error_log("[export.php] ERROR: File does not start with PDF signature!");
                throw new Exception('Generated file is not a valid PDF (starts with: ' . substr($sample, 0, 20) . ')');
            }
        }
        
        // Determine download filename
        $timestampSuffix = '_' . date('Ymd_Hi');
        $ext = in_array($fileFormat, ['xlsx','xls','pdf'], true) ? $fileFormat : 'pdf';
        $downloadName = $fileName . $timestampSuffix . '.' . $ext;
        
        // Set appropriate content type
        $contentTypes = [
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel'
        ];
        $contentType = $contentTypes[$ext] ?? 'application/octet-stream';
        
        // Verify file exists and is readable before streaming
        if (!file_exists($generatedFile)) {
            throw new Exception('Generated file not found: ' . $generatedFile);
        }
        
        if (!is_readable($generatedFile)) {
            throw new Exception('Generated file is not readable: ' . $generatedFile);
        }
        
        $actualFileSize = filesize($generatedFile);
        if ($actualFileSize === false || $actualFileSize == 0) {
            throw new Exception('Generated file is empty or unreadable');
        }
        
        // Stream file to browser
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . $actualFileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Clear any output buffers before sending file to prevent corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        readfile($generatedFile);
        
        // Clean up temporary file
        @unlink($generatedFile);
        
        exit;
        
    } catch (Exception $e) {
        // Clean up output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Clean up on error
        if (isset($generatedFile) && file_exists($generatedFile)) {
            @unlink($generatedFile);
        }
        throw $e;
    }

} catch (Exception $e) {
    // Clear any output that might have been generated
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(400);
    header('Content-Type: application/json');
    $errorMessage = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
    error_log("[export.php] FINAL CATCH: Exception caught - " . $errorMessage);
    error_log("[export.php] Stack trace: " . $errorTrace);
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage,
        'trace' => $errorTrace  // Include trace in response for debugging
    ]);
}


