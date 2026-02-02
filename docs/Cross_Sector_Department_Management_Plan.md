# Cross-Sector Department Management & Program Head Assignment Implementation Plan

## Table of Contents

1. [Overview](#overview)
2. [Approach](#approach)
3. [UI/UX Changes](#uiux-changes)
4. [Files to Update](#files-to-update)
5. [Implementation Steps](#implementation-steps)
6. [Database Considerations](#database-considerations)

---

## Overview

### Business Requirements

- Program Heads assigned to a department (e.g., ICT) can manage that department across **all sectors** where it exists
- Example: Program Head assigned to **College ICT** can manage:
  - College students in ICT
  - Faculty members in ICT (Faculty sector)
  - Clearance forms for both sectors
- Shared departments use the same `department_name` and `department_code` across sectors
- Faculty-only departments (e.g., General Education) exist only in Faculty sector
- Student departments (College/SHS) are also accessible to faculty

### Key Principle

**Program Head access is department-scoped (by name/code), not sector-scoped.** If assigned to "ICT" in any sector, they manage all "ICT" members across all sectors where ICT exists.

---

## Approach

### 1. Cross-Sector Department Matching

**Use `department_name` as the primary cross-sector identifier, with `department_code` as a fallback.**

#### Implementation Logic:

```php
// Helper function to get cross-sector department IDs
function getCrossSectorDepartmentIds($pdo, $userId) {
    // Get Program Head's assigned department names/codes
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            COALESCE(d.department_code, d.department_name) as dept_identifier
        FROM user_department_assignments uda
        JOIN departments d ON uda.department_id = d.department_id
        WHERE uda.user_id = ? AND uda.is_active = 1
    ");
    $stmt->execute([$userId]);
    $identifiers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($identifiers)) {
        return [];
    }

    // Get ALL department_ids that match these identifiers (across all sectors)
    $placeholders = implode(',', array_fill(0, count($identifiers), '?'));
    $stmt = $pdo->prepare("
        SELECT department_id
        FROM departments
        WHERE (department_name IN ($placeholders) OR department_code IN ($placeholders))
        AND is_active = 1
    ");
    $stmt->execute(array_merge($identifiers, $identifiers));
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
```

### 2. Program Head Access Control Updates

All Program Head access queries must use **cross-sector matching** instead of direct `department_id` matching.

---

## UI/UX Changes

### 1. Program Head Assignment (StaffRegistryModal.php)

#### Simplified Option A Layout:

```
Program Head Assignment
───────────────────────────────────────────────────────────────

Select departments this Program Head will manage. Access will
be granted across all sectors where each department exists.

───────────────────────────────────────────────────────────────

Available Departments

☐ Information & Communication Technology (ICT)
  Available in: College, Faculty
  Access: College students, Faculty members

☐ Business, Arts, & Science (BAS)
  Available in: College, Faculty
  Access: College students, Faculty members

☐ Tourism & Hospitality Management (THM)
  Available in: College, Faculty
  Access: College students, Faculty members

☐ Academic Track
  Available in: Senior High School, Faculty
  Access: SHS students, Faculty members

☐ General Education (GE)
  [Faculty Only]
  Available in: Faculty only
  Access: Faculty members only

───────────────────────────────────────────────────────────────

Assignment Summary

Selected: 0 departments

───────────────────────────────────────────────────────────────

☐ Transfer existing Program Head if a department is already
  assigned
```

#### Key Features:

- ✅ Text-based layout with separators (no cards/emojis)
- ✅ Shows which sectors each department is available in
- ✅ Shows what access will be granted
- ✅ `[Faculty Only]` badge for faculty-exclusive departments
- ✅ Real-time summary of selected departments
- ✅ Transfer toggle for existing assignments

### 2. Department Creation (AddDepartmentModal.php)

#### Enhanced Form Structure:

```
Department Name * (text input)
Department Code * (text input, uppercase, max 10 chars)
Department Type * (select dropdown)
  ┌─ Single Sector ─────────────┐
  │ • College                    │
  │ • Senior High School         │
  │ • Faculty Only               │
  └──────────────────────────────┘
  ┌─ Shared Departments ─────────┐
  │ • College & Faculty          │
  │ • Senior High School & Faculty│
  │ • All Sectors                │
  └──────────────────────────────┘

Status * (Active/Inactive)
Description (optional)
```

#### Dynamic Info Display:

When user selects department type, show information about which sectors will be created.

---

## Files to Update

### Frontend Files

#### 1. `Modals/StaffRegistryModal.php`

- **Changes:**
  - Update Program Head assignment section UI (simplified Option A)
  - Remove category selection dropdown
  - Add unified department list with cross-sector indicators
  - Add summary panel
  - Update JavaScript for cross-sector department loading
  - Update `updateDepartmentCheckboxes()` function
- **Backup Required:** ✅ YES

#### 2. `Modals/EditStaffModal.php`

- **Changes:**
  - Update Program Head assignment section to match new UI
  - Update `updateEditDepartmentCheckboxes()` function
  - Ensure cross-sector department matching in edit mode
- **Backup Required:** ✅ YES

#### 3. `Modals/AddDepartmentModal.php`

- **Changes:**
  - Add `department_code` field
  - Add Faculty and shared department type options
  - Add JavaScript for dynamic sector info display
  - Update `saveDepartment()` function
  - Add department code validation
- **Backup Required:** ✅ YES

#### 4. `Modals/AddCourseModal.php`

- **Changes:**
  - Replace hardcoded department list with dynamic API call
  - Show cross-sector indicators in dropdown
  - Group departments by sector in optgroups
- **Backup Required:** ✅ YES

#### 5. `pages/admin/StaffManagement.php`

- **Changes:** None (uses modals)
- **Backup Required:** ❌ NO

#### 6. `pages/admin/CourseManagement.php`

- **Changes:** None (uses modals)
- **Backup Required:** ❌ NO

### Backend API Files

#### 7. `api/departments/create.php` (NEW FILE)

- **Changes:**
  - Create new endpoint for department creation
  - Handle cross-sector department creation
  - Validate department code uniqueness
  - Map department types to sectors
- **Backup Required:** ❌ N/A (new file)

#### 8. `api/departments/list.php`

- **Changes:**
  - Update to include cross-sector information
  - Add flag to indicate if department is shared across sectors
  - Return sector information for each department
- **Backup Required:** ✅ YES

#### 9. `api/program-head/is_assigned.php`

- **Changes:**
  - Update to use cross-sector department matching
  - Check if Program Head's assigned departments exist in requested clearance type
- **Backup Required:** ✅ YES

#### 10. `api/clearance/signatoryList.php`

- **Changes:**
  - Update Program Head filtering to use cross-sector department IDs
  - Replace direct `department_id` matching with name/code matching
- **Backup Required:** ✅ YES

#### 11. `api/program-head/college_students.php`

- **Changes:**
  - Update to use cross-sector department matching
  - Filter students by cross-sector department IDs
- **Backup Required:** ✅ YES

#### 12. `api/program-head/shs_students.php`

- **Changes:**
  - Update to use cross-sector department matching
  - Filter students by cross-sector department IDs
- **Backup Required:** ✅ YES

#### 13. `controllers/FacultyManagementController.php`

- **Changes:**
  - Update Program Head filtering to use cross-sector department matching
- **Backup Required:** ✅ YES

#### 14. `controllers/StudentManagementController.php`

- **Changes:**
  - Update Program Head filtering to use cross-sector department matching
- **Backup Required:** ✅ YES

#### 15. `api/staff/list.php`

- **Changes:**
  - Update Program Head filtering to use cross-sector department matching
- **Backup Required:** ✅ YES

#### 16. `api/signatories/bulk_assign.php`

- **Changes:**
  - Verify it works with cross-sector logic (may need updates)
- **Backup Required:** ✅ YES

### Helper/Utility Files

#### 17. `includes/helpers/department_helpers.php` (NEW FILE - RECOMMENDED)

- **Changes:**
  - Create reusable helper functions:
    - `getCrossSectorDepartmentIds($pdo, $userId)`
    - `getDepartmentIdentifiers($pdo, $departmentIds)`
    - `checkDepartmentExistsInSector($pdo, $deptName, $deptCode, $sectorId)`
- **Backup Required:** ❌ N/A (new file)

---

## Implementation Steps

### Phase 1: Backend Foundation

1. ✅ Create `includes/helpers/department_helpers.php` with cross-sector helper functions
2. ✅ Create `api/departments/create.php` for department creation
3. ✅ Update `api/departments/list.php` to include cross-sector information
4. ✅ Test backend APIs

### Phase 2: Program Head Access Control

5. ✅ Update `api/program-head/is_assigned.php`
6. ✅ Update `api/clearance/signatoryList.php`
7. ✅ Update `api/program-head/college_students.php`
8. ✅ Update `api/program-head/shs_students.php`
9. ✅ Update `controllers/FacultyManagementController.php`
10. ✅ Update `controllers/StudentManagementController.php`
11. ✅ Update `api/staff/list.php`
12. ✅ Test Program Head access across sectors

### Phase 3: UI Updates - Program Head Assignment

13. ✅ Update `Modals/StaffRegistryModal.php` UI
14. ✅ Update `Modals/EditStaffModal.php` UI
15. ✅ Test Program Head assignment flow

### Phase 4: UI Updates - Department Creation

16. ✅ Update `Modals/AddDepartmentModal.php`
17. ✅ Update `Modals/AddCourseModal.php`
18. ✅ Test department creation flow

### Phase 5: Testing & Validation

19. ✅ Test cross-sector Program Head assignments
20. ✅ Test department creation in all scenarios
21. ✅ Test Program Head access to students and faculty
22. ✅ Test clearance form signatory actions

---

## Detailed Step-by-Step Implementation Guide

### Step 1: Create Helper Functions File

**File:** `includes/helpers/department_helpers.php` (NEW)

**Purpose:** Centralize cross-sector department matching logic

**Implementation:**

```php
<?php
/**
 * Department Helper Functions
 * Provides cross-sector department matching utilities
 */

/**
 * Get all department IDs that match a Program Head's assigned departments across all sectors
 *
 * @param PDO $pdo Database connection
 * @param int $userId Program Head user ID
 * @return array Array of department IDs across all sectors
 */
function getCrossSectorDepartmentIds($pdo, $userId) {
    // Get Program Head's assigned department names/codes
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            COALESCE(d.department_code, d.department_name) as dept_identifier
        FROM user_department_assignments uda
        JOIN departments d ON uda.department_id = d.department_id
        WHERE uda.user_id = ? AND uda.is_active = 1
    ");
    $stmt->execute([$userId]);
    $identifiers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($identifiers)) {
        return [];
    }

    // Get ALL department_ids that match these identifiers (across all sectors)
    $placeholders = implode(',', array_fill(0, count($identifiers), '?'));
    $stmt = $pdo->prepare("
        SELECT department_id
        FROM departments
        WHERE (department_name IN ($placeholders) OR department_code IN ($placeholders))
        AND is_active = 1
    ");
    $stmt->execute(array_merge($identifiers, $identifiers));
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get department identifiers (name/code) from department IDs
 *
 * @param PDO $pdo Database connection
 * @param array $departmentIds Array of department IDs
 * @return array Array of department identifiers
 */
function getDepartmentIdentifiers($pdo, $departmentIds) {
    if (empty($departmentIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            COALESCE(department_code, department_name) as dept_identifier
        FROM departments
        WHERE department_id IN ($placeholders)
        AND is_active = 1
    ");
    $stmt->execute($departmentIds);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Check if a department exists in a specific sector
 *
 * @param PDO $pdo Database connection
 * @param string $deptName Department name
 * @param string $deptCode Department code
 * @param int $sectorId Sector ID
 * @return bool True if department exists in sector
 */
function checkDepartmentExistsInSector($pdo, $deptName, $deptCode, $sectorId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM departments
        WHERE sector_id = ?
        AND is_active = 1
        AND (
            department_name = ?
            OR (department_code IS NOT NULL AND department_code = ?)
        )
    ");
    $stmt->execute([$sectorId, $deptName, $deptCode]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get all sectors where a department exists
 *
 * @param PDO $pdo Database connection
 * @param string $deptName Department name
 * @param string $deptCode Department code
 * @return array Array of sector information
 */
function getDepartmentSectors($pdo, $deptName, $deptCode) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            d.sector_id,
            s.sector_name,
            d.department_id
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE d.is_active = 1
        AND (
            d.department_name = ?
            OR (d.department_code IS NOT NULL AND d.department_code = ?)
        )
        ORDER BY s.sector_name
    ");
    $stmt->execute([$deptName, $deptCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Action Items:**

- [ ] Create the file `includes/helpers/department_helpers.php`
- [ ] Copy the code above
- [ ] Test each function individually

---

### Step 2: Create Department Creation API

**File:** `api/departments/create.php` (NEW)

**Purpose:** Handle department creation with cross-sector support

**Implementation:**

```php
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('manage_departments')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$pdo = Database::getInstance()->getConnection();

$name = trim($input['name'] ?? '');
$code = strtoupper(trim($input['code'] ?? ''));
$type = $input['type'] ?? '';
$status = $input['status'] ?? 'active';
$isActive = ($status === 'active') ? 1 : 0;

// Validate required fields
if (empty($name) || empty($code) || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate department code format (uppercase letters and numbers only)
if (!preg_match('/^[A-Z0-9]+$/', $code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department code must contain only uppercase letters and numbers']);
    exit;
}

// Check if department code already exists across all sectors
$checkStmt = $pdo->prepare("
    SELECT department_id, department_name, sector_id, s.sector_name
    FROM departments d
    JOIN sectors s ON d.sector_id = s.sector_id
    WHERE department_code = ? AND is_active = 1
");
$checkStmt->execute([$code]);
$existing = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($existing)) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => "Department code '$code' already exists",
        'existing' => $existing
    ]);
    exit;
}

// Map type to sectors
$sectorMap = [
    'college' => [1], // College
    'senior-high' => [2], // SHS
    'faculty' => [3], // Faculty
    'college-faculty' => [1, 3], // College + Faculty
    'shs-faculty' => [2, 3], // SHS + Faculty
    'all-sectors' => [1, 2, 3] // All sectors
];

$sectors = $sectorMap[$type] ?? [];
if (empty($sectors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid department type']);
    exit;
}

// Map type to department_type enum
$deptTypeMap = [
    'college' => 'College',
    'senior-high' => 'Senior High School',
    'faculty' => 'Faculty',
    'college-faculty' => 'College', // Primary type
    'shs-faculty' => 'Senior High School', // Primary type
    'all-sectors' => 'College' // Primary type
];

$primaryDeptType = $deptTypeMap[$type] ?? 'College';

try {
    $pdo->beginTransaction();

    $createdIds = [];
    $insertStmt = $pdo->prepare("
        INSERT INTO departments (department_name, department_code, department_type, sector_id, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($sectors as $sectorId) {
        // For Faculty sector, use 'Faculty' as department_type
        $deptType = ($sectorId == 3) ? 'Faculty' : $primaryDeptType;

        $insertStmt->execute([$name, $code, $deptType, $sectorId, $isActive]);
        $createdIds[] = $pdo->lastInsertId();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Department(s) created successfully',
        'department_ids' => $createdIds,
        'sectors_created' => count($sectors)
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
```

**Action Items:**

- [ ] Create the file `api/departments/create.php`
- [ ] Copy the code above
- [ ] Test with different department types
- [ ] Verify department code uniqueness validation

---

### Step 3: Update Departments List API

**File:** `api/departments/list.php`

**Purpose:** Include cross-sector information in department listings

**Changes Needed:**

1. **Add cross-sector detection logic:**

   - After fetching departments, check if each department exists in multiple sectors
   - Add `is_shared` flag to response
   - Add `available_sectors` array to response

2. **Update query to include sector information:**

   ```php
   // Add to SELECT clause
   's.sector_name AS sector_name',
   's.sector_id AS sector_id',

   // Add JOIN
   'JOIN sectors s ON d.sector_id = s.sector_id',
   ```

3. **Add cross-sector grouping:**

   ```php
   // After fetching departments, group by department_name/code
   $grouped = [];
   foreach ($departments as $dept) {
       $key = $dept['department_code'] ?: $dept['department_name'];
       if (!isset($grouped[$key])) {
           $grouped[$key] = [
               'department_name' => $dept['department_name'],
               'department_code' => $dept['department_code'],
               'sectors' => [],
               'is_shared' => false
           ];
       }
       $grouped[$key]['sectors'][] = [
           'sector_id' => $dept['sector_id'],
           'sector_name' => $dept['sector_name'],
           'department_id' => $dept['department_id']
       ];
   }

   // Mark as shared if multiple sectors
   foreach ($grouped as &$group) {
       $group['is_shared'] = count($group['sectors']) > 1;
   }
   ```

**Action Items:**

- [ ] Backup current `api/departments/list.php`
- [ ] Add sector JOIN to query
- [ ] Add cross-sector detection logic
- [ ] Update response format
- [ ] Test API response

---

### Step 4: Update Program Head Access Control - is_assigned.php

**File:** `api/program-head/is_assigned.php`

**Purpose:** Check if Program Head can take action for a clearance type

**Changes Needed:**

1. **Replace department type checking with cross-sector matching:**

   ```php
   // OLD CODE (lines 46-66):
   // Get all department types the Program Head is assigned to
   $deptStmt = $pdo->prepare("
       SELECT DISTINCT d.department_type
       FROM user_department_assignments uda
       JOIN departments d ON uda.department_id = d.department_id
       WHERE uda.user_id = ? AND uda.is_active = 1
   ");
   $deptStmt->execute([$userId]);
   $assignedDeptTypes = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

   // NEW CODE:
   require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

   // Get all department IDs across sectors
   $allDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

   if (empty($allDeptIds)) {
       echo json_encode(["success" => true, "can_take_action" => false]);
       exit;
   }

   // Check if any of these departments exist in the requested clearance type
   $placeholders = implode(',', array_fill(0, count($allDeptIds), '?'));
   $stmt = $pdo->prepare("
       SELECT COUNT(*)
       FROM departments d
       JOIN sectors s ON d.sector_id = s.sector_id
       WHERE d.department_id IN ($placeholders)
       AND s.sector_name = ?
       AND d.is_active = 1
   ");
   $params = array_merge($allDeptIds, [$clearanceType]);
   $stmt->execute($params);
   $hasDepartmentScope = $stmt->fetchColumn() > 0;
   ```

2. **Remove the old Faculty/College special case** (line 63-66) as it's now handled by cross-sector matching

**Action Items:**

- [ ] Backup current `api/program-head/is_assigned.php`
- [ ] Add require for department_helpers.php
- [ ] Replace department type checking logic
- [ ] Test with different clearance types
- [ ] Verify cross-sector access works

---

### Step 5: Update Signatory List API

**File:** `api/clearance/signatoryList.php`

**Purpose:** Filter signatories by Program Head's cross-sector departments

**Changes Needed:**

1. **Find the Program Head filtering section** (around line 458-474)

2. **Replace with cross-sector matching:**

   ```php
   // OLD CODE:
   if ($isProgramHead && !empty($programHeadDepartments)) {
       $phDeptPlaceholders = [];
       foreach ($programHeadDepartments as $i => $id) {
           $key = ":ph_dept_id_$i";
           $phDeptPlaceholders[] = $key;
           $params[$key] = $id;
       }
       $phInClause = implode(',', $phDeptPlaceholders);

       if (strtolower($type) === 'faculty') {
           $where .= " AND u.user_id IN (SELECT user_id FROM user_department_assignments WHERE department_id IN ($phInClause))";
       } else {
           $where .= " AND s.department_id IN ($phInClause)";
       }
   }

   // NEW CODE:
   if ($isProgramHead && !empty($programHeadDepartments)) {
       require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

       // Get cross-sector department IDs
       $allDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

       if (!empty($allDeptIds)) {
           $phDeptPlaceholders = [];
           foreach ($allDeptIds as $i => $id) {
               $key = ":ph_dept_id_$i";
               $phDeptPlaceholders[] = $key;
               $params[$key] = $id;
           }
           $phInClause = implode(',', $phDeptPlaceholders);

           if (strtolower($type) === 'faculty') {
               $where .= " AND u.user_id IN (SELECT user_id FROM user_department_assignments WHERE department_id IN ($phInClause) AND is_active = 1)";
           } else {
               $where .= " AND s.department_id IN ($phInClause)";
           }
       }
   }
   ```

**Action Items:**

- [ ] Backup current `api/clearance/signatoryList.php`
- [ ] Locate Program Head filtering section
- [ ] Replace with cross-sector matching
- [ ] Test signatory list filtering
- [ ] Verify both student and faculty filtering work

---

### Step 6: Update College Students API

**File:** `api/program-head/college_students.php`

**Purpose:** Filter College students by Program Head's cross-sector departments

**Changes Needed:**

1. **Find where Program Head departments are used for filtering**

2. **Replace with cross-sector matching:**

   ```php
   // Add at top of file
   require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

   // Replace department filtering logic
   if ($isProgramHead) {
       // Get cross-sector department IDs
       $allDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

       if (!empty($allDeptIds)) {
           $placeholders = implode(',', array_fill(0, count($allDeptIds), '?'));
           $where .= " AND s.department_id IN ($placeholders)";
           $params = array_merge($params, $allDeptIds);
       } else {
           // No departments assigned, return empty
           $where .= " AND 1=0";
       }
   }
   ```

**Action Items:**

- [ ] Backup current `api/program-head/college_students.php`
- [ ] Add require for department_helpers.php
- [ ] Update department filtering logic
- [ ] Test College student filtering
- [ ] Verify cross-sector access

---

### Step 7: Update SHS Students API

**File:** `api/program-head/shs_students.php`

**Purpose:** Filter SHS students by Program Head's cross-sector departments

**Changes Needed:**

Same as Step 6, but for SHS students

**Action Items:**

- [ ] Backup current `api/program-head/shs_students.php`
- [ ] Add require for department_helpers.php
- [ ] Update department filtering logic
- [ ] Test SHS student filtering
- [ ] Verify cross-sector access

---

### Step 8: Update Faculty Management Controller

**File:** `controllers/FacultyManagementController.php`

**Purpose:** Filter faculty by Program Head's cross-sector departments

**Changes Needed:**

1. **Find Program Head filtering logic**

2. **Replace with cross-sector matching:**

   ```php
   require_once __DIR__ . '/../includes/helpers/department_helpers.php';

   if ($isProgramHead) {
       $allDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

       if (!empty($allDeptIds)) {
           $placeholders = implode(',', array_fill(0, count($allDeptIds), '?'));
           $where .= " AND u.user_id IN (
               SELECT user_id FROM user_department_assignments
               WHERE department_id IN ($placeholders) AND is_active = 1
           )";
           $params = array_merge($params, $allDeptIds);
       } else {
           $where .= " AND 1=0";
       }
   }
   ```

**Action Items:**

- [ ] Backup current `controllers/FacultyManagementController.php`
- [ ] Add require for department_helpers.php
- [ ] Update faculty filtering logic
- [ ] Test faculty filtering
- [ ] Verify cross-sector access

---

### Step 9: Update Student Management Controller

**File:** `controllers/StudentManagementController.php`

**Purpose:** Filter students by Program Head's cross-sector departments

**Changes Needed:**

Same as Step 8, but for students (use `s.department_id` instead of `user_department_assignments`)

**Action Items:**

- [ ] Backup current `controllers/StudentManagementController.php`
- [ ] Add require for department_helpers.php
- [ ] Update student filtering logic
- [ ] Test student filtering
- [ ] Verify cross-sector access

---

### Step 10: Update Staff List API

**File:** `api/staff/list.php`

**Purpose:** Filter staff by Program Head's cross-sector departments

**Changes Needed:**

1. **Find Program Head filtering section** (around line 74-79)

2. **Replace with cross-sector matching:**

   ```php
   require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

   if ($userRole === 'Program Head') {
       $allDeptIds = getCrossSectorDepartmentIds($pdo, $currentUserId);

       if (!empty($allDeptIds)) {
           $placeholders = implode(',', array_fill(0, count($allDeptIds), '?'));
           $where[] = "s.user_id IN (
               SELECT DISTINCT uda.user_id
               FROM user_department_assignments uda
               WHERE uda.department_id IN ($placeholders) AND uda.is_active = 1
           )";
           $params = array_merge($params, $allDeptIds);
       } else {
           // No departments, return empty
           echo json_encode(['success'=> true, 'page' => $page, 'limit' => $limit, 'total' => 0, 'staff' => []]);
           exit;
       }
   }
   ```

**Action Items:**

- [ ] Backup current `api/staff/list.php`
- [ ] Add require for department_helpers.php
- [ ] Update staff filtering logic
- [ ] Test staff list filtering
- [ ] Verify cross-sector access

---

### Step 11: Update Staff Registry Modal - UI

**File:** `Modals/StaffRegistryModal.php`

**Purpose:** Update Program Head assignment UI to simplified Option A

**Changes Needed:**

1. **Replace Program Head Assignment Section HTML** (lines 68-95):

   ```html
   <!-- Program Head Assignment Section (Hidden by default) -->
   <div
     id="programHeadAssignmentSection"
     class="program-head-assignment-section"
     style="display: none;"
   >
     <div class="ph-assignment-header">
       <h3>Program Head Assignment</h3>
       <p class="ph-assignment-description">
         Select departments this Program Head will manage. Access will be
         granted across all sectors where each department exists.
       </p>
     </div>

     <hr class="section-separator" />

     <!-- Department List -->
     <div class="ph-dept-list">
       <label class="section-label">Available Departments</label>
       <div id="departmentCheckboxesList" class="checkbox-group">
         <!-- Departments will be populated dynamically -->
       </div>
     </div>

     <hr class="section-separator" />

     <!-- Summary -->
     <div class="ph-assignment-summary">
       <label class="section-label">Assignment Summary</label>
       <div class="summary-text">
         Selected: <span id="selectedDeptCount">0</span> departments
       </div>
     </div>

     <hr class="section-separator" />

     <!-- Transfer Toggle -->
     <div class="ph-transfer-section">
       <label class="transfer-toggle-label">
         <input type="checkbox" id="phTransferToggle" />
         <span
           >Transfer existing Program Head if a department is already
           assigned</span
         >
       </label>
     </div>
   </div>
   ```

2. **Update `updateDepartmentCheckboxes()` function** (lines 582-663):

   - Remove category selection logic
   - Fetch ALL departments (not filtered by sector)
   - Group by department name/code
   - Show cross-sector information
   - Add real-time summary updates

3. **Add summary update function:**
   ```javascript
   function updatePHSummary() {
     const selected = document.querySelectorAll(
       'input[name="assignedDepartments[]"]:checked'
     );
     const count = selected.length;
     document.getElementById("selectedDeptCount").textContent = count;
   }
   ```

**Action Items:**

- [ ] Backup current `Modals/StaffRegistryModal.php`
- [ ] Replace Program Head assignment HTML
- [ ] Update `updateDepartmentCheckboxes()` function
- [ ] Add summary update function
- [ ] Add CSS styling for new layout
- [ ] Test department selection
- [ ] Test summary updates

---

### Step 12: Update Edit Staff Modal - UI

**File:** `Modals/EditStaffModal.php`

**Purpose:** Match Program Head assignment UI with StaffRegistryModal

**Changes Needed:**

Same as Step 11, but for edit modal

**Action Items:**

- [ ] Backup current `Modals/EditStaffModal.php`
- [ ] Update Program Head assignment section HTML
- [ ] Update `updateEditDepartmentCheckboxes()` function
- [ ] Ensure existing assignments are shown correctly
- [ ] Test edit functionality

---

### Step 13: Update Add Department Modal

**File:** `Modals/AddDepartmentModal.php`

**Purpose:** Add department code field and shared department types

**Changes Needed:**

1. **Add Department Code field** (after Department Name):

   ```html
   <div class="form-group">
     <label for="departmentCode">Department Code *</label>
     <input
       type="text"
       id="departmentCode"
       name="departmentCode"
       required
       placeholder="e.g., ICT"
       maxlength="10"
       pattern="[A-Z0-9]+"
       title="Uppercase letters and numbers only"
       style="text-transform: uppercase;"
     />
     <small class="form-help"
       >Used for cross-sector department matching (must be unique)</small
     >
   </div>
   ```

2. **Update Department Type dropdown** (replace lines 22-29):

   ```html
   <div class="form-group">
     <label for="departmentType">Department Type *</label>
     <select
       id="departmentType"
       name="departmentType"
       required
       onchange="updateDepartmentTypeInfo()"
     >
       <option value="">Select department type</option>
       <optgroup label="Single Sector">
         <option value="college">College</option>
         <option value="senior-high">Senior High School</option>
         <option value="faculty">Faculty Only</option>
       </optgroup>
       <optgroup label="Shared Departments (Cross-Sector)">
         <option value="college-faculty">College & Faculty</option>
         <option value="shs-faculty">Senior High School & Faculty</option>
         <option value="all-sectors">
           All Sectors (College, SHS, Faculty)
         </option>
       </optgroup>
     </select>
     <div
       id="departmentTypeInfo"
       class="form-info"
       style="display: none; margin-top: 8px;"
     >
       <!-- Dynamic info will appear here -->
     </div>
   </div>
   ```

3. **Add JavaScript for type info display:**

   ```javascript
   function updateDepartmentTypeInfo() {
     const type = document.getElementById("departmentType").value;
     const infoDiv = document.getElementById("departmentTypeInfo");

     const info = {
       college: "Will be created in College sector only.",
       "senior-high": "Will be created in Senior High School sector only.",
       faculty:
         "Will be created in Faculty sector only. Faculty-only departments are not accessible to Program Heads assigned to student sectors.",
       "college-faculty":
         "Will be created in both College and Faculty sectors with the same name and code. Program Heads assigned to this department in either sector can manage both College students and Faculty members.",
       "shs-faculty":
         "Will be created in both Senior High School and Faculty sectors with the same name and code. Program Heads assigned to this department in either sector can manage both SHS students and Faculty members.",
       "all-sectors":
         "Will be created in all three sectors (College, Senior High School, Faculty) with the same name and code. Program Heads assigned to this department can manage students and faculty across all sectors.",
     };

     if (type && info[type]) {
       infoDiv.innerHTML = `<small style="color: #0c5591;">${info[type]}</small>`;
       infoDiv.style.display = "block";
     } else {
       infoDiv.style.display = "none";
     }
   }
   ```

4. **Update `saveDepartment()` function** (lines 180-244):
   - Add department code to form data
   - Update API endpoint to `api/departments/create.php`
   - Send JSON instead of FormData
   - Handle department type properly

**Action Items:**

- [ ] Backup current `Modals/AddDepartmentModal.php`
- [ ] Add department code field
- [ ] Update department type dropdown
- [ ] Add JavaScript for type info
- [ ] Update saveDepartment() function
- [ ] Test department creation
- [ ] Test all department types

---

### Step 14: Update Add Course Modal

**File:** `Modals/AddCourseModal.php`

**Purpose:** Dynamically load departments and show cross-sector indicators

**Changes Needed:**

1. **Replace hardcoded department dropdown** (lines 30-47) with dynamic loading

2. **Add function to populate departments:**

   ```javascript
   async function populateCourseDepartments() {
     const deptSelect = document.getElementById("courseDepartment");
     if (!deptSelect) return;

     try {
       const response = await fetch(
         "../../api/departments/list.php?limit=500",
         {
           credentials: "include",
         }
       );
       const data = await response.json();

       if (data.success && data.departments) {
         deptSelect.innerHTML = '<option value="">Select department</option>';

         // Group by sector
         const bySector = {};
         data.departments.forEach((dept) => {
           const sector = dept.sector_name || "Unknown";
           if (!bySector[sector]) bySector[sector] = [];
           bySector[sector].push(dept);
         });

         // Show departments grouped by sector
         ["College", "Senior High School", "Faculty"].forEach((sector) => {
           if (bySector[sector] && bySector[sector].length > 0) {
             const optgroup = document.createElement("optgroup");
             optgroup.label = sector;

             bySector[sector].forEach((dept) => {
               const option = document.createElement("option");
               option.value = dept.department_id;
               let text = `${dept.department_name}`;
               if (dept.department_code) {
                 text += ` (${dept.department_code})`;
               }
               // Show indicator if shared
               if (dept.is_shared) {
                 text += " [Shared]";
               }
               option.textContent = text;
               optgroup.appendChild(option);
             });

             deptSelect.appendChild(optgroup);
           }
         });
       }
     } catch (error) {
       console.error("Failed to load departments:", error);
     }
   }
   ```

3. **Call this function when modal opens:**
   - Update `openAddCourseModalInternal()` to call `populateCourseDepartments()`

**Action Items:**

- [ ] Backup current `Modals/AddCourseModal.php`
- [ ] Replace hardcoded department list
- [ ] Add populateCourseDepartments() function
- [ ] Update modal open function
- [ ] Test department loading
- [ ] Test course creation

---

### Step 15: Testing & Validation

**Comprehensive Testing Checklist:**

1. **Department Creation:**

   - [ ] Create single-sector department (College)
   - [ ] Create single-sector department (Faculty)
   - [ ] Create shared department (College & Faculty)
   - [ ] Create shared department (All Sectors)
   - [ ] Verify department code uniqueness validation
   - [ ] Verify departments are created in correct sectors

2. **Program Head Assignment:**

   - [ ] Assign Program Head to College ICT
   - [ ] Verify they can see Faculty ICT in their management pages
   - [ ] Assign Program Head to Faculty ICT
   - [ ] Verify they can see College ICT in their management pages
   - [ ] Test with faculty-only department (should not appear for College/SHS PH)

3. **Access Control:**

   - [ ] Program Head assigned to College ICT can view College students in ICT
   - [ ] Program Head assigned to College ICT can view Faculty members in ICT
   - [ ] Program Head can act as signatory for both College and Faculty clearances
   - [ ] Verify `is_assigned.php` returns correct permissions

4. **UI/UX:**

   - [ ] Program Head assignment modal shows cross-sector information
   - [ ] Department creation modal shows sector info
   - [ ] Summary updates in real-time
   - [ ] Transfer toggle works correctly

5. **Edge Cases:**
   - [ ] Program Head with no departments sees nothing
   - [ ] Faculty-only departments don't appear for student sector Program Heads
   - [ ] Department code validation prevents duplicates
   - [ ] Existing single-sector departments still work

**Action Items:**

- [ ] Complete all testing checklist items
- [ ] Document any issues found
- [ ] Fix any bugs discovered
- [ ] Re-test after fixes

---

## Implementation Order Summary

**Recommended Implementation Order:**

1. **Phase 1: Backend Foundation** (Steps 1-3)

   - Create helper functions
   - Create department creation API
   - Update departments list API

2. **Phase 2: Access Control** (Steps 4-10)

   - Update all Program Head access control endpoints
   - Test each endpoint individually

3. **Phase 3: UI Updates** (Steps 11-14)

   - Update Program Head assignment modals
   - Update department creation modal
   - Update course creation modal

4. **Phase 4: Testing** (Step 15)
   - Comprehensive testing
   - Bug fixes
   - Final validation

---

**Note:** Always backup files before making changes. Test each step before moving to the next.

---

## Database Considerations

### Current Schema

- `departments` table has:
  - `department_id` (primary key)
  - `department_name`
  - `department_code`
  - `department_type` (enum: 'College', 'Senior High School', 'Faculty')
  - `sector_id` (foreign key to sectors table)
  - `is_active`

### Important: No New Tables Needed ✅

**The existing database structure already supports cross-sector departments:**

- Multiple rows can have the same `department_name` and `department_code` with different `sector_id`
- Example:
  - Row 1: `department_id=44, department_name='ICT', department_code='ICT', sector_id=1` (College)
  - Row 2: `department_id=X, department_name='ICT', department_code='ICT', sector_id=3` (Faculty)

**What changes:**

- ❌ **No schema changes needed**
- ❌ **No new tables needed**
- ✅ **Only application logic changes** (queries and UI)

### Constraints

- `department_code` should be unique across all sectors (enforced in application logic)
- `department_name` + `sector_id` should be unique (if unique constraint exists)
- For shared departments, same `department_name` and `department_code` exist in multiple rows with different `sector_id`

### Validation Rules

1. **Department code uniqueness:** Check across all sectors before creation
2. **Shared departments:** Must have identical `department_name` and `department_code` across sectors
3. **Faculty-only departments:** Cannot be assigned to College/SHS Program Heads

---

## Notes

### Important Considerations

1. **Backward Compatibility:** Existing single-sector departments continue to work
2. **Migration:** No data migration needed if departments are already properly structured
3. **Performance:** Cross-sector queries may need optimization with proper indexes
4. **Validation:** Ensure department code validation prevents duplicates across sectors

### Testing Checklist

- [ ] Create shared department (College & Faculty)
- [ ] Create faculty-only department
- [ ] Assign Program Head to College ICT, verify access to Faculty ICT
- [ ] Assign Program Head to Faculty ICT, verify access to College ICT
- [ ] Verify Program Head can manage students and faculty in assigned departments
- [ ] Verify Program Head can act as signatory for cross-sector clearances
- [ ] Test department code uniqueness validation
- [ ] Test edit department functionality
- [ ] Test course creation with cross-sector departments

---

## Backup Checklist

Before making changes, backup these files:

### Critical Files (Must Backup)

1. ✅ `Modals/StaffRegistryModal.php`
2. ✅ `Modals/EditStaffModal.php`
3. ✅ `Modals/AddDepartmentModal.php`
4. ✅ `Modals/AddCourseModal.php`
5. ✅ `api/departments/list.php`
6. ✅ `api/program-head/is_assigned.php`
7. ✅ `api/clearance/signatoryList.php`
8. ✅ `api/program-head/college_students.php`
9. ✅ `api/program-head/shs_students.php`
10. ✅ `controllers/FacultyManagementController.php`
11. ✅ `controllers/StudentManagementController.php`
12. ✅ `api/staff/list.php`
13. ✅ `api/signatories/bulk_assign.php`

### Database Backup

- ✅ Backup `departments` table
- ✅ Backup `user_department_assignments` table
- ✅ Backup `staff_department_assignments` table (if still in use)

---

## Summary

### What We're Implementing

1. **Cross-sector department matching** using `department_name` and `department_code`
2. **Simplified Program Head assignment UI** (Option A - text-based)
3. **Enhanced department creation** with shared department support
4. **Updated access control** for Program Heads across all sectors

### What We're NOT Changing

- ❌ Database schema (no new tables)
- ❌ Existing table structures
- ❌ Core data model

### What We're ONLY Changing

- ✅ Query logic (application layer)
- ✅ UI/UX (frontend)
- ✅ Access control logic (backend)

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-XX  
**Status:** Planning Phase - Ready for Implementation
