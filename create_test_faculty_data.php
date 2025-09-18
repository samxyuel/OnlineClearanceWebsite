<?php
// Create test faculty data and clearance applications
require_once 'includes/config/database.php';

echo "Creating test faculty data and applications...\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check if we have faculty designation
    $stmt = $pdo->query("SELECT designation_id FROM designations WHERE designation_name = 'Faculty' LIMIT 1");
    $facultyDesignationId = $stmt->fetchColumn();
    
    if (!$facultyDesignationId) {
        echo "❌ Faculty designation not found. Creating it...\n";
        $stmt = $pdo->prepare("INSERT INTO designations (designation_name, description) VALUES (?, ?)");
        $stmt->execute(['Faculty', 'Faculty member designation']);
        $facultyDesignationId = $pdo->lastInsertId();
        echo "✅ Created Faculty designation: $facultyDesignationId\n";
    } else {
        echo "✅ Faculty designation found: $facultyDesignationId\n";
    }
    
    // Check if we have faculty members
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff WHERE designation_id = ?");
    $stmt->execute([$facultyDesignationId]);
    $facultyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($facultyCount == 0) {
        echo "Creating test faculty members...\n";
        
        // Create test faculty members
        $facultyMembers = [
            ['John', 'Doe', 'FAC001', 'john.doe@university.edu'],
            ['Jane', 'Smith', 'FAC002', 'jane.smith@university.edu'],
            ['Bob', 'Johnson', 'FAC003', 'bob.johnson@university.edu'],
            ['Alice', 'Brown', 'FAC004', 'alice.brown@university.edu'],
            ['Charlie', 'Wilson', 'FAC005', 'charlie.wilson@university.edu']
        ];
        
        foreach ($facultyMembers as $faculty) {
            // Create user account
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, 'active')");
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $stmt->execute([$faculty[2], $faculty[3], $hashedPassword]);
            $userId = $pdo->lastInsertId();
            
            // Create staff record
            $stmt = $pdo->prepare("INSERT INTO staff (user_id, first_name, last_name, employee_number, designation_id, employment_status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->execute([$userId, $faculty[0], $faculty[1], $faculty[2], $facultyDesignationId]);
            
            echo "   ✅ Created faculty: {$faculty[0]} {$faculty[1]} ({$faculty[2]})\n";
        }
    } else {
        echo "✅ Faculty members already exist: $facultyCount\n";
    }
    
    // Get active clearance period
    $stmt = $pdo->query("SELECT period_id FROM clearance_periods WHERE status = 'active' LIMIT 1");
    $activePeriodId = $stmt->fetchColumn();
    
    if (!$activePeriodId) {
        echo "❌ No active clearance period found\n";
        exit;
    }
    
    echo "✅ Active clearance period: $activePeriodId\n";
    
    // Check for existing clearance applications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clearance_applications WHERE period_id = ?");
    $stmt->execute([$activePeriodId]);
    $applicationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($applicationCount == 0) {
        echo "Creating test clearance applications...\n";
        
        // Get faculty user IDs
        $stmt = $pdo->prepare("SELECT s.user_id, s.first_name, s.last_name FROM staff s WHERE s.designation_id = ? LIMIT 3");
        $stmt->execute([$facultyDesignationId]);
        $facultyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $applicationStatuses = ['pending', 'in-progress', 'completed'];
        
        foreach ($facultyUsers as $index => $faculty) {
            $status = $applicationStatuses[$index % count($applicationStatuses)];
            
            $stmt = $pdo->prepare("INSERT INTO clearance_applications (user_id, period_id, status, applied_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$faculty['user_id'], $activePeriodId, $status]);
            
            echo "   ✅ Created application for {$faculty['first_name']} {$faculty['last_name']} (Status: $status)\n";
        }
    } else {
        echo "✅ Clearance applications already exist: $applicationCount\n";
    }
    
    // Check for signatory assignments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sector_signatory_assignments WHERE clearance_type = 'Faculty'");
    $signatoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($signatoryCount == 0) {
        echo "Creating test signatory assignments...\n";
        
        // Get a user to assign as signatory (let's use the first user)
        $stmt = $pdo->query("SELECT user_id FROM users LIMIT 1");
        $signatoryUserId = $stmt->fetchColumn();
        
        if ($signatoryUserId) {
            // Get a designation for the signatory
            $stmt = $pdo->query("SELECT designation_id FROM designations WHERE designation_name IN ('Registrar', 'Accountant', 'Dean') LIMIT 1");
            $signatoryDesignationId = $stmt->fetchColumn();
            
            if (!$signatoryDesignationId) {
                // Create a test designation
                $stmt = $pdo->prepare("INSERT INTO designations (designation_name, description) VALUES (?, ?)");
                $stmt->execute(['Registrar', 'Registrar designation']);
                $signatoryDesignationId = $pdo->lastInsertId();
            }
            
            $stmt = $pdo->prepare("INSERT INTO sector_signatory_assignments (user_id, clearance_type, designation_id, assigned_at) VALUES (?, 'Faculty', ?, NOW())");
            $stmt->execute([$signatoryUserId, $signatoryDesignationId]);
            
            echo "   ✅ Created Faculty signatory assignment for user $signatoryUserId\n";
        }
    } else {
        echo "✅ Faculty signatory assignments already exist: $signatoryCount\n";
    }
    
    echo "\n🎉 Test data creation complete!\n";
    echo "\n📊 Summary:\n";
    
    // Final counts
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff WHERE designation_id = ?");
    $stmt->execute([$facultyDesignationId]);
    $finalFacultyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "- Faculty members: $finalFacultyCount\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clearance_applications WHERE period_id = ?");
    $stmt->execute([$activePeriodId]);
    $finalApplicationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "- Clearance applications: $finalApplicationCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sector_signatory_assignments WHERE clearance_type = 'Faculty'");
    $finalSignatoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "- Faculty signatory assignments: $finalSignatoryCount\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
