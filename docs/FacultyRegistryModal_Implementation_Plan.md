# Faculty Registry Modal - Implementation Plan

## Overview

This document outlines the implementation plan for updating the `FacultyRegistryModal.php` to integrate cross-sector department management and role-based access control.

**Date:** December 15, 2025  
**Status:** ✅ Implemented  
**Related Feature:** Cross-Sector Department Management

---

## Goals

1. **Unify department assignment** for faculty with the cross-sector system
2. **Match design consistency** with student registration modals
3. **Implement role-based access** for Admin vs Program Head
4. **Remove hardcoded department** (currently `department_id: 50` - General Education)

---

## Current State

### FacultyRegistryModal.php (Before)

- **Primary Department:** Hardcoded to `department_id: 50` (General Education)
- **Multi-Department Section:** Optional, uses simple dropdown
- **No role detection:** Same behavior for Admin and Program Head
- **No cross-sector indicators:** Flat list of departments

### Problem

- All faculty default to "General Education" department
- Program Heads cannot see faculty in their departments
- No consistency with student registration design

---

## Implementation Plan

### 1. Add Primary Department Dropdown (Required)

**Location:** After Contact Number field (around line 44)

**New HTML:**

```html
<!-- Primary Department (Required) - matches student registration pattern -->
<div class="form-group">
  <label for="primaryDepartment">Department *</label>
  <select id="primaryDepartment" name="primaryDepartment" required>
    <option value="">Loading Departments...</option>
  </select>
  <small class="form-help" id="departmentHelpText"
    >Select the primary department for this faculty member</small
  >
</div>
```

**Design Reference:** `CollegeStudentRegistryModal.php` lines 32-38

---

### 2. Detect Admin vs Program Head Context

**Logic:**

```javascript
// Check if we're in Program Head context (restricted mode)
const isRestrictedMode =
  typeof DEPARTMENT_IDS !== "undefined" &&
  Array.isArray(DEPARTMENT_IDS) &&
  DEPARTMENT_IDS.length > 0;
```

**Context Detection:**

| Page                                       | `DEPARTMENT_IDS` Exists? | Mode                      |
| ------------------------------------------ | ------------------------ | ------------------------- |
| `pages/admin/FacultyManagement.php`        | ❌ No                    | Admin (full access)       |
| `pages/program-head/FacultyManagement.php` | ✅ Yes                   | Program Head (restricted) |

---

### 3. Filter Departments Based on User's Access

**Admin Mode:**

- Fetch all Faculty sector departments (`sector_id = 3`)
- Show cross-sector indicators
- Group by: Cross-Sector vs Faculty-Only

**Program Head Mode:**

- Fetch only assigned departments (from `DEPARTMENT_IDS`)
- Filter to Faculty sector versions only
- If single department: auto-select and make readonly

---

### 4. Show Cross-Sector Indicators (Option A with Smart Display)

**For Admin:**

```
Primary Department *
┌─────────────────────────────────────────────────────┐
│ Select primary department...                      ▼ │
├─────────────────────────────────────────────────────┤
│ ── Cross-Sector Departments ──                      │
│   ICT (Information & Communication Technology)      │
│     └ Shared with: College                          │
│   BAS (Business, Arts, & Science)                   │
│     └ Shared with: College                          │
│   THM (Tourism & Hospitality Management)            │
│     └ Shared with: College                          │
│                                                     │
│ ── Faculty-Only Departments ──                      │
│   General Education                                 │
└─────────────────────────────────────────────────────┘
```

**For Program Head (single department):**

```
Primary Department *
┌─────────────────────────────────────────────────────┐
│ ICT (Information & Communication Technology)    🔒  │
└─────────────────────────────────────────────────────┘
ℹ️ Faculty will be registered under your assigned department.
```

---

### 5. Auto-Select for Single-Department Program Heads

**Behavior:**

- If Program Head has only 1 assigned department:
  - Auto-select that department
  - Make dropdown readonly (disabled)
  - Update help text: "Faculty will be registered under your assigned department"
  - Hide multi-department section

---

### 6. Keep Multi-Department Section (Optional)

**Location:** Below primary department field

**Changes:**

- Move section below primary department
- Update help text
- Filter additional departments to exclude primary
- For Program Heads: only show departments within their scope

---

## Files to Modify

### Primary File

- `Modals/FacultyRegistryModal.php`

### No Changes Required

- `api/departments/list.php` - Already returns cross-sector info
- `api/users/create_faculty.php` - Already accepts `department_id` parameter
- `includes/classes/UserManager.php` - Already handles department assignment

---

## Code Changes Summary

### HTML Changes (lines ~44-65)

1. Add primary department dropdown (required)
2. Update multi-department section position
3. Add help text elements

### JavaScript Changes

1. Create `populatePrimaryDepartmentDropdown()` function
2. Update `populateAdditionalDepartmentSelect()` to use cross-sector data
3. Add context detection logic
4. Update `confirmFacultyCreation()` to use selected department
5. Add validation to prevent duplicate department selection

---

## Implementation Checklist

- [x] Add primary department dropdown HTML
- [x] Create `populatePrimaryDepartmentDropdown()` function
- [x] Implement Admin vs Program Head detection
- [x] Fetch and display Faculty sector departments
- [x] Add cross-sector indicators for Admin view
- [x] Implement auto-select for single-department Program Heads
- [x] Update help text based on context
- [x] Update multi-department section
- [x] Update `confirmFacultyCreation()` to use selected department
- [x] Add validation (no duplicate selections)
- [ ] Test with Admin account
- [ ] Test with Program Head account (single department)
- [ ] Test with Program Head account (multiple departments)

---

## Testing Scenarios

### Scenario 1: Admin Registration

1. Login as Admin
2. Open Faculty Registration Modal
3. Verify all Faculty sector departments are shown
4. Verify cross-sector indicators are displayed
5. Select ICT department
6. Register faculty
7. Verify faculty has `department_id` = ICT Faculty (53)

### Scenario 2: Program Head Registration (Single Department)

1. Login as ICT Program Head
2. Open Faculty Registration Modal
3. Verify only ICT is shown (auto-selected, readonly)
4. Verify multi-department section is hidden
5. Register faculty
6. Verify faculty has `department_id` = ICT Faculty (53)

### Scenario 3: Program Head Registration (Multiple Departments)

1. Login as Program Head with multiple departments
2. Open Faculty Registration Modal
3. Verify only assigned departments are shown
4. Select one as primary
5. Optionally add additional departments
6. Register faculty
7. Verify correct department assignments

---

## Related Documentation

- `docs/Implementation_Summary.md` - Cross-sector feature overview
- `docs/Cross_Sector_Department_Management_Plan.md` - Original implementation plan
- `docs/Phase5_Testing_Checklist.md` - Testing guide

---

## Notes

### Database Prerequisites

The following Faculty sector departments must exist:

- ICT (department_id: 53) ✅ Created
- BAS (department_id: 54) ✅ Created by user
- THM (department_id: 55) ✅ Created by user
- General Education (department_id: 50) ✅ Existing

### Role-Based Behavior Summary

| Role                  | Primary Dept Dropdown | Multi-Dept Section | Cross-Sector Indicators |
| --------------------- | --------------------- | ------------------ | ----------------------- |
| Admin                 | All Faculty depts     | Full access        | ✅ Shown                |
| Program Head (1 dept) | Auto-select, readonly | Hidden             | ❌ Not needed           |
| Program Head (multi)  | Limited to scope      | Limited to scope   | ❌ Not needed           |

---

**Implementation Status:** ✅ Complete  
**Implementation Date:** December 15, 2025  
**Next Step:** Test with Admin and Program Head accounts
