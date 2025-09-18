<?php
/**
 * Clearance Term Status API
 * Returns the current status of clearance terms/periods
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get current academic year and terms with sector support
    $sql = "
        SELECT 
            ay.academic_year_id,
            ay.year as school_year,
            s.semester_id,
            s.semester_name
        FROM academic_years ay
        LEFT JOIN semesters s ON ay.academic_year_id = s.academic_year_id
        WHERE ay.is_active = 1
        ORDER BY s.semester_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $termStatus = [
        'has_active_term' => false,
        'active_terms' => [],
        'terms' => [],
        'academic_year' => null,
        'sector_status' => []
    ];
    
    if (count($results) > 0) {
        $termStatus['academic_year'] = [
            'academic_year_id' => $results[0]['academic_year_id'],
            'school_year' => $results[0]['school_year']
        ];
        
        // Get sector-specific periods for each term
        foreach ($results as $row) {
            $sectorPeriodsSql = "
                SELECT 
                    p.period_id,
                    p.sector,
                    p.status as period_status,
                    p.start_date,
                    p.end_date,
                    p.ended_at
                FROM clearance_periods p
                WHERE p.academic_year_id = ? AND p.semester_id = ?
                ORDER BY p.sector
            ";
            
            $sectorStmt = $pdo->prepare($sectorPeriodsSql);
            $sectorStmt->execute([$row['academic_year_id'], $row['semester_id']]);
            $sectorPeriods = $sectorStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $term = [
                'semester_id' => $row['semester_id'],
                'semester_name' => $row['semester_name'],
                'sector_periods' => $sectorPeriods
            ];
            
            // Group periods by sector
            $sectors = ['College', 'Senior High School', 'Faculty'];
            foreach ($sectors as $sector) {
                $sectorPeriod = null;
                foreach ($sectorPeriods as $period) {
                    if ($period['sector'] === $sector) {
                        $sectorPeriod = $period;
                        break;
                    }
                }
                
                if (!$sectorPeriod) {
                    $sectorPeriod = [
                        'period_id' => null,
                        'sector' => $sector,
                        'status' => 'Not Started',
                        'start_date' => null,
                        'end_date' => null,
                        'ended_at' => null
                    ];
                }
                
                $term['sectors'][$sector] = $sectorPeriod;
                
                // Check if any sector is active
                if ($sectorPeriod['status'] === 'Ongoing') {
                    $termStatus['has_active_term'] = true;
                    $termStatus['active_terms'][] = [
                        'semester_id' => $row['semester_id'],
                        'semester_name' => $row['semester_name'],
                        'sector' => $sector,
                        'period_id' => $sectorPeriod['period_id'],
                        'status' => $sectorPeriod['status'],
                        'start_date' => $sectorPeriod['start_date'],
                        'end_date' => $sectorPeriod['end_date']
                    ];
                }
            }
            
            $termStatus['terms'][] = $term;
        }
        
        // Build sector status summary
        $termStatus['sector_status'] = [
            'College' => ['status' => 'Not Started', 'periods' => 0],
            'Senior High School' => ['status' => 'Not Started', 'periods' => 0],
            'Faculty' => ['status' => 'Not Started', 'periods' => 0]
        ];
        
        foreach ($termStatus['terms'] as $term) {
            foreach ($term['sectors'] as $sector => $period) {
                if ($period['period_id']) {
                    $termStatus['sector_status'][$sector]['periods']++;
                    
                    // Update overall status for sector
                    if ($period['status'] === 'Ongoing') {
                        $termStatus['sector_status'][$sector]['status'] = 'Ongoing';
                    } elseif ($period['status'] === 'Closed' && $termStatus['sector_status'][$sector]['status'] === 'Not Started') {
                        $termStatus['sector_status'][$sector]['status'] = 'Closed';
                    } elseif ($period['status'] === 'Paused' && $termStatus['sector_status'][$sector]['status'] !== 'Ongoing') {
                        $termStatus['sector_status'][$sector]['status'] = 'Paused';
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'term_status' => $termStatus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
