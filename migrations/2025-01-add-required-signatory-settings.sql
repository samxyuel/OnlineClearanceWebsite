-- ================================================
-- Add Required First/Last Signatory Settings
-- ================================================

-- Add new columns to scope_settings table
ALTER TABLE scope_settings 
ADD COLUMN required_first_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN required_first_designation_id INT NULL,
ADD COLUMN required_last_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN required_last_designation_id INT NULL;

-- Add foreign key constraints
ALTER TABLE scope_settings 
ADD CONSTRAINT fk_scope_settings_first_designation 
FOREIGN KEY (required_first_designation_id) REFERENCES designations(designation_id) 
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE scope_settings 
ADD CONSTRAINT fk_scope_settings_last_designation 
FOREIGN KEY (required_last_designation_id) REFERENCES designations(designation_id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add indexes for performance
ALTER TABLE scope_settings 
ADD INDEX idx_required_first (required_first_enabled, required_first_designation_id),
ADD INDEX idx_required_last (required_last_enabled, required_last_designation_id);

-- Update existing scope_settings to have default values
UPDATE scope_settings SET 
required_first_enabled = 0,
required_last_enabled = 0
WHERE required_first_enabled IS NULL OR required_last_enabled IS NULL;
