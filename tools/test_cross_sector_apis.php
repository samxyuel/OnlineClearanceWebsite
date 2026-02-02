<?php
/**
 * Test Script for Cross-Sector Department APIs
 * Tests the department creation and list APIs
 * 
 * Usage: php tools/test_cross_sector_apis.php
 */

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

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

printHeader("Cross-Sector Department APIs Test");

try {
    $pdo = Database::getInstance()->getConnection();
    printSuccess("Database connection successful");
    
    // Test 1: Departments List API (simulate the endpoint)
    printHeader("Test 1: Departments List API Logic");
    
    // Simulate the list API query
    $stmt = $pdo->query("
        SELECT 
            d.department_id,
            d.department_name,
            d.department_code,
            s.sector_id,
            s.sector_name,
            d.is_active
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE d.is_active = 1
        ORDER BY d.department_name ASC, s.sector_name ASC
        LIMIT 20
    ");
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group departments by name/code to detect cross-sector departments
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
    
    // Mark as shared if multiple sectors
    foreach ($grouped as &$group) {
        $group['is_shared'] = count($group['sectors']) > 1;
    }
    unset($group);
    
    $departments = array_values($grouped);
    $total = count($departments);
    
    printInfo("Total unique departments: $total");
    
    $sharedCount = 0;
    foreach ($departments as $dept) {
        if ($dept['is_shared']) {
            $sharedCount++;
            printInfo("SHARED: {$dept['department_name']} ({$dept['department_code']}) in " . count($dept['sectors']) . " sectors");
            foreach ($dept['sectors'] as $sector) {
                printInfo("  - {$sector['sector_name']} (ID: {$sector['department_id']})");
            }
        }
    }
    
    if ($sharedCount > 0) {
        printSuccess("Found $sharedCount shared department(s)");
    } else {
        printInfo("No shared departments found (this is expected if none have been created yet)");
    }
    
    // Test 2: Check if department code uniqueness is enforced
    printHeader("Test 2: Department Code Uniqueness Check");
    
    $stmt = $pdo->query("
        SELECT 
            department_code,
            COUNT(*) as count,
            GROUP_CONCAT(CONCAT(department_name, ' (sector:', sector_id, ')') SEPARATOR ', ') as locations
        FROM departments
        WHERE department_code IS NOT NULL
        AND is_active = 1
        GROUP BY department_code
        HAVING count > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        printSuccess("Department code uniqueness enforced correctly (no duplicates found)");
    } else {
        printError("Found duplicate department codes:");
        foreach ($duplicates as $dup) {
            printInfo("  Code '{$dup['department_code']}' appears {$dup['count']} times: {$dup['locations']}");
        }
    }
    
    // Test 3: Test Program Head cross-sector access
    printHeader("Test 3: Program Head Cross-Sector Access");
    
    require_once __DIR__ . '/../includes/helpers/department_helpers.php';
    
    // Find a Program Head
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
        
        $crossSectorIds = getCrossSectorDepartmentIds($pdo, $testUserId);
        
        if (!empty($crossSectorIds)) {
            printSuccess("Found " . count($crossSectorIds) . " department IDs across sectors");
            
            // Show which sectors these departments are in
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
            
            $currentDept = null;
            foreach ($depts as $dept) {
                $deptKey = $dept['department_code'] ?: $dept['department_name'];
                if ($currentDept !== $deptKey) {
                    $currentDept = $deptKey;
                    printInfo("  {$dept['department_name']} ({$dept['department_code']}):");
                }
                printInfo("    - {$dept['sector_name']}");
            }
        } else {
            printInfo("No departments found for this Program Head");
        }
    } else {
        printInfo("No Program Head found with department assignments");
    }
    
    // Test 4: Verify API endpoint files exist
    printHeader("Test 4: API Endpoint Files Check");
    
    $endpoints = [
        'api/departments/create.php' => 'Department Creation API',
        'api/departments/list.php' => 'Departments List API',
        'api/program-head/is_assigned.php' => 'Program Head Access Check API',
        'includes/helpers/department_helpers.php' => 'Helper Functions'
    ];
    
    foreach ($endpoints as $file => $name) {
        $fullPath = __DIR__ . '/../' . $file;
        if (file_exists($fullPath)) {
            printSuccess("$name exists: $file");
        } else {
            printError("$name missing: $file");
        }
    }
    
    printHeader("Test Summary");
    printSuccess("All API tests completed");
    printInfo("\nRecommendations:");
    printInfo("1. Create a shared department to test cross-sector matching");
    printInfo("2. Test department creation API via browser: pages/diagnostics/test_cross_sector.php");
    printInfo("3. Assign Program Head to shared department and verify access");
    
} catch (Exception $e) {
    printError("Test failed: " . $e->getMessage());
    echo "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

