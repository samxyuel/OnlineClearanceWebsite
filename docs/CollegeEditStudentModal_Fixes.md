# CollegeEditStudentModal.php - Required Key Fixes

**File:** `Modals/CollegeEditStudentModal.php`  
**Date:** December 26, 2024  
**Purpose:** Document required fixes for the College Edit Student Modal based on role-based access requirements

---

## Overview

The `CollegeEditStudentModal.php` requires fixes to properly handle role-based editing permissions and resolve data loading issues.

---

## Permission Model

### Program Head Permissions

| Field                       | Permission   | Behavior                                                         |
| --------------------------- | ------------ | ---------------------------------------------------------------- |
| **Department**              | ❌ View Only | Disabled dropdown - shows current department but cannot change   |
| **Program/Course**          | ✅ Editable  | Shows only programs within Program Head's assigned department(s) |
| **Year Level**              | ✅ Editable  | Full access                                                      |
| **Section (Term + Number)** | ✅ Editable  | Full access                                                      |
| **Email**                   | ✅ Editable  | Full access                                                      |
| **Contact Number**          | ✅ Editable  | Full access                                                      |
| **Account Status**          | ✅ Editable  | Full access                                                      |
| **Password Reset**          | ✅ Available | Full access                                                      |

**Key Constraint:** Program dropdown must be filtered to only show programs belonging to the Program Head's assigned department(s).

### Admin Permissions

| Field                       | Permission   | Behavior                                      |
| --------------------------- | ------------ | --------------------------------------------- |
| **Department**              | ✅ Editable  | Can change to any College department          |
| **Program/Course**          | ✅ Editable  | Programs cascade based on selected department |
| **Year Level**              | ✅ Editable  | Full access                                   |
| **Section (Term + Number)** | ✅ Editable  | Full access                                   |
| **Email**                   | ✅ Editable  | Full access                                   |
| **Contact Number**          | ✅ Editable  | Full access                                   |
| **Account Status**          | ✅ Editable  | Full access                                   |
| **Password Reset**          | ✅ Available | Full access                                   |

---

## Key Behavioral Differences

| Scenario                                             | Admin                            | Program Head                        |
| ---------------------------------------------------- | -------------------------------- | ----------------------------------- |
| Department dropdown state                            | Enabled - all College depts      | **Disabled** - current dept only    |
| Program dropdown source                              | Based on **selected** department | Based on **assigned** department(s) |
| Can move student to different department?            | ✅ Yes                           | ❌ No                               |
| Can change student's program within same department? | ✅ Yes                           | ✅ Yes (within scope)               |

---

## Required Key Fixes

### Fix 1: CRITICAL - Error Handling in `loadStudentData()`

**Problem:** The `await Promise.all([loadEditDepartments(), loadEditYearLevels()])` is placed OUTSIDE the try-catch block, causing unhandled promise rejections when dropdown loading fails.

**Solution:** Wrap ALL async operations inside the try-catch block.

```javascript
async function loadStudentData(userId) {
  try {
    // Move dropdown loading INSIDE try-catch
    await Promise.all([loadEditDepartments(), loadEditYearLevels()]);

    // Then fetch student data...
  } catch (error) {
    // Handle error, close modal
  }
}
```

---

### Fix 2: HTTP Response Validation

**Problem:** No check for `response.ok` before parsing JSON. If API returns 404 or 500, parsing fails silently.

**Solution:** Add response status check.

```javascript
const response = await fetch(
  `../../api/users/get_student.php?user_id=${userId}`,
  {
    credentials: "include",
  }
);

if (!response.ok) {
  throw new Error(`HTTP error! Status: ${response.status}`);
}

const data = await response.json();
```

---

### Fix 3: userId Validation Before API Call

**Problem:** If `student.user_id` is undefined in the table data, the modal opens but passes invalid value to API.

**Solution:** Validate userId at the start of `openEditStudentModal()`.

```javascript
window.openEditStudentModal = function (userId) {
  if (!userId || userId === "undefined" || userId === "null") {
    showToastNotification("Cannot edit student: Invalid user ID.", "error");
    return;
  }
  // Continue with modal opening...
};
```

---

### Fix 4: Role-Based Access Control

**Problem:** No detection of user role (Admin vs Program Head). All users see the same UI.

**Solution:** Implement role detection and apply restrictions.

```javascript
function isEditStudentRestrictedMode() {
  return (
    typeof DEPARTMENT_IDS !== "undefined" &&
    Array.isArray(DEPARTMENT_IDS) &&
    DEPARTMENT_IDS.length > 0
  );
}

function applyEditStudentRoleRestrictions() {
  const isRestricted = isEditStudentRestrictedMode();

  const departmentSelect = document.getElementById("editDepartment");

  if (isRestricted) {
    // Program Head: Disable department only
    departmentSelect.disabled = true;
    departmentSelect.style.cursor = "not-allowed";
    departmentSelect.style.opacity = "0.7";

    // Program dropdown stays ENABLED but filtered to assigned departments
  } else {
    // Admin: Enable all
    departmentSelect.disabled = false;
  }
}
```

---

### Fix 5: Program Dropdown Filtering for Program Heads

**Problem:** Program dropdown shows all programs regardless of user role.

**Solution:** For Program Heads, filter programs based on their assigned department(s).

```javascript
async function loadEditPrograms(departmentId = "") {
  const url = new URL(
    `../../api/clearance/get_filter_options.php`,
    window.location.href
  );
  url.searchParams.append("type", "programs");
  url.searchParams.append("sector", "College");

  if (isEditStudentRestrictedMode()) {
    // Program Head: Use assigned department IDs
    if (typeof DEPARTMENT_IDS !== "undefined" && DEPARTMENT_IDS.length > 0) {
      url.searchParams.append("department_ids", DEPARTMENT_IDS.join(","));
    }
  } else if (departmentId) {
    // Admin: Use selected department
    url.searchParams.append("department_id", departmentId);
  }

  await populateSelect("editProgram", url, "Select Program", "value", "text");
}
```

---

### Fix 6: Event Dispatching for Parent Page Refresh

**Problem:** After successful update, parent page refresh depends on `loadStudentsData()` function existing.

**Solution:** Dispatch a custom event (pattern from EditFacultyModal).

```javascript
// After successful update
document.dispatchEvent(
  new CustomEvent("student-updated", {
    detail: {
      student_number: studentNumber,
      user_id: window.editStudentCurrentUserId,
    },
  })
);
```

---

### Fix 7: Remove Duplicate Close Button

**Problem:** Modal HTML has two close buttons (lines 11 and 20).

**Solution:** Remove the duplicate close button on line 20.

---

### Fix 8: Global State Management

**Problem:** No tracking of current user ID being edited.

**Solution:** Add global state variables.

```javascript
window.editStudentCurrentUserId = null;
window.editStudentDepartmentsData = [];
```

---

### Fix 9: Year Level Change Handler

**Problem:** Year Level dropdown doesn't trigger section regeneration.

**Solution:** Add event listener.

```javascript
const yearLevelSelect = document.getElementById("editYearLevel");
if (yearLevelSelect) {
  yearLevelSelect.addEventListener("change", updateGeneratedSection);
}
```

---

## API Considerations

### `get_filter_options.php` - Programs Endpoint

The API may need to support a `department_ids` parameter (comma-separated) to filter programs for Program Heads with multiple department assignments.

**Current support:**

- `department_id` - single department filter

**May need:**

- `department_ids` - multiple department filter (comma-separated)

---

## Files Affected

| File                                              | Changes Needed                       |
| ------------------------------------------------- | ------------------------------------ |
| `Modals/CollegeEditStudentModal.php`              | All fixes above                      |
| `api/clearance/get_filter_options.php`            | May need `department_ids` support    |
| `pages/admin/CollegeStudentManagement.php`        | Add `student-updated` event listener |
| `pages/program-head/CollegeStudentManagement.php` | Add `student-updated` event listener |

---

## Testing Checklist

- [ ] Admin can edit all fields including department
- [ ] Admin can change department and see programs update
- [ ] Program Head sees department as disabled/read-only
- [ ] Program Head can still edit program (within assigned departments)
- [ ] Program Head can edit year level, section, email, contact, account status
- [ ] Both roles can reset password
- [ ] Error handling works when API fails
- [ ] Modal closes on error with proper message
- [ ] Form validation works correctly
- [ ] Parent page refreshes after successful update

---

## Changelog

| Date       | Change                                  |
| ---------- | --------------------------------------- |
| 2024-12-26 | Initial documentation of required fixes |
