<?php
/**
 * ClearanceFormPDFGenerator Class
 * Handles generating clearance form PDFs programmatically using FPDF/FPDI
 * Similar approach to ReportGenerator - builds PDFs from scratch
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

use DateTime;
use DateTimeZone;
use setasign\Fpdi\Fpdi;

// Also include FPDF class directly for fallback
if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';
}

class ClearanceFormPDFGenerator {
    private $pdo;
    private $tempDir;
    
    public function __construct($connection) {
        $this->pdo = $connection;
        $this->tempDir = sys_get_temp_dir();
    }
    
    /**
     * Generate clearance form PDF
     * @param string $userType 'student' or 'faculty'
     * @param int $formId Clearance form ID
     * @param string $outputFile Path to output PDF file
     * @return string Path to generated file
     */
    public function generateClearancePDF($userType, $formId, $outputFile) {
        error_log("[ClearanceFormPDFGenerator] ========== generateClearancePDF START ==========");
        error_log("[ClearanceFormPDFGenerator] userType=$userType, formId=$formId");
        
        try {
            // Fetch all required data
            $data = $this->fetchClearanceData($userType, $formId);
            error_log("[ClearanceFormPDFGenerator] Data fetched successfully");
            
            // Create PDF instance
            $pdf = $this->createPDFInstance();
            
            // Add single page for both copies
            $pdf->AddPage();
            
            // Generate School Copy (Top half: 8 x 5 inches = 203.2mm x 127mm)
            $this->buildClearancePage($pdf, $data, 'School Copy', $userType, 6.35, 6.35);
            
            // Generate Student/Faculty Copy (Bottom half: 8 x 5 inches = 203.2mm x 127mm)
            // Start at Y = 6.35mm (top margin) + 127mm (first copy) + 6.35mm (spacing) = 139.7mm
            $this->buildClearancePage($pdf, $data, 'Student Copy', $userType, 6.35, 139.7);
            
            // Save PDF
            $pdf->Output('F', $outputFile);
            
            if (!file_exists($outputFile) || filesize($outputFile) === 0) {
                throw new Exception('PDF file was not created or is empty');
            }
            
            error_log("[ClearanceFormPDFGenerator] PDF created successfully: $outputFile (" . filesize($outputFile) . " bytes)");
            error_log("[ClearanceFormPDFGenerator] ========== generateClearancePDF END ==========");
            
            return $outputFile;
            
        } catch (Exception $e) {
            error_log("[ClearanceFormPDFGenerator] ERROR: " . $e->getMessage());
            error_log("[ClearanceFormPDFGenerator] Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Fetch clearance form data from database
     */
    private function fetchClearanceData($userType, $formId) {
        // Fetch user and form details
        $stmt = $this->pdo->prepare("
            SELECT 
                u.user_id,
                u.username,
                u.first_name,
                u.middle_name,
                u.last_name,
                cf.clearance_form_id,
                cf.clearance_form_progress,
                cf.completed_at,
                cf.clearance_type,
                ay.year as academic_year,
                s.semester_name
            FROM clearance_forms cf
            JOIN users u ON cf.user_id = u.user_id
            JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
            JOIN semesters s ON cf.semester_id = s.semester_id
            WHERE cf.clearance_form_id = ?
        ");
        $stmt->execute([$formId]);
        $formData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$formData) {
            throw new Exception("Clearance form not found");
        }
        
        // Fetch signatories
        $sigStmt = $this->pdo->prepare("
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
        $sigStmt->execute([$formId]);
        $signatories = $sigStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Determine user type from database (check if user is student or faculty)
        $detectedUserType = 'student'; // default
        $userDetails = [];
        
        // Check if user is a student
        $studentStmt = $this->pdo->prepare("
            SELECT 
                s.year_level,
                s.section,
                p.program_name,
                d.department_name
            FROM students s
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON s.department_id = d.department_id
            WHERE s.user_id = ?
        ");
        $studentStmt->execute([$formData['user_id']]);
        $studentDetails = $studentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($studentDetails) {
            // User is a student
            $detectedUserType = 'student';
            $userDetails = $studentDetails;
        } else {
            // Check if user is faculty
            $facultyStmt = $this->pdo->prepare("
                SELECT 
                    f.employee_number,
                    f.employment_status,
                    d.department_name
                FROM faculty f
                LEFT JOIN departments d ON f.department_id = d.department_id
                WHERE f.user_id = ?
            ");
            $facultyStmt->execute([$formData['user_id']]);
            $facultyDetails = $facultyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($facultyDetails) {
                // User is faculty
                $detectedUserType = 'faculty';
                $userDetails = $facultyDetails;
            } else {
                // Fallback to provided userType if database check fails
                $detectedUserType = strtolower($userType ?? 'student');
                error_log("[ClearanceFormPDFGenerator] Warning: Could not determine user type from database, using provided: $detectedUserType");
            }
        }
        
        // Extract Registrar from signatories
        $registrarName = 'Registrar';
        $registrarAction = 'Pending';
        foreach ($signatories as $s) {
            if (isset($s['designation_name']) && stripos($s['designation_name'], 'Registrar') !== false) {
                if (!empty($s['signatory_name'])) {
                    $registrarName = $s['signatory_name'];
                }
                $registrarAction = $s['action'] ?? 'Pending';
                break;
            }
        }
        
        return [
            'user' => $formData,
            'user_details' => $userDetails,
            'signatories' => $signatories,
            'registrar_name' => $registrarName,
            'registrar_action' => $registrarAction,
            'user_type' => $detectedUserType
        ];
    }
    
    /**
     * Create PDF instance (FPDI or FPDF)
     * Page size: 8.5 x 11 inches (Letter size) = 215.9mm x 279.4mm
     */
    private function createPDFInstance() {
        // Letter size: 8.5 x 11 inches = 215.9mm x 279.4mm
        if (class_exists('setasign\Fpdi\Fpdi')) {
            $pdf = new Fpdi('P', 'mm', [215.9, 279.4]);
        } elseif (class_exists('FPDF')) {
            $pdf = new FPDF('P', 'mm', [215.9, 279.4]);
        } else {
            throw new Exception('Neither FPDI nor FPDF class found');
        }
        
        // Set PDF metadata
        $pdf->SetCreator('goSTI Clearance System');
        $pdf->SetAuthor('STI College Lucena');
        $pdf->SetTitle('Clearance Form');
        $pdf->SetSubject('Clearance Form');
        
        // Set margins - smaller margins to fit both copies
        $pdf->SetMargins(6.35, 6.35, 6.35); // 0.25 inches = 6.35mm
        $pdf->SetAutoPageBreak(FALSE); // Disable auto page break - we control it manually
        
        return $pdf;
    }
    
    /**
     * Build a complete clearance page (School Copy or Student/Faculty Copy)
     * @param float $startX Starting X position in mm
     * @param float $startY Starting Y position in mm
     */
    private function buildClearancePage($pdf, $data, $copyType, $userType, $startX = 6.35, $startY = 6.35) {
        // Save current position
        $originalX = $pdf->GetX();
        $originalY = $pdf->GetY();
        
        // Set starting position for this copy
        $pdf->SetXY($startX, $startY);
        
        // Build static header (logo + school name)
        $this->buildStaticHeader($pdf, $startX, $startY);
        
        // Build clearance header (title, form ID, copy type)
        $this->buildClearanceHeader($pdf, $data, $copyType, $startX);
        
        // Build user information section
        $this->buildUserInfo($pdf, $data, $userType, $startX);
        
        // Build signatory table
        $this->buildSignatoryTable($pdf, $data['signatories'], $data['registrar_name'], $data['registrar_action'], $data, $startX, $startY);
        
        // Build footer
        $this->buildFooter($pdf, $copyType, $data, $startX, $startY);
        
        // Restore original position
        $pdf->SetXY($originalX, $originalY);
    }
    
    /**
     * Build Static Header - Logo + School Name
     * @param float $startX Starting X position
     * @param float $startY Starting Y position
     */
    private function buildStaticHeader($pdf, $startX = 6.35, $startY = 6.35) {
        $logoY = $startY;
        $logoX = $startX;
        
        // Logo (top-left) - smaller size for 8x5 copy
        $logoPath = __DIR__ . '/../../assets/images/STI_Lucena_Logo.jpg';
        if (file_exists($logoPath)) {
            try {
                if (method_exists($pdf, 'Image')) {
                    $pdf->Image($logoPath, $logoX, $logoY, 12, 0, 'JPG');
                }
            } catch (Exception $e) {
                error_log("[ClearanceFormPDFGenerator] Warning: Could not add logo: " . $e->getMessage());
            }
        }
        
        // "STI COLLEGE LUCENA" text (top-right) - font size 10
        $pdf->SetFont('Arial', 'B', 10);
        $rightX = $startX + 203.2 - 40; // 8 inches = 203.2mm, minus 40mm for text width
        $pdf->SetXY($rightX, $logoY + 1);
        $pdf->Cell(40, 4, 'STI COLLEGE LUCENA', 0, 0, 'R');
        
        // Reset Y position
        $pdf->SetY($logoY + 10);
    }
    
    /**
     * Build Clearance Header - Title, Form ID, Copy Type
     * @param float $startX Starting X position
     */
    private function buildClearanceHeader($pdf, $data, $copyType, $startX = 6.35) {
        $currentY = $pdf->GetY();
        $width = 203.2; // 8 inches = 203.2mm
        
        // Title - font size 10
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($startX, $currentY);
        $pdf->Cell($width, 5, 'CLEARANCE FORM', 0, 0, 'C');
        
        // Copy Type Label
        $currentY += 6;
        $copyLabel = ($copyType === 'School Copy') ? 'SCHOOL COPY' : strtoupper($data['user_type']) . ' COPY';
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($startX, $currentY);
        $pdf->Cell($width, 5, $copyLabel, 0, 0, 'C');
        
        // Form ID and Period
        $currentY += 6;
        $pdf->SetFont('Arial', '', 10);
        $formId = $data['user']['clearance_form_id'];
        $period = $data['user']['academic_year'] . ' | ' . $data['user']['semester_name'];
        
        $pdf->SetXY($startX, $currentY);
        $pdf->Cell($width / 2, 4, 'Form ID: ' . $formId, 0, 0, 'L');
        $pdf->SetXY($startX + $width / 2, $currentY);
        $pdf->Cell($width / 2, 4, 'Academic Year and Term: ' . $period, 0, 0, 'R');
        
        // Horizontal line
        $currentY += 5;
        $pdf->Line($startX, $currentY, $startX + $width, $currentY);
        
        $pdf->SetY($currentY + 3);
    }
    
    /**
     * Build User Information Section
     * @param float $startX Starting X position
     */
    private function buildUserInfo($pdf, $data, $userType, $startX = 6.35) {
        $currentY = $pdf->GetY();
        $width = 203.2; // 8 inches = 203.2mm
        
        // Use user_type from data array for consistency (fallback to parameter)
        $actualUserType = strtolower($data['user_type'] ?? $userType ?? 'student');
        
        // Section title - font size 10
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($startX, $currentY);
        $sectionTitle = ($actualUserType === 'student') ? 'Student Information' : 'Faculty Information';
        $pdf->Cell($width, 4, $sectionTitle, 0, 1, 'L');
        
        $currentY += 5;
        $pdf->SetFont('Arial', '', 10);
        
        // Format full name
        $firstName = $data['user']['first_name'] ?? '';
        $middleName = $data['user']['middle_name'] ?? '';
        $lastName = $data['user']['last_name'] ?? '';
        $fullName = trim($lastName . ($lastName && $firstName ? ', ' : '') . $firstName . ($middleName ? ' ' . substr($middleName, 0, 1) . '.' : ''));
        
        if ($actualUserType === 'student') {
            // 2-column, 3-row layout without borders (with wrapping)
            $colSpacing = 5;
            $colWidth = ($width - $colSpacing) / 2;
            $labelWidth = 30;
            $lineHeight = 4;
            
            $rows = [
                [
                    ['label' => 'Name:', 'value' => $fullName],
                    ['label' => 'Student No:', 'value' => $data['user']['username'] ?? 'N/A'],
                ],
                [
                    ['label' => 'Program:', 'value' => $data['user_details']['program_name'] ?? 'N/A'],
                    ['label' => 'Year Level:', 'value' => $data['user_details']['year_level'] ?? 'N/A'],
                ],
                [
                    ['label' => 'Section:', 'value' => $data['user_details']['section'] ?? 'N/A'],
                    null,
                ],
            ];
            
            foreach ($rows as $row) {
                $rowY = $currentY;
                $maxHeight = 0;
                $colX = $startX;
                
                foreach ($row as $column) {
                    if ($column === null) {
                        $colX += $colWidth + $colSpacing;
                        continue;
                    }
                    
                    $height = $this->drawInfoColumn(
                        $pdf,
                        $colX,
                        $rowY,
                        $labelWidth,
                        $colWidth - $labelWidth,
                        $column['label'],
                        $column['value'],
                        $lineHeight
                    );
                    
                    $maxHeight = max($maxHeight, $height);
                    $colX += $colWidth + $colSpacing;
                }
                
                $currentY = $rowY + $maxHeight + 1;
            }
        } else {
            // Faculty - single column layout with wrapping
            $labelWidth = 35;
            $valueWidth = $width - $labelWidth;
            $lineHeight = 4;
            
            $facultyFields = [
                ['label' => 'Name:', 'value' => $fullName],
                ['label' => 'Employee Number:', 'value' => $data['user_details']['employee_number'] ?? 'N/A'],
                ['label' => 'Department:', 'value' => $data['user_details']['department_name'] ?? 'N/A'],
                ['label' => 'Employment Status:', 'value' => $data['user_details']['employment_status'] ?? 'N/A'],
            ];
            
            foreach ($facultyFields as $field) {
                $rowY = $currentY;
                $height = $this->drawInfoColumn(
                    $pdf,
                    $startX,
                    $rowY,
                    $labelWidth,
                    $valueWidth,
                    $field['label'],
                    $field['value'],
                    $lineHeight
                );
                
                $currentY = $rowY + $height + 1;
            }
        }
        
        // Horizontal line
        $currentY += 2;
        $pdf->Line($startX, $currentY, $startX + $width, $currentY);
        
        $pdf->SetY($currentY + 3);
    }
    
    /**
     * Build Signatory Table
     * @param float $startX Starting X position
     * @param float $startY Starting Y position (top of this copy)
     */
    private function buildSignatoryTable($pdf, $signatories, $registrarName, $registrarAction, $data, $startX = 6.35, $startY = 6.35) {
        $currentY = $pdf->GetY();
        $width = 203.2; // 8 inches = 203.2mm
        $copyHeight = 127; // 5 inches = 127mm
        
        // Section title - font size 10
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($startX, $currentY);
        $pdf->Cell($width, 4, 'Signatory Status', 0, 1, 'L');
        
        $currentY += 5;
        
        if (empty($signatories)) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetXY($startX, $currentY);
            $pdf->Cell($width, 4, 'No signatories assigned', 0, 1, 'L');
            return;
        }
        
        // Table header - font size 10
        $headerY = $currentY;
        $headerHeight = 5;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(232, 244, 248);
        $pdf->SetTextColor(0, 0, 0);
        
        // Column widths - adjusted for 8 inches width, font size 10
        // Total: 203.2mm, leave 3mm for borders = 200.2mm usable
        $colWidths = [60, 50, 45, 45]; // Designation, Signatory, Status, Date
        $colHeaders = ['Designation', 'Signatory', 'Status', 'Date Signed'];
        $xPos = $startX;
        
        // Draw header row
        foreach ($colHeaders as $idx => $header) {
            $pdf->Rect($xPos, $headerY, $colWidths[$idx], $headerHeight, 'DF');
            $pdf->SetXY($xPos + 1, $headerY + 1);
            $pdf->Cell($colWidths[$idx] - 2, 3, $header, 0, 0, 'L');
            $xPos += $colWidths[$idx];
        }
        
        $currentY = $headerY + $headerHeight;
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetFillColor(255, 255, 255);
        
        // Separate registrar from other signatories
        $nonRegistrarSignatories = [];
        $registrarSignatory = null;
        
        foreach ($signatories as $sig) {
            if (isset($sig['designation_name']) && stripos($sig['designation_name'], 'Registrar') !== false) {
                $registrarSignatory = $sig;
            } else {
                $nonRegistrarSignatories[] = $sig;
            }
        }
        
        // Calculate available space for table
        $footerSpace = 8; // Footer needs 8mm
        $maxTableY = $startY + $copyHeight - $footerSpace - $rowHeight;
        
        // Draw non-registrar signatories
        $rowHeight = 5; // Smaller row height for font size 10
        $maxRows = floor(($maxTableY - $currentY - $rowHeight) / $rowHeight); // Reserve space for registrar row
        $rowsDrawn = 0;
        
        foreach ($nonRegistrarSignatories as $sig) {
            // Stop if we've reached max rows (leave space for registrar)
            if ($rowsDrawn >= $maxRows - 1) {
                break;
            }
            
            $rowY = $currentY;
            $xPos = $startX;
            
            // Draw row borders
            foreach ($colWidths as $colWidth) {
                $pdf->Rect($xPos, $rowY, $colWidth, $rowHeight, 'D');
                $xPos += $colWidth;
            }
            
            // Draw cell content
            $xPos = $startX;
            
            // Designation
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[0] - 2, 3, $sig['designation_name'] ?? 'N/A', 0, 0, 'L');
            $xPos += $colWidths[0];
            
            // Signatory Name - show blank if unapplied/rejected/pending
            $action = strtolower(trim($sig['action'] ?? 'Unapplied'));
            $signatoryName = '';
            if ($action === 'approved') {
                $signatoryName = $sig['signatory_name'] ?? '';
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[1] - 2, 3, $signatoryName, 0, 0, 'L');
            $xPos += $colWidths[1];
            
            // Status - show blank if unapplied/rejected/pending
            $status = '';
            if ($action === 'approved') {
                $status = 'Approved';
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[2] - 2, 3, $status, 0, 0, 'L');
            $xPos += $colWidths[2];
            
            // Date Signed - show blank if unapplied/rejected/pending
            $dateSigned = '';
            if ($action === 'approved' && !empty($sig['date_signed'])) {
                $dateSigned = date('M j, Y', strtotime($sig['date_signed']));
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[3] - 2, 3, $dateSigned, 0, 0, 'L');
            
            $currentY += $rowHeight;
            $rowsDrawn++;
        }
        
        // Add Registrar row at the end (always show, even if pending/unapplied)
        // Ensure we have space for registrar row
        if ($currentY < $maxTableY) {
            $rowY = $currentY;
            $xPos = $startX;
            
            // Draw row borders
            foreach ($colWidths as $colWidth) {
                $pdf->Rect($xPos, $rowY, $colWidth, $rowHeight, 'D');
                $xPos += $colWidth;
            }
            
            // Draw cell content
            $xPos = $startX;
            
            // Designation
            $registrarDesignation = 'Registrar';
            if ($registrarSignatory && !empty($registrarSignatory['designation_name'])) {
                $registrarDesignation = $registrarSignatory['designation_name'];
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[0] - 2, 3, $registrarDesignation, 0, 0, 'L');
            $xPos += $colWidths[0];
            
            // Signatory Name - show blank if not approved
            $registrarActionLower = strtolower(trim($registrarAction));
            $registrarNameDisplay = '';
            if ($registrarActionLower === 'approved') {
                $registrarNameDisplay = $registrarName;
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[1] - 2, 3, $registrarNameDisplay, 0, 0, 'L');
            $xPos += $colWidths[1];
            
            // Status - show blank if not approved
            $registrarStatus = '';
            if ($registrarActionLower === 'approved') {
                $registrarStatus = 'Approved';
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[2] - 2, 3, $registrarStatus, 0, 0, 'L');
            $xPos += $colWidths[2];
            
            // Date Signed - show blank if not approved
            $registrarDateSigned = '';
            if ($registrarActionLower === 'approved' && $registrarSignatory && !empty($registrarSignatory['date_signed'])) {
                $registrarDateSigned = date('M j, Y', strtotime($registrarSignatory['date_signed']));
            }
            $pdf->SetXY($xPos + 1, $rowY + 1);
            $pdf->Cell($colWidths[3] - 2, 3, $registrarDateSigned, 0, 0, 'L');
            
            $currentY += $rowHeight;
        }
        
        // No Overall Status section - removed as per requirements
        $pdf->SetY($currentY);
    }
    
    /**
     * Build Footer
     * @param float $startX Starting X position
     * @param float $startY Starting Y position (top of this copy)
     */
    private function buildFooter($pdf, $copyType, $data, $startX = 6.35, $startY = 6.35) {
        $width = 203.2; // 8 inches = 203.2mm
        $copyHeight = 127; // 5 inches = 127mm
        
        // Footer Y position: startY + copyHeight - footer height (8mm)
        $footerY = $startY + $copyHeight - 8;
        
        $pdf->SetFont('Arial', '', 10);
        
        // Set footer text color to gray (less visible)
        $pdf->SetTextColor(128, 128, 128); // Gray color
        
        // Date Generated (left)
        $pdf->SetXY($startX, $footerY);
        $pdf->Cell(50, 3, 'Date Generated:', 0, 0, 'L');
        $pdf->SetXY($startX, $footerY + 3);
        // Use DateTime with Asia/Manila timezone for accurate local time
        $dateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $dateGenerated = $dateTime->format('F j, Y, g:i a');
        $pdf->Cell(50, 3, $dateGenerated, 0, 0, 'L');
        
        // Processed by (right)
        $rightX = $startX + $width - 50;
        $pdf->SetXY($rightX, $footerY);
        $pdf->Cell(50, 3, 'Processed by goSTI for', 0, 0, 'R');
        $pdf->SetXY($rightX, $footerY + 3);
        $pdf->Cell(50, 3, 'STI College Lucena', 0, 0, 'R');
        
        // Reset text color to black for other content
        $pdf->SetTextColor(0, 0, 0);
    }
    
    /**
     * Format status for display
     */
    private function formatStatus($status) {
        if (empty($status) || $status === 'Unapplied') {
            return 'Unapplied';
        }
        
        $status = strtolower(trim($status));
        
        switch ($status) {
            case 'pending':
                return 'Pending';
            case 'approved':
                return 'Approved';
            case 'rejected':
                return 'Rejected';
            case 'in-progress':
            case 'in progress':
                return 'In Progress';
            case 'complete':
            case 'completed':
                return 'Completed';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Draw a label/value column with wrapped text and return the consumed height.
     */
    private function drawInfoColumn($pdf, $x, $y, $labelWidth, $valueWidth, $label, $value, $lineHeight = 4) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($labelWidth, $lineHeight, $label, 0, 0, 'L');
        
        $pdf->SetXY($x + $labelWidth, $y);
        $valueHeight = $this->renderWrappedText($pdf, $valueWidth, $lineHeight, $value);
        
        return max($lineHeight, $valueHeight);
    }
    
    /**
     * Render wrapped text using MultiCell and return the height used.
     */
    private function renderWrappedText($pdf, $width, $lineHeight, $text) {
        $text = ($text === null || $text === '') ? 'N/A' : $text;
        
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        
        $pdf->MultiCell($width, $lineHeight, $text, 0, 'L');
        $endY = $pdf->GetY();
        $height = $endY - $startY;
        
        if ($height <= 0) {
            $height = $lineHeight;
        }
        
        // Reset position to the right of the rendered text so subsequent columns continue correctly
        $pdf->SetXY($startX + $width, $startY);
        
        return $height;
    }
}

