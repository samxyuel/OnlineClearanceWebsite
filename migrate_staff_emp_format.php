<?php
// Migration: enforce LCA123P format on staff.employee_number and add helpful constraints
require_once __DIR__ . '/includes/config/database.php';

function indexExists(PDO $pdo, string $table, string $indexName): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $indexName]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // 1) Unique index on staff.employee_number
    if (!indexExists($pdo, 'staff', 'uq_staff_emp')) {
        $pdo->exec("ALTER TABLE staff ADD UNIQUE KEY uq_staff_emp (employee_number)");
        echo "Added unique index uq_staff_emp on staff.employee_number\n";
    } else {
        echo "Index uq_staff_emp already exists\n";
    }

    // 2) CHECK constraint (MySQL 8+) – ignore errors on older versions
    try {
        // Attempt to add only if not present (MySQL <8 doesn't expose check_constraints reliably)
        $pdo->exec("ALTER TABLE staff ADD CONSTRAINT chk_staff_emp_format CHECK (employee_number REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$')");
        echo "Added CHECK constraint chk_staff_emp_format\n";
    } catch (Throwable $e) {
        echo "CHECK constraint skipped: " . $e->getMessage() . "\n";
    }

    // 3) Triggers to UPPER and validate format
    try { $pdo->exec("DROP TRIGGER IF EXISTS staff_bi"); } catch (Throwable $e) {}
    try { $pdo->exec("DROP TRIGGER IF EXISTS staff_bu"); } catch (Throwable $e) {}

    $triggerBI = <<<SQL
CREATE TRIGGER staff_bi BEFORE INSERT ON staff FOR EACH ROW
BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LLLDDDL)';
  END IF;
END
SQL;
    $pdo->exec($triggerBI);
    echo "Created trigger staff_bi\n";

    $triggerBU = <<<SQL
CREATE TRIGGER staff_bu BEFORE UPDATE ON staff FOR EACH ROW
BEGIN
  SET NEW.employee_number = UPPER(NEW.employee_number);
  IF NEW.employee_number NOT REGEXP '^[A-Z]{3}[0-9]{3}[A-Z]$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid employee number format (expected LLLDDDL)';
  END IF;
END
SQL;
    $pdo->exec($triggerBU);
    echo "Created trigger staff_bu\n";

    // 4) Optional: must_change_password on users
    if (!columnExists($pdo, 'users', 'must_change_password')) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
            echo "Added users.must_change_password\n";
        } catch (Throwable $e) {
            echo "Add users.must_change_password failed/skipped: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Column users.must_change_password already exists\n";
    }

    echo "Migration complete.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Migration error: " . $e->getMessage() . "\n";
}
?>

