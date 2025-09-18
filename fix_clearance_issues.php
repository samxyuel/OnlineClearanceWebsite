<?php
/**
 * Fix Clearance Issues
 * 
 * This script fixes the form distribution and signatory assignment issues
 */

require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h1>🔧 Fixing Clearance Issues</h1>\n";
    echo "<p><strong>Started:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    
    // 1. Check current state
    echo "<h2>📊 Current State</h2>\n";
    
    $stmt = $connection->query("SELECT COUNT(*) FROM clearance_forms");
    $totalForms = $stmt->fetchColumn();
    echo "<p><strong>Total Clearance Forms:</strong> $totalForms</p>\n";
    
    $stmt = $connection->query("SELECT COUNT(*) FROM clearance_signatories");
    $totalSignatories = $stmt->fetchColumn();
    echo "<p><strong>Total Signatory Assignments:</strong> $totalSignatories</p>\n";
    
    $stmt = $connection->query("SELECT COUNT(*) FROM clearance_signatories WHERE clearance_form_id = '' OR clearance_form_id IS NULL");
    $emptyFormIds = $stmt->fetchColumn();
    echo "<p><strong>Signatory records with empty clearance_form_id:</strong> $emptyFormIds</p>\n";
    
    // 2. Fix the clearance_form_id issue in clearance_signatories
    echo "<h2>🔧 Fixing Clearance Form IDs</h2>\n";
    
    if ($emptyFormIds > 0) {
        // Get all clearance forms
        $stmt = $connection->query("
            SELECT 
                cf.clearance_form_id,
                cf.user_id,
                cf.academic_year_id,
                cf.semester_id,
                cf.clearance_type
            FROM clearance_forms cf
            ORDER BY cf.created_at
        ");
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Found clearance forms:</strong> " . count($forms) . "</p>\n";
        
        $updatedCount = 0;
        foreach ($forms as $form) {
            // Update clearance_signatories that belong to this form
            $stmt = $connection->prepare("
                UPDATE clearance_signatories cs
                SET clearance_form_id = ?
                WHERE (cs.clearance_form_id = '' OR cs.clearance_form_id IS NULL)
                AND EXISTS (
                    SELECT 1 FROM clearance_forms cf2 
                    WHERE cf2.user_id = ? 
                    AND cf2.academic_year_id = ? 
                    AND cf2.semester_id = ? 
                    AND cf2.clearance_type = ?
                    AND cf2.clearance_form_id = ?
                )
            ");
            
            $result = $stmt->execute([
                $form['clearance_form_id'],
                $form['user_id'],
                $form['academic_year_id'],
                $form['semester_id'],
                $form['clearance_type'],
                $form['clearance_form_id']
            ]);
            
            $updatedCount += $stmt->rowCount();
        }
        
        echo "<p><strong>✅ Updated clearance_form_id for $updatedCount signatory records</strong></p>\n";
    }
    
    // 3. Create missing clearance forms for all eligible students
    echo "<h2>📋 Creating Missing Clearance Forms</h2>\n";
    
    // Get the current active period
    $stmt = $connection->query("
        SELECT 
            cp.period_id,
            cp.sector,
            cp.academic_year_id,
            cp.semester_id
        FROM clearance_periods cp
        WHERE cp.status = 'Ongoing'
        LIMIT 1
    ");
    $activePeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activePeriod) {
        echo "<p><strong>❌ No active clearance period found</strong></p>\n";
        exit;
    }
    
    echo "<p><strong>Active Period:</strong> {$activePeriod['sector']} (ID: {$activePeriod['period_id']})</p>\n";
    
    // Get all eligible students for the active sector
    $sector = $activePeriod['sector'];
    $academicYearId = $activePeriod['academic_year_id'];
    $semesterId = $activePeriod['semester_id'];
    
    if ($sector === 'College') {
        $stmt = $connection->prepare("
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
        ");
    } else {
        $stmt = $connection->prepare("
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
        ");
    }
    
    $stmt->execute();
    $eligibleStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Eligible students for $sector:</strong> " . count($eligibleStudents) . "</p>\n";
    
    // Check which students already have forms
    $existingForms = [];
    $stmt = $connection->prepare("
        SELECT user_id FROM clearance_forms 
        WHERE academic_year_id = ? AND semester_id = ? AND clearance_type = ?
    ");
    $stmt->execute([$academicYearId, $semesterId, $sector]);
    $existingForms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Students with existing forms:</strong> " . count($existingForms) . "</p>\n";
    
    // Create forms for students who don't have them
    $newFormsCreated = 0;
    foreach ($eligibleStudents as $student) {
        if (!in_array($student['user_id'], $existingForms)) {
            // Create clearance form
            $stmt = $connection->prepare("
                INSERT INTO clearance_forms (
                    user_id,
                    academic_year_id,
                    semester_id,
                    clearance_type,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, 'Unapplied', NOW())
            ");
            
            $stmt->execute([
                $student['user_id'],
                $academicYearId,
                $semesterId,
                $sector
            ]);
            
            $formId = $connection->lastInsertId();
            $newFormsCreated++;
            
            echo "<p>✅ Created form for {$student['first_name']} {$student['last_name']} (ID: {$student['user_id']})</p>\n";
        }
    }
    
    echo "<p><strong>✅ Created $newFormsCreated new clearance forms</strong></p>\n";
    
    // 4. Assign signatories to all forms
    echo "<h2>👥 Assigning Signatories to Forms</h2>\n";
    
    // Get sector signatory assignments
    $stmt = $connection->prepare("
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
    ");
    $stmt->execute([$sector]);
    $signatoryAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Signatory assignments for $sector:</strong> " . count($signatoryAssignments) . "</p>\n";
    
    // Get all forms for this period
    $stmt = $connection->prepare("
        SELECT 
            cf.clearance_form_id,
            cf.user_id,
            cf.clearance_type
        FROM clearance_forms cf
        WHERE cf.academic_year_id = ? AND cf.semester_id = ? AND cf.clearance_type = ?
    ");
    $stmt->execute([$academicYearId, $semesterId, $sector]);
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Forms to assign signatories to:</strong> " . count($forms) . "</p>\n";
    
    $signatoriesAssigned = 0;
    foreach ($forms as $form) {
        // Check if signatories are already assigned
        $stmt = $connection->prepare("
            SELECT COUNT(*) FROM clearance_signatories 
            WHERE clearance_form_id = ?
        ");
        $stmt->execute([$form['clearance_form_id']]);
        $existingSignatories = $stmt->fetchColumn();
        
        if ($existingSignatories == 0) {
            // Get user details for department matching
            $stmt = $connection->prepare("
                SELECT s.department_id FROM students s WHERE s.user_id = ?
            ");
            $stmt->execute([$form['user_id']]);
            $userDept = $stmt->fetchColumn();
            
            // Assign signatories
            foreach ($signatoryAssignments as $assignment) {
                // Skip if this is a Program Head assignment and user doesn't belong to that department
                if ($assignment['is_program_head'] && $assignment['department_id'] != $userDept) {
                    continue;
                }
                
                // Create signatory entry
                $stmt = $connection->prepare("
                    INSERT INTO clearance_signatories (
                        clearance_form_id,
                        designation_id,
                        actual_user_id,
                        action,
                        created_at
                    ) VALUES (?, ?, ?, 'Unapplied', NOW())
                ");
                
                $stmt->execute([
                    $form['clearance_form_id'],
                    $assignment['designation_id'],
                    $assignment['user_id']
                ]);
                
                $signatoriesAssigned++;
            }
        }
    }
    
    echo "<p><strong>✅ Assigned $signatoriesAssigned signatory records</strong></p>\n";
    
    // 5. Final verification
    echo "<h2>✅ Final Verification</h2>\n";
    
    $stmt = $connection->query("SELECT COUNT(*) FROM clearance_forms");
    $totalForms = $stmt->fetchColumn();
    echo "<p><strong>Total Clearance Forms:</strong> $totalForms</p>\n";
    
    $stmt = $connection->query("SELECT COUNT(*) FROM clearance_signatories WHERE clearance_form_id != '' AND clearance_form_id IS NOT NULL");
    $totalSignatories = $stmt->fetchColumn();
    echo "<p><strong>Total Signatory Assignments with Form IDs:</strong> $totalSignatories</p>\n";
    
    echo "<p><strong>🎉 Fix completed successfully!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>