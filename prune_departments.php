<?php
// Remove old departments not in the approved list per sector
require_once __DIR__ . '/includes/config/database.php';

function out($m){ echo $m . "\n"; }

try {
    $pdo = Database::getInstance()->getConnection();
    out('Connected.');

    // Approved lists per sector
    $approved = [
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

    // Resolve sector ids
    $getSectorId = $pdo->prepare("SELECT sector_id FROM sectors WHERE sector_name = ? LIMIT 1");
    $secIds = [];
    foreach ($approved as $sectorName => $_) {
        $getSectorId->execute([$sectorName]);
        $sid = $getSectorId->fetchColumn();
        if ($sid) { $secIds[$sectorName] = (int)$sid; }
    }

    $pdo->beginTransaction();
    $delStmt = $pdo->prepare("DELETE FROM departments WHERE sector_id = :sid AND department_name NOT IN (:n1)");

    // Because PDO doesn't allow array expansion in IN() directly, prepare per-sector statement dynamically
    foreach ($approved as $sectorName => $names) {
        $sid = $secIds[$sectorName] ?? null;
        if (!$sid) { out("Skipping sector (not found): $sectorName"); continue; }

        // Build dynamic IN list
        $ph = [];
        $params = [':sid' => $sid];
        foreach ($names as $idx => $name) {
            $key = ':n' . $idx;
            $ph[] = $key;
            $params[$key] = $name;
        }
        $sql = 'DELETE FROM departments WHERE sector_id = :sid AND department_name NOT IN (' . implode(',', $ph) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        out("Pruned old departments for sector: $sectorName");
    }

    $pdo->commit();
    out('Pruning complete.');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    out('Error: ' . $e->getMessage());
    exit(1);
}


