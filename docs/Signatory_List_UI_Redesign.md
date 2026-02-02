# Signatory List UI Redesign Documentation

## Overview

This document outlines the UI redesign of the **signatory list display** in `ClearanceManagement.php` to align with the designation-based approach implemented in the AddScopeSignatoryModal. The signatory list will show **designation names only** instead of "Designation — Staff Name".

---

## Background & Problem Statement

### Current UI Behavior
The signatory list displays signatories in the format:
```
Cashier — Agnes Theresa Eubion
Disciplinary Officer — Jovert Eldrich Test Villones
Registrar — Camille Mae Gabito
```

**Format:** `Designation Name — Staff First Name Last Name`

### Issues with Current Display

1. **Inconsistent with Modal**: The modal now shows/selects designations only
2. **Misleading Information**: Suggests only that specific person can sign
3. **Broken for New Assignments**: When `user_id = null`, displays as `"Cashier — "` (empty after dash)
4. **Doesn't Match Backend Reality**: Any staff with the designation can sign, not just the displayed person

### Backend Reality
From `api/signatories/sector_assignments.php` lines 58-60:
```sql
LEFT JOIN users u ON ssa.user_id = u.user_id
```

- **Old assignments** (user_id = 231): Returns name ✅
- **New assignments** (user_id = null): Returns NULL for name → displays as `"Cashier — "` ❌

---

## Solution: Designation-Only Display

### New UI Behavior
The signatory list will display:
```
Cashier
Disciplinary Officer
Registrar
```

**Format:** `Designation Name` (only)

---

## Design Goals

1. ✅ Display **designation names only** (no staff names)
2. ✅ Maintain all existing functionality (Required First/Last badges, removal)
3. ✅ Fix broken display for new assignments (user_id = null)
4. ✅ Consistent with modal UI redesign
5. ✅ Keep all API connections intact

---

## UI Comparison: Before vs After

### Current Display (Staff-Based)

```html
<div class="signatory-list" id="collegeSignatoryList">
    <div class="signatory-item-header">
        <strong>Program Head (Dynamic)</strong>
        <span class="signatory-requirement">(Assigned based on student's department)</span>
    </div>
    
    <div class="signatory-item required-first">
        <span class="signatory-name">Cashier — Agnes Theresa Eubion</span>
        <span class="signatory-requirement">(Required First)</span>
        <button class="remove-signatory" onclick="removeScope('College', 231, 2, 'Cashier')">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="signatory-item optional">
        <span class="signatory-name">Disciplinary Officer — Jovert Eldrich Test Villones</span>
        <button class="remove-signatory" onclick="removeScope('College', 280, 15, 'Disciplinary Officer')">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="signatory-item required-last">
        <span class="signatory-name">Registrar — Camille Mae Gabito</span>
        <span class="signatory-requirement">(Required Last)</span>
        <button class="remove-signatory" onclick="removeScope('College', 232, 1, 'Registrar')">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
```

**Visual Display:**
```
┌─────────────────────────────────────────────────────┐
│  Program Head (Dynamic)                             │
│  (Assigned based on student's department)           │
├─────────────────────────────────────────────────────┤
│  Cashier — Agnes Theresa Eubion                  [×]│
│  (Required First)                                   │
├─────────────────────────────────────────────────────┤
│  Disciplinary Officer — Jovert Eldrich Test      [×]│
│  Villones                                           │
├─────────────────────────────────────────────────────┤
│  Registrar — Camille Mae Gabito                  [×]│
│  (Required Last)                                    │
└─────────────────────────────────────────────────────┘
```

---

### New Display (Designation-Based)

```html
<div class="signatory-list" id="collegeSignatoryList">
    <div class="signatory-item-header">
        <strong>Program Head (Dynamic)</strong>
        <span class="signatory-requirement">(Assigned based on student's department)</span>
    </div>
    
    <div class="signatory-item required-first">
        <span class="signatory-name">Cashier</span>
        <span class="signatory-requirement">(Required First)</span>
        <button class="remove-signatory" onclick="removeScope('College', null, 2, 'Cashier')">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="signatory-item optional">
        <span class="signatory-name">Disciplinary Officer</span>
        <button class="remove-signatory" onclick="removeScope('College', null, 15, 'Disciplinary Officer')">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="signatory-item required-last">
        <span class="signatory-name">Registrar</span>
        <span class="signatory-requirement">(Required Last)</span>
        <button class="remove-signatory" onclick="removeScope('College', null, 1, 'Registrar')">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
```

**Visual Display:**
```
┌─────────────────────────────────────────────────────┐
│  Program Head (Dynamic)                             │
│  (Assigned based on student's department)           │
├─────────────────────────────────────────────────────┤
│  Cashier                                         [×]│
│  (Required First)                                   │
├─────────────────────────────────────────────────────┤
│  Disciplinary Officer                            [×]│
├─────────────────────────────────────────────────────┤
│  Registrar                                       [×]│
│  (Required Last)                                    │
└─────────────────────────────────────────────────────┘
```

---

## Implementation Details

### Location
**File:** `pages/admin/ClearanceManagement.php`

**Function:** `loadScopeSignatories(type)`

**Lines:** 2164-2201 (approximately)

### Current Code

```javascript
finalHtml += items.map(it => {
    let itemClass = 'signatory-item optional';
    let requirementText = '';
    
    // Check if this signatory is Required First
    if (settings.required_first_enabled && settings.required_first_designation_id) {
        const isRequiredFirst = it.designation_id === settings.required_first_designation_id;
        if (isRequiredFirst) {
            itemClass = 'signatory-item required-first';
            requirementText = '<span class="signatory-requirement">(Required First)</span>';
        }
    }
    
    // Check if this signatory is Required Last
    if (settings.required_last_enabled && settings.required_last_designation_id) {
        const isRequiredLast = it.designation_id === settings.required_last_designation_id;
        if (isRequiredLast) {
            itemClass = 'signatory-item required-last';
            requirementText = '<span class="signatory-requirement">(Required Last)</span>';
        }
    }
    
    // Add department info for Program Heads
    let departmentInfo = '';
    if (it.is_program_head && it.department_name) {
        departmentInfo = ` <span style="color:#6c757d;font-size:12px;">(${it.department_name})</span>`;
    }
    
    return `
        <div class="${itemClass}">
            <span class="signatory-name">${it.designation_name} — ${[it.first_name, it.last_name].filter(Boolean).join(' ')}${departmentInfo}</span>
            ${requirementText}
            <button class="remove-signatory" onclick="removeScope('${type}', ${it.user_id}, ${it.designation_id}, '${it.designation_name.replace(/'/g, "\'")}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
}).join('');
```

### New Code (Changes)

**Line 2194 - Display Change:**
```javascript
// OLD:
<span class="signatory-name">${it.designation_name} — ${[it.first_name, it.last_name].filter(Boolean).join(' ')}${departmentInfo}</span>

// NEW:
<span class="signatory-name">${it.designation_name}${departmentInfo}</span>
```

**Line 2196 - Remove Button Change:**
```javascript
// OLD:
onclick="removeScope('${type}', ${it.user_id}, ${it.designation_id}, '${it.designation_name.replace(/'/g, "\'")}')"

// NEW:
onclick="removeScope('${type}', ${it.user_id || null}, ${it.designation_id}, '${it.designation_name.replace(/'/g, "\'")}')"
```

---

## Key Changes Summary

| Aspect | Old (Staff-Based) | New (Designation-Based) |
|--------|-------------------|-------------------------|
| **Display Format** | `Cashier — Agnes Theresa Eubion` | `Cashier` |
| **Staff Name** | Shown after dash | Not shown |
| **Remove Button** | Uses `it.user_id` | Uses `it.user_id || null` |
| **Program Head Info** | Still shows department | Still shows department |
| **Required Badges** | Unchanged | Unchanged |
| **Remove Functionality** | Works | Works (handles null) |

---

## API Compatibility Verification

### 1. Display Function (`loadScopeSignatories`)

**API Endpoint:** `GET api/signatories/sector_assignments.php?clearance_type=College`

**Returns:**
```json
{
  "success": true,
  "signatories": [
    {
      "user_id": 231,              // Can be null for new assignments
      "designation_id": 2,
      "designation_name": "Cashier",
      "first_name": "Agnes Theresa",  // null if user_id is null
      "last_name": "Eubion"           // null if user_id is null
    }
  ]
}
```

**New Code Behavior:**
- Only uses `designation_name` ✅
- Ignores `first_name` and `last_name` ✅
- No errors if names are null ✅

---

### 2. Remove Function (`removeScope`)

**Current Function Call:**
```javascript
removeScope('College', 231, 2, 'Cashier')
// Parameters: (clearanceType, userId, designationId, designationName)
```

**New Function Call:**
```javascript
removeScope('College', null, 2, 'Cashier')
// Parameters: (clearanceType, userId, designationId, designationName)
```

**API Endpoint:** `DELETE api/signatories/sector_assignments.php`

**Request Body:**
```json
{
  "clearance_type": "College",
  "user_id": null,
  "designation_id": 2
}
```

**API DELETE SQL** (line 181):
```sql
UPDATE sector_signatory_assignments 
SET is_active = 0, updated_at = NOW()
WHERE clearance_type = ? AND user_id = ? AND designation_id = ?
```

**With `user_id = null`:**
```sql
WHERE clearance_type = 'College' AND user_id IS NULL AND designation_id = 2
```

✅ **Will correctly match and delete records with `user_id = null`**

**With `user_id = 231` (old assignments):**
```sql
WHERE clearance_type = 'College' AND user_id = 231 AND designation_id = 2
```

✅ **Will still work for old assignments**

---

## Benefits of This Change

### 1. **Fixes Broken Display**
- **Before:** New assignments show as `"Cashier — "` (broken)
- **After:** Shows as `"Cashier"` (clean) ✅

### 2. **Consistent with Modal**
- Modal selects designations only
- List displays designations only
- Perfect consistency ✅

### 3. **Matches Backend Reality**
- Any staff with designation can sign
- Display no longer implies specific person ✅

### 4. **Backward Compatible**
- Old assignments (with user_id) still work
- New assignments (with user_id = null) work
- No data migration needed ✅

### 5. **Cleaner UI**
- Less visual clutter
- Faster to scan
- Clearer intent ✅

---

## Edge Cases & Considerations

### 1. **Program Heads**
**Special Case:** Program Heads may still show department info

**Current Code (line 2187-2189):**
```javascript
let departmentInfo = '';
if (it.is_program_head && it.department_name) {
    departmentInfo = ` <span style="color:#6c757d;font-size:12px;">(${it.department_name})</span>`;
}
```

**Display:**
- `Program Head (IT Department)` - Shows department ✅
- This is intentional since PH is department-specific

### 2. **Old Assignments (with user_id)**
**Question:** What if old assignments still have `user_id = 231`?

**Answer:** 
- Display will show designation only (no staff name)
- Removal will use the existing user_id
- Both work correctly ✅

### 3. **Mixed Assignments**
**Scenario:** Some signatories have `user_id`, others have `user_id = null`

**Result:**
- All display as designation only
- Removal works for both types
- No issues ✅

### 4. **Required First/Last Validation**
**Current Validation (lines 2242-2250):**
```javascript
const signatoryData = await fetchJSON(...);
const signatory = signatoryData.signatories?.find(s => s.user_id === userId);

if (signatory && signatory.designation_id === settings.required_first_designation_id) {
    showToast('This signatory is currently set as Required First...', 'warning');
    return;
}
```

**Issue:** Uses `s.user_id === userId` which won't work if `userId` is null

**Solution:** Should use `designation_id` for matching instead:
```javascript
const signatory = signatoryData.signatories?.find(s => s.designation_id === designationId);
```

---

## Additional Changes Needed

### Update `removeScope()` Validation Logic

**Current Code (lines 2244-2250):**
```javascript
const signatoryData = await fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`);
const signatory = signatoryData.signatories?.find(s => s.user_id === userId);

if (signatory && signatory.designation_id === settings.required_first_designation_id) {
    showToast('This signatory is currently set as Required First. Please disable this feature in Settings before removing the signatory.', 'warning');
    return;
}
```

**New Code (Should use designation_id):**
```javascript
const signatoryData = await fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`);
const signatory = signatoryData.signatories?.find(s => s.designation_id === designationId);

if (signatory && signatory.designation_id === settings.required_first_designation_id) {
    showToast('This signatory is currently set as Required First. Please disable this feature in Settings before removing the signatory.', 'warning');
    return;
}
```

**Same for Required Last validation (lines 2254-2260)**

---

## Implementation Checklist

### Phase 1: Update Display Function
- [ ] Change line 2194: Remove staff name display
- [ ] Keep designation_name only
- [ ] Preserve departmentInfo for Program Heads
- [ ] Test display with new assignments (user_id = null)
- [ ] Test display with old assignments (user_id = 231)

### Phase 2: Update Remove Function Call
- [ ] Change line 2196: Use `${it.user_id || null}`
- [ ] Ensure null is passed for new assignments
- [ ] Test removal of new assignments
- [ ] Test removal of old assignments

### Phase 3: Update Validation Logic
- [ ] Update line 2245: Use `s.designation_id === designationId` instead of `s.user_id === userId`
- [ ] Update line 2255: Same change for Required Last
- [ ] Test Required First removal validation
- [ ] Test Required Last removal validation

### Phase 4: Testing
- [ ] Test display for College sector
- [ ] Test display for Senior High School sector
- [ ] Test display for Faculty sector
- [ ] Test removal functionality
- [ ] Test Required First/Last badges
- [ ] Test Program Head dynamic header
- [ ] Test with mixed assignments (some null, some with user_id)

---

## Testing Scenarios

### Test Case 1: Display New Assignments
1. Add "Cashier" designation using modal (user_id = null)
2. View signatory list
3. Expected: Shows "Cashier" only (no staff name)
4. Expected: No broken display (no "Cashier — ")

### Test Case 2: Display Old Assignments
1. View signatory list with old assignments (user_id = 231)
2. Expected: Shows "Cashier" only (no staff name)
3. Expected: Consistent display with new assignments

### Test Case 3: Remove New Assignment
1. Click remove button on new assignment (user_id = null)
2. Expected: Confirmation prompt
3. Expected: Signatory removed successfully
4. Expected: List refreshes without that designation

### Test Case 4: Remove Old Assignment
1. Click remove button on old assignment (user_id = 231)
2. Expected: Confirmation prompt
3. Expected: Signatory removed successfully
4. Expected: List refreshes without that designation

### Test Case 5: Required First Validation
1. Set Cashier as Required First
2. Try to remove Cashier
3. Expected: Warning message
4. Expected: Removal blocked

### Test Case 6: Program Head Display
1. Enable Program Head for sector
2. View signatory list
3. Expected: Shows "Program Head (Dynamic)" header
4. Expected: Shows department info if applicable

### Test Case 7: Mixed Assignments
1. Have some signatories with user_id, others with null
2. View signatory list
3. Expected: All display consistently (designation only)
4. Expected: All removal buttons work

---

## Visual Mock-ups

### College Sector - Before
```
┌────────────────────────────────────────────────────────────┐
│  📋 College Signatories                                    │
├────────────────────────────────────────────────────────────┤
│  Program Head (Dynamic)                                    │
│  (Assigned based on student's department)                  │
├────────────────────────────────────────────────────────────┤
│  Cashier — Agnes Theresa Eubion                         [×]│
│  (Required First)                                          │
├────────────────────────────────────────────────────────────┤
│  Guidance — John Smith                                  [×]│
├────────────────────────────────────────────────────────────┤
│  Clinic — Maria Garcia                                  [×]│
├────────────────────────────────────────────────────────────┤
│  Registrar — Camille Mae Gabito                         [×]│
│  (Required Last)                                           │
└────────────────────────────────────────────────────────────┘
```

### College Sector - After
```
┌────────────────────────────────────────────────────────────┐
│  📋 College Signatories                                    │
├────────────────────────────────────────────────────────────┤
│  Program Head (Dynamic)                                    │
│  (Assigned based on student's department)                  │
├────────────────────────────────────────────────────────────┤
│  Cashier                                                [×]│
│  (Required First)                                          │
├────────────────────────────────────────────────────────────┤
│  Guidance                                               [×]│
├────────────────────────────────────────────────────────────┤
│  Clinic                                                 [×]│
├────────────────────────────────────────────────────────────┤
│  Registrar                                              [×]│
│  (Required Last)                                           │
└────────────────────────────────────────────────────────────┘
```

---

## Code Changes Summary

### File: `pages/admin/ClearanceManagement.php`

#### Change 1: Display (Line ~2194)
```javascript
// BEFORE:
<span class="signatory-name">${it.designation_name} — ${[it.first_name, it.last_name].filter(Boolean).join(' ')}${departmentInfo}</span>

// AFTER:
<span class="signatory-name">${it.designation_name}${departmentInfo}</span>
```

#### Change 2: Remove Button (Line ~2196)
```javascript
// BEFORE:
onclick="removeScope('${type}', ${it.user_id}, ${it.designation_id}, '${it.designation_name.replace(/'/g, "\'")}')"

// AFTER:
onclick="removeScope('${type}', ${it.user_id || null}, ${it.designation_id}, '${it.designation_name.replace(/'/g, "\'")}')"
```

#### Change 3: Validation (Line ~2245)
```javascript
// BEFORE:
const signatory = signatoryData.signatories?.find(s => s.user_id === userId);

// AFTER:
const signatory = signatoryData.signatories?.find(s => s.designation_id === designationId);
```

#### Change 4: Validation (Line ~2255)
```javascript
// BEFORE (line ~2255):
const signatoryData = await fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`);
const signatory = signatoryData.signatories?.find(s => s.user_id === userId);

// AFTER:
// (Already fetched above, just reuse and change the find logic)
const signatory = signatoryData.signatories?.find(s => s.designation_id === designationId);
```

---

## Related Documentation

- [AddScopeSignatoryModal UI Redesign](./AddScopeSignatoryModal_UI_Redesign.md)
- [Signatory Name Preservation Implementation Plan](./Signatory_Name_Preservation_Implementation_Plan.md)

---

## Approval & Sign-off

- [ ] UI/UX Review
- [ ] Technical Review
- [ ] Security Review
- [ ] Admin User Acceptance
- [ ] Implementation Approved

---

**Document Version:** 1.0  
**Created:** January 15, 2026  
**Last Updated:** January 15, 2026  
**Status:** Draft - Awaiting Approval  
**Related To:** AddScopeSignatoryModal UI Redesign
