<?php
/**
 * Department Helper Functions
 * Provides cross-sector department matching utilities
 */

/**
 * Get all department IDs that match a Program Head's assigned departments across all sectors
 *
 * @param PDO $pdo Database connection
 * @param int $userId Program Head user ID
 * @return array Array of department IDs across all sectors
 */
function getCrossSectorDepartmentIds($pdo, $userId) {
    // Get Program Head's assigned department names/codes
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            COALESCE(d.department_code, d.department_name) as dept_identifier
        FROM user_department_assignments uda
        JOIN departments d ON uda.department_id = d.department_id
        WHERE uda.user_id = ? AND uda.is_active = 1
    ");
    $stmt->execute([$userId]);
    $identifiers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($identifiers)) {
        return [];
    }

    // Get ALL department_ids that match these identifiers (across all sectors)
    $placeholders = implode(',', array_fill(0, count($identifiers), '?'));
    $stmt = $pdo->prepare("
        SELECT department_id
        FROM departments
        WHERE (department_name IN ($placeholders) OR department_code IN ($placeholders))
        AND is_active = 1
    ");
    $stmt->execute(array_merge($identifiers, $identifiers));
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get department identifiers (name/code) from department IDs
 *
 * @param PDO $pdo Database connection
 * @param array $departmentIds Array of department IDs
 * @return array Array of department identifiers
 */
function getDepartmentIdentifiers($pdo, $departmentIds) {
    if (empty($departmentIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            COALESCE(department_code, department_name) as dept_identifier
        FROM departments
        WHERE department_id IN ($placeholders)
        AND is_active = 1
    ");
    $stmt->execute($departmentIds);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Check if a department exists in a specific sector
 *
 * @param PDO $pdo Database connection
 * @param string $deptName Department name
 * @param string $deptCode Department code
 * @param int $sectorId Sector ID
 * @return bool True if department exists in sector
 */
function checkDepartmentExistsInSector($pdo, $deptName, $deptCode, $sectorId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM departments
        WHERE sector_id = ?
        AND is_active = 1
        AND (
            department_name = ?
            OR (department_code IS NOT NULL AND department_code = ?)
        )
    ");
    $stmt->execute([$sectorId, $deptName, $deptCode]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get all sectors where a department exists
 *
 * @param PDO $pdo Database connection
 * @param string $deptName Department name
 * @param string $deptCode Department code
 * @return array Array of sector information
 */
function getDepartmentSectors($pdo, $deptName, $deptCode) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            d.sector_id,
            s.sector_name,
            d.department_id
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE d.is_active = 1
        AND (
            d.department_name = ?
            OR (d.department_code IS NOT NULL AND d.department_code = ?)
        )
        ORDER BY s.sector_name
    ");
    $stmt->execute([$deptName, $deptCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

