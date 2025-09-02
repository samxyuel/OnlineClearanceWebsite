<?php
// Cleanup duplicates in departments by (department_name, sector_id)
require_once __DIR__ . '/includes/config/database.php';

function out($m){ echo $m."\n"; }

try{
    $pdo = Database::getInstance()->getConnection();
    out('Connected.');

    // Find duplicates
    $dupSql = "SELECT department_name, sector_id, COUNT(*) c
               FROM departments
               GROUP BY department_name, sector_id
               HAVING c > 1";
    $dups = $pdo->query($dupSql)->fetchAll(PDO::FETCH_ASSOC);
    if (!$dups) { out('No duplicates found.'); exit(0); }

    $pdo->beginTransaction();
    $keepStmt = $pdo->prepare("SELECT department_id FROM departments WHERE department_name = ? AND sector_id <=> ? ORDER BY department_id ASC");
    $delStmt  = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");

    foreach ($dups as $d) {
        $name = $d['department_name'];
        $sid  = $d['sector_id'] === null ? null : (int)$d['sector_id'];
        $keepStmt->execute([$name, $sid]);
        $rows = $keepStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (count($rows) <= 1) continue;
        $toDelete = array_slice($rows, 1); // keep smallest id
        foreach ($toDelete as $id) {
            $delStmt->execute([$id]);
        }
        out("Cleaned duplicates for '{$name}' (sector_id=" . ($sid===null?'NULL':$sid) . "): removed " . count($toDelete));
    }

    $pdo->commit();
    out('Cleanup complete.');

    // Try to add the unique index now
    try {
        $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY uq_department_sector (department_name, sector_id)");
        out('Added unique index uq_department_sector.');
    } catch (Exception $e) {
        out('Note: unique index may already exist.');
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    out('Error: ' . $e->getMessage());
    exit(1);
}


