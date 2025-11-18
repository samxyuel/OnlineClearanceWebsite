-- Migration: Add encrypted answer columns for display purposes
-- This implements dual storage: hashed answers for verification, encrypted answers for display
-- Date: 2024

USE `online_clearance_db`;

-- Add encrypted answer columns for display
ALTER TABLE `user_security_questions`
ADD COLUMN `answer_1_display` TEXT NULL COMMENT 'Encrypted answer for display purposes',
ADD COLUMN `answer_2_display` TEXT NULL COMMENT 'Encrypted answer for display purposes',
ADD COLUMN `answer_3_display` TEXT NULL COMMENT 'Encrypted answer for display purposes';

-- Note: Existing records will have NULL values for display columns
-- Users with existing security questions will need to re-enter them to populate display columns

