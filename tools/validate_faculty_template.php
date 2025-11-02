<?php
/**
 * Validate Faculty Progress Template Structure
 * Checks if Faculty template has all required placeholders matching Student template
 * 
 * Run: php tools/validate_faculty_template.php
 */

require_once __DIR__ . '/../includes/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

function extractPlaceholders($docxPath) {
    if (!file_exists($docxPath)) {
        return [];
    }
    
    $phpWord = IOFactory::load($docxPath);
    $placeholders = [];
    
    // Extract full text first, then parse placeholders (more reliable)
    $fullText = '';
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            $fullText .= extractAllText($element);
        }
    }
    
    // Match ${Placeholder} format
    preg_match_all('/\$\{(\w+)\}/', $fullText, $matches);
    if (!empty($matches[1])) {
        $placeholders = array_merge($placeholders, $matches[1]);
    }
    
    return array_unique($placeholders);
}

function extractFromElement($element, &$placeholders) {
    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $text = $element->getText();
        if (is_array($text)) $text = implode('', $text);
        // Match ${Placeholder} format
        preg_match_all('/\$\{(\w+)\}/', $text, $matches);
        if (!empty($matches[1])) {
            $placeholders = array_merge($placeholders, $matches[1]);
        }
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $subElement) {
            extractFromElement($subElement, $placeholders);
        }
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
        foreach ($element->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                foreach ($cell->getElements() as $cellElement) {
                    extractFromElement($cellElement, $placeholders);
                }
            }
        }
    }
}

function extractAllText($element) {
    $text = '';
    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $t = $element->getText();
        $text = is_array($t) ? implode('', $t) : (string)$t;
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $subElement) {
            $text .= extractAllText($subElement);
        }
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
        foreach ($element->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                foreach ($cell->getElements() as $cellElement) {
                    $text .= extractAllText($cellElement);
                }
            }
        }
    }
    return $text;
}

// Required placeholders
$requiredHeaders = ['ReportTitle', 'Sector', 'SchoolYear', 'Semester', 'DepartmentName', 'GeneratedBy'];
$requiredSummary = ['TotalForms', 'TotalUnapplied', 'TotalInProgress', 'TotalCompleted'];
$requiredRowFields = ['EmployeeNo', 'FirstName', 'MiddleName', 'LastName', 'Department', 'EmploymentStatus', 'FormStatus'];

$studentTemplate = __DIR__ . '/../assets/templates/reports/Student_Clearance_Form_Progress.docx';
$facultyTemplate = __DIR__ . '/../assets/templates/reports/Faculty_Clearance_Form_Progress.docx';

echo "=== Faculty Template Validation ===\n\n";

// Extract placeholders
$studentPlaceholders = extractPlaceholders($studentTemplate);
$facultyPlaceholders = extractPlaceholders($facultyTemplate);

echo "Student Template Placeholders: " . count($studentPlaceholders) . "\n";
echo "Faculty Template Placeholders: " . count($facultyPlaceholders) . "\n\n";

// Check headers
echo "--- Header Placeholders ---\n";
$missingHeaders = [];
foreach ($requiredHeaders as $ph) {
    if (in_array($ph, $facultyPlaceholders)) {
        echo "✓ \${$ph}\n";
    } else {
        echo "✗ \${$ph} - MISSING\n";
        $missingHeaders[] = $ph;
    }
}

// Check summary
echo "\n--- Summary Placeholders ---\n";
$missingSummary = [];
foreach ($requiredSummary as $ph) {
    if (in_array($ph, $facultyPlaceholders)) {
        echo "✓ \${$ph}\n";
    } else {
        echo "✗ \${$ph} - MISSING\n";
        $missingSummary[] = $ph;
    }
}

// Check row fields
echo "\n--- Table Row Placeholders ---\n";
$missingRowFields = [];
foreach ($requiredRowFields as $ph) {
    if (in_array($ph, $facultyPlaceholders)) {
        echo "✓ \${$ph}\n";
    } else {
        echo "✗ \${$ph} - MISSING\n";
        $missingRowFields[] = $ph;
    }
}

// Overall status
echo "\n=== Validation Summary ===\n";
$allMissing = array_merge($missingHeaders, $missingSummary, $missingRowFields);
if (empty($allMissing)) {
    echo "✅ PASS: Faculty template matches Student template structure!\n";
    echo "\nFirst table row placeholder (for cloneRow): ";
    if (in_array('EmployeeNo', $facultyPlaceholders)) {
        echo "\${EmployeeNo} ✓\n";
    } else {
        echo "❌ EmployeeNo not found - MUST be first column in table row!\n";
    }
} else {
    echo "❌ FAIL: Missing " . count($allMissing) . " required placeholders\n";
    echo "\nMissing placeholders:\n";
    foreach ($allMissing as $ph) {
        echo "  - \${$ph}\n";
    }
    echo "\nPlease add these placeholders to the Faculty template.\n";
    echo "See: assets/templates/reports/FACULTY_TEMPLATE_SPECIFICATION.md\n";
}

// Show all placeholders found
echo "\n--- All Placeholders Found in Faculty Template ---\n";
sort($facultyPlaceholders);
foreach ($facultyPlaceholders as $ph) {
    echo "  \${$ph}\n";
}

