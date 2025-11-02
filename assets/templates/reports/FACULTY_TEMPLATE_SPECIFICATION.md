# Faculty Clearance Form Progress Report Template Specification

This document specifies what placeholders should be in the **Faculty_Clearance_Form_Progress.docx** template to match the Student template structure.

---

## **Template Structure**

The Faculty template should mirror the Student template, but with faculty-specific fields.

---

## **Header Section Placeholders**

Add these placeholders in the header/header area of your template:

```
${ReportTitle}      → "Faculty Clearance Form Progress Report"
${Sector}           → College | Senior High School | Faculty
${SchoolYear}       → 2024-2025 (example)
${Semester}         → 1st | 2nd
${DepartmentName}   → Information, Communication, and Technology (if filtered)
${GeneratedBy}      → Admin | School Administrator (role name)
```

**Note:** These appear at the top of the document, usually in a header section or title area.

---

## **Summary Section Placeholders**

Add a summary section (usually after the header, before the table) with these placeholders:

```
${TotalForms}        → Total number of faculty forms
${TotalUnapplied}    → Count of forms with status "Unapplied"
${TotalApplied}      → Count of forms with status "Applied" (optional)
${TotalInProgress}   → Count of forms with status "In Progress"
${TotalCompleted}    → Count of forms with status "Completed"
```

---

## **Table Row Placeholders**

Create a table with ONE sample row containing these placeholders. PHPWord will clone this row for each faculty member.

**Important:** The FIRST placeholder in the row will be used for `cloneRow()`. Use `${EmployeeNo}` as the first placeholder.

**Table Column Structure:**

| Column Order | Placeholder Name      | Description                                 |
| ------------ | --------------------- | ------------------------------------------- | --------- | --------------------- | --------- |
| 1            | `${EmployeeNo}`       | **MUST BE FIRST** - Faculty employee number |
| 2            | `${FirstName}`        | First name                                  |
| 3            | `${MiddleName}`       | Middle name (or initial)                    |
| 4            | `${LastName}`         | Last name                                   |
| 5            | `${Department}`       | Department name                             |
| 6            | `${EmploymentStatus}` | Full Time                                   | Part Time | Part Time - Full Load |
| 7            | `${FormStatus}`       | Unapplied                                   | Applied   | In Progress           | Completed |

**Template Row Example:**

```
${EmployeeNo} | ${FirstName} | ${MiddleName} | ${LastName} | ${Department} | ${EmploymentStatus} | ${FormStatus}
```

---

## **Implementation Steps (Manual)**

1. **Open** `Faculty_Clearance_Form_Progress.docx` in LibreOffice Writer or Microsoft Word
2. **Copy header structure** from `Student_Clearance_Form_Progress.docx`:
   - Copy the header/title area
   - Replace `${ReportTitle}` text to "Faculty Clearance Form Progress Report"
   - Keep all other header placeholders the same
3. **Add summary section** (copy from Student template):
   - Copy the summary/totals section
   - Keep the same placeholder names (`${TotalForms}`, `${TotalUnapplied}`, etc.)
4. **Update table structure:**
   - Replace student columns with faculty columns
   - Change `${StudentNo}` → `${EmployeeNo}` (and make it FIRST column)
   - Change `${Program}` → `${Department}`
   - Remove `${YearLevel}` and `${Section}`
   - Add `${EmploymentStatus}` column
   - Keep: `${FirstName}`, `${MiddleName}`, `${LastName}`, `${FormStatus}`
5. **Save** the template

---

## **Verification**

After updating, run the extraction tool to verify:

```bash
php tools/extract_template_placeholders.php
```

Expected placeholders for Faculty Progress:

- Header: `ReportTitle`, `Sector`, `SchoolYear`, `Semester`, `DepartmentName`, `GeneratedBy`
- Summary: `TotalForms`, `TotalUnapplied`, `TotalInProgress`, `TotalCompleted`
- Table Row: `EmployeeNo` (first), `FirstName`, `MiddleName`, `LastName`, `Department`, `EmploymentStatus`, `FormStatus`

---

## **Quick Checklist**

- [ ] Header has `${ReportTitle}`, `${Sector}`, `${SchoolYear}`, `${Semester}`, `${DepartmentName}`, `${GeneratedBy}`
- [ ] Summary section has `${TotalForms}`, `${TotalUnapplied}`, `${TotalInProgress}`, `${TotalCompleted}`
- [ ] Table has ONE sample row
- [ ] First column in table row is `${EmployeeNo}`
- [ ] Table row includes: `${FirstName}`, `${MiddleName}`, `${LastName}`, `${Department}`, `${EmploymentStatus}`, `${FormStatus}`
