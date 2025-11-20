# Export API Test Script

Test script to verify the report export functionality works correctly.

## Usage

```bash
# Test a specific report type and format
php tools/test_export_api.php [report_type] [format]

# Examples:
php tools/test_export_api.php student_progress pdf
php tools/test_export_api.php faculty_progress pdf
php tools/test_export_api.php student_progress xlsx
php tools/test_export_api.php faculty_progress xlsx

# Test all predefined combinations
php tools/test_export_api.php all
```

## Report Types

- `student_progress` - Student Clearance Form Progress Report
- `faculty_progress` - Faculty Clearance Form Progress Report
- `student_applicant_status` - Student Clearance Applicant Status Report (requires template)
- `faculty_applicant_status` - Faculty Clearance Applicant Status Report (requires template)

## Formats

- `pdf` - PDF format (generated from .docx templates)
- `xlsx` - Excel format (HTML table)
- `xls` - Legacy Excel format (HTML table)

## Test Output

The script will:

1. ✅ Check database connection
2. ✅ Verify template files exist
3. ✅ Check class availability
4. ✅ Verify test data in database
5. ✅ Run generation tests
6. ✅ Validate generated files
7. ✅ Clean up temporary files

## Example Output

```
=== Export API Test Script ===

=== Database Connection ===
✓ Database connection successful

=== Template Files Check ===
✓ student_progress template found (21.85 KB)
✓ faculty_progress template found (25.05 KB)

=== Running Tests ===

Test: student_progress (pdf)
  Parameters:
    School Year: 2024-2025
    Semester: 1st
    Sector: College
    Department ID: 1
    Program ID: 1
✓ Report generated successfully
    File: rep1675.tmp.pdf
    Size: 8.58 KB
    Duration: 377.4ms
✓ Valid PDF file

=== Test Summary ===
✓ Passed: 1
✓ All tests passed!
```

## Troubleshooting

- **"Template not found"**: Ensure template files are in `assets/templates/reports/`
- **"Database connection failed"**: Check database configuration in `includes/config/database.php`
- **"No clearance forms found"**: Add test data to the database
- **"PDF header check failed"**: May still be valid, but check template processing

## Notes

- Generated files are automatically cleaned up after testing
- Tests use real database data - ensure you have test data available
- The script validates PDF files by checking for `%PDF` header
- Excel files are generated as HTML tables for simplicity
