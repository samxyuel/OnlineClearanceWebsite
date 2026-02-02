# Cross-Sector Department Management - Testing Guide

## Quick Test Results

✅ **Helper Functions Test**: PASSED

- `getCrossSectorDepartmentIds()` - Working correctly
- `getDepartmentIdentifiers()` - Working correctly
- `checkDepartmentExistsInSector()` - Working correctly
- `getDepartmentSectors()` - Ready (needs shared departments to test)

**Test Output:**

- Found Program Head (user_id: 269) with 3 department assignments
- Helper functions return correct results
- Cross-sector matching ready (no shared departments exist yet)

---

## Testing Checklist

### Phase 1: Backend Foundation ✅

- [x] Helper functions created and tested
- [ ] Department creation API tested
- [ ] Departments list API tested

### Phase 2: Program Head Access Control ✅

- [x] All endpoints updated with cross-sector matching
- [ ] `is_assigned.php` tested
- [ ] `signatoryList.php` tested
- [ ] Student APIs tested
- [ ] Controllers tested

### Phase 3: End-to-End Testing

- [ ] Create shared department (College & Faculty)
- [ ] Assign Program Head to shared department
- [ ] Verify cross-sector access
- [ ] Test signatory actions across sectors

---

## Test Scenarios

### Scenario 1: Create Shared Department

**Goal**: Create a department that exists in both College and Faculty sectors

**Steps**:

1. Login as School Administrator
2. Navigate to Department Management
3. Create new department:
   - Name: "Information Technology"
   - Code: "IT"
   - Type: "College & Faculty"
   - Status: Active

**Expected Result**:

- Department created in both College (sector_id: 1) and Faculty (sector_id: 3)
- Same `department_name` and `department_code` in both sectors
- Different `department_id` values

**Verify**:

```sql
SELECT d.*, s.sector_name
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE d.department_code = 'IT';
-- Should show 2 rows
```

### Scenario 2: Assign Program Head to Shared Department

**Goal**: Assign Program Head to College IT, verify they can access Faculty IT

**Steps**:

1. Login as School Administrator
2. Navigate to Staff Management
3. Edit Program Head user
4. Assign to "Information Technology" (College sector)
5. Save assignment

**Expected Result**:

- Program Head assigned to College IT department
- Cross-sector matching should find Faculty IT department automatically

**Verify**:

```sql
-- Check assignment
SELECT uda.*, d.department_name, d.department_code, s.sector_name
FROM user_department_assignments uda
JOIN departments d ON uda.department_id = d.department_id
JOIN sectors s ON d.sector_id = s.sector_id
WHERE uda.user_id = [PROGRAM_HEAD_USER_ID];

-- Test cross-sector matching (replace USER_ID)
SELECT d.department_id, d.department_name, s.sector_name
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE d.department_id IN (
    -- This simulates getCrossSectorDepartmentIds()
    SELECT department_id
    FROM departments
    WHERE (department_name IN (
        SELECT DISTINCT COALESCE(d2.department_code, d2.department_name)
        FROM user_department_assignments uda2
        JOIN departments d2 ON uda2.department_id = d2.department_id
        WHERE uda2.user_id = [USER_ID] AND uda2.is_active = 1
    ) OR department_code IN (
        SELECT DISTINCT COALESCE(d2.department_code, d2.department_name)
        FROM user_department_assignments uda2
        JOIN departments d2 ON uda2.department_id = d2.department_id
        WHERE uda2.user_id = [USER_ID] AND uda2.is_active = 1
    ))
    AND is_active = 1
)
ORDER BY d.department_name, s.sector_name;
-- Should show both College IT and Faculty IT
```

### Scenario 3: Test Program Head Access

**Goal**: Verify Program Head can see students and faculty across sectors

**Steps**:

1. Login as Program Head assigned to College IT
2. Navigate to College Student Management
   - **Expected**: See College IT students
3. Navigate to Faculty Management
   - **Expected**: See Faculty IT members (cross-sector access)
4. Check signatory list for College
   - **Expected**: See College IT clearance forms
5. Check signatory list for Faculty
   - **Expected**: See Faculty IT clearance forms

**API Tests**:

1. **Test is_assigned.php**:

   ```http
   GET /api/program-head/is_assigned.php?clearance_type=College
   ```

   Expected: `can_take_action: true`

   ```http
   GET /api/program-head/is_assigned.php?clearance_type=Faculty
   ```

   Expected: `can_take_action: true` (if IT exists in Faculty)

2. **Test signatoryList.php**:

   ```http
   GET /api/clearance/signatoryList.php?type=student&sector=College
   ```

   Expected: College IT students only

   ```http
   GET /api/clearance/signatoryList.php?type=faculty
   ```

   Expected: Faculty IT members only

3. **Test college_students.php**:
   ```http
   GET /api/program-head/college_students.php
   ```
   Expected: College IT students

---

## API Testing Commands

### Using cURL (Command Line)

**Test Department Creation**:

```bash
curl -X POST http://localhost/OnlineClearanceWebsite/api/departments/create.php \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id" \
  -d '{
    "name": "Information Technology",
    "code": "IT",
    "type": "college-faculty",
    "status": "active"
  }'
```

**Test Departments List**:

```bash
curl http://localhost/OnlineClearanceWebsite/api/departments/list.php \
  -b "PHPSESSID=your_session_id"
```

**Test Program Head Access**:

```bash
curl "http://localhost/OnlineClearanceWebsite/api/program-head/is_assigned.php?clearance_type=College" \
  -b "PHPSESSID=your_session_id"
```

### Using Browser Console (JavaScript)

**Test Department Creation**:

```javascript
fetch("/OnlineClearanceWebsite/api/departments/create.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  credentials: "include",
  body: JSON.stringify({
    name: "Information Technology",
    code: "IT",
    type: "college-faculty",
    status: "active",
  }),
})
  .then((r) => r.json())
  .then((data) => console.log(data));
```

**Test Departments List**:

```javascript
fetch("/OnlineClearanceWebsite/api/departments/list.php", {
  credentials: "include",
})
  .then((r) => r.json())
  .then((data) => {
    console.log("Departments:", data.departments);
    // Check for is_shared flag
    data.departments.forEach((dept) => {
      if (dept.is_shared) {
        console.log(
          `Shared: ${dept.department_name} in ${dept.sectors.length} sectors`
        );
      }
    });
  });
```

---

## Database Verification Queries

### Check Shared Departments

```sql
SELECT
    d.department_code,
    d.department_name,
    COUNT(DISTINCT d.sector_id) as sector_count,
    GROUP_CONCAT(DISTINCT s.sector_name ORDER BY s.sector_name) as sectors
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE d.is_active = 1
GROUP BY d.department_code, d.department_name
HAVING sector_count > 1
ORDER BY d.department_name;
```

### Check Program Head Cross-Sector Access

```sql
-- Replace USER_ID with actual Program Head user_id
SET @user_id = 269; -- Example

SELECT
    d.department_id,
    d.department_name,
    d.department_code,
    s.sector_name,
    s.sector_id
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE d.department_id IN (
    SELECT department_id
    FROM departments
    WHERE (department_name IN (
        SELECT DISTINCT COALESCE(d2.department_code, d2.department_name)
        FROM user_department_assignments uda2
        JOIN departments d2 ON uda2.department_id = d2.department_id
        WHERE uda2.user_id = @user_id AND uda2.is_active = 1
    ) OR department_code IN (
        SELECT DISTINCT COALESCE(d2.department_code, d2.department_name)
        FROM user_department_assignments uda2
        JOIN departments d2 ON uda2.department_id = d2.department_id
        WHERE uda2.user_id = @user_id AND uda2.is_active = 1
    ))
    AND is_active = 1
)
ORDER BY d.department_name, s.sector_name;
```

### Verify Department Code Uniqueness

```sql
SELECT
    department_code,
    COUNT(*) as count,
    GROUP_CONCAT(CONCAT(department_name, ' (', sector_id, ')') SEPARATOR ', ') as locations
FROM departments
WHERE department_code IS NOT NULL
AND is_active = 1
GROUP BY department_code
HAVING count > 1;
-- Should return empty if uniqueness is enforced correctly
```

---

## Troubleshooting

### Issue: "No departments found" in helper function test

**Solution**: Verify Program Head has active assignments:

```sql
SELECT * FROM user_department_assignments
WHERE user_id = [USER_ID] AND is_active = 1;
```

### Issue: Cross-sector matching not working

**Solution**: Verify departments have matching names/codes:

```sql
-- Check if departments share name or code
SELECT department_name, department_code, sector_id, sector_name
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE department_name = 'Information Technology'
   OR department_code = 'IT';
```

### Issue: Department creation fails

**Solution**:

1. Check if code already exists: `SELECT * FROM departments WHERE department_code = 'IT';`
2. Verify user is School Administrator
3. Check PHP error logs: `C:\xampp\htdocs\OnlineClearanceWebsite\logs\error.log`

### Issue: Program Head can't see cross-sector data

**Solution**:

1. Verify department assignments exist
2. Check if departments share name/code across sectors
3. Test helper function directly: `php tools/test_department_helpers.php [USER_ID]`

---

## Next Steps After Testing

1. ✅ Helper functions - **COMPLETE**
2. ⏳ Test department creation API
3. ⏳ Test departments list API
4. ⏳ Test Program Head access endpoints
5. ⏳ Create shared department
6. ⏳ Assign Program Head and verify cross-sector access
7. ⏳ Test signatory actions across sectors
8. ⏳ Proceed with Phase 3 & 4 (UI updates)

---

**Last Updated**: After Phase 1 & 2 Implementation
**Status**: Backend implementation complete, testing in progress
