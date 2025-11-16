# Database Relationships Documentation

## Overview

This document describes all primary keys (PK) and foreign keys (FK) in the Online Clearance System database.

---

## Core Tables

### 1. users

**Primary Key:** `user_id` (INT, AUTO_INCREMENT)

**Referenced By (Foreign Keys):**

- `audit_logs.user_id` → ON DELETE SET NULL
- `bulk_operations.user_id` → ON DELETE SET NULL
- `clearance_forms.user_id` → ON DELETE CASCADE
- `clearance_signatories.actual_user_id` → ON DELETE CASCADE
- `data_versions.user_id` → ON DELETE SET NULL
- `faculty.user_id` → ON DELETE CASCADE
- `file_uploads.user_id` → ON DELETE SET NULL
- `login_sessions.user_id` → ON DELETE CASCADE
- `role_permissions.granted_by` → ON DELETE SET NULL
- `sector_signatory_assignments.user_id` → ON DELETE CASCADE
- `signatory_assignments.user_id` → References users
- `staff.user_id` → ON DELETE CASCADE
- `staff_designation_assignments.assigned_by` → ON DELETE SET NULL
- `students.user_id` → ON DELETE CASCADE
- `system_settings.updated_by` → ON DELETE SET NULL
- `user_activities.user_id` → ON DELETE CASCADE
- `user_roles.user_id` → ON DELETE CASCADE
- `user_roles.assigned_by` → ON DELETE SET NULL

**Unique Constraints:**

- `username` UNIQUE
- `email` UNIQUE

---

### 2. roles

**Primary Key:** `role_id` (INT, AUTO_INCREMENT)

**Referenced By:**

- `role_permissions.role_id` → ON DELETE CASCADE
- `user_roles.role_id` → ON DELETE CASCADE

**Unique Constraints:**

- `role_name` UNIQUE

---

### 3. permissions

**Primary Key:** `permission_id` (INT, AUTO_INCREMENT)

**Referenced By:**

- `role_permissions.permission_id` → ON DELETE CASCADE

**Unique Constraints:**

- `permission_name` UNIQUE

---

### 4. role_permissions

**Primary Key:** Composite (`role_id`, `permission_id`)

**Foreign Keys:**

- `role_id` → `roles.role_id` ON DELETE CASCADE
- `permission_id` → `permissions.permission_id` ON DELETE CASCADE
- `granted_by` → `users.user_id` ON DELETE SET NULL

---

### 5. user_roles

**Primary Key:** Composite (`user_id`, `role_id`)

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE
- `role_id` → `roles.role_id` ON DELETE CASCADE
- `assigned_by` → `users.user_id` ON DELETE SET NULL

---

## Academic Structure Tables

### 6. academic_years

**Primary Key:** `academic_year_id` (INT, AUTO_INCREMENT)

**Referenced By:**

- `clearance_forms.academic_year_id` → ON DELETE CASCADE
- `clearance_periods.academic_year_id` → ON DELETE CASCADE
- `semesters.academic_year_id` → ON DELETE CASCADE

**Unique Constraints:**

- `year` UNIQUE

---

### 7. semesters

**Primary Key:** `semester_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `academic_year_id` → `academic_years.academic_year_id` ON DELETE CASCADE

**Referenced By:**

- `clearance_forms.semester_id` → ON DELETE CASCADE
- `clearance_periods.semester_id` → ON DELETE CASCADE

---

### 8. sectors

**Primary Key:** `sector_id` (INT, AUTO_INCREMENT)

**Referenced By:**

- `departments.sector_id` → ON DELETE SET NULL
- `signatory_assignments.sector_id` → References sectors
- `staff_designation_assignments.sector_id` → ON DELETE CASCADE

---

### 9. departments

**Primary Key:** `department_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `sector_id` → `sectors.sector_id` ON DELETE SET NULL

**Referenced By:**

- `clearance_signatories_new.department_id` → ON DELETE SET NULL
- `faculty.department_id` → ON DELETE CASCADE
- `programs.department_id` → ON DELETE CASCADE
- `sector_signatory_assignments.department_id` → ON DELETE CASCADE
- `signatory_assignments.department_id` → References departments
- `staff.department_id` → ON DELETE CASCADE
- `staff_designation_assignments.department_id` → ON DELETE CASCADE
- `students.department_id` → ON DELETE CASCADE

**Unique Constraints:**

- `department_code` UNIQUE

---

### 10. programs

**Primary Key:** `program_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `department_id` → `departments.department_id` ON DELETE CASCADE

**Referenced By:**

- `students.program_id` → ON DELETE CASCADE

**Unique Constraints:**

- `program_code` UNIQUE

---

### 11. designations

**Primary Key:** `designation_id` (INT, AUTO_INCREMENT)

**Referenced By:**

- `clearance_requirements.designation_id` → ON DELETE CASCADE
- `clearance_signatories.designation_id` → ON DELETE CASCADE
- `clearance_signatories_new.designation_id` → ON DELETE CASCADE
- `scope_settings.required_first_designation_id` → ON DELETE SET NULL
- `scope_settings.required_last_designation_id` → ON DELETE SET NULL
- `sector_clearance_settings.required_first_designation_id` → ON DELETE SET NULL
- `sector_clearance_settings.required_last_designation_id` → ON DELETE SET NULL
- `sector_signatory_assignments.designation_id` → ON DELETE CASCADE
- `signatory_assignments.designation_id` → References designations
- `staff.designation_id` → ON DELETE CASCADE

---

## Clearance Management Tables

### 12. clearance_periods

**Primary Key:** `period_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `academic_year_id` → `academic_years.academic_year_id` ON DELETE CASCADE
- `semester_id` → `semesters.semester_id` ON DELETE CASCADE

**Referenced By:**

- `clearance_signatories_new.clearance_period_id` → ON DELETE CASCADE

---

### 13. clearance_forms

**Primary Key:** `clearance_form_id` (VARCHAR(20))

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE
- `academic_year_id` → `academic_years.academic_year_id` ON DELETE CASCADE
- `semester_id` → `semesters.semester_id` ON DELETE CASCADE

**Referenced By:**

- `clearance_signatories.clearance_form_id` → ON DELETE CASCADE
- `signatory_actions.clearance_form_id` → ON DELETE CASCADE

**Unique Constraints:**

- Unique combination of (`user_id`, `academic_year_id`, `semester_id`)

---

### 14. clearance_requirements

**Primary Key:** `requirement_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `designation_id` → `designations.designation_id` ON DELETE CASCADE

---

### 15. clearance_signatories

**Primary Key:** `signatory_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `clearance_form_id` → `clearance_forms.clearance_form_id` ON DELETE CASCADE
- `designation_id` → `designations.designation_id` ON DELETE CASCADE
- `actual_user_id` → `users.user_id` ON DELETE CASCADE
- `reason_id` → `rejection_reasons.reason_id`

**Referenced By:**

- `rejection_remarks.signatory_id` → ON DELETE CASCADE

---

### 16. clearance_signatories_new

**Primary Key:** `signatory_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `department_id` → `departments.department_id` ON DELETE SET NULL
- `designation_id` → `designations.designation_id` ON DELETE CASCADE
- `clearance_period_id` → `clearance_periods.period_id` ON DELETE CASCADE
- `staff_id` → `staff.employee_number` ON DELETE CASCADE

**Referenced By:**

- `signatory_actions.signatory_id` → ON DELETE CASCADE

---

### 17. signatory_actions

**Primary Key:** `action_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `clearance_form_id` → `clearance_forms.clearance_form_id` ON DELETE CASCADE
- `rejection_reason_id` → `rejection_reasons.reason_id` ON DELETE SET NULL
- `signatory_id` → `clearance_signatories_new.signatory_id` ON DELETE CASCADE

---

### 18. rejection_reasons

**Primary Key:** `reason_id` (INT, AUTO_INCREMENT)

**Referenced By:**

- `clearance_signatories.reason_id` → References rejection_reasons
- `rejection_remarks.reason_id` → ON DELETE CASCADE
- `signatory_actions.rejection_reason_id` → ON DELETE SET NULL

---

### 19. rejection_remarks

**Primary Key:** `remark_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `signatory_id` → `clearance_signatories.signatory_id` ON DELETE CASCADE
- `reason_id` → `rejection_reasons.reason_id` ON DELETE CASCADE

---

## User Type Tables

### 20. students

**Primary Key:** `student_id` (VARCHAR(11))

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE
- `program_id` → `programs.program_id` ON DELETE CASCADE
- `department_id` → `departments.department_id` ON DELETE CASCADE

**Unique Constraints:**

- `user_id` UNIQUE

---

### 21. faculty

**Primary Key:** `employee_number` (VARCHAR(8))

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE
- `department_id` → `departments.department_id` ON DELETE CASCADE

**Unique Constraints:**

- `user_id` UNIQUE

---

### 22. staff

**Primary Key:** `employee_number` (VARCHAR(8))

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE
- `designation_id` → `designations.designation_id` ON DELETE CASCADE
- `department_id` → `departments.department_id` ON DELETE CASCADE

**Referenced By:**

- `clearance_signatories_new.staff_id` → ON DELETE CASCADE
- `staff_designation_assignments.staff_id` → ON DELETE CASCADE

**Unique Constraints:**

- `user_id` UNIQUE

---

## Signatory Assignment Tables

### 23. signatory_assignments

**Primary Key:** `assignment_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `department_id` → `departments.department_id`
- `designation_id` → `designations.designation_id`
- `sector_id` → `sectors.sector_id`
- `user_id` → `users.user_id`

---

### 24. sector_signatory_assignments

**Primary Key:** `assignment_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE
- `designation_id` → `designations.designation_id` ON DELETE CASCADE
- `department_id` → `departments.department_id` ON DELETE CASCADE

---

### 25. staff_designation_assignments

**Primary Key:** `assignment_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `assigned_by` → `users.user_id` ON DELETE SET NULL
- `department_id` → `departments.department_id` ON DELETE CASCADE
- `sector_id` → `sectors.sector_id` ON DELETE CASCADE
- `staff_id` → `staff.employee_number` ON DELETE CASCADE

---

## Configuration Tables

### 26. scope_settings

**Primary Key:** `clearance_type` (ENUM)

**Foreign Keys:**

- `required_first_designation_id` → `designations.designation_id` ON DELETE SET NULL
- `required_last_designation_id` → `designations.designation_id` ON DELETE SET NULL

---

### 27. sector_clearance_settings

**Primary Key:** `setting_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `required_first_designation_id` → `designations.designation_id` ON DELETE SET NULL
- `required_last_designation_id` → `designations.designation_id` ON DELETE SET NULL

---

### 28. system_settings

**Primary Key:** `setting_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `updated_by` → `users.user_id` ON DELETE SET NULL

**Unique Constraints:**

- `setting_key` UNIQUE

---

## Audit and Tracking Tables

### 29. audit_logs

**Primary Key:** `log_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE SET NULL

---

### 30. user_activities

**Primary Key:** `activity_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE

---

### 31. login_sessions

**Primary Key:** `session_id` (VARCHAR(255))

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE CASCADE

---

### 32. bulk_operations

**Primary Key:** `operation_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE SET NULL

**Referenced By:**

- `operation_logs.operation_id` → ON DELETE CASCADE

---

### 33. operation_logs

**Primary Key:** `log_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `operation_id` → `bulk_operations.operation_id` ON DELETE CASCADE

---

### 34. file_uploads

**Primary Key:** `file_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `user_id` → `users.user_id` ON DELETE SET NULL

**Referenced By:**

- `data_versions.file_id` → ON DELETE SET NULL

---

### 35. data_versions

**Primary Key:** `version_id` (INT, AUTO_INCREMENT)

**Foreign Keys:**

- `file_id` → `file_uploads.file_id` ON DELETE SET NULL
- `user_id` → `users.user_id` ON DELETE SET NULL

---

## Relationship Summary

### Total Counts

- **Total Tables:** 35
- **Total Primary Keys:** 35
- **Total Foreign Keys:** ~70+
- **Tables with Composite Primary Keys:** 2 (role_permissions, user_roles)

### Most Referenced Tables (Hub Tables)

1. **users** - Referenced by 19 tables
2. **designations** - Referenced by 11 tables
3. **departments** - Referenced by 10 tables
4. **clearance_forms** - Referenced by 2 tables

### Cascade Delete Hierarchy

When a user is deleted (`users` table):

- Cascades to: students, faculty, staff, user_roles, clearance_forms, login_sessions, user_activities
- Sets NULL: audit_logs, bulk_operations, data_versions, file_uploads, system_settings

### Critical Relationships

1. **User Management:** users ↔ roles ↔ permissions
2. **Academic Structure:** academic_years → semesters → clearance_periods
3. **Department Hierarchy:** sectors → departments → programs
4. **Clearance Flow:** clearance_forms → clearance_signatories → signatory_actions
5. **User Types:** users → students/faculty/staff

---

## ON DELETE Actions Explained

- **CASCADE:** When parent is deleted, child records are automatically deleted
- **SET NULL:** When parent is deleted, foreign key in child is set to NULL
- **RESTRICT/NO ACTION:** Prevents deletion of parent if child records exist

---

## Indexes for Performance

All foreign key columns automatically have indexes. Additional indexes include:

- Username, email lookups (users table)
- Status and type filters (clearance_forms, students, faculty)
- Date-based queries (audit_logs, user_activities)
- Academic year and semester lookups

---

## Verification Commands

Check all foreign keys:

```sql
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'basedata_db'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;
```

Count foreign keys per table:

```sql
SELECT
    TABLE_NAME,
    COUNT(*) as FK_COUNT
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'basedata_db'
  AND REFERENCED_TABLE_NAME IS NOT NULL
GROUP BY TABLE_NAME
ORDER BY FK_COUNT DESC;
```

---

## Generated: November 12, 2025







