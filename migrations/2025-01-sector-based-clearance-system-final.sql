-- Migration: Sector-Based Clearance System - Final Implementation
-- Date: 2025-01-11
-- Description: Implements sector-based clearance system with independent clearance periods per sector

-- =====================================================
-- STEP 1: Update clearance_periods table to support sectors
-- =====================================================

-- Add sector column to clearance_periods table
ALTER TABLE `clearance_periods` 
ADD COLUMN `sector` ENUM('College','Senior High School','Faculty') NOT NULL DEFAULT 'College' AFTER `semester_id`;

-- Update status enum to include new states
ALTER TABLE `clearance_periods` 
MODIFY COLUMN `status` ENUM('Not Started','Ongoing','Paused','Closed') NOT NULL DEFAULT 'Not Started';

-- Add indexes for better performance
ALTER TABLE `clearance_periods` 
ADD INDEX `idx_clearance_periods_sector` (`sector`),
ADD INDEX `idx_clearance_periods_academic_semester` (`academic_year_id`, `semester_id`),
ADD INDEX `idx_clearance_periods_sector_status` (`sector`, `status`);

-- Update existing periods to have a default sector (College)
UPDATE `clearance_periods` SET `sector` = 'College' WHERE `sector` = '';

-- =====================================================
-- STEP 2: Update students table to include sector
-- =====================================================

-- Add sector column to students table
ALTER TABLE `students` 
ADD COLUMN `sector` ENUM('College','Senior High School') NOT NULL DEFAULT 'College' AFTER `department_id`;

-- Add index for sector-based queries
ALTER TABLE `students` 
ADD INDEX `idx_students_sector` (`sector`);

-- Determine sector based on existing data
-- This is a placeholder - actual sector assignment should be based on business logic
UPDATE `students` SET `sector` = 'College' WHERE `sector` = '';

-- =====================================================
-- STEP 3: Update faculty table to include sector
-- =====================================================

-- Add sector column to faculty table
ALTER TABLE `faculty` 
ADD COLUMN `sector` ENUM('College','Senior High School','Faculty') NOT NULL DEFAULT 'Faculty' AFTER `department_id`;

-- Add index for sector-based queries
ALTER TABLE `faculty` 
ADD INDEX `idx_faculty_sector` (`sector`);

-- Set default sector for existing faculty
UPDATE `faculty` SET `sector` = 'Faculty' WHERE `sector` = '';

-- =====================================================
-- STEP 4: Create new clearance_signatories table for sector-based assignments
-- =====================================================

-- Create new clearance_signatories table for sector-based period assignments
CREATE TABLE `clearance_signatories_new` (
  `signatory_id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(8) NOT NULL COMMENT 'Employee number from staff table',
  `clearance_period_id` int(11) NOT NULL COMMENT 'FK to clearance_periods',
  `designation_id` int(11) NOT NULL COMMENT 'FK to designations',
  `department_id` int(11) DEFAULT NULL COMMENT 'For department-specific signatories',
  `is_required_first` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign first',
  `is_required_last` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign last',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'FALSE to temporarily disable assignment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`signatory_id`),
  KEY `idx_clearance_signatories_staff` (`staff_id`),
  KEY `idx_clearance_signatories_period` (`clearance_period_id`),
  KEY `idx_clearance_signatories_designation` (`designation_id`),
  KEY `idx_clearance_signatories_department` (`department_id`),
  KEY `idx_clearance_signatories_active` (`is_active`),
  CONSTRAINT `fk_clearance_signatories_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`employee_number`) ON DELETE CASCADE,
  CONSTRAINT `fk_clearance_signatories_period` FOREIGN KEY (`clearance_period_id`) REFERENCES `clearance_periods` (`period_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_clearance_signatories_designation` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_clearance_signatories_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STEP 5: Create clearance_signatory_actions table for tracking actions
-- =====================================================

CREATE TABLE `clearance_signatory_actions` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `clearance_form_id` varchar(20) NOT NULL COMMENT 'FK to clearance_forms',
  `signatory_id` int(11) NOT NULL COMMENT 'FK to clearance_signatories_new',
  `action` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL COMMENT 'General remarks',
  `rejection_reason_id` int(11) DEFAULT NULL COMMENT 'Predefined rejection reason',
  `additional_remarks` text DEFAULT NULL COMMENT 'Additional details for rejection',
  `date_signed` timestamp NULL DEFAULT NULL COMMENT 'When action was taken',
  `grace_period_ends` timestamp NULL DEFAULT NULL COMMENT 'End of 5-minute grace period',
  `is_undone` tinyint(1) DEFAULT 0 COMMENT 'TRUE if action was undone during grace period',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`action_id`),
  KEY `idx_signatory_actions_form` (`clearance_form_id`),
  KEY `idx_signatory_actions_signatory` (`signatory_id`),
  KEY `idx_signatory_actions_action` (`action`),
  KEY `idx_signatory_actions_grace_period` (`grace_period_ends`),
  CONSTRAINT `fk_signatory_actions_form` FOREIGN KEY (`clearance_form_id`) REFERENCES `clearance_forms` (`clearance_form_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_signatory_actions_signatory` FOREIGN KEY (`signatory_id`) REFERENCES `clearance_signatories_new` (`signatory_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_signatory_actions_rejection_reason` FOREIGN KEY (`rejection_reason_id`) REFERENCES `rejection_reasons` (`reason_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STEP 6: Update clearance_forms table to support new workflow
-- =====================================================

-- Update status enum to include new states
ALTER TABLE `clearance_forms` 
MODIFY COLUMN `status` ENUM('Unapplied','Pending','Processing','Approved','Rejected') DEFAULT 'Unapplied';

-- Add grace period tracking
ALTER TABLE `clearance_forms` 
ADD COLUMN `grace_period_ends` timestamp NULL DEFAULT NULL COMMENT 'End of grace period for final status update' AFTER `rejected_at`;

-- Add indexes for better performance
ALTER TABLE `clearance_forms` 
ADD INDEX `idx_clearance_forms_sector` (`clearance_type`),
ADD INDEX `idx_clearance_forms_status` (`status`),
ADD INDEX `idx_clearance_forms_grace_period` (`grace_period_ends`);

-- =====================================================
-- STEP 7: Migrate existing signatory assignments to new structure
-- =====================================================

-- Migrate from sector_signatory_assignments to clearance_signatories_new
-- This will be done when clearance periods are created for each sector
INSERT INTO `clearance_signatories_new` (
    `staff_id`, 
    `designation_id`, 
    `department_id`, 
    `is_active`
)
SELECT 
    ssa.user_id as staff_id,
    ssa.designation_id,
    ssa.department_id,
    ssa.is_active
FROM `sector_signatory_assignments` ssa
JOIN `staff` s ON s.user_id = ssa.user_id
WHERE ssa.is_active = 1;

-- =====================================================
-- STEP 8: Create views for easier data access
-- =====================================================

-- Create view for active clearance periods by sector
CREATE VIEW `active_clearance_periods` AS
SELECT 
    cp.period_id,
    cp.academic_year_id,
    cp.semester_id,
    cp.sector,
    cp.status,
    cp.start_date,
    cp.end_date,
    ay.year as academic_year,
    s.semester_name
FROM `clearance_periods` cp
JOIN `academic_years` ay ON cp.academic_year_id = ay.academic_year_id
JOIN `semesters` s ON cp.semester_id = s.semester_id
WHERE cp.status IN ('Ongoing', 'Paused');

-- Create view for clearance forms with sector information
CREATE VIEW `clearance_forms_with_sector` AS
SELECT 
    cf.clearance_form_id,
    cf.user_id,
    cf.academic_year_id,
    cf.semester_id,
    cf.clearance_type,
    cf.status,
    cf.applied_at,
    cf.completed_at,
    cf.rejected_at,
    cf.grace_period_ends,
    ay.year as academic_year,
    s.semester_name,
    cp.period_id as clearance_period_id,
    cp.sector,
    cp.status as period_status
FROM `clearance_forms` cf
JOIN `academic_years` ay ON cf.academic_year_id = ay.academic_year_id
JOIN `semesters` s ON cf.semester_id = s.semester_id
LEFT JOIN `clearance_periods` cp ON (
    cf.academic_year_id = cp.academic_year_id 
    AND cf.semester_id = cp.semester_id 
    AND cf.clearance_type = cp.sector
);

-- =====================================================
-- STEP 9: Create stored procedures for common operations
-- =====================================================

DELIMITER $$

-- Procedure to start clearance period for a sector
CREATE PROCEDURE `StartClearancePeriod`(
    IN p_academic_year_id INT,
    IN p_semester_id INT,
    IN p_sector VARCHAR(20),
    IN p_start_date DATE
)
BEGIN
    DECLARE v_period_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Create new clearance period
    INSERT INTO `clearance_periods` (
        `academic_year_id`, 
        `semester_id`, 
        `sector`, 
        `status`, 
        `start_date`, 
        `end_date`
    ) VALUES (
        p_academic_year_id, 
        p_semester_id, 
        p_sector, 
        'Ongoing', 
        p_start_date, 
        DATE_ADD(p_start_date, INTERVAL 3 MONTH)
    );
    
    SET v_period_id = LAST_INSERT_ID();
    
    -- Create clearance forms for eligible users
    IF p_sector = 'College' THEN
        INSERT INTO `clearance_forms` (
            `clearance_form_id`,
            `user_id`, 
            `academic_year_id`, 
            `semester_id`, 
            `clearance_type`, 
            `status`
        )
        SELECT 
            CONCAT('CF-', YEAR(CURDATE()), '-', LPAD(ROW_NUMBER() OVER(), 5, '0')),
            s.user_id,
            p_academic_year_id,
            p_semester_id,
            'College',
            'Unapplied'
        FROM `students` s
        WHERE s.sector = 'College' 
        AND s.enrollment_status = 'Enrolled'
        AND s.user_id IS NOT NULL;
        
    ELSEIF p_sector = 'Senior High School' THEN
        INSERT INTO `clearance_forms` (
            `clearance_form_id`,
            `user_id`, 
            `academic_year_id`, 
            `semester_id`, 
            `clearance_type`, 
            `status`
        )
        SELECT 
            CONCAT('CF-', YEAR(CURDATE()), '-', LPAD(ROW_NUMBER() OVER(), 5, '0')),
            s.user_id,
            p_academic_year_id,
            p_semester_id,
            'Senior High School',
            'Unapplied'
        FROM `students` s
        WHERE s.sector = 'Senior High School' 
        AND s.enrollment_status = 'Enrolled'
        AND s.user_id IS NOT NULL;
        
    ELSEIF p_sector = 'Faculty' THEN
        INSERT INTO `clearance_forms` (
            `clearance_form_id`,
            `user_id`, 
            `academic_year_id`, 
            `semester_id`, 
            `clearance_type`, 
            `status`
        )
        SELECT 
            CONCAT('CF-', YEAR(CURDATE()), '-', LPAD(ROW_NUMBER() OVER(), 5, '0')),
            f.user_id,
            p_academic_year_id,
            p_semester_id,
            'Faculty',
            'Unapplied'
        FROM `faculty` f
        WHERE f.user_id IS NOT NULL;
    END IF;
    
    COMMIT;
    
    SELECT v_period_id as period_id, 'Clearance period started successfully' as message;
END$$

-- Procedure to close clearance period
CREATE PROCEDURE `CloseClearancePeriod`(
    IN p_period_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Update period status
    UPDATE `clearance_periods` 
    SET `status` = 'Closed', 
        `end_date` = CURDATE(),
        `updated_at` = NOW()
    WHERE `period_id` = p_period_id;
    
    -- Update any pending forms to rejected
    UPDATE `clearance_forms` cf
    JOIN `clearance_periods` cp ON (
        cf.academic_year_id = cp.academic_year_id 
        AND cf.semester_id = cp.semester_id 
        AND cf.clearance_type = cp.sector
    )
    SET cf.status = 'Rejected',
        cf.rejected_at = NOW(),
        cf.updated_at = NOW()
    WHERE cp.period_id = p_period_id 
    AND cf.status IN ('Unapplied', 'Pending', 'Processing');
    
    COMMIT;
    
    SELECT 'Clearance period closed successfully' as message;
END$$

DELIMITER ;

-- =====================================================
-- STEP 10: Add comments for documentation
-- =====================================================

ALTER TABLE `clearance_periods` COMMENT = 'Clearance periods with sector support (College, Senior High School, Faculty)';
ALTER TABLE `students` COMMENT = 'Students with sector assignment (College, Senior High School)';
ALTER TABLE `faculty` COMMENT = 'Faculty with sector assignment (College, Senior High School, Faculty)';
ALTER TABLE `clearance_signatories_new` COMMENT = 'Sector-based signatory assignments for clearance periods';
ALTER TABLE `clearance_signatory_actions` COMMENT = 'Individual signatory actions with grace period support';

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

-- Summary of changes:
-- 1. Added sector support to clearance_periods, students, and faculty tables
-- 2. Created new clearance_signatories_new table for sector-based assignments
-- 3. Created clearance_signatory_actions table for tracking actions with grace period
-- 4. Updated clearance_forms table to support new workflow states
-- 5. Created views for easier data access
-- 6. Created stored procedures for common operations
-- 7. Added comprehensive indexing for performance
-- 8. Migrated existing signatory assignments to new structure
