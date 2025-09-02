<?php
// Seed sectors and departments for testing Program Head assignment
// Safe to run multiple times (idempotent inserts/alterations)

require_once __DIR__ . '/includes/config/database.php';

function out($msg){ echo $msg . "\n"; }

try {
    $pdo = Database::getInstance()->getConnection();
    out("Connected to DB.");

    // 1) Ensure sectors table
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS sectors (
            sector_id INT AUTO_INCREMENT PRIMARY KEY,
            sector_name VARCHAR(100) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    out("Ensured table: sectors.");

    // Insert default sectors
    $defaultSectors = ['College','Senior High School','Faculty'];
    $insSector = $pdo->prepare("INSERT INTO sectors (sector_name) VALUES (?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
    foreach ($defaultSectors as $s) { $insSector->execute([$s]); }
    out("Seeded default sectors (College, Senior High School, Faculty).");

    // 2) Ensure departments table exists
    $hasDepartments = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'departments'")->fetchColumn() > 0;
    if (!$hasDepartments) {
        $pdo->exec(
            "CREATE TABLE departments (
                department_id INT AUTO_INCREMENT PRIMARY KEY,
                department_name VARCHAR(150) NOT NULL,
                sector_id INT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_department_sector (department_name, sector_id),
                INDEX idx_sector_id (sector_id),
                CONSTRAINT fk_departments_sector_id FOREIGN KEY (sector_id) REFERENCES sectors(sector_id) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        out("Created table: departments.");
    } else {
        out("Table exists: departments.");
    }

    // 3) Ensure departments.sector_id column (if missing) and composite unique key
    $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'departments' AND column_name = 'sector_id'");
    $colCheck->execute();
    $hasSectorId = (int)$colCheck->fetchColumn() > 0;
    if (!$hasSectorId) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN sector_id INT NULL, ADD INDEX idx_sector_id (sector_id)");
        // Try add FK (ignore if fails due to engine constraints)
        try { $pdo->exec("ALTER TABLE departments ADD CONSTRAINT fk_departments_sector_id FOREIGN KEY (sector_id) REFERENCES sectors(sector_id) ON DELETE SET NULL ON UPDATE CASCADE"); } catch (Exception $e) {}
        out("Added departments.sector_id column.");
    } else {
        out("Column exists: departments.sector_id.");
    }

    // Ensure composite unique (department_name, sector_id)
    $idxCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'departments' AND index_name = 'uq_department_sector'");
    $idxCheck->execute();
    $hasCompositeUnique = (int)$idxCheck->fetchColumn() > 0;
    if (!$hasCompositeUnique) {
        try {
            $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY uq_department_sector (department_name, sector_id)");
            out("Added unique index uq_department_sector (department_name, sector_id).");
        } catch (Exception $e) {
            out("Note: could not add unique index (likely duplicates exist) – run cleanup first if needed.");
        }
    }

    // 4) Resolve sector ids
    $getSectorId = $pdo->prepare("SELECT sector_id FROM sectors WHERE sector_name = ? LIMIT 1");
    $sec = [];
    foreach ($defaultSectors as $s) {
        $getSectorId->execute([$s]);
        $sec[$s] = (int)$getSectorId->fetchColumn();
    }

    // 5) Seed departments per sector (as requested)
    $samples = [
        'College' => [
            'Information & Communication Technology',
            'Business, Arts, & Science',
            'Tourism & Hospitality Management'
        ],
        'Senior High School' => [
            'Academic Track',
            'Technological-Vocational Livelihood',
            'Home Economics'
        ],
        'Faculty' => [
            'General Education'
        ],
    ];

    $insDept = $pdo->prepare("INSERT INTO departments (department_name, sector_id, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE sector_id = VALUES(sector_id), updated_at = CURRENT_TIMESTAMP");
    foreach ($samples as $sectorName => $deptList) {
        $sid = $sec[$sectorName] ?? null;
        if (!$sid) { out("Warning: missing sector id for {$sectorName}"); continue; }
        foreach ($deptList as $dname) {
            $insDept->execute([$dname, $sid]);
        }
        out("Seeded departments for sector: {$sectorName}");
    }

    out("Seeding complete.");

} catch (Exception $e) {
    http_response_code(500);
    out('Error: ' . $e->getMessage());
    exit(1);
}


