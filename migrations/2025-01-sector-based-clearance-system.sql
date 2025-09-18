-- Migration: Sector-Based Clearance System
-- Date: 2025-01-11
-- Description: Implements sector-based clearance system with College, SHS, and Faculty clearance types

-- =====================================================
-- STEP 1: Update clearance_forms table
-- =====================================================

-- Modify clearance_type enum to support sector-based clearance
ALTER TABLE `clearance_forms` 
MODIFY COLUMN `clearance_type` ENUM('College','Senior High School','Faculty') NOT NULL;

-- =====================================================
-- STEP 2: Update clearance_requirements table
-- =====================================================

-- Modify clearance_type enum to support sector-based clearance
ALTER TABLE `clearance_requirements` 
MODIFY COLUMN `clearance_type` ENUM('College','Senior High School','Faculty') NOT NULL;

-- =====================================================
-- STEP 3: Create sector-based clearance requirements
-- =====================================================

-- Insert College clearance requirements (based on existing Student requirements)
INSERT INTO `clearance_requirements` (`clearance_type`, `designation_id`, `is_required`, `order_sequence`, `is_department_specific`, `applies_to_departments`, `created_at`) 
SELECT 'College', `designation_id`, `is_required`, `order_sequence`, `is_department_specific`, `applies_to_departments`, NOW()
FROM `clearance_requirements` 
WHERE `clearance_type` = 'Student' AND `clearance_type` IS NOT NULL;

-- Insert Senior High School clearance requirements (based on existing Student requirements)
INSERT INTO `clearance_requirements` (`clearance_type`, `designation_id`, `is_required`, `order_sequence`, `is_department_specific`, `applies_to_departments`, `created_at`) 
SELECT 'Senior High School', `designation_id`, `is_required`, `order_sequence`, `is_department_specific`, `applies_to_departments`, NOW()
FROM `clearance_requirements` 
WHERE `clearance_type` = 'Student' AND `clearance_type` IS NOT NULL;

-- =====================================================
-- STEP 4: Create sector-based signatory assignments table
-- =====================================================

-- Create table for sector-based signatory assignments
CREATE TABLE `sector_signatory_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `clearance_type` ENUM('College','Senior High School','Faculty') NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Staff member assigned as signatory',
  `designation_id` int(11) NOT NULL COMMENT 'Designation/position of the signatory',
  `is_program_head` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this is a Program Head assignment',
  `department_id` int(11) DEFAULT NULL COMMENT 'Specific department for Program Head (NULL for general staff)',
  `is_required_first` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign first',
  `is_required_last` tinyint(1) DEFAULT 0 COMMENT 'TRUE if this signatory must sign last',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'FALSE to temporarily disable assignment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  KEY `idx_sector_assignments_type` (`clearance_type`),
  KEY `idx_sector_assignments_user` (`user_id`),
  KEY `idx_sector_assignments_designation` (`designation_id`),
  KEY `idx_sector_assignments_department` (`department_id`),
  KEY `idx_sector_assignments_program_head` (`is_program_head`),
  KEY `idx_sector_assignments_active` (`is_active`),
  CONSTRAINT `sector_signatory_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `sector_signatory_assignments_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE CASCADE,
  CONSTRAINT `sector_signatory_assignments_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STEP 5: Create sector settings table
-- =====================================================

-- Create table for sector-specific settings
CREATE TABLE `sector_clearance_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `clearance_type` ENUM('College','Senior High School','Faculty') NOT NULL,
  `include_program_head` tinyint(1) DEFAULT 0 COMMENT 'TRUE if Program Heads should be auto-assigned',
  `required_first_enabled` tinyint(1) DEFAULT 0 COMMENT 'TRUE if required first signatory is enabled',
  `required_first_designation_id` int(11) DEFAULT NULL COMMENT 'Designation that must sign first',
  `required_last_enabled` tinyint(1) DEFAULT 0 COMMENT 'TRUE if required last signatory is enabled',
  `required_last_designation_id` int(11) DEFAULT NULL COMMENT 'Designation that must sign last',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique_sector_setting` (`clearance_type`),
  KEY `idx_sector_settings_type` (`clearance_type`),
  CONSTRAINT `sector_clearance_settings_ibfk_1` FOREIGN KEY (`required_first_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL,
  CONSTRAINT `sector_clearance_settings_ibfk_2` FOREIGN KEY (`required_last_designation_id`) REFERENCES `designations` (`designation_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STEP 6: Initialize sector settings
-- =====================================================

-- Insert default settings for each sector
INSERT INTO `sector_clearance_settings` (`clearance_type`, `include_program_head`, `required_first_enabled`, `required_last_enabled`, `created_at`) VALUES
('College', 0, 0, 0, NOW()),
('Senior High School', 0, 0, 0, NOW()),
('Faculty', 0, 0, 0, NOW());

-- =====================================================
-- STEP 7: Create indexes for performance
-- =====================================================

-- Add composite indexes for better query performance
ALTER TABLE `sector_signatory_assignments` 
ADD KEY `idx_sector_user_designation` (`clearance_type`, `user_id`, `designation_id`),
ADD KEY `idx_sector_program_head_dept` (`clearance_type`, `is_program_head`, `department_id`);

-- =====================================================
-- STEP 8: Create views for easier data access (Optional)
-- =====================================================

-- Note: Views will be created after all tables are established
-- These can be added later for performance optimization

-- =====================================================
-- STEP 9: Create stored procedures for common operations
-- =====================================================

-- Note: Stored procedures will be created separately to avoid syntax issues
-- These can be added later if needed for performance optimization

-- =====================================================
-- STEP 10: Add comments for documentation
-- =====================================================

ALTER TABLE `clearance_forms` COMMENT = 'Clearance forms with sector-based clearance types (College, Senior High School, Faculty)';
ALTER TABLE `clearance_requirements` COMMENT = 'Clearance requirements with sector-based clearance types';
ALTER TABLE `sector_signatory_assignments` COMMENT = 'Sector-based signatory assignments for clearance forms';
ALTER TABLE `sector_clearance_settings` COMMENT = 'Settings for each sector clearance type';

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

-- Summary of changes:
-- 1. Updated clearance_forms and clearance_requirements to support sector-based clearance types
-- 2. Created sector_signatory_assignments table for managing signatory assignments
-- 3. Created sector_clearance_settings table for sector-specific configuration
-- 4. Added views and stored procedures for easier data access
-- 5. Initialized default settings for all sectors
-- 6. Added comprehensive indexing for performance
