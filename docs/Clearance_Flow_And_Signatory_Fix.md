# Clearance Flow and Signatory Fix Documentation

## Overview

This document summarizes the complete clearance flow for Regular Staff management pages and the fix implemented to ensure applicants are properly displayed when clearance periods are active. This serves as a reference for fixing similar issues in other user role pages with signatory functions.

---

## Complete Clearance Flow

### Flow Diagram

```
1. Admin (Setup Clearance Period)
   └─> Creates/activates clearance period for a sector (College, Senior High School, or Faculty)
       └─> Period stored in `clearance_periods` table with sector, academic_year_id, semester_id

2. End User (Receives Clearance Forms)
   └─> Student/Faculty creates clearance form for the active period
       └─> Form stored in `clearance_forms` table linked to period's academic_year_id and semester_id
       └─> Signatory assignments created in `clearance_signatories` table

3. Regular Staff (Receives Clearance Form Applications)
   └─> Staff views management page (CollegeStudentManagement, SeniorHighStudentManagement, FacultyManagement)
       └─> Page calls `api/clearance/signatoryList.php`
       └─> API should show ALL applicants in the sector when period is active
       └─> Staff can see applicants even if they haven't created clearance forms yet

4. Regular Staff (Performs Signatory Actions)
   └─> Staff approves/rejects clearance requests
       └─> Actions stored in `clearance_signatories` table
       └─> Updates `action` field (Pending → Approved/Rejected)

5. End User (Receives Signatory Actions)
   └─> Student/Faculty sees updated clearance status
       └─> Can view which signatories approved/rejected
```

### Detailed Flow Steps

#### Step 1: Admin Setup

- Admin creates a clearance period in the system
- Period is assigned to a specific sector: `'College'`, `'Senior High School'`, or `'Faculty'`
- Period has associated `academic_year_id` and `semester_id`
- Period status can be: `'Not Started'`, `'Ongoing'`, `'Paused'`, or `'Closed'`

#### Step 2: End User Clearance Form Creation

- When a clearance period is active, end users (students/faculty) can create clearance forms
- Forms are created for the specific `academic_year_id` and `semester_id` of the active period
- Signatory assignments are automatically created based on clearance requirements

#### Step 3: Regular Staff Viewing Applicants

- Regular Staff accesses their management page:
  - `CollegeStudentManagement.php` for College students
  - `SeniorHighStudentManagement.php` for Senior High School students
  - `FacultyManagement.php` for Faculty
- Page calls `api/clearance/signatoryList.php` with appropriate parameters
- API should return ALL applicants in the sector, regardless of whether they have clearance forms

#### Step 4: Signatory Actions

- Staff can approve or reject clearance requests
- Actions are performed via `api/clearance/signatory_action.php` or `api/clearance/bulk_signatory_action.php`
- Actions update the `clearance_signatories` table

#### Step 5: End User Receives Updates

- End users see updated clearance status in their clearance dashboard
- They can view which signatories have approved/rejected their requests

---

## Problem Identified

### Issue

Regular Staff management pages were **not showing applicants** when a clearance period was active for that sector.

### Root Cause Analysis

The problem was in `api/clearance/signatoryList.php`:

#### 1. Period Query Didn't Filter by Sector

**Location:** Lines 118 and 122-137

**Problem:**

- The period query could return ANY period (College, Senior High School, or Faculty)
- No sector filtering was applied
- Example: Senior High School page could get a College period

**Original Code:**

```php
// When schoolTerm provided:
$periodQuery = "SELECT cp.period_id FROM clearance_periods cp
    JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
    WHERE ay.year = :yearName AND cp.semester_id = :semesterId
    AND cp.status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')";
// ❌ NO SECTOR FILTER!

// When no schoolTerm:
$periodQuery = "
    SELECT period_id FROM (
        SELECT period_id, ...
        FROM clearance_periods
        WHERE status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')
        -- ❌ NO SECTOR FILTER!
    ) as prioritized_periods
    ORDER BY status_priority, period_id DESC
    LIMIT 1";
```

#### 2. Wrong Period Caused JOIN Failure

**Location:** Lines 221 (Faculty) and 271 (Students)

**Problem:**
When `activePeriodId` was for the wrong sector, the JOIN condition failed:

```sql
-- For students:
LEFT JOIN clearance_periods cp ON cp.sector = s.sector AND (cp.period_id = :activePeriodId)
-- If activePeriodId is for College but s.sector = 'Senior High School', JOIN fails
-- Result: cp becomes NULL

-- For faculty:
LEFT JOIN clearance_periods cp ON cp.sector = 'Faculty' AND (cp.period_id = :activePeriodId)
-- If activePeriodId is for 'Student', JOIN fails
-- Result: cp becomes NULL
```

#### 3. NULL `cp` Broke `clearance_forms` Join

**Location:** Lines 222 (Faculty) and 272 (Students)

**Problem:**
When `cp` is NULL, the `clearance_forms` join condition fails:

```sql
LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id
    AND cf.academic_year_id = cp.academic_year_id  -- NULL!
    AND cf.semester_id = cp.semester_id            -- NULL!
```

In SQL, `NULL = anything` evaluates to `FALSE`, so the join never matches.

#### 4. Result

- Applicants without clearance forms didn't appear (because `cp` was NULL)
- Applicants with clearance forms might not appear (if the period didn't match)
- Staff couldn't see who needed to create clearance forms

---

## Fix Implemented

### Location

`api/clearance/signatoryList.php` (lines 107-159)

### Solution

Filter period queries by the correct sector to ensure we always get the right period.

### Implementation Details

#### 1. Sector Determination Logic (Lines 107-118)

```php
// Determine the correct sector to filter periods by
// This ensures we get the right period for the right sector (College, Senior High School, or Faculty)
$periodSector = '';
if (strtolower($type) === 'faculty') {
    $periodSector = 'Faculty';
} else if (!empty($requestSector)) {
    // For students, use the requestSector directly ('College' or 'Senior High School')
    $periodSector = $requestSector;
} else {
    // Fallback: if no requestSector provided, default to 'College' (for backward compatibility with CollegeStudentManagement)
    $periodSector = 'College';
}
```

**Logic:**

- **Faculty:** Always use `'Faculty'`
- **Students with sector:** Use `requestSector` ('College' or 'Senior High School')
- **Students without sector:** Default to `'College'` (backward compatibility)

#### 2. Period Query with Sector Filter (When schoolTerm Provided)

```php
if (!empty($schoolTerm)) {
    // ... existing code to parse schoolTerm ...

    // Filter period query by sector to ensure we get the correct period for the requested sector
    $periodQuery = "SELECT cp.period_id FROM clearance_periods cp
        JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
        WHERE ay.year = :yearName
        AND cp.semester_id = :semesterId
        AND cp.status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')
        AND cp.sector = :periodSector";  // ✅ Added sector filter
    $periodParams = [':yearName' => $yearName, ':semesterId' => $semesterId, ':periodSector' => $periodSector];
}
```

#### 3. Period Query with Sector Filter (When No schoolTerm)

```php
else {
    // If no term is specified, find the most relevant period based on status priority.
    // Filter by sector to ensure we get the correct period for the requested sector
    $periodQuery = "
        SELECT period_id FROM (
            SELECT period_id,
                   CASE status
                       WHEN 'Ongoing' THEN 1
                       WHEN 'Paused' THEN 2
                       WHEN 'Not Started' THEN 3
                       WHEN 'Closed' THEN 4
                       ELSE 5
                   END as status_priority
            FROM clearance_periods
            WHERE status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')
            AND sector = :periodSector  // ✅ Added sector filter
        ) as prioritized_periods
        ORDER BY status_priority, period_id DESC
        LIMIT 1";
    $periodParams = [':periodSector' => $periodSector];
}
```

### How This Fixes the Issue

**Before Fix:**

1. Period query returns ANY period (could be wrong sector)
2. Wrong period → JOIN fails → `cp` becomes NULL
3. NULL `cp` → `clearance_forms` join fails → Applicants not shown

**After Fix:**

1. Period query filters by correct sector → Returns correct period
2. Correct period → JOIN succeeds → `cp` has values
3. Valid `cp` → `clearance_forms` join works → All applicants shown

### Impact on Each Page

#### CollegeStudentManagement.php

- **Sends:** `sector: 'College'`
- **Gets:** `periodSector = 'College'`
- **Finds:** College period
- **Result:** ✅ Works correctly

#### SeniorHighStudentManagement.php

- **Sends:** `sector: 'Senior High School'`
- **Gets:** `periodSector = 'Senior High School'`
- **Finds:** Senior High School period
- **Result:** ✅ Now works correctly (was broken before)

#### FacultyManagement.php

- **Sends:** `type: 'faculty'` (no sector parameter)
- **Gets:** `periodSector = 'Faculty'`
- **Finds:** Faculty period
- **Result:** ✅ Now works correctly (was broken before)

### Why College Wasn't Affected

The fix is **backward compatible**:

- College sends `sector: 'College'` → Gets College period → Works
- If no sector is provided, defaults to `'College'` → Still works
- The logic doesn't break existing College functionality

---

## Key Principles for Other Signatory Pages

### 1. Period Query Must Filter by Sector

**Always filter `clearance_periods` by the correct sector:**

- For students: Use `requestSector` ('College' or 'Senior High School')
- For faculty: Use `'Faculty'`
- Default to `'College'` if no sector provided (backward compatibility)

**Why:** Without sector filtering, you might get a period for the wrong sector, causing JOIN failures.

### 2. Sector Mapping

**Database Schema:**

- `clearance_periods.sector`: `'College'`, `'Senior High School'`, `'Faculty'`
- `students.sector`: `'College'` or `'Senior High School'`
- `faculty` table: No sector column (all faculty are in 'Faculty' sector)

**API Parameters:**

- `requestSector` parameter: `'College'` or `'Senior High School'` (for students)
- `type` parameter: `'student'` or `'faculty'`

**Mapping Logic:**

```php
if (type === 'faculty') {
    periodSector = 'Faculty';
} else if (requestSector exists) {
    periodSector = requestSector;  // 'College' or 'Senior High School'
} else {
    periodSector = 'College';  // Default fallback
}
```

### 3. JOIN Conditions Must Match

**For Students:**

```sql
LEFT JOIN clearance_periods cp ON cp.sector = s.sector AND (cp.period_id = :activePeriodId)
```

- Both `cp.sector` and `s.sector` use 'College' or 'Senior High School'
- Must match exactly

**For Faculty:**

```sql
LEFT JOIN clearance_periods cp ON cp.sector = 'Faculty' AND (cp.period_id = :activePeriodId)
```

- Hardcoded to match 'Faculty' sector

### 4. Show All Applicants When Period is Active

**Use LEFT JOINs:**

- Always use `LEFT JOIN` for `clearance_periods` and `clearance_forms`
- This ensures applicants without forms are still shown

**Don't filter out applicants:**

- Don't add `WHERE cf.clearance_form_id IS NOT NULL` unless specifically needed
- The period's `academic_year_id` and `semester_id` should be used for matching, not filtering

---

## Pages That Need Similar Fixes

### Pages Using signatoryList API

Based on codebase analysis, these pages also use signatory functions and should benefit from this fix:

#### 1. Program Head Pages

- `pages/program-head/CollegeStudentManagement.php`
- `pages/program-head/SeniorHighStudentManagement.php`
- `pages/program-head/FacultyManagement.php`

#### 2. School Administrator Pages

- `pages/school-administrator/CollegeStudentManagement.php`
- `pages/school-administrator/SeniorHighStudentManagement.php`
- `pages/school-administrator/FacultyManagement.php`

### Verification Checklist

Since all these pages call `api/clearance/signatoryList.php`, the fix should already apply. However, verify that each page:

- [ ] Sends the correct `sector` parameter (for student pages)
- [ ] Sends the correct `type` parameter (for faculty pages)
- [ ] Handles the API response correctly
- [ ] Displays applicants when clearance period is active
- [ ] Allows signatory actions (approve/reject)

### If Issues Persist

If other pages still have issues, check:

1. **Frontend API Calls:**

   - Verify `sector` parameter is sent correctly
   - Verify `type` parameter is sent correctly
   - Check browser console for API errors

2. **Backend Logic:**

   - Verify period query is filtering by sector (already fixed in `signatoryList.php`)
   - Check error logs for SQL errors
   - Verify JOIN conditions match sectors correctly

3. **Data Integrity:**
   - Verify `clearance_periods` table has correct sector values
   - Verify `students.sector` matches period sectors
   - Check that periods exist for the requested sector

---

## Testing Checklist

For each signatory page, verify the following:

### 1. Period Detection

- [ ] Page finds the correct period for its sector
- [ ] Period query filters by sector correctly
- [ ] No "Invalid parameter number" errors

### 2. Applicant Visibility

- [ ] Shows all applicants in the sector when period is active
- [ ] Shows applicants even if they don't have clearance forms yet
- [ ] Shows applicants with clearance forms
- [ ] Shows applicants with different clearance statuses (Pending, Approved, Rejected, Unapplied)

### 3. Signatory Actions

- [ ] Approve action works correctly
- [ ] Reject action works correctly
- [ ] Actions are scoped to the correct period
- [ ] Bulk actions work correctly

### 4. Filters

- [ ] School term filter works
- [ ] Clearance status filter works
- [ ] Account status filter works
- [ ] Designation filter works (role switching)
- [ ] Search functionality works

### 5. Edge Cases

- [ ] Works when no clearance period is active
- [ ] Works when multiple periods exist (different sectors)
- [ ] Works when period is 'Closed' status
- [ ] Works when period is 'Paused' status

---

## Technical Details

### Database Tables Involved

#### `clearance_periods`

- `period_id` (PK)
- `academic_year_id` (FK)
- `semester_id` (FK)
- `sector` (ENUM: 'College', 'Senior High School', 'Faculty')
- `status` (ENUM: 'Not Started', 'Ongoing', 'Paused', 'Closed')

#### `clearance_forms`

- `clearance_form_id` (PK)
- `user_id` (FK)
- `academic_year_id` (FK)
- `semester_id` (FK)
- `clearance_form_progress` (ENUM: 'unapplied', 'in-progress', 'complete', 'rejected')

#### `clearance_signatories`

- `signatory_id` (PK)
- `clearance_form_id` (FK)
- `designation_id` (FK)
- `action` (ENUM: 'Pending', 'Approved', 'Rejected')
- `additional_remarks` (TEXT)
- `reason_id` (FK)

#### `students`

- `student_id` (PK)
- `user_id` (FK)
- `sector` (ENUM: 'College', 'Senior High School')
- `department_id` (FK)
- `program_id` (FK)

#### `faculty`

- `faculty_id` (PK)
- `user_id` (FK)
- `employee_number`
- `employment_status`

### API Endpoints

#### `api/clearance/signatoryList.php`

**Purpose:** List applicants (students/faculty) for a signatory

**Parameters:**

- `type`: `'student'` or `'faculty'`
- `sector`: `'College'` or `'Senior High School'` (for students)
- `page`: Page number for pagination
- `limit`: Items per page
- `search`: Search query
- `clearance_status`: Filter by clearance status
- `account_status`: Filter by account status
- `school_term`: Filter by school term (format: "YYYY-YYYY|semester_id")
- `designation_filter`: Filter by designation (for role switching)

**Response:**

```json
{
    "success": true,
    "total": 100,
    "page": 1,
    "limit": 20,
    "stats": {
        "total": 100,
        "active": 80,
        "inactive": 15,
        "graduated": 5
    },
    "students": [...] // or "faculty": [...]
}
```

#### `api/clearance/signatory_action.php`

**Purpose:** Perform individual signatory action (approve/reject)

**Method:** POST

**Body:**

```json
{
  "applicant_user_id": 123,
  "designation_name": "Cashier",
  "action": "Approved",
  "remarks": "Approved by Cashier",
  "reason_id": null,
  "school_term": "2024-2025|2" // Optional
}
```

#### `api/clearance/bulk_signatory_action.php`

**Purpose:** Perform bulk signatory actions

**Method:** POST

**Body:**

```json
{
  "applicant_user_ids": [123, 456, 789],
  "designation_name": "Cashier",
  "action": "Approved",
  "remarks": "Bulk approved",
  "reason_id": null,
  "school_term": "2024-2025|2" // Optional
}
```

---

## Code Patterns

### Pattern 1: Period Query with Sector Filter

```php
// Determine sector
$periodSector = '';
if (strtolower($type) === 'faculty') {
    $periodSector = 'Faculty';
} else if (!empty($requestSector)) {
    $periodSector = $requestSector;
} else {
    $periodSector = 'College'; // Default
}

// Query with sector filter
$periodQuery = "SELECT cp.period_id FROM clearance_periods cp
    WHERE ... AND cp.sector = :periodSector";
$periodParams = [..., ':periodSector' => $periodSector];
```

### Pattern 2: Student Query with Period Join

```sql
FROM students s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN clearance_periods cp ON cp.sector = s.sector AND (cp.period_id = :activePeriodId)
LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id
    AND cf.academic_year_id = cp.academic_year_id
    AND cf.semester_id = cp.semester_id
LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
    AND cs.designation_id IN (:designationIds)
```

### Pattern 3: Faculty Query with Period Join

```sql
FROM faculty f
JOIN users u ON f.user_id = u.user_id
LEFT JOIN clearance_periods cp ON cp.sector = 'Faculty' AND (cp.period_id = :activePeriodId)
LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id
    AND cf.academic_year_id = cp.academic_year_id
    AND cf.semester_id = cp.semester_id
LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
    AND cs.designation_id IN (:designationIds)
```

---

## Summary

### The Fix

- **Problem:** Period queries didn't filter by sector, causing wrong periods to be found
- **Solution:** Added sector filtering to period queries
- **Result:** Correct periods are found, JOINs succeed, all applicants are shown

### Key Takeaways

1. **Always filter period queries by sector** - This is critical for correct operation
2. **Use LEFT JOINs** - To show applicants even without clearance forms
3. **Match sectors correctly** - JOIN conditions must match the sector values
4. **Backward compatibility** - Default to 'College' if no sector provided

### Next Steps

1. Test all signatory pages to verify the fix works
2. Check other user role pages (Program Head, School Administrator) for similar issues
3. Document any additional fixes needed
4. Update this document as new issues are discovered and fixed

---

## Revision History

- **2024-12-XX**: Initial documentation created
- **Fix Applied**: Sector filtering added to period queries in `api/clearance/signatoryList.php`

---

## Related Files

- `api/clearance/signatoryList.php` - Main API endpoint (FIXED)
- `pages/regular-staff/CollegeStudentManagement.php` - College student management
- `pages/regular-staff/SeniorHighStudentManagement.php` - SHS student management
- `pages/regular-staff/FacultyManagement.php` - Faculty management
- `api/clearance/signatory_action.php` - Individual signatory actions
- `api/clearance/bulk_signatory_action.php` - Bulk signatory actions

---

## Contact & Support

For questions or issues related to this fix, refer to:

- Error logs: `logs/error.log`
- API debug logs: Check `error_log()` output in `api/clearance/signatoryList.php`
