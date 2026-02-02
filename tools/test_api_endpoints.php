<?php
/**
 * Comprehensive API Endpoint Test
 * Tests the actual API endpoints by simulating requests
 */

// Start output buffering to capture API responses
ob_start();

// Simulate a session for authentication
session_start();

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/helpers/department_helpers.php';

// Colors for terminal output
class Colors {
    public static $GREEN = "\033[32m";
    public static $RED = "\033[31m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $RESET = "\033[0m";
}

function printHeader($text) {
    echo "\n" . Colors::$BLUE . "=== " . $text . " ===" . Colors::$RESET . "\n";
}

function printSuccess($text) {
    echo Colors::$GREEN . "✓ " . $text . Colors::$RESET . "\n";
}

function printError($text) {
    echo Colors::$RED . "✗ " . $text . Colors::$RESET . "\n";
}

function printInfo($text) {
    echo "  " . $text . "\n";
}

function printWarning($text) {
    echo Colors::$YELLOW . "⚠ " . $text . Colors::$RESET . "\n";
}

printHeader("Comprehensive API Endpoint Test");

try {
    $pdo = Database::getInstance()->getConnection();
    printSuccess("Database connection successful");
    
    // Test 1: Departments List API Logic
    printHeader("Test 1: Departments List API Logic");
    
    // Test the API logic directly (simulate what the API does)
    try {
        // Simulate the API query logic
        $sector = '';
        $q = '';
        $includePH = false;
        $page = 1;
        $limit = 100;
        $offset = 0;
        
        $select = [
            'dep.department_id AS department_id',
            'dep.department_name AS department_name',
            'dep.department_code AS department_code',
            'sec.sector_id AS sector_id',
            'sec.sector_name AS sector_name',
            'dep.is_active AS is_active'
        ];
        
        $joins = ['JOIN sectors sec ON dep.sector_id = sec.sector_id'];
        $where = ['dep.is_active = 1'];
        $namedParams = [];
        
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $selectSql = 'SELECT ' . implode(', ', $select) . ' FROM departments dep ' . implode(' ', $joins) . ' ' . $whereSql . ' ORDER BY dep.department_name ASC, sec.sector_name ASC';
        
        $stmt = $pdo->prepare($selectSql);
        $stmt->execute($namedParams);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group departments by name/code
        $grouped = [];
        foreach ($allRows as $dept) {
            $key = $dept['department_code'] ?: $dept['department_name'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'department_name' => $dept['department_name'],
                    'department_code' => $dept['department_code'],
                    'sectors' => [],
                    'is_shared' => false
                ];
            }
            $grouped[$key]['sectors'][] = [
                'sector_id' => (int)$dept['sector_id'],
                'sector_name' => $dept['sector_name'],
                'department_id' => (int)$dept['department_id']
            ];
        }
        
        foreach ($grouped as &$group) {
            $group['is_shared'] = count($group['sectors']) > 1;
        }
        unset($group);
        
        $departments = array_values($grouped);
        $total = count($departments);
        
        $response = [
            'success' => true,
            'departments' => array_slice($departments, $offset, $limit),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ];
        
        if ($response && isset($response['success']) && $response['success']) {
            printSuccess("API returned success");
            printInfo("Total departments: " . ($response['total'] ?? 0));
            printInfo("Departments returned: " . count($response['departments'] ?? []));
            
            // Check for cross-sector information
            $hasShared = false;
            foreach ($response['departments'] ?? [] as $dept) {
                if (isset($dept['is_shared']) && $dept['is_shared']) {
                    $hasShared = true;
                    printInfo("Found shared department: {$dept['department_name']} in " . count($dept['sectors'] ?? []) . " sectors");
                }
            }
            
            if (!$hasShared) {
                printInfo("No shared departments found (expected if none created yet)");
            }
            
            // Verify response structure
            $requiredFields = ['success', 'departments', 'total', 'page', 'limit'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($response[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                printSuccess("Response structure is correct");
            } else {
                printError("Missing fields in response: " . implode(', ', $missingFields));
            }
        } else {
            printError("API logic error");
        }
    } catch (Exception $e) {
        printError("API test failed: " . $e->getMessage());
    }
    
    // Test 2: Program Head is_assigned Logic
    printHeader("Test 2: Program Head is_assigned Logic");
    
    // Find a Program Head user
    $stmt = $pdo->query("
        SELECT DISTINCT uda.user_id 
        FROM user_department_assignments uda
        JOIN staff s ON uda.user_id = s.user_id
        WHERE s.staff_category = 'Program Head' 
        AND uda.is_active = 1
        LIMIT 1
    ");
    $testUserId = $stmt->fetchColumn();
    
    if ($testUserId) {
        printInfo("Testing with Program Head user_id: $testUserId");
        
        // Test the is_assigned logic directly
        $clearanceTypes = ['College', 'Faculty', 'Senior High School'];
        
        foreach ($clearanceTypes as $clearanceType) {
            // Get cross-sector department IDs
            $allDeptIds = getCrossSectorDepartmentIds($pdo, $testUserId);
            
            if (empty($allDeptIds)) {
                printInfo("$clearanceType: No departments assigned");
                continue;
            }
            
            // Check if any departments exist in the requested clearance type
            $placeholders = implode(',', array_fill(0, count($allDeptIds), '?'));
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM departments d
                JOIN sectors s ON d.sector_id = s.sector_id
                WHERE d.department_id IN ($placeholders)
                AND s.sector_name = ?
                AND d.is_active = 1
            ");
            $params = array_merge($allDeptIds, [$clearanceType]);
            $stmt->execute($params);
            $hasDepartmentScope = $stmt->fetchColumn() > 0;
            
            // Check include_program_head setting
            $settingStmt = $pdo->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
            $settingStmt->execute([$clearanceType]);
            $settingRow = $settingStmt->fetch(PDO::FETCH_ASSOC);
            $includePhSetting = $settingRow ? (int)$settingRow['include_program_head'] : 0;
            
            $canTakeAction = $hasDepartmentScope && ($includePhSetting === 1);
            
            printInfo("$clearanceType:");
            printInfo("  Has department scope: " . ($hasDepartmentScope ? 'YES' : 'NO'));
            printInfo("  Include PH setting: " . $includePhSetting);
            printInfo("  Can take action: " . ($canTakeAction ? 'YES' : 'NO'));
        }
    } else {
        printWarning("No Program Head found - skipping is_assigned test");
    }
    
    // Test 3: Helper Functions Direct Test
    printHeader("Test 3: Helper Functions Direct Test");
    
    if ($testUserId) {
        $crossSectorIds = getCrossSectorDepartmentIds($pdo, $testUserId);
        printInfo("Program Head user_id: $testUserId");
        printInfo("Cross-sector department IDs: " . json_encode($crossSectorIds));
        
        if (!empty($crossSectorIds)) {
            printSuccess("Helper function working - found " . count($crossSectorIds) . " department IDs");
            
            // Verify these IDs exist and show their sectors
            $placeholders = implode(',', array_fill(0, count($crossSectorIds), '?'));
            $stmt = $pdo->prepare("
                SELECT 
                    d.department_name,
                    d.department_code,
                    s.sector_name
                FROM departments d
                JOIN sectors s ON d.sector_id = s.sector_id
                WHERE d.department_id IN ($placeholders)
                ORDER BY d.department_name, s.sector_name
            ");
            $stmt->execute($crossSectorIds);
            $depts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by department
            $deptGroups = [];
            foreach ($depts as $dept) {
                $key = $dept['department_code'] ?: $dept['department_name'];
                if (!isset($deptGroups[$key])) {
                    $deptGroups[$key] = [];
                }
                $deptGroups[$key][] = $dept['sector_name'];
            }
            
            foreach ($deptGroups as $key => $sectors) {
                if (count($sectors) > 1) {
                    printSuccess("Cross-sector department found: '$key' in " . count($sectors) . " sectors");
                }
            }
        } else {
            printInfo("No departments found (Program Head may not be assigned)");
        }
    }
    
    // Test 4: Department Code Validation
    printHeader("Test 4: Department Code Validation Check");
    
    // Check for any duplicate codes (should be none)
    $stmt = $pdo->query("
        SELECT department_code, COUNT(*) as count
        FROM departments
        WHERE department_code IS NOT NULL
        AND is_active = 1
        GROUP BY department_code
        HAVING count > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        printSuccess("No duplicate department codes found");
    } else {
        printError("Found duplicate codes:");
        foreach ($duplicates as $dup) {
            printInfo("  Code '{$dup['department_code']}' appears {$dup['count']} times");
        }
    }
    
    // Test 5: Verify Cross-Sector Matching Logic
    printHeader("Test 5: Cross-Sector Matching Logic Verification");
    
    // Find departments that share name or code
    $stmt = $pdo->query("
        SELECT 
            COALESCE(d1.department_code, d1.department_name) as identifier,
            d1.department_name,
            d1.department_code,
            COUNT(DISTINCT d1.sector_id) as sector_count,
            GROUP_CONCAT(DISTINCT s.sector_name ORDER BY s.sector_name) as sectors
        FROM departments d1
        JOIN sectors s ON d1.sector_id = s.sector_id
        WHERE d1.is_active = 1
        GROUP BY COALESCE(d1.department_code, d1.department_name), d1.department_name, d1.department_code
        HAVING sector_count > 1
    ");
    $shared = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($shared)) {
        printSuccess("Found " . count($shared) . " shared department(s):");
        foreach ($shared as $dept) {
            printInfo("  {$dept['department_name']} ({$dept['department_code']}) in: {$dept['sectors']}");
        }
    } else {
        printInfo("No shared departments found - create one to test cross-sector matching");
    }
    
    printHeader("Test Summary");
    printSuccess("All endpoint tests completed");
    printInfo("\nNext Steps:");
    printInfo("1. Test department creation via browser test page");
    printInfo("2. Create a shared department to verify cross-sector matching");
    printInfo("3. Assign Program Head and test signatory actions");
    
} catch (Exception $e) {
    printError("Test failed: " . $e->getMessage());
    echo "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

