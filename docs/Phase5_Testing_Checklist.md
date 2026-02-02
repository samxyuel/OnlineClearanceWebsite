# Phase 5: Comprehensive Testing & Validation Checklist

## Overview

This document provides a comprehensive testing checklist for the Cross-Sector Department Management feature implementation.

**Last Updated:** 2025-01-XX  
**Status:** Ready for Testing

---

## Pre-Testing Setup

### Prerequisites

- [ ] Database backup completed
- [ ] Test user accounts created:
  - [ ] School Administrator account
  - [ ] Program Head account (assigned to a department)
  - [ ] Regular Staff account
  - [ ] Student account (College)
  - [ ] Student account (SHS)
  - [ ] Faculty account

### Test Data Requirements

- [ ] At least one department exists in College sector
- [ ] At least one department exists in SHS sector
- [ ] At least one department exists in Faculty sector
- [ ] At least one shared department (same name/code across multiple sectors)
- [ ] Clearance periods configured for each sector

---

## 1. Backend API Testing

### 1.1 Department Helper Functions (`includes/helpers/department_helpers.php`)

#### Test: `getCrossSectorDepartmentIds()`

- [ ] **Test Case 1.1.1**: Program Head assigned to College ICT department

  - **Expected**: Returns department IDs for College ICT and Faculty ICT (if exists)
  - **Command**: Run `php tools/test_department_helpers.php`
  - **Verification**: Check output shows correct department IDs across sectors

- [ ] **Test Case 1.1.2**: Program Head assigned to Faculty-only department

  - **Expected**: Returns only Faculty department ID
  - **Verification**: No College/SHS department IDs returned

- [ ] **Test Case 1.1.3**: Program Head with no assignments
  - **Expected**: Returns empty array
  - **Verification**: Empty result set

#### Test: `getDepartmentSectors()`

- [ ] **Test Case 1.1.4**: Shared department (e.g., ICT in College and Faculty)

  - **Expected**: Returns array with both sectors
  - **Verification**: Both sectors listed in result

- [ ] **Test Case 1.1.5**: Single-sector department
  - **Expected**: Returns array with one sector
  - **Verification**: Only one sector in result

### 1.2 Department Creation API (`api/departments/create.php`)

#### Test: Single-Sector Department Creation

- [ ] **Test Case 1.2.1**: Create College-only department

  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Test College Dept", "code": "TCD001", "type": "college", "status": "active"}`
  - **Expected**: HTTP 200, department created in College sector only
  - **Verification**: Check database for single entry in `departments` table with `sector_id = 1`

- [ ] **Test Case 1.2.2**: Create SHS-only department

  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Test SHS Dept", "code": "TSD001", "type": "senior-high", "status": "active"}`
  - **Expected**: HTTP 200, department created in SHS sector only
  - **Verification**: Check database for single entry with `sector_id = 2`

- [ ] **Test Case 1.2.3**: Create Faculty-only department
  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Test Faculty Dept", "code": "TFD001", "type": "faculty", "status": "active"}`
  - **Expected**: HTTP 200, department created in Faculty sector only
  - **Verification**: Check database for single entry with `sector_id = 3`

#### Test: Multi-Sector Department Creation

- [ ] **Test Case 1.2.4**: Create College & Faculty shared department

  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Shared CF Dept", "code": "SCF001", "type": "college-faculty", "status": "active"}`
  - **Expected**: HTTP 200, departments created in both College (sector_id=1) and Faculty (sector_id=3)
  - **Verification**: Check database for two entries with same `department_name` and `department_code`

- [ ] **Test Case 1.2.5**: Create SHS & Faculty shared department

  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Shared SF Dept", "code": "SSF001", "type": "shs-faculty", "status": "active"}`
  - **Expected**: HTTP 200, departments created in both SHS (sector_id=2) and Faculty (sector_id=3)
  - **Verification**: Check database for two entries

- [ ] **Test Case 1.2.6**: Create All-Sectors department
  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "All Sectors Dept", "code": "ASD001", "type": "all-sectors", "status": "active"}`
  - **Expected**: HTTP 200, departments created in all three sectors
  - **Verification**: Check database for three entries (sector_id=1, 2, 3)

#### Test: Validation & Error Handling

- [ ] **Test Case 1.2.7**: Duplicate department code

  - **Request**: `POST /api/departments/create.php` (use existing code)
  - **Expected**: HTTP 409, error message indicating code already exists
  - **Verification**: Error response includes `existing` array with details

- [ ] **Test Case 1.2.8**: Invalid department code format

  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Test", "code": "test-123", "type": "college"}`
  - **Expected**: HTTP 400, error message about code format
  - **Verification**: Error message mentions uppercase letters and numbers only

- [ ] **Test Case 1.2.9**: Missing required fields

  - **Request**: `POST /api/departments/create.php`
  - **Payload**: `{"name": "Test"}`
  - **Expected**: HTTP 400, error message about missing fields
  - **Verification**: Error message lists missing fields

- [ ] **Test Case 1.2.10**: Unauthorized access (non-School Administrator)
  - **Request**: `POST /api/departments/create.php` (as Program Head or Regular Staff)
  - **Expected**: HTTP 403, access denied message
  - **Verification**: Error message indicates only School Administrators can create

### 1.3 Department List API (`api/departments/list.php`)

- [ ] **Test Case 1.3.1**: List all departments with cross-sector info

  - **Request**: `GET /api/departments/list.php`
  - **Expected**: HTTP 200, JSON response with `is_shared` and `sectors` arrays
  - **Verification**:
    - Shared departments have `is_shared: true` and multiple entries in `sectors` array
    - Single-sector departments have `is_shared: false` and single entry in `sectors` array

- [ ] **Test Case 1.3.2**: Filter by sector

  - **Request**: `GET /api/departments/list.php?sector=College`
  - **Expected**: HTTP 200, only College departments returned
  - **Verification**: All returned departments have `sector_name: "College"`

- [ ] **Test Case 1.3.3**: Search by name/code
  - **Request**: `GET /api/departments/list.php?q=ICT`
  - **Expected**: HTTP 200, departments matching "ICT" returned
  - **Verification**: Results include departments with "ICT" in name or code

### 1.4 Program Head Access Control APIs

#### Test: `api/program-head/is_assigned.php`

- [ ] **Test Case 1.4.1**: Program Head assigned to College ICT, checking College clearance

  - **Request**: `GET /api/program-head/is_assigned.php?clearanceType=College`
  - **Expected**: HTTP 200, `hasDepartmentScope: true` if College clearance period is active
  - **Verification**: Response includes correct department scope

- [ ] **Test Case 1.4.2**: Program Head assigned to College ICT, checking Faculty clearance
  - **Request**: `GET /api/program-head/is_assigned.php?clearanceType=Faculty`
  - **Expected**: HTTP 200, `hasDepartmentScope: true` if Faculty ICT exists and Faculty clearance period is active
  - **Verification**: Cross-sector access verified

#### Test: `api/clearance/signatoryList.php`

- [ ] **Test Case 1.4.3**: Program Head sees students from cross-sector departments

  - **Request**: `GET /api/clearance/signatoryList.php?clearanceType=College`
  - **Expected**: HTTP 200, students from College ICT department listed
  - **Verification**: Only students from assigned departments appear

- [ ] **Test Case 1.4.4**: Program Head sees faculty from cross-sector departments
  - **Request**: `GET /api/clearance/signatoryList.php?clearanceType=Faculty`
  - **Expected**: HTTP 200, faculty from Faculty ICT department listed
  - **Verification**: Only faculty from assigned departments appear

#### Test: `api/program-head/college_students.php`

- [ ] **Test Case 1.4.5**: Program Head assigned to College ICT sees College ICT students
  - **Request**: `GET /api/program-head/college_students.php`
  - **Expected**: HTTP 200, only College ICT students returned
  - **Verification**: Students filtered by cross-sector department IDs

#### Test: `api/program-head/shs_students.php`

- [ ] **Test Case 1.4.6**: Program Head assigned to SHS department sees SHS students
  - **Request**: `GET /api/program-head/shs_students.php`
  - **Expected**: HTTP 200, only SHS students from assigned departments returned
  - **Verification**: Students filtered correctly

---

## 2. UI/UX Testing

### 2.1 Program Head Assignment Modal (`Modals/StaffRegistryModal.php`)

#### Test: Department Selection UI

- [ ] **Test Case 2.1.1**: Modal opens and loads departments

  - **Steps**:
    1. Navigate to Staff Registry
    2. Click "Add New Staff"
    3. Select "Program Head" from designation dropdown
  - **Expected**: Program Head assignment section appears, departments load with cross-sector indicators
  - **Verification**:
    - Departments display with "[Available in: College, Faculty]" badges
    - Faculty-only departments show "[Faculty Only]" badge
    - Summary shows "0 departments selected"

- [ ] **Test Case 2.1.2**: Select shared department

  - **Steps**:
    1. Open Program Head assignment section
    2. Check a shared department (e.g., ICT)
  - **Expected**:
    - Checkbox checked
    - Summary updates to show "1 department selected"
    - Department appears in selected list
  - **Verification**: Summary count increases

- [ ] **Test Case 2.1.3**: Select multiple departments

  - **Steps**:
    1. Select multiple departments (mix of shared and single-sector)
  - **Expected**: Summary shows correct count, all selected departments listed
  - **Verification**: All selections reflected in UI

- [ ] **Test Case 2.1.4**: Deselect department
  - **Steps**:
    1. Select a department
    2. Uncheck the same department
  - **Expected**: Summary count decreases, department removed from selection
  - **Verification**: UI updates correctly

#### Test: Form Submission

- [ ] **Test Case 2.1.5**: Submit Program Head with department assignments
  - **Steps**:
    1. Fill in staff details
    2. Select "Program Head" designation
    3. Select one or more departments
    4. Submit form
  - **Expected**: Staff created successfully, department assignments saved
  - **Verification**:
    - Check `user_department_assignments` table
    - Verify assignments linked to correct department IDs

### 2.2 Edit Staff Modal (`Modals/EditStaffModal.php`)

#### Test: Existing Assignments Display

- [ ] **Test Case 2.2.1**: Edit existing Program Head

  - **Steps**:
    1. Open edit modal for existing Program Head
    2. View Program Head assignment section
  - **Expected**:
    - Current assignments displayed
    - Departments loaded with existing selections checked
    - Cross-sector information displayed
  - **Verification**: Existing assignments visible and correct

- [ ] **Test Case 2.2.2**: Modify department assignments
  - **Steps**:
    1. Edit Program Head
    2. Uncheck one department, check another
    3. Save changes
  - **Expected**: Assignments updated correctly
  - **Verification**: Database reflects new assignments

### 2.3 Department Creation Modal (`Modals/AddDepartmentModal.php`)

#### Test: Department Type Selection

- [ ] **Test Case 2.3.1**: Select single-sector type

  - **Steps**:
    1. Open "Add Department" modal
    2. Select "College" from department type dropdown
  - **Expected**: Info text shows "This department will be created in: College"
  - **Verification**: Info text updates correctly

- [ ] **Test Case 2.3.2**: Select shared type (College & Faculty)

  - **Steps**:
    1. Select "College & Faculty" from dropdown
  - **Expected**: Info text shows "This department will be created in: College, Faculty"
  - **Verification**: Multiple sectors listed

- [ ] **Test Case 2.3.3**: Select "All Sectors"
  - **Steps**:
    1. Select "All Sectors" from dropdown
  - **Expected**: Info text shows all three sectors
  - **Verification**: All sectors listed

#### Test: Department Code Validation

- [ ] **Test Case 2.3.4**: Enter valid department code

  - **Steps**:
    1. Enter code "TEST001"
  - **Expected**: Code accepted, no error
  - **Verification**: Code field accepts uppercase letters and numbers

- [ ] **Test Case 2.3.5**: Enter invalid department code
  - **Steps**:
    1. Enter code "test-123" (lowercase, special chars)
  - **Expected**: Browser validation prevents submission or shows error
  - **Verification**: Pattern validation works (`pattern="[A-Z0-9]+"`)

#### Test: Form Submission

- [ ] **Test Case 2.3.6**: Create single-sector department

  - **Steps**:
    1. Enter name "Test Department"
    2. Enter code "TD001"
    3. Select "College"
    4. Submit
  - **Expected**: Success message, department created
  - **Verification**: Database shows one entry for College sector

- [ ] **Test Case 2.3.7**: Create shared department

  - **Steps**:
    1. Enter name "Shared Department"
    2. Enter code "SD001"
    3. Select "College & Faculty"
    4. Submit
  - **Expected**: Success message, departments created in both sectors
  - **Verification**: Database shows two entries with same name/code

- [ ] **Test Case 2.3.8**: Create department with duplicate code
  - **Steps**:
    1. Enter code that already exists
    2. Submit
  - **Expected**: Error message indicating code already exists
  - **Verification**: Error response shows existing department details

### 2.4 Course Creation Modal (`Modals/AddCourseModal.php`)

#### Test: Department Dropdown

- [ ] **Test Case 2.4.1**: Departments load with cross-sector info

  - **Steps**:
    1. Open "Add Course" modal
    2. View department dropdown
  - **Expected**:
    - Departments grouped by sector (`<optgroup>`)
    - Shared departments show "[Shared]" indicator
    - All active departments listed
  - **Verification**: Dropdown populated correctly

- [ ] **Test Case 2.4.2**: Select department from dropdown
  - **Steps**:
    1. Select a department from dropdown
  - **Expected**: Department selected, form can be submitted
  - **Verification**: Selection works correctly

---

## 3. Integration Testing

### 3.1 End-to-End: Program Head Assignment & Access

- [ ] **Test Case 3.1.1**: Complete flow - Assign Program Head to shared department

  - **Steps**:
    1. Create shared department (College & Faculty) via Add Department modal
    2. Assign Program Head to this department via Staff Registry modal
    3. Log in as Program Head
    4. Navigate to signatory list for College clearance
    5. Navigate to signatory list for Faculty clearance
  - **Expected**:
    - Program Head can see students/faculty from both College and Faculty instances
    - Access is limited to active clearance periods
  - **Verification**:
    - Check `user_department_assignments` table
    - Verify signatory list shows correct students/faculty
    - Verify access control works per sector clearance period

- [ ] **Test Case 3.1.2**: Program Head with multiple department assignments
  - **Steps**:
    1. Assign Program Head to multiple departments (some shared, some single-sector)
    2. Log in as Program Head
    3. Check access to students/faculty
  - **Expected**: Program Head sees students/faculty from all assigned departments across all sectors
  - **Verification**: All relevant students/faculty appear in lists

### 3.2 Cross-Sector Department Matching

- [ ] **Test Case 3.2.1**: Existing departments with same name/code

  - **Steps**:
    1. Check existing departments in database
    2. Verify departments with same name/code are recognized as shared
  - **Expected**: `api/departments/list.php` shows `is_shared: true` for matching departments
  - **Verification**: API response includes cross-sector information

- [ ] **Test Case 3.2.2**: Program Head assigned to one sector, accessing another
  - **Steps**:
    1. Assign Program Head to College ICT
    2. Verify Faculty ICT exists with same code
    3. Check Program Head access to Faculty clearance
  - **Expected**: Program Head can access Faculty ICT if Faculty clearance period is active
  - **Verification**: Cross-sector matching works via helper functions

### 3.3 Clearance Period Integration

- [ ] **Test Case 3.3.1**: College clearance active, Faculty clearance inactive

  - **Steps**:
    1. Ensure College clearance period is active
    2. Ensure Faculty clearance period is inactive
    3. Log in as Program Head assigned to shared department
    4. Check access to College and Faculty clearances
  - **Expected**:
    - Program Head can perform signatory actions for College
    - Program Head cannot perform signatory actions for Faculty
  - **Verification**: `api/program-head/is_assigned.php` returns correct `hasDepartmentScope` per sector

- [ ] **Test Case 3.3.2**: Both clearance periods active
  - **Steps**:
    1. Ensure both College and Faculty clearance periods are active
    2. Log in as Program Head assigned to shared department
    3. Check access to both clearances
  - **Expected**: Program Head can perform signatory actions for both sectors
  - **Verification**: Access granted to both sectors

---

## 4. Edge Cases & Error Handling

### 4.1 Data Integrity

- [ ] **Test Case 4.1.1**: Department code case sensitivity

  - **Steps**:
    1. Create department with code "ICT001"
    2. Try to create another with code "ict001" (lowercase)
  - **Expected**: Code converted to uppercase, duplicate detected
  - **Verification**: Validation prevents duplicate regardless of case

- [ ] **Test Case 4.1.2**: Department with null code

  - **Steps**:
    1. Check existing departments with null `department_code`
    2. Verify cross-sector matching works with `department_name` only
  - **Expected**: Matching works via `department_name` when code is null
  - **Verification**: Helper functions handle null codes correctly

- [ ] **Test Case 4.1.3**: Inactive departments
  - **Steps**:
    1. Deactivate a department
    2. Check if it appears in department lists
  - **Expected**: Inactive departments excluded from lists
  - **Verification**: `is_active = 0` departments filtered out

### 4.2 Permission & Access Control

- [ ] **Test Case 4.2.1**: Regular Staff trying to create department

  - **Steps**:
    1. Log in as Regular Staff
    2. Try to access department creation (if UI accessible)
  - **Expected**: Access denied (403) or UI not accessible
  - **Verification**: API returns 403 error

- [ ] **Test Case 4.2.2**: Program Head without assignments
  - **Steps**:
    1. Log in as Program Head with no department assignments
    2. Check signatory list access
  - **Expected**: Empty lists or appropriate "no access" message
  - **Verification**: No students/faculty returned

### 4.3 UI Edge Cases

- [ ] **Test Case 4.3.1**: Large number of departments

  - **Steps**:
    1. Create many departments (50+)
    2. Open Program Head assignment modal
  - **Expected**: Departments load, UI remains responsive
  - **Verification**: Performance acceptable, no timeouts

- [ ] **Test Case 4.3.2**: Special characters in department names
  - **Steps**:
    1. Create department with special characters (e.g., "IT & CS Department")
    2. Verify display in modals
  - **Expected**: Special characters displayed correctly
  - **Verification**: No HTML/JavaScript errors

---

## 5. Performance Testing

- [ ] **Test Case 5.1**: Department list API response time

  - **Steps**:
    1. Measure response time for `api/departments/list.php` with many departments
  - **Expected**: Response time < 1 second for 100+ departments
  - **Verification**: Performance metrics recorded

- [ ] **Test Case 5.2**: Cross-sector query performance
  - **Steps**:
    1. Program Head with many department assignments
    2. Measure `getCrossSectorDepartmentIds()` execution time
  - **Expected**: Query executes quickly (< 100ms)
  - **Verification**: Database queries optimized

---

## 6. Browser Compatibility

- [ ] **Test Case 6.1**: Chrome/Edge

  - **Steps**: Test all UI modals in Chrome/Edge
  - **Expected**: All features work correctly
  - **Verification**: No console errors

- [ ] **Test Case 6.2**: Firefox

  - **Steps**: Test all UI modals in Firefox
  - **Expected**: All features work correctly
  - **Verification**: No console errors

- [ ] **Test Case 6.3**: Safari (if applicable)
  - **Steps**: Test all UI modals in Safari
  - **Expected**: All features work correctly
  - **Verification**: No console errors

---

## 7. Regression Testing

- [ ] **Test Case 7.1**: Existing single-sector departments still work

  - **Steps**:
    1. Verify existing Program Head assignments still function
    2. Check existing department lists display correctly
  - **Expected**: No breaking changes to existing functionality
  - **Verification**: All existing features work as before

- [ ] **Test Case 7.2**: Existing student/faculty assignments
  - **Steps**:
    1. Verify students and faculty can still be assigned to departments
    2. Check clearance workflows still function
  - **Expected**: No impact on existing workflows
  - **Verification**: All existing processes work correctly

---

## Testing Tools & Commands

### Command-Line Testing

```bash
# Test department helper functions
php tools/test_department_helpers.php

# Test API endpoints
php tools/test_api_endpoints.php
```

### Browser-Based Testing

- Navigate to: `http://localhost/OnlineClearanceWebsite/pages/diagnostics/test_cross_sector.php`
- Use the interactive test interface to test APIs

### Database Verification Queries

```sql
-- Check cross-sector departments
SELECT department_name, department_code, sector_id, s.sector_name
FROM departments d
JOIN sectors s ON d.sector_id = s.sector_id
WHERE department_code = 'ICT001'
ORDER BY sector_id;

-- Check Program Head assignments
SELECT u.user_id, u.first_name, u.last_name, d.department_name, d.department_code, s.sector_name
FROM user_department_assignments uda
JOIN users u ON uda.user_id = u.user_id
JOIN departments d ON uda.department_id = d.department_id
JOIN sectors s ON d.sector_id = s.sector_id
WHERE uda.is_active = 1
ORDER BY u.user_id, s.sector_id;
```

---

## Sign-Off

### Testing Completed By

- **Name**: **\*\***\_\_\_\_**\*\***
- **Date**: **\*\***\_\_\_\_**\*\***
- **Role**: **\*\***\_\_\_\_**\*\***

### Approval

- **Tested By**: **\*\***\_\_\_\_**\*\***
- **Approved By**: **\*\***\_\_\_\_**\*\***
- **Date**: **\*\***\_\_\_\_**\*\***

---

## Notes & Issues

### Issues Found

1.
2.
3.

### Resolutions

1.
2.
3.

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-XX
