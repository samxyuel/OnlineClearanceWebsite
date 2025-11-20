# Clearance Progress Modal School Term Filtering Enhancement

## Overview

**Purpose:** Enable ClearanceProgressModal to view past clearance progress based on the selected school term filter from the management table.

**Date Created:** 2025-01-27

**Status:** ✅ **COMPLETED**

**Issue:** The ClearanceProgressModal was not respecting the schoolTermFilter selection, so it always showed current/active clearance progress instead of the filtered term's progress.

---

## Executive Summary

This document outlines the enhancement of ClearanceProgressModal to support viewing clearance progress for specific school terms. The modal now automatically uses the selected school term from the `schoolTermFilter` dropdown in the management pages, allowing users to view historical clearance data.

**Implementation Status:**

- ✅ **Admin pages** - Updated to pass schoolTerm to modal
- ✅ **School Administrator pages** - Already passing schoolTerm correctly
- ✅ **Program Head pages** - Already passing schoolTerm correctly
- ✅ **Regular Staff pages** - Already passing schoolTerm correctly
- ✅ **ClearanceProgressModal** - Enhanced to display and use schoolTerm

---

## Problem Analysis

### Issue Description

When users filtered the management table by a specific school term using the `schoolTermFilter` dropdown, clicking "View Clearance Progress" would always show the current/active clearance progress instead of the progress for the selected term.

**Expected Behavior:**

- User selects "2024-2025 - 1st Semester" in schoolTermFilter
- User clicks "View Clearance Progress" on a student
- Modal should show clearance progress for "2024-2025 - 1st Semester"

**Previous Behavior:**

- Modal always showed current/active clearance progress regardless of filter selection

### Root Cause

**Admin Pages (BEFORE - PROBLEM):**

```javascript
function viewClearanceProgress(studentId) {
  openClearanceProgressModal(studentId, "student", "Student Name");
  // ❌ Not passing schoolTerm parameter
}
```

**Admin Pages (AFTER - FIXED):**

```javascript
function viewClearanceProgress(studentId, studentName = "", schoolTerm = "") {
  // Get schoolTerm from filter if not provided
  if (!schoolTerm) {
    schoolTerm = document.getElementById("schoolTermFilter")?.value || "";
  }
  openClearanceProgressModal(studentId, "student", studentName, schoolTerm);
  // ✅ Now passing schoolTerm parameter
}
```

---

## Files Fixed

### Admin Role Pages (3 files updated)

1. **`pages/admin/CollegeStudentManagement.php`**

   - **Function:** `viewClearanceProgress()` (Line ~914)
   - **Function:** `createStudentRow()` (Line ~617)
   - **Changes:**
     - Updated `viewClearanceProgress()` to accept and pass `schoolTerm`
     - Updated `createStudentRow()` to capture `currentSchoolTerm` from filter
     - Updated onclick handler to pass `schoolTerm` to `viewClearanceProgress()`

2. **`pages/admin/SeniorHighStudentManagement.php`**

   - **Function:** `viewClearanceProgress()` (Line ~835)
   - **Function:** `createStudentRow()` (Line ~528)
   - **Changes:**
     - Updated `viewClearanceProgress()` to accept and pass `schoolTerm`
     - Updated `createStudentRow()` to capture `currentSchoolTerm` from filter
     - Updated onclick handler to pass `schoolTerm` to `viewClearanceProgress()`

3. **`pages/admin/FacultyManagement.php`**
   - **Function:** `viewClearanceProgress()` (Line ~1738)
   - **Changes:**
     - Updated `viewClearanceProgress()` to get `schoolTerm` from filter and pass it to modal

### Modal Enhancement (1 file updated)

4. **`Modals/ClearanceProgressModal.php`**
   - **HTML:** Modal header (Line ~19)
   - **Function:** `openClearanceProgressModal()` (Line ~290)
   - **Function:** `loadClearanceProgressData()` (Line ~360)
   - **Changes:**
     - Added `id="progressSchoolTerm"` to modal supporting text
     - Updated `openClearanceProgressModal()` to display selected school term
     - Enhanced `loadClearanceProgressData()` with better logging and loading state
     - Improved school_term parameter handling

### Files Already Correct (No Changes Needed)

**School Administrator:**

- ✅ `pages/school-administrator/CollegeStudentManagement.php` - Already passes `schoolTerm`
- ✅ `pages/school-administrator/SeniorHighStudentManagement.php` - Already passes `schoolTerm`
- ✅ `pages/school-administrator/FacultyManagement.php` - Already passes `schoolTerm`

**Program Head:**

- ✅ `pages/program-head/CollegeStudentManagement.php` - Already passes `schoolTerm`
- ✅ `pages/program-head/SeniorHighStudentManagement.php` - Already passes `schoolTerm`
- ✅ `pages/program-head/FacultyManagement.php` - Already passes `schoolTerm`

**Regular Staff:**

- ✅ `pages/regular-staff/CollegeStudentManagement.php` - Already passes `schoolTerm`
- ✅ `pages/regular-staff/SeniorHighStudentManagement.php` - Already passes `schoolTerm`
- ✅ `pages/regular-staff/FacultyManagement.php` - Already passes `schoolTerm`

---

## Implementation Details

### Standard Pattern for viewClearanceProgress()

All management pages now follow this pattern:

```javascript
function viewClearanceProgress(personId, personName = "", schoolTerm = "") {
  // If personName not provided, try to get it from the table row
  if (!personName) {
    const row = document
      .querySelector(`.person-checkbox[data-id="${personId}"]`)
      ?.closest("tr");
    if (row) {
      const nameCell = row.querySelector("td:nth-child(3)");
      personName = nameCell ? nameCell.textContent.trim() : "Person";
    } else {
      personName = "Person";
    }
  }

  // If schoolTerm not provided, get it from the filter
  if (!schoolTerm) {
    schoolTerm = document.getElementById("schoolTermFilter")?.value || "";
  }

  // Open the clearance progress modal with school term
  openClearanceProgressModal(
    personId,
    "student" | "faculty",
    personName,
    schoolTerm
  );
}
```

### Table Row Creation Pattern

For student management pages, the `createStudentRow()` function now captures the current school term:

```javascript
function createStudentRow(student) {
  // Capture the currently selected school term from the filters
  const currentSchoolTerm =
    document.getElementById("schoolTermFilter")?.value || "";

  // Escape HTML for safe insertion
  const escapeHtml = (text) => {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  };

  // ... row creation code ...

  // In the onclick handler:
  onclick =
    "viewClearanceProgress('${student.user_id}', '${escapeHtml(student.name || 'Student')}', '${escapeHtml(currentSchoolTerm)}')";
}
```

### Modal Enhancements

**1. Modal Header Display:**

```html
<div class="modal-supporting-text" id="progressSchoolTerm">
  View detailed clearance progress and signatory status
</div>
```

**2. School Term Display Logic:**

```javascript
const schoolTermElement = document.getElementById("progressSchoolTerm");
if (schoolTermElement) {
  if (schoolTerm && schoolTerm.trim() !== "") {
    schoolTermElement.textContent = `School Term: ${schoolTerm} - View detailed clearance progress and signatory status`;
  } else {
    schoolTermElement.textContent =
      "View detailed clearance progress and signatory status (Current/Active Term)";
  }
}
```

**3. Enhanced API Call:**

```javascript
if (schoolTerm && schoolTerm.trim() !== "") {
  url.searchParams.append("school_term", schoolTerm.trim());
  console.log(
    "[ClearanceProgressModal] Loading clearance progress for term:",
    schoolTerm
  );
} else {
  console.log(
    "[ClearanceProgressModal] Loading clearance progress for current/active term"
  );
}
```

---

## Changes Made

### 1. Admin CollegeStudentManagement.php

**JavaScript Changes:**

- Updated `viewClearanceProgress()` to accept `studentName` and `schoolTerm` parameters
- Added logic to get `schoolTerm` from filter if not provided
- Updated `createStudentRow()` to capture `currentSchoolTerm` from filter
- Added HTML escaping function for safe string insertion
- Updated onclick handler to pass `schoolTerm` to `viewClearanceProgress()`

### 2. Admin SeniorHighStudentManagement.php

**JavaScript Changes:**

- Updated `viewClearanceProgress()` to accept `studentName` and `schoolTerm` parameters
- Added logic to get `schoolTerm` from filter if not provided
- Updated `createStudentRow()` to capture `currentSchoolTerm` from filter
- Added HTML escaping function for safe string insertion
- Updated onclick handler to pass `schoolTerm` to `viewClearanceProgress()`

### 3. Admin FacultyManagement.php

**JavaScript Changes:**

- Updated `viewClearanceProgress()` to get `schoolTerm` from filter
- Added null safety check for row element
- Updated to pass `schoolTerm` to `openClearanceProgressModal()`

### 4. ClearanceProgressModal.php

**HTML Changes:**

- Added `id="progressSchoolTerm"` to modal supporting text element

**JavaScript Changes:**

- Updated `openClearanceProgressModal()` to display selected school term
- Enhanced `loadClearanceProgressData()` with:
  - Better school_term parameter validation
  - Console logging for debugging
  - Loading state display
  - Improved error handling

---

## Testing Checklist

### Functional Testing

- [x] Filter table by school term, then open modal - should show that term's data
- [x] Open modal without filter - should show current/active term
- [x] Modal header displays selected term (if filtered)
- [x] Modal shows correct signatories for selected term
- [x] Progress percentage matches selected term's data
- [x] Works for both students and faculty
- [x] Works across all role pages (Admin, School Admin, Program Head, Regular Staff)

### Edge Cases

- [x] Empty schoolTerm filter - shows current/active term
- [x] Invalid schoolTerm - API handles gracefully
- [x] Student name not found - falls back to "Student"
- [x] Faculty name not found - falls back to "Faculty"
- [x] Network errors - shows error message in modal

---

## Success Criteria

✅ **All Criteria Met:**

1. **Filter Integration:** Modal respects schoolTermFilter selection
2. **Display Clarity:** Modal header shows which term is being viewed
3. **Data Accuracy:** Modal shows correct clearance progress for selected term
4. **User Experience:** Clear indication of which term's data is displayed
5. **Consistency:** All role pages work identically
6. **Error Handling:** Graceful handling of missing or invalid data

---

## Related Files

### JavaScript Functions

- `viewClearanceProgress(personId, personName, schoolTerm)` - Opens modal with school term
- `openClearanceProgressModal(personId, personType, personName, schoolTerm)` - Modal opener
- `loadClearanceProgressData(personId, personType, schoolTerm)` - Loads clearance data
- `createStudentRow(student)` - Creates table row with school term context

### API Endpoints

- `../../api/clearance/user_status.php` - Main API for clearance progress
  - Parameters: `user_id` or `employee_number`, `school_term` (optional)

### HTML Elements

- `#schoolTermFilter` - School term filter dropdown in management pages
- `#progressPersonName` - Person name display in modal header
- `#progressSchoolTerm` - School term display in modal supporting text
- `#signatoriesList` - Signatories list container in modal

---

## Notes

- The `schoolTerm` parameter format should match the format used in the `schoolTermFilter` dropdown (e.g., "2024-2025 - 1st Semester")
- If `schoolTerm` is empty or not provided, the API returns current/active clearance progress
- The modal automatically updates the display text to indicate which term is being viewed
- All management pages now consistently pass the school term to the modal
- The implementation follows the same pattern as the end-user clearance page

---

## Reference Implementation

The end-user clearance page (`pages/end-user/clearance.php`) uses a similar pattern:

- Uses `form_id` parameter to load specific clearance forms
- Has a dropdown selector for different school terms/periods
- Calls `user_status.php?form_id={id}` to load specific form data

The management pages use:

- `school_term` parameter (string format like "2024-2025 - 1st Semester")
- Calls `user_status.php?user_id={id}&school_term={term}` to load term-specific data

Both approaches work with the same API endpoint, which handles both `form_id` and `school_term` parameters.

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-27  
**Status:** ✅ **COMPLETED**
