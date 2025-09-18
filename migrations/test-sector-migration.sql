-- Test Script for Sector-Based Clearance System Migration
-- This script validates that all changes were applied correctly

-- =====================================================
-- TEST 1: Verify clearance_periods table structure
-- =====================================================

-- Check if sector column exists
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'clearance_periods' 
AND COLUMN_NAME = 'sector';

-- Check if status enum was updated
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'clearance_periods' 
AND COLUMN_NAME = 'status';

-- =====================================================
-- TEST 2: Verify students table structure
-- =====================================================

-- Check if sector column exists in students table
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'students' 
AND COLUMN_NAME = 'sector';

-- =====================================================
-- TEST 3: Verify faculty table structure
-- =====================================================

-- Check if sector column exists in faculty table
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'faculty' 
AND COLUMN_NAME = 'sector';

-- =====================================================
-- TEST 4: Verify new tables exist
-- =====================================================

-- Check if clearance_signatories_new table exists
SELECT 
    TABLE_NAME,
    TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'clearance_signatories_new';

-- Check if clearance_signatory_actions table exists
SELECT 
    TABLE_NAME,
    TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'clearance_signatory_actions';

-- =====================================================
-- TEST 5: Verify clearance_forms table updates
-- =====================================================

-- Check if status enum was updated
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'clearance_forms' 
AND COLUMN_NAME = 'status';

-- Check if grace_period_ends column exists
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'clearance_forms' 
AND COLUMN_NAME = 'grace_period_ends';

-- =====================================================
-- TEST 6: Verify views exist
-- =====================================================

-- Check if views were created
SELECT 
    TABLE_NAME,
    TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN ('active_clearance_periods', 'clearance_forms_with_sector')
AND TABLE_TYPE = 'VIEW';

-- =====================================================
-- TEST 7: Verify stored procedures exist
-- =====================================================

-- Check if stored procedures were created
SELECT 
    ROUTINE_NAME,
    ROUTINE_TYPE
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_NAME IN ('StartClearancePeriod', 'CloseClearancePeriod')
AND ROUTINE_SCHEMA = DATABASE();

-- =====================================================
-- TEST 8: Test data integrity
-- =====================================================

-- Check if existing clearance_periods have sector values
SELECT 
    period_id,
    sector,
    status,
    start_date,
    end_date
FROM clearance_periods
LIMIT 5;

-- Check if existing students have sector values
SELECT 
    student_id,
    sector,
    enrollment_status
FROM students
LIMIT 5;

-- Check if existing faculty have sector values
SELECT 
    employee_number,
    sector,
    employment_status
FROM faculty
LIMIT 5;

-- =====================================================
-- TEST 9: Test foreign key constraints
-- =====================================================

-- Test clearance_signatories_new foreign keys
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'clearance_signatories_new'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Test clearance_signatory_actions foreign keys
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'clearance_signatory_actions'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- TEST 10: Test indexes
-- =====================================================

-- Check if indexes were created on clearance_periods
SELECT 
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'clearance_periods'
AND INDEX_NAME LIKE 'idx_%';

-- Check if indexes were created on students
SELECT 
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'students'
AND INDEX_NAME LIKE 'idx_%';

-- Check if indexes were created on faculty
SELECT 
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'faculty'
AND INDEX_NAME LIKE 'idx_%';

-- =====================================================
-- TEST SUMMARY
-- =====================================================

SELECT 'Migration validation complete. Check results above for any issues.' as summary;
