-- =====================================================
-- Clearance Forms Progress Column Update Script
-- =====================================================
-- This script updates the clearance_forms table to:
-- 1. Rename 'status' column to 'clearance_form_progress'
-- 2. Update values according to new Clearance Form Progress logic:
--    - 'Unapplied' -> 'unapplied' (when user hasn't applied to any signatory)
--    - 'Pending', 'Processing', 'Approved', 'Rejected' -> 'in-progress' (when user has applied to one or more signatories)
--    - 'Complete' -> 'complete' (when all signatories have approved)
-- =====================================================

-- Start transaction for data safety
START TRANSACTION;

-- Step 1: Add new column with the correct enum values
ALTER TABLE `clearance_forms` 
ADD COLUMN `clearance_form_progress` ENUM('unapplied', 'in-progress', 'complete') 
NOT NULL DEFAULT 'unapplied' 
COMMENT 'Clearance Form Progress: unapplied, in-progress, complete' 
AFTER `clearance_type`;

-- Step 2: Update the new column based on existing status values
-- Map old status values to new clearance_form_progress values
UPDATE `clearance_forms` 
SET `clearance_form_progress` = 
    CASE 
        WHEN `status` = 'Unapplied' THEN 'unapplied'
        WHEN `status` IN ('Pending', 'Processing', 'Approved', 'Rejected') THEN 'in-progress'
        WHEN `status` = 'Complete' THEN 'complete'
        ELSE 'unapplied'  -- Default fallback
    END;

-- Step 3: Verify the data migration
-- Show count of records by new clearance_form_progress values
SELECT 
    clearance_form_progress,
    COUNT(*) as record_count,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM clearance_forms), 2), '%') as percentage
FROM `clearance_forms` 
GROUP BY clearance_form_progress
ORDER BY 
    CASE clearance_form_progress
        WHEN 'unapplied' THEN 1
        WHEN 'in-progress' THEN 2
        WHEN 'complete' THEN 3
    END;

-- Step 4: Show sample of updated records
SELECT 
    clearance_form_id,
    user_id,
    clearance_type,
    status as old_status,
    clearance_form_progress as new_progress,
    created_at
FROM `clearance_forms` 
LIMIT 10;

-- Step 5: Drop the old status column (commented out for safety - uncomment after verification)
-- ALTER TABLE `clearance_forms` DROP COLUMN `status`;

-- Step 6: Add index for performance on the new column
ALTER TABLE `clearance_forms` 
ADD INDEX `idx_clearance_form_progress` (`clearance_form_progress`);

-- Step 7: Update table comment to reflect the new structure
ALTER TABLE `clearance_forms` 
COMMENT = 'Clearance forms with sector-based clearance types and progress tracking (College, Senior High School, Faculty)';

-- =====================================================
-- VERIFICATION QUERIES (Run these to check the results)
-- =====================================================

-- Check if all records were updated correctly
SELECT 
    'Migration Summary' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN clearance_form_progress = 'unapplied' THEN 1 ELSE 0 END) as unapplied_count,
    SUM(CASE WHEN clearance_form_progress = 'in-progress' THEN 1 ELSE 0 END) as in_progress_count,
    SUM(CASE WHEN clearance_form_progress = 'complete' THEN 1 ELSE 0 END) as complete_count
FROM `clearance_forms`;

-- Check for any potential data issues
SELECT 
    'Data Quality Check' as info,
    COUNT(*) as total_records,
    COUNT(clearance_form_progress) as non_null_progress,
    COUNT(*) - COUNT(clearance_form_progress) as null_progress_count
FROM `clearance_forms`;

-- Show distribution by clearance_type
SELECT 
    clearance_type,
    clearance_form_progress,
    COUNT(*) as count
FROM `clearance_forms` 
GROUP BY clearance_type, clearance_form_progress
ORDER BY clearance_type, clearance_form_progress;

-- =====================================================
-- ROLLBACK INSTRUCTIONS (if needed)
-- =====================================================
-- If you need to rollback this change, run:
-- ROLLBACK;
-- 
-- Or if you've already committed and need to revert:
-- ALTER TABLE `clearance_forms` DROP COLUMN `clearance_form_progress`;
-- ALTER TABLE `clearance_forms` ADD COLUMN `status` ENUM('Unapplied','Pending','Processing','Approved','Rejected') DEFAULT 'Unapplied';

-- Commit the transaction
COMMIT;

-- =====================================================
-- FINAL SUCCESS MESSAGE
-- =====================================================
SELECT 'SUCCESS: Clearance Forms Progress column has been updated successfully!' as status;
