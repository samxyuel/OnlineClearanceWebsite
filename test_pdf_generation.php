<?php
/**
 * Simple test script to verify PDF generation works
 * Run this directly in browser: http://localhost/OnlineClearanceWebsite/test_pdf_generation.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PDF Generation Test</h1>";
echo "<pre>";

// Test 1: Check vendor autoload
echo "1. Checking vendor autoload...\n";
$vendorPath = __DIR__ . '/includes/vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
    echo "   ✓ Vendor autoload loaded\n";
} else {
    echo "   ✗ Vendor autoload not found at: $vendorPath\n";
    echo "   Run: composer install\n";
    exit;
}

// Test 2: Check FPDF
echo "\n2. Checking FPDF class...\n";
$fpdfPath = __DIR__ . '/includes/vendor/setasign/fpdf/fpdf.php';
if (file_exists($fpdfPath)) {
    require_once $fpdfPath;
    echo "   ✓ FPDF file found\n";
} else {
    echo "   ✗ FPDF file not found at: $fpdfPath\n";
}

if (class_exists('FPDF')) {
    echo "   ✓ FPDF class exists\n";
} else {
    echo "   ✗ FPDF class not found\n";
}

// Test 3: Check FPDI
echo "\n3. Checking FPDI class...\n";
use setasign\Fpdi\Fpdi;
if (class_exists('setasign\Fpdi\Fpdi')) {
    echo "   ✓ FPDI class exists\n";
} else {
    echo "   ✗ FPDI class not found\n";
}

// Test 4: Try creating PDF instance
echo "\n4. Testing PDF instance creation...\n";
try {
    if (class_exists('setasign\Fpdi\Fpdi')) {
        $pdf = new Fpdi('P', 'mm', 'A4');
        echo "   ✓ FPDI instance created successfully\n";
    } elseif (class_exists('FPDF')) {
        $pdf = new FPDF('P', 'mm', 'A4');
        echo "   ✓ FPDF instance created successfully\n";
    } else {
        echo "   ✗ Cannot create PDF instance - no class available\n";
        exit;
    }
} catch (Exception $e) {
    echo "   ✗ Error creating PDF instance: " . $e->getMessage() . "\n";
    exit;
} catch (Error $e) {
    echo "   ✗ Fatal error creating PDF instance: " . $e->getMessage() . "\n";
    exit;
}

// Test 5: Try adding content
echo "\n5. Testing PDF content creation...\n";
try {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->AddPage();
    $pdf->Cell(0, 10, 'Test PDF Generation', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'If you can read this, PDF generation is working!', 0, 1, 'C');
    echo "   ✓ PDF content added successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error adding content: " . $e->getMessage() . "\n";
    exit;
}

// Test 6: Try saving PDF
echo "\n6. Testing PDF file output...\n";
$testPdfPath = __DIR__ . '/test_output.pdf';
try {
    ob_start();
    $pdf->Output('F', $testPdfPath);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "   ⚠ Warning: Output generated during PDF->Output(): " . substr($output, 0, 100) . "\n";
    }
    
    if (file_exists($testPdfPath)) {
        $size = filesize($testPdfPath);
        echo "   ✓ PDF file created: $testPdfPath ($size bytes)\n";
        
        // Check if it's a valid PDF
        $content = file_get_contents($testPdfPath, false, null, 0, 4);
        if ($content === '%PDF') {
            echo "   ✓ PDF file has valid PDF signature\n";
        } else {
            echo "   ✗ PDF file does not have valid PDF signature (starts with: " . bin2hex($content) . ")\n";
        }
    } else {
        echo "   ✗ PDF file was not created\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error saving PDF: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    exit;
} catch (Error $e) {
    echo "   ✗ Fatal error saving PDF: " . $e->getMessage() . "\n";
    exit;
}

echo "\n========================================\n";
echo "All tests completed!\n";
echo "If all tests passed, PDF generation should work.\n";
echo "Check test_output.pdf in the project root.\n";
echo "</pre>";

