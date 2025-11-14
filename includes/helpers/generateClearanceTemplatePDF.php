<?php
/**
 * ============================================================================
 * OLD APPROACH: DOCX Template to PDF Conversion
 * ============================================================================
 * 
 * This file has been REPLACED by ClearanceFormPDFGenerator.php
 * which uses programmatic PDF generation (similar to ReportGenerator approach).
 * 
 * This file is kept for backup/reference purposes only.
 * The new approach is faster, more maintainable, and doesn't require DOCX templates.
 * 
 * To use the old approach, uncomment this file and comment out the new generator
 * in api/clearance/export_report.php
 * 
 * ============================================================================
 */

// COMMENTED OUT - Using new ClearanceFormPDFGenerator instead
/*
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use setasign\Fpdi\Fpdi;

require_once __DIR__ . '/../vendor/autoload.php';
// FPDF (FPDI depends on it)
require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

// Ensure TCPDF renderer directory exists (you already set this earlier; keep as a check)
$tcpdfPath = realpath(__DIR__ . '/../vendor/tecnickcom/tcpdf');
if ($tcpdfPath && file_exists($tcpdfPath . '/tcpdf.php')) {
    // Use a string name to avoid subtle issues
    Settings::setPdfRendererName('TCPDF');
    Settings::setPdfRendererPath($tcpdfPath);
} else {
    throw new Exception('TCPDF library not found. Please run: composer require tecnickcom/tcpdf');
}

/**
 * generateClearanceTemplatePDF
 *
 * @param array  $templateData   Key-value pairs for ${PLACEHOLDERS} in DOCX
 * @param array  $signatories    Array of signatory rows (designation_name, signatory_name, action, date_signed)
 * @param string $schoolTemplate Path to school copy DOCX
 * @param string $studentTemplate Path to student copy DOCX
 * @param string $outputFile     Path to final merged PDF to write
 */
function generateClearanceTemplatePDF($templateData, $signatories, $schoolTemplate, $studentTemplate, $outputFile) {
    error_log("--- [HELPER] generateClearanceTemplatePDF started ---");

    // Ensure there is a generation date
    if (empty($templateData['DATE_GENERATED'])) {
        $templateData['DATE_GENERATED'] = date('F j, Y — g:i A');
    }

    $tempFiles = [];
    $templatesToProcess = [
        'School Copy' => $schoolTemplate,
        'Student Copy' => $studentTemplate
    ];

    foreach ($templatesToProcess as $copyType => $templateFile) {
        error_log("-> Processing template: $copyType (" . basename($templateFile) . ")");

        if (!file_exists($templateFile)) {
            error_log("❌ Template not found: $templateFile");
            continue;
        }

        // 1️⃣ Fill the DOCX template
        $templateProcessor = new TemplateProcessor($templateFile);
        foreach ($templateData as $key => $value) {
            // TemplateProcessor expects placeholders without ${}, e.g. setValue('FULL_NAME', '...')
            $templateProcessor->setValue($key, htmlspecialchars($value, ENT_COMPAT, 'UTF-8'));
        }

        // 2️⃣ Handle signatories: send registrar to footer and clone only non-registrars
        $registrarSignatory = null;
        $nonRegistrarSignatories = [];

        if (!empty($signatories) && is_array($signatories)) {
            foreach ($signatories as $s) {
                if (isset($s['designation_name']) && stripos($s['designation_name'], 'Registrar') !== false) {
                    $registrarSignatory = $s;
                } else {
                    $nonRegistrarSignatories[] = $s;
                }
            }
        }

        // Clone rows for non-registrar signatories
        $count = count($nonRegistrarSignatories);
        if ($count > 0) {
            $templateProcessor->cloneRow('DESIGNATION_NAME', $count);

            $i = 1;
            foreach ($nonRegistrarSignatories as $signatory) {
                $templateProcessor->setValue("DESIGNATION_NAME#{$i}", htmlspecialchars($signatory['designation_name'] ?? '', ENT_COMPAT, 'UTF-8'));
                $templateProcessor->setValue("SIGNATORY_NAME#{$i}", htmlspecialchars($signatory['signatory_name'] ?? 'N/A', ENT_COMPAT, 'UTF-8'));
                $templateProcessor->setValue("SIGNATORY_ACTION#{$i}", htmlspecialchars($signatory['action'] ?? 'Unapplied', ENT_COMPAT, 'UTF-8'));
                $templateProcessor->setValue("DATE_SIGNED#{$i}", $signatory['date_signed'] ? date('M j, Y', strtotime($signatory['date_signed'])) : 'N/A');
                $i++;
            }
        } else {
            // No non-registrar signatories
            $templateProcessor->setValue('DESIGNATION_NAME', 'No signatories assigned.');
            $templateProcessor->setValue('SIGNATORY_NAME', '');
            $templateProcessor->setValue('SIGNATORY_ACTION', '');
            $templateProcessor->setValue('DATE_SIGNED', '');
        }

        // Set registrar name into footer placeholder
        // Use registrar from the signatories list if found
        if ($registrarSignatory && !empty($registrarSignatory['signatory_name'])) {
            $templateProcessor->setValue('REGISTRAR_NAME', htmlspecialchars($registrarSignatory['signatory_name'], ENT_COMPAT, 'UTF-8'));
        } 
        // Fallback to the official registrar name if provided in $templateData
        elseif (!empty($templateData['REGISTRAR_NAME'])) {
            $templateProcessor->setValue('REGISTRAR_NAME', htmlspecialchars($templateData['REGISTRAR_NAME'], ENT_COMPAT, 'UTF-8'));
        } 
        // Final fallback
        else {
            $templateProcessor->setValue('REGISTRAR_NAME', 'Registrar');
        }

        // 3️⃣ Save processed DOCX to a temp file
        $tempDocxPath = tempnam(sys_get_temp_dir(), 'clearance_') . '.docx';
        $templateProcessor->saveAs($tempDocxPath);
        if (!file_exists($tempDocxPath) || filesize($tempDocxPath) === 0) {
            error_log("❌ Failed to save processed DOCX: $tempDocxPath");
            @unlink($tempDocxPath);
            continue;
        }
        error_log("-> Saved processed DOCX: $tempDocxPath (" . filesize($tempDocxPath) . " bytes)");

        // 4️⃣ Load DOCX into PhpWord (for programmatic cleanup / HTML export)
        $phpWord = IOFactory::load($tempDocxPath);

        // 5️⃣ Option B: Remove borders from every table except the signatory table (detect by header text "Designation")
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $isSignatoryTable = false;

                    foreach ($element->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            foreach ($cell->getElements() as $cellElement) {
                                // TextRun or Text elements may hold text
                                if ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                    foreach ($cellElement->getElements() as $te) {
                                        if ($te instanceof \PhpOffice\PhpWord\Element\Text) {
                                            $txt = $te->getText();
                                            if (is_array($txt)) $txt = implode(' ', $txt);
                                            if (is_string($txt) && stripos($txt, 'Designation') !== false) {
                                                $isSignatoryTable = true;
                                                break 4;
                                            }
                                        }
                                    }
                                } elseif ($cellElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                    $txt = $cellElement->getText();
                                    if (is_array($txt)) $txt = implode(' ', $txt);
                                    if (is_string($txt) && stripos($txt, 'Designation') !== false) {
                                        $isSignatoryTable = true;
                                        break 4;
                                    }
                                }
                            }
                        }
                    }

                    if (!$isSignatoryTable) {
                        // Remove table style borders safely
                        if (method_exists($element, 'getStyle') && $element->getStyle()) {
                            $tableStyle = $element->getStyle();
                            if (method_exists($tableStyle, 'setBorderSize')) $tableStyle->setBorderSize(0);
                            if (method_exists($tableStyle, 'setBorderColor')) $tableStyle->setBorderColor('FFFFFF');
                        }
                        // Remove each cell's border style
                        foreach ($element->getRows() as $row) {
                            foreach ($row->getCells() as $cell) {
                                if (method_exists($cell, 'getStyle') && $cell->getStyle()) {
                                    $cellStyle = $cell->getStyle();
                                    if (method_exists($cellStyle, 'setBorderSize')) $cellStyle->setBorderSize(0);
                                    if (method_exists($cellStyle, 'setBorderColor')) $cellStyle->setBorderColor('FFFFFF');
                                }
                            }
                        }
                    }
                }
            }
        }

        // 6️⃣ Convert DOCX -> HTML (PhpWord HTML writer preserves tables/images much better)
        $tempHtmlPath = tempnam(sys_get_temp_dir(), 'html_') . '.html';
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($tempHtmlPath);
        if (!file_exists($tempHtmlPath) || filesize($tempHtmlPath) === 0) {
            error_log("❌ Failed to create HTML: $tempHtmlPath");
            @unlink($tempDocxPath);
            continue;
        }
        $htmlContent = file_get_contents($tempHtmlPath);

        // Clean up excessive blank paragraphs that cause large white spaces in PDF
        $htmlContent = preg_replace(
            '/(Registrar<\/p>)(?:\s*<p[^>]*>(&nbsp;|\s)*<\/p>)+/i',
            '$1',
            $htmlContent
        );

        // Also remove 3+ consecutive empty paragraphs anywhere in the document (general cleanup)
        $htmlContent = preg_replace(
            '/(<p[^>]*>(&nbsp;|\s)*<\/p>){3,}/i',
            '',
            $htmlContent
        );


        // 7️⃣ Build header (logo absolute path + header text + copy label)
        $logoPath = realpath(__DIR__ . '/../../assets/images/STI_Lucena_Logo.jpg');
        if (!$logoPath || !file_exists($logoPath)) {
            error_log("⚠️ Logo not found at: " . __DIR__ . '/../../assets/images/STI_Lucena_Logo.jpg');
            $logoFragment = '';
        } else {
            // Use the absolute path for TCPDF
            $logoFragment = '<img src="' . $logoPath . '" style="height:58px;" alt="STI Logo">';
        }

        $copyTypeLabel = ($copyType === 'School Copy') ? 'SCHOOL COPY' : 'STUDENT COPY';
        $headerHtml = '
        <table style="width:100%; border-bottom:1px solid #000; margin-bottom:12px;">
            <tr>
                <!-- Left: Logo -->
                <td style="width:25%; vertical-align:middle; text-align:left;">
                    <div style="display:flex; align-items:center;">
                        ' . ($logoFragment ? '<img src="' . $logoPath . '" style="height:55px; margin-right:5px; vertical-align:middle;" alt="STI Logo">' : '') . '
                    </div>
                </td>

                <!-- Center: School Name -->
                <td style="width:50%; text-align:center; vertical-align:middle;">
                    <div style="font-size:18pt; font-weight:bold; margin:0;">STI College Lucena</div>
                    <div style="font-size:12pt; margin:2px 0;">Clearance Form</div>
                </td>

                <!-- Right: Form ID + Copy Type -->
                <td style="width:25%; text-align:right; vertical-align:top; font-size:10pt;">
                    <div style="margin-bottom:4px;">Clearance Form ID: <b>' . htmlspecialchars($templateData['CLEARANCE_FORM_ID'] ?? 'N/A') . '</b></div>
                    <div style="font-weight:bold;">' . $copyTypeLabel . '</div>
                </td>
            </tr>
        </table>
        ';


        // Prepend header to the HTML content
        $htmlContent = $headerHtml . $htmlContent;

        // 8️⃣ Convert HTML -> PDF using TCPDF (explicit include ensures class exists)
        require_once Settings::getPdfRendererPath() . '/tcpdf.php';
        $pdfTempPath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('goSTI Clearance System');
        $pdf->SetAuthor('goSTI College');
        $pdf->SetTitle('Clearance Form');
        $pdf->SetMargins(15, 15, 15);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->writeHTML($htmlContent, true, false, true, false, '');
        $pdf->Output($pdfTempPath, 'F');

        // 9️⃣ Validate generated PDF and queue for merging
        if (file_exists($pdfTempPath) && filesize($pdfTempPath) > 1000) {
            $tempFiles[] = $pdfTempPath;
            error_log("✅ Generated PDF: $copyType (" . filesize($pdfTempPath) . " bytes)");
        } else {
            error_log("⚠️ PDF empty or missing for: $copyType");
            @unlink($pdfTempPath);
        }

        // Cleanup intermediate files
        @unlink($tempDocxPath);
        @unlink($tempHtmlPath);
    } // end foreach templates

    // If nothing generated, abort
    if (empty($tempFiles)) {
        error_log("[HELPER] ❌ No valid PDFs generated; aborting merge.");
        throw new Exception("No valid PDF pages were generated from the templates. The final PDF would be blank.");
    }

    // 10️⃣ Merge PDFs with FPDI
    $pdf = new Fpdi();
    foreach ($tempFiles as $file) {
        $pageCount = $pdf->setSourceFile($file);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplIdx = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplIdx);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplIdx);
        }
        @unlink($file);
    }

    // 11️⃣ Save final merged PDF
    $pdf->Output($outputFile, 'F');
    if (file_exists($outputFile) && filesize($outputFile) > 0) {
        error_log("✅ Final PDF created: $outputFile (size: " . filesize($outputFile) . " bytes)");
    } else {
        error_log("❌ Final PDF missing or empty at $outputFile");
        throw new Exception("Final PDF not created.");
    }
}
*/
