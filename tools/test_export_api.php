<?php
/**
 * Test Script for Export API
 * Tests the report generation functionality
 * 
 * Usage: php tools/test_export_api.php [report_type] [format]
 * 
 * Examples:
 *   php tools/test_export_api.php student_progress pdf
 *   php tools/test_export_api.php faculty_progress xlsx
 *   php tools/test_export_api.php all  (tests all combinations)
 */

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/ReportGenerator.php';

// Color output for terminal
class Colors {
    public static $GREEN = "\033[32m";
    public static $RED = "\033[31m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $RESET = "\033[0m";
}

function printHeader($text) {
    echo "\n" . Colors::$BLUE . "=== " . $text . " ===" . Colors::$RESET . "\n";
}

function printSuccess($text) {
    echo Colors::$GREEN . "✓ " . $text . Colors::$RESET . "\n";
}

function printError($text) {
    echo Colors::$RED . "✗ " . $text . Colors::$RESET . "\n";
}

function printWarning($text) {
    echo Colors::$YELLOW . "⚠ " . $text . Colors::$RESET . "\n";
}

function printInfo($text) {
    echo "  " . $text . "\n";
}

// Get command line arguments
$reportType = $argv[1] ?? 'student_progress';
$fileFormat = $argv[2] ?? 'pdf';
$testAll = ($argv[1] ?? '') === 'all';

printHeader("Export API Test Script");

// Test configurations
$testCases = [
    [
        'report_type' => 'student_progress',
        'file_format' => 'pdf',
        'school_year' => '2024-2025',
        'semester_name' => '1st',
        'sector' => 'College',
        'department_id' => 1,
        'program_id' => 1
    ],
    [
        'report_type' => 'faculty_progress',
        'file_format' => 'pdf',
        'school_year' => '2024-2025',
        'semester_name' => '1st',
        'sector' => 'Faculty',
        'department_id' => 1,
        'program_id' => 0
    ],
    [
        'report_type' => 'student_progress',
        'file_format' => 'xlsx',
        'school_year' => '2024-2025',
        'semester_name' => '2nd',
        'sector' => 'Senior High School',
        'department_id' => 1,
        'program_id' => 1
    ]
];

// Check database connection
printHeader("Database Connection");
try {
    $pdo = Database::getInstance()->getConnection();
    printSuccess("Database connection successful");
} catch (Exception $e) {
    printError("Database connection failed: " . $e->getMessage());
    exit(1);
}

// Check if templates exist
printHeader("Template Files Check");
$templates = [
    'student_progress' => __DIR__ . '/../assets/templates/reports/Student_Clearance_Form_Progress.docx',
    'faculty_progress' => __DIR__ . '/../assets/templates/reports/Faculty_Clearance_Form_Progress.docx',
    'student_applicant_status' => __DIR__ . '/../assets/templates/reports/Student_Clearance_Applicant_Status.docx',
    'faculty_applicant_status' => __DIR__ . '/../assets/templates/reports/Faculty_Clearance_Applicant_Status.docx'
];

foreach ($templates as $type => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        printSuccess("$type template found (" . round($size/1024, 2) . " KB)");
    } else {
        printWarning("$type template not found: " . basename($path));
    }
}

// Check if ReportGenerator class exists
printHeader("Class Check");
if (class_exists('ReportGenerator')) {
    printSuccess("ReportGenerator class loaded");
} else {
    printError("ReportGenerator class not found");
    exit(1);
}

// Test data availability
printHeader("Test Data Check");
try {
    // Check for clearance periods
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM clearance_periods WHERE status = 'closed'");
    $periodCount = $stmt->fetchColumn();
    printInfo("Closed clearance periods: $periodCount");
    
    // Check for clearance forms
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM clearance_forms");
    $formCount = $stmt->fetchColumn();
    printInfo("Total clearance forms: $formCount");
    
    if ($formCount == 0) {
        printWarning("No clearance forms found - reports will be empty");
    }
} catch (Exception $e) {
    printWarning("Could not check data: " . $e->getMessage());
}

// Run tests
printHeader("Running Tests");

if ($testAll) {
    $testsToRun = $testCases;
} else {
    // Filter test cases based on arguments
    $filtered = array_filter($testCases, function($tc) use ($reportType, $fileFormat) {
        return $tc['report_type'] === $reportType && $tc['file_format'] === $fileFormat;
    });
    
    if (!empty($filtered)) {
        $testsToRun = array_values($filtered);
    } else {
        // If no exact match, create a custom test case
        $testsToRun = [[
            'report_type' => $reportType,
            'file_format' => $fileFormat,
            'school_year' => '2024-2025',
            'semester_name' => '1st',
            'sector' => $reportType === 'faculty_progress' ? 'Faculty' : 'College',
            'department_id' => 1,
            'program_id' => ($reportType === 'faculty_progress') ? 0 : 1
        ]];
    }
}

$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($testsToRun as $testCase) {
    $rt = $testCase['report_type'];
    $ff = $testCase['file_format'];
    
    echo "\n" . Colors::$BLUE . "Test: $rt ($ff)" . Colors::$RESET . "\n";
    
    // Check if template exists
    $templatePath = $templates[$rt] ?? null;
    if (!$templatePath || !file_exists($templatePath)) {
        printWarning("Template not found - skipping test");
        $skipped++;
        continue;
    }
    
    try {
        // Create ReportGenerator instance
        $generator = new ReportGenerator($pdo);
        
        // Prepare parameters
        $params = [
            'school_year' => $testCase['school_year'],
            'semester_name' => $testCase['semester_name'],
            'sector' => $testCase['sector'],
            'department_id' => $testCase['department_id'],
            'program_id' => $testCase['program_id'],
            'role' => 'Admin',
            'user_id' => 1
        ];
        
        printInfo("Parameters:");
        printInfo("  School Year: " . $params['school_year']);
        printInfo("  Semester: " . $params['semester_name']);
        printInfo("  Sector: " . $params['sector']);
        printInfo("  Department ID: " . $params['department_id']);
        printInfo("  Program ID: " . ($params['program_id'] ?: 'N/A'));
        
        // Generate report
        $startTime = microtime(true);
        $outputFile = $generator->generateReport($rt, $ff, $params);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Verify file was created
        if (!file_exists($outputFile)) {
            throw new Exception("Generated file not found: $outputFile");
        }
        
        $fileSize = filesize($outputFile);
        $fileSizeKB = round($fileSize / 1024, 2);
        
        if ($fileSize == 0) {
            throw new Exception("Generated file is empty");
        }
        
        printSuccess("Report generated successfully");
        printInfo("  File: " . basename($outputFile));
        printInfo("  Size: $fileSizeKB KB");
        printInfo("  Duration: {$duration}ms");
        
        // For PDF, try to verify it's a valid PDF
        if ($ff === 'pdf') {
            $header = file_get_contents($outputFile, false, null, 0, 4);
            if ($header === '%PDF') {
                printSuccess("Valid PDF file");
            } else {
                printWarning("PDF header check failed (might still be valid)");
            }
        }
        
        // Clean up test file
        @unlink($outputFile);
        printInfo("  Cleaned up temporary file");
        
        $passed++;
        
    } catch (Exception $e) {
        printError("Test failed: " . $e->getMessage());
        printInfo("  Error: " . $e->getFile() . ":" . $e->getLine());
        $failed++;
        
        // Clean up on error
        if (isset($outputFile) && file_exists($outputFile)) {
            @unlink($outputFile);
        }
    }
}

// Summary
printHeader("Test Summary");
echo "  Total tests: " . count($testsToRun) . "\n";
printSuccess("Passed: $passed");
if ($failed > 0) {
    printError("Failed: $failed");
}
if ($skipped > 0) {
    printWarning("Skipped: $skipped");
}

if ($failed === 0 && $skipped === 0) {
    printSuccess("\nAll tests passed!");
    exit(0);
} else {
    exit(1);
}

