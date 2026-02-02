# EditFacultyModal.php - Implementation Summary

**File:** `Modals/EditFacultyModal.php`  
**Purpose:** Modal for editing existing faculty information with role-based access control

---

## Overview

The EditFacultyModal provides a unified interface for both **Admin** and **Program Head** users to edit faculty information, with different permission levels based on the user role.

---

## Key Features Implemented

### 1. Role-Based Access Control

**Detection Function (Lines 122-131):**

```javascript
function isEditRestrictedMode() {
  return (
    typeof DEPARTMENT_IDS !== "undefined" &&
    Array.isArray(DEPARTMENT_IDS) &&
    DEPARTMENT_IDS.length > 0
  );
}
```

| Feature                                  | Admin | Program Head   |
| ---------------------------------------- | ----- | -------------- |
| Edit Basic Info (email, contact, status) | ✅    | ✅             |
| Change Primary Department                | ✅    | ❌ (View Only) |
| Add/Remove Additional Departments        | ✅    | ❌ (View Only) |
| Reset Password                           | ✅    | ✅             |

---

### 2. Department Management Section (Lines 56-90)

**HTML Structure:**

```html
<div id="editDepartmentManagementSection">
  <!-- Primary Department Dropdown -->
  <select id="editPrimaryDepartment" name="primaryDepartment" required>
    <option value="">Loading Departments...</option>
  </select>

  <!-- Additional Departments Section -->
  <div id="editAdditionalDepartmentsSection">
    <select id="editAdditionalDepartmentSelect">
      ...
    </select>
    <button id="editAddDeptBtn">Add</button>
    <div id="editDepartmentsList"><!-- Chips appear here --></div>
  </div>
</div>
```

---

### 3. Cross-Sector Department Support (Lines 201-372)

**Function:** `populateEditPrimaryDepartmentDropdown(currentDepartmentId)`

**API Endpoint:** `../../api/departments/list.php?sector=Faculty&limit=500`

**Admin Mode Features:**

- Departments grouped into `Cross-Sector Departments` and `Faculty-Only Departments`
- Shows "[Shared with: College, SHS]" indicators for cross-sector departments
- Full dropdown functionality enabled

**Program Head Mode Features:**

- Dropdown disabled (grayed out, cursor: not-allowed)
- Shows lock icon with message: "Department assignments are managed by administrators"
- Add button hidden
- Chips displayed without remove buttons

---

### 4. Faculty Data Loading (Lines 383-445)

**Function:** `populateEditFacultyForm(employeeNumber)`

**API Endpoint:** `../../api/users/facultyList.php?search={employeeNumber}&limit=1`

**Fields Populated:**
| Field ID | Source | Editable |
|----------|--------|----------|
| `editFacultyId` | employee_number | No (hidden) |
| `editEmployeeNumber` | employee_number | No (readonly) |
| `editEmploymentStatus` | employment_status | Yes |
| `editLastName` | last_name | No (readonly) |
| `editFirstName` | first_name | No (readonly) |
| `editMiddleName` | middle_name | No (readonly) |
| `editEmail` | email | Yes |
| `editContactNumber` | contact_number | Yes |
| `editAccountStatus` | account_status | Yes |

---

### 5. Department Assignments Loading (Lines 945-995)

**Function:** `fetchEditDepartmentAssignments(userId)`

**API Endpoint:** `../../api/faculty/department_assignments.php?user_id={userId}`

**Workflow:**

1. Fetches department assignments from API
2. Identifies primary department (via `data.primary_department_id` or first department)
3. Populates primary department dropdown with correct selection
4. Populates additional departments array (excluding primary)
5. Renders department chips

---

### 6. Additional Departments Management (Lines 811-883)

**Global State:**

```javascript
window.editAdditionalDepartments = []; // Array of {department_id, department_name}
```

**Functions:**
| Function | Purpose |
|----------|---------|
| `addEditAdditionalDepartment()` | Add department to list (prevents duplicates, primary conflicts) |
| `removeEditAdditionalDepartment(name)` | Remove department from list |
| `renderEditAdditionalDepartments()` | Render chips (with/without remove buttons based on role) |

**Chip Rendering (Admin vs Program Head):**

```javascript
// Program Head: No remove button
<span class="chip">Department Name</span>

// Admin: With remove button
<span class="chip">
  Department Name
  <button onclick="removeEditAdditionalDepartment('...')">×</button>
</span>
```

---

### 7. Form Submission (Lines 717-796)

**Function:** `submitEditFacultyForm()`

**API Endpoint:** `../../api/users/update_faculty.php` (POST)

**Payload Structure:**

```javascript
// Common fields (all roles)
{
  employee_number: "...",
  employment_status: "Full Time|Part Time|Part Time - Full Load",
  email: "...",
  contact_number: "...",
  account_status: "active|inactive|resigned"
}

// Admin only (additional fields)
{
  department_id: 123,  // Primary department
  assignedDepartments: [124, 125]  // Additional departments
}
```

**Post-Submit Actions:**

1. Shows success toast notification
2. Closes modal
3. Dispatches `faculty-updated` custom event for parent page refresh

---

### 8. Password Reset (Lines 449-497)

**Function:** `resetFacultyPassword()`

**API Endpoint:** `../../api/users/password.php` (PUT)

**Workflow:**

1. Shows confirmation modal
2. Generates secure password client-side
3. Sends password to API for hashing and storage
4. Opens `GeneratedCredentialsModal` to display new password

---

### 9. Form Validation (Lines 133-199)

**Function:** `validateEditFacultyForm()`

**Required Fields:**

- `editEmploymentStatus`
- `editEmail` (with email format validation)
- `editContactNumber`
- `editAccountStatus`
- `editPrimaryDepartment` (Admin only)

**Visual Feedback:**

- Red border + error message for invalid fields
- Green border for valid fields

---

## Global Functions Exposed

| Function                                      | Purpose                            |
| --------------------------------------------- | ---------------------------------- |
| `window.openEditFacultyModal(facultyId)`      | Opens modal and loads faculty data |
| `window.closeEditFacultyModal()`              | Closes modal and resets state      |
| `window.submitEditFacultyForm()`              | Validates and submits form         |
| `window.addEditAdditionalDepartment()`        | Adds additional department         |
| `window.removeEditAdditionalDepartment(name)` | Removes additional department      |
| `window.sendPasswordEmail()`                  | Sends password reset email         |

---

## Global State Variables

| Variable                                | Type   | Purpose                           |
| --------------------------------------- | ------ | --------------------------------- |
| `window.editFacultyDepartmentsData`     | Array  | Cached departments from API       |
| `window.editCurrentPrimaryDepartmentId` | Number | Current primary department ID     |
| `window.editAdditionalDepartments`      | Array  | Additional department assignments |

---

## Event Dispatched

```javascript
document.dispatchEvent(
  new CustomEvent("faculty-updated", {
    detail: { employee_number: "..." },
  })
);
```

Parent pages should listen for this event to refresh their faculty tables:

```javascript
document.addEventListener("faculty-updated", function (e) {
  loadFacultyData(); // Refresh table
});
```

---

## Employment Status Values

The dropdown values match the database ENUM exactly:

| Display Value         | Database ENUM           |
| --------------------- | ----------------------- |
| Full Time             | `Full Time`             |
| Part Time             | `Part Time`             |
| Part Time - Full Load | `Part Time - Full Load` |

---

## Dependencies

- `../../assets/css/modals.css` - Modal styling
- `window.openModal()` / `window.closeModal()` - Modal management functions
- `showToastNotification()` / `showToast()` - Toast notifications
- `showConfirmationModal()` - Confirmation dialogs
- `openGeneratedCredentialsModal()` - Password display modal
- `escapeHtml()` - XSS prevention (expected from parent page)

---

## Files That Use This Modal

| File                                       | How It's Included                                       |
| ------------------------------------------ | ------------------------------------------------------- |
| `pages/admin/FacultyManagement.php`        | `<?php include '../../Modals/EditFacultyModal.php'; ?>` |
| `pages/program-head/FacultyManagement.php` | `<?php include '../../Modals/EditFacultyModal.php'; ?>` |

---

## Changelog Summary

1. **Role-Based UI** - Added detection for Program Head vs Admin mode
2. **Cross-Sector Departments** - Departments grouped and labeled by sector sharing
3. **View-Only Mode** - Program Heads can see but not modify department assignments
4. **Self-Loading** - Modal fetches its own data via `populateEditFacultyForm()` and `fetchEditDepartmentAssignments()`
5. **Employment Status Fix** - Dropdown values now match database ENUM exactly
6. **Event-Driven Updates** - Dispatches `faculty-updated` event for parent page refresh
