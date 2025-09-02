-- ================================================
-- goSTI Online Clearance - Seed Staff & Signatories
-- Safe to run multiple times (idempotent inserts)
-- ================================================

START TRANSACTION;

-- --------------------------------
-- 0) Reusable constants
-- --------------------------------
SET @now := NOW();
-- Reuse a known bcrypt (all seeded users will share this hash)
-- You can change later via application or manual update.
SET @default_hash := '$2y$10$MJzbYK1lT6ijvH9E83ZGq.zjWoW.fHT0o9ZzfywwvkeQe54TgLUL.';

-- --------------------------------
-- 1) Ensure sectors table and rows
-- --------------------------------
CREATE TABLE IF NOT EXISTS sectors (
  sector_id   INT AUTO_INCREMENT PRIMARY KEY,
  sector_name VARCHAR(100) NOT NULL UNIQUE,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sectors (sector_name) VALUES
  ('College'), ('Senior High School'), ('Faculty')
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

-- Sector IDs for later use
SET @sec_college := (SELECT sector_id FROM sectors WHERE sector_name='College' LIMIT 1);
SET @sec_shs     := (SELECT sector_id FROM sectors WHERE sector_name='Senior High School' LIMIT 1);
SET @sec_faculty := (SELECT sector_id FROM sectors WHERE sector_name='Faculty' LIMIT 1);

-- ---------------------------------------------------
-- 2) Ensure departments has sector_id and base rows
-- ---------------------------------------------------
-- Add sector_id to departments if missing
-- (MariaDB supports IF NOT EXISTS for columns)
ALTER TABLE departments
  ADD COLUMN IF NOT EXISTS sector_id INT NULL,
  ADD INDEX IF NOT EXISTS idx_sector_id (sector_id);

-- Try add FK (ignore if already exists)
-- (If it errors, it just means it's already there)
DO
  (SELECT 1 FROM DUAL WHERE 1=0);
-- Best-effort FK add
SET @fk_err := 0;
DO
  (SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'departments'
     AND CONSTRAINT_NAME = 'fk_departments_sector_id'
   LIMIT 1);
-- Try to add FK (will fail harmlessly if exists)
-- Wrap in handler
BEGIN
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET @fk_err := 1;
  SET @sql := 'ALTER TABLE departments ADD CONSTRAINT fk_departments_sector_id FOREIGN KEY (sector_id) REFERENCES sectors(sector_id) ON DELETE SET NULL ON UPDATE CASCADE';
  PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
END;

-- Backfill sector_id using legacy department_type if present
UPDATE departments d
JOIN sectors s ON s.sector_name = d.department_type
SET d.sector_id = s.sector_id
WHERE d.sector_id IS NULL
  AND d.department_type IN ('College','Senior High School');

-- Ensure baseline departments exist for each sector
-- College
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'Information & Communication Technology', @sec_college, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='Information & Communication Technology' AND sector_id=@sec_college);
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'Business, Arts, & Science', @sec_college, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='Business, Arts, & Science' AND sector_id=@sec_college);
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'Tourism & Hospitality Management', @sec_college, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='Tourism & Hospitality Management' AND sector_id=@sec_college);

-- Senior High School
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'Academic Track', @sec_shs, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='Academic Track' AND sector_id=@sec_shs);
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'Technological-Vocational Livelihood', @sec_shs, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='Technological-Vocational Livelihood' AND sector_id=@sec_shs);
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'Home Economics', @sec_shs, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='Home Economics' AND sector_id=@sec_shs);

-- Faculty
INSERT INTO departments (department_name, sector_id, is_active, created_at, updated_at)
SELECT 'General Education', @sec_faculty, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name='General Education' AND sector_id=@sec_faculty);

-- --------------------------------
-- 3) Ensure designations exist
-- --------------------------------
-- Helper: insert-if-not-exists
-- (repeat for all required designations)
INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'PAMO', 'Purchasing and Assets Management Officer', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='PAMO');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'MIS/IT', 'IT and MIS staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='MIS/IT');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Petty Cash Custodian', 'Petty Cash Custodian', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Petty Cash Custodian');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Building Administrator', 'Building and facilities staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Building Administrator');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Accountant', 'Accounting staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Accountant');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Academic Head', 'Academic Head', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Academic Head');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'School Administrator', 'School Administrator', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='School Administrator');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'HR', 'Human Resources staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='HR');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Guidance', 'Guidance office', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Guidance');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Disciplinary Officer', 'Disciplinary office', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Disciplinary Officer');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Clinic', 'Clinic staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Clinic');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Librarian', 'Library staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Librarian');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Alumni Placement Officer', 'Alumni Placement Officer', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Alumni Placement Officer');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Student Affairs Officer', 'Student Affairs Officer', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Student Affairs Officer');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Registrar', 'Registrar office staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Registrar');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Cashier', 'Cashier office staff', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Cashier');

INSERT INTO designations (designation_name, description, is_active, created_at)
SELECT 'Program Head', 'Department program head', 1, @now
WHERE NOT EXISTS (SELECT 1 FROM designations WHERE designation_name='Program Head');

-- Grab IDs for mapping
SET @desig_pamo   := (SELECT designation_id FROM designations WHERE designation_name='PAMO' LIMIT 1);
SET @desig_misit  := (SELECT designation_id FROM designations WHERE designation_name='MIS/IT' LIMIT 1);
SET @desig_petty  := (SELECT designation_id FROM designations WHERE designation_name='Petty Cash Custodian' LIMIT 1);
SET @desig_bldg   := (SELECT designation_id FROM designations WHERE designation_name='Building Administrator' LIMIT 1);
SET @desig_acct   := (SELECT designation_id FROM designations WHERE designation_name='Accountant' LIMIT 1);
SET @desig_acad   := (SELECT designation_id FROM designations WHERE designation_name='Academic Head' LIMIT 1);
SET @desig_sadmin := (SELECT designation_id FROM designations WHERE designation_name='School Administrator' LIMIT 1);
SET @desig_hr     := (SELECT designation_id FROM designations WHERE designation_name='HR' LIMIT 1);
SET @desig_guid   := (SELECT designation_id FROM designations WHERE designation_name='Guidance' LIMIT 1);
SET @desig_disc   := (SELECT designation_id FROM designations WHERE designation_name='Disciplinary Officer' LIMIT 1);
SET @desig_clinic := (SELECT designation_id FROM designations WHERE designation_name='Clinic' LIMIT 1);
SET @desig_lib    := (SELECT designation_id FROM designations WHERE designation_name='Librarian' LIMIT 1);
SET @desig_alumni := (SELECT designation_id FROM designations WHERE designation_name='Alumni Placement Officer' LIMIT 1);
SET @desig_sao    := (SELECT designation_id FROM designations WHERE designation_name='Student Affairs Officer' LIMIT 1);
SET @desig_reg    := (SELECT designation_id FROM designations WHERE designation_name='Registrar' LIMIT 1);
SET @desig_cash   := (SELECT designation_id FROM designations WHERE designation_name='Cashier' LIMIT 1);
SET @desig_ph     := (SELECT designation_id FROM designations WHERE designation_name='Program Head' LIMIT 1);

-- --------------------------------
-- 4) Ensure signatory_assignments
-- --------------------------------
CREATE TABLE IF NOT EXISTS signatory_assignments (
  assignment_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  designation_id INT NOT NULL,
  clearance_type ENUM('student','faculty') NULL,
  department_id INT NULL,
  sector_id     INT NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sa_user   FOREIGN KEY (user_id)        REFERENCES users(user_id),
  CONSTRAINT fk_sa_desig  FOREIGN KEY (designation_id) REFERENCES designations(designation_id),
  CONSTRAINT fk_sa_dept   FOREIGN KEY (department_id)  REFERENCES departments(department_id),
  CONSTRAINT fk_sa_sector FOREIGN KEY (sector_id)      REFERENCES sectors(sector_id),
  UNIQUE KEY uq_sa_ph (department_id, designation_id),
  UNIQUE KEY uq_sa_scope (user_id, clearance_type, designation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------
-- 5) Insert users (one per non-PH designation) + 3 PHs
-- --------------------------------
-- Helper procedure-less upsert pattern:
-- If username exists, reuse; else insert.

-- Non-PH staff (employee_number pattern AAA999A to satisfy search regex)
-- Columns required by your schema: username, password, email, first_name, last_name, status
-- Use consistent naming; adjust as needed.

-- PAMO
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA101P', @default_hash, 'pamo@gosti.seed', 'Test', 'Pamo', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA101P');

-- MIS/IT
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA102P', @default_hash, 'misit@gosti.seed', 'Test', 'Misit', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA102P');

-- Petty Cash Custodian
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA103P', @default_hash, 'pettycash@gosti.seed', 'Test', 'Pettycash', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA103P');

-- Building Administrator
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA104P', @default_hash, 'bldgadmin@gosti.seed', 'Test', 'Bldgadmin', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA104P');

-- Accountant
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA105P', @default_hash, 'accountant@gosti.seed', 'Test', 'Accountant', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA105P');

-- Academic Head
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA106P', @default_hash, 'acadhead@gosti.seed', 'Test', 'Acadhead', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA106P');

-- School Administrator
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA107P', @default_hash, 'schooladmin@gosti.seed', 'Test', 'Schooladmin', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA107P');

-- HR
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA108P', @default_hash, 'hr@gosti.seed', 'Test', 'Hr', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA108P');

-- Guidance
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA109P', @default_hash, 'guidance@gosti.seed', 'Test', 'Guidance', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA109P');

-- Disciplinary Officer
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA110P', @default_hash, 'discipline@gosti.seed', 'Test', 'Discipline', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA110P');

-- Clinic
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA111P', @default_hash, 'clinic@gosti.seed', 'Test', 'Clinic', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA111P');

-- Librarian
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA112P', @default_hash, 'librarian@gosti.seed', 'Test', 'Librarian', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA112P');

-- Alumni Placement Officer
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA113P', @default_hash, 'alumni@gosti.seed', 'Test', 'Alumni', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA113P');

-- Student Affairs Officer
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA114P', @default_hash, 'sao@gosti.seed', 'Test', 'Sao', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA114P');

-- Registrar
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA115P', @default_hash, 'registrar@gosti.seed', 'Test', 'Registrar', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA115P');

-- Cashier
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'LCA116P', @default_hash, 'cashier@gosti.seed', 'Test', 'Cashier', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='LCA116P');

-- Program Head (College)
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'PHC101P', @default_hash, 'ph_college@gosti.seed', 'PH', 'College', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='PHC101P');

-- Program Head (SHS)
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'PHS101P', @default_hash, 'ph_shs@gosti.seed', 'PH', 'Shs', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='PHS101P');

-- Program Head (Faculty)
INSERT INTO users (username, password, email, first_name, last_name, status, created_at)
SELECT 'PHF101P', @default_hash, 'ph_faculty@gosti.seed', 'PH', 'Faculty', 'active', @now
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='PHF101P');

-- --------------------------------
-- 6) Assign roles (default Staff role_id=2)
-- --------------------------------
-- If role ids differ in your DB, adjust these.
SET @role_staff := (SELECT role_id FROM roles WHERE role_name='Staff' LIMIT 1);
SET @role_ph    := (SELECT role_id FROM roles WHERE role_name='Program Head' LIMIT 1);
SET @role_sa    := (SELECT role_id FROM roles WHERE role_name='School Administrator' LIMIT 1);

-- Helper to insert role if missing mapping
INSERT INTO user_roles (user_id, role_id, assigned_at, is_primary)
SELECT u.user_id, @role_staff, @now, 0
FROM users u
WHERE u.username IN ('LCA101P','LCA102P','LCA103P','LCA104P','LCA105P','LCA106P','LCA107P','LCA108P',
                     'LCA109P','LCA110P','LCA111P','LCA112P','LCA113P','LCA114P','LCA115P','LCA116P')
  AND NOT EXISTS (SELECT 1 FROM user_roles r WHERE r.user_id=u.user_id AND r.role_id=@role_staff);

-- Program Head users -> Program Head role
INSERT INTO user_roles (user_id, role_id, assigned_at, is_primary)
SELECT u.user_id, @role_ph, @now, 0
FROM users u
WHERE u.username IN ('PHC101P','PHS101P','PHF101P')
  AND @role_ph IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM user_roles r WHERE r.user_id=u.user_id AND r.role_id=@role_ph);

-- School Administrator user also gets School Administrator role (optional)
INSERT INTO user_roles (user_id, role_id, assigned_at, is_primary)
SELECT u.user_id, @role_sa, @now, 0
FROM users u
WHERE u.username='LCA107P'
  AND @role_sa IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM user_roles r WHERE r.user_id=u.user_id AND r.role_id=@role_sa);

-- --------------------------------
-- 7) Create staff rows
-- --------------------------------
-- Get user ids
SET @u_pamo   := (SELECT user_id FROM users WHERE username='LCA101P');
SET @u_misit  := (SELECT user_id FROM users WHERE username='LCA102P');
SET @u_petty  := (SELECT user_id FROM users WHERE username='LCA103P');
SET @u_bldg   := (SELECT user_id FROM users WHERE username='LCA104P');
SET @u_acct   := (SELECT user_id FROM users WHERE username='LCA105P');
SET @u_acad   := (SELECT user_id FROM users WHERE username='LCA106P');
SET @u_sadmin := (SELECT user_id FROM users WHERE username='LCA107P');
SET @u_hr     := (SELECT user_id FROM users WHERE username='LCA108P');
SET @u_guid   := (SELECT user_id FROM users WHERE username='LCA109P');
SET @u_disc   := (SELECT user_id FROM users WHERE username='LCA110P');
SET @u_clinic := (SELECT user_id FROM users WHERE username='LCA111P');
SET @u_lib    := (SELECT user_id FROM users WHERE username='LCA112P');
SET @u_alumni := (SELECT user_id FROM users WHERE username='LCA113P');
SET @u_sao    := (SELECT user_id FROM users WHERE username='LCA114P');
SET @u_reg    := (SELECT user_id FROM users WHERE username='LCA115P');
SET @u_cash   := (SELECT user_id FROM users WHERE username='LCA116P');
SET @u_phc    := (SELECT user_id FROM users WHERE username='PHC101P');
SET @u_phs    := (SELECT user_id FROM users WHERE username='PHS101P');
SET @u_phf    := (SELECT user_id FROM users WHERE username='PHF101P');

-- Insert staff rows (Regular Staff)
INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA101P', @u_pamo, @desig_pamo, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_pamo);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA102P', @u_misit, @desig_misit, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_misit);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA103P', @u_petty, @desig_petty, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_petty);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA104P', @u_bldg, @desig_bldg, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_bldg);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA105P', @u_acct, @desig_acct, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_acct);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA106P', @u_acad, @desig_acad, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_acad);

-- School Administrator (as its own category)
INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA107P', @u_sadmin, @desig_sadmin, 'School Administrator', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_sadmin);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA108P', @u_hr, @desig_hr, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_hr);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA109P', @u_guid, @desig_guid, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_guid);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA110P', @u_disc, @desig_disc, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_disc);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA111P', @u_clinic, @desig_clinic, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_clinic);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA112P', @u_lib, @desig_lib, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_lib);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA113P', @u_alumni, @desig_alumni, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_alumni);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA114P', @u_sao, @desig_sao, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_sao);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA115P', @u_reg, @desig_reg, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_reg);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'LCA116P', @u_cash, @desig_cash, 'Regular Staff', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_cash);

-- Program Heads (category = Program Head)
INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'PHC101P', @u_phc, @desig_ph, 'Program Head', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_phc);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'PHS101P', @u_phs, @desig_ph, 'Program Head', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_phs);

INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, employment_status, is_active, created_at)
SELECT 'PHF101P', @u_phf, @desig_ph, 'Program Head', NULL, NULL, 1, @now
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE user_id=@u_phf);

-- --------------------------------
-- 8) Seed Program Head coverage via signatory_assignments
-- --------------------------------
-- College PH -> all College departments
INSERT INTO signatory_assignments (user_id, designation_id, clearance_type, department_id, sector_id, is_active, created_at)
SELECT @u_phc, @desig_ph, NULL, d.department_id, d.sector_id, 1, @now
FROM departments d
WHERE d.sector_id = @sec_college
  AND NOT EXISTS (
    SELECT 1 FROM signatory_assignments sa
    WHERE sa.designation_id=@desig_ph AND sa.department_id=d.department_id AND sa.is_active=1
  );

-- SHS PH -> all SHS departments
INSERT INTO signatory_assignments (user_id, designation_id, clearance_type, department_id, sector_id, is_active, created_at)
SELECT @u_phs, @desig_ph, NULL, d.department_id, d.sector_id, 1, @now
FROM departments d
WHERE d.sector_id = @sec_shs
  AND NOT EXISTS (
    SELECT 1 FROM signatory_assignments sa
    WHERE sa.designation_id=@desig_ph AND sa.department_id=d.department_id AND sa.is_active=1
  );

-- Faculty PH -> General Education department
INSERT INTO signatory_assignments (user_id, designation_id, clearance_type, department_id, sector_id, is_active, created_at)
SELECT @u_phf, @desig_ph, NULL, d.department_id, d.sector_id, 1, @now
FROM departments d
WHERE d.department_name='General Education' AND d.sector_id=@sec_faculty
  AND NOT EXISTS (
    SELECT 1 FROM signatory_assignments sa
    WHERE sa.designation_id=@desig_ph AND sa.department_id=d.department_id AND sa.is_active=1
  );

COMMIT;

-- ===========================
-- Notes:
-- - Regular staff are intentionally NOT added to signatory_assignments here.
--   Use the Clearance Management UI to assign them to Student/Faculty scopes.
-- - If you later need to remove seeded users, remember FK order or temporarily use ON DELETE CASCADE for dev.
-- ===========================