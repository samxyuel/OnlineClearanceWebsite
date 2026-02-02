# Implementation Plan: Add "Department(s)" Column to Faculty Management Tables

**Created:** December 23, 2024  
**Display Format:** Simple comma-separated list (e.g., `BSIT, BSCS, BSCE`)

---

## Summary

Add a "Department(s)" column to all Faculty Management pages to display each faculty member's primary and additional departments.

---

## Files to Modify (5 files)

| #   | File                                               | Type        |
| --- | -------------------------------------------------- | ----------- |
| 1   | `api/users/facultyList.php`                        | Backend API |
| 2   | `pages/admin/FacultyManagement.php`                | Frontend UI |
| 3   | `pages/program-head/FacultyManagement.php`         | Frontend UI |
| 4   | `pages/regular-staff/FacultyManagement.php`        | Frontend UI |
| 5   | `pages/school-administrator/FacultyManagement.php` | Frontend UI |

---

## Detailed Changes

### 1. `api/users/facultyList.php`

#### Lines 167-178: Update SQL Query

**Current Code:**

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

**New Code:**

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

#### Line 225: Add GROUP BY clause

**Current Code:**

```php
$dataStmt = $pdo->prepare("SELECT $selectFields $baseQuery $where ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
```

**New Code:**

```php
$dataStmt = $pdo->prepare("SELECT $selectFields $baseQuery $where GROUP BY f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name, u.email, u.contact_number, f.employment_status, u.account_status, u.created_at, cf.clearance_form_progress ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
```

---

### 2. `pages/admin/FacultyManagement.php`

#### Lines 203-208: Add Table Header

**Current Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Actions</th>
```

**New Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Department(s)</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Actions</th>
```

#### Lines 1193-1203: Add Department Cell in Row Generation

**Current Code:**

```javascript
tr.innerHTML=`<td class="checkbox-column"><input type=\"checkbox\" class=\"faculty-checkbox\" data-id=\"${f.employee_number}\"></td>
            <td data-label="Employee Number:">${f.employee_number}</td>
            <td data-label="Name:">${f.first_name} ${f.last_name}</td>
            <td data-label="Employment Status:"><span class="status-badge employment-${(f.employment_status || '').toLowerCase().replace(/ /g,'-')}">${f.employment_status}</span></td>
            ...
```

**New Code:**

```javascript
tr.innerHTML=`<td class="checkbox-column"><input type=\"checkbox\" class=\"faculty-checkbox\" data-id=\"${f.employee_number}\"></td>
            <td data-label="Employee Number:">${f.employee_number}</td>
            <td data-label="Name:">${f.first_name} ${f.last_name}</td>
            <td data-label="Department(s):">${f.departments || 'N/A'}</td>
            <td data-label="Employment Status:"><span class="status-badge employment-${(f.employment_status || '').toLowerCase().replace(/ /g,'-')}">${f.employment_status}</span></td>
            ...
```

#### Lines 1127, 1142, 1147, 1214: Update colspan from 7 to 8

**Change all occurrences:**

```javascript
// From:
colspan = "7";
// To:
colspan = "8";
```

---

### 3. `pages/program-head/FacultyManagement.php`

#### Lines 310-316: Add Table Header

**Current Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Clearance Status</th>
<th>Actions</th>
```

**New Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Department(s)</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Clearance Status</th>
<th>Actions</th>
```

#### Lines 1058-1065: Add Department Cell in Row Generation

**Current Code:**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Employment Status:">...</td>
    ...
```

**New Code:**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Department(s):">${escapeHtml(faculty.departments || 'N/A')}</td>
    <td data-label="Employment Status:">...</td>
    ...
```

#### Lines 861, 1214: Update colspan from 7 to 8

---

### 4. `pages/regular-staff/FacultyManagement.php`

#### Lines 352-358: Add Table Header

**Current Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Clearance Status</th>
<th>Actions</th>
```

**New Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Department(s)</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Clearance Status</th>
<th>Actions</th>
```

#### Lines 816-823: Add Department Cell in Row Generation

**Current Code:**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Employment Status:">...</td>
    ...
```

**New Code:**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Department(s):">${escapeHtml(faculty.departments || 'N/A')}</td>
    <td data-label="Employment Status:">...</td>
    ...
```

#### Lines 644, 712, 983: Update colspan from 7 to 8

---

### 5. `pages/school-administrator/FacultyManagement.php`

#### Lines 313-319: Add Table Header

**Current Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Clearance Status</th>
<th>Actions</th>
```

**New Code:**

```html
<th>Employee Number</th>
<th>Name</th>
<th>Department(s)</th>
<th>Employment Status</th>
<th>Account Status</th>
<th>Clearance Form Progress</th>
<th>Clearance Status</th>
<th>Actions</th>
```

#### Lines 1424-1431: Add Department Cell in Row Generation

**Current Code:**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Employment Status:">...</td>
    ...
```

**New Code:**

```javascript
tr.innerHTML = `
    <td class="checkbox-column">...</td>
    <td data-label="Employee Number:">${faculty.id}</td>
    <td data-label="Name:">${escapeHtml(faculty.name)}</td>
    <td data-label="Department(s):">${escapeHtml(faculty.departments || 'N/A')}</td>
    <td data-label="Employment Status:">...</td>
    ...
```

#### Lines 1264, 1471: Update colspan from 7/8 to 9

---

## Data Flow

```
Database (user_department_assignments)
    ↓
GROUP_CONCAT(department_name) → "BSIT, BSCS, BSCE"
    ↓
API Response: { departments: "BSIT, BSCS, BSCE" }
    ↓
Table Cell: <td>BSIT, BSCS, BSCE</td>
```

---

## Column Order After Implementation

| ☑ | Employee # | Name | **Department(s)** | Employment Status | Account Status | Clearance Progress | [Clearance Status] | Actions |

_Note: "Clearance Status" column only appears on Program Head, Regular Staff, and School Administrator pages._

---

## Checklist

- [x] Update `api/users/facultyList.php` - SQL joins and GROUP_CONCAT
- [x] Update `pages/admin/FacultyManagement.php` - Header, row, colspan
- [x] Update `pages/program-head/FacultyManagement.php` - Header, row, colspan
- [x] Update `pages/regular-staff/FacultyManagement.php` - Header, row, colspan
- [x] Update `pages/school-administrator/FacultyManagement.php` - Header, row, colspan
- [ ] Test all pages to verify departments display correctly
