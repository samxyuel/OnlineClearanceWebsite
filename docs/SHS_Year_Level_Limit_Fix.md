# Senior High School Year Level Limit Fix

## Problem Statement

Senior High School (SHS) students are incorrectly incrementing beyond "2nd Year" when new academic years are created. After creating 4 school years, SHS students that should be limited to "2nd Year" are showing as "4th Year" in the database.

### Symptoms

- SHS students in the interface display as "2nd Year" (due to UI filtering)
- Database contains SHS students with year levels: "3rd Year" and "4th Year"
- Issue occurs automatically when new academic years are created
- No distinction between SHS and College students in the increment logic

## Root Cause Analysis

### Primary Issue: `api/clearance/years.php`

**Location:** Lines 188-225

The year level increment logic does **NOT** differentiate between College and Senior High School students. It applies the same increment mapping to all students:

```php
// Current problematic code:
$yearLevelMap = [
    '1st Year' => '2nd Year',
    '2nd Year' => '3rd Year',  // ← WRONG for SHS!
    '3rd Year' => '4th Year',
];

// Query doesn't filter by sector:
$studentsToIncrement = $pdo->prepare("
    SELECT s.student_id, s.user_id, s.year_level, s.sector, ...
    WHERE u.account_status = 'active'
    AND (s.retain_year_level_for_next_year = FALSE OR ...)
    AND s.year_level IN ('1st Year', '2nd Year', '3rd Year')  // ← Includes SHS!
");
```

**Problem Flow:**

1. New academic year is created
2. System increments ALL active students (College + SHS)
3. SHS students at "2nd Year" get incremented to "3rd Year"
4. Next year, "3rd Year" SHS students get incremented to "4th Year"
5. Database now contains invalid year levels for SHS students

### Expected Behavior

- **College Students:** Can progress: 1st Year → 2nd Year → 3rd Year → 4th Year
- **Senior High School Students:** Should only progress: 1st Year → 2nd Year (then graduate)

## Additional Issues Found

### 1. No Validation in Update Controller

**File:** `controllers/updateUsers.php` (lines 126, 152)

- Year level updates are accepted without sector validation
- Manual edits can set SHS students to invalid year levels (3rd/4th Year)
- No checks prevent invalid year level assignments

### 2. Display vs Database Mismatch

**File:** `api/clearance/get_filter_options.php` (lines 78-83)

- UI correctly limits SHS dropdowns to `['1st Year', '2nd Year']`
- This only affects **display** - database can still contain invalid values
- Creates confusion: UI shows "2nd Year" but database has "4th Year"

### 3. Data Import Paths

**File:** `controllers/importData.php` (lines 1844-1848)

- CSV imports may not validate sector when setting year levels
- Potential for invalid data entry through bulk imports

## Solution

### Fix 1: Sector-Aware Year Level Increment Logic

**File:** `api/clearance/years.php`

**Changes Required:**

1. **Separate year level mappings by sector:**

   ```php
   // College year level mapping
   $collegeYearLevelMap = [
       '1st Year' => '2nd Year',
       '2nd Year' => '3rd Year',
       '3rd Year' => '4th Year',
   ];

   // Senior High School year level mapping
   $shsYearLevelMap = [
       '1st Year' => '2nd Year',
       // SHS students at '2nd Year' should NOT be incremented
   ];
   ```

2. **Filter query by sector:**

   ```php
   $studentsToIncrement = $pdo->prepare("
       SELECT s.student_id, s.user_id, s.year_level, s.sector, ...
       WHERE u.account_status = 'active'
       AND (s.retain_year_level_for_next_year = FALSE OR ...)
       AND (
           (s.sector = 'College' AND s.year_level IN ('1st Year', '2nd Year', '3rd Year'))
           OR
           (s.sector = 'Senior High School' AND s.year_level = '1st Year')
       )
   ");
   ```

3. **Apply sector-specific mapping in loop:**

   ```php
   foreach ($students as $student) {
       $sector = $student['sector'];
       $yearLevelMap = ($sector === 'Senior High School') ? $shsYearLevelMap : $collegeYearLevelMap;

       if (isset($yearLevelMap[$currentYearLevel])) {
           // Increment only if mapping exists
           $newYearLevel = $yearLevelMap[$currentYearLevel];
           // Update student...
       } elseif ($sector === 'Senior High School' && $currentYearLevel === '2nd Year') {
           // Log skipped SHS students at 2nd Year
           // They should be marked as graduated before creating new year
       }
   }
   ```

### Implementation Details

**Key Points:**

- ✅ No database schema changes required
- ✅ Application-level validation only
- ✅ Backward compatible (doesn't break existing functionality)
- ✅ Logs skipped SHS students for audit trail

**Response Enhancement:**
Include `shs_skipped_count` in the API response to track how many SHS students were skipped:

```php
'year_level_increment' => [
    'incremented_count' => $incrementedCount,
    'retained_count' => $retainedCount,
    'shs_skipped_count' => $shsSkippedCount,  // New field
    'retention_flags_reset' => $resetCount
]
```

## Potential Issues After Implementation

### 1. Existing Invalid Data

**Issue:** SHS students already at 3rd/4th Year in database will remain invalid

**Impact:**

- These students won't be incremented further (good)
- But they'll still show invalid year levels in reports/queries

**Recommendations:**

- Add data cleanup script to identify and correct invalid records
- Or add display normalization in student list APIs to cap SHS at "2nd Year"

### 2. Graduation Workflow

**Issue:** SHS students at "2nd Year" should be marked as graduated before creating new year

**Current Behavior:**

- System skips them (doesn't increment)
- But they remain as "active" students

**Recommendation:**

- Add warning/check in "Add Year" modal if SHS students at 2nd Year exist
- Prompt admin to mark them as graduated first

### 3. Import/Export Validation

**Issue:** CSV imports may still allow invalid year levels

**Recommendation:**

- Add validation in `controllers/importData.php` to check sector before setting year level
- Reject imports with invalid year level for sector

### 4. Direct API Calls

**Issue:** Direct API calls may bypass validation

**Recommendation:**

- Add validation to all student update endpoints
- Consider adding validation in `controllers/updateUsers.php` (optional, user reverted this)

### 5. Display Consistency

**Issue:** If database has invalid values, display should normalize them

**Recommendation:**

- Add display normalization in student list APIs:
  ```php
  // In student list APIs, normalize SHS year levels
  if ($student['sector'] === 'Senior High School' &&
      in_array($student['year_level'], ['3rd Year', '4th Year'])) {
      $student['year_level'] = '2nd Year'; // Cap at max
      $student['year_level_normalized'] = true; // Flag for display
  }
  ```

## Testing Checklist

After implementing the fix:

- [ ] Create new academic year with SHS students at "1st Year" → Should increment to "2nd Year"
- [ ] Create new academic year with SHS students at "2nd Year" → Should NOT increment (skip)
- [ ] Create new academic year with College students → Should increment normally (1st→2nd→3rd→4th)
- [ ] Verify SHS students at "2nd Year" are logged as skipped
- [ ] Verify API response includes `shs_skipped_count`
- [ ] Test with retained students (should not increment regardless of sector)
- [ ] Test with graduated students (should not be included in increment query)

## Files Modified

1. **`api/clearance/years.php`**
   - Add sector-specific year level mappings
   - Filter increment query by sector
   - Apply sector-specific logic in increment loop
   - Add logging for skipped SHS students
   - Update API response to include `shs_skipped_count`

## Related Files (Not Modified, but Relevant)

- `controllers/updateUsers.php` - Could add validation here (user chose not to)
- `api/clearance/get_filter_options.php` - Already limits UI options correctly
- `controllers/importData.php` - Could add validation for imports
- `Modals/SHSEditStudentModal.php` - Already limits year level options in UI

## Notes

- **No database changes required** - All validation is application-level
- **Backward compatible** - Existing functionality remains intact
- **Future-proof** - Prevents the issue from recurring
- **Audit trail** - Logs skipped students for tracking

## Summary

The fix ensures that:

1. ✅ SHS students only increment from "1st Year" to "2nd Year"
2. ✅ SHS students at "2nd Year" are skipped (not incremented)
3. ✅ College students continue normal progression (1st→2nd→3rd→4th)
4. ✅ System logs skipped SHS students for audit purposes
5. ✅ No database schema changes needed

**Priority:** High - This prevents data corruption and maintains data integrity for SHS students.
