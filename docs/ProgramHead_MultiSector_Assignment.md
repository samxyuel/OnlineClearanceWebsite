# Program Head Multi-Sector Assignment Feature

## Overview

This document outlines the implementation plan for enabling Program Heads to be assigned to multiple sectors (Student and Faculty) simultaneously, with one department per sector.

---

## Requirements

### Business Rules

1. **Sector Separation**: Each sector (College, Senior High School, Faculty) has separate, independent department lists

   - Example: College has "BAS" (Business, Arts, & Science)
   - Example: Senior High School has "Academic Track"
   - Example: Faculty has "GE" (General Education)
   - **Departments are NOT shared across sectors**

2. **Assignment Constraints**:

   - **Minimum**: 1 sector + 1 department (can be student OR faculty)
   - **Maximum**: 1 student sector + 1 department + 1 faculty sector + 1 department
   - **Student sectors are mutually exclusive**: Cannot assign to both College AND Senior High School
   - **Faculty is separate**: Can be assigned independently

3. **Valid Assignment Combinations**:
   - ✅ Student only: College/BAS OR Senior High School/Academic Track
   - ✅ Faculty only: Faculty/GE (no student assignment required)
   - ✅ Both: (College/BAS) + (Faculty/GE) OR (SHS/Academic Track) + (Faculty/GE)

### Use Cases

1. **LCA1234P** is Program Head of:

   - **College** → BAS (Business, Arts, & Science)
   - **Faculty** → BAS-F (Business, Arts, & Science - Faculty)
   - **Result**: Can manage both College students and Faculty clearance forms

2. **LCA5678P** is Program Head of:

   - **Faculty** → GE (General Education only)
   - **Result**: Can manage only Faculty clearance forms

3. **LCA9012P** is Program Head of:
   - **Senior High School** → Academic Track
   - **Result**: Can manage only Senior High School clearance forms

---

## Database Schema Changes

### Current Table: `sector_signatory_assignments`

#### Current Constraints:

```sql
-- Current unique constraint prevents multiple assignments
ADD UNIQUE KEY `uq_sa_scope` (`user_id`, `clearance_type`, `designation_id`)
```

#### Problem:

- This constraint prevents the same user from having multiple Program Head assignments across different sectors with different departments
- The constraint only considers `user_id`, `clearance_type`, and `designation_id`, but not `department_id`

#### Required Changes:

1. **Modify Unique Constraint**:

```sql
-- Remove old constraint
ALTER TABLE `sector_signatory_assignments`
DROP INDEX `uq_sa_scope`;

-- Add new constraint that includes department_id
ALTER TABLE `sector_signatory_assignments`
ADD UNIQUE KEY `uq_sa_scope_dept`
(`user_id`, `clearance_type`, `designation_id`, `department_id`);
```

2. **Ensure Existing Constraint Remains**:

```sql
-- Keep this constraint to prevent multiple PHs per department
ADD UNIQUE KEY `uq_sa_ph` (`department_id`, `designation_id`)
```

#### Expected Table State:

| assignment_id | clearance_type | user_id | designation_id | department_id | is_program_head |
| ------------- | -------------- | ------- | -------------- | ------------- | --------------- |
| 1             | College        | 100     | 8 (PH)         | 45 (BAS)      | 1               |
| 2             | Faculty        | 100     | 8 (PH)         | 47 (BAS-F)    | 1               |

✅ **Same user_id (100), same designation_id (8), but different clearance_type + department_id** = **ALLOWED**

---

## UI/UX Structure

### Visual Layout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Program Head Assignment Section                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │  🎓 Student Sector Assignment *                                       │ │
│  │  Select ONE student sector and ONE department (required)              │ │
│  ├───────────────────────────────────────────────────────────────────────┤ │
│  │                                                                       │ │
│  │  Student Sector: [Dropdown ▼]                                         │ │
│  │  ┌─────────────────────────────────────────────────────────────┐     │ │
│  │  │ Select Student Sector                                        │     │ │
│  │  │ ─────────────────────────────────────────────────────────── │     │ │
│  │  │ College                                                      │     │ │
│  │  │ Senior High School                                           │     │ │
│  │  └─────────────────────────────────────────────────────────────┘     │ │
│  │                                                                       │ │
│  │  ┌─ Selected Assignment Display (when assigned) ───────────────────┐ │ │
│  │  │  [College → Business, Arts, & Science]  [× Remove]              │ │ │
│  │  └──────────────────────────────────────────────────────────────────┘ │ │
│  │                                                                       │ │
│  │  ┌─ Department Selection (when sector selected) ───────────────────┐ │ │
│  │  │  Select Department *                                             │ │ │
│  │  │  ┌──────────────────────────────────────────────────────────┐  │ │ │
│  │  │  │ ○ Business, Arts, & Science                                │  │ │ │
│  │  │  │   (Assigned to Dr. Cruz)                                   │  │ │ │
│  │  │  │                                                             │  │ │ │
│  │  │  │ ○ Information & Communication Technology                   │  │ │ │
│  │  │  │                                                             │  │ │ │
│  │  │  │ ○ Tourism & Hospitality Management                          │  │ │ │
│  │  │  │   (Assigned to Prof. Reyes)                                │  │ │ │
│  │  │  └──────────────────────────────────────────────────────────┘  │ │ │
│  │  └──────────────────────────────────────────────────────────────────┘ │ │
│  │                                                                       │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │  👨‍🏫 Faculty Sector Assignment [Optional]                              │ │
│  │  Select ONE faculty department (optional)                             │ │
│  ├───────────────────────────────────────────────────────────────────────┤ │
│  │                                                                       │ │
│  │  Faculty Sector: [Dropdown ▼]                                        │ │
│  │  ┌─────────────────────────────────────────────────────────────┐     │ │
│  │  │ Select Faculty Sector                                        │     │ │
│  │  │ ─────────────────────────────────────────────────────────── │     │ │
│  │  │ Faculty                                                      │     │ │
│  │  └─────────────────────────────────────────────────────────────┘     │ │
│  │                                                                       │ │
│  │  ┌─ Selected Assignment Display (when assigned) ───────────────────┐ │ │
│  │  │  [Faculty → Business, Arts, & Science - Faculty]  [× Remove]   │ │ │
│  │  └──────────────────────────────────────────────────────────────────┘ │ │
│  │                                                                       │ │
│  │  ┌─ Department Selection (when sector selected) ───────────────────┐ │ │
│  │  │  Select Department *                                             │ │ │
│  │  │  ┌──────────────────────────────────────────────────────────┐  │ │ │
│  │  │  │ ○ Business, Arts, & Science - Faculty                      │  │ │ │
│  │  │  │                                                             │  │ │ │
│  │  │  │ ○ ICT - Faculty                                            │  │ │ │
│  │  │  │   (Assigned to Dr. Santos)                                 │  │ │ │
│  │  │  │                                                             │  │ │ │
│  │  │  │ ○ Tourism & Hospitality - Faculty                           │  │ │ │
│  │  │  └──────────────────────────────────────────────────────────┘  │ │ │
│  │  └──────────────────────────────────────────────────────────────────┘ │ │
│  │                                                                       │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  ☑ Transfer existing Program Head if a department is already assigned     │
│    When checked, assigning will replace the current Program Head for       │
│    occupied departments.                                                   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### HTML Structure

```html
<!-- Program Head Assignment Section -->
<div
  id="programHeadAssignmentSection"
  class="program-head-assignment-section"
  style="display: none;"
>
  <!-- Student Sector Assignment (College OR Senior High School) -->
  <div class="sector-assignment-group" id="studentSectorGroup">
    <h4 class="sector-group-title">
      <i class="fas fa-graduation-cap"></i> Student Sector Assignment
      <span class="required-asterisk">*</span>
    </h4>
    <small class="form-help"
      >Select ONE student sector and ONE department (required)</small
    >

    <div class="form-group">
      <label for="studentSectorSelect">Student Sector</label>
      <select
        id="studentSectorSelect"
        name="studentSector"
        onchange="updateStudentDepartmentCheckboxes()"
      >
        <option value="">Select Student Sector</option>
        <option value="College">College</option>
        <option value="Senior High School">Senior High School</option>
      </select>
    </div>

    <!-- Selected Assignment Display -->
    <div
      id="studentAssignmentDisplay"
      class="selected-assignment-display"
      style="display: none;"
    >
      <div class="assignment-chip">
        <span class="assignment-text">
          <strong id="studentSectorDisplay"></strong> →
          <span id="studentDeptDisplay"></span>
        </span>
        <button
          type="button"
          class="remove-assignment-btn"
          onclick="removeStudentAssignment()"
        >
          ×
        </button>
      </div>
    </div>

    <!-- Department Selection (Radio buttons for single select) -->
    <div
      id="studentDepartmentContainer"
      class="department-checkboxes-container"
      style="display: none;"
    >
      <label class="checkbox-section-label"
        >Select Department <span class="required-asterisk">*</span></label
      >
      <div id="studentDepartmentList" class="checkbox-group">
        <!-- Radio buttons populated dynamically -->
      </div>
    </div>
  </div>

  <!-- Faculty Sector Assignment (Optional) -->
  <div class="sector-assignment-group" id="facultySectorGroup">
    <h4 class="sector-group-title">
      <i class="fas fa-chalkboard-teacher"></i> Faculty Sector Assignment
      <span class="optional-badge">Optional</span>
    </h4>
    <small class="form-help">Select ONE faculty department (optional)</small>

    <div class="form-group">
      <label for="facultySectorSelect">Faculty Sector</label>
      <select
        id="facultySectorSelect"
        name="facultySector"
        onchange="updateFacultyDepartmentCheckboxes()"
      >
        <option value="">Select Faculty Sector</option>
        <option value="Faculty">Faculty</option>
      </select>
    </div>

    <!-- Selected Assignment Display -->
    <div
      id="facultyAssignmentDisplay"
      class="selected-assignment-display"
      style="display: none;"
    >
      <div class="assignment-chip">
        <span class="assignment-text">
          <strong>Faculty</strong> →
          <span id="facultyDeptDisplay"></span>
        </span>
        <button
          type="button"
          class="remove-assignment-btn"
          onclick="removeFacultyAssignment()"
        >
          ×
        </button>
      </div>
    </div>

    <!-- Department Selection (Radio buttons for single select) -->
    <div
      id="facultyDepartmentContainer"
      class="department-checkboxes-container"
      style="display: none;"
    >
      <label class="checkbox-section-label"
        >Select Department <span class="required-asterisk">*</span></label
      >
      <div id="facultyDepartmentList" class="checkbox-group">
        <!-- Radio buttons populated dynamically -->
      </div>
    </div>
  </div>

  <!-- Transfer Toggle (applies to both) -->
  <div
    class="form-group"
    style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;"
  >
    <label
      class="checkbox-label"
      style="display:flex; align-items:center; gap:8px;"
    >
      <input type="checkbox" id="phTransferToggle" checked />
      <span
        >Transfer existing Program Head if a department is already
        assigned</span
      >
    </label>
    <small class="form-help"
      >When checked, assigning will replace the current Program Head for
      occupied departments.</small
    >
  </div>
</div>
```

### Key UI Components

1. **Two Independent Sections**:

   - Student Sector Group (required indicator)
   - Faculty Sector Group (optional badge)

2. **Single Select Dropdowns**:

   - Student: College OR Senior High School (mutually exclusive)
   - Faculty: Faculty (single option)

3. **Radio Buttons for Departments**:

   - Ensures exactly ONE department per sector
   - Shows "(Assigned to...)" for occupied departments

4. **Assignment Chips**:
   - Display selected assignments as chips
   - Allow removal without clearing entire section
   - Format: `[Sector → Department] [×]`

---

## Validation Rules

### JavaScript Validation Logic

```javascript
function validateProgramHeadAssignments() {
  const studentAssignment = getStudentAssignment(); // { sector: "College"|"SHS"|null, dept: id|null }
  const facultyAssignment = getFacultyAssignment(); // { sector: "Faculty"|null, dept: id|null }

  // Minimum: At least ONE complete assignment
  const hasStudent = studentAssignment.sector && studentAssignment.dept;
  const hasFaculty = facultyAssignment.sector && facultyAssignment.dept;

  if (!hasStudent && !hasFaculty) {
    return {
      valid: false,
      message:
        "Please select at least one sector and department (student or faculty)",
    };
  }

  // If student sector selected, must have department
  if (studentAssignment.sector && !studentAssignment.dept) {
    return {
      valid: false,
      message: "Please select a department for the student sector",
    };
  }

  // If faculty sector selected, must have department
  if (facultyAssignment.sector && !facultyAssignment.dept) {
    return {
      valid: false,
      message: "Please select a department for the faculty sector",
    };
  }

  // Maximum validation (enforced by UI - radio buttons)
  // Can have both: 1 student + 1 faculty (each with 1 department)

  return { valid: true };
}
```

### Data Structure

```javascript
// State management
window.programHeadAssignments = {
  student: {
    sector: null, // "College" | "Senior High School" | null
    department_id: null,
    department_name: null,
  },
  faculty: {
    sector: "Faculty", // Always "Faculty" when selected
    department_id: null,
    department_name: null,
  },
};

// Submission payload
const submissionData = {
  studentAssignment: {
    clearance_type: "College" | "Senior High School" | null,
    department_id: number | null,
  },
  facultyAssignment: {
    clearance_type: "Faculty" | null,
    department_id: number | null,
  },
  transfer: boolean,
};
```

---

## API Changes

### Current API: `api/signatories/sector_assignments.php`

#### Issue in `assignSignatory()` function:

```php
// Current check (line 119-122)
$checkSql = "
    SELECT assignment_id, is_active FROM sector_signatory_assignments
    WHERE clearance_type = ? AND user_id = ? AND designation_id = ?
";
```

**Problem**: Doesn't check `department_id`, so it prevents multiple sector assignments.

#### Required Fix:

```php
function assignSignatory($pdo, $data) {
    // ... existing validation ...

    // Updated check to include department_id
    $checkSql = "
        SELECT assignment_id, is_active
        FROM sector_signatory_assignments
        WHERE clearance_type = ?
        AND user_id = ?
        AND designation_id = ?
        AND department_id = ?
    ";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([
        $data['clearance_type'],
        $data['user_id'],
        $data['designation_id'],
        $data['department_id'] ?? null  // Include department_id
    ]);

    // ... rest of the function ...
}
```

### Bulk Assignment API: `api/signatories/bulk_assign.php`

#### Required Changes:

1. **Handle Multiple Sector Assignments**:

   - Accept array of assignments with different `clearance_type` values
   - Process each assignment independently
   - Validate that same user can have multiple sectors

2. **Submission Format**:

```php
POST /api/signatories/bulk_assign.php
{
    "assignments": [
        {
            "user_id": 100,
            "designation": "Program Head",
            "clearance_type": "College",
            "department_id": 45,
            "transfer": true
        },
        {
            "user_id": 100,
            "designation": "Program Head",
            "clearance_type": "Faculty",
            "department_id": 47,
            "transfer": true
        }
    ]
}
```

---

## Implementation Checklist

### Phase 1: Database Changes

- [ ] Create migration script to modify unique constraint
- [ ] Test constraint allows multiple sectors per user
- [ ] Verify existing constraint prevents multiple PHs per department
- [ ] Backup database before changes

### Phase 2: Backend API Updates

- [ ] Update `assignSignatory()` to check `department_id`
- [ ] Update `bulk_assign.php` to handle multi-sector assignments
- [ ] Update assignment retrieval APIs to return all sectors
- [ ] Add validation for maximum assignments (1 student + 1 faculty)

### Phase 3: Frontend UI Changes

- [ ] Refactor `StaffRegistryModal.php` with new structure
- [ ] Refactor `EditStaffModal.php` with new structure
- [ ] Create separate sections for Student and Faculty
- [ ] Implement assignment chip displays
- [ ] Update `updateDepartmentCheckboxes()` functions
- [ ] Add state management for both assignments
- [ ] Implement validation logic

### Phase 4: JavaScript Functions

- [ ] `updateStudentDepartmentCheckboxes()` - Load student depts
- [ ] `updateFacultyDepartmentCheckboxes()` - Load faculty depts
- [ ] `removeStudentAssignment()` - Clear student selection
- [ ] `removeFacultyAssignment()` - Clear faculty selection
- [ ] `validateProgramHeadAssignments()` - Validation
- [ ] `getStudentAssignment()` - Get current student assignment
- [ ] `getFacultyAssignment()` - Get current faculty assignment
- [ ] Update form submission to handle both assignments

### Phase 5: Testing

- [ ] Test faculty-only assignment
- [ ] Test student-only assignment (College)
- [ ] Test student-only assignment (SHS)
- [ ] Test both student + faculty assignment
- [ ] Test assignment removal
- [ ] Test validation messages
- [ ] Test edit mode with existing multi-sector assignments
- [ ] Test transfer toggle behavior

---

## Edge Cases & Considerations

### 1. Editing Existing Single-Sector Assignment

- **Scenario**: Program Head currently assigned only to College/BAS
- **Action**: Admin wants to add Faculty/GE assignment
- **Solution**: UI should show existing assignment, allow adding faculty without removing student

### 2. Removing One Assignment

- **Scenario**: Program Head has both College/BAS and Faculty/GE
- **Action**: Admin removes faculty assignment
- **Solution**: Only faculty assignment removed, student remains

### 3. Department Already Assigned

- **Scenario**: Trying to assign to a department that already has a Program Head
- **Action**: Show "(Assigned to...)" message, disable radio button
- **Solution**: If transfer toggle is ON, allow assignment and transfer existing PH

### 4. Program Head Dashboard Access

- **Scenario**: Program Head assigned to both College and Faculty
- **Solution**: Dashboard should show both management pages:
  - College Student Management (if College assigned)
  - SHS Student Management (if SHS assigned)
  - Faculty Management (if Faculty assigned)

### 5. Clearance Form Generation

- **Scenario**: Clearance forms for students in College/BAS department
- **Action**: System should find Program Head assigned to College + department_id=45
- **Solution**: Query `sector_signatory_assignments` with matching `clearance_type` and `department_id`

---

## Migration Script

```sql
-- Migration: Update sector_signatory_assignments constraint
-- Date: [CURRENT_DATE]
-- Purpose: Allow Program Heads to be assigned to multiple sectors

-- Step 1: Backup check (verify current constraint exists)
SELECT CONSTRAINT_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'sector_signatory_assignments'
AND CONSTRAINT_NAME = 'uq_sa_scope';

-- Step 2: Remove old constraint
ALTER TABLE `sector_signatory_assignments`
DROP INDEX `uq_sa_scope`;

-- Step 3: Add new constraint that includes department_id
ALTER TABLE `sector_signatory_assignments`
ADD UNIQUE KEY `uq_sa_scope_dept`
(`user_id`, `clearance_type`, `designation_id`, `department_id`);

-- Step 4: Verify new constraint
SELECT CONSTRAINT_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'sector_signatory_assignments'
AND CONSTRAINT_NAME = 'uq_sa_scope_dept';

-- Step 5: Test constraint allows multiple sectors
-- This should work:
-- INSERT INTO sector_signatory_assignments (clearance_type, user_id, designation_id, department_id, is_program_head)
-- VALUES ('College', 100, 8, 45, 1);
-- INSERT INTO sector_signatory_assignments (clearance_type, user_id, designation_id, department_id, is_program_head)
-- VALUES ('Faculty', 100, 8, 47, 1);
```

---

## Related Files

### Frontend Modals

- `Modals/StaffRegistryModal.php` - Registration form
- `Modals/EditStaffModal.php` - Edit form

### Backend APIs

- `api/signatories/sector_assignments.php` - Assignment CRUD
- `api/signatories/bulk_assign.php` - Bulk assignments
- `api/signatories/assign.php` - Legacy assignment (if used)
- `api/departments/list.php` - Department listing

### Database

- `database/updated_online_clearance_db.sql` - Schema file
- `database/Nov14updated_full_schema_summary.txt` - Schema summary

### Controllers

- `controllers/addStaff.php` - Staff registration handler
- `controllers/updateStaff.php` - Staff update handler

---

## Notes

1. **Backward Compatibility**: Existing single-sector assignments should continue to work
2. **UI Consistency**: Both registration and edit modals should use the same structure
3. **User Experience**: Selected assignments should be clearly visible and removable
4. **Data Integrity**: Ensure database constraints prevent invalid states
5. **Performance**: Consider caching department lists to reduce API calls

---

## Revision History

| Date   | Version | Changes                   | Author |
| ------ | ------- | ------------------------- | ------ |
| [DATE] | 1.0     | Initial document creation | System |

---

**End of Document**
