<?php
// Migration: create signatory_assignments and backfill Program Heads from staff
require_once __DIR__ . '/includes/config/database.php';

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = Database::getInstance()->getConnection();

    if (!tableExists($pdo, 'signatory_assignments')) {
        $pdo->exec("CREATE TABLE signatory_assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            designation_id INT NOT NULL,
            clearance_type ENUM('student','faculty') NULL,
            department_id INT NULL,
            sector_id INT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_sa_user FOREIGN KEY (user_id) REFERENCES users(user_id),
            CONSTRAINT fk_sa_desig FOREIGN KEY (designation_id) REFERENCES designations(designation_id),
            CONSTRAINT fk_sa_dept FOREIGN KEY (department_id) REFERENCES departments(department_id),
            CONSTRAINT fk_sa_sector FOREIGN KEY (sector_id) REFERENCES sectors(sector_id),
            UNIQUE KEY uq_sa_ph (department_id, designation_id),
            UNIQUE KEY uq_sa_scope (user_id, clearance_type, designation_id)
        ) ENGINE=InnoDB");
        echo "Created signatory_assignments table\n";
    } else {
        echo "signatory_assignments already exists\n";
    }

    // Backfill: Program Head rows from staff
    $designationId = null;
    $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) { $designationId = (int)$row['designation_id']; }

    if ($designationId) {
        $sql = "SELECT s.user_id, s.department_id, sec.sector_id
                FROM staff s
                JOIN departments d ON s.department_id = d.department_id
                JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE s.staff_category = 'Program Head' AND s.department_id IS NOT NULL AND s.is_active = 1";
        foreach ($pdo->query($sql) as $r) {
            $userId = (int)$r['user_id'];
            $deptId = (int)$r['department_id'];
            $sectorId = (int)$r['sector_id'];
            // Insert if not exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments WHERE user_id=? AND designation_id=? AND department_id=? AND is_active=1");
            $check->execute([$userId, $designationId, $deptId]);
            if ((int)$check->fetchColumn() === 0) {
                $ins = $pdo->prepare("INSERT INTO signatory_assignments (user_id, designation_id, clearance_type, department_id, sector_id, is_active) VALUES (?,?,?,?,?,1)");
                $ins->execute([$userId, $designationId, null, $deptId, $sectorId]);
            }
        }
        echo "Backfill complete for Program Head assignments\n";
    } else {
        echo "Program Head designation not found; backfill skipped\n";
    }

    echo "Migration finished.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Migration error: ".$e->getMessage()."\n";
}
?>

