<?php
require_once 'includes/config/database.php';

$db = Database::getInstance();
$connection = $db->getConnection();

echo "=== FIXING CLEARANCE FORM DISTRIBUTION ===\n\n";

// Get active academic year and semester
$stmt = $connection->query("
    SELECT 
        ay.academic_year_id,
        ay.year,
        s.semester_id,
        s.semester_name
    FROM academic_years ay
    JOIN semesters s ON ay.academic_year_id = s.academic_year_id
    WHERE ay.is_active = 1 AND s.is_active = 1
    LIMIT 1
");
$activePeriod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activePeriod) {
    echo "❌ No active academic year/semester found!\n";
    exit;
}

echo "Active Period: {$activePeriod['year']} - {$activePeriod['semester_name']}\n";
echo "Academic Year ID: {$activePeriod['academic_year_id']}, Semester ID: {$activePeriod['semester_id']}\n\n";

$academicYearId = $activePeriod['academic_year_id'];
$semesterId = $activePeriod['semester_id'];

// Process each sector
$sectors = ['College', 'Senior High School', 'Faculty'];

foreach ($sectors as $sector) {
    echo "=== PROCESSING {$sector} ===\n";
    
    // 1. Get eligible users for this sector
    $eligibleUsers = getEligibleUsersForSector($connection, $sector);
    echo "Found " . count($eligibleUsers) . " eligible users for {$sector}\n";
    
    if (empty($eligibleUsers)) {
        echo "⚠️ No eligible users found for {$sector}\n\n";
        continue;
    }
    
    // 2. Get signatory assignments for this sector
    $signatoryAssignments = getSectorSignatoryAssignments($connection, $sector);
    echo "Found " . count($signatoryAssignments) . " signatory assignments for {$sector}\n";
    
    if (empty($signatoryAssignments)) {
        echo "⚠️ No signatory assignments found for {$sector}\n\n";
        continue;
    }
    
    // 3. Create/update clearance forms for all eligible users
    $formsCreated = 0;
    $formsUpdated = 0;
    $signatoriesAssigned = 0;
    
    foreach ($eligibleUsers as $user) {
        // Check if form already exists
        $existingForm = checkExistingClearanceForm($connection, $user['user_id'], $academicYearId, $semesterId, $sector);
        
        if ($existingForm) {
            $formId = $existingForm['clearance_form_id'];
            $formsUpdated++;
            echo "  📝 Form exists for {$user['first_name']} {$user['last_name']} (ID: {$formId})\n";
        } else {
            // Create new form
            $formId = createClearanceFormForUser($connection, $user, $academicYearId, $semesterId, $sector);
            $formsCreated++;
            echo "  ✅ Created form for {$user['first_name']} {$user['last_name']} (ID: {$formId})\n";
        }
        
        // Check if signatories are already assigned
        $stmt = $connection->prepare("
            SELECT COUNT(*) as count 
            FROM clearance_signatories 
            WHERE clearance_form_id = ?
        ");
        $stmt->execute([$formId]);
        $existingSignatories = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($existingSignatories == 0) {
            // Assign signatories to the form
            $assignedCount = assignSignatoriesToClearanceForm($connection, $formId, $signatoryAssignments, $user, $sector);
            $signatoriesAssigned += $assignedCount;
            echo "    👥 Assigned {$assignedCount} signatories\n";
        } else {
            echo "    👥 Signatories already assigned ({$existingSignatories})\n";
        }
    }
    
    echo "📊 Summary for {$sector}:\n";
    echo "  - Forms created: {$formsCreated}\n";
    echo "  - Forms updated: {$formsUpdated}\n";
    echo "  - Signatories assigned: {$signatoriesAssigned}\n\n";
}

echo "✅ Clearance form distribution fix completed!\n";

// Helper functions (copied from periods.php)
function getEligibleUsersForSector($connection, $clearanceType) {
    $sql = "";
    $params = [];
    
    switch ($clearanceType) {
        case 'College':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       s.section, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'College'
                AND u.status = 'active'
                AND s.enrollment_status = 'Enrolled'
                ORDER BY u.last_name, u.first_name
            ";
            break;
            
        case 'Senior High School':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       s.section, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Senior High School'
                AND u.status = 'active'
                AND s.enrollment_status = 'Enrolled'
                ORDER BY u.last_name, u.first_name
            ";
            break;
            
        case 'Faculty':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       f.employment_status, f.department_id, d.department_name
                FROM users u
                INNER JOIN faculty f ON u.user_id = f.user_id
                INNER JOIN departments d ON f.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Faculty'
                AND u.status = 'active'
                ORDER BY u.last_name, u.first_name
            ";
            break;
    }
    
    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSectorSignatoryAssignments($connection, $clearanceType) {
    $sql = "
        SELECT 
            ssa.assignment_id,
            ssa.clearance_type,
            ssa.user_id,
            ssa.designation_id,
            ssa.is_program_head,
            ssa.department_id,
            ssa.is_required_first,
            ssa.is_required_last,
            ssa.is_active,
            u.first_name,
            u.last_name,
            d.designation_name,
            dept.department_name
        FROM sector_signatory_assignments ssa
        LEFT JOIN users u ON ssa.user_id = u.user_id
        LEFT JOIN designations d ON ssa.designation_id = d.designation_id
        LEFT JOIN departments dept ON ssa.department_id = dept.department_id
        WHERE ssa.clearance_type = ?
        AND ssa.is_active = 1
        ORDER BY ssa.is_required_first DESC, ssa.is_required_last ASC, d.designation_name
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$clearanceType]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function checkExistingClearanceForm($connection, $userId, $academicYearId, $semesterId, $clearanceType) {
    $sql = "
        SELECT clearance_form_id FROM clearance_forms 
        WHERE user_id = ? AND academic_year_id = ? AND semester_id = ? AND clearance_type = ?
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$userId, $academicYearId, $semesterId, $clearanceType]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createClearanceFormForUser($connection, $user, $academicYearId, $semesterId, $clearanceType) {
    $sql = "
        INSERT INTO clearance_forms (
            user_id,
            academic_year_id,
            semester_id,
            clearance_type,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, 'Unapplied', NOW())
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        $user['user_id'],
        $academicYearId,
        $semesterId,
        $clearanceType
    ]);
    
    return $connection->lastInsertId();
}

function assignSignatoriesToClearanceForm($connection, $formId, $signatoryAssignments, $user, $clearanceType) {
    $assignedCount = 0;
    
    // Get the clearance form ID (varchar format)
    $stmt = $connection->prepare("SELECT clearance_form_id FROM clearance_forms WHERE clearance_form_id = ?");
    $stmt->execute([$formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        echo "    ❌ Form not found for form_id: $formId\n";
        return 0;
    }
    
    $clearanceFormId = $form['clearance_form_id'];
    
    foreach ($signatoryAssignments as $assignment) {
        // Check if signatory is already assigned to this form
        $stmt = $connection->prepare("
            SELECT COUNT(*) as count 
            FROM clearance_signatories 
            WHERE clearance_form_id = ? AND designation_id = ?
        ");
        $stmt->execute([$clearanceFormId, $assignment['designation_id']]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$exists) {
            // Insert signatory assignment
            $stmt = $connection->prepare("
                INSERT INTO clearance_signatories (
                    clearance_form_id,
                    designation_id,
                    action,
                    created_at,
                    updated_at
                ) VALUES (?, ?, 'Unapplied', NOW(), NOW())
            ");
            $stmt->execute([$clearanceFormId, $assignment['designation_id']]);
            $assignedCount++;
        }
    }
    
    return $assignedCount;
}
?>
