# Import Modal - Database Schema Reference

This document describes the database tables and columns used by the Import Modal to fetch departments and programs.

## API Endpoint

**File:** `api/import/options.php`

---

## For Loading Departments

### Admin Role

**Query:**

```sql
SELECT
    d.department_id,
    d.department_name,
    s.sector_name
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE d.is_active = 1
    AND s.sector_name = ?
ORDER BY d.department_name ASC
```

**Tables Used:**

- `departments` (aliased as `d`)

  - `department_id` (INT) - Primary key
  - `department_name` (VARCHAR) - Department display name
  - `sector_id` (INT) - Foreign key to sectors table
  - `is_active` (BOOLEAN) - Active status filter

- `sectors` (aliased as `s`)
  - `sector_id` (INT) - Primary key
  - `sector_name` (VARCHAR) - Sector name ('College', 'Senior High School', 'Faculty')

**Query Parameters:**

- `?` = Expected sector name based on `pageType`:
  - `'college'` → `'College'`
  - `'shs'` → `'Senior High School'`
  - `'faculty'` → `'Faculty'`

---

### Program Head Role

**Step 1: Get Staff Employee Number**

```sql
SELECT s.employee_number
FROM staff s
WHERE s.user_id = ?
    AND s.staff_category = 'Program Head'
    AND s.is_active = 1
LIMIT 1
```

**Tables Used:**

- `staff` (aliased as `s`)
  - `employee_number` (VARCHAR(8)) - Primary key (format: LCA####P)
  - `user_id` (INT) - Foreign key to users table
  - `staff_category` (VARCHAR) - Must be 'Program Head'
  - `is_active` (BOOLEAN) - Active status filter

**Step 2: Get Assigned Departments**

```sql
SELECT
    d.department_id,
    d.department_name,
    s.sector_name
FROM staff_department_assignments sda
JOIN departments d ON sda.department_id = d.department_id
JOIN sectors s ON sda.sector_id = s.sector_id
WHERE sda.staff_id = ?
    AND sda.is_active = 1
    AND d.is_active = 1
    AND s.sector_name = ?
ORDER BY d.department_name ASC
```

**Tables Used:**

- `staff_department_assignments` (aliased as `sda`)

  - `staff_id` (VARCHAR(8)) - Foreign key to staff.employee_number
  - `department_id` (INT) - Foreign key to departments table
  - `sector_id` (INT) - Foreign key to sectors table
  - `is_active` (BOOLEAN) - Active assignment filter

- `departments` (aliased as `d`)

  - `department_id` (INT) - Primary key
  - `department_name` (VARCHAR) - Department display name
  - `is_active` (BOOLEAN) - Active status filter

- `sectors` (aliased as `s`)
  - `sector_id` (INT) - Primary key
  - `sector_name` (VARCHAR) - Sector name filter

**Query Parameters:**

- First `?` = `staff.employee_number` from Step 1
- Second `?` = Expected sector name based on `pageType`

---

## For Loading Programs

### Admin Role

**Query:**

```sql
SELECT
    p.program_id,
    p.program_name,
    p.program_code,
    p.department_id
FROM programs p
WHERE p.is_active = 1
    AND p.department_id = ?
ORDER BY p.program_name ASC
```

**Tables Used:**

- `programs` (aliased as `p`)
  - `program_id` (INT) - Primary key
  - `program_name` (VARCHAR) - Program display name
  - `program_code` (VARCHAR) - Program code/abbreviation
  - `department_id` (INT) - Foreign key to departments table
  - `is_active` (BOOLEAN) - Active status filter

**Query Parameters:**

- `?` = `department_id` from department selection

---

### Program Head Role

**Step 1: Verify Staff and Get Employee Number**

```sql
SELECT s.employee_number
FROM staff s
WHERE s.user_id = ?
    AND s.staff_category = 'Program Head'
    AND s.is_active = 1
LIMIT 1
```

**Tables Used:**

- `staff` (aliased as `s`)
  - `employee_number` (VARCHAR(8))
  - `user_id` (INT)
  - `staff_category` (VARCHAR)
  - `is_active` (BOOLEAN)

**Step 2: Verify Department Assignment**

```sql
SELECT 1
FROM staff_department_assignments sda
WHERE sda.staff_id = ?
    AND sda.department_id = ?
    AND sda.is_active = 1
LIMIT 1
```

**Tables Used:**

- `staff_department_assignments` (aliased as `sda`)
  - `staff_id` (VARCHAR(8))
  - `department_id` (INT)
  - `is_active` (BOOLEAN)

**Step 3: Get Programs (Same as Admin)**

```sql
SELECT
    p.program_id,
    p.program_name,
    p.program_code,
    p.department_id
FROM programs p
WHERE p.is_active = 1
    AND p.department_id = ?
ORDER BY p.program_name ASC
```

**Tables Used:**

- `programs` (aliased as `p`)
  - `program_id` (INT)
  - `program_name` (VARCHAR)
  - `program_code` (VARCHAR)
  - `department_id` (INT)
  - `is_active` (BOOLEAN)

---

## Summary Table Reference

| Table Name                     | Key Columns Used                                                           | Purpose                                |
| ------------------------------ | -------------------------------------------------------------------------- | -------------------------------------- |
| `departments`                  | `department_id`, `department_name`, `sector_id`, `is_active`               | Department information                 |
| `sectors`                      | `sector_id`, `sector_name`                                                 | Sector filtering (College/SHS/Faculty) |
| `staff`                        | `employee_number`, `user_id`, `staff_category`, `is_active`                | Program Head identification            |
| `staff_department_assignments` | `staff_id`, `department_id`, `sector_id`, `is_active`                      | Program Head department assignments    |
| `programs`                     | `program_id`, `program_name`, `program_code`, `department_id`, `is_active` | Program/course information             |

---

## Notes

1. **All queries filter by `is_active = 1`** to only return active records
2. **Program Heads** must have:
   - A `staff` record with `staff_category = 'Program Head'`
   - Active assignments in `staff_department_assignments` table
3. **Sector filtering** ensures departments/programs match the selected page type (College/SHS/Faculty)
4. **Department-Program relationship** is via `programs.department_id` → `departments.department_id`
