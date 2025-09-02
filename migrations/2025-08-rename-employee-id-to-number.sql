-- Migration: Rename employee_id to employee_number for faculty and staff
-- Date: 2025-08-25
-- Safe to run once; verify backups before applying

START TRANSACTION;

-- Faculty table
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faculty' AND COLUMN_NAME = 'employee_number');
SET @has_old := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faculty' AND COLUMN_NAME = 'employee_id');

-- Rename column if old exists and new does not
SET @sql := IF(@has_old = 1 AND @has_col = 0,
    'ALTER TABLE faculty CHANGE COLUMN employee_id employee_number VARCHAR(8) NOT NULL COMMENT "Employee Number format: LCA123P"',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Recreate PK if needed
SET @drop_pk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faculty' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
-- Primary key remains; ensure it references employee_number
-- MySQL keeps PK on CHANGE COLUMN; else you can drop/add PK explicitly.

-- Staff table
SET @has_col2 := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'employee_number');
SET @has_old2 := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'employee_id');

SET @sql2 := IF(@has_old2 = 1 AND @has_col2 = 0,
    'ALTER TABLE staff CHANGE COLUMN employee_id employee_number VARCHAR(8) NOT NULL COMMENT "Employee Number format: LCA123P"',
    'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Optional: regenerate dependent indexes if your engine didn''t carry them over
-- Example (no-op if index names unchanged):
-- ALTER TABLE faculty DROP PRIMARY KEY, ADD PRIMARY KEY (employee_number);
-- ALTER TABLE staff DROP PRIMARY KEY, ADD PRIMARY KEY (employee_number);

COMMIT;

-- One-time data cleanup plan (manual step)
-- 1) Prepare mapping CSV (old,new): EMP00001,LCA123P
-- 2) Apply updates:
--    UPDATE faculty f JOIN mapping m ON f.employee_number = m.old SET f.employee_number = m.new;
--    UPDATE staff   s JOIN mapping m ON s.employee_number = m.old SET s.employee_number = m.new;
--    UPDATE users   u JOIN mapping m ON u.username        = m.old SET u.username        = m.new;
-- 3) Validate uniqueness and LCA pattern: ^[A-Z]{3}[0-9]{3}[A-Z]$

-- Optional: allow NULL for users.email to avoid import failures when email duplicates exist or are missing
-- Execute separately if desired:
-- ALTER TABLE users MODIFY email VARCHAR(255) NULL;
