<?php
/**
 * Clearance Periods for Export API
 * Returns clearance periods available for export based on user role
 * Only includes periods where status = 'closed' in clearance_periods table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $userId = $auth->getUserId();
    $roleName = $auth->getRoleName() ?? 'Guest';
    $roleNorm = strtolower($roleName);
    
    // Debug logging
    error_log("[periods_for_export] User ID: $userId, Role: $roleName ($roleNorm)");
    
    // Build query based on role
    if ($roleNorm === 'admin') {
        // Admin: Get all closed periods
        $sql = "
            SELECT DISTINCT
                ay.year as academic_year,
                s.semester_name,
                cp.period_id
            FROM clearance_periods cp
            INNER JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cp.semester_id = s.semester_id
            WHERE cp.status = 'closed'
            ORDER BY ay.year DESC, 
                     CASE s.semester_name 
                         WHEN '1st' THEN 1 
                         WHEN '2nd' THEN 2 
                         ELSE 3 
                     END ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } elseif ($roleNorm === 'school administrator' || $roleNorm === 'regular staff') {
        // Get periods where user has signatory assignments OR where clearance forms exist
        $sql = "
            SELECT DISTINCT
                ay.year as academic_year,
                s.semester_name,
                cp.period_id
            FROM clearance_periods cp
            INNER JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cp.semester_id = s.semester_id
            WHERE cp.status = 'closed'
            AND (
                -- Periods where user has signatory assignments
                EXISTS (
                    SELECT 1 FROM sector_signatory_assignments ssa
                    WHERE ssa.user_id = ? 
                    AND ssa.is_active = 1
                    AND (
                        (cp.sector = 'College' OR cp.sector = 'Senior High School')
                        OR (cp.sector = 'Faculty' AND EXISTS (
                            SELECT 1 FROM clearance_forms cf2
                            INNER JOIN faculty f ON cf2.user_id = f.user_id
                            WHERE cf2.academic_year_id = cp.academic_year_id
                            AND cf2.semester_id = cp.semester_id
                            AND f.department_id IN (
                                SELECT DISTINCT ssa2.department_id 
                                FROM sector_signatory_assignments ssa2
                                WHERE ssa2.user_id = ?
                                AND ssa2.is_active = 1
                            )
                        ))
                    )
                )
                OR
                -- Periods that have clearance forms (historical periods)
                EXISTS (
                    SELECT 1 FROM clearance_forms cf
                    WHERE cf.academic_year_id = cp.academic_year_id
                    AND cf.semester_id = cp.semester_id
                    AND cf.clearance_type = cp.sector
                )
            )
            ORDER BY ay.year DESC, 
                     CASE s.semester_name 
                         WHEN '1st' THEN 1 
                         WHEN '2nd' THEN 2 
                         ELSE 3 
                     END ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
    } elseif ($roleNorm === 'program head') {
        // Program Head: Show all closed periods for College/SHS where they have signatory assignments
        // Simplified logic: Show closed periods where Program Head has assignments
        // Department filtering happens during report generation, not here
        $sql = "
            SELECT DISTINCT
                ay.year as academic_year,
                s.semester_name,
                cp.period_id
            FROM clearance_periods cp
            INNER JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cp.semester_id = s.semester_id
            WHERE cp.status = 'closed'
            AND (cp.sector = 'College' OR cp.sector = 'Senior High School')
            AND EXISTS (
                SELECT 1 FROM sector_signatory_assignments ssa
                WHERE ssa.user_id = ?
                AND ssa.is_active = 1
                AND ssa.clearance_type IN ('College', 'Senior High School')
            )
            ORDER BY ay.year DESC, 
                     CASE s.semester_name 
                         WHEN '1st' THEN 1 
                         WHEN '2nd' THEN 2 
                         ELSE 3 
                     END ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        /* OLD CODE - Commented out for reference (may be useful for future file format implementations)
        // Program Head: Only periods for their assigned department (College/SHS only)
        // This version required clearance forms to exist, which was too restrictive
        $sql = "
            SELECT DISTINCT
                ay.year as academic_year,
                s.semester_name,
                cp.period_id
            FROM clearance_periods cp
            INNER JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cp.semester_id = s.semester_id
            WHERE cp.status = 'closed'
            AND (cp.sector = 'College' OR cp.sector = 'Senior High School')
            AND EXISTS (
                SELECT 1 FROM clearance_forms cf
                INNER JOIN students st ON cf.user_id = st.user_id
                WHERE cf.academic_year_id = cp.academic_year_id
                AND cf.semester_id = cp.semester_id
                AND cf.clearance_type = cp.sector
                AND st.department_id IN (
                    SELECT DISTINCT ssa.department_id
                    FROM sector_signatory_assignments ssa
                    WHERE ssa.user_id = ?
                    AND ssa.is_active = 1
                )
            )
            ORDER BY ay.year DESC, 
                     CASE s.semester_name 
                         WHEN '1st' THEN 1 
                         WHEN '2nd' THEN 2 
                         ELSE 3 
                     END ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        */
    } else {
        // Default: Get periods from user's own clearance forms
        $sql = "
            SELECT DISTINCT
                ay.year as academic_year,
                s.semester_name,
                cp.period_id
            FROM clearance_forms cf
            INNER JOIN clearance_periods cp ON cf.academic_year_id = cp.academic_year_id 
                AND cf.semester_id = cp.semester_id 
                AND cf.clearance_type = cp.sector
            INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cf.semester_id = s.semester_id
            WHERE cp.status = 'closed'
            AND cf.user_id = ?
            ORDER BY ay.year DESC, 
                     CASE s.semester_name 
                         WHEN '1st' THEN 1 
                         WHEN '2nd' THEN 2 
                         ELSE 3 
                     END ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("[periods_for_export] Raw periods fetched: " . count($periods));
    
    // Format periods for frontend
    $formattedPeriods = [];
    foreach ($periods as $period) {
        if (!empty($period['academic_year']) && !empty($period['semester_name'])) {
            // Skip Summer semester (as per user requirement)
            if (strtolower($period['semester_name']) === 'summer') {
                continue;
            }
            $formattedPeriods[] = [
                'value' => $period['academic_year'] . '|' . $period['semester_name'],
                'label' => $period['academic_year'] . ' - ' . $period['semester_name'],
                'academic_year' => $period['academic_year'],
                'semester_name' => $period['semester_name'],
                'period_id' => $period['period_id'] ?? null
            ];
        }
    }
    
    // Remove duplicates by value (academic_year|semester_name)
    $seen = [];
    $formattedPeriods = array_filter($formattedPeriods, function($p) use (&$seen) {
        $key = $p['value'];
        if (isset($seen[$key])) {
            return false;
        }
        $seen[$key] = true;
        return true;
    });
    $formattedPeriods = array_values($formattedPeriods);
    
    error_log("[periods_for_export] Formatted periods: " . count($formattedPeriods));
    if (count($formattedPeriods) > 0) {
        error_log("[periods_for_export] First period: " . json_encode($formattedPeriods[0]));
    }
    
    $response = [
        'success' => true,
        'periods' => $formattedPeriods,
        'count' => count($formattedPeriods),
        'debug' => [
            'role' => $roleName,
            'role_normalized' => $roleNorm,
            'user_id' => $userId,
            'raw_count' => count($periods)
        ]
    ];
    
    error_log("[periods_for_export] Response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Periods for Export API Error: " . $e->getMessage());
    error_log("Periods for Export API Stack Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'periods' => [],
        'debug' => [
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
}

