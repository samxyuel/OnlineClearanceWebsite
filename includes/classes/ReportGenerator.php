<?php
/**
 * ReportGenerator Class
 * Handles generating reports from PDF templates using FPDI
 * Uses FPDI to import existing PDF templates and overlay dynamic data
 */

require_once __DIR__ . '/../vendor/autoload.php';
// FPDF (FPDI depends on it)
require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use setasign\Fpdi\Fpdi;

// Also include FPDF class directly for fallback
if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';
}


class ReportGenerator {
    private $pdo;
    private $tempDir;
    private $footerPages = []; // Track pages that already have footers
    
    public function __construct($connection) {
        $this->pdo = $connection;
        $this->tempDir = sys_get_temp_dir();
    }
    
    /**
     * Generate report from template
     * @param string $reportType One of: student_progress, faculty_progress, student_applicant_status, faculty_applicant_status
     * @param string $fileFormat xlsx, xls, or pdf
     * @param array $params [school_year, semester_name, sector, department_id, program_id, role]
     * @return string Path to generated file
     */
    public function generateReport($reportType, $fileFormat, $params) {
        error_log("[ReportGenerator] ========== generateReport START ==========");
        error_log("[ReportGenerator] generateReport called: type=$reportType, format=$fileFormat");
        error_log("[ReportGenerator] Params: " . json_encode($params));
        
        try {
            // Fetch report data from database FIRST (before any file operations)
            error_log("[ReportGenerator] Fetching report data from database...");
            $reportData = $this->fetchReportData($reportType, $params);
            error_log("[ReportGenerator] Report data fetched successfully");
            error_log("[ReportGenerator] Rows count: " . count($reportData['rows']));
            error_log("[ReportGenerator] Summary: " . json_encode($reportData['summary']));
            
            if ($fileFormat === 'pdf') {
                error_log("[ReportGenerator] Generating PDF using FPDI with PDF templates");
                // Use FPDI to import PDF template and overlay data
                $result = $this->generatePDFWithFPDI($reportType, $reportData, $params);
                error_log("[ReportGenerator] PDF generation completed, file: $result");
                return $result;
            } else {
                error_log("[ReportGenerator] Generating Excel file");
                $result = $this->generateExcel($reportType, $reportData, $params, $fileFormat);
                error_log("[ReportGenerator] Excel generation completed, file: $result");
                return $result;
            }
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR in generateReport: " . $e->getMessage());
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw $e;
        } finally {
            error_log("[ReportGenerator] ========== generateReport END ==========");
        }
    }
    
    /**
     * Get template file path (PDF templates)
     */
    private function getTemplatePath($reportType) {
        $templates = [
            'student_progress' => 'Student_Clearance_Form_Progress.pdf',
            'faculty_progress' => 'Faculty_Clearance_Form_Progress.pdf',
            'student_applicant_status' => 'Student_Clearance_Applicant_Status.pdf',
            'faculty_applicant_status' => 'Faculty_Clearance_Applicant_Status.pdf'
        ];
        
        $filename = $templates[$reportType] ?? null;
        if (!$filename) {
            throw new Exception("Unknown report type: $reportType");
        }
        
        $path = __DIR__ . '/../../assets/templates/reports/' . $filename;
        if (!file_exists($path)) {
            throw new Exception("PDF template not found: $path");
        }
        return $path;
    }
    
    /**
     * Fetch data from database based on report type
     */
    private function fetchReportData($reportType, $params) {
        $schoolYear = $params['school_year'];
        $semesterName = $params['semester_name'];
        $sector = $params['sector'];
        $departmentId = (int)($params['department_id'] ?? 0);
        $programId = (int)($params['program_id'] ?? 0);
        
        // Build WHERE conditions
        $where = ["ay.year = ?", "s.semester_name = ?", "cf.clearance_type = ?"];
        $bindings = [$schoolYear, $semesterName, $sector];
        
        // Join to clearance_periods to ensure status = 'closed'
        $joinPeriod = "JOIN clearance_periods cp ON cp.academic_year_id = ay.academic_year_id 
                       AND cp.semester_id = s.semester_id AND cp.sector = cf.clearance_type 
                       AND cp.status = 'closed'";
        
        if ($departmentId > 0) {
            if ($sector === 'Faculty') {
                $where[] = "f.department_id = ?";
                $bindings[] = $departmentId;
            } else {
                $where[] = "st.department_id = ?";
                $bindings[] = $departmentId;
            }
        }
        
        if ($programId > 0 && $sector !== 'Faculty') {
            $where[] = "st.program_id = ?";
            $bindings[] = $programId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        if (in_array($reportType, ['student_progress', 'faculty_progress'])) {
            // Progress reports: count clearance_forms.clearance_form_progress
            return $this->fetchProgressData($reportType, $whereClause, $bindings, $sector, $joinPeriod);
        } else {
            // Applicant status reports: count clearance_signatories.action
            // Pass params to filter by logged-in user's designation(s)
            return $this->fetchApplicantStatusData($reportType, $whereClause, $bindings, $sector, $joinPeriod, $params);
        }
    }
    
    /**
     * Fetch data for Progress reports
     */
    private function fetchProgressData($reportType, $whereClause, $bindings, $sector, $joinPeriod) {
        if ($reportType === 'student_progress') {
            $sql = "SELECT 
                        cf.clearance_form_id,
                        u.username as student_no,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        p.program_name,
                        st.year_level,
                        st.section,
                        cf.clearance_form_progress as form_status
                    FROM clearance_forms cf
                    JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                    JOIN semesters s ON cf.semester_id = s.semester_id
                    JOIN users u ON cf.user_id = u.user_id
                    LEFT JOIN students st ON u.user_id = st.user_id
                    LEFT JOIN programs p ON st.program_id = p.program_id
                    $joinPeriod
                    WHERE $whereClause
                    ORDER BY u.last_name, u.first_name";
        } else {
            // faculty_progress
            $sql = "SELECT 
                        cf.clearance_form_id,
                        f.employee_number,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        d.department_name as department,
                        f.employment_status,
                        cf.clearance_form_progress as form_status
                    FROM clearance_forms cf
                    JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                    JOIN semesters s ON cf.semester_id = s.semester_id
                    JOIN users u ON cf.user_id = u.user_id
                    JOIN faculty f ON u.user_id = f.user_id
                    LEFT JOIN departments d ON f.department_id = d.department_id
                    $joinPeriod
                    WHERE $whereClause
                    ORDER BY u.last_name, u.first_name";
        }
        
        // Log SQL for debugging
        error_log("[ReportGenerator] SQL: " . $sql);
        error_log("[ReportGenerator] Bindings: " . json_encode($bindings));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("[ReportGenerator] Rows fetched: " . count($rows));
        if (count($rows) > 0) {
            error_log("[ReportGenerator] First row: " . json_encode($rows[0]));
        }
        
        // Calculate summary counts
        // Map database values (unapplied, in-progress, complete) to display values
        $summary = ['TotalForms' => count($rows)];
        $statusCounts = ['unapplied' => 0, 'in-progress' => 0, 'complete' => 0];
        foreach ($rows as $row) {
            $status = strtolower(trim($row['form_status'] ?? 'unapplied'));
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            } else {
                // Handle variations or unknown values
                $statusCounts['unapplied']++;
            }
        }
        $summary['TotalUnapplied'] = $statusCounts['unapplied'];
        $summary['TotalInProgress'] = $statusCounts['in-progress'];
        $summary['TotalCompleted'] = $statusCounts['complete'];
        // Note: 'TotalApplied' may not be in template, but keep for compatibility
        $summary['TotalApplied'] = 0; // Applied is not a valid clearance_form_progress value
        
        error_log("[ReportGenerator] Summary: " . json_encode($summary));
        
        return ['rows' => $rows, 'summary' => $summary];
    }
    
    /**
     * Fetch data for Applicant Status reports
     * Filters by logged-in user's designation(s) to show only their assigned applicants
     */
    private function fetchApplicantStatusData($reportType, $whereClause, $bindings, $sector, $joinPeriod, $params = []) {
        $userId = $params['user_id'] ?? null;
        
        // Get logged-in user's designation_id(s) for this clearance period's sector
        $userDesignationIds = [];
        if ($userId) {
            $desigStmt = $this->pdo->prepare("SELECT DISTINCT designation_id 
                                               FROM sector_signatory_assignments 
                                               WHERE user_id = ? 
                                               AND clearance_type = ? 
                                               AND is_active = 1");
            $desigStmt->execute([$userId, $sector]);
            $userDesignationIds = $desigStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // If user has no designations for this sector, return empty result
        if (empty($userDesignationIds)) {
            return ['rows' => [], 'summary' => [
                'TotalForms' => 0,
                'TotalUnapplied' => 0,
                'TotalPending' => 0,
                'TotalInProgress' => 0,
                'TotalApproved' => 0,
                'TotalRejected' => 0
            ]];
        }
        
        // Build IN clause for designation_ids
        $placeholders = implode(',', array_fill(0, count($userDesignationIds), '?'));
        $designationFilter = "cs.designation_id IN ($placeholders) AND cs.actual_user_id = ?";
        $allBindings = array_merge($bindings, $userDesignationIds, [$userId]);
        
        if ($reportType === 'student_applicant_status') {
            $sql = "SELECT 
                        cf.clearance_form_id,
                        u.username as student_no,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        p.program_name,
                        st.year_level,
                        st.section,
                        MAX(des.designation_name) as designation_name,
                        MAX(CONCAT(us.first_name, ' ', us.last_name)) as signatory_name,
                        MAX(cs.action) as action_status,
                        MAX(cs.date_signed) as date_signed,
                        MAX(cs.remarks) as remarks
                    FROM clearance_forms cf
                    JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                    JOIN semesters s ON cf.semester_id = s.semester_id
                    JOIN users u ON cf.user_id = u.user_id
                    LEFT JOIN students st ON u.user_id = st.user_id
                    LEFT JOIN programs p ON st.program_id = p.program_id
                    INNER JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
                    LEFT JOIN designations des ON cs.designation_id = des.designation_id
                    LEFT JOIN users us ON cs.actual_user_id = us.user_id
                    $joinPeriod
                    WHERE $whereClause AND $designationFilter
                    GROUP BY cf.clearance_form_id, u.username, u.first_name, u.middle_name, u.last_name, 
                             p.program_name, st.year_level, st.section
                    ORDER BY u.last_name, u.first_name";
        } else {
            // faculty_applicant_status
            $sql = "SELECT 
                        cf.clearance_form_id,
                        f.employee_number,
                        u.first_name,
                        u.middle_name,
                        u.last_name,
                        d.department_name as department,
                        f.employment_status,
                        MAX(des.designation_name) as designation_name,
                        MAX(CONCAT(us.first_name, ' ', us.last_name)) as signatory_name,
                        MAX(cs.action) as action_status,
                        MAX(cs.date_signed) as date_signed,
                        MAX(cs.remarks) as remarks
                    FROM clearance_forms cf
                    JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                    JOIN semesters s ON cf.semester_id = s.semester_id
                    JOIN users u ON cf.user_id = u.user_id
                    JOIN faculty f ON u.user_id = f.user_id
                    LEFT JOIN departments d ON f.department_id = d.department_id
                    INNER JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
                    LEFT JOIN designations des ON cs.designation_id = des.designation_id
                    LEFT JOIN users us ON cs.actual_user_id = us.user_id
                    $joinPeriod
                    WHERE $whereClause AND $designationFilter
                    GROUP BY cf.clearance_form_id, f.employee_number, u.first_name, u.middle_name, u.last_name,
                             d.department_name, f.employment_status
                    ORDER BY u.last_name, u.first_name";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($allBindings);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary counts based on action
        $summary = ['TotalForms' => count($rows)];
        $actionCounts = ['Unapplied' => 0, 'Pending' => 0, 'In Progress' => 0, 'Approved' => 0, 'Rejected' => 0];
        foreach ($rows as $row) {
            $action = $row['action_status'] ?? 'Unapplied';
            if (isset($actionCounts[$action])) {
                $actionCounts[$action]++;
            } else {
                // Handle any other values
                $actionCounts['Unapplied']++;
            }
        }
        $summary['TotalUnapplied'] = $actionCounts['Unapplied'];
        $summary['TotalPending'] = $actionCounts['Pending'];
        $summary['TotalInProgress'] = $actionCounts['In Progress'];
        $summary['TotalApproved'] = $actionCounts['Approved'];
        $summary['TotalRejected'] = $actionCounts['Rejected'];
        
        return ['rows' => $rows, 'summary' => $summary];
    }
    
    /**
     * Generate PDF programmatically using FPDF (not importing template)
     * Creates PDF from scratch based on the clean layout specification
     */
    private function generatePDFWithFPDI($reportType, $reportData, $params) {
        // Start output buffering to catch any errors/warnings
        ob_start();
        
        // Track which pages have had footers drawn (to prevent duplicates)
        $this->footerPages = [];
        
        error_log("[ReportGenerator] ========== generatePDFWithFPDI START ==========");
        error_log("[ReportGenerator] generatePDFWithFPDI: Starting PDF generation for $reportType");
        error_log("[ReportGenerator] Rows to process: " . count($reportData['rows']));
        error_log("[ReportGenerator] Summary: " . json_encode($reportData['summary']));
        
        // Create FPDF instance (using FPDI class which extends FPDF)
        try {
            error_log("[ReportGenerator] Creating FPDF instance...");
            error_log("[ReportGenerator] Checking for FPDI class...");
            error_log("[ReportGenerator] class_exists('setasign\\Fpdi\\Fpdi'): " . (class_exists('setasign\Fpdi\Fpdi') ? 'true' : 'false'));
            error_log("[ReportGenerator] Checking for FPDF class...");
            error_log("[ReportGenerator] class_exists('FPDF'): " . (class_exists('FPDF') ? 'true' : 'false'));
            
            // Check if FPDI is available, otherwise use FPDF directly
            // Create a custom class that extends FPDI/FPDF to override Footer() method
            if (class_exists('setasign\Fpdi\Fpdi')) {
                error_log("[ReportGenerator] Instantiating custom FPDI class with Footer override...");
                
                // Create anonymous class extending FPDI with Footer override
                $pdf = new class('P', 'mm', 'A4') extends Fpdi {
                    private $reportGenerator;
                    
                    public function setReportGenerator($rg) {
                        $this->reportGenerator = $rg;
                    }
                    
                    function Footer() {
                        // Call ReportGenerator's footer builder
                        // This is called automatically by FPDF when each page is finalized
                        if ($this->reportGenerator) {
                            $reflection = new ReflectionClass($this->reportGenerator);
                            $method = $reflection->getMethod('buildPDFFooter');
                            $method->setAccessible(true);
                            $method->invoke($this->reportGenerator, $this);
                        }
                    }
                    
                    function Header() {
                        // Empty - we handle headers manually
                    }
                    
                    public function getTotalPages() {
                        // Get total pages using reflection to access FPDF's internal page count
                        try {
                            $reflection = new ReflectionClass($this);
                            if ($reflection->hasProperty('page')) {
                                $pageProp = $reflection->getProperty('page');
                                $pageProp->setAccessible(true);
                                return $pageProp->getValue($this);
                            }
                        } catch (Exception $e) {
                            // Fallback to PageNo()
                            return $this->PageNo();
                        }
                        return $this->PageNo();
                    }
                };
                
                $pdf->setReportGenerator($this);
                error_log("[ReportGenerator] Using custom FPDI class with Footer override - SUCCESS");
            } elseif (class_exists('FPDF')) {
                error_log("[ReportGenerator] Instantiating custom FPDF class with Footer override...");
                
                // Create anonymous class extending FPDF with Footer override
                $pdf = new class('P', 'mm', 'A4') extends FPDF {
                    private $reportGenerator;
                    
                    public function setReportGenerator($rg) {
                        $this->reportGenerator = $rg;
                    }
                    
                    function Footer() {
                        // Call ReportGenerator's footer builder
                        // This is called automatically by FPDF when each page is finalized
                        if ($this->reportGenerator) {
                            $reflection = new ReflectionClass($this->reportGenerator);
                            $method = $reflection->getMethod('buildPDFFooter');
                            $method->setAccessible(true);
                            $method->invoke($this->reportGenerator, $this);
                        }
                    }
                    
                    function Header() {
                        // Empty - we handle headers manually
                    }
                    
                    public function getTotalPages() {
                        // Get total pages using reflection to access FPDF's internal page count
                        try {
                            $reflection = new ReflectionClass($this);
                            if ($reflection->hasProperty('page')) {
                                $pageProp = $reflection->getProperty('page');
                                $pageProp->setAccessible(true);
                                return $pageProp->getValue($this);
                            }
                        } catch (Exception $e) {
                            // Fallback to PageNo()
                            return $this->PageNo();
                        }
                        return $this->PageNo();
                    }
                };
                
                $pdf->setReportGenerator($this);
                error_log("[ReportGenerator] Using custom FPDF class with Footer override - SUCCESS");
            } else {
                error_log("[ReportGenerator] ERROR: Neither FPDI nor FPDF class found!");
                throw new Exception('Neither FPDI nor FPDF class found. Please check that vendor libraries are installed.');
            }
            error_log("[ReportGenerator] PDF instance created successfully");
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR creating PDF instance: " . $e->getMessage());
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw new Exception('Failed to create PDF instance: ' . $e->getMessage());
        } catch (Error $e) {
            error_log("[ReportGenerator] FATAL ERROR creating PDF instance: " . $e->getMessage());
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw new Exception('Fatal error creating PDF instance: ' . $e->getMessage());
        }
        
        // Set PDF metadata
        $reportTitle = $this->getReportTitle($reportType);
        $pdf->SetCreator('goSTI Clearance System');
        $pdf->SetAuthor('STI College Lucena');
        $pdf->SetTitle($reportTitle);
        $pdf->SetSubject('Clearance Report');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 20); // 20mm from bottom for footer space
        
        // Note: FPDI doesn't have setPrintHeader/setPrintFooter methods
        // We'll handle headers/footers manually, so they won't interfere
        
        // Enable page numbering
        $pdf->AliasNbPages();
        
        // Add first page
        $pdf->AddPage();
        
        // Build the PDF according to layout
        try {
            error_log("[ReportGenerator] Building static header on first page...");
            $this->buildStaticHeader($pdf); // Logo + STI COLLEGE LUCENA (every page)
            error_log("[ReportGenerator] Building body header on first page...");
            $this->buildBodyHeader($pdf, $reportType, $params); // Title, period, info boxes (page 1 only)
            error_log("[ReportGenerator] Building PDF summary...");
            $this->buildPDFSummary($pdf, $reportData['summary'], $reportType); // Summary (page 1 only)
            error_log("[ReportGenerator] Building PDF table...");
            $this->buildPDFTable($pdf, $reportType, $reportData['rows'], $params);
            // Static footer is added in buildPDFTable (on each page at fixed position)
            error_log("[ReportGenerator] PDF content built successfully");
            
            // Footer() method is automatically called by FPDF when:
            // 1. AddPage() is called (for the previous page)
            // 2. Close() is called via Output() (for the last page)
            // So all pages should already have footers via the Footer() override
            error_log("[ReportGenerator] Footer pages tracked: " . implode(', ', $this->footerPages));
            
            // Ensure at least one page exists
            if ($pdf->PageNo() == 0) {
                error_log("[ReportGenerator] WARNING: No pages created, adding empty page");
                $pdf->AddPage();
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(0, 10, 'No data available', 0, 1, 'C');
            }
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR building PDF content: " . $e->getMessage());
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            // Try to add an error page to the PDF
            try {
                if ($pdf->PageNo() == 0) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->Cell(0, 10, 'Error generating report', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->MultiCell(0, 5, 'Error: ' . $e->getMessage(), 0, 'L');
            } catch (Exception $innerE) {
                error_log("[ReportGenerator] Failed to add error page: " . $innerE->getMessage());
            }
            throw $e;
        } catch (Error $e) {
            error_log("[ReportGenerator] FATAL ERROR building PDF content: " . $e->getMessage());
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw new Exception('Fatal error building PDF: ' . $e->getMessage());
        }
        
        // Discard any output that was accidentally generated
        $output = ob_get_clean();
        if (!empty($output)) {
            error_log("[ReportGenerator] WARNING: Output buffer contained data: " . substr($output, 0, 200));
        }
        
        // Save PDF
        $pdfPath = tempnam($this->tempDir, 'report_') . '.pdf';
        error_log("[ReportGenerator] About to save PDF to: $pdfPath");
        error_log("[ReportGenerator] Current page count: " . $pdf->PageNo());
        
        // Start output buffering again for the Output call
        ob_start();
        
        try {
            error_log("[ReportGenerator] Calling PDF->Output('F', '$pdfPath')...");
            // Use Output with 'F' flag to write to file
            $result = $pdf->Output('F', $pdfPath);
            
            // Check if any output was generated (this would corrupt the PDF)
            $output = ob_get_clean();
            if (!empty($output)) {
                error_log("[ReportGenerator] ERROR: Output was generated during PDF->Output(): " . substr($output, 0, 500));
                // If output was generated, the file might be corrupted - delete it
                if (file_exists($pdfPath)) {
                    @unlink($pdfPath);
                }
                throw new Exception('PDF generation produced unexpected output, file may be corrupted');
            }
            
            error_log("[ReportGenerator] PDF->Output() completed successfully");
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR in PDF->Output(): " . $e->getMessage());
            error_log("[ReportGenerator] Error type: " . get_class($e));
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw $e;
        } catch (Error $e) {
            error_log("[ReportGenerator] FATAL ERROR in PDF->Output(): " . $e->getMessage());
            error_log("[ReportGenerator] Error type: " . get_class($e));
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw new Exception('PDF generation failed: ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log("[ReportGenerator] THROWABLE ERROR in PDF->Output(): " . $e->getMessage());
            error_log("[ReportGenerator] Error type: " . get_class($e));
            error_log("[ReportGenerator] Stack trace: " . $e->getTraceAsString());
            throw new Exception('PDF generation failed: ' . $e->getMessage());
        }
        
        // Verify file was created
        if (!file_exists($pdfPath)) {
            error_log("[ReportGenerator] CRITICAL ERROR: PDF file was not created at: $pdfPath");
            error_log("[ReportGenerator] Temp directory exists: " . (is_dir($this->tempDir) ? 'yes' : 'no'));
            error_log("[ReportGenerator] Temp directory writable: " . (is_writable($this->tempDir) ? 'yes' : 'no'));
            throw new Exception('PDF file was not created at: ' . $pdfPath);
        }
        
        $pdfSize = filesize($pdfPath);
        error_log("[ReportGenerator] PDF file created successfully");
        error_log("[ReportGenerator] File path: $pdfPath");
        error_log("[ReportGenerator] File size: $pdfSize bytes");
        
        if ($pdfSize < 1000) {
            error_log("[ReportGenerator] WARNING: PDF file is very small ($pdfSize bytes), content may be missing");
            error_log("[ReportGenerator] Debug info: Report type=$reportType, Rows=" . count($reportData['rows']));
            
            // Try to read the file to see what's in it
            $content = file_get_contents($pdfPath, false, null, 0, min(200, $pdfSize));
            if ($content !== false) {
                error_log("[ReportGenerator] First 200 bytes (hex): " . bin2hex($content));
                error_log("[ReportGenerator] First 200 bytes (text): " . substr($content, 0, 200));
            }
        }
        
        error_log("[ReportGenerator] ========== generatePDFWithFPDI END (SUCCESS) ==========");
        return $pdfPath;
    }
    
    /**
     * Build Static Header - appears on EVERY page
     * Logo (top-left) + "STI COLLEGE LUCENA" (top-right)
     */
    private function buildStaticHeader($pdf) {
        $logoY = 5; // Top margin
        
        // Logo (top-left) - ~15mm width as per layout
        $logoPath = __DIR__ . '/../../assets/images/STI_Lucena_Logo.jpg';
        if (file_exists($logoPath)) {
            try {
                if (method_exists($pdf, 'Image')) {
                    $pdf->Image($logoPath, 10, $logoY, 15, 0, 'JPG');
                }
            } catch (Exception $e) {
                error_log("[ReportGenerator] Warning: Could not add logo: " . $e->getMessage());
            }
        }
        
        // "STI COLLEGE LUCENA" text (top-right) - 10pt Bold
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(165, $logoY + 2);
        $pdf->Cell(30, 5, 'STI COLLEGE LUCENA', 0, 0, 'R');
        
        // Reset Y position after static header
        $pdf->SetY($logoY + 12); // Leave some space after header
    }
    
    /**
     * Build Body Header - appears ONLY on page 1
     * Report Title, Period, Info Boxes
     */
    private function buildBodyHeader($pdf, $reportType, $params) {
        error_log("[ReportGenerator] buildBodyHeader: Starting body header build");
        
        $currentY = $pdf->GetY();
        
        // Report Title (centered) - 14-16pt Bold
        $pdf->SetFont('Arial', 'B', 16);
        $reportTitle = $this->getReportTitle($reportType);
        $pdf->SetXY(15, $currentY);
        $pdf->Cell(180, 8, $reportTitle, 0, 0, 'C');
        
        // School Year | Semester Name (centered) - 10-12pt Regular
        $currentY += 12;
        $pdf->SetFont('Arial', '', 10);
        $periodText = $params['school_year'] . ' | ' . $params['semester_name'];
        $pdf->SetXY(15, $currentY);
        $pdf->Cell(180, 5, $periodText, 0, 0, 'C');
        
        // Two-column info boxes - ~85mm width each, ~20mm height
        $currentY += 10;
        $boxWidth = 85;
        $boxHeight = 20;
        $boxSpacing = 10;
        
        // Left box: GeneratedBy and AccountDesignation
        $leftBoxX = 15;
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Rect($leftBoxX, $currentY, $boxWidth, $boxHeight, 'DF');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($leftBoxX + 5, $currentY + 2);
        $pdf->Cell($boxWidth - 10, 4, 'Generated by:', 0, 0, 'L');
        
        if (!empty($params['user_id'])) {
            $stmt = $this->pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE user_id = ?");
            $stmt->execute([$params['user_id']]);
            $userName = $stmt->fetchColumn();
            if ($userName) {
                $pdf->SetXY($leftBoxX + 5, $currentY + 6);
                $pdf->Cell($boxWidth - 10, 4, $userName, 0, 0, 'L');
            }
        }
        
        $pdf->SetXY($leftBoxX + 5, $currentY + 11);
        $pdf->Cell($boxWidth - 10, 4, 'Designation:', 0, 0, 'L');
        
        if (!empty($params['user_id'])) {
            $stmt = $this->pdo->prepare("SELECT r.role_name FROM users u JOIN user_roles ur ON u.user_id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ?");
            $stmt->execute([$params['user_id']]);
            $accountDesignation = $stmt->fetchColumn();
            if ($accountDesignation) {
                $pdf->SetXY($leftBoxX + 5, $currentY + 15);
                $pdf->Cell($boxWidth - 10, 4, $accountDesignation, 0, 0, 'L');
            }
        }
        
        // Right box: Sector and Department Scope
        $rightBoxX = $leftBoxX + $boxWidth + $boxSpacing;
        $pdf->Rect($rightBoxX, $currentY, $boxWidth, $boxHeight, 'DF');
        
        $pdf->SetXY($rightBoxX + 5, $currentY + 2);
        $pdf->Cell($boxWidth - 10, 4, 'Sector:', 0, 0, 'L');
        if (!empty($params['sector'])) {
            $pdf->SetXY($rightBoxX + 5, $currentY + 6);
            $pdf->Cell($boxWidth - 10, 4, $params['sector'], 0, 0, 'L');
        }
        
        $pdf->SetXY($rightBoxX + 5, $currentY + 11);
        $pdf->Cell($boxWidth - 10, 4, 'Department Scope:', 0, 0, 'L');
        if (!empty($params['department_id'])) {
            $stmt = $this->pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $stmt->execute([$params['department_id']]);
            $deptName = $stmt->fetchColumn();
            if ($deptName) {
                $pdf->SetXY($rightBoxX + 5, $currentY + 15);
                $pdf->Cell($boxWidth - 10, 4, $deptName, 0, 0, 'L');
            }
        }
        
        // Set Y position for next section
        $pdf->SetY($currentY + $boxHeight + 10);
        
        error_log("[ReportGenerator] buildBodyHeader: Body header built, Y position now at " . $pdf->GetY());
    }
    
    /**
     * Build PDF Summary section - appears ONLY on page 1
     */
    private function buildPDFSummary($pdf, $summary, $reportType = null) {
        error_log("[ReportGenerator] buildPDFSummary: Starting summary build");
        
        $currentY = $pdf->GetY();
        
        // Section title - 10pt Bold (same font size for both report types)
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(15, $currentY);
        
        // Use appropriate title based on report type
        $isApplicantStatus = in_array($reportType, ['student_applicant_status', 'faculty_applicant_status']);
        $sectionTitle = $isApplicantStatus ? 'SUMMARY OF APPLICANT STATUS' : 'SUMMARY OF CLEARANCE FORMS';
        $pdf->Cell(180, 6, $sectionTitle, 0, 1, 'L');
        
        $currentY += 8;
        $pdf->SetFont('Arial', '', 10); // Same font size (10pt) as Progress Report
        
        // Summary items - different metrics for applicant status vs progress
        if ($isApplicantStatus) {
            $items = [
                'Total Forms:' => $summary['TotalForms'] ?? 0,
                'Total Pending:' => $summary['TotalPending'] ?? 0,
                'Total Approved:' => $summary['TotalApproved'] ?? 0,
                'Total Rejected:' => $summary['TotalRejected'] ?? 0,
            ];
        } else {
        $items = [
            'Total Forms:' => $summary['TotalForms'] ?? 0,
            'Total Unapplied:' => $summary['TotalUnapplied'] ?? 0,
            'Total In Progress:' => $summary['TotalInProgress'] ?? 0,
            'Total Completed:' => $summary['TotalCompleted'] ?? 0,
        ];
        }
        
        foreach ($items as $label => $value) {
            $pdf->SetXY(15, $currentY);
            $pdf->Cell(50, 5, $label, 0, 0, 'L');
            $pdf->SetXY(65, $currentY);
            $pdf->Cell(30, 5, (string)$value, 0, 1, 'L');
            $currentY += 6;
        }
        
        // Horizontal line separator
        $pdf->Line(15, $currentY + 2, 195, $currentY + 2);
        $pdf->SetY($currentY + 8);
        
        error_log("[ReportGenerator] buildPDFSummary: Summary built, Y position now at " . $pdf->GetY());
    }
    
    /**
     * Build PDF Table section
     */
    private function buildPDFTable($pdf, $reportType, $rows, $params) {
        error_log("[ReportGenerator] buildPDFTable: Starting table build for $reportType");
        error_log("[ReportGenerator] buildPDFTable: Rows count = " . count($rows));
        
        $currentY = $pdf->GetY();
        $startY = $currentY;
        
        // Table title
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(15, $currentY);
        $pdf->Cell(180, 6, 'DETAILED LIST', 0, 1, 'L');
        $currentY += 8;
        
        if (empty($rows)) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetXY(15, $currentY);
            $pdf->Cell(180, 5, 'No data available', 0, 1, 'C');
            return;
        }
        
        // Get table configuration based on report type
        $tableConfig = $this->getTableConfig($reportType);
        $columns = $tableConfig['columns'];
        $rowHeight = $tableConfig['row_height'];
        
        // Table header - 9pt Bold
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(232, 244, 248); // Light blue/gray
        $pdf->SetTextColor(0, 0, 0);
        
        $headerY = $currentY;
        $headerHeight = 8;
        
        // Draw header row
        $xPos = 15;
        foreach ($columns as $colKey => $col) {
            $pdf->Rect($xPos, $headerY, $col['width'], $headerHeight, 'DF');
            $pdf->SetXY($xPos + 2, $headerY + 2);
            $pdf->Cell($col['width'] - 4, 4, $col['header'], 0, 0, 'L');
            $xPos += $col['width'];
        }
        
        $currentY = $headerY + $headerHeight;
        
        // Table rows - 8pt Regular (7pt for Department column to prevent truncation)
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        foreach ($rows as $rowIdx => $row) {
            // Check if we need a new page (leave 30mm for footer)
            if ($currentY > 260) {
                // Footer will be automatically called by FPDF's AddPage() through Footer() override
                // So we don't need to manually call buildPDFFooter here
                
                $pdf->AddPage(); // This will automatically trigger Footer() which calls buildPDFFooter()
                
                // Build static header on new page
                $this->buildStaticHeader($pdf);
                
                // Start table header again on new page (after static header)
                $currentY = $pdf->GetY() + 5; // Some spacing after static header
                
                // Redraw table header on new page
                $xPos = 15;
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(232, 244, 248);
                foreach ($columns as $colKey => $col) {
                    $pdf->Rect($xPos, $currentY, $col['width'], $headerHeight, 'DF');
                    $pdf->SetXY($xPos + 2, $currentY + 2);
                    $pdf->Cell($col['width'] - 4, 4, $col['header'], 0, 0, 'L');
                    $xPos += $col['width'];
                }
                $currentY += $headerHeight;
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetFillColor(255, 255, 255);
            }
            
            // Draw row
            $rowY = $currentY;
            $xPos = 15;
            
            // First, calculate if department needs wrapping and determine row height
            $deptValue = $this->getTableCellValue($row, 'department', $reportType);
            // Student reports (progress and applicant status) use program_name
            $isStudentReport = in_array($reportType, ['student_progress', 'student_applicant_status']);
            $deptNeedsWrapping = ($isStudentReport ? 
                (isset($row['program_name']) && mb_strlen($row['program_name'] ?? '') > floor($columns['department']['width'] / 1.5)) :
                (mb_strlen($deptValue) > floor($columns['department']['width'] / 1.5)));
            
            // Calculate actual row height needed (accounting for department wrapping if needed)
            $actualRowHeight = $rowHeight;
            if ($deptNeedsWrapping && isset($columns['department'])) {
                // Estimate lines needed for department text
                $deptText = $deptValue;
                $charsPerLine = floor($columns['department']['width'] / 1.8); // 7pt font
                $estimatedLines = max(1, ceil(mb_strlen($deptText) / $charsPerLine));
                $actualRowHeight = max($rowHeight, $estimatedLines * 4 + 2);
            }
            
            // Draw all cell borders first
            $tempX = 15;
            foreach ($columns as $colKey => $col) {
                $pdf->Rect($tempX, $rowY, $col['width'], $actualRowHeight, 'D');
                $tempX += $col['width'];
            }
            
            // Now draw cell content
            foreach ($columns as $colKey => $col) {
                // Get cell value based on column key
                $value = $this->getTableCellValue($row, $colKey, $reportType);
                
                // Handle Full Name column specially (multi-line)
                if ($colKey === 'full_name') {
                    $pdf->SetXY($xPos + 2, $rowY + 1);
                    
                    // Format: "LastName, FirstName" on first line, "MiddleName" on second if exists
                    $lastName = $row['last_name'] ?? '';
                    $firstName = $row['first_name'] ?? '';
                    $middleName = $row['middle_name'] ?? '';
                    
                    $line1 = $lastName . ($lastName && $firstName ? ', ' : '') . $firstName;
                    $line2 = trim($middleName);
                    
                    if ($line1) {
                        $pdf->Cell($col['width'] - 4, 3, $line1, 0, 0, 'L');
                    }
                    
                    if ($line2) {
                        $pdf->SetXY($xPos + 2, $rowY + 4);
                        $pdf->Cell($col['width'] - 4, 3, $line2, 0, 0, 'L');
                    }
                } else {
                    // Regular cell - Department uses 7pt font, others use 8pt
                    if ($colKey === 'department') {
                        $pdf->SetFont('Arial', '', 7); // 7pt font to fit full department names
                        $cellY = $rowY + 1;
                        $lineHeight = 3;
                    } else {
                        $pdf->SetFont('Arial', '', 8);
                        $cellY = $rowY + 2;
                        $lineHeight = 4;
                    }
                    
                    $pdf->SetXY($xPos + 2, $cellY);
                    
                    // For Department column with wrapping, use MultiCell with fixed height
                    if ($colKey === 'department' && $deptNeedsWrapping) {
                        // Save current Y position
                        $startY = $pdf->GetY();
                        // Use MultiCell but limit to actualRowHeight
                        $maxHeight = $actualRowHeight - 2;
                        $pdf->MultiCell($col['width'] - 4, $lineHeight, $value, 0, 'L', false);
                        // Reset Y to start position so other columns align
                        $pdf->SetY($startY);
                    } else {
                        // Regular single-line cell
                        $pdf->Cell($col['width'] - 4, $lineHeight, $value, 0, 0, 'L');
                    }
                    
                    // Reset font for next cell
                    $pdf->SetFont('Arial', '', 8);
                }
                
                $xPos += $col['width'];
            }
            
            // Advance Y position by actual row height used
            $currentY += $actualRowHeight;
        }
        
        // Footer will be automatically added when Output() is called
        // FPDF calls Footer() on each page automatically
        error_log("[ReportGenerator] buildPDFTable: Table built, final Y position: " . $pdf->GetY());
    }
    
    /**
     * Build Static Footer - appears on EVERY page
     * Date Generated (left) + Processed by text + Page number (right)
     * This method always writes at a fixed Y position (285mm) regardless of current Y
     * Prevents duplicate footers on the same page
     */
    private function buildPDFFooter($pdf) {
        // Get current page number
        $pageNum = $pdf->PageNo();
        
        // Check if footer already drawn on this page
        if (in_array($pageNum, $this->footerPages)) {
            error_log("[ReportGenerator] buildPDFFooter: Footer already drawn on page $pageNum, skipping");
            return;
        }
        
        // Mark this page as having footer drawn
        $this->footerPages[] = $pageNum;
        
        // Footer Y position (bottom of page, ~285mm on A4) - ALWAYS use fixed absolute position
        // A4 page height is 297mm, so 285mm leaves ~12mm margin at bottom
        // Use absolute coordinates - don't use relative positioning like SetY(-15)
        $footerY = 285;
        
        // Get total pages - try using getTotalPages() method if available (from our custom class)
        // Otherwise, use reflection to access FPDF's internal page count
        $totalPages = $pageNum; // Default to current page
        
        if (method_exists($pdf, 'getTotalPages')) {
            $totalPages = $pdf->getTotalPages();
        } else {
            // Try to get total pages using reflection
            try {
                $reflection = new ReflectionClass($pdf);
                if ($reflection->hasProperty('page')) {
                    $pageProp = $reflection->getProperty('page');
                    $pageProp->setAccessible(true);
                    $totalPages = $pageProp->getValue($pdf);
                }
            } catch (Exception $e) {
                // Fallback: use current page number
                error_log("[ReportGenerator] Could not get total pages, using current: " . $e->getMessage());
            }
        }
        
        // Three-column footer layout:
        // Left: Date Generated (60mm)
        // Center: Page X of X (60mm, centered)
        // Right: Processed by goSTI for STI College Lucena (60mm)
        
        $pdf->SetFont('Arial', '', 8);
        
        // Left column: Date Generated
        $pdf->SetXY(15, $footerY);
        $pdf->Cell(60, 4, 'Date Generated:', 0, 0, 'L');
        $pdf->SetXY(15, $footerY + 4);
        // Use Asia/Manila timezone for Date Generated
        $dateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $dateGenerated = $dateTime->format('Y-m-d H:i:s');
        $pdf->Cell(60, 4, $dateGenerated, 0, 0, 'L');
        
        // Center column: Page X of X
        $pageText = 'Page ' . $pageNum . ' of ' . $totalPages;
        $pdf->SetXY(75, $footerY + 2); // Center vertically by offsetting +2mm
        $pdf->Cell(60, 4, $pageText, 0, 0, 'C');
        
        // Right column: Processed by goSTI for STI College Lucena
        $pdf->SetXY(135, $footerY);
        $pdf->Cell(60, 4, 'Processed by goSTI for', 0, 0, 'R');
        $pdf->SetXY(135, $footerY + 4);
        $pdf->Cell(60, 4, 'STI College Lucena', 0, 0, 'R');
        
        error_log("[ReportGenerator] buildPDFFooter: Footer built on page $pageNum of $totalPages at fixed Y=$footerY");
    }
    
    /**
     * Get table configuration based on report type
     */
    private function getTableConfig($reportType) {
        // Total available width: 180mm (210mm page - 15mm margins each side)
        // Font sizes: Table header 9pt, Table data 8pt, Department column 7pt to prevent truncation
        // All report types use the same font sizes to maintain consistency
        if ($reportType === 'student_progress') {
            return [
                'row_height' => 10, // 8-10mm to accommodate Full Name on 2-3 lines
                'columns' => [
                    'student_no' => ['header' => 'Student No', 'width' => 25],
                    'full_name' => ['header' => 'Full Name', 'width' => 45],
                    'department' => ['header' => 'Department', 'width' => 35], // Increased from 30 to 35mm
                    'year_level' => ['header' => 'Year Level', 'width' => 25],
                    'section' => ['header' => 'Section', 'width' => 20],
                    'status' => ['header' => 'Status', 'width' => 25],
                ]
            ];
        } elseif ($reportType === 'faculty_progress') {
            return [
                'row_height' => 10,
                'columns' => [
                    'employee_no' => ['header' => 'Employee No', 'width' => 28],
                    'full_name' => ['header' => 'Full Name', 'width' => 50],
                    'employment_status' => ['header' => 'Employment Status', 'width' => 35], // Reduced from 45
                    'department' => ['header' => 'Department', 'width' => 37], // Increased from 32 to 37mm
                    'status' => ['header' => 'Status', 'width' => 25],
                ]
            ];
        } elseif ($reportType === 'student_applicant_status') {
            return [
                'row_height' => 10, // Same row height as progress report
                'columns' => [
                    'student_no' => ['header' => 'Student No', 'width' => 22], // Reduced from 25
                    'full_name' => ['header' => 'Full Name', 'width' => 40], // Reduced from 45
                    'department' => ['header' => 'Department', 'width' => 30], // Reduced from 35, 7pt font
                    'year_level' => ['header' => 'Year Level', 'width' => 22], // Reduced from 25
                    'section' => ['header' => 'Section', 'width' => 18], // Reduced from 20
                    'action_status' => ['header' => 'Action Status', 'width' => 26], // Reduced from 30
                    'date_signed' => ['header' => 'Date Signed', 'width' => 22], // Reduced from 30
                ]
                // Total: 180mm (fits exactly)
            ];
        } elseif ($reportType === 'faculty_applicant_status') {
            return [
                'row_height' => 10, // Same row height as progress report
                'columns' => [
                    'employee_no' => ['header' => 'Employee No', 'width' => 22], // Reduced from 25
                    'full_name' => ['header' => 'Full Name', 'width' => 42], // Reduced from 50
                    'employment_status' => ['header' => 'Employment Status', 'width' => 30], // Reduced from 35
                    'department' => ['header' => 'Department', 'width' => 32], // Reduced from 37, 7pt font
                    'action_status' => ['header' => 'Action Status', 'width' => 26], // Reduced from 30
                    'date_signed' => ['header' => 'Date Signed', 'width' => 28], // Reduced from 30
                ]
                // Total: 180mm (fits exactly)
            ];
        }
        
        // Default (should not reach here)
        return ['row_height' => 8, 'columns' => []];
    }
    
    /**
     * Get cell value for table column
     */
    private function getTableCellValue($row, $colKey, $reportType) {
        switch ($colKey) {
            case 'student_no':
                return $row['student_no'] ?? '';
                
            case 'employee_no':
                return $row['employee_number'] ?? '';
                
            case 'full_name':
                // Full name is handled separately in buildPDFTable
                return '';
                
            case 'department':
                if ($reportType === 'student_progress' || $reportType === 'student_applicant_status') {
                    return $row['program_name'] ?? '';
                } else {
                    return $row['department'] ?? ($row['department_name'] ?? '');
                }
                
            case 'year_level':
                $yearLevel = $row['year_level'] ?? '';
                // Format: "1st Year", "2nd Year", etc. (already formatted in DB as enum)
                return $yearLevel;
                
            case 'section':
                return $row['section'] ?? '';
                
            case 'employment_status':
                return $row['employment_status'] ?? '';
                
            case 'status':
                $status = $row['form_status'] ?? '';
                // Format status
                if (strtolower($status) === 'unapplied') {
                    return 'Unapplied';
                } elseif (strtolower($status) === 'in-progress') {
                    return 'In Progress';
                } elseif (strtolower($status) === 'complete') {
                    return 'Complete';
                }
                return ucfirst($status);
                
            case 'action_status':
                $action = $row['action_status'] ?? 'Pending';
                // Format action status (Pending, Approved, Rejected)
                return ucfirst($action);
                
            case 'date_signed':
                $dateSigned = $row['date_signed'] ?? null;
                // Display: "Pending" if NULL/empty, otherwise YYYY-MM-DD format
                if (empty($dateSigned)) {
                    return 'Pending';
                }
                // Format date as YYYY-MM-DD
                if (is_string($dateSigned)) {
                    // Try to parse and reformat if needed
                    $timestamp = strtotime($dateSigned);
                    if ($timestamp !== false) {
                        return date('Y-m-d', $timestamp);
                    }
                }
                return (string)$dateSigned;
                
            default:
                return '';
        }
    }
    
    /**
     * Legacy method - kept for reference but not used for progress reports
     */
    private function getPDFCoordinates($reportType) {
        // This method is no longer used for progress reports
        // Kept for reference only
        return [];
    }
    
    /**
     * Legacy overlay method - not used anymore
     */
    private function overlayHeaderInfo($pdf, $reportType, $params, $coords) {
        // Legacy method - kept for backward compatibility
        // No longer used for progress reports
    }
    
    /**
     * Overlay summary information on PDF
     * Uses white-filled cells to cover placeholder text, then overlays new text
     */
    private function overlaySummaryInfo($pdf, $summary, $coords) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $summCoords = $coords['summary'];
        
        $coverWidth = 50;
        $coverHeight = 6;
        
        if (isset($summCoords['total_forms'])) {
            $c = $summCoords['total_forms'];
            $text = 'Total Forms: ' . ($summary['TotalForms'] ?? 0);
            $pdf->Rect($c['x'] - 5, $c['y'] - 2, $coverWidth, $coverHeight, 'F');
            $pdf->SetXY($c['x'], $c['y']);
            $pdf->Cell(0, 5, $text, 0, 0, 'L');
        }
        
        if (isset($summCoords['total_unapplied'])) {
            $c = $summCoords['total_unapplied'];
            $text = 'Total Unapplied: ' . ($summary['TotalUnapplied'] ?? 0);
            $pdf->Rect($c['x'] - 5, $c['y'] - 2, $coverWidth, $coverHeight, 'F');
            $pdf->SetXY($c['x'], $c['y']);
            $pdf->Cell(0, 5, $text, 0, 0, 'L');
        }
        
        if (isset($summCoords['total_in_progress'])) {
            $c = $summCoords['total_in_progress'];
            $text = 'Total In-Progress: ' . ($summary['TotalInProgress'] ?? 0);
            $pdf->Rect($c['x'] - 5, $c['y'] - 2, $coverWidth, $coverHeight, 'F');
            $pdf->SetXY($c['x'], $c['y']);
            $pdf->Cell(0, 5, $text, 0, 0, 'L');
        }
        
        if (isset($summCoords['total_completed'])) {
            $c = $summCoords['total_completed'];
            $text = 'Total Complete: ' . ($summary['TotalCompleted'] ?? 0);
            $pdf->Rect($c['x'] - 5, $c['y'] - 2, $coverWidth, $coverHeight, 'F');
            $pdf->SetXY($c['x'], $c['y']);
            $pdf->Cell(0, 5, $text, 0, 0, 'L');
        }
    }
    
    /**
     * Overlay table rows on PDF
     * Uses white-filled cells to cover placeholder text, then overlays new text
     */
    private function overlayTableRows($pdf, $reportType, $rows, $coords) {
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $tableCoords = $coords['table'];
        $currentY = $tableCoords['start_y'];
        $rowHeight = $tableCoords['row_height'];
        $columns = $tableCoords['columns'];
        
        if (empty($rows)) {
            error_log("[ReportGenerator] No rows to overlay");
            // Cover the placeholder row if no data
            $coverWidth = 200;
            $pdf->Rect(10, $currentY - 2, $coverWidth, $rowHeight, 'F');
            return;
        }
        
        // Determine which fields to display based on report type
        $fieldMap = $this->getTableFieldMap($reportType);
        error_log("[ReportGenerator] Field map: " . json_encode($fieldMap));
        error_log("[ReportGenerator] First row keys: " . json_encode(array_keys($rows[0] ?? [])));
        
        // First, cover the placeholder row in the template
        $coverWidth = 200;
        $pdf->Rect(10, $currentY - 2, $coverWidth, $rowHeight, 'F');
        
        foreach ($rows as $idx => $row) {
            // Check if we need a new page
            if ($currentY > 270) { // Near bottom of A4 page (297mm - 27mm margin)
                // Import and add template page again for continuation
                $templatePath = $this->getTemplatePath($reportType);
                $pdf->AddPage();
                $tplId = $pdf->importPage(1);
                $pdf->useTemplate($tplId, 0, 0, null, null, true);
                $currentY = $tableCoords['start_y'];
                // Cover placeholder row on new page too
                $pdf->Rect(10, $currentY - 2, $coverWidth, $rowHeight, 'F');
            }
            
            // Output each column - cover placeholder area first, then write text
            foreach ($fieldMap as $colKey => $field) {
                if (!isset($columns[$colKey])) {
                    error_log("[ReportGenerator] Warning: Column '$colKey' not found in coordinates");
                    continue;
                }
                
                $col = $columns[$colKey];
                
                // Map field name to actual row key
                $value = '';
                if (isset($row[$field])) {
                    $value = $row[$field];
                } elseif ($field === 'status' && isset($row['form_status'])) {
                    $value = $row['form_status'];
                } elseif ($field === 'status' && isset($row['action_status'])) {
                    $value = $row['action_status'];
                } elseif ($field === 'student_id' && isset($row['student_no'])) {
                    $value = $row['student_no'];
                } elseif ($field === 'employee_no' && isset($row['employee_number'])) {
                    $value = $row['employee_number'];
                } elseif ($field === 'department' && isset($row['department_name'])) {
                    $value = $row['department_name'];
                }
                
                // Format status for display
                if (($field === 'status' || $colKey === 'status') && !empty($value)) {
                    $value = ucfirst(strtolower(str_replace('-', ' ', $value)));
                }
                
                // Truncate if too long
                $maxLen = floor($col['width'] / 2); // Rough estimate
                if (strlen($value) > $maxLen) {
                    $value = substr($value, 0, $maxLen - 3) . '...';
                }
                
                // Cover placeholder area with white rectangle
                $pdf->Rect($col['x'], $currentY - 2, $col['width'], $rowHeight, 'F');
                // Write the actual text
                $pdf->SetXY($col['x'], $currentY);
                $pdf->Cell($col['width'], $rowHeight, $value, 0, 0, 'L', false); // false = no fill (already filled)
            }
            
            $currentY += $rowHeight;
        }
        
        error_log("[ReportGenerator] Overlaid " . count($rows) . " table rows");
    }
    
    /**
     * Get field mapping for table columns based on report type
     * Returns array: ['column_key' => 'row_field_name']
     */
    private function getTableFieldMap($reportType) {
        if ($reportType === 'student_progress') {
            return [
                'student_id' => 'student_no',
                'first_name' => 'first_name',
                'middle_name' => 'middle_name',
                'last_name' => 'last_name',
                'department' => 'department_name',
                'status' => 'form_status',
            ];
        } elseif ($reportType === 'faculty_progress') {
            return [
                'student_id' => 'employee_number',  // Using student_id column key but employee_number field
                'first_name' => 'first_name',
                'middle_name' => 'middle_name',
                'last_name' => 'last_name',
                'department' => 'department_name',
                'status' => 'form_status',
            ];
        } elseif ($reportType === 'student_applicant_status') {
            return [
                'student_id' => 'student_no',
                'first_name' => 'first_name',
                'middle_name' => 'middle_name',
                'last_name' => 'last_name',
                'department' => 'program_name',  // Students have program_name
                'status' => 'action_status',
            ];
        } else { // faculty_applicant_status
            return [
                'student_id' => 'employee_number',  // Using student_id column key but employee_number field
                'first_name' => 'first_name',
                'middle_name' => 'middle_name',
                'last_name' => 'last_name',
                'department' => 'department',
                'status' => 'action_status',
            ];
        }
    }
    
    /**
     * Generate PDF from template using TCPDF (legacy method - kept for reference)
     */
    private function generatePDF($templatePath, $reportType, $reportData, $params) {
        error_log("[ReportGenerator] generatePDF: Loading template from $templatePath");
        
        // Load and fill template
        try {
            $templateProcessor = new TemplateProcessor($templatePath);
            error_log("[ReportGenerator] Template loaded successfully");
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR loading template: " . $e->getMessage());
            throw $e;
        }
        
        // Set header placeholders
        error_log("[ReportGenerator] Setting header placeholders");
        $this->setHeaderPlaceholders($templateProcessor, $reportType, $params);
        
        // Set summary placeholders
        error_log("[ReportGenerator] Setting summary placeholders");
        $this->setSummaryPlaceholders($templateProcessor, $reportData['summary']);
        
        // Clone and fill table rows
        $rowKey = $this->getRowKey($reportType);
        $rows = $reportData['rows'];
        $count = count($rows);
        
        error_log("[ReportGenerator] Row key for cloning: $rowKey");
        error_log("[ReportGenerator] Number of rows to clone: $count");
        
        if ($count > 0) {
            try {
                $templateProcessor->cloneRow($rowKey, $count);
                error_log("[ReportGenerator] Successfully cloned $count rows");
            } catch (Exception $e) {
                error_log("[ReportGenerator] ERROR cloning rows: " . $e->getMessage());
                error_log("[ReportGenerator] Row key '$rowKey' may not exist in template. Available placeholders might be different.");
                throw $e;
            }
            
            $i = 1;
            foreach ($rows as $row) {
                error_log("[ReportGenerator] Setting placeholders for row $i");
                $this->setRowPlaceholders($templateProcessor, $reportType, $row, $i);
                $i++;
            }
        } else {
            error_log("[ReportGenerator] WARNING: No rows to process, PDF will be empty");
        }
        
        // Save filled template
        $tempDocx = tempnam($this->tempDir, 'report_') . '.docx';
        error_log("[ReportGenerator] Saving filled template to: $tempDocx");
        
        try {
            $templateProcessor->saveAs($tempDocx);
            $docxSize = filesize($tempDocx);
            error_log("[ReportGenerator] Template saved, file size: $docxSize bytes");
            
            if ($docxSize < 5000) {
                error_log("[ReportGenerator] WARNING: Saved template file is very small ($docxSize bytes), data may not have been written");
            }
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR saving template: " . $e->getMessage());
            throw $e;
        }
        
        // Convert to PDF using TCPDF
        error_log("[ReportGenerator] Converting to PDF using TCPDF");
        return $this->convertWithTCPDF($tempDocx);
    }
    
    /**
     * Convert DOCX to PDF using PHPWord + TCPDF renderer
     * Primary method for PDF generation
     * Note: TCPDF may have limitations with complex formatting, but handles most content well
     */
    private function convertWithTCPDF($docxPath) {
        error_log("[ReportGenerator] convertWithTCPDF: Starting conversion");
        
        // Verify DOCX file exists and has content
        if (!file_exists($docxPath)) {
            error_log("[ReportGenerator] ERROR: DOCX file not found: $docxPath");
            throw new Exception('DOCX file not found');
        }
        
        $docxSize = filesize($docxPath);
        error_log("[ReportGenerator] DOCX file size: $docxSize bytes");
        
        if ($docxSize === 0) {
            error_log("[ReportGenerator] ERROR: DOCX file is empty");
            throw new Exception('DOCX file is empty - template processing may have failed');
        }
        
        // Configure TCPDF as PDF renderer for PHPWord
        $tcpdfPath = realpath(__DIR__ . '/../vendor/tecnickcom/tcpdf');
        if (!$tcpdfPath || !file_exists($tcpdfPath . '/tcpdf.php')) {
            error_log("[ReportGenerator] ERROR: TCPDF library not found at $tcpdfPath");
            throw new Exception('TCPDF library not found. Please ensure tecnickcom/tcpdf is installed via composer.');
        }
        
        error_log("[ReportGenerator] TCPDF path: $tcpdfPath");
        
        try {
            Settings::setPdfRendererName('TCPDF');
            Settings::setPdfRendererPath($tcpdfPath);
            error_log("[ReportGenerator] TCPDF renderer configured");
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR configuring TCPDF: " . $e->getMessage());
            throw $e;
        }
        
        // Load the filled DOCX
        error_log("[ReportGenerator] Loading DOCX file: $docxPath");
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
            error_log("[ReportGenerator] DOCX loaded successfully");
            
            // Log document info for debugging
            $sections = $phpWord->getSections();
            error_log("[ReportGenerator] Document sections: " . count($sections));
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR loading DOCX: " . $e->getMessage());
            error_log("[ReportGenerator] Error details: " . $e->getFile() . ":" . $e->getLine());
            throw $e;
        }
        
        // Generate PDF
        $pdfPath = tempnam($this->tempDir, 'report_') . '.pdf';
        error_log("[ReportGenerator] Generating PDF to: $pdfPath");
        
        try {
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
            
            // Set PDF settings if needed
            if (method_exists($pdfWriter, 'setFont')) {
                error_log("[ReportGenerator] PDF writer settings configured");
            }
            
            $pdfWriter->save($pdfPath);
            $pdfSize = filesize($pdfPath);
            error_log("[ReportGenerator] PDF saved successfully, file size: $pdfSize bytes");
            
            if ($pdfSize < 1000) {
                error_log("[ReportGenerator] WARNING: PDF file is very small ($pdfSize bytes), content may be missing");
            }
        } catch (Exception $e) {
            error_log("[ReportGenerator] ERROR generating PDF: " . $e->getMessage());
            error_log("[ReportGenerator] Error stack: " . $e->getTraceAsString());
            throw $e;
        }
        
        // Clean up DOCX
        @unlink($docxPath);
        error_log("[ReportGenerator] Temporary DOCX file cleaned up");
        
        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            error_log("[ReportGenerator] ERROR: PDF file is empty or missing");
            throw new Exception('PDF generation failed. Output file is empty or missing.');
        }
        
        return $pdfPath;
    }
    
    /**
     * Generate Excel file (simplified HTML table approach for now)
     */
    private function generateExcel($reportType, $reportData, $params, $ext) {
        error_log("[ReportGenerator] generateExcel called: type=$reportType, rows=" . count($reportData['rows']));
        
        $rows = $reportData['rows'];
        $headers = $this->getExcelHeaders($reportType);
        
        error_log("[ReportGenerator] Excel headers: " . json_encode($headers));
        
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h3>' . htmlspecialchars($this->getReportTitle($reportType)) . '</h3>';
        $html .= '<p>Period: ' . htmlspecialchars($params['school_year'] . ' ' . $params['semester_name']) . '</p>';
        if (!empty($params['sector'])) {
            $html .= '<p>Sector: ' . htmlspecialchars($params['sector']) . '</p>';
        }
        if (!empty($params['department_id'])) {
            $deptStmt = $this->pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $deptStmt->execute([$params['department_id']]);
            $deptName = $deptStmt->fetchColumn();
            if ($deptName) {
                $html .= '<p>Department: ' . htmlspecialchars($deptName) . '</p>';
            }
        }
        $html .= '<table border="1" cellspacing="0" cellpadding="4"><tr>';
        
        // Output header row
        foreach ($headers as $key => $label) {
            $html .= '<th>' . htmlspecialchars($label) . '</th>';
        }
        $html .= '</tr>';
        
        // Output data rows
        if (count($rows) === 0) {
            $html .= '<tr><td colspan="' . count($headers) . '" style="text-align:center;">No data available</td></tr>';
        } else {
            foreach ($rows as $rowIndex => $row) {
                $html .= '<tr>';
                foreach ($headers as $key => $label) {
                    $value = $row[$key] ?? '';
                    // Format form_status for display
                    if ($key === 'form_status') {
                        $value = match(strtolower($value)) {
                            'unapplied' => 'Unapplied',
                            'in-progress' => 'In Progress',
                            'complete' => 'Completed',
                            default => ucfirst($value)
                        };
                    }
                    $html .= '<td>' . htmlspecialchars((string)$value) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</table></body></html>';
        
        $filePath = tempnam($this->tempDir, 'report_') . '.' . $ext;
        file_put_contents($filePath, $html);
        
        error_log("[ReportGenerator] Excel file created: $filePath, size=" . filesize($filePath));
        
        return $filePath;
    }
    
    /**
     * Set header placeholders in template
     * Based on actual placeholders found in templates
     */
    private function setHeaderPlaceholders($processor, $reportType, $params) {
        error_log("[ReportGenerator] setHeaderPlaceholders: reportType=$reportType");
        
        // Common headers (found in Student Progress template)
        if ($reportType === 'student_progress') {
            $processor->setValue('ReportTitle', $this->getReportTitle($reportType));
            $processor->setValue('Sector', $params['sector']);
            $processor->setValue('SchoolYear', $params['school_year']);
            $processor->setValue('Semester', $params['semester_name']);
            $processor->setValue('GeneratedBy', $params['role'] ?? 'System');
            
            error_log("[ReportGenerator] Set header values: ReportTitle=" . $this->getReportTitle($reportType) . ", Sector=" . $params['sector']);
            
            if (!empty($params['department_id'])) {
                $deptStmt = $this->pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
                $deptStmt->execute([$params['department_id']]);
                $dept = $deptStmt->fetchColumn();
                $processor->setValue('DepartmentName', $dept ?: '');
                error_log("[ReportGenerator] Set DepartmentName: " . ($dept ?: 'N/A'));
            }
        }
        
        // Faculty Progress template
        if ($reportType === 'faculty_progress') {
            $processor->setValue('ReportTitle', $this->getReportTitle($reportType));
            $processor->setValue('Sector', $params['sector']);
            $processor->setValue('SchoolYear', $params['school_year']);
            $processor->setValue('Semester', $params['semester_name']);
            $processor->setValue('GeneratedBy', $params['role'] ?? 'System');
            
            // Get current user info for AccountDesignation if needed
            $userId = $params['user_id'] ?? null;
            if ($userId) {
                $stmt = $this->pdo->prepare("SELECT r.role_name FROM users u JOIN user_roles ur ON u.user_id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ?");
                $stmt->execute([$userId]);
                $roleName = $stmt->fetchColumn();
                $processor->setValue('AccountDesignation', $roleName ?: 'System');
            }
            
            if (!empty($params['department_id'])) {
                $deptStmt = $this->pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
                $deptStmt->execute([$params['department_id']]);
                $dept = $deptStmt->fetchColumn();
                $processor->setValue('DepartmentName', $dept ?: '');
            }
        }
    }
    
    /**
     * Set summary placeholders
     * Based on actual placeholders found: TotalForms, TotalUnapplied, TotalInProgress, TotalCompleted
     */
    private function setSummaryPlaceholders($processor, $summary) {
        error_log("[ReportGenerator] setSummaryPlaceholders: " . json_encode($summary));
        
        // Map summary keys to actual template placeholder names
        $placeholderMap = [
            'TotalForms' => 'TotalForms',
            'TotalUnapplied' => 'TotalUnapplied',
            'TotalApplied' => 'TotalApplied',  // May not exist in template
            'TotalInProgress' => 'TotalInProgress',
            'TotalCompleted' => 'TotalCompleted'
        ];
        
        foreach ($summary as $key => $value) {
            $placeholder = $placeholderMap[$key] ?? $key;
            $processor->setValue($placeholder, (string)$value);
            error_log("[ReportGenerator] Set summary placeholder: $placeholder = $value");
        }
    }
    
    /**
     * Set row placeholders (cloned row)
     * Maps database columns to ACTUAL template placeholder names found in .docx files
     */
    private function setRowPlaceholders($processor, $reportType, $row, $index) {
        // Helper to format status values
        $formatStatus = function($status) {
            return match(strtolower(trim($status ?? 'unapplied'))) {
                'unapplied' => 'Unapplied',
                'in-progress' => 'In Progress',
                'complete' => 'Completed',
                default => ucfirst($status)
            };
        };
        
        if ($reportType === 'student_progress') {
            // Map DB columns to actual template placeholders found
            $studentNo = htmlspecialchars($row['student_no'] ?? '', ENT_COMPAT, 'UTF-8');
            $firstName = htmlspecialchars($row['first_name'] ?? '', ENT_COMPAT, 'UTF-8');
            $lastName = htmlspecialchars($row['last_name'] ?? '', ENT_COMPAT, 'UTF-8');
            
            $processor->setValue("StudentNo#{$index}", $studentNo);
            $processor->setValue("FirstName#{$index}", $firstName);
            $processor->setValue("MiddleName#{$index}", htmlspecialchars($row['middle_name'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("LastName#{$index}", $lastName);
            $processor->setValue("Program#{$index}", htmlspecialchars($row['program_name'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("YearLevel#{$index}", htmlspecialchars($row['year_level'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("Section#{$index}", htmlspecialchars($row['section'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("FormStatus#{$index}", htmlspecialchars($formatStatus($row['form_status']), ENT_COMPAT, 'UTF-8'));
            
            error_log("[ReportGenerator] Row $index: $studentNo - $firstName $lastName");
        } elseif ($reportType === 'faculty_progress') {
            // Map DB columns to template placeholders
            // Note: Template may use combined name format "${LastName}, ${FirstName} ${MiddleName}" in one cell
            $processor->setValue("EmployeeNo#{$index}", htmlspecialchars($row['employee_number'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("FirstName#{$index}", htmlspecialchars($row['first_name'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("MiddleName#{$index}", htmlspecialchars($row['middle_name'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("LastName#{$index}", htmlspecialchars($row['last_name'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("Department#{$index}", htmlspecialchars($row['department'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("EmploymentStatus#{$index}", htmlspecialchars($row['employment_status'] ?? '', ENT_COMPAT, 'UTF-8'));
            $processor->setValue("FormStatus#{$index}", htmlspecialchars($formatStatus($row['form_status']), ENT_COMPAT, 'UTF-8'));
        }
        // Note: Applicant Status templates not found yet - will need placeholder list
    }
    
    /**
     * Get row key for cloning (first placeholder in table row)
     * Based on actual templates: Student Progress uses $StudentNo, Faculty Progress may need different key
     */
    private function getRowKey($reportType) {
        // Row key for cloneRow() - MUST be the FIRST placeholder in the table row
        $keys = [
            'student_progress' => 'StudentNo',      // Confirmed from template
            'faculty_progress' => 'EmployeeNo',     // Should be first in updated template
            'student_applicant_status' => 'StudentNo',
            'faculty_applicant_status' => 'EmployeeNo'
        ];
        return $keys[$reportType] ?? 'StudentNo';
    }
    
    /**
     * Map row data to template field names
     */
    private function mapRowData($reportType, $row) {
        // This would map DB column names to template placeholder names
        // For now, we'll set them directly in setRowPlaceholders
        return $row;
    }
    
    /**
     * Get report title
     */
    private function getReportTitle($reportType) {
        $titles = [
            'student_progress' => 'Student Clearance Form Progress Report',
            'faculty_progress' => 'Faculty Clearance Form Progress Report',
            'student_applicant_status' => 'Student Clearance Applicant Status Report',
            'faculty_applicant_status' => 'Faculty Clearance Applicant Status Report'
        ];
        return $titles[$reportType] ?? 'Report';
    }
    
    /**
     * Get Excel headers
     */
    private function getExcelHeaders($reportType) {
        $headers = [
            'student_progress' => ['student_no' => 'Student No', 'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'program_name' => 'Program', 'year_level' => 'Year Level', 'section' => 'Section', 'form_status' => 'Status'],
            'faculty_progress' => ['employee_number' => 'Employee No', 'first_name' => 'First Name', 'last_name' => 'Last Name', 'department' => 'Department', 'employment_status' => 'Employment Status', 'form_status' => 'Status'],
            'student_applicant_status' => ['student_no' => 'Student No', 'first_name' => 'First Name', 'last_name' => 'Last Name', 'program_name' => 'Program', 'year_level' => 'Year Level', 'designation_name' => 'Designation', 'signatory_name' => 'Signatory', 'action_status' => 'Action', 'date_signed' => 'Date Signed'],
            'faculty_applicant_status' => ['employee_number' => 'Employee No', 'first_name' => 'First Name', 'last_name' => 'Last Name', 'department' => 'Department', 'employment_status' => 'Employment Status', 'designation_name' => 'Designation', 'signatory_name' => 'Signatory', 'action_status' => 'Action', 'date_signed' => 'Date Signed']
        ];
        return $headers[$reportType] ?? [];
    }
    
    
    /**
     * Build Progress Report PDF (Student/Faculty)
     */
    private function buildProgressReportPDF($pdf, $reportType, $reportData, $params) {
        error_log("[ReportGenerator] buildProgressReportPDF: Starting, rows=" . count($reportData['rows']));
        
        $rows = $reportData['rows'];
        $summary = $reportData['summary'];
        
        // Department info (if specified)
        if (!empty($params['department_id'])) {
            try {
                $deptStmt = $this->pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
                $deptStmt->execute([$params['department_id']]);
                $deptName = $deptStmt->fetchColumn();
                if ($deptName) {
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Department: ' . $deptName, 0, 1, 'L');
                }
            } catch (Exception $e) {
                error_log("[ReportGenerator] Warning: Could not fetch department: " . $e->getMessage());
            }
        }
        
        // Summary section
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        $summaryText = sprintf(
            'Total Forms: %d | Unapplied: %d | In Progress: %d | Completed: %d',
            $summary['TotalForms'] ?? 0,
            $summary['TotalUnapplied'] ?? 0,
            $summary['TotalInProgress'] ?? 0,
            $summary['TotalCompleted'] ?? 0
        );
        $pdf->Cell(0, 6, $summaryText, 0, 1, 'L');
        $pdf->Ln(5);
        
        // Table headers
        $headers = $this->getTableHeaders($reportType);
        $widths = $this->getTableWidths($reportType);
        $aligns = $this->getTableAligns($reportType);
        
        // Header row
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, $aligns[$i], true);
        }
        $pdf->Ln();
        
        // Data rows
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        error_log("[ReportGenerator] Writing " . count($rows) . " data rows");
        
        if (count($rows) === 0) {
            // Empty state
            $colspan = count($headers);
            $totalWidth = array_sum($widths);
            $pdf->Cell($totalWidth, 10, 'No data available', 1, 1, 'C');
        } else {
            foreach ($rows as $rowIndex => $row) {
                try {
                    $rowData = $this->formatProgressRow($reportType, $row, $headers);
                    error_log("[ReportGenerator] Row $rowIndex data: " . json_encode($rowData));
                    
                    foreach ($rowData as $i => $cell) {
                        // Ensure cell is a string and not too long
                        $cellText = (string)($cell ?? '');
                        if (strlen($cellText) > 50) {
                            $cellText = substr($cellText, 0, 47) . '...';
                        }
                        $pdf->Cell($widths[$i], 7, $cellText, 1, 0, $aligns[$i], false);
                    }
                    $pdf->Ln();
                } catch (Exception $e) {
                    error_log("[ReportGenerator] ERROR writing row $rowIndex: " . $e->getMessage());
                }
            }
        }
        
        // Generated by
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Generated by: ' . ($params['role'] ?? 'System'), 0, 1, 'R');
        
        error_log("[ReportGenerator] buildProgressReportPDF completed");
    }
    
    /**
     * Build Applicant Status Report PDF (Student/Faculty)
     */
    private function buildApplicantStatusReportPDF($pdf, $reportType, $reportData, $params) {
        error_log("[ReportGenerator] buildApplicantStatusReportPDF: Starting, rows=" . count($reportData['rows']));
        
        $rows = $reportData['rows'];
        $summary = $reportData['summary'];
        
        // Department info (if specified)
        if (!empty($params['department_id'])) {
            try {
                $deptStmt = $this->pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
                $deptStmt->execute([$params['department_id']]);
                $deptName = $deptStmt->fetchColumn();
                if ($deptName) {
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Department: ' . $deptName, 0, 1, 'L');
                }
            } catch (Exception $e) {
                error_log("[ReportGenerator] Warning: Could not fetch department: " . $e->getMessage());
            }
        }
        
        // Summary section
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        $summaryText = sprintf(
            'Total: %d | Unapplied: %d | Pending: %d | In Progress: %d | Approved: %d | Rejected: %d',
            $summary['TotalForms'] ?? 0,
            $summary['TotalUnapplied'] ?? 0,
            $summary['TotalPending'] ?? 0,
            $summary['TotalInProgress'] ?? 0,
            $summary['TotalApproved'] ?? 0,
            $summary['TotalRejected'] ?? 0
        );
        $pdf->Cell(0, 6, $summaryText, 0, 1, 'L');
        $pdf->Ln(5);
        
        // Table
        $headers = $this->getTableHeaders($reportType);
        $widths = $this->getTableWidths($reportType);
        $aligns = $this->getTableAligns($reportType);
        
        // Header row
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, $aligns[$i], true);
        }
        $pdf->Ln();
        
        // Data rows
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        error_log("[ReportGenerator] Writing " . count($rows) . " data rows");
        
        if (count($rows) === 0) {
            // Empty state
            $totalWidth = array_sum($widths);
            $pdf->Cell($totalWidth, 10, 'No data available', 1, 1, 'C');
        } else {
            foreach ($rows as $rowIndex => $row) {
                try {
                    $rowData = $this->formatApplicantStatusRow($reportType, $row, $headers);
                    error_log("[ReportGenerator] Row $rowIndex data: " . json_encode($rowData));
                    
                    foreach ($rowData as $i => $cell) {
                        // Ensure cell is a string and not too long
                        $cellText = (string)($cell ?? '');
                        if (strlen($cellText) > 50) {
                            $cellText = substr($cellText, 0, 47) . '...';
                        }
                        $pdf->Cell($widths[$i], 7, $cellText, 1, 0, $aligns[$i], false);
                    }
                    $pdf->Ln();
                } catch (Exception $e) {
                    error_log("[ReportGenerator] ERROR writing row $rowIndex: " . $e->getMessage());
                }
            }
        }
        
        // Generated by
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Generated by: ' . ($params['role'] ?? 'System'), 0, 1, 'R');
        
        error_log("[ReportGenerator] buildApplicantStatusReportPDF completed");
    }
    
    /**
     * Get table headers for report type
     */
    private function getTableHeaders($reportType) {
        $headers = [
            'student_progress' => ['Student No', 'First Name', 'Middle Name', 'Last Name', 'Program', 'Year Level', 'Section', 'Status'],
            'faculty_progress' => ['Employee No', 'First Name', 'Last Name', 'Department', 'Employment Status', 'Status'],
            'student_applicant_status' => ['Student No', 'First Name', 'Last Name', 'Program', 'Year Level', 'Designation', 'Signatory', 'Action', 'Date Signed'],
            'faculty_applicant_status' => ['Employee No', 'First Name', 'Last Name', 'Department', 'Employment Status', 'Designation', 'Signatory', 'Action', 'Date Signed']
        ];
        return $headers[$reportType] ?? [];
    }
    
    /**
     * Get table column widths for report type
     */
    private function getTableWidths($reportType) {
        $widths = [
            'student_progress' => [25, 30, 25, 30, 35, 20, 20, 25],
            'faculty_progress' => [30, 35, 35, 40, 30, 20],
            'student_applicant_status' => [25, 30, 30, 30, 20, 30, 35, 20, 30],
            'faculty_applicant_status' => [30, 30, 30, 35, 30, 30, 35, 20, 30]
        ];
        return $widths[$reportType] ?? [];
    }
    
    /**
     * Get table column alignments for report type
     */
    private function getTableAligns($reportType) {
        $aligns = [
            'student_progress' => ['L', 'L', 'L', 'L', 'L', 'C', 'C', 'C'],
            'faculty_progress' => ['L', 'L', 'L', 'L', 'L', 'C'],
            'student_applicant_status' => ['L', 'L', 'L', 'L', 'C', 'L', 'L', 'C', 'C'],
            'faculty_applicant_status' => ['L', 'L', 'L', 'L', 'L', 'L', 'L', 'C', 'C']
        ];
        return $aligns[$reportType] ?? [];
    }
    
    /**
     * Format progress report row data
     */
    private function formatProgressRow($reportType, $row, $headers) {
        if ($reportType === 'student_progress') {
            return [
                $row['student_no'] ?? '',
                $row['first_name'] ?? '',
                $row['middle_name'] ?? '',
                $row['last_name'] ?? '',
                $row['program_name'] ?? '',
                $row['year_level'] ?? '',
                $row['section'] ?? '',
                $this->formatStatus($row['form_status'] ?? 'unapplied')
            ];
        } else { // faculty_progress
            return [
                $row['employee_number'] ?? '',
                $row['first_name'] ?? '',
                $row['last_name'] ?? '',
                $row['department'] ?? '',
                $row['employment_status'] ?? '',
                $this->formatStatus($row['form_status'] ?? 'unapplied')
            ];
        }
    }
    
    /**
     * Format applicant status report row data
     */
    private function formatApplicantStatusRow($reportType, $row, $headers) {
        if ($reportType === 'student_applicant_status') {
            return [
                $row['student_no'] ?? '',
                $row['first_name'] ?? '',
                $row['last_name'] ?? '',
                $row['program_name'] ?? '',
                $row['year_level'] ?? '',
                $row['designation_name'] ?? '',
                $row['signatory_name'] ?? '',
                $row['action_status'] ?? '',
                $row['date_signed'] ?? ''
            ];
        } else { // faculty_applicant_status
            return [
                $row['employee_number'] ?? '',
                $row['first_name'] ?? '',
                $row['last_name'] ?? '',
                $row['department'] ?? '',
                $row['employment_status'] ?? '',
                $row['designation_name'] ?? '',
                $row['signatory_name'] ?? '',
                $row['action_status'] ?? '',
                $row['date_signed'] ?? ''
            ];
        }
    }
    
    /**
     * Format status value for display
     */
    private function formatStatus($status) {
        return match(strtolower(trim($status ?? 'unapplied'))) {
            'unapplied' => 'Unapplied',
            'in-progress' => 'In Progress',
            'complete' => 'Completed',
            default => ucfirst($status)
        };
    }
}

