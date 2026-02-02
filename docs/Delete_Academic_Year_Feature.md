# Delete Academic Year Feature - Implementation Guide

## Overview

This document outlines the implementation of the "Delete Academic Year" feature in the `ViewPastClearancesModal.php`. The feature allows administrators to delete entire academic years from the clearance history, with automatic cascade deletion of all associated data.

**Key Design Decision:** Only academic year-level deletion is available. Individual period or semester deletion is not supported to maintain simplicity and prevent partial data deletion.

---

## Table of Contents

1. [User Experience (UX) Design](#user-experience-ux-design)
2. [User Interface (UI) Design](#user-interface-ui-design)
3. [Database Adjustments](#database-adjustments)
4. [Implementation Details](#implementation-details)
5. [Testing Checklist](#testing-checklist)

---

## User Experience (UX) Design

### Purpose

The `ViewPastClearancesModal.php` functions as a **history viewer** where administrators can:

- View past clearance history organized by sector (College, Senior High School, Faculty)
- Delete entire academic years (bulk administrative action)

### User Flow

#### Step 1: Access Past Clearances

1. Admin navigates to Course Management or appropriate admin page
2. Clicks "View Past Clearances" button
3. Modal opens showing sector tabs (College, SHS, Faculty)

#### Step 2: View History

1. Admin selects a sector tab (e.g., "College")
2. Sees list of academic years with their clearance periods
3. Each academic year displays:
   - Academic year label (e.g., "2024-2025")
   - All semesters under that year
   - All clearance periods for each semester
   - Statistics (applications, completed, status)

#### Step 3: Delete Academic Year

1. Admin clicks "Delete" button on an academic year card
2. Confirmation modal appears with:
   - Academic year details
   - Complete list of what will be deleted
   - Warning about permanence
3. Admin reviews the impact summary
4. Admin confirms deletion
5. System processes deletion with loading indicator
6. Success notification appears
7. Modal refreshes automatically, removed academic year disappears

### Confirmation Modal Content

```
⚠️ Delete Academic Year

Are you sure you want to delete the academic year "2024-2025"?

This will permanently delete:
• The academic year record
• 2 semesters (1st Semester, 2nd Semester)
• 5 clearance periods
  - 1st Semester: College, SHS, Faculty (3 periods)
  - 2nd Semester: College, SHS (2 periods)
• 172 clearance applications total
  - 1st Semester: 87 applications
  - 2nd Semester: 85 applications

⚠️ Warning: This is a destructive action!
All data associated with this academic year will be
permanently removed and cannot be recovered.

[Cancel] [Delete Academic Year]
```

### Error Handling

The system should prevent deletion and show appropriate errors for:

1. **Active Academic Year**

   ```
   ❌ Cannot Delete Academic Year
   This academic year is currently active.
   Please deactivate it first before deleting.
   [OK]
   ```

2. **Active Clearance Periods**

   ```
   ❌ Cannot Delete Academic Year
   All clearance periods must be closed before deleting an academic year.

   The following periods are still active:
   • 2024-2025 - 1st Semester - College (Status: Ongoing)
   • 2024-2025 - 2nd Semester - SHS (Status: Ongoing)

   Please close all periods first.
   [OK]
   ```

3. **Active Clearance Applications**

   ```
   ❌ Cannot Delete Academic Year
   This academic year has 5 active clearance applications.

   All applications must be completed or cancelled before
   deleting the academic year.
   [OK]
   ```

---

## User Interface (UI) Design

### Modal Structure

The `ViewPastClearancesModal.php` displays data in a hierarchical structure:

```
┌─────────────────────────────────────────────────────────┐
│  View Past Clearances                          [× Close] │
├─────────────────────────────────────────────────────────┤
│  [College] [Senior High School] [Faculty]  ← Sector Tabs │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  📅 Academic Year: 2024-2025          [🗑️ Delete]       │
│  ├── 📚 1st Semester                                   │
│  │   ├── 🏛️ College Period (Closed)                    │
│  │   │   Status: Closed | Applications: 45 | Completed: 42│
│  │   │   Dates: Jan 1, 2024 - Mar 31, 2024            │
│  │   ├── 🎓 Senior High School Period (Closed)         │
│  │   │   Status: Closed | Applications: 30 | Completed: 28│
│  │   │   Dates: Jan 1, 2024 - Mar 31, 2024            │
│  │   └── 👨‍🏫 Faculty Period (Closed)                    │
│  │       Status: Closed | Applications: 12 | Completed: 12│
│  │       Dates: Jan 1, 2024 - Mar 31, 2024            │
│  │                                                      │
│  └── 📚 2nd Semester                                    │
│      ├── 🏛️ College Period (Closed)                     │
│      │   Status: Closed | Applications: 50 | Completed: 48│
│      └── 🎓 Senior High School Period (Closed)          │
│          Status: Closed | Applications: 35 | Completed: 33│
│                                                          │
│  📅 Academic Year: 2023-2024          [🗑️ Delete]       │
│  └── ... (older data)                                   │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Visual Design Elements

#### Academic Year Card

- **Header**: Academic year label with delete button
- **Body**: Collapsible/expandable sections showing semesters and periods
- **Delete Button**:
  - Position: Top-right of academic year card
  - Style: Red outline or danger button
  - Icon: Trash can (🗑️)
  - Tooltip: "Delete this academic year and all associated data"
  - Disabled state: Grayed out with tooltip explaining why

#### Color Coding

- **Red**: Delete buttons (destructive actions)
- **Gray**: Disabled buttons (with reason tooltip)
- **Green**: Success indicators
- **Yellow/Orange**: Warning indicators

#### Status Indicators

- ✅ Green checkmark: Deletable
- ❌ Red X: Not deletable (with reason tooltip)
- ⚠️ Warning icon: In confirmation modals

### Data Grouping

The modal should group periods hierarchically:

1. **By Academic Year** (top level)
2. **By Semester** (second level)
3. **By Sector/Period** (third level)

This structure makes it clear what will be deleted when an academic year is removed.

---

## Database Adjustments

### Current State

The database (`clrbasedata_online`) currently has:

- ✅ `semesters` → `academic_years` (ON DELETE CASCADE) - **EXISTS**
- ❌ `clearance_periods` → `academic_years` (ON DELETE CASCADE) - **MISSING**
- ❌ `clearance_periods` → `semesters` (ON DELETE CASCADE) - **MISSING**
- ❌ `clearance_forms` → `academic_years` (ON DELETE CASCADE) - **MISSING**
- ❌ `clearance_forms` → `semesters` (ON DELETE CASCADE) - **MISSING**

### Required Changes

Add the following foreign key constraints to enable proper cascade deletion:

#### 1. Add Constraints for `clearance_periods`

```sql
ALTER TABLE `clearance_periods`
  ADD CONSTRAINT `clearance_periods_ibfk_1`
    FOREIGN KEY (`academic_year_id`)
    REFERENCES `academic_years` (`academic_year_id`)
    ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_periods_ibfk_2`
    FOREIGN KEY (`semester_id`)
    REFERENCES `semesters` (`semester_id`)
    ON DELETE CASCADE;
```

#### 2. Add Constraints for `clearance_forms`

```sql
ALTER TABLE `clearance_forms`
  ADD CONSTRAINT `clearance_forms_ibfk_2`
    FOREIGN KEY (`academic_year_id`)
    REFERENCES `academic_years` (`academic_year_id`)
    ON DELETE CASCADE,
  ADD CONSTRAINT `clearance_forms_ibfk_3`
    FOREIGN KEY (`semester_id`)
    REFERENCES `semesters` (`semester_id`)
    ON DELETE CASCADE;
```

**Note:** Check if `clearance_forms_ibfk_1` (for `user_id`) already exists before adding constraints.

### Verification Queries

Before adding constraints, verify current state:

```sql
-- Check existing foreign keys for clearance_periods
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'clrbasedata_online'
  AND TABLE_NAME = 'clearance_periods'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check existing foreign keys for clearance_forms
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'clrbasedata_online'
  AND TABLE_NAME = 'clearance_forms'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### Cleanup Orphaned Records

Before adding constraints, clean up any orphaned records:

```sql
-- Check for orphaned clearance_periods
SELECT cp.*
FROM clearance_periods cp
LEFT JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
WHERE ay.academic_year_id IS NULL;

-- Check for orphaned clearance_forms
SELECT cf.*
FROM clearance_forms cf
LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
WHERE ay.academic_year_id IS NULL;

-- Delete orphaned records (if any exist)
DELETE cp FROM clearance_periods cp
LEFT JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
WHERE ay.academic_year_id IS NULL;

DELETE cf FROM clearance_forms cf
LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
WHERE ay.academic_year_id IS NULL;
```

### Cascade Delete Flow

After adding constraints, deleting an academic year will cascade as follows:

```
DELETE academic_years WHERE academic_year_id = X
  ↓ (CASCADE)
DELETE semesters WHERE academic_year_id = X
  ↓ (CASCADE)
DELETE clearance_periods WHERE academic_year_id = X
  ↓ (CASCADE)
DELETE clearance_periods WHERE semester_id IN (deleted semester IDs)
  ↓ (CASCADE)
DELETE clearance_forms WHERE academic_year_id = X
  ↓ (CASCADE)
DELETE clearance_forms WHERE semester_id IN (deleted semester IDs)
  ↓ (CASCADE)
DELETE clearance_signatories WHERE clearance_form_id IN (deleted form IDs)
```

---

## Implementation Details

### Backend API

The existing API endpoint `api/clearance/years.php` already handles DELETE requests. Verify it includes:

1. **Authentication check** (admin only)
2. **Validation checks**:
   - Academic year exists
   - All periods are closed/ended
   - No active clearance applications
   - Academic year is not currently active
3. **Transaction handling** for data integrity
4. **Proper error messages**

**API Endpoint:** `DELETE /api/clearance/years.php?id={academic_year_id}`

### Frontend Implementation

#### 1. Update `ViewPastClearancesModal.php`

**Data Grouping Function:**

```javascript
function groupPeriodsByAcademicYear(periods) {
  const grouped = {};

  periods.forEach((period) => {
    const yearKey = period.academic_year;
    const semesterKey = period.semester_name;

    if (!grouped[yearKey]) {
      grouped[yearKey] = {
        academic_year: yearKey,
        academic_year_id: period.academic_year_id,
        semesters: {},
      };
    }

    if (!grouped[yearKey].semesters[semesterKey]) {
      grouped[yearKey].semesters[semesterKey] = {
        semester_name: semesterKey,
        semester_id: period.semester_id,
        periods: [],
      };
    }

    grouped[yearKey].semesters[semesterKey].periods.push(period);
  });

  return grouped;
}
```

**Render Academic Year Cards:**

```javascript
function renderAcademicYearCard(yearData) {
  const card = document.createElement("div");
  card.className = "academic-year-card";

  // Header with delete button
  const header = document.createElement("div");
  header.className = "academic-year-header";
  header.innerHTML = `
        <h4>📅 Academic Year: ${yearData.academic_year}</h4>
        <button class="btn btn-sm btn-danger" 
                onclick="deleteAcademicYear(${yearData.academic_year_id}, '${yearData.academic_year}')"
                title="Delete this academic year and all associated data">
            <i class="fas fa-trash"></i> Delete
        </button>
    `;

  // Body with semesters and periods
  const body = document.createElement("div");
  body.className = "academic-year-body";

  Object.values(yearData.semesters).forEach((semester) => {
    const semesterSection = renderSemesterSection(semester);
    body.appendChild(semesterSection);
  });

  card.appendChild(header);
  card.appendChild(body);
  return card;
}
```

**Delete Function:**

```javascript
async function deleteAcademicYear(academicYearId, academicYearLabel) {
  // First, check if deletion is allowed
  try {
    const checkResponse = await fetch(
      `../../api/clearance/check_year_deletable.php?id=${academicYearId}`,
      { credentials: "include" }
    );
    const checkData = await checkResponse.json();

    if (!checkData.can_delete) {
      showToastNotification(checkData.message, "error");
      return;
    }

    // Show confirmation modal
    showConfirmationModal(
      `Delete Academic Year "${academicYearLabel}"`,
      `This will permanently delete the academic year and all associated data.`,
      buildDeletionImpactSummary(checkData.impact),
      "Delete Academic Year",
      "Cancel",
      async () => {
        // Perform deletion
        try {
          showToastNotification("Deleting academic year...", "info");

          const response = await fetch(
            `../../api/clearance/years.php?id=${academicYearId}`,
            {
              method: "DELETE",
              credentials: "include",
            }
          );

          const data = await response.json();

          if (data.success) {
            showToastNotification(
              `Academic year "${academicYearLabel}" deleted successfully`,
              "success"
            );
            // Reload the modal data
            loadPastClearances(currentSector);
          } else {
            showToastNotification(
              data.message || "Failed to delete academic year",
              "error"
            );
          }
        } catch (error) {
          console.error("Error deleting academic year:", error);
          showToastNotification(
            "An error occurred while deleting the academic year",
            "error"
          );
        }
      },
      "danger"
    );
  } catch (error) {
    console.error("Error checking deletability:", error);
    showToastNotification(
      "Unable to check if academic year can be deleted",
      "error"
    );
  }
}
```

**Impact Summary Builder:**

```javascript
function buildDeletionImpactSummary(impact) {
  let summary = "This will permanently delete:\n\n";
  summary += `• The academic year record\n`;
  summary += `• ${impact.semester_count} semester(s)\n`;
  summary += `• ${impact.period_count} clearance period(s)\n`;
  summary += `• ${impact.form_count} clearance application(s)\n\n`;
  summary += "⚠️ Warning: This action cannot be undone!";

  return summary;
}
```

#### 2. Create Check Deletability API (Optional but Recommended)

Create `api/clearance/check_year_deletable.php` to provide detailed impact information before deletion:

```php
<?php
// Check if academic year can be deleted and return impact summary
header('Content-Type: application/json');
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$academicYearId = $_GET['id'] ?? null;
if (!$academicYearId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Academic year ID required']);
    exit;
}

$pdo = Database::getInstance()->getConnection();

// Check if year exists
$stmt = $pdo->prepare('SELECT academic_year_id, year, is_active FROM academic_years WHERE academic_year_id = ?');
$stmt->execute([$academicYearId]);
$year = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$year) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Academic year not found']);
    exit;
}

// Check if active
if ($year['is_active']) {
    echo json_encode([
        'success' => true,
        'can_delete' => false,
        'message' => 'Cannot delete active academic year. Please deactivate it first.',
        'impact' => null
    ]);
    exit;
}

// Count impact
$semesterCount = $pdo->prepare('SELECT COUNT(*) FROM semesters WHERE academic_year_id = ?');
$semesterCount->execute([$academicYearId]);
$semesterCount = $semesterCount->fetchColumn();

$periodCount = $pdo->prepare('SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ?');
$periodCount->execute([$academicYearId]);
$periodCount = $periodCount->fetchColumn();

$formCount = $pdo->prepare('SELECT COUNT(*) FROM clearance_forms WHERE academic_year_id = ?');
$formCount->execute([$academicYearId]);
$formCount = $formCount->fetchColumn();

// Check for active periods
$activePeriods = $pdo->prepare("
    SELECT COUNT(*) FROM clearance_periods
    WHERE academic_year_id = ?
    AND (status = 'Ongoing' OR ended_at IS NULL)
");
$activePeriods->execute([$academicYearId]);
$activePeriodCount = $activePeriods->fetchColumn();

if ($activePeriodCount > 0) {
    echo json_encode([
        'success' => true,
        'can_delete' => false,
        'message' => 'Cannot delete academic year with active clearance periods. Please close all periods first.',
        'impact' => null
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'can_delete' => true,
    'message' => 'Academic year can be deleted',
    'impact' => [
        'semester_count' => (int)$semesterCount,
        'period_count' => (int)$periodCount,
        'form_count' => (int)$formCount
    ]
]);
?>
```

---

## Testing Checklist

### Database Testing

- [ ] Verify foreign key constraints are added successfully
- [ ] Test cascade delete with a test academic year
- [ ] Verify no orphaned records remain after deletion
- [ ] Test transaction rollback on errors

### Backend API Testing

- [ ] Test DELETE endpoint with valid academic year ID
- [ ] Test DELETE endpoint with invalid academic year ID
- [ ] Test DELETE endpoint with active academic year (should fail)
- [ ] Test DELETE endpoint with active periods (should fail)
- [ ] Test DELETE endpoint with active applications (should fail)
- [ ] Verify proper error messages are returned
- [ ] Verify audit logging is working

### Frontend Testing

- [ ] Verify modal displays academic years correctly
- [ ] Verify data grouping (year → semester → period)
- [ ] Test delete button appears on academic year cards
- [ ] Test delete button is disabled for active years
- [ ] Test confirmation modal displays correct impact summary
- [ ] Test successful deletion flow
- [ ] Test error handling (active year, active periods, etc.)
- [ ] Verify modal refreshes after successful deletion
- [ ] Test with different sectors (College, SHS, Faculty)

### Integration Testing

- [ ] Test complete flow: View → Delete → Confirm → Success
- [ ] Test error scenarios and user feedback
- [ ] Verify no data inconsistencies after deletion
- [ ] Test with multiple academic years
- [ ] Test with academic years that have no data

---

## Security Considerations

1. **Authentication**: Only authenticated users can access the modal
2. **Authorization**: Only admin users can delete academic years
3. **Validation**: Backend validates all deletion conditions
4. **Audit Logging**: All deletions should be logged
5. **Confirmation**: User must explicitly confirm destructive actions

---

## Future Enhancements (Optional)

1. **Soft Delete**: Instead of hard delete, add `deleted_at` timestamp
2. **Restore Functionality**: Allow restoration of deleted academic years
3. **Export Before Delete**: Option to export data before deletion
4. **Bulk Delete**: Delete multiple academic years at once
5. **Deletion History**: View log of deleted academic years

---

## Related Files

- `Modals/ViewPastClearancesModal.php` - Main modal component
- `api/clearance/years.php` - DELETE endpoint for academic years
- `api/clearance/past_clearances.php` - GET endpoint for past clearances
- `api/clearance/check_year_deletable.php` - Check if year can be deleted (to be created)

---

## Notes

- The delete feature is intentionally limited to academic year level only
- Individual period or semester deletion is not supported
- All deletions are permanent and cannot be undone
- Always backup database before running migration scripts
- Test thoroughly in development environment before production deployment
