# School Administrator, Regular Staff & Program Head Management Pages - Fixes Documentation

## Overview

This document tracks all fixes implemented for School Administrator, Regular Staff, and Program Head management pages to enable view-only mode and resolve various errors.

**Date:** November 26, 2025  
**Issues Resolved:**

1. School Administrators couldn't view end-user data when not assigned as signatories
2. Regular Staff couldn't view end-user data when their designation wasn't assigned as signatory
3. 500 Internal Server Errors occurred when fetching data (parameter binding issues)
4. JavaScript `ReferenceError` when using permission variables in Regular Staff pages
5. Program Head pages had dual API conflicts and inconsistent permission handling

---

## Table of Contents

1. [School Administrator Frontend Fixes](#school-administrator-frontend-fixes)
2. [Regular Staff Frontend Fixes](#regular-staff-frontend-fixes)
3. [Backend API Fixes](#backend-api-fixes)
4. [Testing Notes](#testing-notes)
5. [Program Head Fixes](#program-head-fixes-added-november-26-2025)
6. [Program Head "Add Button" Permission Fixes](#program-head-add-button-permission-fixes-added-november-27-2025)

---

## School Administrator Frontend Fixes

### 1. Syntax Errors in DOMContentLoaded Event Listener

**Files Affected:**

- `pages/school-administrator/CollegeStudentManagement.php`
- `pages/school-administrator/SeniorHighStudentManagement.php`

**Issue:**

- JavaScript syntax error: "missing ) after argument list"
- Erroneous closing brace `}` in `DOMContentLoaded` event listener

**Fix:**

- Removed erroneous closing brace that was breaking the function structure
- Lines affected: ~2648 (College), ~2651 (Senior High)

**Code Change:**

```javascript
// BEFORE (BROKEN):
document.addEventListener('DOMContentLoaded', async function() {
    }
    updateTermIndicatorBanner();

// AFTER (FIXED):
document.addEventListener('DOMContentLoaded', async function() {
    updateTermIndicatorBanner();
```

---

### 2. String Escaping Issues in View-Only Indicator

**Files Affected:**

- `pages/school-administrator/CollegeStudentManagement.php` (line ~699)
- `pages/school-administrator/SeniorHighStudentManagement.php` (line ~678)
- `pages/school-administrator/FacultyManagement.php` (line ~2414)

**Issue:**

- JavaScript syntax error from escaped apostrophe in single-quoted string
- `sector\'s` breaking string parsing

**Fix:**

- Changed from single quotes with escaped apostrophe to template literals (backticks)

**Code Change:**

```javascript
// BEFORE (BROKEN):
indicator.innerHTML =
  '<i class="fas fa-eye"></i> <strong>View Only Mode:</strong> You are viewing this sector\'s clearance data...';

// AFTER (FIXED):
indicator.innerHTML = `<i class="fas fa-eye"></i> <strong>View Only Mode:</strong> You are viewing this sector's clearance data...`;
```

---

### 3. View-Only Mode Implementation

**Files Affected:**

- `pages/school-administrator/CollegeStudentManagement.php`
- `pages/school-administrator/SeniorHighStudentManagement.php`
- `pages/school-administrator/FacultyManagement.php`

**Features Added:**

#### A. Initialization Variables

- Added `CURRENT_STAFF_POSITION` JavaScript variable
- Added `canPerformSignatoryActions` flag from PHP backend

#### B. API Response Handling

- Updated `loadStudentsData()` / `fetchFaculty()` to read `can_perform_actions` from API response
- Updates `canPerformSignatoryActions` flag dynamically

#### C. Action Button State Management

- `updateActionButtonsState()`: Disables bulk action buttons when in view-only mode
- Individual approve/reject buttons disabled based on `canPerformSignatoryActions` flag
- `updateBulkButtons()`: Considers `canPerformSignatoryActions` when enabling/disabling

#### D. View-Only Indicator Banner

- `updateViewOnlyIndicator()`: Shows/hides view-only banner
- Displays message: "You are viewing this sector's clearance data, but you are not assigned as a signatory for this clearance period. Approve/Reject actions are disabled."

#### E. Simplified Signatory Actions

- `approveSignatory()` / `rejectSignatory()`: Simplified to use `signatory_action.php`
- Removed explicit `clearanceFormId` and `signatoryId` parameters
- Uses automatic resolution based on `applicant_user_id` and `designation_name`

#### F. Bulk Actions Update

- Updated to use `bulk_signatory_action.php` API
- Retrieves `user_id` from `data-user-id` attribute

---

## Regular Staff Frontend Fixes

### 1. Global Variable Pattern for Dynamic Permission

**Files Affected:**

- `pages/regular-staff/CollegeStudentManagement.php`
- `pages/regular-staff/SeniorHighStudentManagement.php`
- `pages/regular-staff/FacultyManagement.php`

**Issue:**

- PHP-embedded `canPerformActions` variable was static and didn't update when switching designations
- `ReferenceError: canPerformActions is not defined` when variable was used in wrong scope
- Multi-designation feature requires dynamic permission updates from API

**Fix:**

- Changed to global JavaScript variable `canPerformSignatoryActions`
- Default value set to `false` for safety
- Updated from API response on each data fetch

**Code Pattern:**

```javascript
// 1. DECLARE global variable at the top (default false for safety)
let canPerformSignatoryActions = false;

// 2. UPDATE from API response when fetching data
canPerformSignatoryActions = data.can_perform_actions === true;

// 3. USE consistently everywhere (no more PHP-embedded versions)
if (!canPerformSignatoryActions) {
  showToastNotification("You do not have permission...", "warning");
  return;
}
```

---

### 2. Replaced PHP-Embedded Variables with Global Variable

**Files Affected:**

- `pages/regular-staff/CollegeStudentManagement.php` (4 locations)
- `pages/regular-staff/SeniorHighStudentManagement.php` (2 locations)
- `pages/regular-staff/FacultyManagement.php` (1 location)

**Locations Fixed:**

#### A. `updateBulkButtons()` Function

```javascript
// BEFORE (BROKEN):
const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;

// AFTER (FIXED):
// Uses global canPerformSignatoryActions variable directly
button.disabled = checkedBoxes.length === 0 || !canPerformSignatoryActions;
```

#### B. `approveStudentClearance()` / `approveFacultyClearance()` Function

```javascript
// BEFORE (BROKEN):
const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
if (!canPerformActions) { ... }

// AFTER (FIXED):
if (!canPerformSignatoryActions) {
    showToastNotification('You do not have permission to perform this action.', 'warning');
    return;
}
```

#### C. `rejectStudentClearance()` / `rejectFacultyClearance()` Function

- Same pattern as approve functions

#### D. Debug Logging in Row Creation

```javascript
// BEFORE (BROKEN):
console.log('Sample button states:', { canPerformActions, ... });

// AFTER (FIXED):
console.log('Sample button states:', { canPerformSignatoryActions, ... });
```

---

### 3. View-Only Mode UI Components

**Features Added:**

#### A. `updateViewOnlyIndicator()` Function

- Shows/hides view-only banner based on `canPerformSignatoryActions`
- Displays message when staff is not assigned as signatory
- Identical implementation to School Administrator pages

#### B. `updateActionButtonsState()` Function

- Disables individual approve/reject buttons when in view-only mode
- Called after data loads

#### C. `updateBulkButtons()` Enhancement

- Added `canPerformSignatoryActions` check
- Bulk buttons disabled when in view-only mode, even if checkboxes are selected

---

## Backend API Fixes

### 1. No-Period Query Path for School Administrators and Regular Staff

**File:** `api/clearance/signatoryList.php`

**Issue:**

- School Administrators and Regular Staff couldn't see data when no active clearance period exists
- Query failed or returned empty results

**Fix:**

- Added special query path when `!$activePeriodId`
- Removes `clearance_periods` JOIN to show all applicants in sector
- Enables view-only mode even when no clearance periods are configured

**Code Location:**

- Faculty (School Admin): Lines 265-279
- Faculty (Regular Staff): Lines 280-294
- Students (School Admin): Lines 349-361
- Students (Regular Staff): Lines 362-374

**Query Structure:**

```php
// For School Admins with no period: Show all applicants without clearance_period join
} else if ($isSchoolAdmin && !$activePeriodId) {
    $from = "FROM faculty f / students s ...";
// For Regular Staff with no period: Same behavior
} else if (!$isSchoolAdmin && !$activePeriodId && !$queryDirectByTerm) {
    $from = "FROM faculty f / students s ...";
}
```

---

### 2. Permission Check for No-Period Case

**File:** `api/clearance/signatoryList.php` (Lines 603-606)

**Fix:**

- Added check: If no active period and no direct term query → `can_perform_actions = false`
- Ensures view-only mode when no periods exist

**Code:**

```php
// If there's no period at all, they can't perform actions (view-only mode)
if (!$activePeriodId && !$queryDirectByTerm) {
    $canPerformActions = false;
    error_log("SIGNATORY_LIST_DEBUG: No active period or term - setting canPerformActions to false (view-only mode)");
}
```

---

### 3. Parameter Binding Fix (500 Error Resolution)

**File:** `api/clearance/signatoryList.php` (Lines 570-576)

**Issue:**

- `SQLSTATE[HY093]: Invalid parameter number: parameter was not defined`
- `:designationId_0` was in `$params` but not used in SQL
- PDO complained about unused parameters
- Issue affected both School Administrators AND Regular Staff after view-only mode implementation

**Fix:**

- Filter out unused `:designationId_*` parameters for ALL staff roles
- Both School Admins and Regular Staff now use the same JOIN condition without designation filtering
- Only pass parameters that are actually used in the SQL query

**Code:**

```php
// Remove unused designationId parameters since they're not in the SQL (for both School Admins and Regular Staff)
// Both roles now use the same JOIN condition without designation filtering
$executeParams = array_filter($params, function($key) {
    return strpos($key, ':designationId_') === false;
}, ARRAY_FILTER_USE_KEY);
$executeParams[':limit'] = (int)$limit;
$executeParams[':offset'] = (int)$offset;
```

---

### 4. Comprehensive Debugging Logs

**File:** `api/clearance/signatoryList.php`

**Added Logging:**

- Request start: User info, parameters, flags
- Main query: SQL, parameters, placeholder matching
- Permission checks: Query execution, results
- Error handling: Full stack traces, detailed error messages

**Log Prefixes:**

- `SIGNATORY_LIST_DEBUG:` - General debugging information
- `SIGNATORY_LIST_ERROR:` - Error conditions

---

### 5. Role Detection and Conditional Query Building

**File:** `api/clearance/signatoryList.php` (Lines 25-48, 243-247, 313-317)

**Features:**

- Detects "School Administrator" role
- Conditional `clearance_signatories` JOIN:
  - **School Admins:** `cf.clearance_form_id = cs.clearance_form_id` (shows all)
  - **Regular Staff:** `cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($designationInClause)` (filtered)

**Code:**

```php
$signatoryJoinCondition = $isSchoolAdmin
    ? "cf.clearance_form_id = cs.clearance_form_id"
    : "cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($designationInClause)";
```

---

### 6. Permission Check Queries

**File:** `api/clearance/signatoryList.php` (Lines 607-688)

**Features:**

- Checks if School Administrator's designation is assigned as signatory
- Handles two scenarios:
  1. Active period exists: Checks `clearance_periods` table
  2. Direct term query: Checks `clearance_forms` directly
- Uses unique parameter names (`:permCheckDesig_$i`) to avoid conflicts
- Try-catch blocks for error handling
- Sets `can_perform_actions` flag in API response

---

### 7. API Response Enhancement

**File:** `api/clearance/signatoryList.php` (Line 935)

**Added:**

- `can_perform_actions` flag in JSON response
- Frontend uses this to enable/disable action buttons

**Response Structure:**

```json
{
    "success": true,
    "total": 100,
    "page": 1,
    "limit": 20,
    "stats": {...},
    "can_perform_actions": true/false,
    "students"/"faculty": [...]
}
```

---

### 8. Conditional WHERE Clause for Regular Staff View-Only Mode

**File:** `api/clearance/signatoryList.php` (Lines 394-419)

**Issue:**

- Regular Staff with a designation NOT assigned as signatory saw empty results
- The WHERE clause always filtered by `designation_filter`, even when filter didn't match staff's designations
- Example: Staff with "Guidance" designation viewing as "Staff" (from dropdown) → empty list

**Root Cause:**

```php
// BEFORE: Always applied filter, causing empty results
if (!empty($designationFilter) && !$isSchoolAdmin) {
    $where .= " AND (d_sig.designation_name = :designationFilter OR cf.clearance_form_id IS NULL)";
}
```

**Fix:**

- Check if `designation_filter` matches any of the staff's actual designations
- If matches → Apply WHERE filter (shows only assigned applicants)
- If doesn't match → Don't apply filter (shows ALL applicants in view-only mode)

**Code:**

```php
if (!empty($designationFilter) && !$isSchoolAdmin) {
    // Check if the designation filter matches any of the staff's actual designations
    $filterMatchesStaffDesignation = false;
    if (!empty($staffDesignations)) {
        foreach ($staffDesignations as $desig) {
            if (strcasecmp($desig['designation_name'], $designationFilter) === 0) {
                $filterMatchesStaffDesignation = true;
                break;
            }
        }
    }

    // Only apply the WHERE filter if the designation matches
    // This allows "View Only" mode when the filter doesn't match (show all records)
    if ($filterMatchesStaffDesignation) {
        $where .= " AND (d_sig.designation_name = :designationFilter OR cf.clearance_form_id IS NULL)";
        $params[':designationFilter'] = $designationFilter;
    }
    // If filter doesn't match, don't add WHERE clause - show all records (view-only mode)
}
```

**Behavior Summary:**

| Scenario                           | Filter Matches? | WHERE Applied? | Records Shown              |
| ---------------------------------- | --------------- | -------------- | -------------------------- |
| Staff "Cashier" views as "Cashier" | ✅ Yes          | ✅ Yes         | Only assigned applicants   |
| Staff "Guidance" views as "Staff"  | ❌ No           | ❌ No          | ALL applicants (view-only) |
| School Admin (any filter)          | N/A             | ❌ No          | ALL applicants             |

---

### 9. Regular Staff Permission Check Logic

**File:** `api/clearance/signatoryList.php` (Lines 794-920)

**Features:**

- Checks if Regular Staff's designation is assigned as signatory
- Respects `designation_filter` parameter for multi-designation support
- If filter provided but doesn't match staff's designations → `can_perform_actions = false`
- If filter matches → checks database for actual signatory assignment

**Code:**

```php
// If designation filter doesn't match any of their designations, they can't perform actions
if (!empty($designationFilter)) {
    foreach ($staffDesignations as $desig) {
        if (strcasecmp($desig['designation_name'], $designationFilter) === 0) {
            $checkDesignationIds[] = $desig['designation_id'];
            break;
        }
    }
    if (empty($checkDesignationIds)) {
        $canPerformActions = false;
    }
}
```

---

## Feature Comparison

| Feature                           | School Administrator | Regular Staff      |
| --------------------------------- | -------------------- | ------------------ |
| See applicants when not assigned? | ✅ Yes (view-only)   | ✅ Yes (view-only) |
| `can_perform_actions` check?      | ✅ Yes               | ✅ Yes             |
| View-only UI banner?              | ✅ Yes               | ✅ Yes             |
| No-period query path?             | ✅ Yes               | ✅ Yes             |
| Multi-designation support?        | N/A                  | ✅ Yes             |
| Dynamic permission from API?      | ✅ Yes               | ✅ Yes             |

---

## Testing Notes

### Test Scenarios

#### School Administrator

1. ✅ **Assigned as signatory:** Can see data and perform actions
2. ✅ **Not assigned as signatory:** Can see data in view-only mode (buttons disabled)
3. ✅ **No active period:** Can see all data in view-only mode
4. ✅ **No clearance periods at all:** Can see all data in view-only mode

#### Regular Staff

1. ✅ **Assigned as signatory:** Can see data and perform actions
2. ✅ **Not assigned as signatory:** Can see ALL data in view-only mode (buttons disabled)
3. ✅ **Multi-designation - matching:** Actions enabled for selected designation
4. ✅ **Multi-designation - not matching:** View-only mode when viewing as different designation
5. ✅ **No active period:** Can see all data in view-only mode
6. ✅ **No clearance periods at all:** Can see all data in view-only mode

### Error Resolution

- ✅ Fixed 500 Internal Server Error for School Administrators
- ✅ Fixed 500 Internal Server Error for Regular Staff (same parameter binding issue)
- ✅ Fixed JavaScript syntax errors
- ✅ Fixed parameter binding issues (now applies to both roles)
- ✅ Fixed string escaping issues
- ✅ Fixed `canPerformActions is not defined` ReferenceError in Regular Staff pages

---

## Files Modified

### School Administrator Frontend Files

1. `pages/school-administrator/CollegeStudentManagement.php`
2. `pages/school-administrator/SeniorHighStudentManagement.php`
3. `pages/school-administrator/FacultyManagement.php`

### Regular Staff Frontend Files

1. `pages/regular-staff/CollegeStudentManagement.php`
2. `pages/regular-staff/SeniorHighStudentManagement.php`
3. `pages/regular-staff/FacultyManagement.php`

### Backend Files

1. `api/clearance/signatoryList.php`

---

## Key Takeaways

1. **School Administrators** now have full view-only mode support
2. **Regular Staff** now have full view-only mode support (with multi-designation awareness)
3. **500 errors** resolved by filtering unused parameters (applies to both roles)
4. **No-period scenarios** handled gracefully for both roles
5. **Permission checks** properly implemented with API-driven dynamic updates
6. **Multi-designation feature** preserved and enhanced for Regular Staff
7. **Consistent architecture** - both roles use same pattern (global variable + API response)

---

## Architecture Summary

### Permission Flow

```
1. Frontend loads page
   ↓
2. JavaScript declares: let canPerformSignatoryActions = false;
   ↓
3. Fetch data from API (signatoryList.php)
   ↓
4. API checks:
   - Does designation filter match staff's designations?
   - Is staff assigned as signatory for this period/sector?
   ↓
5. API returns: { can_perform_actions: true/false, ... }
   ↓
6. Frontend updates: canPerformSignatoryActions = data.can_perform_actions;
   ↓
7. UI updates:
   - Enable/disable buttons
   - Show/hide view-only banner
```

### Multi-Designation Flow (Regular Staff Only)

```
1. Staff has designations: [Cashier, Guidance]
   ↓
2. Staff selects "Cashier" from dropdown
   ↓
3. Frontend sends: designation_filter=Cashier
   ↓
4. API checks:
   - Does "Cashier" match [Cashier, Guidance]? → YES
   - Is "Cashier" assigned as signatory? → Check DB
   ↓
5. If assigned → can_perform_actions: true
   If not assigned → can_perform_actions: false (view-only)
   ↓
6. Staff switches to "Staff" (not in their designations)
   ↓
7. API checks:
   - Does "Staff" match [Cashier, Guidance]? → NO
   ↓
8. Show ALL records (no WHERE filter), can_perform_actions: false
```

---

---

## Program Head Fixes (Added November 26, 2025)

### Overview

Program Head pages had similar issues to School Administrator and Regular Staff:

1. Dual API usage creating potential conflicts (`is_assigned.php` vs `signatoryList.php`)
2. Inconsistent variable naming (`canPerformActions`, `CAN_TAKE_ACTION`, `window.CAN_TAKE_ACTION`)
3. PHP-embedded variables not updating dynamically
4. No dedicated permission check path in `signatoryList.php`

---

### 1. Added Program Head Permission Check Path in signatoryList.php

**File:** `api/clearance/signatoryList.php`

**Issue:**

- Program Head was treated as "Regular Staff" in permission checks
- Used different logic than `is_assigned.php` API

**Fix:**

- Added dedicated `else if ($isProgramHead)` block in permission check section
- Uses same logic as `is_assigned.php`:
  1. Check department scope (`$programHeadDepartments`)
  2. Check `include_program_head` setting from `sector_clearance_settings`
  3. Check for active period

**Code Added:**

```php
} else if ($isProgramHead) {
    // Program Head permission check
    // Uses the same logic as api/program-head/is_assigned.php for consistency
    $canPerformActions = false;

    // Check 1: Department scope
    $hasDepartmentScope = !empty($programHeadDepartments);

    // Check 2: include_program_head setting
    $checkSector = ($type === 'faculty') ? 'Faculty' : $requestSector;
    $settingStmt = $pdo->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
    $settingStmt->execute([$checkSector]);
    $settingRow = $settingStmt->fetch(PDO::FETCH_ASSOC);
    $includePhSetting = $settingRow ? (int)$settingRow['include_program_head'] : 0;

    // Check 3: Active period
    $hasActivePeriod = $activePeriodId || $queryDirectByTerm;

    // Final permission: all three checks must pass
    $canPerformActions = $hasDepartmentScope && ($includePhSetting === 1) && $hasActivePeriod;
}
```

---

### 2. Standardized Variable Naming in Program Head Pages

**Files Affected:**

- `pages/program-head/CollegeStudentManagement.php`
- `pages/program-head/SeniorHighStudentManagement.php`
- `pages/program-head/FacultyManagement.php`

**Changes:**

- Renamed all `canPerformActions` → `canPerformSignatoryActions`
- Removed all `window.CAN_TAKE_ACTION` references
- Declared global variable with default `false`:

```javascript
// Global permission flag - default to false for safety
// Updated dynamically from is_assigned.php API (primary source for Program Head)
let canPerformSignatoryActions = false;
```

---

### 3. Updated Permission API Calls

**Files Affected:**

- `pages/program-head/CollegeStudentManagement.php` - `fetchCanTakeAction()`
- `pages/program-head/SeniorHighStudentManagement.php` - `fetchCanTakeActionSHS()`
- `pages/program-head/FacultyManagement.php` - `fetchCanTakeActionFaculty()`

**Code Pattern:**

```javascript
async function fetchCanTakeAction() {
  try {
    const resp = await fetch(
      "../../api/program-head/is_assigned.php?clearance_type=College",
      { credentials: "include" }
    );
    const data = await resp.json();
    if (data && data.success) {
      canPerformSignatoryActions = !!data.can_take_action;
      console.log("is_assigned (College):", data);
      console.log(
        "Program Head canPerformSignatoryActions set to:",
        canPerformSignatoryActions
      );
    }
  } catch (e) {
    console.error("Error fetching assignment status:", e);
  }
}
```

---

### 4. Added API Response Logging for Debugging

**Files Affected:**

- All Program Head management pages

**Purpose:**

- Log `can_perform_actions` from `signatoryList.php` for validation
- Warn if permission mismatch between `is_assigned.php` and `signatoryList.php`

**Code Added:**

```javascript
// Log permission from signatoryList.php for debugging
console.log(
  "signatoryList.php response - can_perform_actions:",
  data.can_perform_actions
);
console.log(
  "Current canPerformSignatoryActions (from is_assigned.php):",
  canPerformSignatoryActions
);

// If signatoryList.php says NO but is_assigned.php said YES, log warning
if (canPerformSignatoryActions && data.can_perform_actions === false) {
  console.warn(
    "Permission mismatch: is_assigned.php=true, signatoryList.php=false. Using is_assigned.php as source of truth."
  );
}
```

---

### 5. Simplified Action Button State Management

**Files Affected:**

- All Program Head management pages

**Changes:**

- Removed complex fallback logic
- Uses `canPerformSignatoryActions` directly

```javascript
// BEFORE (complex fallback):
const canAct =
  typeof window.CAN_TAKE_ACTION !== "undefined"
    ? window.CAN_TAKE_ACTION
    : typeof CAN_TAKE_ACTION !== "undefined"
    ? CAN_TAKE_ACTION
    : false;

// AFTER (simplified):
const canAct = canPerformSignatoryActions;
```

---

### Program Head API Strategy

**Dual API Usage:**

| API                 | Purpose                                     | Source of Truth |
| ------------------- | ------------------------------------------- | --------------- |
| `is_assigned.php`   | Permission check (specific to Program Head) | ✅ Primary      |
| `signatoryList.php` | Data fetching + backup permission           | Validation only |

**Flow Diagram:**

```
┌─────────────────────────────────────────────────────────────────┐
│                    Program Head Page Load                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 1. fetchCanTakeAction() → is_assigned.php                       │
│    Returns: { can_take_action: true/false }                     │
│    Sets: canPerformSignatoryActions                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. loadStudentsData() → signatoryList.php                       │
│    Returns: { students/faculty: [...], can_perform_actions }    │
│    Logs: can_perform_actions for debugging/validation           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. updateActionButtonsState()                                   │
│    Uses: canPerformSignatoryActions (from is_assigned.php)      │
│    Result: Buttons enabled/disabled based on permission         │
└─────────────────────────────────────────────────────────────────┘
```

---

### Feature Comparison (Updated)

| Feature                           | School Admin       | Regular Staff      | Program Head       |
| --------------------------------- | ------------------ | ------------------ | ------------------ |
| See applicants when not assigned? | ✅ Yes (view-only) | ✅ Yes (view-only) | ✅ Yes (view-only) |
| `can_perform_actions` check?      | ✅ Yes             | ✅ Yes             | ✅ Yes             |
| View-only UI banner?              | ✅ Yes             | ✅ Yes             | ✅ Yes             |
| No-period query path?             | ✅ Yes             | ✅ Yes             | ✅ Yes             |
| Multi-designation support?        | N/A                | ✅ Yes             | N/A                |
| Dynamic permission from API?      | ✅ Yes             | ✅ Yes             | ✅ Yes             |
| Uses `is_assigned.php`?           | ❌ No              | ❌ No              | ✅ Yes             |
| Uses `sector_clearance_settings`? | ❌ No              | ❌ No              | ✅ Yes             |

---

### Files Modified for Program Head

**Frontend Files:**

1. `pages/program-head/CollegeStudentManagement.php`
2. `pages/program-head/SeniorHighStudentManagement.php`
3. `pages/program-head/FacultyManagement.php`

**Backend Files:**

1. `api/clearance/signatoryList.php` (added Program Head permission check path)

---

**Last Updated:** November 27, 2025  
**Status:** ✅ All fixes implemented for School Administrator, Regular Staff, and Program Head

---

## Program Head "Add Button" Permission Fixes (Added November 27, 2025)

### Overview

Implemented department-based permission checks for "Add Student" and "Add Faculty" buttons in Program Head pages. These buttons are now disabled when the Program Head is not assigned to the relevant sector's departments.

---

### 1. CollegeStudentManagement.php - Add Student Button

**File:** `pages/program-head/CollegeStudentManagement.php`

**Issue:**

- "Add Student" button was always enabled, even when Program Head had no College department assignments
- No validation of department scope before allowing student creation

**Fix:**

- Updated `updateActionButtonsState()` function (lines 1554-1582)
- Added check for `window.managedDepartments` (populated by `loadProgramHeadProfile()`)
- Button is disabled if `managedDepartments` is empty or undefined
- Tooltip changes to: _"You are not assigned to any College departments"_

**Code:**

```javascript
// Check if Program Head has College department assignments
const hasCollegeDepartments =
  window.managedDepartments &&
  Array.isArray(window.managedDepartments) &&
  window.managedDepartments.length > 0;

// Handle Add Student button separately (requires department assignment)
document.querySelectorAll(".add-student-btn").forEach((btn) => {
  if (hasCollegeDepartments) {
    btn.disabled = false;
    btn.title = "Add a new college student to the system";
  } else {
    btn.disabled = true;
    btn.title = "You are not assigned to any College departments";
  }
});
```

---

### 2. SeniorHighStudentManagement.php - Add Student Button

**File:** `pages/program-head/SeniorHighStudentManagement.php`

**Issue:**

- "Add Student" button was always enabled for SHS page
- No check if Program Head is assigned to Senior High School department

**Fix:**

- Updated `updateActionButtonsState()` function (lines 1417-1445)
- Added specific check for Senior High School department using `.some()` with name matching
- Button is disabled if no SHS department found in assignments
- Tooltip changes to: _"You are not assigned to the Senior High School department"_

**Code:**

```javascript
// Check if Program Head has Senior High School department assignments
const hasSHSDepartment =
  window.managedDepartments &&
  Array.isArray(window.managedDepartments) &&
  window.managedDepartments.some(
    (dept) =>
      dept.department_name &&
      dept.department_name.toLowerCase().includes("senior high")
  );

// Handle Add Student button separately (requires SHS department assignment)
document.querySelectorAll(".add-student-btn").forEach((btn) => {
  if (hasSHSDepartment) {
    btn.disabled = false;
    btn.title = "Add a new senior high school student to the system";
  } else {
    btn.disabled = true;
    btn.title = "You are not assigned to the Senior High School department";
  }
});
```

---

### 3. FacultyManagement.php - Add Faculty Button + Profile Loading

**File:** `pages/program-head/FacultyManagement.php`

**Issues:**

- "Add Faculty" button was not included in button state management
- `loadProgramHeadProfile()` function was never called (unlike student pages)
- `window.managedDepartments` was undefined, causing validation issues

**Fixes:**

**A. Added `loadProgramHeadProfile()` function (line 1636):**

```javascript
async function loadProgramHeadProfile() {
  try {
    const response = await fetch("../../api/program-head/profile.php", {
      credentials: "include",
    });
    const data = await response.json();
    if (data.success) {
      window.managedDepartments = data.data.departments;
      const deptNames = data.data.departments
        .map((d) => d.department_name)
        .join(", ");
      const scopeElement = document.getElementById("departmentScopeText");
      if (scopeElement) {
        scopeElement.textContent = `Scope: ${deptNames}`;
      }
    }
  } catch (error) {
    console.error("Error loading Program Head profile:", error);
  }
}
```

**B. Updated `DOMContentLoaded` to call profile loading (line 1660):**

```javascript
document.addEventListener("DOMContentLoaded", async function () {
  updateTermIndicatorBanner();

  // Load Program Head profile FIRST to get department assignments
  await loadProgramHeadProfile();

  await Promise.all([
    loadRejectionReasons(),
    // ... other loads
  ]);
  // ... rest of initialization
});
```

**C. Updated `updateFacultyActionButtonsState()` function (lines 1106-1131):**

```javascript
// Check if Program Head has College department assignments (faculty are linked to college depts)
const hasCollegeDepartments =
  window.managedDepartments &&
  Array.isArray(window.managedDepartments) &&
  window.managedDepartments.length > 0;

// Handle Add Faculty button separately (requires department assignment)
document.querySelectorAll(".add-faculty-btn").forEach((btn) => {
  if (hasCollegeDepartments) {
    btn.disabled = false;
    btn.title = "Add a new faculty member to the system";
  } else {
    btn.disabled = true;
    btn.title = "You are not assigned to any departments";
  }
});
```

---

### 4. Import/Export Buttons

**All Pages:** Import and Export buttons remain **always enabled** for Program Heads regardless of department assignments, as these are administrative functions that can be restricted server-side if needed.

---

### Permission Logic Summary

| Page                 | Add Button    | Enabled When              | Disabled When          | Disabled Tooltip                                            |
| -------------------- | ------------- | ------------------------- | ---------------------- | ----------------------------------------------------------- |
| **College Students** | Add Student   | Has ≥1 College department | No College departments | "You are not assigned to any College departments"           |
| **SHS Students**     | Add Student   | Has SHS department        | No SHS department      | "You are not assigned to the Senior High School department" |
| **Faculty**          | Add Faculty   | Has ≥1 department         | No departments         | "You are not assigned to any departments"                   |
| **All Pages**        | Import/Export | Always                    | Never                  | N/A                                                         |

---

### Real-World Example

**Scenario:** LCA2001P is Program Head of **ICT Department** (College) only.

| Page                 | Add Button State                | Why?                        |
| -------------------- | ------------------------------- | --------------------------- |
| **College Students** | ✅ Enabled                      | ICT is a College department |
| **SHS Students**     | ❌ Disabled                     | ICT ≠ Senior High School    |
| **Faculty**          | ✅ Enabled                      | Has department assignment   |
| **All Pages**        | ✅ Import/Export always enabled | Administrative functions    |

---

## Regular Staff Export Report Fixes (Added November 27, 2025)

### Overview

Fixed critical error preventing Regular Staff from exporting PDF reports (Student/Faculty Clearance Applicant Status Reports). The issue was caused by missing PHP `mbstring` extension on Heroku deployment.

---

### Issue: Call to undefined function mb_strlen()

**Error Message:**

```
Fatal error building PDF: Call to undefined function mb_strlen()
```

**Affected Files:**

- `includes/classes/ReportGenerator.php` (lines 885-886, 894)
- Used `mb_strlen()` to calculate text wrapping for table cells

**Stack Trace:**

```
#0 /app/includes/classes/ReportGenerator.php(56): ReportGenerator->generatePDFWithFPDI()
#1 /app/api/reports/export.php(167): ReportGenerator->generateReport()
#2 {main}
```

**When Error Occurred:**

- Regular Staff attempting to export "Student Clearance Applicant Status Report"
- Regular Staff attempting to export "Faculty Clearance Applicant Status Report"
- Any report generated by `ReportGenerator.php` using `mb_strlen()`

**Why Clearance Form PDFs Worked:**

- Clearance forms use `ClearanceFormPDFGenerator.php`
- Uses FPDF's native `MultiCell()` for text wrapping
- Doesn't require `mb_strlen()`

---

### Root Cause Analysis

**Initial Attempt (Failed):**

1. Removed `ext-mbstring` requirement from `composer.json`
2. Assumed it would be automatically available with PHP 8.4
3. Deployment failed - Heroku didn't enable the extension

**Discovery:**
Checking Heroku build logs showed:

```bash
-----> Installing platform packages...
       - php (8.4.15)
       - ext-gd (bundled with php)
       # ext-mbstring was NOT listed!
```

**Conclusion:**

- `mbstring` IS bundled with PHP 8.4
- But Heroku buildpack requires **explicit declaration** in `composer.json` to enable it
- Without declaration, the extension remains disabled even though it's available

---

### Solution: Explicit mbstring Requirement

**File Modified:** `composer.json`

**Changes:**

```json
{
  "require": {
    "php": ">=7.4",
    "ext-mbstring": "*",
    "phpoffice/phpword": "^1.4",
    "tecnickcom/tcpdf": "^6.10",
    "setasign/fpdi": "^2.6",
    "setasign/fpdf": "^1.8"
  }
}
```

**Implementation Steps:**

1. Added `"ext-mbstring": "*"` to `composer.json`
2. Ran `composer update --lock --ignore-platform-reqs` to update lock file
3. New `content-hash`: `ca766b0a7a04967b7228ade73d90878b`
4. Committed: `841019c` - "Add ext-mbstring requirement to enable mbstring on Heroku PHP 8.4"
5. Pushed to GitHub and deployed to Heroku

---

### Verification

**After Deployment - Heroku Build Log:**

```bash
-----> Installing platform packages...
       - php (8.4.15)
       - ext-mbstring (bundled with php)  ← Now appears!
       - ext-gd (bundled with php)
```

**Result:**

- ✅ Export functionality now works
- ✅ No more `mb_strlen()` errors
- ✅ PDF reports generate successfully for Regular Staff

---

### Technical Details

**Why `mb_strlen()` is Needed:**

In `ReportGenerator.php`, the function calculates if text needs wrapping:

```php
// Line 885-886: Check if department/program name is too long
$deptNeedsWrapping = ($isStudentReport ?
    (isset($row['program_name']) && mb_strlen($row['program_name'] ?? '') > floor($columns['department']['width'] / 1.5)) :
    (mb_strlen($deptValue) > floor($columns['department']['width'] / 1.5)));

// Line 894: Calculate estimated lines needed for wrapping
$charsPerLine = floor($columns['department']['width'] / 1.8);
$estimatedLines = max(1, ceil(mb_strlen($deptText) / $charsPerLine));
```

**Why `mb_strlen()` vs `strlen()`:**

- `strlen()` counts **bytes**, not **characters**
- `mb_strlen()` counts **characters** correctly for UTF-8 (accented letters, special chars)
- Example: `"José"` → `strlen() = 5 bytes`, `mb_strlen() = 4 characters`
- Proper character counting ensures accurate text wrapping in PDFs

---

### Related Issue: Designation vs Role in Export Header

**Current Behavior:**

- Exported PDF header shows user's **role** ("Regular Staff")
- Not their **designation** ("Disciplinary Officer")

**Why This Happens:**

- `ExportModal.php` (line 997) sends `role` parameter from session
- `api/reports/export.php` (line 51) uses `$roleName = $auth->getRoleName()`
- Doesn't include `designation_filter` value from dropdown

**Status:** ⚠️ Known issue - Low priority (cosmetic only, doesn't affect functionality)

**Potential Fix (Not Yet Implemented):**

1. Modify `ExportModal.php` to capture and send `designation_filter` from page context
2. Modify `export.php` to accept `designation` parameter
3. Pass designation to `ReportGenerator.php` for PDF header
4. Use designation if provided, otherwise fall back to role

---

### Testing Results

**Before Fix:**

- ❌ Export Student Applicant Status Report → 400 Error (`mb_strlen()` undefined)
- ❌ Export Faculty Applicant Status Report → 400 Error (`mb_strlen()` undefined)
- ✅ Export Clearance Forms → Works (different generator, doesn't use `mb_strlen()`)

**After Fix:**

- ✅ Export Student Applicant Status Report → PDF generated successfully
- ✅ Export Faculty Applicant Status Report → PDF generated successfully
- ✅ Export Clearance Forms → Still works
- ⚠️ PDF header shows "Regular Staff" instead of "Disciplinary Officer" (known cosmetic issue)

---

### Lessons Learned

1. **Heroku PHP Extensions:**

   - Bundled extensions still require explicit declaration in `composer.json`
   - Check build logs to verify which extensions are actually installed
   - Use `ext-{name}: "*"` syntax for bundled extensions
   - Don't assume bundled = automatically enabled

2. **Composer Lock File:**

   - Always run `composer update` after changing `composer.json`
   - Commit both `composer.json` AND `composer.lock`
   - Use `--ignore-platform-reqs` locally to bypass local PHP differences
   - Lock file `content-hash` changes when requirements change

3. **Error Diagnosis:**

   - Check Heroku build logs, not just runtime logs
   - "Call to undefined function" = missing PHP extension
   - Compare working vs failing code paths (why clearance forms worked but reports didn't)
   - Look for functions that require specific extensions (`mb_*`, `gd_*`, etc.)

4. **Different Code Paths:**
   - `ClearanceFormPDFGenerator.php` doesn't need `mbstring`
   - `ReportGenerator.php` requires `mbstring` for text wrapping calculations
   - Always test all export types, not just one

---

### Commit History

1. **0ee150b** - "Remove ext-mbstring and ext-gd requirements (bundled with PHP 8.4)" - ❌ Failed (wrong approach)
2. **452fd2c** - "still fixing" - ❌ Didn't include composer changes
3. **841019c** - "Add ext-mbstring requirement to enable mbstring on Heroku PHP 8.4" - ✅ **SUCCESSFUL FIX**

---

**Status:** ✅ Export functionality fully restored for Regular Staff  
**Date Fixed:** November 27, 2025  
**Fixed By:** Adding explicit `ext-mbstring` requirement to `composer.json`
