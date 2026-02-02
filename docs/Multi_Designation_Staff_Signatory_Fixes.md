# Multi-Designation Staff Signatory Action Fixes

**Date:** January 23, 2026  
**Issue:** Multi-designation staff members unable to sign clearances with non-primary designations  
**Status:** ✅ Resolved

---

## Table of Contents
1. [Problem Summary](#problem-summary)
2. [Root Causes](#root-causes)
3. [Solutions Implemented](#solutions-implemented)
4. [Files Changed](#files-changed)
5. [Testing Checklist](#testing-checklist)
6. [Related Context](#related-context)

---

## Problem Summary

### The Issue
Staff members with multiple designations were unable to approve/reject student clearances when:
- They had multiple designations (e.g., Clinic, Cashier, Registrar)
- Their primary designation was NOT assigned as a signatory
- They selected a non-primary designation from the dropdown

### Example Scenario
**Staff Profile:**
- Primary Designation: Clinic
- Other Designations: Cashier, Registrar

**Sector Signatory Assignments (College):**
- ✅ Cashier (assigned)
- ✅ Registrar (assigned)  
- ❌ Clinic (NOT assigned)

**Error Flow:**
1. Staff selects "Cashier" from dropdown
2. Staff attempts to reject a student's clearance
3. System sends "Clinic" (primary) instead of "Cashier" (selected)
4. API returns: `403 Forbidden - "The Clinic designation is not assigned to sign for College clearances"`

---

## Root Causes

### 1. Database Schema Constraint
**File:** Database table `sector_signatory_assignments`  
**Issue:** Column `user_id` was defined as `NOT NULL`

```sql
`user_id` int(11) NOT NULL COMMENT 'Staff member assigned as signatory'
```

This prevented designation-based assignments where `user_id` should be `NULL`.

### 2. Missing Table Reference
**File:** `api/signatories/sector_settings.php`  
**Issue:** Function `getProgramHeadsForSector()` queried non-existent table `staff_department_assignments`

```php
FROM staff_department_assignments sda
JOIN staff s ON sda.staff_id = s.employee_number
```

**Impact:** Modal failed to load, blocking all signatory assignments.

### 3. Validation Logic Issue
**File:** `api/signatories/sector_assignments.php`  
**Issue:** Used `isset()` which returns `false` for `null` values

```php
if (!isset($data['user_id'])) {  // Returns false when user_id = null
    throw new Exception("Missing required field: user_id");
}
```

### 4. JavaScript Fetch Bug (6 files)
**Files:** All regular-staff and school-administrator student management pages  
**Issue:** JavaScript fetched primary designation from database instead of reading dropdown

```javascript
// ❌ WRONG: Fetches primary designation
const desigResponse = await fetch('../../api/users/get_current_staff_designation.php');
currentDesignation = desigData.designation_name;  // Always returns "Clinic"
```

---

## Solutions Implemented

### Solution 1: Database Migration ✅
**Made `user_id` column nullable**

```sql
ALTER TABLE `sector_signatory_assignments` 
MODIFY COLUMN `user_id` int(11) NULL 
COMMENT 'Staff member (NULL for designation-only assignments)';
```

**Why:** Allows designation-based signatory assignments where any staff with the designation can sign.

---

### Solution 2: Fixed Missing Table Reference ✅
**File:** `api/signatories/sector_settings.php` (Lines 160-183)

**Before:**
```php
function getProgramHeadsForSector($pdo, $clearanceType) {
    $sql = "
        SELECT 
            s.user_id,
            u.first_name,
            u.last_name,
            u.username as employee_number,
            sda.department_id,
            d.department_name,
            sec.sector_name,
            sda.is_primary
        FROM staff_department_assignments sda  -- ❌ Table doesn't exist
        JOIN staff s ON sda.staff_id = s.employee_number
        ...
```

**After:**
```php
function getProgramHeadsForSector($pdo, $clearanceType) {
    $sql = "
        SELECT 
            s.user_id,
            u.first_name,
            u.last_name,
            u.username as employee_number,
            s.department_id,  -- ✅ Direct from staff table
            d.department_name,
            sec.sector_name,
            1 as is_primary
        FROM staff s  -- ✅ Uses staff table directly
        JOIN users u ON s.user_id = u.user_id
        JOIN departments d ON s.department_id = d.department_id
        JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE sec.sector_name = ? 
            AND s.department_id IS NOT NULL
        ...
```

**Why:** The `staff_department_assignments` table was removed in a previous schema simplification. Now uses `staff.department_id` directly.

---

### Solution 3: Fixed Validation Logic ✅
**File:** `api/signatories/sector_assignments.php` (Line 107)

**Before:**
```php
function assignSignatory($pdo, $data) {
    $requiredFields = ['clearance_type', 'user_id', 'designation_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {  // ❌ Returns false for null
            throw new Exception("Missing required field: $field");
        }
    }
```

**After:**
```php
function assignSignatory($pdo, $data) {
    $requiredFields = ['clearance_type', 'user_id', 'designation_id'];
    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $data)) {  // ✅ Checks key exists, allows null
            throw new Exception("Missing required field: $field");
        }
    }
```

**Why:** `array_key_exists()` checks if the key exists in the array, regardless of whether the value is `null`.

---

### Solution 4: Fixed JavaScript Dropdown Reading ✅
**Files:** 6 student management pages (regular-staff & school-administrator)

**Before:**
```javascript
// ❌ Fetches from database (gets primary designation)
let currentDesignation = CURRENT_STAFF_POSITION;
try {
    const desigResponse = await fetch('../../api/users/get_current_staff_designation.php', { 
        credentials: 'include' 
    });
    const desigData = await desigResponse.json();
    if (desigData.success && desigData.designation_name) { 
        currentDesignation = desigData.designation_name;  // Gets "Clinic"
    }
} catch (e) { 
    // Use fallback
}
```

**After:**
```javascript
// ✅ Reads from dropdown (gets selected designation)
const roleSelector = document.getElementById('roleSelector');
let currentDesignation = CURRENT_STAFF_POSITION;

if (roleSelector) {
    currentDesignation = roleSelector.value;  // Gets "Cashier" if selected
}
```

**Why:** Respects the user's dropdown selection instead of always using the primary designation from the database.

---

## Files Changed

### Backend API Changes (2 files)
| File | Lines Changed | Change Type |
|------|--------------|-------------|
| `api/signatories/sector_settings.php` | 160-183 | Query rewrite (removed table reference) |
| `api/signatories/sector_assignments.php` | 107 | Validation fix (`isset` → `array_key_exists`) |

### Frontend JavaScript Changes (6 files)
| File | Lines Changed | Change Type |
|------|--------------|-------------|
| `pages/regular-staff/CollegeStudentManagement.php` | ~1835-1855 | Dropdown read (removed API fetch) |
| `pages/regular-staff/SeniorHighStudentManagement.php` | ~1937-1957 | Dropdown read (removed API fetch) |
| `pages/regular-staff/FacultyManagement.php` | ~2004-2024 | Dropdown read (removed API fetch) |
| `pages/school-administrator/CollegeStudentManagement.php` | ~1944-1956 | Dropdown read (removed API fetch) |
| `pages/school-administrator/SeniorHighStudentManagement.php` | ~2045-2057 | Dropdown read (removed API fetch) |
| `pages/school-administrator/FacultyManagement.php` | ~2434-2446 | Dropdown read (removed API fetch) |

### Database Schema Changes (1 migration)
| Table | Column | Change |
|-------|--------|--------|
| `sector_signatory_assignments` | `user_id` | `NOT NULL` → `NULL` |

**Total Files Modified:** 8 files + 1 database migration

---

## Testing Checklist

### ✅ Prerequisites
- [ ] Database migration applied (user_id column is nullable)
- [ ] All 8 files updated with new code
- [ ] Browser cache cleared

### ✅ Test Case 1: Single Designation Staff
**Setup:**
- Staff has only one designation (e.g., "Cashier")
- Designation is assigned as College signatory

**Steps:**
1. Login as the staff member
2. Navigate to College Student Management
3. Approve/reject a student clearance
4. Check Network tab for `signatory_action.php`

**Expected Result:**
- ✅ Action succeeds (200 OK)
- ✅ Payload shows: `designation_name: "Cashier"`
- ✅ No console errors

---

### ✅ Test Case 2: Multi-Designation Staff (Primary IS Signatory)
**Setup:**
- Staff has: Cashier (primary), Registrar, Clinic
- All three are assigned as College signatories

**Steps:**
1. Login as the staff member
2. Select "Cashier" from dropdown
3. Approve/reject a student clearance
4. Select "Registrar" from dropdown
5. Approve/reject another student clearance

**Expected Result:**
- ✅ Both actions succeed
- ✅ First payload: `designation_name: "Cashier"`
- ✅ Second payload: `designation_name: "Registrar"`

---

### ✅ Test Case 3: Multi-Designation Staff (Primary NOT Signatory) 🎯
**Setup:**
- Staff has: Clinic (primary), Cashier, Registrar
- Only Cashier & Registrar are assigned as College signatories
- Clinic is NOT assigned

**Steps:**
1. Login as the staff member
2. Verify dropdown shows only: Cashier, Registrar (NO Clinic)
3. Select "Cashier" from dropdown
4. Approve/reject a student clearance
5. Check Network tab

**Expected Result:**
- ✅ Action succeeds (200 OK) ← **Was failing before!**
- ✅ Payload shows: `designation_name: "Cashier"` ← **Was sending "Clinic" before!**
- ✅ No 403 Forbidden error
- ✅ No console errors

---

### ✅ Test Case 4: Signatory Assignment Modal
**Steps:**
1. Login as admin
2. Navigate to Clearance Management
3. Click "Add Signatories" for College sector
4. Verify modal loads without errors
5. Select designations (Cashier, Registrar)
6. Click "Add"
7. Verify designations appear in signatory list

**Expected Result:**
- ✅ Modal loads without errors ← **Was breaking before!**
- ✅ Designations table populates
- ✅ POST request succeeds (200 OK)
- ✅ Signatory list updates with new designations
- ✅ No `staff_department_assignments` error

---

### ✅ Test Case 5: Dropdown Selection Persistence
**Steps:**
1. Login as multi-designation staff
2. Select "Registrar" from dropdown
3. Perform an action (approve/reject)
4. Refresh page
5. Check which designation is selected

**Expected Result:**
- ✅ Selected designation is remembered (via session/localStorage)
- ✅ Subsequent actions use the selected designation

---

## Related Context

### Background: Designation-Based vs User-Based Signatories

**Old Approach (User-Based):**
```
Assignment: user_id = 195, designation_id = 2
Result: ONLY John Doe (user 195) can sign clearances
Problem: If John is absent, clearances get stuck
```

**New Approach (Designation-Based):**
```
Assignment: user_id = NULL, designation_id = 2
Result: ANY staff with designation_id = 2 (Cashier) can sign
Benefit: Maria, John, or Rosa (all Cashiers) can sign
```

### Why `user_id = NULL`?
- **Assignment Time:** `user_id = NULL` means "assign the role, not a specific person"
- **Distribution Time:** System finds all active staff with that `designation_id`
- **Signing Time:** Any of those staff members can sign the clearance

### Schema Evolution
The `staff_department_assignments` table was removed during a previous schema simplification:
- **Old:** Complex multi-department assignments per staff member
- **New:** Simple `staff.department_id` (one department per staff)
- **Issue:** One function (`getProgramHeadsForSector`) still referenced the old table

### Related Files Not Modified
These files were identified but do NOT need changes:
- `pages/admin/*` - Admins don't sign clearances
- `pages/program-head/CollegeStudentManagement.php` - Program Heads don't sign college clearances
- `pages/program-head/SeniorHighStudentManagement.php` - Uses parameter (clean implementation)

### Pending Issues
**Program-Head Files (3 files) - Hardcoded Designation:**
- `pages/program-head/CollegeStudentManagement.php` (Line 630)
- `pages/program-head/SeniorHighStudentManagement.php` (Line 614)
- `pages/program-head/FacultyManagement.php` (Line 2024-2028)

These files hardcode `'Program Head'` instead of using `CURRENT_STAFF_POSITION` or `roleSelector.value`. To be fixed in a separate task.

---

## Benefits of These Fixes

### 🎯 User Experience
- ✅ Multi-designation staff can now use ALL their assigned designations
- ✅ Dropdown selection is respected
- ✅ No more confusing 403 errors

### ⚡ Performance
- ✅ Removed unnecessary API call (`get_current_staff_designation.php`)
- ✅ Faster page interactions (one less network request per action)

### 🔒 Reliability
- ✅ Modal loads without errors
- ✅ Correct designation always sent to backend
- ✅ Database schema aligns with application logic

### 🧹 Code Quality
- ✅ Consistent logic across all 6 student management pages
- ✅ Removed dependency on missing database table
- ✅ Proper null handling in validation

---

## Summary

**Problem:** Multi-designation staff couldn't sign clearances with non-primary designations  
**Root Cause:** JavaScript ignored dropdown, fetched primary designation from database  
**Solution:** Read from dropdown instead of API + fix database schema + API validation  
**Impact:** All multi-designation staff can now use any of their assigned designations  
**Files Changed:** 8 files + 1 database migration  
**Status:** ✅ Fully Resolved

---

**Document Version:** 1.0  
**Last Updated:** January 23, 2026  
**Next Steps:** Fix hardcoded designation issues in program-head pages (separate task)
