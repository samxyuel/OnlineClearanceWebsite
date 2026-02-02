<?php
/**
 * Test Script for Department Helper Functions
 * Tests cross-sector department matching
 * 
 * Usage: php tools/test_department_helpers.php [user_id]
 */

require_once __DIR__ . '/../includes/config/database.php';
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

printHeader("Department Helper Functions Test");

try {
    $pdo = Database::getInstance()->getConnection();
    printSuccess("Database connection successful");
    
    // Get a Program Head user ID from command line or use first available
    $testUserId = isset($argv[1]) ? (int)$argv[1] : null;
    
    if (!$testUserId) {
        // Find first Program Head
        $stmt = $pdo->query("
            SELECT DISTINCT uda.user_id 
            FROM user_department_assignments uda
            JOIN staff s ON uda.user_id = s.user_id
            WHERE s.staff_category = 'Program Head' 
            LIMIT 1
        ");
        $testUserId = $stmt->fetchColumn();
        
        if (!$testUserId) {
            printError("No Program Head found. Please create one or provide user_id as argument.");
            exit(1);
        }
        printInfo("Using Program Head user_id: $testUserId");
    }
    
    // Test 1: getCrossSectorDepartmentIds
    printHeader("Test 1: getCrossSectorDepartmentIds()");
    $crossSectorIds = getCrossSectorDepartmentIds($pdo, $testUserId);
    printInfo("User ID: $testUserId");
    printInfo("Cross-sector department IDs: " . json_encode($crossSectorIds));
    
    if (empty($crossSectorIds)) {
        printError("No departments found - Program Head may not be assigned to any departments");
    } else {
        printSuccess("Found " . count($crossSectorIds) . " department IDs across sectors");
        
        // Show department details
        $placeholders = implode(',', array_fill(0, count($crossSectorIds), '?'));
        $stmt = $pdo->prepare("
            SELECT d.department_id, d.department_name, d.department_code, s.sector_name
            FROM departments d
            JOIN sectors s ON d.sector_id = s.sector_id
            WHERE d.department_id IN ($placeholders)
            ORDER BY d.department_name, s.sector_name
        ");
        $stmt->execute($crossSectorIds);
        $depts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        printInfo("Department details:");
        $currentDept = null;
        foreach ($depts as $dept) {
            $deptKey = $dept['department_code'] ?: $dept['department_name'];
            if ($currentDept !== $deptKey) {
                $currentDept = $deptKey;
                printInfo("  {$dept['department_name']} ({$dept['department_code']}):");
            }
            printInfo("    - {$dept['sector_name']} (ID: {$dept['department_id']})");
        }
        
        // Check if cross-sector matching is working
        $deptGroups = [];
        foreach ($depts as $dept) {
            $key = $dept['department_code'] ?: $dept['department_name'];
            if (!isset($deptGroups[$key])) {
                $deptGroups[$key] = [];
            }
            $deptGroups[$key][] = $dept['sector_name'];
        }
        
        $hasCrossSector = false;
        foreach ($deptGroups as $key => $sectors) {
            if (count($sectors) > 1) {
                $hasCrossSector = true;
                printSuccess("Cross-sector matching detected: '$key' exists in " . count($sectors) . " sectors");
            }
        }
        
        if (!$hasCrossSector) {
            printInfo("Note: No cross-sector departments found. Create shared departments to test cross-sector matching.");
        }
    }
    
    // Test 2: getDepartmentIdentifiers
    if (!empty($crossSectorIds)) {
        printHeader("Test 2: getDepartmentIdentifiers()");
        $identifiers = getDepartmentIdentifiers($pdo, $crossSectorIds);
        printInfo("Identifiers: " . json_encode($identifiers));
        printSuccess("Function executed successfully");
    }
    
    // Test 3: getDepartmentSectors
    printHeader("Test 3: getDepartmentSectors()");
    // Find a department that exists in multiple sectors
    $stmt = $pdo->query("
        SELECT department_name, department_code 
        FROM departments 
        WHERE department_code IS NOT NULL 
        AND is_active = 1
        GROUP BY department_code 
        HAVING COUNT(DISTINCT sector_id) > 1 
        LIMIT 1
    ");
    $sharedDept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sharedDept) {
        $sectors = getDepartmentSectors($pdo, $sharedDept['department_name'], $sharedDept['department_code']);
        printInfo("Department: {$sharedDept['department_name']} ({$sharedDept['department_code']})");
        printInfo("Found in " . count($sectors) . " sectors:");
        foreach ($sectors as $sector) {
            printInfo("  - {$sector['sector_name']} (Department ID: {$sector['department_id']})");
        }
        printSuccess("Cross-sector detection working correctly");
    } else {
        printInfo("No shared departments found - create one to test this function");
        printInfo("You can create a shared department using: api/departments/create.php");
    }
    
    // Test 4: checkDepartmentExistsInSector
    printHeader("Test 4: checkDepartmentExistsInSector()");
    // Test with a known department
    $stmt = $pdo->query("
        SELECT department_name, department_code, sector_id 
        FROM departments 
        WHERE is_active = 1 
        LIMIT 1
    ");
    $testDept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testDept) {
        $exists = checkDepartmentExistsInSector(
            $pdo, 
            $testDept['department_name'], 
            $testDept['department_code'] ?: '', 
            $testDept['sector_id']
        );
        if ($exists) {
            printSuccess("Department '{$testDept['department_name']}' exists in sector ID {$testDept['sector_id']}");
        } else {
            printError("Department '{$testDept['department_name']}' not found in sector ID {$testDept['sector_id']}");
        }
    }
    
    printHeader("Test Summary");
    printSuccess("All helper function tests completed");
    printInfo("\nNext steps:");
    printInfo("1. Test department creation API: POST api/departments/create.php");
    printInfo("2. Test departments list API: GET api/departments/list.php");
    printInfo("3. Test Program Head access: GET api/program-head/is_assigned.php?clearance_type=College");
    
} catch (Exception $e) {
    printError("Test failed: " . $e->getMessage());
    echo "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

