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

**Last Updated:** November 26, 2025  
**Status:** ✅ All fixes implemented for School Administrator, Regular Staff, and Program Head
