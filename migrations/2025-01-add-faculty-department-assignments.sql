-- Migration: Add faculty_department_assignments table for multi-department support
-- Created: 2025-01-14

CREATE TABLE IF NOT EXISTS `faculty_department_assignments` (
  `assignment_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL COMMENT 'Foreign key to users table',
  `department_id` INT NOT NULL COMMENT 'Foreign key to departments table',
  `is_primary` TINYINT(1) DEFAULT 0 COMMENT 'Primary department assignment (1=primary, 0=secondary)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Active/inactive flag',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` INT COMMENT 'User who assigned this',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY `unique_user_dept` (`user_id`, `department_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE CASCADE,
  
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_department_id` (`department_id`),
  INDEX `idx_is_primary` (`is_primary`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Faculty multi-department assignments table';
