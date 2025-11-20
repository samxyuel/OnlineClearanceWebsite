-- Add ended_at columns to semesters and academic_years tables
-- This script adds the ended_at timestamp columns needed for term and school year ending logic

-- Add ended_at column to semesters table
ALTER TABLE `semesters` 
ADD COLUMN `ended_at` datetime DEFAULT NULL COMMENT 'Timestamp when the semester was ended' 
AFTER `updated_at`;

-- Add ended_at column to academic_years table
ALTER TABLE `academic_years` 
ADD COLUMN `ended_at` datetime DEFAULT NULL COMMENT 'Timestamp when the academic year was ended' 
AFTER `updated_at`;

-- Add indexes for better query performance
ALTER TABLE `semesters` 
ADD KEY `idx_semesters_ended_at` (`ended_at`);

ALTER TABLE `academic_years` 
ADD KEY `idx_academic_years_ended_at` (`ended_at`);

