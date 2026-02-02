# User Deletion Implementation Plan

## Overview

This document outlines the implementation plan for **hard deleting** user accounts across the system, including:

- **Students** (College and Senior High School)
- **Faculty**
- **Staff** (Program Heads, School Administrators, Regular Staff)

### Architecture Decision: Single Unified API Endpoint

After analysis, we chose a **single unified API endpoint** (`api/users/delete.php`) that routes to type-specific methods in `UserManager.php`.

| Approach               | Pros                                                   | Cons                                     |
| ---------------------- | ------------------------------------------------------ | ---------------------------------------- |
| **Single Endpoint** ✅ | DRY, consistent API, shared validation, easier updates | Complex internal logic                   |
| Separate Endpoints     | Type-specific logic, simpler files                     | Code duplication, more files to maintain |

**Conclusion**: Single endpoint with `UserManager` handling type-specific deletion logic provides the best balance of maintainability and flexibility.

---

## Database Structure Analysis

### Current Database State

**Date of Analysis:** 2025-01-02

### Foreign Key Constraints Status

#### ✅ Tables with CASCADE DELETE to `users`:

- `user_department_assignments` → `users.user_id` (CASCADE DELETE)
- `user_activities` → `users.user_id` (CASCADE DELETE)
- `user_roles` → `users.user_id` (CASCADE DELETE, UPDATE RESTRICT)
- `user_designation_assignments` → `users.user_id` (CASCADE DELETE)

#### ❌ Missing Foreign Key Constraints:

- **`students` table**: NO foreign key constraint from `students.user_id` to `users.user_id`
- **`clearance_signatories` table**: NO foreign key constraints at all

#### ✅ Confirmed Foreign Key Constraint:

- **`clearance_forms` table**: Has foreign key to `students.student_id` with CASCADE DELETE

---

## Deletion Chains by User Type

### Student Deletion Chain

```
clearance_signatories → clearance_forms → students → users
```

| Step | Table                   | Method        | Notes                                              |
| ---- | ----------------------- | ------------- | -------------------------------------------------- |
| 1    | `clearance_signatories` | Manual (JOIN) | No FK constraint exists                            |
| 2    | `clearance_forms`       | Manual        | Has CASCADE from students, but explicit for safety |
| 3    | `students`              | Manual        | No FK constraint to users                          |
| 4    | `users`                 | Manual        | Base table; `user_roles` CASCADE automatically     |

### Faculty Deletion Chain

```
clearance_signatories → clearance_forms → sector_signatory_assignments → user_department_assignments → faculty → users
```

| Step | Table                          | Method        | Notes                                                   |
| ---- | ------------------------------ | ------------- | ------------------------------------------------------- |
| 1    | `clearance_signatories`        | Manual (JOIN) | No FK constraint exists; uses JOIN with clearance_forms |
| 2    | `clearance_forms`              | Manual        | Uses user_id (NOT employee_number)                      |
| 3    | `sector_signatory_assignments` | Manual        | Prevents ghost signatories in clearance configuration   |
| 4    | `user_department_assignments`  | Manual        | Has CASCADE, but explicit for clarity                   |
| 5    | `faculty`                      | Manual        | Delete faculty record using employee_number             |
| 6    | `users`                        | Manual        | Base table; `user_roles` CASCADE automatically          |

### Staff Deletion Chain

```
clearance_signatories → clearance_forms → sector_signatory_assignments → user_designation_assignments → user_department_assignments → staff → users
```

| Step | Table                          | Method        | Notes                                                   |
| ---- | ------------------------------ | ------------- | ------------------------------------------------------- |
| 1    | `clearance_signatories`        | Manual (JOIN) | No FK constraint exists; uses JOIN with clearance_forms |
| 2    | `clearance_forms`              | Manual        | Uses user_id                                            |
| 3    | `sector_signatory_assignments` | Manual        | Prevents ghost signatories in clearance configuration   |
| 4    | `user_designation_assignments` | Manual        | Position assignments                                    |
| 5    | `user_department_assignments`  | Manual        | Department relationships                                |
| 6    | `staff`                        | Manual        | Delete staff record using employee_number               |
| 7    | `users`                        | Manual        | Base table; `user_roles` CASCADE automatically          |

---

## Implementation Plan

### Phase 1: Backend - UserManager.php Updates

Add the following methods to `includes/classes/UserManager.php`:

#### 1. `deleteStudent($userId)` - Single Student Deletion

```php
public function deleteStudent($userId) {
    // 1. Get student_id from user_id
    // 2. Check if admin (prevent deletion)
    // 3. Begin transaction
    // 4. Delete clearance_signatories (JOIN with clearance_forms)
    // 5. Delete clearance_forms
    // 6. Delete students
    // 7. Delete users
    // 8. Commit transaction
}
```

#### 2. `deleteStudents($userIds)` - Bulk Student Deletion

```php
public function deleteStudents($userIds) {
    // Loop through userIds and call deleteStudent()
    // Track success/failure counts
    // Return aggregated results
}
```

#### 3. Update `deleteFaculty($employeeId)` - Hard Delete

```php
public function deleteFaculty($employeeId) {
    // Change from soft delete to hard delete
    // 1. Get user_id from employee_number
    // 2. Check if admin
    // 3. Delete user_department_assignments
    // 4. Delete faculty
    // 5. Delete users
}
```

#### 4. `deleteStaff($employeeId)` - Staff Deletion

```php
public function deleteStaff($employeeId) {
    // 1. Get user_id from employee_number
    // 2. Check if admin
    // 3. Delete user_department_assignments
    // 4. Delete user_designation_assignments
    // 5. Delete sector_signatory_assignments
    // 6. Delete staff
    // 7. Delete users
}
```

### Phase 2: Backend - API Endpoint

Update `api/users/delete.php` to handle all user types:

```php
// Request body:
{
    "user_type": "student" | "faculty" | "staff",
    "user_id": 123,           // For single deletion
    "user_ids": [1, 2, 3],    // For bulk deletion (students only)
    "employee_id": "EMP001"   // For faculty/staff
}
```

**Routing Logic:**

```php
switch ($userType) {
    case 'student':
        if (isset($payload['user_ids'])) {
            $result = $userManager->deleteStudents($payload['user_ids']);
        } else {
            $result = $userManager->deleteStudent($payload['user_id']);
        }
        break;
    case 'faculty':
        $result = $userManager->deleteFaculty($payload['employee_id']);
        break;
    case 'staff':
        $result = $userManager->deleteStaff($payload['employee_id']);
        break;
}
```

### Phase 3: Frontend - Student Management Pages

Update the following pages with deletion functionality:

| Page                       | Location                                             | User Role    |
| -------------------------- | ---------------------------------------------------- | ------------ |
| College Student Management | `pages/admin/CollegeStudentManagement.php`           | Admin        |
| SHS Student Management     | `pages/admin/SeniorHighStudentManagement.php`        | Admin        |
| College Student Management | `pages/program-head/CollegeStudentManagement.php`    | Program Head |
| SHS Student Management     | `pages/program-head/SeniorHighStudentManagement.php` | Program Head |

#### Frontend Functions to Update:

1. **`deleteStudent(userId)`** - Single deletion with confirmation modal
2. **`deleteSelected()`** - Bulk deletion with confirmation modal
3. **`toggleSelectAll()`** - Select/deselect all checkboxes
4. **`updateBulkButtons()`** - Enable/disable bulk action buttons
5. **`updateSelectionCounter()`** - Show count of selected items

#### Confirmation Modal Content:

**Single Deletion:**

> Are you sure you want to delete **[Student Name]**? This action will permanently remove the student's data, including all clearance forms and clearance applications. This action cannot be undone.

**Bulk Deletion:**

> Are you sure you want to delete **[X]** selected student(s)? This action will permanently remove the students' data, including all clearance forms and clearance applications. This action cannot be undone.

---

## SQL Queries Reference

### Student Deletion Queries

> ⚠️ **IMPORTANT**: The `clearance_forms` table uses `user_id`, NOT `student_id`!
> The `students.student_id` is a VARCHAR (student number like '02000288322'), while `clearance_forms.user_id` is an INT.

```sql
-- Step 1: Delete clearance_signatories (uses user_id via JOIN)
DELETE cs FROM clearance_signatories cs
INNER JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
WHERE cf.user_id = ?;

-- Step 2: Delete clearance_forms (uses user_id, NOT student_id)
DELETE FROM clearance_forms WHERE user_id = ?;

-- Step 3: Delete students (uses student_id which is VARCHAR)
DELETE FROM students WHERE student_id = ?;

-- Step 4: Delete users
DELETE FROM users WHERE user_id = ?;
```

### Faculty Deletion Queries

```sql
-- Step 1: Delete user_department_assignments
DELETE FROM user_department_assignments WHERE user_id = ?;

-- Step 2: Delete faculty
DELETE FROM faculty WHERE employee_number = ?;

-- Step 3: Delete users
DELETE FROM users WHERE user_id = ?;
```

### Staff Deletion Queries

```sql
-- Step 1: Delete user_department_assignments
DELETE FROM user_department_assignments WHERE user_id = ?;

-- Step 2: Delete user_designation_assignments
DELETE FROM user_designation_assignments WHERE user_id = ?;

-- Step 3: Delete sector_signatory_assignments
DELETE FROM sector_signatory_assignments WHERE user_id = ?;

-- Step 4: Delete staff
DELETE FROM staff WHERE employee_number = ?;

-- Step 5: Delete users
DELETE FROM users WHERE user_id = ?;
```

---

## Important Notes

1. **Hard Delete Only**: All deletions are permanent. No soft delete option.

2. **No Audit Logging**: Deletion events are not logged (per requirements).

3. **No Pre-Deletion Checks**: No checks for active clearance periods or pending clearances before deletion.

4. **Transaction Safety**: All deletions are wrapped in database transactions for atomicity.

5. **Admin Protection**: The admin user account cannot be deleted.

6. **Bulk Deletion Info**: For bulk deletion, only show the count of students being deleted, not individual names.

---

## Testing Checklist

### Student Deletion

- [ ] Test individual student deletion (Admin)
- [ ] Test individual student deletion (Program Head)
- [ ] Test bulk student deletion
- [ ] Verify checkbox "Select All" functionality
- [ ] Verify bulk delete button enables/disables correctly
- [ ] Verify confirmation modal displays correct count
- [ ] Verify all related records are deleted:
  - [ ] clearance_signatories
  - [ ] clearance_forms
  - [ ] students
  - [ ] users
- [ ] Test deletion of student with no clearance forms
- [ ] Test deletion of student with multiple clearance forms
- [ ] Verify table refreshes after deletion
- [ ] Verify statistics update after deletion

### Faculty Deletion

- [ ] Test individual faculty deletion
- [ ] Verify user_department_assignments deleted
- [ ] Verify faculty record deleted
- [ ] Verify user record deleted

### Staff Deletion

- [ ] Test individual staff deletion
- [ ] Verify all assignment tables cleared
- [ ] Verify staff record deleted
- [ ] Verify user record deleted

### Error Handling

- [ ] Test admin user deletion prevention
- [ ] Test transaction rollback on error
- [ ] Verify no orphaned records remain
- [ ] Test API error responses

---

## Related Files

### Backend

- `includes/classes/UserManager.php` - Add deletion methods
- `api/users/delete.php` - Update unified deletion endpoint

### Frontend - Student Management

- `pages/admin/CollegeStudentManagement.php`
- `pages/admin/SeniorHighStudentManagement.php`
- `pages/program-head/CollegeStudentManagement.php`
- `pages/program-head/SeniorHighStudentManagement.php`

### Frontend - Faculty Management (Future)

- `pages/admin/FacultyManagement.php`
- `pages/program-head/FacultyManagement.php`

### Frontend - Staff Management (Future)

- `pages/admin/StaffManagement.php`

---

## Implementation Order

| Phase | Task                                                  | Priority | Status  |
| ----- | ----------------------------------------------------- | -------- | ------- |
| 1     | Add `deleteStudent()` to UserManager.php              | High     | ✅ Done |
| 2     | Add `deleteStudents()` to UserManager.php             | High     | ✅ Done |
| 3     | Update `api/users/delete.php` for student deletion    | High     | ✅ Done |
| 4     | Update Admin CollegeStudentManagement.php frontend    | High     | ✅ Done |
| 5     | Update Admin SeniorHighStudentManagement.php frontend | High     | ✅ Done |
| 6     | Update Program Head student management pages          | Medium   | ✅ Done |
| 7     | Update `deleteFaculty()` to hard delete               | Medium   | ✅ Done |
| 8     | Add `deleteStaff()` to UserManager.php                | Medium   | ✅ Done |
| 9     | Update faculty management pages                       | Medium   | ✅ Done |
| 10    | Update staff management pages                         | Low      | ✅ Done |

---

## Bug Fixes & Implementation Notes

### ✅ Bug Fix #1: Wrong Column Name for clearance_forms (2025-01-03)

**Issue**: Student deletion was failing with 400 Bad Request error.

**Root Cause**: The `deleteStudent()` method was using `student_id` to query `clearance_forms`, but `clearance_forms` uses `user_id` column instead.

**Schema Discovery**:

- `students.student_id` = VARCHAR(11) (student number like '02000288322')
- `clearance_forms.user_id` = INT(11) (references users.user_id)
- `clearance_forms` does NOT have a `student_id` column

**Fix Applied** (in `UserManager.php`):

```php
// BEFORE (WRONG):
WHERE cf.student_id = ?
DELETE FROM clearance_forms WHERE student_id = ?

// AFTER (CORRECT):
WHERE cf.user_id = ?
DELETE FROM clearance_forms WHERE user_id = ?
```

**Files Modified**: `includes/classes/UserManager.php` (lines 484-493)

---

### ✅ Bug Fix #2: Faculty Deletion Not Working - Wrong API Endpoint and Parameters (2025-01-03)

**Issue**: Faculty deletion appeared to succeed in the UI (showed success message) but faculty records remained in the database. Page refresh would show the "deleted" faculty still present.

**Root Causes**:

1. **Single Deletion**: The `deleteFaculty()` JavaScript function was calling the wrong API endpoint:

   - **Wrong**: `../../api/users/delete_faculty.php` with `employee_number`
   - **Correct**: `../../api/users/delete.php` with `user_type: 'faculty'` and `user_id`

2. **Bulk Deletion**: The `deleteSelected()` function was only removing rows from the DOM (UI-only) without calling any API endpoint to delete from the database.

3. **Missing user_id in Frontend**: The checkbox was storing `employee_number` but the API requires `user_id` for faculty deletion.

**Fixes Applied**:

1. **Updated checkbox to store `user_id`** (`pages/admin/FacultyManagement.php` line 1195):

   ```javascript
   // BEFORE:
   data-id="${f.employee_number}"

   // AFTER:
   data-id="${f.employee_number}" data-user-id="${f.user_id}"
   ```

2. **Updated delete button to pass `user_id`** (line 1205):

   ```javascript
   // BEFORE:
   onclick = "deleteFaculty('${f.employee_number}')";

   // AFTER:
   onclick = "deleteFaculty(${f.user_id})";
   ```

3. **Fixed `deleteFaculty()` function** to use correct API endpoint and parameters (lines 1005-1029):

   ```javascript
   // BEFORE (WRONG):
   fetch("../../api/users/delete_faculty.php", {
     body: JSON.stringify({ employee_number: facultyId }),
   });

   // AFTER (CORRECT):
   fetch("../../api/users/delete.php", {
     body: JSON.stringify({
       user_type: "faculty",
       user_id: userId,
     }),
   });
   ```

4. **Fixed `deleteSelected()` function** to actually call the API (lines 913-970):
   - Added API calls to delete each selected faculty member
   - Collects `user_id` from each checkbox's `data-user-id` attribute
   - Loops through and deletes via API instead of just removing DOM elements
   - Shows success/failure counts and refreshes table after completion

**Files Modified**:

- `pages/admin/FacultyManagement.php` (lines 1005-1029, 913-970, 1195, 1205)

**Note**: The backend `UserManager->deleteFaculty()` method was already correct - it accepts `user_id` and handles the deletion chain properly. The issue was purely in the frontend JavaScript.

---

## ✅ Student Deletion - VERIFIED WORKING (2025-01-03)

Both **College** and **Senior High School** student deletion is now fully functional.

### Features Confirmed Working:

| Feature                                | Status      |
| -------------------------------------- | ----------- |
| Single student deletion (Admin)        | ✅ Working  |
| Single student deletion (Program Head) | ✅ Working  |
| Bulk student deletion                  | ✅ Working  |
| Confirmation modal with warning        | ✅ Working  |
| Table refresh after deletion           | ✅ Working  |
| Statistics update after deletion       | ✅ Working  |
| All related records deleted            | ✅ Verified |

### Files Modified for Student Deletion:

| File                                                 | Changes                                                |
| ---------------------------------------------------- | ------------------------------------------------------ |
| `includes/classes/UserManager.php`                   | Added `deleteStudent()` and `deleteStudents()` methods |
| `api/users/delete.php`                               | Updated unified endpoint with student deletion routing |
| `pages/admin/CollegeStudentManagement.php`           | Updated `deleteSelected()` and `deleteStudent()`       |
| `pages/admin/SeniorHighStudentManagement.php`        | Updated `deleteSelected()` and `deleteStudent()`       |
| `pages/program-head/CollegeStudentManagement.php`    | Updated `deleteSelected()` and `deleteStudent()`       |
| `pages/program-head/SeniorHighStudentManagement.php` | Updated `deleteSelected()` and `deleteStudent()`       |

---

## SQL Queries to Verify Student Deletion

Use these queries in phpMyAdmin to verify deletion was successful.

### Before Deletion - Find the student's data

```sql
-- Get student info (replace with the student number you're deleting)
SELECT
    s.student_id,
    s.user_id,
    u.first_name,
    u.last_name,
    u.username
FROM students s
JOIN users u ON s.user_id = u.user_id
WHERE s.student_id = '02000288325';  -- Replace with actual student number
```

### After Deletion - All-in-One Verification Query

```sql
-- Replace 123 with the user_id of the deleted student
SET @deleted_user_id = 123;

SELECT
    'users' AS table_name,
    COUNT(*) AS remaining_records
FROM users WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'students' AS table_name,
    COUNT(*) AS remaining_records
FROM students WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'clearance_forms' AS table_name,
    COUNT(*) AS remaining_records
FROM clearance_forms WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'clearance_signatories' AS table_name,
    COUNT(*) AS remaining_records
FROM clearance_signatories cs
JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
WHERE cf.user_id = @deleted_user_id

UNION ALL

SELECT
    'user_roles' AS table_name,
    COUNT(*) AS remaining_records
FROM user_roles WHERE user_id = @deleted_user_id;
```

**Expected Result** (all zeros means deletion was successful):

| table_name            | remaining_records |
| --------------------- | ----------------- |
| users                 | 0                 |
| students              | 0                 |
| clearance_forms       | 0                 |
| clearance_signatories | 0                 |
| user_roles            | 0                 |

### Quick Count Check (Before & After)

Run this BEFORE and AFTER deletion to see the difference:

```sql
SELECT
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM students) AS total_students,
    (SELECT COUNT(*) FROM clearance_forms) AS total_clearance_forms,
    (SELECT COUNT(*) FROM clearance_signatories) AS total_signatories;
```

---

## ✅ Faculty Deletion - VERIFIED WORKING (2025-01-03)

**Faculty deletion is now fully functional** for both single and bulk deletion.

### Features Confirmed Working:

| Feature                          | Status      |
| -------------------------------- | ----------- |
| Single faculty deletion          | ✅ Working  |
| Bulk faculty deletion            | ✅ Working  |
| Confirmation modal with warning  | ✅ Working  |
| Table refresh after deletion     | ✅ Working  |
| Statistics update after deletion | ✅ Working  |
| All related records deleted      | ✅ Verified |

### Deletion Chain for Faculty:

The faculty deletion process removes data from the following tables in order:

1. `clearance_signatories` (via JOIN with clearance_forms)
2. `clearance_forms` (uses user_id)
3. `user_department_assignments` (uses user_id)
4. `faculty` (uses employee_number as primary key)
5. `users` (uses user_id; user_roles CASCADE automatically)

### Files Modified for Faculty Deletion:

| File                                | Changes                                                  |
| ----------------------------------- | -------------------------------------------------------- |
| `includes/classes/UserManager.php`  | Updated `deleteFaculty()` to accept user_id              |
| `api/users/delete.php`              | Updated routing for faculty deletion                     |
| `pages/admin/FacultyManagement.php` | Fixed `deleteFaculty()` and `deleteSelected()` functions |

---

## SQL Queries to Verify Faculty Deletion

Use these queries in phpMyAdmin to verify deletion was successful.

### Before Deletion - Find the faculty member's data

```sql
-- Get faculty info (replace with the user_id you're deleting)
SELECT
    f.employee_number,
    f.user_id,
    u.first_name,
    u.last_name,
    u.username,
    u.email
FROM faculty f
JOIN users u ON f.user_id = u.user_id
WHERE f.user_id = 123;  -- Replace with actual user_id
```

**Alternative**: If you only know the employee_number:

```sql
-- Get user_id from employee_number
SELECT
    f.employee_number,
    f.user_id,
    u.first_name,
    u.last_name,
    u.username
FROM faculty f
JOIN users u ON f.user_id = u.user_id
WHERE f.employee_number = 'EMP001';  -- Replace with actual employee_number
```

### After Deletion - All-in-One Verification Query

```sql
-- Replace 123 with the user_id of the deleted faculty member
SET @deleted_user_id = 123;

-- Get employee_number (you'll need this for faculty table check)
SET @deleted_employee_number = (SELECT employee_number FROM faculty WHERE user_id = @deleted_user_id);
-- Note: This will be NULL if already deleted, which is expected

SELECT
    'users' AS table_name,
    COUNT(*) AS remaining_records
FROM users WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'faculty' AS table_name,
    COUNT(*) AS remaining_records
FROM faculty WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'user_department_assignments' AS table_name,
    COUNT(*) AS remaining_records
FROM user_department_assignments WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'clearance_forms' AS table_name,
    COUNT(*) AS remaining_records
FROM clearance_forms WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'clearance_signatories' AS table_name,
    COUNT(*) AS remaining_records
FROM clearance_signatories cs
JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
WHERE cf.user_id = @deleted_user_id

UNION ALL

SELECT
    'user_roles' AS table_name,
    COUNT(*) AS remaining_records
FROM user_roles WHERE user_id = @deleted_user_id;
```

**Expected Result** (all zeros means deletion was successful):

| table_name                  | remaining_records |
| --------------------------- | ----------------- |
| users                       | 0                 |
| faculty                     | 0                 |
| user_department_assignments | 0                 |
| clearance_forms             | 0                 |
| clearance_signatories       | 0                 |
| user_roles                  | 0                 |

### Quick Count Check (Before & After)

Run this BEFORE and AFTER deletion to see the difference:

```sql
SELECT
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM faculty) AS total_faculty,
    (SELECT COUNT(*) FROM user_department_assignments) AS total_department_assignments,
    (SELECT COUNT(*) FROM clearance_forms) AS total_clearance_forms,
    (SELECT COUNT(*) FROM clearance_signatories) AS total_signatories;
```

### Verify Using Employee Number (Alternative Method)

If you want to verify using the employee_number directly:

```sql
-- Replace 'EMP001' with the employee_number of the deleted faculty
SET @deleted_employee_number = 'EMP001';

-- Check if faculty record exists
SELECT
    'faculty' AS table_name,
    COUNT(*) AS remaining_records
FROM faculty WHERE employee_number = @deleted_employee_number

UNION ALL

-- Get user_id from employee_number (if faculty record still exists)
SELECT
    'faculty_user_id' AS table_name,
    user_id AS remaining_records
FROM faculty WHERE employee_number = @deleted_employee_number;
```

**Note**: The second query will return the `user_id` if the faculty record still exists. Use this `user_id` in the main verification query above.

---

## ✅ Staff Deletion - VERIFIED WORKING (2025-01-06)

**Regular staff deletion is now fully functional** and verified working.

### Features Confirmed Working:

| Feature                          | Status      |
| -------------------------------- | ----------- |
| Single staff deletion (Admin)    | ✅ Working  |
| Confirmation modal with warning  | ✅ Working  |
| UI update after deletion         | ✅ Working  |
| All related records deleted      | ✅ Verified |

### Implementation Details:

**Backend**: `UserManager.php` → `deleteStaff($userId)` method  
**API**: `POST /api/users/delete.php` with `user_type: 'staff'` and `user_id`  
**Frontend**: `pages/admin/StaffManagement.php` with updated delete button and JavaScript function

### Implementation Approach:

Following the same pattern as student and faculty deletion:
- Uses `user_id` instead of `employee_number` for consistency
- Unified API endpoint (`api/users/delete.php`)
- Hard delete with transaction safety
- Admin user protection

### Deletion Chain (6 Steps):

The staff deletion process removes data from the following tables in order:

1. **clearance_signatories** → Manual (JOIN with clearance_forms)
2. **clearance_forms** → Manual (uses user_id)
3. **user_designation_assignments** → Manual (position assignments)
4. **user_department_assignments** → Manual (department relationships)
5. **staff** → Manual (uses employee_number as primary key)
6. **users** → Manual (user_roles CASCADE automatically)

### Files Modified for Staff Deletion:

| File                                | Changes                                                  |
| ----------------------------------- | -------------------------------------------------------- |
| `includes/classes/UserManager.php`  | Added `deleteStaff($userId)` method                     |
| `api/users/delete.php`              | Added staff case to switch statement                     |
| `pages/admin/StaffManagement.php`   | Updated delete button to pass `user_id`, simplified JavaScript function |

### Implementation Notes:

- **Delete Button**: Changed from passing `employee_number` to `user_id` in the onclick handler
- **JavaScript Function**: Simplified `deleteStaff()` function to use unified API endpoint, removed old Program Head unassignment logic (as per user requirements, only single deletion needed for regular staff)
- **API Integration**: Uses same unified endpoint pattern as students and faculty (`user_type: 'staff'`, `user_id`)
- **Scope**: Currently implemented for **Regular Staff** only. Program Head and School Administrator deletion to be implemented separately.

---

## SQL Verification Queries for Staff Deletion

### Before Deletion - Find the staff's data

```sql
-- Get staff info (replace with the employee_number you're deleting)
SELECT
    s.employee_number,
    s.user_id,
    u.first_name,
    u.last_name,
    u.username,
    s.staff_category,
    s.designation_id
FROM staff s
JOIN users u ON s.user_id = u.user_id
WHERE s.employee_number = 'LCA9999P';  -- Replace with actual employee_number
```

```sql
-- Get this staff's user_id (you'll need this for the other queries)
SET @user_id = (SELECT user_id FROM staff WHERE employee_number = 'LCA9999P');
SELECT @user_id AS user_id_to_delete;
```

### After Deletion - All-in-One Verification Query

```sql
-- Replace 123 with the user_id of the deleted staff member
SET @deleted_user_id = 123;

SELECT
    'users' AS table_name,
    COUNT(*) AS remaining_records
FROM users WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'staff' AS table_name,
    COUNT(*) AS remaining_records
FROM staff WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'user_designation_assignments' AS table_name,
    COUNT(*) AS remaining_records
FROM user_designation_assignments WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'user_department_assignments' AS table_name,
    COUNT(*) AS remaining_records
FROM user_department_assignments WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'clearance_forms' AS table_name,
    COUNT(*) AS remaining_records
FROM clearance_forms WHERE user_id = @deleted_user_id

UNION ALL

SELECT
    'clearance_signatories' AS table_name,
    COUNT(*) AS remaining_records
FROM clearance_signatories cs
JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
WHERE cf.user_id = @deleted_user_id

UNION ALL

SELECT
    'user_roles' AS table_name,
    COUNT(*) AS remaining_records
FROM user_roles WHERE user_id = @deleted_user_id;
```

**Expected Result** (all zeros means deletion was successful):

| table_name                   | remaining_records |
| ---------------------------- | ----------------- |
| users                        | 0                 |
| staff                        | 0                 |
| user_designation_assignments | 0                 |
| user_department_assignments  | 0                 |
| clearance_forms              | 0                 |
| clearance_signatories        | 0                 |
| user_roles                   | 0                 |

### Quick Count Check (Before & After)

Run this BEFORE and AFTER deletion to see the difference:

```sql
SELECT
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM staff) AS total_staff,
    (SELECT COUNT(*) FROM user_designation_assignments) AS total_designation_assignments,
    (SELECT COUNT(*) FROM user_department_assignments) AS total_department_assignments,
    (SELECT COUNT(*) FROM clearance_forms) AS total_clearance_forms,
    (SELECT COUNT(*) FROM clearance_signatories) AS total_signatories;
```

---

## Last Updated

- **Date**: 2025-01-06
- **Status**: ✅ Student Deletion Complete | ✅ Faculty Deletion Complete | ✅ Regular Staff Deletion Complete and Verified Working
- **Next Steps**: Implement Program Head and School Administrator deletion (separate from regular staff)
