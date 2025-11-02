<?php
/**
 * Utility to extract placeholder names from .docx templates
 * Run: php tools/extract_template_placeholders.php
 */

require_once __DIR__ . '/../includes/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

function extractPlaceholders($docxPath) {
    if (!file_exists($docxPath)) {
        echo "File not found: $docxPath\n";
        return [];
    }
    
    $phpWord = IOFactory::load($docxPath);
    $placeholders = [];
    
    // Extract from all text elements
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            extractFromElement($element, $placeholders);
        }
    }
    
    return array_unique($placeholders);
}

function extractFromElement($element, &$placeholders) {
    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $text = $element->getText();
        if (is_array($text)) $text = implode('', $text);
        // Match ${Placeholder} format (what users type in Word)
        preg_match_all('/\$\{(\w+)\}/', $text, $matches);
        if (!empty($matches[1])) {
            $placeholders = array_merge($placeholders, $matches[1]);
        }
        // Also match $Placeholder format (alternative)
        preg_match_all('/\$(\w+)(?![{])/', $text, $matches2);
        if (!empty($matches2[1])) {
            $placeholders = array_merge($placeholders, $matches2[1]);
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

// Extract from both templates
$templates = [
    'Student Progress' => __DIR__ . '/../assets/templates/reports/Student_Clearance_Form_Progress.docx',
    'Faculty Progress' => __DIR__ . '/../assets/templates/reports/Faculty_Clearance_Form_Progress.docx'
];

echo "=== Template Placeholder Extraction ===\n\n";

foreach ($templates as $name => $path) {
    echo "--- $name ---\n";
    $placeholders = extractPlaceholders($path);
    if (!empty($placeholders)) {
        sort($placeholders);
        foreach ($placeholders as $p) {
            echo "  \${$p}\n";
        }
    } else {
        echo "  (No placeholders found - templates may need placeholders added)\n";
    }
    echo "\n";
}

