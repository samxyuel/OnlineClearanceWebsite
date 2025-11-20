# Diagnostic Tools

This directory contains diagnostic tools to help troubleshoot issues with the Online Clearance Website.

## Program Head Data Visibility Checker

### Purpose

Diagnoses why students/faculty data might not be showing on Program Head management pages (College Student Management, Senior High Student Management, Faculty Management).

### Usage

#### Option 1: Web Interface (Recommended)

1. Navigate to: `pages/diagnostics/program_head_checker.php`
2. Fill in the form:
   - **User ID**: The Program Head's user ID
   - **Type**: Select "Student" or "Faculty"
   - **School Term** (optional): Format "2024-2025|2" (academic year|semester_id)
3. Click "Run Diagnostics"
4. Review the results to identify issues

#### Option 2: API Endpoint

Direct API call:

```
GET api/diagnostics/program_head_data_check.php?user_id=123&type=student&school_term=2024-2025|2
```

#### Option 3: Command Line

```bash
php api/diagnostics/program_head_data_check.php --user_id=123 --type=student --school_term="2024-2025|2"
```

### What It Checks

1. **User Role Check**: Verifies user has "Program Head" or "Admin" role
2. **Program Head Designation**: Checks if user has "Program Head" designation in `staff` or `user_designation_assignments`
3. **Department Assignments**: Verifies user has active entries in `user_department_assignments`
4. **Data Exists**: Checks if students/faculty exist in assigned departments
5. **Clearance Period**: Verifies clearance period exists for selected term (or can query directly)
6. **Clearance Forms**: Checks if clearance forms exist for selected term
7. **Clearance Signatories**: Verifies clearance_signatories exist with Program Head designation

### Common Issues and Fixes

#### Issue: No Department Assignments

**Symptom**: Check #3 fails
**Fix**: Add entries to `user_department_assignments` table:

```sql
INSERT INTO user_department_assignments (user_id, department_id, is_active)
VALUES (?, ?, 1);
```

#### Issue: No Students/Faculty in Departments

**Symptom**: Check #4 fails
**Fix**: Ensure students/faculty are assigned to departments that match Program Head's assignments

#### Issue: No Clearance Forms

**Symptom**: Check #6 fails
**Fix**: Ensure clearance forms are created for the selected academic term

#### Issue: No Clearance Signatories

**Symptom**: Check #7 fails
**Note**: This might be OK if clearance forms haven't been assigned signatories yet. The data should still show, but signatory actions won't be available.

### Output Format

The diagnostic tool returns JSON with:

- `checks`: Object containing results of each check
- `summary`: Overall summary with critical failures and recommendations
- `data_should_show`: Boolean indicating if data should be visible

Each check includes:

- `name`: Name of the check
- `passed`: Boolean indicating if check passed
- `details`: Object with detailed information about the check

### Example Output

```json
{
  "user_id": 123,
  "type": "student",
  "school_term": "2024-2025|2",
  "checks": {
    "role": {
      "name": "User Role Check",
      "passed": true,
      "details": { "role": "Program Head" }
    },
    "departments": {
      "name": "Department Assignments Check",
      "passed": true,
      "details": {
        "count": 2,
        "departments": [...]
      }
    }
  },
  "summary": {
    "total_checks": 7,
    "passed_checks": 6,
    "failed_checks": 1,
    "data_should_show": false,
    "critical_failures": [
      "No clearance forms for selected term"
    ],
    "recommendation": "Fix the critical failures listed above to enable data display"
  }
}
```
