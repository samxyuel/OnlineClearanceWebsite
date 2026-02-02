# Cross-Sector Department Management & Faculty Department Column - Complete Implementation Summary

**Created:** January 2025  
**Status:** ✅ Implementation Complete  
**Purpose:** Comprehensive reference document for Cross-Sector Department Management and Faculty Department Column features

---

## Table of Contents

1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [Key Concepts](#key-concepts)
4. [Implementation Changes](#implementation-changes)
5. [Important Files](#important-files)
6. [Code Examples](#code-examples)
7. [Faculty Department Column Implementation](#faculty-department-column-implementation)
8. [Considerations for CourseManagement.php](#considerations-for-coursemanagementphp)

---

## Overview

### Business Requirements

- Departments can exist across multiple sectors (College, Senior High School, Faculty)
- Departments share the same `department_name` and `department_code` across sectors
- Program Heads assigned to a department in one sector can manage that department across **all sectors**
- Faculty members can be assigned to multiple departments via `user_department_assignments`
- Department code must be unique across all sectors

### Key Principle

**Program Head access is department-scoped (by name/code), not sector-scoped.** If assigned to "ICT" in any sector, they manage all "ICT" members across all sectors where ICT exists.

### Example Scenario

- Program Head assigned to **College ICT** can manage:
  - College students in ICT
  - Faculty members in ICT (Faculty sector)
  - Clearance forms for both sectors
- Shared departments use the same `department_name` and `department_code` across sectors
- Faculty-only departments (e.g., General Education) exist only in Faculty sector
- Student departments (College/SHS) are also accessible to faculty

---

## Database Structure

### 1. `departments` Table

```sql
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,              -- Primary key (unique per sector)
  `department_name` varchar(100) NOT NULL,       -- Shared across sectors
  `department_code` varchar(10) DEFAULT NULL,    -- Shared across sectors, must be unique
  `department_type` enum('College','Senior High School','Faculty') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sector_id` int(11) DEFAULT NULL              -- Links to sectors table (1=College, 2=SHS, 3=Faculty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Important Notes:**

- Multiple rows can have the same `department_name` and `department_code` with different `sector_id`
- Example: ICT department exists as:
  - `department_id=44, department_name='ICT', department_code='ICT', sector_id=1` (College)
  - `department_id=X, department_name='ICT', department_code='ICT', sector_id=3` (Faculty)
- Each sector instance has its own `department_id` but shares name/code

### 2. `user_department_assignments` Table

```sql
CREATE TABLE `user_department_assignments` (
  `department_assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,                   -- Foreign key to users table
  `department_id` int(11) NOT NULL,             -- Foreign key to departments table
  `sector_id` int(11) DEFAULT NULL,             -- For sector-wide assignments (optional)
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,   -- Primary department flag
  `is_active` tinyint(1) NOT NULL DEFAULT 1     -- Active assignment flag
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Important Notes:**

- One user can have multiple department assignments (many-to-many relationship)
- `is_primary` flag indicates the primary department (used for ordering in displays)
- `is_active` flag allows soft deletion of assignments
- Used for both Program Head assignments and Faculty department assignments

### 3. Database Schema Considerations

- ✅ **No schema changes needed** - Implementation uses existing tables
- Uses `departments.department_name` and `departments.department_code` for matching
- Uses `user_department_assignments` for Program Head assignments and Faculty department assignments
- Multiple `department_id` values can share the same name/code across sectors

---

## Key Concepts

### Cross-Sector Department Matching

Departments are matched across sectors using:

1. **Primary:** `department_name`
2. **Fallback:** `department_code`

**Matching Logic:**

- If a Program Head is assigned to "ICT" department (department_id=44 in College sector)
- System finds all departments with `department_name = 'ICT'` OR `department_code = 'ICT'`
- Returns department IDs across all sectors (College, SHS, Faculty)

### Department Creation Types

When creating a department, the system supports:

| Type              | Sectors Created | Sector IDs        | Description               |
| ----------------- | --------------- | ----------------- | ------------------------- |
| `college`         | [1]             | College only      | Single-sector department  |
| `senior-high`     | [2]             | SHS only          | Single-sector department  |
| `faculty`         | [3]             | Faculty only      | Single-sector department  |
| `college-faculty` | [1, 3]          | College + Faculty | Shared department         |
| `shs-faculty`     | [2, 3]          | SHS + Faculty     | Shared department         |
| `all-sectors`     | [1, 2, 3]       | All sectors       | Shared across all sectors |

**Important:** When creating a shared department (e.g., `college-faculty`), the system creates **separate department records** in each sector with the **same name and code**.

**Example:**

- Creating "ICT" as `college-faculty` creates:
  - Row 1: `department_id=44, department_name='ICT', department_code='ICT', sector_id=1` (College)
  - Row 2: `department_id=50, department_name='ICT', department_code='ICT', sector_id=3` (Faculty)

### Sector IDs Reference

- **1** = College
- **2** = Senior High School (SHS)
- **3** = Faculty

---

## Implementation Changes

### Phase 1: Backend Foundation

#### 1.1 Helper Functions (`includes/helpers/department_helpers.php`)

Created helper functions for cross-sector matching:

**Function: `getCrossSectorDepartmentIds($pdo, $userId)`**

Gets all department IDs that match a Program Head's assigned departments across all sectors.

```php
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
```

**Function: `getDepartmentIdentifiers($pdo, $departmentIds)`**

Gets department identifiers (name/code) from department IDs.

```php
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
```

**Function: `checkDepartmentExistsInSector($pdo, $deptName, $deptCode, $sectorId)`**

Checks if a department exists in a specific sector.

```php
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
```

**Function: `getDepartmentSectors($pdo, $deptName, $deptCode)`**

Gets all sectors where a department exists.

```php
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

#### 1.2 Department Creation API (`api/departments/create.php`)

**Key Features:**

- Validates department code uniqueness across all sectors
- Creates department records in multiple sectors based on type
- Maps department type to sector IDs

**Key Code Sections:**

**Validation - Check Department Code Uniqueness:**

```php
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
```

**Sector Mapping:**

```php
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
```

**Create Department in Multiple Sectors:**

```php
// Create department in each sector
foreach ($sectors as $sectorId) {
    $insertStmt = $pdo->prepare("
        INSERT INTO departments
        (department_name, department_code, department_type, sector_id, is_active)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([$name, $code, $deptType, $sectorId, $isActive]);
}
```

#### 1.3 Departments List API (`api/departments/list.php`)

Returns departments with cross-sector information.

**Key Implementation:**

**Grouping Logic:**

```php
// Groups departments by name/code to detect cross-sector departments
$grouped = [];
foreach ($allRows as $dept) {
    $key = $dept['department_code'] ?: $dept['department_name'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'department_name' => $dept['department_name'],
            'department_code' => $dept['department_code'],
            'sectors' => [],
            'is_shared' => false
        ];

        // Include Program Head info if requested (use first occurrence)
        if ($includePH) {
            $grouped[$key]['current_program_head_user_id'] = $dept['current_program_head_user_id'] ?? null;
            $grouped[$key]['current_program_head_name'] = $dept['current_program_head_name'] ?? null;
            $grouped[$key]['current_program_head_designation'] = $dept['current_program_head_designation'] ?? null;
            $grouped[$key]['current_program_head_employee_number'] = $dept['current_program_head_employee_number'] ?? null;
        }
    }

    $grouped[$key]['sectors'][] = [
        'sector_id' => (int)$dept['sector_id'],
        'sector_name' => $dept['sector_name'],
        'department_id' => (int)$dept['department_id']
    ];
}

// Mark as shared if multiple sectors
foreach ($grouped as &$group) {
    $group['is_shared'] = count($group['sectors']) > 1;
}
unset($group); // Break reference
```

**API Response Format:**

```json
{
  "success": true,
  "departments": [
    {
      "department_name": "ICT",
      "department_code": "ICT",
      "is_shared": true,
      "sectors": [
        {
          "sector_id": 1,
          "sector_name": "College",
          "department_id": 44
        },
        {
          "sector_id": 3,
          "sector_name": "Faculty",
          "department_id": 50
        }
      ]
    }
  ],
  "page": 1,
  "limit": 100,
  "total": 1,
  "total_pages": 1
}
```

### Phase 2: Program Head Access Control

All Program Head access endpoints were updated to use `getCrossSectorDepartmentIds()` instead of direct `department_id` matching.

**Files Updated:**

- `api/program-head/is_assigned.php`
- `api/clearance/signatoryList.php`
- `api/program-head/college_students.php`
- `api/program-head/shs_students.php`
- `controllers/FacultyManagementController.php`
- `controllers/StudentManagementController.php`
- `api/staff/list.php`

**Standard Pattern:**

```php
require_once '../../includes/helpers/department_helpers.php';

// Get cross-sector department IDs
$crossSectorDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

if (empty($crossSectorDeptIds)) {
    // No access - return empty result
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Use in WHERE clause
$placeholders = implode(',', array_fill(0, count($crossSectorDeptIds), '?'));
$stmt = $pdo->prepare("
    SELECT * FROM students
    WHERE department_id IN ($placeholders)
");
$stmt->execute($crossSectorDeptIds);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Phase 3: UI Updates

#### Program Head Assignment Modals

**Files Updated:**

- `Modals/StaffRegistryModal.php` - Simplified cross-sector department selection
- `Modals/EditStaffModal.php` - Matching UI and functionality

**Key Features:**

- Unified department list with cross-sector indicators
- Real-time department selection summary
- Shows which sectors each department is available in
- Example: "Available in: College, Faculty"

#### Department Creation Modal

**File Updated:**

- `Modals/AddDepartmentModal.php`

**Key Features:**

- Department code field with validation
- Multi-sector department type selection
- Dynamic info text showing which sectors will be created
- Validation for department code uniqueness

#### Course Creation Modal

**File Updated:**

- `Modals/AddCourseModal.php`

**Key Features:**

- Dynamic department loading from API
- Cross-sector indicators in dropdown
- Sector grouping in dropdown

---

## Important Files

### Created Files

1. **`includes/helpers/department_helpers.php`**

   - Cross-sector matching helper functions
   - All 4 helper functions documented above

2. **`api/departments/create.php`**

   - Department creation endpoint with cross-sector support
   - Validates department code uniqueness
   - Creates departments in multiple sectors

3. **`docs/Cross_Sector_Department_Management_Plan.md`**

   - Comprehensive implementation plan
   - Detailed requirements and approach

4. **`docs/Implementation_Summary.md`**

   - Implementation completion summary
   - Testing information

5. **`docs/Phase5_Testing_Checklist.md`**

   - Comprehensive testing checklist
   - Test scenarios and expected results

6. **`tools/test_department_helpers.php`**

   - Command-line test script for helper functions

7. **`docs/FacultyDepartmentColumn_Implementation_Plan.md`**
   - Faculty Department Column implementation plan
   - Specific changes made to display departments

### Modified Files

**Backend APIs:**

- `api/departments/list.php` - Added cross-sector grouping (`is_shared`, `sectors`)
- `api/program-head/is_assigned.php` - Cross-sector department matching
- `api/clearance/signatoryList.php` - Cross-sector filtering
- `api/program-head/college_students.php` - Cross-sector filtering
- `api/program-head/shs_students.php` - Cross-sector filtering
- `api/staff/list.php` - Cross-sector filtering
- `api/users/facultyList.php` - Added GROUP_CONCAT for departments (see Faculty Column section)

**Controllers:**

- `controllers/FacultyManagementController.php` - Cross-sector department filtering
- `controllers/StudentManagementController.php` - Cross-sector department filtering

**UI Modals:**

- `Modals/StaffRegistryModal.php` - Cross-sector department selection UI
- `Modals/EditStaffModal.php` - Cross-sector department selection UI
- `Modals/AddDepartmentModal.php` - Multi-sector department creation
- `Modals/AddCourseModal.php` - Cross-sector department loading

**Frontend Pages:**

- `pages/admin/FacultyManagement.php` - Added Department(s) column
- `pages/program-head/FacultyManagement.php` - Added Department(s) column
- `pages/regular-staff/FacultyManagement.php` - Added Department(s) column
- `pages/school-administrator/FacultyManagement.php` - Added Department(s) column

**Course Management:**

- `api/course_data.php` - Returns departments grouped by sector (may need review)
- `pages/admin/CourseManagement.php` - Department display (needs updates - see considerations section)

---

## Code Examples

### Example 1: Get Departments for Course Dropdown

**API Endpoint:** `GET /api/departments/list.php`

**Response Format:**

```json
{
  "success": true,
  "departments": [
    {
      "department_name": "ICT",
      "department_code": "ICT",
      "is_shared": true,
      "sectors": [
        {
          "sector_id": 1,
          "sector_name": "College",
          "department_id": 44
        },
        {
          "sector_id": 3,
          "sector_name": "Faculty",
          "department_id": 50
        }
      ]
    }
  ]
}
```

**Usage in JavaScript:**

```javascript
// Fetch departments
const response = await fetch("../../api/departments/list.php", {
  credentials: "include",
});
const data = await response.json();

// Populate dropdown with cross-sector info
data.departments.forEach((dept) => {
  const option = document.createElement("option");
  option.value = dept.sectors[0].department_id; // Use first sector's ID
  option.textContent = `${dept.department_name} (${dept.department_code})`;
  if (dept.is_shared) {
    option.textContent += ` - Available in: ${dept.sectors
      .map((s) => s.sector_name)
      .join(", ")}`;
  }
  dropdown.appendChild(option);
});
```

### Example 2: Filter by Cross-Sector Departments (Program Head)

**Full Implementation Pattern:**

```php
<?php
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/helpers/department_helpers.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = $auth->getUserId();

// Get cross-sector department IDs
$crossSectorDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

if (empty($crossSectorDeptIds)) {
    // No departments assigned - return empty result
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Build query with cross-sector department IDs
$placeholders = implode(',', array_fill(0, count($crossSectorDeptIds), '?'));
$stmt = $pdo->prepare("
    SELECT
        s.student_id,
        s.student_number,
        u.first_name,
        u.last_name,
        d.department_name
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN departments d ON s.department_id = d.department_id
    WHERE s.department_id IN ($placeholders)
    AND s.is_active = 1
    ORDER BY u.last_name, u.first_name
");
$stmt->execute($crossSectorDeptIds);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $results
]);
?>
```

### Example 3: Check Department Exists in Sector

```php
require_once 'includes/helpers/department_helpers.php';

$pdo = Database::getInstance()->getConnection();

// Check if ICT exists in College sector
$exists = checkDepartmentExistsInSector(
    $pdo,
    'Information & Communication Technology',  // department_name
    'ICT',                                     // department_code
    1                                          // sector_id (1=College)
);

if ($exists) {
    echo "ICT exists in College sector";
} else {
    echo "ICT does not exist in College sector";
}
```

### Example 4: Get All Sectors for a Department

```php
require_once 'includes/helpers/department_helpers.php';

$pdo = Database::getInstance()->getConnection();

// Get all sectors where ICT exists
$sectors = getDepartmentSectors($pdo, 'Information & Communication Technology', 'ICT');

foreach ($sectors as $sector) {
    echo "ICT exists in {$sector['sector_name']} (sector_id: {$sector['sector_id']}, dept_id: {$sector['department_id']})\n";
}
```

---

## Faculty Department Column Implementation

### Overview

Added "Department(s)" column to Faculty Management tables to display all assigned departments for each faculty member as a comma-separated list (e.g., `BSIT, BSCS, BSCE`).

**Implementation Date:** December 2024  
**Display Format:** Simple comma-separated list with primary department first

### Database Changes

**None** - Uses existing `user_department_assignments` table.

### Code Changes

#### 1. Backend API (`api/users/facultyList.php`)

**Changed SQL Query:**

**Before:**

```php
$baseQuery = "
    FROM faculty f
    JOIN users u ON f.user_id = u.user_id
    LEFT JOIN departments d ON f.department_id = d.department_id
    $clearanceFormJoin
";
$selectFields = "
    f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name,
    u.email, u.contact_number, d.department_name, f.employment_status, u.account_status,
    u.created_at as user_created_at,
    COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
";
```

**After:**

```php
$baseQuery = "
    FROM faculty f
    JOIN users u ON f.user_id = u.user_id
    LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id AND uda.is_active = 1
    LEFT JOIN departments d ON uda.department_id = d.department_id
    $clearanceFormJoin
";
$selectFields = "
    f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name,
    u.email, u.contact_number,
    GROUP_CONCAT(DISTINCT d.department_name ORDER BY uda.is_primary DESC SEPARATOR ', ') as departments,
    f.employment_status, u.account_status,
    u.created_at as user_created_at,
    COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
";
```

**Key Changes:**

1. Changed JOIN from `departments` via `faculty.department_id` to `user_department_assignments` table
2. Added `GROUP_CONCAT(DISTINCT d.department_name ORDER BY uda.is_primary DESC SEPARATOR ', ')` to combine department names
3. Primary department appears first due to `ORDER BY uda.is_primary DESC`

**Added GROUP BY Clause:**

```php
// Get paginated data with GROUP BY for aggregation
$groupBy = "GROUP BY f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name, u.email, u.contact_number, f.employment_status, u.account_status, u.created_at, cf.clearance_form_progress";
$dataStmt = $pdo->prepare("SELECT $selectFields $baseQuery $where $groupBy ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
```

**Updated COUNT Queries:**

```php
// Changed from COUNT(*) to COUNT(DISTINCT u.user_id) to handle multiple department assignments
$countStmt = $pdo->prepare("SELECT COUNT(DISTINCT u.user_id) $baseQuery $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Also updated statistics query
$statsQuery = "SELECT u.account_status, COUNT(DISTINCT u.user_id) as count $baseQuery $where GROUP BY u.account_status";
```

**Updated Department Filter:**

```php
// Changed from f.department_id to uda.department_id
if ($departmentId) {
    $where .= " AND uda.department_id = :departmentId";
    $params[':departmentId'] = $departmentId;
}
```

#### 2. Frontend Changes

**All 4 Faculty Management pages updated:**

1. **`pages/admin/FacultyManagement.php`**
2. **`pages/program-head/FacultyManagement.php`**
3. **`pages/regular-staff/FacultyManagement.php`**
4. **`pages/school-administrator/FacultyManagement.php`**

**Changes Made:**

**1. Added Table Header:**

```html
<th>Department(s)</th>
```

**Placement:** After "Name" column, before "Employment Status"

**2. Added Table Cell in JavaScript Row Generation:**

**Admin Page Example:**

```javascript
tr.innerHTML = `<td class="checkbox-column"><input type=\"checkbox\" class=\"faculty-checkbox\" data-id=\"${
  f.employee_number
}\"></td>
            <td data-label="Employee Number:">${f.employee_number}</td>
            <td data-label="Name:">${f.first_name} ${f.last_name}</td>
            <td data-label="Department(s):">${f.departments || "N/A"}</td>
            <td data-label="Employment Status:"><span class="status-badge employment-${(
              f.employment_status || ""
            )
              .toLowerCase()
              .replace(/ /g, "-")}">${f.employment_status}</span></td>
            ...`;
```

**Other Pages Example (with escapeHtml):**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Department(s):">${escapeHtml(
      faculty.departments || "N/A"
    )}</td>
    <td data-label="Employment Status:">...</td>
    ...`;
```

**3. Updated colspan Values:**

| Page                 | Old colspan | New colspan | Notes                                                   |
| -------------------- | ----------- | ----------- | ------------------------------------------------------- |
| Admin                | 7           | 8           | Added Department(s) column                              |
| Program Head         | 7           | 8           | Added Department(s) column                              |
| Regular Staff        | 7           | 8           | Added Department(s) column                              |
| School Administrator | 7 → 8       | 8 → 9       | Added Department(s) column, has Clearance Status column |

**All occurrences updated:**

- Loading state messages
- Error state messages
- Empty state messages

**Example:**

```javascript
// Before
tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading faculty...</td></tr>`;

// After
tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;">Loading faculty...</td></tr>`;
```

### Data Flow

```
Database (user_department_assignments)
    ↓
LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id AND uda.is_active = 1
LEFT JOIN departments d ON uda.department_id = d.department_id
    ↓
GROUP_CONCAT(DISTINCT d.department_name ORDER BY uda.is_primary DESC SEPARATOR ', ')
    → "BSIT, BSCS, BSCE"  (primary department first)
    ↓
API Response: {
    faculty: [
        {
            employee_number: "EMP001",
            first_name: "John",
            last_name: "Doe",
            departments: "BSIT, BSCS, BSCE",
            ...
        }
    ]
}
    ↓
Frontend JavaScript: ${f.departments || 'N/A'}
    ↓
Table Cell: <td>BSIT, BSCS, BSCE</td>
```

### Column Order After Implementation

| ☑   | Employee # | Name | **Department(s)** | Employment Status | Account Status | Clearance Progress | [Clearance Status] | Actions |
| --- | ---------- | ---- | ----------------- | ----------------- | -------------- | ------------------ | ------------------ | ------- |

**Note:** "Clearance Status" column only appears on Program Head, Regular Staff, and School Administrator pages.

### Key Technical Notes

1. **GROUP_CONCAT Function:** Aggregates multiple rows into one comma-separated string
2. **ORDER BY is_primary DESC:** Ensures the primary department appears first in the list
3. **GROUP BY Clause:** Required when using aggregate functions - must include all non-aggregated columns
4. **Fallback to 'N/A':** Handles faculty with no department assignments: `${f.departments || 'N/A'}`
5. **COUNT DISTINCT:** Changed to `COUNT(DISTINCT u.user_id)` to avoid duplicate counting due to multiple department assignments
6. **The plan removes direct reliance on `faculty.department_id`** in favor of the more flexible `user_department_assignments` table

### Benefits

- ✅ Displays **all** departments a faculty member is assigned to
- ✅ Shows **primary department first** for clarity
- ✅ Consistent display format across all role-based pages
- ✅ Uses the proper multi-assignment data model (`user_department_assignments`) instead of the legacy single-department field
- ✅ Handles faculty with no department assignments gracefully (shows "N/A")

---

## Considerations for CourseManagement.php

### Current State

The `pages/admin/CourseManagement.php` file currently:

- Displays departments grouped by sector (College, SHS, Faculty)
- Uses `api/course_data.php` to fetch department data
- Shows departments in separate tabs per sector
- Displays department cards with course information

### Recommended Changes

#### 1. Department Display

**Considerations:**

- Display cross-sector information in department cards
- Show which sectors each department exists in
- Indicate shared departments with visual indicators

**Example Enhancement:**

```javascript
// In department card creation function
function createDepartmentCard(department, sectorKey) {
  const card = document.createElement("div");
  card.className = "department-card";

  // Add cross-sector indicator if department is shared
  if (
    department.is_shared &&
    department.sectors &&
    department.sectors.length > 1
  ) {
    const sectorsLabel = department.sectors
      .map((s) => s.sector_name)
      .join(", ");
    const badge = document.createElement("span");
    badge.className = "cross-sector-badge";
    badge.textContent = `Available in: ${sectorsLabel}`;
    card.appendChild(badge);
  }

  // ... rest of card creation
}
```

#### 2. Department Registry/Display

**Key Points:**

- Departments should show all sectors they exist in
- When displaying a department, show all `department_id`s for that department name/code
- Filter departments by sector for display purposes, but maintain cross-sector awareness

**SQL Pattern for CourseManagement:**

```php
// Get all departments with cross-sector info
$stmt = $pdo->prepare("
    SELECT
        d.department_id,
        d.department_name,
        d.department_code,
        d.sector_id,
        s.sector_name,
        COUNT(*) OVER (PARTITION BY COALESCE(d.department_code, d.department_name)) as sector_count
    FROM departments d
    JOIN sectors s ON d.sector_id = s.sector_id
    WHERE d.is_active = 1
    ORDER BY d.department_name, s.sector_name
");

// Or use the existing api/departments/list.php which already handles this
```

**Alternative: Use Existing API**

Since `api/departments/list.php` already returns cross-sector information, you can use it:

```javascript
// Fetch departments with cross-sector info
const response = await fetch("../../api/departments/list.php", {
  credentials: "include",
});
const data = await response.json();

// Group by sector for display
const departmentsBySector = {};
data.departments.forEach((dept) => {
  dept.sectors.forEach((sector) => {
    if (!departmentsBySector[sector.sector_name]) {
      departmentsBySector[sector.sector_name] = [];
    }
    departmentsBySector[sector.sector_name].push({
      ...dept,
      current_sector_id: sector.sector_id,
      current_department_id: sector.department_id,
    });
  });
});
```

#### 3. Department Update

**Considerations:**

- When updating a department name/code, update ALL sectors
- Ensure department code uniqueness across all sectors
- Update logic should handle:
  - Single sector update
  - Multi-sector update
  - Sector addition/removal

**Update Pattern:**

```php
// 1. Get all departments with same name/code
$stmt = $pdo->prepare("
    SELECT department_id, sector_id
    FROM departments
    WHERE (department_name = ? OR department_code = ?)
    AND is_active = 1
");
$stmt->execute([$oldName, $oldCode]);
$relatedDepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Update all matching departments
$updateStmt = $pdo->prepare("
    UPDATE departments
    SET department_name = ?, department_code = ?, updated_at = NOW()
    WHERE department_id = ?
");

foreach ($relatedDepts as $dept) {
    $updateStmt->execute([$newName, $newCode, $dept['department_id']]);
}
```

**Important:** When updating a department code, validate uniqueness:

```php
// Check if new code conflicts with existing departments (excluding current department)
$checkStmt = $pdo->prepare("
    SELECT department_id
    FROM departments
    WHERE department_code = ?
    AND is_active = 1
    AND department_id NOT IN (SELECT department_id FROM departments WHERE department_name = ? OR department_code = ?)
");
$checkStmt->execute([$newCode, $oldName, $oldCode]);
$conflicts = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($conflicts)) {
    // Department code conflict
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Department code already exists']);
    exit;
}
```

#### 4. Department Delete

**Considerations:**

- When deleting a department, decide:
  - Delete from all sectors? (default behavior for cross-sector departments)
  - Delete from specific sector only? (if you want to remove department from one sector)
- Consider cascading deletes:
  - Courses/programs in that department
  - User assignments (`user_department_assignments`)
  - Program Head assignments

**Delete Pattern - Option 1: Delete from All Sectors (Soft Delete)**

```php
// Get all departments with same name/code
$stmt = $pdo->prepare("
    SELECT department_id
    FROM departments
    WHERE (department_name = ? OR department_code = ?)
    AND is_active = 1
");
$stmt->execute([$deptName, $deptCode]);
$deptIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Soft delete all matching departments
$deleteStmt = $pdo->prepare("
    UPDATE departments
    SET is_active = 0, updated_at = NOW()
    WHERE department_id = ?
");

foreach ($deptIds as $deptId) {
    $deleteStmt->execute([$deptId]);
}

// Also deactivate related assignments
$assignStmt = $pdo->prepare("
    UPDATE user_department_assignments
    SET is_active = 0
    WHERE department_id IN (" . implode(',', array_fill(0, count($deptIds), '?')) . ")
");
$assignStmt->execute($deptIds);
```

**Delete Pattern - Option 2: Delete from Specific Sector Only**

```php
// Delete specific department (one sector only)
$deleteStmt = $pdo->prepare("
    UPDATE departments
    SET is_active = 0, updated_at = NOW()
    WHERE department_id = ?
");
$deleteStmt->execute([$departmentId]);

// Also deactivate related assignments for this specific department
$assignStmt = $pdo->prepare("
    UPDATE user_department_assignments
    SET is_active = 0
    WHERE department_id = ?
");
$assignStmt->execute([$departmentId]);
```

**Important:** Consider impact on courses/programs before deleting:

```php
// Check if department has courses/programs
$checkStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM courses
    WHERE department_id = ? AND is_active = 1
");
$checkStmt->execute([$departmentId]);
$courseCount = $checkStmt->fetchColumn();

if ($courseCount > 0) {
    // Handle courses - either delete them or prevent deletion
    // Decision depends on business requirements
}
```

#### 5. Course Registry/Display

**Considerations:**

- Courses belong to a specific `department_id` (not cross-sector)
- When displaying courses, show the department name/code
- Filter courses by department, considering cross-sector matching if needed for Program Heads

**SQL Pattern:**

```php
// Get courses with department info
$stmt = $pdo->prepare("
    SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.department_id,
        d.department_name,
        d.department_code,
        d.sector_id,
        s.sector_name
    FROM courses c
    JOIN departments d ON c.department_id = d.department_id
    JOIN sectors s ON d.sector_id = s.sector_id
    WHERE c.is_active = 1
    AND d.is_active = 1
    ORDER BY d.department_name, c.course_code
");
```

**For Program Head Filtering:**

```php
// Use cross-sector department IDs if filtering by Program Head
if ($programHeadUserId) {
    require_once 'includes/helpers/department_helpers.php';
    $crossSectorDeptIds = getCrossSectorDepartmentIds($pdo, $programHeadUserId);

    if (!empty($crossSectorDeptIds)) {
        $placeholders = implode(',', array_fill(0, count($crossSectorDeptIds), '?'));
        $where .= " AND c.department_id IN ($placeholders)";
        $params = array_merge($params, $crossSectorDeptIds);
    } else {
        // No access
        return [];
    }
}
```

#### 6. Course Update/Delete

**Considerations:**

- Courses are tied to specific `department_id`
- Updating/deleting a course only affects that specific course
- No cross-sector considerations needed for courses themselves
- However, when updating a course's department, consider if the new department should be in the same sector

**Update Pattern:**

```php
// Update course
$updateStmt = $pdo->prepare("
    UPDATE courses
    SET course_code = ?, course_name = ?, department_id = ?, updated_at = NOW()
    WHERE course_id = ?
");
$updateStmt->execute([$courseCode, $courseName, $departmentId, $courseId]);
```

**Department Change Validation:**

```php
// Validate that new department is in appropriate sector (if sector matters for courses)
$deptStmt = $pdo->prepare("
    SELECT sector_id FROM departments WHERE department_id = ?
");
$deptStmt->execute([$departmentId]);
$sectorId = $deptStmt->fetchColumn();

// Check if sector is valid for course (business logic)
if ($sectorId !== $expectedSectorId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid department for this course']);
    exit;
}
```

### Key API Endpoints to Review

1. **`api/course_data.php`** - Returns departments grouped by sector

   - **Review:** How it handles cross-sector departments
   - **May need:** Updates to include cross-sector indicators
   - **Check:** If it groups by sector correctly

2. **`api/departments/list.php`** - Already returns cross-sector info

   - **Use:** For department dropdowns and lists
   - **Includes:** `is_shared` and `sectors` fields
   - **Returns:** Grouped departments with cross-sector information

3. **`api/departments/create.php`** - Creates departments across sectors

   - **Reference:** For update/delete patterns
   - **Shows:** How to handle multi-sector operations

4. **`api/departments/update.php`** - (May need to create)

   - **Should:** Update all sectors if name/code changes
   - **Should:** Validate department code uniqueness

5. **`api/departments/delete.php`** - (May need to create)
   - **Should:** Handle soft delete (is_active = 0)
   - **Should:** Deactivate related assignments
   - **Should:** Consider cascading to courses

### Testing Checklist for CourseManagement Updates

- [ ] Department cards show cross-sector indicators (badges/labels)
- [ ] Department update updates all sectors when name/code changes
- [ ] Department delete handles cross-sector departments correctly
- [ ] Department delete deactivates related user assignments
- [ ] Course creation uses correct `department_id` from selected sector
- [ ] Course filtering works correctly with cross-sector departments
- [ ] Program Head filtering works with cross-sector departments
- [ ] Department code validation prevents duplicates across sectors
- [ ] UI clearly shows which sectors each department exists in
- [ ] Search functionality works with cross-sector departments
- [ ] Department statistics count correctly with cross-sector departments

### UI/UX Recommendations

1. **Visual Indicators:**

   - Add badge/icon for shared departments
   - Show sector list: "Available in: College, Faculty"
   - Different styling for single-sector vs. shared departments

2. **Department Cards:**

   - Display sector information prominently
   - Show count of courses per sector (if applicable)
   - Add tooltip showing all sectors

3. **Department Actions:**

   - Update action should clarify it updates all sectors
   - Delete action should clarify scope (all sectors vs. one sector)
   - Add confirmation dialogs explaining cross-sector impact

4. **Course-Department Relationship:**
   - Show department sector in course listings
   - Filter courses by sector within department
   - Group courses by sector in department view

---

## Summary of Key Points

### 1. Database Structure

- **`departments` table:** Supports cross-sector via `department_name` and `department_code`
- **`user_department_assignments` table:** Enables multi-department assignments
- **No schema changes needed:** Implementation uses existing tables

### 2. Cross-Sector Matching

- **Primary identifier:** `department_name`
- **Fallback identifier:** `department_code`
- **Implementation:** `getCrossSectorDepartmentIds()` helper function
- **Result:** Program Heads assigned to a department get access across all sectors

### 3. Department Creation

- **Can create in multiple sectors:** Simultaneously
- **Same name/code:** Across sectors
- **Department code:** Must be unique globally
- **Implementation:** `api/departments/create.php`

### 4. Program Head Access

- **Scope:** Department-scoped, not sector-scoped
- **Access:** Automatic cross-sector when assigned to a department
- **Implementation:** All Program Head endpoints use `getCrossSectorDepartmentIds()`

### 5. Faculty Departments

- **Display:** All assigned departments using `GROUP_CONCAT`
- **Ordering:** Primary department shown first
- **Source:** `user_department_assignments` table
- **Format:** Comma-separated list (e.g., "BSIT, BSCS, BSCE")

### 6. CourseManagement Considerations

- **Display:** Cross-sector indicators in department cards
- **Update:** Handle multi-sector updates when name/code changes
- **Delete:** Consider scope (all sectors vs. one sector)
- **Courses:** Tied to specific `department_id`, but show cross-sector context
- **API:** Use `api/departments/list.php` which already includes cross-sector info

### 7. Important Helper Functions

- `getCrossSectorDepartmentIds($pdo, $userId)` - Get all department IDs across sectors
- `getDepartmentIdentifiers($pdo, $departmentIds)` - Get names/codes from IDs
- `checkDepartmentExistsInSector($pdo, $deptName, $deptCode, $sectorId)` - Check existence
- `getDepartmentSectors($pdo, $deptName, $deptCode)` - Get all sectors for a department

### 8. Files to Review for CourseManagement Updates

- `api/course_data.php` - Current department data source
- `api/departments/list.php` - Cross-sector department API
- `api/departments/create.php` - Reference for update/delete patterns
- `includes/helpers/department_helpers.php` - Helper functions
- `pages/admin/CourseManagement.php` - Target file for updates

---

## Additional Resources

### Documentation Files

- `docs/Cross_Sector_Department_Management_Plan.md` - Original implementation plan
- `docs/Implementation_Summary.md` - Implementation completion summary
- `docs/Phase5_Testing_Checklist.md` - Testing checklist
- `docs/FacultyDepartmentColumn_Implementation_Plan.md` - Faculty column implementation

### Test Files

- `tools/test_department_helpers.php` - Helper function tests
- `tools/test_api_endpoints.php` - API endpoint tests
- `pages/diagnostics/test_cross_sector.php` - Browser-based test page

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** ✅ Complete and Ready for Reference
