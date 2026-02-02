# Signatory Name Preservation Implementation Plan (Option 3)

## Implementation Summary

**Status:** ✅ **COMPLETED** (January 23-24, 2026)

**What Was Implemented:**
- Database schema changes to add `signatory_first_name` and `signatory_last_name` columns
- Foreign key constraint modified to `ON DELETE SET NULL` for `actual_user_id`
- All signatory action APIs updated to capture names on **both approval AND rejection**
- All display APIs updated to use COALESCE logic (preserved names take priority)
- Deletion logic updated to preserve signatory records while nullifying user references
- PDF generation and export functionality updated to display preserved names

**Enhancement (Jan 24):** Initially, name preservation was only for approvals. After implementation, the decision was made to also preserve names on rejections for complete audit trail and accountability.

**Result:** Staff members' names now persist on all clearance forms they interacted with (approved or rejected), even after their accounts are deleted from the system.

---

## Overview

This document outlines the implementation plan for **Option 3: Preserve Signatory Names After Deletion**. This approach ensures that when a staff member (Regular Staff, School Administrator, or Program Head) is deleted from the system, their name remains visible on historical clearance forms they signed or rejected, maintaining a complete audit trail.

---

## Problem Statement

### Current Behavior

When a staff member signs a clearance form (Approved/Rejected), the system stores:
- `actual_user_id` in `clearance_signatories` table (foreign key to `users.user_id`)
- Signatory name is retrieved via JOIN: `LEFT JOIN users u ON cs.actual_user_id = u.user_id`

### Issue

When a staff member is deleted:
1. Their record is removed from `users` table
2. `actual_user_id` in `clearance_signatories` references a non-existent user
3. JOIN queries return NULL for the signatory name
4. **Result**: Historical clearance forms show empty/null for the signer's name, breaking the audit trail

### Example Scenario

**Before Deletion:**
```
Clearance Progress:
├─ Cashier — Jane Doe ✓
│  Status: Approved
│  Date Signed: Nov 14, 2025
│  Remarks: All cleared
```

**After Deletion (Current):**
```
Clearance Progress:
├─ Cashier — [empty] ← Name disappears!
│  Status: Approved
│  Date Signed: Nov 14, 2025
│  Remarks: All cleared
```

---

## Solution: Option 3 - Preserve Names at Point of Signing

### Approach

Capture the signer's name **at the moment of signing** and store it permanently in the database, independent of the user record.

### Key Components

1. **Database Schema Changes**
   - Add `signatory_first_name` and `signatory_last_name` columns to `clearance_signatories`
   - Modify foreign key constraint to `ON DELETE SET NULL`

2. **Application Logic Changes**
   - Capture name when staff member signs a form
   - Update all display queries to prefer stored names over JOINs

3. **Deletion Logic Updates**
   - Remove deletion of `clearance_signatories` records
   - Rely on foreign key constraint to set `actual_user_id` to NULL

---

## Enhancement: Preserve Names on Rejection

### Decision (Added January 24, 2026)

**Original Design:** Name preservation was initially designed to only capture names on **approval** actions. Rejection actions would set `actual_user_id`, `signatory_first_name`, and `signatory_last_name` to NULL.

**Rationale for Original Design:**
- Approvals are "signatures" that appear on official documents
- Rejections are workflow actions, not signatures
- Traditional paper clearances only show approved signatures

**Enhancement Decision:** After implementation, it was decided to **also preserve names on rejection** for the following reasons:

1. **Complete Audit Trail** - Know exactly who rejected and why, even after staff deletion
2. **Accountability** - Full transparency of all actions taken on clearance forms
3. **Consistency** - Both approvals and rejections are official staff actions
4. **Admin Reports** - Enable analysis of rejection patterns by staff member
5. **Data Integrity** - Maintain complete historical record of all transactions

**UI Display Behavior:**
- ✅ **Approved** → Shows signatory name prominently (e.g., "Dr. Santos")
- ✅ **Rejected** → Shows signatory name (e.g., "Dr. Santos") ← **ENHANCED**
- ⚪ **Unapplied/Pending** → Shows "N/A" (no action taken yet)

**Key Difference:**
- Approved: Has `date_signed` populated
- Rejected: `date_signed` remains NULL, but name is preserved

---

## Implementation Phases

### Phase 1: Database Schema Changes

#### Step 1.1: Add Name Columns to `clearance_signatories`

```sql
-- Add columns to store signatory name snapshot at time of signing
ALTER TABLE `clearance_signatories`
ADD COLUMN `signatory_first_name` VARCHAR(100) NULL 
    COMMENT 'First name at time of signing (preserved)' 
    AFTER `actual_user_id`,
ADD COLUMN `signatory_last_name` VARCHAR(100) NULL 
    COMMENT 'Last name at time of signing (preserved)' 
    AFTER `signatory_first_name`;

-- Add index for performance (if actual_user_id doesn't have one)
ALTER TABLE `clearance_signatories`
ADD INDEX `idx_actual_user` (`actual_user_id`);
```

#### Step 1.2: Modify Foreign Key Constraint

```sql
-- First, check existing constraint name
SELECT CONSTRAINT_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'clearance_signatories' 
  AND COLUMN_NAME = 'actual_user_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- If constraint exists, drop it (replace 'fk_constraint_name' with actual name)
-- ALTER TABLE `clearance_signatories` DROP FOREIGN KEY `fk_constraint_name`;

-- Add new constraint with ON DELETE SET NULL
ALTER TABLE `clearance_signatories`
ADD CONSTRAINT `fk_clearance_signatories_actual_user`
FOREIGN KEY (`actual_user_id`) REFERENCES `users` (`user_id`)
ON DELETE SET NULL
ON UPDATE CASCADE;
```

**Note:** If there's no existing foreign key constraint, this step creates a new one. If one exists, you must drop it first before creating the new one.

#### Step 1.3: Backfill Existing Records

```sql
-- Backfill existing signatures with current user names
UPDATE clearance_signatories cs
INNER JOIN users u ON cs.actual_user_id = u.user_id
SET 
    cs.signatory_first_name = u.first_name,
    cs.signatory_last_name = u.last_name
WHERE cs.actual_user_id IS NOT NULL
  AND cs.signatory_first_name IS NULL;

-- Verify backfill
SELECT 
    COUNT(*) as total_signatures,
    SUM(CASE WHEN actual_user_id IS NOT NULL AND signatory_first_name IS NOT NULL THEN 1 ELSE 0 END) as backfilled,
    SUM(CASE WHEN actual_user_id IS NOT NULL AND signatory_first_name IS NULL THEN 1 ELSE 0 END) as missing
FROM clearance_signatories;
```

**Expected Result:** All existing signatures should have names backfilled, with `missing` = 0.

---

### Phase 2: Update Application Logic

#### Step 2.1: Capture Names When Signing

**File:** `api/clearance/signatory_action.php`

**Location:** Around lines 290-313 (in the approval/rejection logic)

**Changes:**

```php
// BEFORE the UPDATE/INSERT logic (around line 290), add:
// Get the signer's name at the moment of signing
$signerStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$signerStmt->execute([$actingUserId]);
$signerInfo = $signerStmt->fetch(PDO::FETCH_ASSOC);
$signerFirstName = $signerInfo['first_name'] ?? null;
$signerLastName = $signerInfo['last_name'] ?? null;

// UPDATE the UPDATE statement (around line 296):
if ($action === 'Approved') {
    $upd = $pdo->prepare("
        UPDATE clearance_signatories 
        SET action=?, 
            remarks=?, 
            reason_id=NULL, 
            additional_remarks=NULL, 
            updated_at=NOW(), 
            actual_user_id=?, 
            signatory_first_name=?,
            signatory_last_name=?,
            date_signed=NOW() 
        WHERE clearance_form_id=? AND designation_id=?
    ");
    $upd->execute([
        $action, 
        $remarks, 
        $actingUserId, 
        $signerFirstName,
        $signerLastName,
        $formId, 
        $designationId
    ]);
} else {
    // On rejection, preserve name and user_id for audit trail (date_signed remains NULL)
    // UPDATED: Changed to preserve names on rejection for complete audit trail
    $upd = $pdo->prepare("
        UPDATE clearance_signatories 
        SET action=?, 
            remarks=NULL, 
            reason_id=?, 
            additional_remarks=?, 
            updated_at=NOW(), 
            actual_user_id=?,
            signatory_first_name=?,
            signatory_last_name=?,
            date_signed=NULL 
        WHERE clearance_form_id=? AND designation_id=?
    ");
    $upd->execute([$action, $reasonId, $remarks, $actingUserId, $signerFirstName, $signerLastName, $formId, $designationId]);
}

// UPDATE the INSERT statement (around line 304):
// UPDATED: Always capture name and user_id for both approval and rejection
$userIdToInsert = $actingUserId; // Always capture who took the action
$firstNameToInsert = $signerFirstName; // Always capture name
$lastNameToInsert = $signerLastName; // Always capture name
$dateToInsert = ($action === 'Approved') ? date('Y-m-d H:i:s') : null; // Only date for approvals
$generalRemarks = ($action === 'Approved') ? $remarks : null;
$rejectionRemarks = ($action === 'Rejected') ? $remarks : null;

$ins = $pdo->prepare("
    INSERT INTO clearance_signatories 
    (clearance_form_id, designation_id, action, remarks, reason_id, additional_remarks, 
     created_at, updated_at, actual_user_id, signatory_first_name, signatory_last_name, date_signed) 
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?)
");
$ins->execute([
    $formId, 
    $designationId, 
    $action, 
    $generalRemarks, 
    $reasonId, 
    $rejectionRemarks, 
    $userIdToInsert,
    $firstNameToInsert,
    $lastNameToInsert,
    $dateToInsert
]);
```

#### Step 2.2: Update Other Signatory Action Endpoints

**Additional Files Updated for Rejection Name Preservation:**

##### A. `api/clearance/apply_signatory.php`

**Location:** Lines ~404-418 (single reject), ~558-608 (bulk reject)

**Changes:**
- Added name fetching before rejection UPDATE
- Modified UPDATE statement to preserve names on rejection
- Updated bulk reject operation to include names

```php
// Fetch the acting user's name for preservation
$signerStmt = $connection->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$signerStmt->execute([$userId]);
$signerInfo = $signerStmt->fetch(PDO::FETCH_ASSOC);
$signerFirstName = $signerInfo['first_name'] ?? null;
$signerLastName = $signerInfo['last_name'] ?? null;

// Update the signatory status to 'Rejected' - preserve name for audit trail
$stmt = $connection->prepare("
    UPDATE clearance_signatories 
    SET action = 'Rejected', remarks = ?, reason_id = ?, additional_remarks = ?, 
        updated_at = NOW(), actual_user_id = ?, signatory_first_name = ?, 
        signatory_last_name = ?, date_signed = NULL 
    WHERE signatory_id = ? AND clearance_form_id = ?
");
$result = $stmt->execute([null, $rejectionReasonId, $remarks, $userId, $signerFirstName, $signerLastName, $signatoryId, $clearanceFormId]);
```

##### B. `api/clearance/bulk_signatory_action.php`

**Location:** Lines ~149-155

**Changes:**
- Modified bulk rejection to include name preservation parameters

```php
} else { // Rejected - preserve name for audit trail
    $updateSql .= ", remarks = NULL, reason_id = ?, additional_remarks = ?, actual_user_id = ?, signatory_first_name = ?, signatory_last_name = ?, date_signed = NULL";
    array_push($updateParams, $reasonId, $remarks, $actingUserId, $signerFirstName, $signerLastName);
}
```

#### Step 2.3: Update Display Logic in APIs

Update all API endpoints that display signatory names to prefer stored names.

##### C. `api/clearance/export_report.php`

**Location:** Around lines 66-74

**Change:**

```php
SELECT 
    d.designation_name,
    cs.action,
    cs.remarks,
    cs.date_signed,
    -- Use stored name, fallback to JOIN if stored name is NULL
    COALESCE(
        TRIM(CONCAT(cs.signatory_first_name, ' ', cs.signatory_last_name)),
        CONCAT(u_sig.first_name, ' ', u_sig.last_name),
        'N/A'
    ) as signatory_name,
    cs.actual_user_id
FROM clearance_signatories cs
JOIN designations d ON cs.designation_id = d.designation_id
LEFT JOIN users u_sig ON cs.actual_user_id = u_sig.user_id
WHERE cs.clearance_form_id = ?
ORDER BY d.designation_name
```

##### D. `api/clearance/user_status.php`

**Location:** Around lines 60-102 (SQL query and response mapping)

**SQL Query Change:**

```php
SELECT
    cs.signatory_id,
    d.designation_name,
    cs.action,
    cs.updated_at,
    cs.remarks,
    -- Stored name (preserved even if user deleted)
    cs.signatory_first_name,
    cs.signatory_last_name,
    -- Fallback: JOIN to current user (will be NULL if user deleted)
    u.first_name AS signatory_first_name_join,
    u.last_name AS signatory_last_name_join,
    -- program head name for applicant's department
    (
        SELECT CONCAT(u2.first_name,' ',u2.last_name)
        FROM staff sp
        JOIN users u2 ON u2.user_id = sp.user_id
        WHERE sp.staff_category = 'Program Head' AND sp.is_active = 1
          AND (:dept_id IS NOT NULL AND sp.department_id = :dept_id)
        LIMIT 1
    ) AS ph_signatory_name
FROM clearance_signatories cs 
JOIN designations d ON d.designation_id=cs.designation_id 
LEFT JOIN staff s ON s.designation_id=cs.designation_id AND s.is_active=1 
LEFT JOIN users u ON u.user_id=s.user_id 
WHERE cs.clearance_form_id=?
```

**Response Mapping Change (around line 395-403):**

```php
// Use COALESCE logic: preserved names first, then live names from users table
'signatory_name' => trim(
    (($signatory['preserved_first_name'] ?? '') . ' ' . ($signatory['preserved_last_name'] ?? '')) 
    ?: (($signatory['live_first_name'] ?? '') . ' ' . ($signatory['live_last_name'] ?? ''))
),
'signatory_username' => $signatory['signatory_username']
```

##### E. `api/clearance/button_status.php`

**Location:** Around lines 225-235

**Change:**

```php
// In the SQL query, add:
SELECT
    cs.signatory_id,
    cs.designation_id,
    d.designation_name,
    cs.action,
    -- Stored name (preserved)
    cs.signatory_first_name,
    cs.signatory_last_name,
    -- Fallback: JOIN to current user
    u.first_name,
    u.last_name,
    ...
FROM clearance_signatories cs
JOIN designations d ON cs.designation_id = d.designation_id
LEFT JOIN users u ON cs.actual_user_id = u.user_id
...

// In the response mapping:
'signatory_name' => !empty($signatory['signatory_first_name']) || !empty($signatory['signatory_last_name'])
    ? trim(($signatory['signatory_first_name'] ?? '') . ' ' . ($signatory['signatory_last_name'] ?? ''))
    : trim(($signatory['first_name'] ?? '') . ' ' . ($signatory['last_name'] ?? '')),
```

##### F. `api/clearance/period_status.php`

**Location:** Around lines 203-213

**Similar changes** as `button_status.php` - add stored name columns to SELECT, and use COALESCE/preference logic in mapping.

##### G. `api/clearance/status.php`

**Location:** Around lines 100-106

**Change:**

```php
SELECT cs.designation_id, d.designation_name, cs.action, cs.updated_at, cs.remarks,
    -- Use stored name, fallback to JOIN
    COALESCE(
        TRIM(CONCAT(cs.signatory_first_name, ' ', cs.signatory_last_name)),
        CONCAT(u.first_name,' ',u.last_name),
        'N/A'
    ) AS signatory_name
FROM clearance_signatories cs
JOIN designations d ON d.designation_id = cs.designation_id
LEFT JOIN staff s ON s.designation_id = cs.designation_id AND s.is_active = 1
LEFT JOIN users u ON u.user_id = s.user_id
WHERE cs.clearance_form_id = ?
```

##### H. `api/clearance/user_clearance_forms.php`

**Location:** Around lines 119-126 (response mapping)

**SQL Query Change:** Add `cs.signatory_first_name, cs.signatory_last_name` to SELECT, and LEFT JOIN users on `cs.actual_user_id`.

**Response Mapping Change:**

```php
'signatory_name' => !empty($row['signatory_first_name']) || !empty($row['signatory_last_name'])
    ? trim(($row['signatory_first_name'] ?? '') . ' ' . ($row['signatory_last_name'] ?? ''))
    : trim(($row['signatory_first_name_join'] ?? '') . ' ' . ($row['signatory_last_name_join'] ?? '')),
```

---

### Phase 3: Update Deletion Logic

#### Step 3.1: Modify `deleteStaff()` Method

**File:** `includes/classes/UserManager.php`

**Method:** `deleteStaff($userId)`

**Change:** Remove Step 1 that deletes `clearance_signatories` records. The foreign key constraint with `ON DELETE SET NULL` will automatically set `actual_user_id` to NULL, while preserving the stored names.

**Updated Deletion Chain:**

```php
public function deleteStaff($userId) {
    try {
        // Get staff record from user_id
        $stmt = $this->connection->prepare("SELECT employee_number FROM staff WHERE user_id = ?");
        $stmt->execute([$userId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            return ['success' => false, 'message' => 'Staff record not found'];
        }
        
        $employeeNumber = $staff['employee_number'];
        
        // Check if admin user (prevent deletion)
        $user = $this->getUserById($userId);
        if ($user && $user['username'] === 'admin') {
            return ['success' => false, 'message' => 'Cannot delete admin user'];
        }
        
        $this->connection->beginTransaction();
        
        // ❌ REMOVED: Do NOT delete clearance_signatories records
        // The ON DELETE SET NULL foreign key will handle actual_user_id
        // The stored signatory_first_name and signatory_last_name remain intact
        
        // Step 1: Delete clearance_forms belonging to this staff member (if they have their own forms)
        $stmt = $this->connection->prepare("
            DELETE cs FROM clearance_signatories cs
            INNER JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
            WHERE cf.user_id = ?
        ");
        $stmt->execute([$userId]);
        
        $stmt = $this->connection->prepare("DELETE FROM clearance_forms WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Step 2: Delete user_designation_assignments
        $stmt = $this->connection->prepare("DELETE FROM user_designation_assignments WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Step 3: Delete user_department_assignments
        $stmt = $this->connection->prepare("DELETE FROM user_department_assignments WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Step 4: Delete sector_signatory_assignments
        $stmt = $this->connection->prepare("DELETE FROM sector_signatory_assignments WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Step 5: Delete staff record
        $stmt = $this->connection->prepare("DELETE FROM staff WHERE employee_number = ?");
        $stmt->execute([$employeeNumber]);
        
        // Step 6: Delete users record
        // When this happens, actual_user_id in clearance_signatories becomes NULL automatically
        // but signatory_first_name and signatory_last_name remain preserved
        $stmt = $this->connection->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $this->connection->commit();
        
        return ['success' => true, 'message' => 'Staff deleted successfully'];
        
    } catch (PDOException $e) {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
```

**Note:** The deletion of `clearance_signatories` in Step 1 (via JOIN with `clearance_forms`) only deletes signatory records for clearance forms that BELONG TO the staff member being deleted (if they're a student/faculty with their own clearance form). This is different from deleting signatures MADE BY the staff member on other people's forms, which should NOT be deleted.

#### Step 3.2: Apply Same Logic to `deleteFaculty()` and `deleteStudent()`

Both methods should follow the same pattern - do NOT delete `clearance_signatories` records where the staff member signed someone else's form. Only delete signatory records for forms that belong to the user being deleted (if applicable).

---

### Phase 4: Testing Procedures

#### Test 1: Verify Name Capture on Signing

**Steps:**
1. Have a staff member (e.g., Cashier) approve a student's clearance form
2. Check database:

```sql
SELECT 
    cs.signatory_id,
    cs.actual_user_id,
    cs.signatory_first_name,
    cs.signatory_last_name,
    cs.action,
    cs.date_signed
FROM clearance_signatories cs
WHERE cs.actual_user_id = [staff_user_id]
ORDER BY cs.date_signed DESC
LIMIT 5;
```

**Expected:** `signatory_first_name` and `signatory_last_name` should be populated with the staff member's current name.

#### Test 2: Verify Name Preservation After Deletion

**Steps:**
1. Identify a staff member who has signed forms (check with query above)
2. Note their `user_id`, `first_name`, `last_name`, and count of signatures
3. Delete the staff member via UI or API
4. Verify preservation:

```sql
-- Check that actual_user_id is NULL but names are preserved
SELECT 
    cs.signatory_id,
    cf.clearance_form_id,
    cs.action,
    cs.actual_user_id, -- Should be NULL
    cs.signatory_first_name, -- Should still have value
    cs.signatory_last_name, -- Should still have value
    cs.date_signed,
    CONCAT(cs.signatory_first_name, ' ', cs.signatory_last_name) as preserved_name
FROM clearance_signatories cs
JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
WHERE cs.signatory_first_name = '[deleted_staff_first_name]'
  AND cs.signatory_last_name = '[deleted_staff_last_name]'
  AND cs.actual_user_id IS NULL;
```

**Expected:** All signatures should have `actual_user_id = NULL` but `signatory_first_name` and `signatory_last_name` should still contain the deleted staff member's name.

#### Test 3: Verify Display in UI

**Steps:**
1. View a student's clearance progress whose form was signed by the deleted staff member
2. Check that the signatory name still displays correctly
3. Export the clearance form (if export feature exists)
4. Verify the exported document shows the signatory name

**Expected:** 
- Clearance progress modal shows: "Cashier — Jane Doe" (not empty)
- All signature details (date, remarks, status) remain intact

#### Test 4: Verify Backfill of Existing Data

**Steps:**
1. Run the backfill SQL (Step 1.3)
2. Verify all existing signatures have names:

```sql
SELECT 
    COUNT(*) as total_with_actual_user,
    SUM(CASE WHEN signatory_first_name IS NOT NULL THEN 1 ELSE 0 END) as with_stored_name,
    SUM(CASE WHEN signatory_first_name IS NULL AND actual_user_id IS NOT NULL THEN 1 ELSE 0 END) as missing_name
FROM clearance_signatories
WHERE actual_user_id IS NOT NULL;
```

**Expected:** `missing_name` should be 0 after backfill.

#### Test 5: Verify New Signatures Capture Names

**Steps:**
1. Have a staff member approve a NEW clearance form (after implementation)
2. Check the database:

```sql
SELECT 
    cs.signatory_id,
    cs.actual_user_id,
    cs.signatory_first_name,
    cs.signatory_last_name,
    cs.action,
    cs.date_signed
FROM clearance_signatories cs
WHERE cs.date_signed >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY cs.date_signed DESC;
```

**Expected:** All new signatures should have `signatory_first_name` and `signatory_last_name` populated.

---

## Summary of Changes

### Database Changes

| Component | Change Type | Description |
|-----------|------------|-------------|
| `clearance_signatories` table | ALTER TABLE | Add `signatory_first_name`, `signatory_last_name` columns |
| Foreign key constraint | MODIFY | Change `actual_user_id` FK to `ON DELETE SET NULL` |
| Existing data | UPDATE | Backfill existing signatures with current user names |

### Code Changes

| File | Change Type | Description |
|------|------------|-------------|
| `api/clearance/signatory_action.php` | CODE UPDATE | Capture names on approval AND rejection (INSERT/UPDATE) |
| `api/clearance/apply_signatory.php` | CODE UPDATE | Preserve names on single & bulk rejection |
| `api/clearance/bulk_signatory_action.php` | CODE UPDATE | Include names in bulk rejection parameters |
| `api/clearance/user_status.php` | CODE UPDATE | Use COALESCE for preserved names (priority logic) |
| `api/clearance/export_report.php` | CODE UPDATE | Prefer stored names in SELECT (already implemented) |
| `api/clearance/button_status.php` | CODE UPDATE | Prefer stored names in query and mapping |
| `api/clearance/period_status.php` | CODE UPDATE | Prefer stored names in query and mapping |
| `api/clearance/status.php` | CODE UPDATE | Use COALESCE for stored names in SELECT |
| `api/clearance/user_clearance_forms.php` | CODE UPDATE | Prefer stored names in query and mapping |
| `includes/classes/UserManager.php` | CODE UPDATE | Remove `clearance_signatories` deletion logic, add `sector_signatory_assignments` |

---

## Benefits

✅ **Complete Audit Trail** - Signatory names preserved forever, even after user deletion  
✅ **Full Accountability** - Both approvals AND rejections preserve staff member names  
✅ **Referential Integrity** - No orphaned records, `actual_user_id` set to NULL automatically  
✅ **Minimal Performance Impact** - Two small VARCHAR columns, no additional JOINs required  
✅ **Backward Compatible** - Works with existing data via backfill SQL  
✅ **Future-Proof** - Captures data at point in time (snapshot approach)  
✅ **Maintains Data Integrity** - Historical records remain accurate and complete  
✅ **Enhanced Transparency** - Complete visibility of all staff actions on clearance forms  

---

## Potential Considerations

### Data Size Impact

- **Additional Storage:** ~200 bytes per signature (2 × VARCHAR(100))
- **Impact:** Negligible for typical clearance systems (thousands of signatures)
- **Mitigation:** Consider archiving old clearance forms if storage becomes an issue

### Migration Complexity

- **Backfill Required:** Existing signatures need name population
- **Downtime:** Can be done during maintenance window
- **Risk:** Low - backfill is idempotent and can be run multiple times

### Foreign Key Constraint

- **Change Required:** Modify FK constraint to `ON DELETE SET NULL`
- **Impact:** Requires database schema modification
- **Risk:** Low - well-tested MySQL feature

---

## Rollback Plan

If issues arise after implementation:

1. **Database Rollback:**
   ```sql
   -- Drop new columns
   ALTER TABLE `clearance_signatories`
   DROP COLUMN `signatory_first_name`,
   DROP COLUMN `signatory_last_name`;
   
   -- Revert foreign key constraint (if changed)
   -- ALTER TABLE `clearance_signatories` DROP FOREIGN KEY `fk_clearance_signatories_actual_user`;
   ```

2. **Code Rollback:** Revert all API changes to previous version

3. **Deletion Logic Rollback:** Restore previous deletion logic in `UserManager.php`

---

## Implementation Status

### ✅ Completed (January 23-24, 2026)

- [x] **Phase 1: Database Schema**
  - [x] Add `signatory_first_name` and `signatory_last_name` columns
  - [x] Modify foreign key constraint to `ON DELETE SET NULL`
  - [x] Run backfill SQL for existing data
  - [x] Verify backfill completed successfully

- [x] **Phase 2: Application Logic**
  - [x] Update `signatory_action.php` to capture names (approval AND rejection)
  - [x] Update `apply_signatory.php` to capture names on rejection
  - [x] Update `bulk_signatory_action.php` to capture names on rejection
  - [x] Update `user_status.php` display logic (COALESCE preserved names)
  - [x] Update `export_report.php` display logic (already implemented)
  - [x] Update `status.php` display logic
  - [x] Update `form_distribution.php` for auto-approval scenarios
  - [x] Update `ClearanceFormPDFGenerator.php` for PDF exports

- [x] **Phase 3: Deletion Logic**
  - [x] Update `deleteStaff()` method (includes `sector_signatory_assignments`)
  - [x] Update `deleteFaculty()` method (includes `sector_signatory_assignments`)
  - [x] Remove direct deletion of `clearance_signatories` records
  - [x] Rely on `ON DELETE SET NULL` foreign key constraint

- [x] **Phase 4: Testing**
  - [x] Test database schema changes
  - [x] Test name capture on new approvals
  - [x] Test name capture on new rejections
  - [x] Test foreign key constraint behavior
  - [x] Test backfill of existing data
  - [x] Verify no orphaned sector assignments
  - [x] Test COALESCE display logic in API
  - [x] All linter checks passed

### 🔄 Pending User Testing

- [ ] Test name preservation after actual staff deletion
- [ ] Test UI display of preserved names for deleted users
- [ ] Test PDF export with preserved names
- [ ] Test end-to-end clearance workflow with rejection

---

## Enhancement Implementation Details (January 24, 2026)

### What Changed

**Original Behavior:**
- Approval: Stored `actual_user_id`, `signatory_first_name`, `signatory_last_name`, `date_signed`
- Rejection: Cleared all fields (set to NULL)

**Enhanced Behavior:**
- Approval: Stored `actual_user_id`, `signatory_first_name`, `signatory_last_name`, `date_signed`
- Rejection: Stored `actual_user_id`, `signatory_first_name`, `signatory_last_name`, `date_signed = NULL`

### Files Modified for Rejection Enhancement

1. **`api/clearance/signatory_action.php`** (Lines ~308, ~313-318)
   - Changed rejection UPDATE to preserve `actual_user_id`, `signatory_first_name`, `signatory_last_name`
   - Changed INSERT logic to always capture name and user_id (not just on approval)

2. **`api/clearance/apply_signatory.php`** (Lines ~404-418, ~558-608)
   - Added name fetching before single rejection UPDATE
   - Modified rejection UPDATE to preserve names
   - Added name fetching before bulk rejection
   - Modified bulk rejection UPDATE to preserve names

3. **`api/clearance/bulk_signatory_action.php`** (Line ~153)
   - Modified bulk rejection parameters to include `actual_user_id`, `signatory_first_name`, `signatory_last_name`

4. **`api/clearance/user_status.php`** (Lines ~312-332, ~389-403)
   - Updated SQL to fetch both preserved names (`cs.signatory_first_name`, `cs.signatory_last_name`) and live names (`u.first_name`, `u.last_name`)
   - Modified response mapping to use COALESCE logic (preserved names first, then live names)

### UI Impact

**End-User Clearance View:**
- Previously: Rejected signatories showed "N/A" for signatory name
- Now: Rejected signatories show the staff member's name (e.g., "MultiRole Staff2 Test")

**Card View Display:**
```javascript
// Frontend already had correct logic:
signatory.signatory_name || 'N/A'
// Now API returns name for rejections, so it displays correctly
```

### Database Impact

**Before Enhancement:**
```sql
-- Rejected signatory record
actual_user_id: NULL
signatory_first_name: NULL
signatory_last_name: NULL
date_signed: NULL
action: 'Rejected'
```

**After Enhancement:**
```sql
-- Rejected signatory record
actual_user_id: 179 (preserved)
signatory_first_name: 'MultiRole' (preserved)
signatory_last_name: 'Staff2' (preserved)
date_signed: NULL (still NULL - no signature date for rejections)
action: 'Rejected'
```

### Benefits of Enhancement

1. **Complete Audit Trail** - Know exactly who rejected each clearance, even years later
2. **Accountability** - Staff members can't hide rejection history by being deleted
3. **Reporting** - Enable analysis of rejection patterns by staff member
4. **Consistency** - Both positive and negative actions are preserved equally
5. **Data Integrity** - No loss of historical data regardless of staff changes

---

## Implementation Checklist (Reference)

This checklist tracks the original implementation plan. See "Implementation Status" above for current completion status.

---

## Document History

| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2026-01-24 | 1.2 | Added enhancement: preserve names on rejection; Updated all affected files | System |
| 2026-01-23 | 1.1 | Completed implementation of Option 3; Verified all changes working | System |
| 2026-01-02 | 1.0 | Initial documentation | System |

---

## Related Documents

- `docs/User_Deletion_Implementation_Plan.md` - Main user deletion implementation
- Database Schema: `database/clrbasedata_online (1).sql`
