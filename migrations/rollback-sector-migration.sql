-- Rollback Script for Sector-Based Clearance System Migration
-- WARNING: This will remove all sector-based enhancements
-- Only run this if you need to revert the migration

-- =====================================================
-- STEP 1: Drop views
-- =====================================================

DROP VIEW IF EXISTS `clearance_forms_with_sector`;
DROP VIEW IF EXISTS `active_clearance_periods`;

-- =====================================================
-- STEP 2: Drop stored procedures
-- =====================================================

DROP PROCEDURE IF EXISTS `StartClearancePeriod`;
DROP PROCEDURE IF EXISTS `CloseClearancePeriod`;

-- =====================================================
-- STEP 3: Drop new tables
-- =====================================================

DROP TABLE IF EXISTS `clearance_signatory_actions`;
DROP TABLE IF EXISTS `clearance_signatories_new`;

-- =====================================================
-- STEP 4: Remove indexes added during migration
-- =====================================================

-- Remove indexes from clearance_periods
ALTER TABLE `clearance_periods` 
DROP INDEX IF EXISTS `idx_clearance_periods_sector`,
DROP INDEX IF EXISTS `idx_clearance_periods_academic_semester`,
DROP INDEX IF EXISTS `idx_clearance_periods_sector_status`;

-- Remove indexes from students
ALTER TABLE `students` 
DROP INDEX IF EXISTS `idx_students_sector`;

-- Remove indexes from faculty
ALTER TABLE `faculty` 
DROP INDEX IF EXISTS `idx_faculty_sector`;

-- Remove indexes from clearance_forms
ALTER TABLE `clearance_forms` 
DROP INDEX IF EXISTS `idx_clearance_forms_sector`,
DROP INDEX IF EXISTS `idx_clearance_forms_status`,
DROP INDEX IF EXISTS `idx_clearance_forms_grace_period`;

-- =====================================================
-- STEP 5: Remove columns added during migration
-- =====================================================

-- Remove sector column from clearance_periods
ALTER TABLE `clearance_periods` 
DROP COLUMN IF EXISTS `sector`;

-- Remove sector column from students
ALTER TABLE `students` 
DROP COLUMN IF EXISTS `sector`;

-- Remove sector column from faculty
ALTER TABLE `faculty` 
DROP COLUMN IF EXISTS `sector`;

-- Remove grace_period_ends column from clearance_forms
ALTER TABLE `clearance_forms` 
DROP COLUMN IF EXISTS `grace_period_ends`;

-- =====================================================
-- STEP 6: Restore original enum values
-- =====================================================

-- Restore original status enum for clearance_periods
ALTER TABLE `clearance_periods` 
MODIFY COLUMN `status` ENUM('inactive','active','deactivated','ended') NOT NULL DEFAULT 'inactive';

-- Restore original status enum for clearance_forms
ALTER TABLE `clearance_forms` 
MODIFY COLUMN `status` ENUM('Unapplied','Applied','In Progress','Completed','Rejected') DEFAULT 'Unapplied';

-- =====================================================
-- STEP 7: Restore original table comments
-- =====================================================

ALTER TABLE `clearance_periods` COMMENT = '';
ALTER TABLE `students` COMMENT = '';
ALTER TABLE `faculty` COMMENT = '';

-- =====================================================
-- STEP 8: Clean up any data that might have been affected
-- =====================================================

-- Reset any clearance_periods that might have been modified
-- (This is optional and depends on your data)

-- =====================================================
-- ROLLBACK COMPLETE
-- =====================================================

SELECT 'Rollback completed successfully. All sector-based enhancements have been removed.' as message;
