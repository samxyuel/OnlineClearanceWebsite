# Faculty Import Feature Documentation

## Overview

The Faculty Import feature allows administrators to bulk import faculty data from various file formats into the Online Clearance System. This feature supports multiple import modes and provides comprehensive data validation.

## Features

### ✅ **Supported File Formats**

- **CSV (.csv)** - Comma-separated values
- **Excel (.xlsx, .xls)** - Microsoft Excel files
- **JSON (.json)** - JavaScript Object Notation
- **XML (.xml)** - Extensible Markup Language

### ✅ **Import Modes**

1. **Skip Existing** - Keep current data, skip duplicates
2. **Update Existing** - Update existing faculty records
3. **Replace All** - Replace all faculty data (use with caution)

### ✅ **Data Validation**

- Required field validation
- Email format validation
- Employment status validation
- Duplicate employee number checking
- Data integrity verification

### ✅ **User Experience**

- Drag & drop file upload
- Real-time file preview
- Progress indicators
- Detailed import summaries
- Error reporting
- Automatic table refresh

## File Structure

```
controllers/
├── importData.php          # Main import controller
├── logs/                   # Import activity logs
└── sample_imports/         # Sample files for testing
    ├── faculty_sample.csv
    └── faculty_sample.json
```

## Database Schema Requirements

### Required Fields

- `employee_number` - Unique employee identifier
- `last_name` - Faculty last name
- `first_name` - Faculty first name
- `email` - Valid email address

### Optional Fields

- `employment_status` - Full Time, Part Time, Part Time - Full Load
- `contact_number` - Phone number
- `middle_name` - Middle name or initial

### Database Tables Used

- `users` - User account information
- `faculty` - Faculty-specific data
- `user_roles` - Role assignments
- `audit_logs` - Import activity tracking

## Usage Instructions

### 1. Access Import Feature

- Navigate to Faculty Management page
- Click the "Import" button in the bulk actions section

### 2. Upload File

- Drag & drop file or click "Choose File"
- Supported formats: CSV, Excel, JSON, XML
- File size limit: 10MB (configurable)

### 3. Configure Import Options

- Select import mode (skip/update/replace)
- Enable/disable data validation
- Enable/disable welcome notifications

### 4. Review Data Mapping

- Verify field mappings match your file structure
- Adjust column mappings if needed

### 5. Preview Data

- Review imported data before processing
- Check for validation errors
- Verify data accuracy

### 6. Execute Import

- Click "Import Faculty Data"
- Monitor progress indicators
- Review import summary

## Sample File Formats

### CSV Format

```csv
employee_number,employment_status,last_name,first_name,email,contact_number
EMP001,Full Time,Santos,Maria,maria.santos@example.com,+63 912 345 6789
EMP002,Part Time,Dela Cruz,Juan,juan.delacruz@example.com,+63 923 456 7890
```

### JSON Format

```json
[
  {
    "employee_number": "EMP001",
    "employment_status": "Full Time",
    "last_name": "Santos",
    "first_name": "Maria",
    "email": "maria.santos@example.com",
    "contact_number": "+63 912 345 6789"
  }
]
```

### XML Format

```xml
<faculty_data>
  <faculty>
    <employee_number>EMP001</employee_number>
    <employment_status>Full Time</employment_status>
    <last_name>Santos</last_name>
    <first_name>Maria</first_name>
    <email>maria.santos@example.com</email>
    <contact_number>+63 912 345 6789</contact_number>
  </faculty>
</faculty_data>
```

## Security Features

### Authentication & Authorization

- User must be logged in
- Requires `import_data` permission
- Role-based access control

### Data Validation

- Input sanitization
- SQL injection prevention
- File type validation
- Size limit enforcement

### Audit Logging

- All import activities logged
- User tracking
- Timestamp recording
- Success/failure logging

## Error Handling

### Common Error Types

1. **File Upload Errors**

   - Invalid file format
   - File too large
   - Upload failure

2. **Data Validation Errors**

   - Missing required fields
   - Invalid email format
   - Duplicate employee numbers
   - Invalid employment status

3. **Database Errors**
   - Connection failures
   - Constraint violations
   - Transaction rollbacks

### Error Recovery

- Automatic transaction rollback on failure
- Detailed error messages
- Partial import prevention
- Data integrity maintenance

## Performance Considerations

### File Processing

- Stream-based CSV parsing
- Memory-efficient JSON processing
- Chunked data processing for large files

### Database Operations

- Transaction-based imports
- Batch processing capabilities
- Prepared statement usage
- Connection pooling

## Configuration Options

### System Settings

- Maximum file size: 10MB
- Supported file types
- Import timeout limits
- Batch size limits

### User Preferences

- Default import mode
- Validation preferences
- Notification settings
- Logging preferences

## Troubleshooting

### Common Issues

1. **Permission Denied**

   - Check user role and permissions
   - Verify `import_data` permission

2. **File Upload Fails**

   - Check file size limits
   - Verify file format
   - Check server upload settings

3. **Import Fails**
   - Review error logs
   - Check data validation
   - Verify database connectivity

### Debug Mode

- Enable detailed error logging
- Review import activity logs
- Check browser console for errors
- Verify network requests

## Future Enhancements

### Planned Features

- **Excel Library Integration** - Full Excel file support
- **Bulk Export** - Export faculty data
- **Template Downloads** - Pre-filled import templates
- **Scheduled Imports** - Automated import processing
- **Data Mapping Profiles** - Saved mapping configurations

### API Extensions

- RESTful import endpoints
- Webhook notifications
- Third-party integrations
- Mobile app support

## Support

### Documentation

- This README file
- Inline code comments
- API documentation
- User guides

### Technical Support

- System administrators
- Development team
- User community
- Issue tracking system

---

**Version:** 1.0.0  
**Last Updated:** August 25, 2025  
**Maintainer:** Development Team
