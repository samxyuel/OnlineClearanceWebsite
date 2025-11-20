<?php
/**
 * Diagnostic tool to see exactly what PHPWord reads from the template
 * Helps identify why placeholders might not be detected
 */

require_once __DIR__ . '/../includes/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

$facultyTemplate = __DIR__ . '/../assets/templates/reports/Faculty_Clearance_Form_Progress.docx';

if (!file_exists($facultyTemplate)) {
    die("Template file not found: $facultyTemplate\n");
}

echo "=== Template Diagnostic Tool ===\n\n";
echo "File: " . basename($facultyTemplate) . "\n";
echo "Size: " . filesize($facultyTemplate) . " bytes\n";
echo "Last Modified: " . date('Y-m-d H:i:s', filemtime($facultyTemplate)) . "\n\n";

try {
    $phpWord = IOFactory::load($facultyTemplate);
    echo "✓ Document loaded successfully\n\n";
    
    echo "--- Document Structure ---\n";
    echo "Sections: " . count($phpWord->getSections()) . "\n\n";
    
    $allText = [];
    $placeholders = [];
    
    foreach ($phpWord->getSections() as $sectionIdx => $section) {
        echo "--- Section " . ($sectionIdx + 1) . " ---\n";
        
        foreach ($section->getElements() as $elementIdx => $element) {
            $elementText = extractAllText($element);
            if (!empty($elementText)) {
                $allText[] = $elementText;
                echo "Element $elementIdx: " . substr($elementText, 0, 100) . (strlen($elementText) > 100 ? '...' : '') . "\n";
                
                // Look for placeholders
                preg_match_all('/\$\{?(\w+)\}?/', $elementText, $matches);
                if (!empty($matches[1])) {
                    $placeholders = array_merge($placeholders, $matches[1]);
                }
            }
        }
        echo "\n";
    }
    
    echo "--- All Placeholders Found ---\n";
    $placeholders = array_unique($placeholders);
    sort($placeholders);
    if (!empty($placeholders)) {
        foreach ($placeholders as $ph) {
            echo "  \${$ph}\n";
        }
    } else {
        echo "  (None found)\n";
    }
    
    echo "\n--- Full Text Sample (first 500 chars) ---\n";
    $fullText = implode(' ', $allText);
    echo substr($fullText, 0, 500) . "...\n";
    
    echo "\n--- Searching for specific placeholders ---\n";
    $expected = ['ReportTitle', 'SchoolYear', 'DepartmentName', 'GeneratedBy', 'TotalForms', 'TotalUnapplied', 'TotalInProgress', 'TotalCompleted', 'EmployeeNo', 'MiddleName', 'EmploymentStatus', 'FormStatus', 'AccountDesignation'];
    foreach ($expected as $ph) {
        if (strpos($fullText, $ph) !== false) {
            echo "✓ Found reference to: $ph\n";
        } else {
            echo "✗ Not found: $ph\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error loading document: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
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

