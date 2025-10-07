<?php
// Export Data Controller - Faculty export
// Ensure clean binary output and suppress HTML warnings in responses
if (function_exists('ini_set')) { @ini_set('display_errors','0'); @ini_set('log_errors','1'); }
if (function_exists('ob_get_level') && ob_get_level() === 0) { @ob_start(); }
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Expose-Headers: Content-Disposition');

require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';

// Helper: safe truncation without mbstring dependency
if (!function_exists('truncate_text_safe')) {
    function truncate_text_safe($text, $limit = 64) {
        $lengthFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $substrFn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        if ($lengthFn($text) > $limit) {
            return $substrFn($text, 0, max(0, $limit - 3)) . '...';
        }
        return $text;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}
if (!$auth->hasPermission('export_data')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$type = $_POST['type'] ?? '';
if ($type !== 'faculty_export') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid export type']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Active clearance period → academic year & semester
    $act = $pdo->query("SELECT academic_year_id, semester_id FROM clearance_periods WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $ayId = $act['academic_year_id'] ?? null;
    $semId = $act['semester_id'] ?? null;

    $exportFormat = strtolower($_POST['exportFormat'] ?? 'csv');
    if (!in_array($exportFormat, ['csv','json','pdf'])) { $exportFormat = 'csv'; }
    $exportScope  = $_POST['exportScope'] ?? 'all';
    $fileNameBase = preg_replace('/[^A-Za-z0-9_\-]/','_', ($_POST['fileName'] ?? 'faculty_data_export'));
    $includeHeaders = isset($_POST['includeHeaders']);
    $includeTimestamp = isset($_POST['includeTimestamp']);

    // Role prefix for filenames (e.g., ADMIN_...)
    $roleName = method_exists($auth,'getRoleName') ? ($auth->getRoleName() ?? 'User') : 'User';
    $rolePrefix = strtoupper(preg_replace('/[^A-Za-z0-9_\-]/','_', $roleName));

    // Column selection and order
    $allowedColumns = ['employee_number','name','employment_status','account_status','clearance_status','email','contact_number','school_term'];
    $labelsFull = [
        'employee_number'   => 'Employee Number',
        'name'              => 'Name',
        'employment_status' => 'Employment Status',
        'account_status'    => 'Account Status',
        'clearance_status'  => 'Clearance Progress Status',
        'email'             => 'Email',
        'contact_number'    => 'Contact Number',
        'school_term'       => 'School Year and Term'
    ];
    // Backward-compatible include flags
    $include = [
        'employee_number'   => isset($_POST['includeEmployeeNumber']),
        'employment_status' => isset($_POST['includeEmploymentStatus']),
        'name'              => isset($_POST['includeName']),
        'email'             => isset($_POST['includeEmail']),
        'contact_number'    => isset($_POST['includeContactNumber']),
        'account_status'    => isset($_POST['includeAccountStatus']),
        'clearance_status'  => isset($_POST['includeClearanceStatus']),
        'school_term'       => isset($_POST['includeSchoolTerm'])
    ];
    // Ordered columns coming from the modal (columns[] in DOM order)
    $columnsOrdered = [];
    if (isset($_POST['columns'])) {
        $cols = is_array($_POST['columns']) ? $_POST['columns'] : [];
        foreach ($cols as $c) {
            $k = trim((string)$c);
            if (in_array($k, $allowedColumns, true) && !in_array($k, $columnsOrdered, true)) {
                $columnsOrdered[] = $k;
            }
        }
    }
    // Fallback to a sane default if nothing provided
    if (empty($columnsOrdered)) {
        // if no include flags checked, default set
        if (!array_filter($include)) {
            $include['employee_number'] = $include['name'] = $include['employment_status'] = true;
            $include['account_status'] = $include['clearance_status'] = true;
        }
        // Build order from include flags in the visual checkbox order
        $visualOrder = ['employee_number','employment_status','name','email','contact_number','account_status','clearance_status','school_term'];
        foreach ($visualOrder as $k) { if (!empty($include[$k])) $columnsOrdered[] = $k; }
    }

    // Build WHERE based on scope/filters
    $where = [];
    $params = [];

    if ($exportScope === 'selected') {
        // Selected employee numbers from form (comma-separated or multiple values)
        $selected = [];
        if (!empty($_POST['selected'])) {
            // Accept either CSV string or array
            if (is_array($_POST['selected'])) { $selected = $_POST['selected']; }
            else { $selected = array_filter(array_map('trim', explode(',', $_POST['selected']))); }
        }
        if (empty($selected)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No selected employee numbers provided']);
            exit;
        }
        $in = implode(',', array_fill(0, count($selected), '?'));
        $where[] = "f.employee_number IN ($in)";
        $params = array_merge($params, $selected);
    } elseif ($exportScope === 'filtered') {
        // Optional filters passed in modal
        $empMap = [
            'full-time' => 'Full Time',
            'part-time' => 'Part Time',
            'part-time-full-load' => 'Part Time - Full Load',
            'Full Time' => 'Full Time',
            'Part Time' => 'Part Time',
            'Part Time - Full Load' => 'Part Time - Full Load'
        ];
        $employmentStatus = $_POST['employmentStatus'] ?? '';
        if ($employmentStatus !== '' && isset($empMap[$employmentStatus])) {
            $where[] = 'f.employment_status = ?';
            $params[] = $empMap[$employmentStatus];
        }
        $accountStatus = $_POST['accountStatus'] ?? '';
        if ($accountStatus !== '') {
            $where[] = 'u.account_status = ?';
            $params[] = $accountStatus;
        }
        // Tab status (from page) could also be passed; we honor modal filters first
        $tabStatus = $_POST['tabStatus'] ?? '';
        if ($tabStatus !== '') {
            $where[] = 'u.account_status = ?';
            $params[] = $tabStatus;
        }
        $clearance = $_POST['clearanceStatus'] ?? '';
        if ($clearance !== '' && $ayId && $semId) {
            // Map to UI labels stored in clearance_forms.status
            $map = [
                'unapplied'   => 'Unapplied',
                'pending'     => 'Pending',
                'in-progress' => 'In Progress',
                'completed'   => 'Completed',
                'rejected'    => 'Rejected'
            ];
            if (isset($map[$clearance])) {
                $where[] = 'COALESCE(NULLIF(cf.status,\'\'),\'Unapplied\') = ?';
                $params[] = $map[$clearance];
            }
        }
    }

    // Base select and joins
    $select = "SELECT f.employee_number, f.employment_status, u.first_name, u.last_name, u.middle_name, u.email, u.contact_number, u.account_status, COALESCE(NULLIF(cf.status,''),'Unapplied') AS clearance_status";
    $join   = " FROM faculty f JOIN users u ON u.user_id=f.user_id ";
    if ($ayId && $semId) {
        $join .= " LEFT JOIN clearance_forms cf ON cf.user_id=u.user_id AND cf.academic_year_id = ? AND cf.semester_id = ? ";
        array_unshift($params, $ayId, $semId); // prepend ay/sem for prepared order
    } else {
        $join .= " LEFT JOIN clearance_forms cf ON 1=0 "; // no active period; treat as Unapplied via COALESCE
    }

    $sql = $select . $join;
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY u.last_name, u.first_name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare school term label once for all formats
    $termLabel = '';
    if ($ayId && $semId) {
        try {
            $ay = $pdo->prepare('SELECT year FROM academic_years WHERE academic_year_id=?');
            $ay->execute([$ayId]);
            $year = $ay->fetchColumn();
            $sem = $pdo->prepare('SELECT semester_name FROM semesters WHERE semester_id=?');
            $sem->execute([$semId]);
            $semName = $sem->fetchColumn();
            $termLabel = trim(($year ?: '') . ' ' . ($semName ?: ''));
        } catch (Exception $e) {
            $termLabel = '';
        }
    }

    if ($exportFormat === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $rolePrefix . '_' . $fileNameBase . ($includeTimestamp?('_'.date('Ymd_Hi')):'') . '.json');
        echo json_encode(['success'=>true,'rows'=>$rows]);
        exit;
    }

    if ($exportFormat === 'pdf') {
        // Try to load FPDF if available
        $fpdfPath = __DIR__ . '/../includes/vendor/fpdf/fpdf.php';
        if (file_exists($fpdfPath)) {
            require_once $fpdfPath;
        }
        if (!class_exists('FPDF')) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'message'=>'PDF export requires FPDF (includes/vendor/fpdf/fpdf.php)']);
            exit;
        }

        // Page size (portrait only). Accept common aliases
        $sizeInput = strtoupper(trim((string)($_POST['pdfPageSize'] ?? 'A4')));
        $sizeMap = [
            'A3' => 'A3', 'A4' => 'A4', 'A5' => 'A5',
            'LETTER' => 'Letter', 'LEGAL' => 'Legal'
        ];
        $pageSize = $sizeMap[$sizeInput] ?? 'A4';

        $pdf = new FPDF('P','mm',$pageSize); // Portrait
        // Set explicit margins and use local copies for calculations to avoid touching protected props
        $leftMargin = 10; $topMargin = 10; $rightMargin = 10;
        $pdf->SetMargins($leftMargin, $topMargin, $rightMargin);
        $pdf->SetTitle('Faculty Report');
        $pdf->AddPage();
        $pageWidth = $pdf->GetPageWidth() - $leftMargin - $rightMargin;

        // Title row: goSTI (left) | Faculty Report (right)
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell($pageWidth/2,8,'goSTI',0,0,'L');
        $pdf->Cell($pageWidth/2,8,'Faculty Report',0,1,'R');
        $pdf->Ln(3);

        // Meta block: two half-width label/value boxes (Operator, Generated)
        $boxH = 12; // two lines with more breathing room
        $lineH = 4.2; // line height for 8pt
        $pdf->SetFont('Arial','B',8);
        // Operator box
        $x = $pdf->GetX(); $y = $pdf->GetY();
        $w = $pageWidth/2 - 1; // small gap between boxes
        $pdf->Rect($x, $y, $w, $boxH);
        $pdf->SetXY($x+2, $y+2);
        $pdf->Cell(0, $lineH, 'Operator:', 0, 1, 'L');
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($x+2, $y+2+$lineH);
        $pdf->Cell(0, $lineH, ucfirst(strtolower($rolePrefix)), 0, 0, 'L');

        // Generated box
        $pdf->SetFont('Arial','B',8);
        $x2 = $x + $w + 2; $y2 = $y; $w2 = $pageWidth - $w - 2; // fill remaining width
        $pdf->Rect($x2, $y2, $w2, $boxH);
        $pdf->SetXY($x2+2, $y2+2);
        $pdf->Cell(0, $lineH, 'Generated:', 0, 1, 'L');
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($x2+2, $y2+2+$lineH);
        $pdf->Cell(0, $lineH, date('Y-m-d, g:ia'), 0, 1, 'L');
        $pdf->Ln(4);

        // Stats row: 1x7 cells with two lines (label bold, value regular)
        $stats = [
            'Total' => count($rows),
            'Active' => 0,
            'Inactive' => 0,
            'Resigned' => 0,
            'FT' => 0,
            'PT' => 0,
            'PT-FL' => 0
        ];
        foreach ($rows as $rr) {
            $st = strtolower((string)$rr['account_status']);
            if ($st==='active') $stats['Active']++; elseif ($st==='inactive') $stats['Inactive']++; elseif ($st==='resigned') $stats['Resigned']++;
            $es = (string)$rr['employment_status'];
            if ($es==='Full Time') $stats['FT']++; elseif ($es==='Part Time') $stats['PT']++; elseif ($es==='Part Time - Full Load') $stats['PT-FL']++;
        }
        $cellW = $pageWidth / 7.0;
        $rowH = 9.5;
        $yStats = $pdf->GetY();
        $xStats = $pdf->GetX();
        $i = 0;
        foreach ($stats as $label => $val) {
            $x = $xStats + ($i * $cellW);
            // Draw cell border
            $pdf->Rect($x, $yStats, $cellW, $rowH);
            // Label (bold) centered
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY($x+1, $yStats+1);
            $pdf->Cell($cellW-2, 4, $label, 0, 2, 'C');
            // Value (regular) centered
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY($x+1, $yStats+1+4);
            $pdf->Cell($cellW-2, 4, (string)$val, 0, 0, 'C');
            $i++;
        }
        $pdf->SetY($yStats + $rowH + 3);

        // School Year and Term bar (single full-width bordered cell with mixed styles)
        $termText = ($termLabel !== '') ? $termLabel : 'N/A';
        $barH = 7;
        $xBar = $pdf->GetX(); $yBar = $pdf->GetY();
        $pdf->Rect($xBar, $yBar, $pageWidth, $barH);
        $pdf->SetXY($xBar+2, $yBar+1.5);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(0, 4, 'School Year and Term: ', 0, 0, 'L');
        $wLabel = $pdf->GetStringWidth('School Year and Term: ');
        $pdf->SetXY($xBar+2+$wLabel, $yBar+1.5);
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(0, 4, $termText, 0, 1, 'L');
        $pdf->Ln(3);

        // Simple summary
        $tot = count($rows);
        $active = 0; $inactive = 0; $resigned = 0; $ft=0; $pt=0; $ptfl=0;
        foreach ($rows as $r) {
            $st = strtolower((string)$r['account_status']);
            if ($st==='active') $active++; elseif ($st==='inactive') $inactive++; elseif ($st==='resigned') $resigned++;
            $es = (string)$r['employment_status'];
            if ($es==='Full Time') $ft++; elseif ($es==='Part Time') $pt++; elseif ($es==='Part Time - Full Load') $ptfl++;
        }
        // Main table

        // Table header and dynamic columns (measured widths)
        $pdf->SetFont('Arial','B',9);
        $availableWidth = $pdf->GetPageWidth() - $leftMargin - $rightMargin;

        // Build column definitions using ordered columns from request
        $defs = [];
        $labelShort = [
            'employee_number'=>'Employee Number',
            'name'=>'Name',
            'employment_status'=>'Employment Status',
            'account_status'=>'Account Status',
            'clearance_status'=>'Clearance Progress Status',
            'email'=>'Email',
            'contact_number'=>'Contact Number',
            'school_term'=>'School Year and Term'
        ];
        foreach ($columnsOrdered as $k) {
            $defs[] = ['key'=>$k, 'label'=>$labelShort[$k] ?? ucfirst(str_replace('_',' ', $k))];
        }

        // Measure widths using header and sample data
        $paddingH = 2.0; // horizontal padding on each side
        $pdf->SetFont('Arial','',8);
        $maxTextWidths = [];
        foreach ($defs as $idx => $d) {
            $maxTextWidths[$idx] = $pdf->GetStringWidth($d['label']);
        }
        $sampleCount = min(50, count($rows));
        for ($i=0; $i<$sampleCount; $i++) {
            $r = $rows[$i];
            $colIdx = 0;
            foreach ($defs as $d) {
                $text = '';
                switch ($d['key']) {
                    case 'employee_number': $text = (string)$r['employee_number']; break;
                    case 'name':
                        $mn = trim((string)($r['middle_name'] ?? ''));
                        $text = trim($r['last_name'] . ', ' . $r['first_name'] . ($mn?(' '.$mn):''));
                        break;
                    case 'employment_status': $text = (string)$r['employment_status']; break;
                    case 'account_status': $text = ucfirst(strtolower((string)$r['account_status'])); break;
                    case 'clearance_status': $text = (string)$r['clearance_status']; break;
                    case 'email': $text = (string)$r['email']; break;
                    case 'contact_number': $text = (string)$r['contact_number']; break;
                    case 'school_term': $text = $termLabel; break;
                }
                $w = $pdf->GetStringWidth(truncate_text_safe($text, 128));
                if ($w > $maxTextWidths[$colIdx]) { $maxTextWidths[$colIdx] = $w; }
                $colIdx++;
            }
        }
        // Convert text widths to cell widths with padding and caps
        $preferred = [];
        $sum = 0.0;
        foreach ($defs as $idx => $d) {
            $base = $maxTextWidths[$idx] + 2*$paddingH;
            $minW = 10.0;
            $maxW = in_array($d['key'], ['name','email']) ? 60.0 : (in_array($d['key'], ['employee_number','employment_status']) ? 35.0 : 28.0);
            if ($d['key']==='school_term') { $maxW = 22.0; }
            $w = max($minW, min($maxW, $base));
            $preferred[$idx] = $w; $sum += $w;
        }
        // Scale to fit available width
        $columns = [];
        $assigned = 0.0;
        $countDefs = count($defs);
        for ($i=0; $i<$countDefs; $i++) {
            $isLast = ($i === $countDefs - 1);
            if ($sum <= 0) { $w = $availableWidth / max(1,$countDefs); }
            else {
                $w = $isLast ? ($availableWidth - $assigned) : round($availableWidth * ($preferred[$i] / $sum), 2);
            }
            if ($w < 10.0) { $w = 10.0; }
            $assigned += $isLast ? 0 : $w;
            $columns[] = [$defs[$i]['label'], $w, $defs[$i]['key']];
        }

        // Helper: compute number of lines needed for a text in given width (word-wrap approximation)
        $computeLines = function($text, $w) use ($pdf) {
            $text = (string)$text;
            if ($text === '') return 1;
            $words = preg_split('/\s+/', $text);
            $lines = 1; $current = '';
            foreach ($words as $word) {
                $candidate = ($current === '') ? $word : ($current . ' ' . $word);
                if ($pdf->GetStringWidth($candidate) <= ($w - 2)) { // padding approximation
                    $current = $candidate;
                } else {
                    $lines++; $current = $word;
                }
            }
            return max(1, $lines);
        };

        // Header row with wrapping
        $pdf->SetFont('Arial','B',8);
        $headerLineH = 4.0;
        $headerLines = [];
        $maxHeaderLines = 1;
        foreach ($columns as $c) {
            $text = (string)$c[0];
            $lines = $computeLines($text, $c[1]);
            if ($lines > 2) $lines = 2; // cap header to 2 lines
            $headerLines[] = $lines;
            if ($lines > $maxHeaderLines) $maxHeaderLines = $lines;
        }
        $headerHeight = $maxHeaderLines * $headerLineH + 2;
        $yHdr = $pdf->GetY();
        for ($i=0; $i<count($columns); $i++) {
            $x = $pdf->GetX();
            $w = $columns[$i][1];
            // Draw cell border
            $pdf->Rect($x, $yHdr, $w, $headerHeight);
            // Write centered label with wrapping
            $pdf->SetXY($x+1, $yHdr+1);
            $pdf->MultiCell($w-2, $headerLineH, (string)$columns[$i][0], 0, 'C');
            $pdf->SetXY($x + $w, $yHdr);
        }
        $pdf->SetY($yHdr + $headerHeight);

        // Rows
        $pdf->SetFont('Arial','',8);
        $lineHeight = 4.8; // slightly taller to avoid touching borders on wrapped lines

        // Helper: compute number of lines needed for a text in given width
        $computeLines = function($text, $w) use ($pdf, $lineHeight) {
            $text = (string)$text;
            if ($text === '') return 1;
            // Rough wrap by words using GetStringWidth
            $words = preg_split('/\s+/', $text);
            $lines = 1; $current = '';
            foreach ($words as $word) {
                $candidate = ($current === '') ? $word : ($current . ' ' . $word);
                if ($pdf->GetStringWidth($candidate) <= ($w - 4)) { // match inner width used by MultiCell
                    $current = $candidate;
                } else {
                    $lines++; $current = $word;
                }
            }
            return max(1, $lines);
        };

        // Helper: print header again on page breaks
        $printHeader = function() use ($pdf, $columns, $headerHeight) {
            $pdf->SetFont('Arial','B',9);
            foreach ($columns as $c) { $pdf->Cell($c[1], $headerHeight, $c[0], 1, 0, 'C'); }
            $pdf->Ln();
            $pdf->SetFont('Arial','',8);
        };

        foreach ($rows as $r) {
            // Build line in column-key order
            $line = [];
            foreach ($columns as $c) {
                $key = $c[2];
                switch ($key) {
                    case 'employee_number': $line[] = $r['employee_number']; break;
                    case 'name':
                        $mn = trim((string)($r['middle_name'] ?? ''));
                        $line[] = trim($r['last_name'] . ', ' . $r['first_name'] . ($mn?(' '.$mn):''));
                        break;
                    case 'employment_status': $line[] = $r['employment_status']; break;
                    case 'account_status':    $line[] = ucfirst(strtolower($r['account_status'])); break;
                    case 'clearance_status':  $line[] = $r['clearance_status']; break;
                    case 'email':             $line[] = (string)$r['email']; break;
                    case 'contact_number':    $line[] = (string)$r['contact_number']; break;
                    case 'school_term':       $line[] = $termLabel; break;
                }
            }

            // Determine row height by max lines among cells
            $maxLines = 1;
            for ($i=0; $i<count($columns); $i++) {
                $t = truncate_text_safe((string)($line[$i] ?? ''), 256);
                $w = $columns[$i][1];
                $lines = $computeLines($t, $w);
                if ($lines > 2 && in_array($columns[$i][2], ['name','email'])) { $lines = 2; } // cap to 2 lines
                if ($lines > $maxLines) $maxLines = $lines;
            }
            $rowHeight = $maxLines * $lineHeight + 5; // extra padding to prevent overlap with borders

            // Page break check
            if ($pdf->GetY() + $rowHeight > ($pdf->GetPageHeight() - $topMargin)) {
                $pdf->AddPage();
                $printHeader();
            }

            // Draw row cells with borders and wrapped text
            $yRow = $pdf->GetY();
            for ($i=0; $i<count($columns); $i++) {
                $x = $pdf->GetX();
                $w = $columns[$i][1];
                $text = truncate_text_safe((string)($line[$i] ?? ''), 256);
                // Border rectangle
                $pdf->Rect($x, $yRow, $w, $rowHeight);
                // Text with small inner padding
                $pdf->SetXY($x + 2, $yRow + 2);
                $pdf->MultiCell($w - 4, $lineHeight, $text, 0, 'L');
                // Move to next cell position
                $pdf->SetXY($x + $w, $yRow);
            }
            // Move cursor to next row
            $pdf->SetY($yRow + $rowHeight);
        }

        // Stream output (send exact bytes)
        $outName = $rolePrefix . '_' . $fileNameBase . ($includeTimestamp?('_'.date('Ymd_Hi')):'') . '.pdf';
        if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
        $content = $pdf->Output('S');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $outName . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    // CSV export (respect ordered columns)
    $filename = $rolePrefix . '_' . $fileNameBase . ($includeTimestamp?('_'.date('Ymd_Hi')):'') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fwrite($out, "\xEF\xBB\xBF");

    // Build header based on ordered columns
    $labelCsv = [
        'employee_number'=>'Employee Number',
        'name'=>'Full Name',
        'employment_status'=>'Employment Status',
        'account_status'=>'Account Status',
        'clearance_status'=>'Clearance Status',
        'email'=>'Email',
        'contact_number'=>'Contact Number',
        'school_term'=>'School Term'
    ];
    $header = [];
    foreach ($columnsOrdered as $k) { $header[] = $labelCsv[$k] ?? ucfirst(str_replace('_',' ',$k)); }
    if ($includeHeaders) fputcsv($out, $header);

    // Reuse previously computed $termLabel from earlier section

    foreach ($rows as $r) {
        $line = [];
        foreach ($columnsOrdered as $k) {
            switch ($k) {
                case 'employee_number': $line[] = $r['employee_number']; break;
                case 'name':
                    $mn = trim((string)($r['middle_name'] ?? ''));
                    $line[] = trim($r['last_name'] . ', ' . $r['first_name'] . ($mn?(' '.$mn):''));
                    break;
                case 'employment_status': $line[] = $r['employment_status']; break;
                case 'account_status': $line[] = ucfirst(strtolower($r['account_status'])); break;
                case 'clearance_status': $line[] = $r['clearance_status']; break;
                case 'email': $line[] = $r['email']; break;
                case 'contact_number': $line[] = $r['contact_number']; break;
                case 'school_term': $line[] = $termLabel; break;
            }
        }
        fputcsv($out, $line);
    }

    fclose($out);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

?>

