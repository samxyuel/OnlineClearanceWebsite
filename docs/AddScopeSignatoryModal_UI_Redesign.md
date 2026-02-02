# AddScopeSignatoryModal UI Redesign Documentation

## Overview

This document outlines the UI redesign of `AddScopeSignatoryModal.php` to align the user interface with the actual backend behavior. The modal will be changed from **staff-based selection** to **designation-based selection**.

---

## Background & Problem Statement

### Current UI Behavior

The modal currently displays a list of **individual staff members** with:

- Name (e.g., "Jane Doe")
- Employee Number (e.g., "LCA123P")
- Designation (e.g., "Cashier")

Users select specific staff members, which gives the impression that only those selected individuals can sign clearances.

### Actual Backend Behavior

The backend (`api/clearance/signatory_action.php` lines 220-231) actually authorizes signatories based on **designation only**:

```php
// 2. Verify that this designation is assigned to sign for the applicant's clearance type.
// This allows any user with the correct designation to sign.
$assignmentCheckStmt = $pdo->prepare("
    SELECT COUNT(*) FROM sector_signatory_assignments
    WHERE designation_id = ? AND clearance_type = ? AND is_active = 1
");
```

**Key Finding:** The `user_id` in `sector_signatory_assignments` is essentially **unused for authorization**. Any staff member with the matching `designation_id` can sign clearances, not just the specific user selected in the modal.

### The Discrepancy

- **UI suggests:** Only Jane Doe (the selected Cashier) can sign
- **Backend reality:** ANY staff with designation "Cashier" can sign

### Solution

**Redesign the UI to show only designations**, matching what the backend actually does. No backend changes are needed.

---

## Design Goals

1. ✅ Display **designations only** (not individual staff members)
2. ✅ Keep all existing **element IDs and classes** for API compatibility
3. ✅ Maintain familiar table structure for ease of implementation
4. ✅ Clear visual indication of selected vs assigned designations
5. ✅ Preserve Program Head toggle functionality

---

## UI Comparison: Before vs After

### Current UI (Staff-Based Selection)

```
┌─────────────────────────────────────────────────────────────────┐
│  ✓ Add Scope Signatory                                      ✕   │
├─────────────────────────────────────────────────────────────────┤
│  Search Staff (name or employee number)                         │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  e.g., LCA123P or Jane                                   │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  All Staff (excluding Program Heads)                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ ┌──┬────────────────┬──────────────┬──────────────────┐ │  │
│  │ │☐ │ Name           │ Employee No. │ Designation      │ │  │
│  │ ├──┼────────────────┼──────────────┼──────────────────┤ │  │
│  │ │☐ │ Jane Doe       │ LCA123P      │ Cashier          │ │  │
│  │ │☐ │ John Smith     │ LCA456P      │ Guidance         │ │  │
│  │ │☐ │ Maria Garcia   │ LCA789P      │ Clinic           │ │  │
│  │ │☑ │ Robert Lee     │ LCA321P      │ School Admin     │ │  │
│  │ └──┴────────────────┴──────────────┴──────────────────┘ │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Selected Staff                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  ┌──────────────────────┐                                │  │
│  │  │ Robert Lee • LCA321P │ ✕                              │  │
│  │  └──────────────────────┘                                │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ☐ Include Program Head as a signatory                         │
│  [Clear All]                                                   │
├─────────────────────────────────────────────────────────────────┤
│                                      [Cancel]  [Add]            │
└─────────────────────────────────────────────────────────────────┘
```

### New UI (Designation-Based Selection)

```
┌─────────────────────────────────────────────────────────────────┐
│  ✓ Add Scope Signatory                                      ✕   │
├─────────────────────────────────────────────────────────────────┤
│  Search Designation                                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  e.g., Cashier or Guidance                               │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Search Results                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  (search results appear here when typing)                 │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Selected Designations                                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  ┌────────────────────────┐                               │  │
│  │  │ School Administrator ✕ │                               │  │
│  │  └────────────────────────┘                               │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ☐ Include Program Head as a signatory (dynamic by department)│
│  [Clear All]                                                   │
│                                                                 │
│  All Designations                                               │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ ┌────────────────────────────────────────────────────┐   │  │
│  │ │   ☐          Designation                           │   │  │
│  │ ├────────────────────────────────────────────────────┤   │  │
│  │ │   ☐          Cashier                               │   │  │
│  │ │   ☐          Guidance                              │   │  │
│  │ │   ☐          Clinic                                │   │  │
│  │ │   ☑          School Administrator                  │   │  │
│  │ │   ☐          Vice Principal                        │   │  │
│  │ │   ☐          Registrar                             │   │  │
│  │ │   ☐          Librarian                             │   │  │
│  │ │   ☐          Dean                                  │   │  │
│  │ │   ☐          Property Officer                      │   │  │
│  │ │   ☐          Accounting Staff                      │   │  │
│  │ └────────────────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                      [Cancel]  [Add]            │
└─────────────────────────────────────────────────────────────────┘
```

---

## Detailed UI Specifications

### 1. Modal Header

**Unchanged**

- Title: "Add Scope Signatory"
- Close button (X)

### 2. Search Input

**Label:** "Search Designation"

**Placeholder:** "e.g., Cashier or Guidance"

**Element ID:** `#scopeSearchInput` (unchanged)

**Behavior:**

- Filters designations in real-time
- Shows matching results in Search Results section
- Case-insensitive matching

### 3. Search Results Section

**Element ID:** `#scopeSearchResults` (unchanged)

**Content:**

- Shows designations matching search query
- Each result has a checkbox
- Clicking checkbox adds designation to Selected Designations
- Empty when no search query entered

**Example:**

```
Search Results
┌──────────────────────────────────────┐
│  ☐  Cashier                          │
│  ☐  Accounting Staff                 │
└──────────────────────────────────────┘
```

### 4. Selected Designations Section

**Label:** "Selected Designations"

**Element ID:** `#scopeSelectedChips` (unchanged)

**Content:**

- Shows chips for selected designations
- Each chip has an (×) remove button
- Displays "No designations selected" when empty

**Chip Style:**

- Background: `#eef3f8`
- Border: `1px solid #d7dee7`
- Border-radius: `16px`
- Padding: `6px 12px`
- Font-size: `14px`

**Example:**

```
Selected Designations
┌─────────────────────────────────────────────┐
│  ┌───────────────────────┐  ┌─────────────┐│
│  │ School Administrator ✕│  │ Cashier  ✕  ││
│  └───────────────────────┘  └─────────────┘│
└─────────────────────────────────────────────┘
```

### 5. Program Head Checkbox

**Unchanged**

- Label: "Include Program Head as a signatory (dynamic by department)"
- Element ID: `#includeProgramHeadCheckbox`
- Shows preview of Program Heads when checked

### 6. All Designations Table

**Label:** "All Designations"

**Element ID:** `#scopeAllStaffTableWrap` (container, unchanged)

**Table ID:** `#scopeAllStaffTable` (tbody, unchanged)

#### Table Structure

**Columns:** 2 columns (reduced from 4)

| Column      | Width | Content            |
| ----------- | ----- | ------------------ |
| Checkbox    | 40px  | Selection checkbox |
| Designation | Auto  | Designation name   |

**HTML Structure:**

```html
<table class="scope-all-staff-table">
  <thead>
    <tr>
      <th class="sel-col">&nbsp;</th>
      <th class="desig-col">Designation</th>
    </tr>
  </thead>
  <tbody id="scopeAllStaffTable">
    <tr data-designation-id="5">
      <td>
        <input
          type="checkbox"
          onchange="toggleScopeDesignation(5, 'Cashier')"
        />
      </td>
      <td>Cashier</td>
    </tr>
    <tr data-designation-id="6">
      <td>
        <input
          type="checkbox"
          onchange="toggleScopeDesignation(6, 'Guidance')"
        />
      </td>
      <td>Guidance</td>
    </tr>
    <!-- more rows... -->
  </tbody>
</table>
```

#### Row States

**Unselected:**

- Checkbox: unchecked
- Background: white
- Text: `#2f3a4b`

**Selected:**

- Checkbox: checked
- Background: `#e3f2fd` (light blue)
- Text: `#1976d2` (blue, bold)
- Visual indicator: ✓ icon or highlight

**Already Assigned (Grayed Out):**

- Checkbox: disabled, checked
- Background: `#fafafa` (light gray)
- Text: `#9e9e9e` (gray)
- Additional text: "(Already assigned)" or badge
- Not clickable

### 7. Action Buttons

**Clear All Button:**

- Position: Below Program Head checkbox
- Style: Secondary button
- Action: Clears all selected designations

**Cancel Button:**

- Position: Bottom right (left of Add)
- Style: Secondary button (`.modal-action-secondary`)
- Action: Closes modal without saving

**Add Button:**

- Position: Bottom right
- Style: Primary button (`.modal-action-primary`)
- Action: Submits selected designations

---

## JavaScript Changes Required

### Data Structures

**Old:**

```javascript
window.scopeSelectedIds = new Set(); // Stores user_id
window.scopeSelectedLabels = new Map(); // Stores "Name • EmployeeNo"
window.scopeStaffData = new Map(); // Stores full staff objects
```

**New:**

```javascript
window.scopeSelectedIds = new Set(); // Now stores designation_id
window.scopeSelectedLabels = new Map(); // Now stores "Designation Name"
window.scopeDesignationData = new Map(); // Stores designation objects
```

### Function Modifications

#### 1. `openAddScopeModal(type)`

**Changes:**

- Fetch designations instead of staff
- API call: `GET /api/designations/list.php` (or similar)
- Populate table with designation rows

#### 2. `loadScopeAllStaff()` → `loadScopeAllDesignations()`

**Changes:**

- Query designations API instead of staff API
- Build table rows with only designation_id and designation_name
- Check which designations are already assigned
- Mark already-assigned designations as disabled

#### 3. `runScopeSearch()`

**Changes:**

- Search designation names instead of staff names/employee numbers
- Filter by designation_name field
- Display matching designation rows in search results

#### 4. `toggleScopeUser(userId, label)` → `toggleScopeDesignation(designationId, name)`

**Changes:**

- Parameter: `designationId` instead of `userId`
- Parameter: `name` (designation name) instead of `label`
- Store designation_id in Set
- Store designation name in Map

#### 5. `renderScopeSelectedChips()`

**Changes:**

- Display designation names only
- Chip text: "School Administrator" instead of "Jane Doe • LCA123P"
- Remove button still calls `removeScopeSelected(designationId)`

#### 6. `submitAddScope()`

**Changes:**

- Get designation_ids from `window.scopeSelectedIds`
- For each designation_id, call API:
  ```javascript
  POST /api/signatories/sector_assignments.php
  Body: {
    designation_id: designationId,
    clearance_type: type
    // NO user_id!
  }
  ```
- Handle already-assigned designations gracefully

---

## API Endpoint Modifications

### Current API Behavior

**Endpoint:** `POST /api/signatories/sector_assignments.php`

**Current Request Body:**

```json
{
  "user_id": 123,
  "designation_id": 5,
  "clearance_type": "College"
}
```

### Required Changes

The API currently requires `user_id`. We need to modify it to:

**Option 1: Make `user_id` optional**

```php
// In assignSignatory() function
$requiredFields = ['clearance_type', 'designation_id']; // Remove user_id from required
// Make user_id nullable in INSERT statement
```

**Option 2: Use a placeholder user_id**

- Set `user_id = NULL` in the frontend
- Backend accepts NULL and only stores designation_id
- Authorization already works designation-based

**Recommendation:** Option 1 is cleaner and more accurate.

---

## Element ID/Class Mapping

### Elements to Keep Unchanged (for API compatibility)

| Element               | ID/Class                       | Purpose                             |
| --------------------- | ------------------------------ | ----------------------------------- |
| Modal Container       | `#addScopeModal`               | Modal wrapper                       |
| Scope Type Field      | `#scopeTypeField`              | Hidden field storing clearance type |
| Search Input          | `#scopeSearchInput`            | Search/filter input                 |
| Search Results        | `#scopeSearchResults`          | Dynamic search results container    |
| Selected Chips        | `#scopeSelectedChips`          | Selected items display              |
| Table Wrapper         | `#scopeAllStaffTableWrap`      | Table container                     |
| Table Body            | `#scopeAllStaffTable`          | Table tbody element                 |
| Program Head Checkbox | `#includeProgramHeadCheckbox`  | PH toggle                           |
| Program Head Preview  | `#programHeadPreviewContainer` | PH preview section                  |

### CSS Classes to Keep

| Class                    | Usage                   |
| ------------------------ | ----------------------- |
| `.modal-overlay`         | Modal backdrop          |
| `.modal-window`          | Modal content box       |
| `.modal-header`          | Header section          |
| `.modal-content-area`    | Main content            |
| `.modal-actions`         | Footer buttons          |
| `.form-group`            | Form sections           |
| `.scope-results-list`    | Search results styling  |
| `.scope-selected-chips`  | Chips container         |
| `.scope-all-staff-table` | Table styling           |
| `.chip`                  | Individual chip styling |

---

## Label Changes Summary

| Element            | Old Label                                | New Label                   |
| ------------------ | ---------------------------------------- | --------------------------- |
| Search Input Label | "Search Staff (name or employee number)" | "Search Designation"        |
| Search Placeholder | "e.g., LCA123P or Jane"                  | "e.g., Cashier or Guidance" |
| Selected Section   | "Selected Staff"                         | "Selected Designations"     |
| Table Section      | "All Staff (excluding Program Heads)"    | "All Designations"          |
| Table Header Col 2 | "Name"                                   | (removed)                   |
| Table Header Col 3 | "Employee No."                           | (removed)                   |
| Table Header Col 4 | "Designation"                            | "Designation"               |

---

## User Workflow Example

### Scenario: Admin wants to add "School Administrator" as signatory

1. **Admin opens modal**

   - Sees currently assigned: Cashier, Guidance, Clinic, Registrar, Librarian
   - Sees available designations in table

2. **Admin searches or scrolls**

   - Option A: Types "school" in search → "School Administrator" appears in results
   - Option B: Scrolls table to find "School Administrator"

3. **Admin selects designation**

   - Checks the checkbox next to "School Administrator"
   - "School Administrator" chip appears in Selected Designations section
   - Table row for "School Administrator" highlights in blue

4. **Admin clicks Add**

   - API saves designation_id (not user_id)
   - Modal closes
   - Success toast: "Added 1 signatory"
   - Signatory list refreshes

5. **Result when clearance starts**
   - Any staff member with designation = "School Administrator" can sign
   - Example: John Doe (School Administrator) signs for some students
   - Example: Maria Lee (School Administrator) signs for other students
   - Both appear as: "School Administrator: John Doe" and "School Administrator: Maria Lee"

---

## Benefits of This Redesign

### 1. **UI Matches Backend Reality**

- No more confusion between what UI shows vs what actually happens
- Users understand that ANY staff with the designation can sign

### 2. **Simpler User Experience**

- Fewer columns to read (2 vs 4)
- No need to pick specific individuals
- Faster designation assignment

### 3. **More Accurate Representation**

- If Jane Doe (Cashier) leaves and John Smith becomes the new Cashier, he automatically inherits signatory permissions
- No need to "update" signatories when staff changes roles

### 4. **Easier Maintenance**

- Admin doesn't need to track which individual staff members are assigned
- Only need to manage which designations are signatories

### 5. **No Backend Changes Needed**

- Backend already works this way
- Only UI needs updating
- Minimal risk of breaking existing functionality

---

## Implementation Checklist

### Phase 1: Modal HTML Structure

- [ ] Update label: "Search Staff" → "Search Designation"
- [ ] Update placeholder: "e.g., LCA123P or Jane" → "e.g., Cashier or Guidance"
- [ ] Update label: "Selected Staff" → "Selected Designations"
- [ ] Update label: "All Staff" → "All Designations"
- [ ] Remove table columns: Name, Employee No.
- [ ] Keep only: Checkbox, Designation columns

### Phase 2: JavaScript Functions

- [ ] Rename `scopeStaffData` → `scopeDesignationData`
- [ ] Modify `openAddScopeModal()` to fetch designations
- [ ] Rename `loadScopeAllStaff()` → `loadScopeAllDesignations()`
- [ ] Update designation fetching logic
- [ ] Modify `runScopeSearch()` to search designations only
- [ ] Rename `toggleScopeUser()` → `toggleScopeDesignation()`
- [ ] Update `renderScopeSelectedChips()` to show designation names
- [ ] Modify `submitAddScope()` to send designation_id only

### Phase 3: API Modifications

- [ ] Modify `sector_assignments.php` to make `user_id` optional
- [ ] Update `assignSignatory()` function
- [ ] Test designation-only assignment
- [ ] Verify authorization still works

### Phase 4: Testing

- [ ] Test opening modal
- [ ] Test searching designations
- [ ] Test selecting designations
- [ ] Test chip display
- [ ] Test submitting designations
- [ ] Test Program Head toggle
- [ ] Test already-assigned designation handling
- [ ] Test clearance signing with new designations

### Phase 5: Documentation

- [ ] Update user manual
- [ ] Update admin guide
- [ ] Document designation management workflow

---

## Testing Scenarios

### Test Case 1: Add Single Designation

1. Open modal for College clearance
2. Search "Cashier"
3. Select Cashier
4. Click Add
5. Verify Cashier appears in signatory list

### Test Case 2: Add Multiple Designations

1. Open modal
2. Select School Administrator, Vice Principal, Dean
3. Verify all 3 appear in chips
4. Click Add
5. Verify all 3 are added

### Test Case 3: Search Functionality

1. Type "cas" → should show "Cashier"
2. Type "admin" → should show "School Administrator"
3. Clear search → should show all designations

### Test Case 4: Already Assigned Designation

1. Open modal
2. See "Cashier" is already assigned (grayed out)
3. Try to select it → should be disabled
4. Verify cannot add duplicate

### Test Case 5: Program Head Toggle

1. Check "Include Program Head"
2. Verify preview shows Program Heads
3. Submit
4. Verify Program Head is added dynamically

### Test Case 6: Clearance Signing Authorization

1. Add "Clinic" designation as signatory
2. Start clearance period
3. Log in as Staff A (Clinic designation)
4. Verify Staff A can sign
5. Log in as Staff B (Clinic designation)
6. Verify Staff B can also sign
7. Verify both names appear on clearance form

---

## Migration Notes

### Data Migration

**Not Required** - The database already stores `designation_id` in `sector_signatory_assignments`. The `user_id` column can remain but will be set to NULL for new assignments.

### Existing Assignments

Existing signatory assignments (with user_id) will continue to work because authorization is designation-based.

### User Communication

Inform admins that:

- Signatory assignment is now designation-based
- Any staff with the assigned designation can sign clearances
- No action needed for existing assignments

---

## Future Enhancements

### 1. Show Current Staff in Preview

When hovering over a designation, show which staff currently have that designation:

```
┌────────────────────────────┐
│ Cashier                    │
│ ────────────────────────   │
│ Current staff:             │
│ • Jane Doe (LCA123P)       │
│ • John Smith (LCA456P)     │
└────────────────────────────┘
```

### 2. Designation Grouping

Group designations by category:

- Administrative (School Admin, Vice Principal)
- Support Services (Cashier, Registrar, Librarian)
- Student Services (Guidance, Clinic)

### 3. Bulk Actions

- "Add All Support Services" button
- "Clear All Selections" button

### 4. Assignment History

- Show when designation was added as signatory
- Show who added it

---

## Questions & Answers

### Q: What happens to existing signatory assignments that have user_id?

**A:** They continue to work. Authorization is designation-based, so the user_id is ignored.

### Q: Can we still see which staff members can sign?

**A:** Yes, the main signatory list should show current staff with each designation. The modal is just for adding/removing designation roles.

### Q: What if there's no staff with an assigned designation?

**A:** The system should show a warning when assigning a designation with no active staff. This can be added as a validation.

### Q: Does this affect Program Head assignment?

**A:** No, Program Head toggle works the same way - it's already designation-based.

---

## Related Documentation

- [Signatory Name Preservation Implementation Plan](./Signatory_Name_Preservation_Implementation_Plan.md)
- [Cross Sector Department Management Plan](./Cross_Sector_Department_Management_Plan.md)
- [Faculty Department Column Implementation Plan](./FacultyDepartmentColumn_Implementation_Plan.md)

---

## Approval & Sign-off

- [ ] UI/UX Review
- [ ] Technical Review
- [ ] Security Review
- [ ] Admin User Acceptance
- [ ] Implementation Approved

---

**Document Version:** 1.0  
**Created:** January 15, 2026  
**Last Updated:** January 15, 2026  
**Status:** Draft - Awaiting Approval
