# Eligible for Graduation Modal

This modal allows administrators to manage the graduation status of 4th Year students. Students marked as "Graduated" will not be included in future clearance periods until their status is updated.

## Files Created

1. **`Modals/EligibleForGraduationModal.php`** - The main modal component
2. **`api/users/get_eligible_students.php`** - API endpoint to fetch eligible students
3. **`api/users/update_graduation_status.php`** - API endpoint to update graduation status
4. **`pages/admin/GraduationManagementDemo.php`** - Demo page showing integration

## Features

- **Filter by Sector**: College or Senior High School
- **Filter by Department**: Dynamic department loading based on sector
- **Search Functionality**: Search by name or student number
- **Bulk Selection**: Select all or individual students
- **Real-time Updates**: Live count of selected students
- **Responsive Design**: Works on desktop and mobile devices

## Integration Steps

### 1. Include the Modal

Add this to your student management page:

```php
<?php include '../../Modals/EligibleForGraduationModal.php'; ?>
```

### 2. Add a Button

Add a button to trigger the modal:

```html
<button class="btn btn-primary" onclick="openEligibleForGraduationModal()">
  <i class="fas fa-graduation-cap"></i> Manage Graduation
</button>
```

### 3. Listen for Updates (Optional)

Listen for graduation status updates to refresh your data:

```javascript
document.addEventListener("graduation-status-updated", function (event) {
  console.log("Graduation status updated:", event.detail);
  // Refresh your student list
  loadStudentsData(); // Your existing function
});
```

## API Endpoints

### Get Eligible Students

- **URL**: `api/users/get_eligible_students.php`
- **Method**: GET
- **Parameters**:
  - `year_level`: Default "4th Year"
  - `enrollment_status`: Default "Enrolled"
  - `sector`: Optional filter (College/Senior High School)
  - `department_id`: Optional department filter
  - `search`: Optional search term
  - `page`: Page number for pagination
  - `limit`: Items per page

### Update Graduation Status

- **URL**: `api/users/update_graduation_status.php`
- **Method**: POST
- **Body**:

```json
{
  "student_ids": [1, 2, 3],
  "action": "graduate" // or "retain"
}
```

## Database Schema

The modal works with the existing `students` table structure:

```sql
CREATE TABLE `students` (
  `student_id` varchar(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') DEFAULT NULL,
  `enrollment_status` enum('Enrolled','Graduated','Transferred','Dropped') DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
```

## Permissions

- Only users with 'Admin' or 'School Administrator' roles can access this modal
- The modal checks authentication and authorization before allowing any operations

## Usage Workflow

1. **Open Modal**: Click the "Manage Graduation" button
2. **Filter Students**: Use sector, department, and search filters to find specific students
3. **Select Students**: Use checkboxes to select students for graduation
4. **Process**: Click "Update Graduation Status" to mark selected students as graduated
5. **Confirmation**: The system will show success/error messages and log the activity

## Customization

### Styling

The modal includes its own CSS styles. You can customize the appearance by modifying the `<style>` section in the modal file.

### API Behavior

You can modify the API endpoints to change the graduation logic:

- Change which year levels are considered eligible
- Modify the graduation status update process
- Add additional validation rules

### Modal Behavior

The modal JavaScript can be customized to:

- Add additional filters
- Change the selection behavior
- Modify the confirmation process

## Testing

Use the demo page (`pages/admin/GraduationManagementDemo.php`) to test the modal functionality:

1. Navigate to the demo page
2. Click "Open Graduation Modal" to test the modal
3. Use "Test API Endpoints" to verify API functionality
4. Check the status log for event tracking

## Future Enhancements

Potential improvements for future versions:

1. **Batch Operations**: Add ability to update multiple students with different actions
2. **Graduation History**: Track graduation status changes over time
3. **Email Notifications**: Send notifications when students are marked as graduated
4. **Export Functionality**: Export graduation lists to CSV/Excel
5. **Advanced Filtering**: Add more filter options (program, section, etc.)
6. **Graduation Certificates**: Generate graduation certificates for eligible students

## Support

For issues or questions about this modal, please check:

1. Browser console for JavaScript errors
2. Server logs for PHP errors
3. Database connection and permissions
4. API endpoint accessibility
