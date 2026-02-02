<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once '../../includes/config/database.php';
    require_once '../../includes/classes/Auth.php';
    $auth = new Auth();
    if (!$auth->isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit; }

    try {
        $pdo = Database::getInstance()->getConnection();
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
        $includeTerms = isset($_GET['include_terms']) && (int)$_GET['include_terms'] === 1;

        $yearsStmt = $pdo->prepare("SELECT academic_year_id, year, is_active, created_at, updated_at FROM academic_years ORDER BY year DESC LIMIT ?");
        $yearsStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $yearsStmt->execute();
        $years = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        if ($includeTerms && $years) {
            $ayIds = array_column($years, 'academic_year_id');
            $in = implode(',', array_fill(0, count($ayIds), '?'));
            $semStmt = $pdo->prepare("SELECT semester_id, academic_year_id, semester_name, is_active FROM semesters WHERE academic_year_id IN ($in) ORDER BY semester_name ASC");
            $semStmt->execute($ayIds);
            $semRows = $semStmt->fetchAll(PDO::FETCH_ASSOC);
            $ayIdToTerms = [];
            foreach ($semRows as $row) {
                $ayIdToTerms[$row['academic_year_id']][] = $row;
            }
        }

        foreach ($years as $y) {
            $item = [
                'academic_year_id' => (int)$y['academic_year_id'],
                'year' => $y['year'],
                'is_active' => (int)$y['is_active'],
                'created_at' => $y['created_at'],
                'updated_at' => $y['updated_at'],
            ];
            if (!empty($ayIdToTerms[$y['academic_year_id']])) {
                $item['semesters'] = $ayIdToTerms[$y['academic_year_id']];
            }
            $result[] = $item;
        }

        echo json_encode(['success' => true, 'years' => $result, 'total' => count($result)]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
    }
    exit;
}
// Handle DELETE: delete an academic year with guards
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_once '../../includes/config/database.php';
    require_once '../../includes/classes/Auth.php';
    $auth = new Auth();
    if (!$auth->isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit; }

    try {
        $pdo = Database::getInstance()->getConnection();
        // Accept id via query string or JSON body
        $ayId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($ayId <= 0) {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input && isset($input['id'])) { $ayId = (int)$input['id']; }
        }
        if ($ayId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'academic_year id is required']); exit; }

        // Verify year exists
        $stmt = $pdo->prepare('SELECT academic_year_id, year FROM academic_years WHERE academic_year_id = ?');
        $stmt->execute([$ayId]);
        $year = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$year) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'School year not found']); exit; }

        // Guard 1: All periods in this AY must be Ended (or no periods exist)
        // A period is considered ended if it has ended_at timestamp OR status is 'ended'
        $notEnded = $pdo->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND ended_at IS NULL AND status <> 'ended'");
        $notEnded->execute([$ayId]);
        if ((int)$notEnded->fetchColumn() > 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Cannot delete year: all terms must be Ended']); exit; }

        // Guard 2: No applications tied to periods in this AY
        $apps = $pdo->prepare('SELECT COUNT(*) FROM clearance_applications WHERE period_id IN (SELECT period_id FROM clearance_periods WHERE academic_year_id = ?)');
        $apps->execute([$ayId]);
        if ((int)$apps->fetchColumn() > 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Cannot delete year: there are clearance applications for this year']); exit; }

        $pdo->beginTransaction();
        // Delete periods first (should be ended only by guard)
        $pdo->prepare('DELETE FROM clearance_periods WHERE academic_year_id = ?')->execute([$ayId]);
        // Delete semesters
        $pdo->prepare('DELETE FROM semesters WHERE academic_year_id = ?')->execute([$ayId]);
        // Delete academic year
        $pdo->prepare('DELETE FROM academic_years WHERE academic_year_id = ?')->execute([$ayId]);
        $pdo->commit();

        echo json_encode(['success'=>true,'message'=>'School year deleted']);
    } catch (Throwable $e) {
        if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Authentication required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);
    $year = isset($input['year']) ? trim($input['year']) : '';
    if (!preg_match('/^\d{4}-\d{4}$/', $year)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Invalid year format. Use YYYY-YYYY']);
        exit;
    }

    // validate that end = start+1 (optional strictness)
    [$y1, $y2] = array_map('intval', explode('-', $year));
    if ($y2 !== $y1 + 1) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Year must be consecutive (e.g., 2025-2026)']);
        exit;
    }

    $pdo->beginTransaction();

    // Check uniqueness
    $stmt = $pdo->prepare('SELECT academic_year_id FROM academic_years WHERE year = ? LIMIT 1');
    $stmt->execute([$year]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success'=>false,'message'=>'School year already exists']);
        exit;
    }

    // Guard: disallow creating a new year if current year has any term not ended
    $curr = $pdo->query("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($curr) {
        $currAyId = (int)$curr['academic_year_id'];
        $notEnded = $pdo->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND status IN ('active','deactivated')");
        $notEnded->execute([$currAyId]);
        if ((int)$notEnded->fetchColumn() > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Cannot create a new school year while the current year has a term not ended. End the active/deactivated term(s) first.']);
            exit;
        }
    }

    // Deactivate previous current year
    $pdo->exec("UPDATE academic_years SET is_active = 0 WHERE is_active = 1");

    // Create new academic year as current
    $insAy = $pdo->prepare('INSERT INTO academic_years (year, is_active, created_at, updated_at) VALUES (?, 1, NOW(), NOW())');
    $insAy->execute([$year]);
    $ayId = (int)$pdo->lastInsertId();

    // Create two semesters: '1st' and '2nd' (inactive)
    $insSem = $pdo->prepare('INSERT INTO semesters (semester_name, academic_year_id, is_active, is_generation, created_at, updated_at) VALUES (?, ?, 0, 0, NOW(), NOW())');
    $insSem->execute(['1st', $ayId]);
    $sem1Id = (int)$pdo->lastInsertId();
    $insSem->execute(['2nd', $ayId]);
    $sem2Id = (int)$pdo->lastInsertId();

    // ============================================
    // YEAR LEVEL INCREMENT LOGIC
    // ============================================
    // Increment year levels for all active students, except:
    // 1. Students with retain_year_level_for_next_year = TRUE (they keep their current year level)
    // 2. Students with account_status = 'graduated' (they are excluded)
    
    // Year level mapping for increment
    $yearLevelMap = [
        '1st Year' => '2nd Year',
        '2nd Year' => '3rd Year',
        '3rd Year' => '4th Year',
        // Note: 4th Year students should have been marked as graduated before creating new year
        // But if any remain, they won't be incremented (they'll stay at 4th Year)
    ];
    
    // Get all active students (not graduated) who are not retained
    $studentsToIncrement = $pdo->prepare("
        SELECT s.student_id, s.user_id, s.year_level, s.sector, u.first_name, u.last_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE u.account_status = 'active'
        AND (s.retain_year_level_for_next_year = FALSE OR s.retain_year_level_for_next_year IS NULL)
        AND s.year_level IN ('1st Year', '2nd Year', '3rd Year')
    ");
    $studentsToIncrement->execute();
    $students = $studentsToIncrement->fetchAll(PDO::FETCH_ASSOC);
    
    $incrementedCount = 0;
    $retainedCount = 0;
    
    foreach ($students as $student) {
        $currentYearLevel = $student['year_level'];
        
        // Check if this year level should be incremented
        if (isset($yearLevelMap[$currentYearLevel])) {
            $newYearLevel = $yearLevelMap[$currentYearLevel];
            
            // Update student's year level
            $updateStmt = $pdo->prepare("
                UPDATE students 
                SET year_level = ?, updated_at = NOW()
                WHERE student_id = ?
            ");
            $updateStmt->execute([$newYearLevel, $student['student_id']]);
            $incrementedCount++;
            
            // Log the year level increment
            $logStmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                VALUES (?, 'year_level_incremented', ?, ?, ?)
            ");
            $logStmt->execute([
                $student['user_id'],
                json_encode([
                    'action' => 'year_level_incremented',
                    'student_id' => $student['student_id'],
                    'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                    'old_year_level' => $currentYearLevel,
                    'new_year_level' => $newYearLevel,
                    'sector' => $student['sector'],
                    'academic_year' => $year,
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    }
    
    // Get count of retained students (for logging)
    $retainedStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE u.account_status = 'active'
        AND s.retain_year_level_for_next_year = TRUE
    ");
    $retainedStmt->execute();
    $retainedCount = (int)$retainedStmt->fetchColumn();
    
    // Reset retain_year_level_for_next_year flag for ALL students (both retained and non-retained)
    // This ensures the flag is cleared for the next school year cycle
    $resetRetentionStmt = $pdo->prepare("
        UPDATE students 
        SET retain_year_level_for_next_year = FALSE, updated_at = NOW()
        WHERE retain_year_level_for_next_year = TRUE
    ");
    $resetRetentionStmt->execute();
    $resetCount = $resetRetentionStmt->rowCount();
    
    // Log the year level increment process
    $adminLogStmt = $pdo->prepare("
        INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
        VALUES (?, 'year_level_bulk_increment', ?, ?, ?)
    ");
    $adminLogStmt->execute([
        $auth->getUserId(),
        json_encode([
            'action' => 'year_level_bulk_increment',
            'academic_year' => $year,
            'incremented_count' => $incrementedCount,
            'retained_count' => $retainedCount,
            'retention_flags_reset' => $resetCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'academic_year' => [
            'academic_year_id' => $ayId,
            'year' => $year,
            'is_active' => 1
        ],
        'semesters' => [
            ['semester_id' => $sem1Id, 'semester_name' => '1st'],
            ['semester_id' => $sem2Id, 'semester_name' => '2nd']
        ],
        'year_level_increment' => [
            'incremented_count' => $incrementedCount,
            'retained_count' => $retainedCount,
            'retention_flags_reset' => $resetCount
        ]
    ]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}
?>


