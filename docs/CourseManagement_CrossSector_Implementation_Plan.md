# Course Management Cross-Sector Implementation Plan

**Created:** January 2025  
**Status:** 📋 Planning Phase  
**Purpose:** Comprehensive implementation plan for cross-sector department indicators and course eligibility logic in Course Management

---

## Table of Contents

1. [Business Rules & Requirements](#business-rules--requirements)
2. [Files to Modify](#files-to-modify)
3. [Detailed Implementation Changes](#detailed-implementation-changes)
4. [CSS Styles to Add](#css-styles-to-add)
5. [User Experience Impact](#user-experience-impact)
6. [Implementation Checklist](#implementation-checklist)

---

## Business Rules & Requirements

### Core Business Logic

1. **Auto-Creation Rule:**

   - Creating a "College" department → Automatically creates in **College + Faculty** sectors (course-eligible)
   - Creating a "Senior High School" department → Automatically creates in **SHS + Faculty** sectors (course-eligible)
   - Creating a "Faculty Only" department → Creates only in **Faculty** sector (NOT course-eligible)

2. **Course Eligibility:**

   - Departments with **College (sector_id = 1)** or **SHS (sector_id = 2)** sectors can have courses
   - Faculty-only departments (sector_id = 3 only) **cannot** have courses
   - Logic: `canAddCourses = department.sectors.some(s => s.sector_id === 1 || s.sector_id === 2)`

3. **Course Assignment:**

   - When adding a course, use the **student sector's `department_id`** (College or SHS), not the Faculty one
   - If department exists in both College and Faculty, use College `department_id`

4. **Department Type Options:**
   - Remove "All-Sectors" option from Add Department Modal
   - Remove explicit "College-Faculty" and "SHS-Faculty" options (handled automatically)
   - Keep: "College", "Senior High School", "Faculty Only"

---

## Files to Modify

### Primary Files

1. **`pages/admin/CourseManagement.php`** - Main page with department cards and tabs
2. **`Modals/AddDepartmentModal.php`** - Department creation modal
3. **`Modals/EditDepartmentModal.php`** - Department editing modal
4. **`Modals/AddCourseModal.php`** - Course creation modal
5. **`Modals/EditCourseModal.php`** - Course editing modal
6. **`assets/css/styles.css`** - Cross-sector indicator styles

---

## Detailed Implementation Changes

### 1. CourseManagement.php

#### A. Data Fetching & Merging

**Add new function to merge cross-sector data:**

```javascript
/**
 * Merge cross-sector department information with course data
 * @param {Object} deptData - Response from api/departments/list.php
 * @param {Object} courseData - Response from api/course_data.php
 * @returns {Object} Merged data with cross-sector info
 */
function mergeDepartmentAndCourseData(deptData, courseData) {
  // Create map: department_id -> cross-sector info
  const crossSectorMap = new Map();
  deptData.departments.forEach((dept) => {
    dept.sectors.forEach((sector) => {
      crossSectorMap.set(sector.department_id, {
        is_shared: dept.is_shared,
        sectors: dept.sectors,
        department_name: dept.department_name,
        department_code: dept.department_code,
      });
    });
  });

  // Merge into course data
  const merged = {
    departments: {
      college: [],
      senior_high: [],
      faculty: [],
    },
  };

  ["college", "senior_high", "faculty"].forEach((sectorKey) => {
    const depts = courseData.data?.departments[sectorKey] || [];
    depts.forEach((dept) => {
      const crossSectorInfo = crossSectorMap.get(dept.department_id);
      if (crossSectorInfo) {
        Object.assign(dept, crossSectorInfo);
        // Add course eligibility flag
        dept.canAddCourses = crossSectorInfo.sectors.some(
          (s) => s.sector_id === 1 || s.sector_id === 2
        );
      } else {
        // Fallback for departments not in cross-sector map
        dept.canAddCourses = false;
      }
    });
    merged.departments[sectorKey] = depts;
  });

  return merged;
}
```

**Update `fetchCourseData()` function:**

```javascript
async function fetchCourseData() {
  setLoadingState(true);
  try {
    // Fetch departments with cross-sector info
    const deptResponse = await fetch(
      "../../api/departments/list.php?limit=500",
      {
        credentials: "include",
      }
    );
    const deptData = await deptResponse.json();

    // Fetch course data (programs)
    const query = courseManagementState.includeInactive
      ? "?include_inactive=1"
      : "";
    const courseResponse = await fetch(`../../api/course_data.php${query}`, {
      credentials: "include",
    });
    const coursePayload = await courseResponse.json();

    // Merge data: combine cross-sector department info with course data
    const mergedData = mergeDepartmentAndCourseData(deptData, coursePayload);

    courseManagementState.data = {
      ...coursePayload.data,
      departments: mergedData.departments,
    };

    setLoadingState(false);

    sectorOrder.forEach((sectorKey) => {
      renderDepartments(sectorKey);
    });
    updateStatistics(courseManagementState.activeSector);
    updateTabCrossSectorBadges(); // Add this call
  } catch (error) {
    console.error("Error loading course data:", error);
    setLoadingState(false);
    showErrorState(error.message || "Unable to load course data.");
  }
}
```

#### B. Helper Functions

**Add helper functions for cross-sector indicators:**

```javascript
/**
 * Get sector key from sector ID
 * @param {number} sectorId - Sector ID (1=College, 2=SHS, 3=Faculty)
 * @returns {string} Sector key
 */
function getSectorKeyFromId(sectorId) {
  const map = { 1: "college", 2: "senior_high", 3: "faculty" };
  return map[sectorId] || null;
}

/**
 * Create cross-sector badge element
 * @param {Array<string>} otherSectors - Array of other sector names
 * @returns {HTMLElement} Badge element
 */
function createCrossSectorBadge(otherSectors) {
  const badge = document.createElement("div");
  badge.className = "cross-sector-badge";
  badge.innerHTML = `
        <i class="fas fa-link"></i>
        <span>Also in: ${otherSectors.join(", ")}</span>
    `;
  return badge;
}

/**
 * Create sector badges showing all sectors
 * @param {Array} sectors - Array of sector objects
 * @param {string} currentSectorKey - Current tab's sector key
 * @returns {HTMLElement} Container with sector badges
 */
function createSectorBadges(sectors, currentSectorKey) {
  const container = document.createElement("div");
  container.className = "sector-badges";

  sectors.forEach((sector) => {
    const badge = document.createElement("span");
    badge.className = `sector-badge sector-${getSectorKeyFromId(
      sector.sector_id
    )}`;
    badge.textContent = sector.sector_name;
    container.appendChild(badge);
  });

  return container;
}

/**
 * Update tab badges with cross-sector department counts
 */
function updateTabCrossSectorBadges() {
  const tabs = {
    college: document.querySelector("[onclick*=\"switchTab('college'\"]"),
    "senior-high": document.querySelector(
      "[onclick*=\"switchTab('senior-high'\"]"
    ),
    faculty: document.querySelector("[onclick*=\"switchTab('faculty'\"]"),
  };

  ["college", "senior_high", "faculty"].forEach((sectorKey) => {
    const departments = getDepartmentsForSector(sectorKey);
    const sharedCount = departments.filter(
      (d) => d.is_shared && d.sectors && d.sectors.length > 1
    ).length;

    const tabKey = sectorKey === "senior_high" ? "senior-high" : sectorKey;
    const tabButton = tabs[tabKey];

    if (tabButton && sharedCount > 0) {
      // Remove existing badge if any
      const existingBadge = tabButton.querySelector(".tab-shared-badge");
      if (existingBadge) {
        existingBadge.remove();
      }

      // Add new badge
      const badge = document.createElement("span");
      badge.className = "tab-shared-badge";
      badge.textContent = sharedCount;
      badge.title = `${sharedCount} department${
        sharedCount > 1 ? "s" : ""
      } shared with other sectors`;
      tabButton.appendChild(badge);
    } else if (tabButton) {
      // Remove badge if count is 0
      const existingBadge = tabButton.querySelector(".tab-shared-badge");
      if (existingBadge) {
        existingBadge.remove();
      }
    }
  });
}
```

#### C. Update Department Card Creation

**Modify `createDepartmentCard()` function:**

```javascript
function createDepartmentCard(department, sectorKey) {
  const card = document.createElement("div");
  card.className = "department-card";
  card.dataset.departmentId = department.department_id;
  card.dataset.departmentName = (
    department.department_name || ""
  ).toLowerCase();
  card.dataset.departmentCode = (
    department.department_code || ""
  ).toLowerCase();

  // Add shared class if cross-sector
  if (
    department.is_shared &&
    department.sectors &&
    department.sectors.length > 1
  ) {
    card.classList.add("shared-department");
  }

  const header = document.createElement("div");
  header.className = "department-card-header";

  const iconWrapper = document.createElement("span");
  iconWrapper.className = "department-icon";
  iconWrapper.innerHTML = `<i class="${getDepartmentIconClass(
    sectorKey
  )}"></i>`;
  header.appendChild(iconWrapper);

  const idSpan = document.createElement("span");
  idSpan.className = "department-id";
  idSpan.textContent =
    department.department_code || `ID-${department.department_id}`;
  header.appendChild(idSpan);

  // ADD: Cross-sector badge if shared
  if (
    department.is_shared &&
    department.sectors &&
    department.sectors.length > 1
  ) {
    const otherSectors = department.sectors
      .filter((s) => getSectorKeyFromId(s.sector_id) !== sectorKey)
      .map((s) => s.sector_name);

    if (otherSectors.length > 0) {
      const badge = createCrossSectorBadge(otherSectors);
      header.appendChild(badge);
    }
  }

  // ADD: Sector badges showing all sectors
  if (department.sectors && department.sectors.length > 0) {
    const sectorBadges = createSectorBadges(department.sectors, sectorKey);
    header.appendChild(sectorBadges);
  }

  card.appendChild(header);

  const body = document.createElement("div");
  body.className = "department-card-body";

  const nameEl = document.createElement("h4");
  nameEl.className = "department-name";
  nameEl.textContent = department.department_name || "Unnamed Department";
  body.appendChild(nameEl);

  // ADD: Faculty-only indicator if applicable
  if (
    !department.canAddCourses &&
    department.sectors &&
    department.sectors.length === 1 &&
    department.sectors[0].sector_id === 3
  ) {
    const facultyOnlyIndicator = document.createElement("div");
    facultyOnlyIndicator.className = "faculty-only-indicator";
    facultyOnlyIndicator.innerHTML = `
            <i class="fas fa-info-circle"></i>
            <span>Faculty-only (No courses)</span>
        `;
    body.appendChild(facultyOnlyIndicator);
  }

  const programCount = (department.programs || []).length;
  const coursesEl = document.createElement("p");
  coursesEl.className = "department-courses";
  coursesEl.textContent = `${programCount} ${
    programCount === 1 ? "Course" : "Courses"
  }`;
  body.appendChild(coursesEl);

  const statusEl = document.createElement("p");
  statusEl.className = "department-status";
  statusEl.textContent =
    department.status || (department.is_active ? "Active" : "Inactive");
  body.appendChild(statusEl);

  const spacer = document.createElement("div");
  spacer.className = "department-content-spacer";
  body.appendChild(spacer);

  card.appendChild(body);

  const actions = document.createElement("div");
  actions.className = "department-card-actions";
  const departmentId = String(department.department_id);
  const departmentCode = department.department_code || departmentId;
  const sectorLabel = department.sector_label || getSectorLabel(sectorKey);

  // MODIFY: Only show "Add Course" if department is course-eligible
  if (department.canAddCourses && sectorKey !== "faculty") {
    actions.appendChild(
      createActionButton(
        "btn btn-sm btn-outline-primary",
        '<i class="fas fa-plus"></i> Add Course',
        () => openAddCourseModal(departmentId)
      )
    );
  }

  actions.appendChild(
    createActionButton(
      "btn btn-sm btn-outline-secondary",
      '<i class="fas fa-edit"></i> Edit',
      () => openEditDepartmentModal(departmentId)
    )
  );

  actions.appendChild(
    createActionButton(
      "btn btn-sm btn-outline-danger",
      '<i class="fas fa-trash"></i> Delete',
      () => deleteDepartment(departmentId, department.department_name)
    )
  );

  card.appendChild(actions);

  // ... rest of card creation (course preview, etc.) ...

  return card;
}
```

**Update `switchTab()` to refresh badges:**

```javascript
function switchTab(tabName, buttonElement) {
  const normalized = normalizeTabName(tabName);
  courseManagementState.activeSector = normalized;

  document
    .querySelectorAll(".compact-tab-button")
    .forEach((btn) => btn.classList.remove("active"));
  if (buttonElement) {
    buttonElement.classList.add("active");
  }

  Object.values(sectorIdMap).forEach((id) => {
    const container = document.getElementById(id);
    if (container) {
      container.classList.remove("active");
    }
  });

  const activeContainer = getSectorContainer(normalized);
  if (activeContainer) {
    activeContainer.classList.add("active");
  }

  updateStatistics(normalized);
  renderDepartments(normalized);
  updateTabCrossSectorBadges(); // ADD: Update badges when switching tabs
}
```

---

### 2. AddDepartmentModal.php

#### A. Update Department Type Options

**Remove options from HTML:**

```html
<!-- BEFORE -->
<optgroup label="Shared Departments (Cross-Sector)">
  <option value="college-faculty">College & Faculty</option>
  <option value="shs-faculty">Senior High School & Faculty</option>
  <option value="all-sectors">All Sectors (College, SHS, Faculty)</option>
</optgroup>

<!-- AFTER -->
<!-- Remove entire "Shared Departments" optgroup -->
```

**Keep only:**

```html
<optgroup label="Single Sector">
  <option value="college">College</option>
  <option value="senior-high">Senior High School</option>
  <option value="faculty">Faculty Only</option>
</optgroup>
```

#### B. Update Info Text

**Modify `updateDepartmentTypeInfo()` function:**

```javascript
function updateDepartmentTypeInfo() {
  const type = document.getElementById("departmentType").value;
  const infoDiv = document.getElementById("departmentTypeInfo");

  const info = {
    college:
      "Will be created in College and Faculty sectors. This department can have courses.",
    "senior-high":
      "Will be created in Senior High School and Faculty sectors. This department can have courses.",
    faculty:
      "Will be created in Faculty sector only. Faculty-only departments cannot have courses.",
  };

  if (type && info[type]) {
    infoDiv.innerHTML = `<small style="color: var(--deep-navy-blue);">${info[type]}</small>`;
    infoDiv.style.display = "block";
  } else {
    infoDiv.style.display = "none";
  }
}
```

**Note:** Backend (`api/departments/create.php`) already handles auto-creation, but verify the mapping:

```php
// Verify this mapping in api/departments/create.php
$sectorMap = [
    'college' => [1, 3], // College + Faculty (MODIFY if needed)
    'senior-high' => [2, 3], // SHS + Faculty (MODIFY if needed)
    'faculty' => [3], // Faculty only
];
```

---

### 3. EditDepartmentModal.php

#### A. Add Department Code Field

**Add to form (after Department Name field):**

```html
<div class="form-group">
  <label for="editDepartmentCode">Department Code *</label>
  <input
    type="text"
    id="editDepartmentCode"
    name="departmentCode"
    required
    placeholder="e.g., ICT"
    maxlength="10"
    pattern="[A-Z0-9]+"
    title="Uppercase letters and numbers only"
    style="text-transform: uppercase;"
  />
  <small class="form-help">Must be unique across all sectors</small>
</div>
```

#### B. Add Cross-Sector Warning

**Add after form opening tag:**

```html
<!-- Cross-Sector Warning Banner -->
<div
  id="crossSectorWarning"
  class="cross-sector-warning"
  style="display: none;"
>
  <i class="fas fa-exclamation-triangle"></i>
  <div>
    <strong>Cross-Sector Department</strong>
    <p id="crossSectorSectors"></p>
    <p class="warning-text">
      Updating name or code will affect all sectors where this department
      exists.
    </p>
  </div>
</div>
```

#### C. Update JavaScript Functions

**Replace `openEditDepartmentModalInternal()` with real API call:**

```javascript
async function openEditDepartmentModalInternal(departmentId) {
  try {
    const modal = document.getElementById("editDepartmentModal");
    if (!modal) {
      if (typeof showToastNotification === "function") {
        showToastNotification(
          "Edit department modal not found. Please refresh the page.",
          "error"
        );
      }
      return;
    }

    // Fetch department with cross-sector info
    const response = await fetch("../../api/departments/list.php?limit=500", {
      credentials: "include",
    });
    const data = await response.json();

    // Find the department
    let department = null;
    data.departments.forEach((dept) => {
      dept.sectors.forEach((sector) => {
        if (sector.department_id == departmentId) {
          department = {
            ...dept,
            current_department_id: sector.department_id,
            current_sector_id: sector.sector_id,
          };
        }
      });
    });

    if (!department) {
      showToastNotification("Department not found", "error");
      return;
    }

    // Populate form
    document.getElementById("editDepartmentId").value =
      department.current_department_id;
    document.getElementById("editDepartmentName").value =
      department.department_name;
    document.getElementById("editDepartmentCode").value =
      department.department_code || "";

    // Set department type based on sectors
    const hasCollege = department.sectors.some((s) => s.sector_id === 1);
    const hasSHS = department.sectors.some((s) => s.sector_id === 2);
    const hasFaculty = department.sectors.some((s) => s.sector_id === 3);

    let deptType = "";
    if (hasCollege && hasFaculty && !hasSHS) {
      deptType = "college";
    } else if (hasSHS && hasFaculty && !hasCollege) {
      deptType = "senior-high";
    } else if (hasFaculty && !hasCollege && !hasSHS) {
      deptType = "faculty";
    }

    document.getElementById("editDepartmentType").value = deptType;
    document.getElementById("editDepartmentStatus").value = department.is_active
      ? "active"
      : "inactive";

    // Show cross-sector warning if shared
    const warningDiv = document.getElementById("crossSectorWarning");
    const sectorsP = document.getElementById("crossSectorSectors");
    if (department.is_shared && department.sectors.length > 1) {
      const sectorsList = department.sectors
        .map((s) => s.sector_name)
        .join(", ");
      sectorsP.textContent = `This department exists in: ${sectorsList}`;
      warningDiv.style.display = "flex";
    } else {
      warningDiv.style.display = "none";
    }

    // Show/hide course management section based on eligibility
    const courseSection = document.querySelector(".course-management-section");
    const canAddCourses = department.sectors.some(
      (s) => s.sector_id === 1 || s.sector_id === 2
    );

    if (courseSection) {
      if (canAddCourses) {
        courseSection.style.display = "block";
        loadDepartmentCourses(departmentId);
      } else {
        courseSection.style.display = "none";
        // Show faculty-only message
        const noCoursesMsg = document.getElementById("noCoursesMessage");
        if (noCoursesMsg) {
          noCoursesMsg.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        <p>This is a faculty-only department. Courses are not applicable.</p>
                    `;
          noCoursesMsg.style.display = "block";
        }
      }
    }

    // Use window.openModal if available, otherwise fallback
    if (typeof window.openModal === "function") {
      window.openModal("editDepartmentModal");
    } else {
      modal.style.display = "flex";
      document.body.style.overflow = "hidden";
      document.body.classList.add("modal-open");
      requestAnimationFrame(() => {
        modal.classList.add("active");
      });
    }
  } catch (error) {
    console.error("Error loading department data:", error);
    if (typeof showToastNotification === "function") {
      showToastNotification(
        "Unable to load department data. Please try again.",
        "error"
      );
    }
  }
}
```

---

### 4. AddCourseModal.php

#### A. Update Department Dropdown Filtering

**Modify `populateCourseDepartments()` function:**

```javascript
async function populateCourseDepartments() {
  const deptSelect = document.getElementById("courseDepartment");
  if (!deptSelect) return;

  try {
    const response = await fetch("../../api/departments/list.php?limit=500", {
      credentials: "include",
    });
    const data = await response.json();

    if (data.success && data.departments) {
      deptSelect.innerHTML = '<option value="">Select department</option>';

      // FILTER: Only include departments that have College or SHS sectors (course-eligible)
      const courseEligibleDepts = data.departments.filter((dept) => {
        return dept.sectors.some(
          (sector) => sector.sector_id === 1 || sector.sector_id === 2
        );
      });

      // Group by sector (only College and SHS)
      const bySector = {};
      courseEligibleDepts.forEach((dept) => {
        dept.sectors.forEach((sector) => {
          // Only include College and SHS sectors
          if (sector.sector_id === 1 || sector.sector_id === 2) {
            const sectorName = sector.sector_name || "Unknown";
            if (!bySector[sectorName]) bySector[sectorName] = [];
            bySector[sectorName].push({
              department_id: sector.department_id, // Use student sector's ID
              department_name: dept.department_name,
              department_code: dept.department_code,
              is_shared: dept.is_shared,
            });
          }
        });
      });

      // Show departments grouped by sector (College, SHS only)
      const sectorOrder = ["College", "Senior High School"];
      sectorOrder.forEach((sector) => {
        if (bySector[sector] && bySector[sector].length > 0) {
          const optgroup = document.createElement("optgroup");
          optgroup.label = sector;

          bySector[sector].forEach((dept) => {
            const option = document.createElement("option");
            option.value = dept.department_id;
            let text = dept.department_name;
            if (dept.department_code) {
              text += ` (${dept.department_code})`;
            }
            // Show indicator if shared
            if (dept.is_shared) {
              text += " [Shared]";
            }
            option.textContent = text;
            optgroup.appendChild(option);
          });

          deptSelect.appendChild(optgroup);
        }
      });
    }
  } catch (error) {
    console.error("Failed to load departments:", error);
    deptSelect.innerHTML =
      '<option value="">Error loading departments</option>';
  }
}
```

---

### 5. EditCourseModal.php

#### A. Replace Hardcoded Dropdown with Dynamic Loading

**Add function to populate departments (same as AddCourseModal):**

```javascript
async function populateEditCourseDepartments() {
  const deptSelect = document.getElementById("editCourseDepartment");
  if (!deptSelect) return;

  try {
    const response = await fetch("../../api/departments/list.php?limit=500", {
      credentials: "include",
    });
    const data = await response.json();

    if (data.success && data.departments) {
      deptSelect.innerHTML = '<option value="">Select department</option>';

      // FILTER: Only include course-eligible departments
      const courseEligibleDepts = data.departments.filter((dept) => {
        return dept.sectors.some(
          (sector) => sector.sector_id === 1 || sector.sector_id === 2
        );
      });

      // Group by sector (only College and SHS)
      const bySector = {};
      courseEligibleDepts.forEach((dept) => {
        dept.sectors.forEach((sector) => {
          if (sector.sector_id === 1 || sector.sector_id === 2) {
            const sectorName = sector.sector_name || "Unknown";
            if (!bySector[sectorName]) bySector[sectorName] = [];
            bySector[sectorName].push({
              department_id: sector.department_id,
              department_name: dept.department_name,
              department_code: dept.department_code,
              is_shared: dept.is_shared,
            });
          }
        });
      });

      const sectorOrder = ["College", "Senior High School"];
      sectorOrder.forEach((sector) => {
        if (bySector[sector] && bySector[sector].length > 0) {
          const optgroup = document.createElement("optgroup");
          optgroup.label = sector;

          bySector[sector].forEach((dept) => {
            const option = document.createElement("option");
            option.value = dept.department_id;
            let text = dept.department_name;
            if (dept.department_code) {
              text += ` (${dept.department_code})`;
            }
            if (dept.is_shared) {
              text += " [Shared]";
            }
            option.textContent = text;
            optgroup.appendChild(option);
          });

          deptSelect.appendChild(optgroup);
        }
      });
    }
  } catch (error) {
    console.error("Failed to load departments:", error);
    deptSelect.innerHTML =
      '<option value="">Error loading departments</option>';
  }
}
```

**Update `openEditCourseModalInternal()` to load departments:**

```javascript
async function openEditCourseModalInternal(courseCode) {
  try {
    const modal = document.getElementById("editCourseModal");
    if (!modal) {
      if (typeof showToastNotification === "function") {
        showToastNotification(
          "Edit course modal not found. Please refresh the page.",
          "error"
        );
      }
      return;
    }

    // Populate departments dropdown
    await populateEditCourseDepartments();

    // Fetch course data (replace mock function with real API call)
    // const courseData = await fetchCourseData(courseCode);
    const courseData = getCourseData(courseCode); // Temporary - replace with API call

    // Populate form fields
    const courseIdField = document.getElementById("editCourseId");
    const courseCodeField = document.getElementById("editCourseCode");
    const courseNameField = document.getElementById("editCourseName");
    const courseDeptField = document.getElementById("editCourseDepartment");
    const courseStatusField = document.getElementById("editCourseStatus");
    const courseDescField = document.getElementById("editCourseDescription");

    if (courseIdField) courseIdField.value = courseCode;
    if (courseCodeField) courseCodeField.value = courseData.code;
    if (courseNameField) courseNameField.value = courseData.name;
    if (courseDeptField) courseDeptField.value = courseData.department;
    if (courseStatusField) courseStatusField.value = courseData.status;
    if (courseDescField) courseDescField.value = courseData.description || "";

    // Use window.openModal if available, otherwise fallback
    if (typeof window.openModal === "function") {
      window.openModal("editCourseModal");
    } else {
      modal.style.display = "flex";
      document.body.style.overflow = "hidden";
      document.body.classList.add("modal-open");
      requestAnimationFrame(() => {
        modal.classList.add("active");
      });
    }
  } catch (error) {
    if (typeof showToastNotification === "function") {
      showToastNotification(
        "Unable to open edit course modal. Please try again.",
        "error"
      );
    }
  }
}
```

---

## CSS Styles to Add

### Add to `assets/css/styles.css`

```css
/* ============================================
   Cross-Sector Department Indicators
   ============================================ */

/* Tab Shared Count Badge */
.course-management-container .tab-shared-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 6px;
  background: linear-gradient(
    135deg,
    var(--darker-saturated-blue) 0%,
    var(--deep-navy-blue) 100%
  );
  color: white;
  border-radius: 9px;
  font-size: 0.65rem;
  font-weight: 600;
  margin-left: 6px;
  box-shadow: 0 2px 4px rgba(12, 85, 145, 0.3);
  line-height: 1;
}

.course-management-container .tab-shared-badge:empty {
  display: none;
}

/* Cross-Sector Indicator Badge */
.course-management-container .cross-sector-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px;
  background: linear-gradient(
    135deg,
    var(--darker-saturated-blue) 0%,
    var(--deep-navy-blue) 100%
  );
  color: white;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 600;
  margin-left: 8px;
  box-shadow: 0 2px 4px rgba(12, 85, 145, 0.3);
  white-space: nowrap;
}

.course-management-container .cross-sector-badge i {
  font-size: 0.65rem;
  opacity: 0.9;
}

/* Cross-sector icon (minimal style alternative) */
.course-management-container .cross-sector-icon {
  color: var(--darker-saturated-blue);
  font-size: 0.8rem;
  margin-left: 6px;
  cursor: help;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.course-management-container .cross-sector-icon:hover {
  opacity: 1;
}

/* Shared Department Visual Indicator */
.course-management-container .department-card.shared-department {
  border-left: 4px solid var(--darker-saturated-blue);
  position: relative;
}

/* Subtle background tint for shared departments */
.course-management-container .department-card.shared-department::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    to right,
    rgba(23, 93, 151, 0.03) 0%,
    transparent 4px
  );
  pointer-events: none;
  border-radius: 8px;
}

/* Enhanced hover for shared departments */
.course-management-container .department-card.shared-department:hover {
  box-shadow: 0 4px 12px rgba(12, 85, 145, 0.2);
  transform: translateY(-2px);
  border-left-color: var(--deep-navy-blue);
}

/* Small indicator dot in top-right corner */
.course-management-container .department-card.shared-department::after {
  content: "";
  position: absolute;
  top: 8px;
  right: 8px;
  width: 8px;
  height: 8px;
  background: var(--darker-saturated-blue);
  border-radius: 50%;
  box-shadow: 0 0 0 2px white, 0 0 0 4px var(--darker-saturated-blue);
  z-index: 1;
}

/* Sector Badges Container */
.course-management-container .sector-badges {
  display: flex;
  gap: 6px;
  margin-top: 6px;
  flex-wrap: wrap;
}

/* Individual Sector Badge */
.course-management-container .sector-badge {
  background: linear-gradient(
    135deg,
    var(--darker-saturated-blue) 0%,
    var(--deep-navy-blue) 100%
  );
  color: white;
  padding: 3px 8px;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  box-shadow: 0 2px 4px rgba(12, 85, 145, 0.3);
}

/* Faculty-Only Department Indicator */
.course-management-container .faculty-only-indicator {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px;
  background: rgba(
    95,
    107,
    122,
    0.15
  ); /* Using --soft-slate-gray with opacity */
  color: var(--soft-slate-gray);
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 500;
  font-style: italic;
  margin-left: 8px;
  margin-top: 4px;
}

.course-management-container .faculty-only-indicator i {
  font-size: 0.65rem;
}

/* Cross-Sector Warning (for Edit Modal) */
.cross-sector-warning {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 8px;
  margin-bottom: 24px;
  align-items: flex-start;
}

.cross-sector-warning i {
  color: #f59e0b;
  font-size: 1.5rem;
  margin-top: 2px;
}

.cross-sector-warning strong {
  display: block;
  color: #92400e;
  margin-bottom: 4px;
}

.cross-sector-warning p {
  margin: 4px 0;
  color: #78350f;
  font-size: 0.9rem;
}

.cross-sector-warning .warning-text {
  font-weight: 600;
  color: #b45309;
}
```

---

## User Experience Impact

### Before Implementation

- ❌ No visual indication of cross-sector departments
- ❌ All departments show "Add Course" button (even faculty-only)
- ❌ Unclear which departments can have courses
- ❌ No indication of shared departments across tabs
- ❌ Confusing department type options in Add Modal
- ❌ No warning when editing cross-sector departments

### After Implementation

- ✅ **Clear Visual Indicators:**

  - Tab badges show shared department counts
  - Department cards show cross-sector badges
  - Blue left border for shared departments
  - Sector badges showing all sectors

- ✅ **Smart Course Management:**

  - "Add Course" button only on course-eligible departments
  - Faculty-only departments clearly marked
  - Course modals filter to relevant departments only

- ✅ **Improved Modals:**

  - Simplified department type options
  - Clear info text explaining auto-creation
  - Cross-sector warnings when editing
  - Conditional course management section

- ✅ **Better User Guidance:**
  - Users understand which departments can have courses
  - Clear indication of cross-sector relationships
  - Warnings prevent accidental cross-sector changes

---

## Implementation Checklist

### Phase 1: Data Integration

- [ ] Add `mergeDepartmentAndCourseData()` function to CourseManagement.php
- [ ] Update `fetchCourseData()` to fetch and merge cross-sector data
- [ ] Add `getSectorKeyFromId()` helper function
- [ ] Test data merging and verify `canAddCourses` flag

### Phase 2: Visual Indicators

- [ ] Add CSS styles for cross-sector indicators
- [ ] Add `updateTabCrossSectorBadges()` function
- [ ] Add `createCrossSectorBadge()` function
- [ ] Add `createSectorBadges()` function
- [ ] Update `createDepartmentCard()` with indicators
- [ ] Update `switchTab()` to refresh badges
- [ ] Test visual indicators display correctly

### Phase 3: Conditional Logic

- [ ] Add conditional "Add Course" button logic
- [ ] Add faculty-only indicator
- [ ] Test button visibility on different department types

### Phase 4: AddDepartmentModal

- [ ] Remove "All-Sectors" option
- [ ] Remove explicit cross-sector options
- [ ] Update `updateDepartmentTypeInfo()` with new messages
- [ ] Verify backend auto-creates Faculty for College/SHS

### Phase 5: EditDepartmentModal

- [ ] Add Department Code field
- [ ] Add cross-sector warning banner HTML
- [ ] Replace mock `getDepartmentData()` with real API call
- [ ] Add conditional course management section
- [ ] Add faculty-only message
- [ ] Test warning display for shared departments

### Phase 6: AddCourseModal

- [ ] Update `populateCourseDepartments()` to filter course-eligible only
- [ ] Ensure student sector `department_id` is used
- [ ] Test dropdown excludes Faculty-only departments

### Phase 7: EditCourseModal

- [ ] Add `populateEditCourseDepartments()` function
- [ ] Replace hardcoded dropdown with dynamic loading
- [ ] Filter to course-eligible departments only
- [ ] Test dropdown functionality

### Phase 8: Testing

- [ ] Test tab badges show correct counts
- [ ] Test department cards show correct indicators
- [ ] Test "Add Course" button visibility
- [ ] Test Add Department modal with all types
- [ ] Test Edit Department modal warnings
- [ ] Test Add Course modal filtering
- [ ] Test Edit Course modal filtering
- [ ] Test cross-sector department creation
- [ ] Test faculty-only department creation
- [ ] Verify course assignment uses correct `department_id`

---

## Notes

1. **Backend Verification:** Ensure `api/departments/create.php` has the correct sector mapping:

   ```php
   $sectorMap = [
       'college' => [1, 3], // College + Faculty
       'senior-high' => [2, 3], // SHS + Faculty
       'faculty' => [3], // Faculty only
   ];
   ```

2. **API Endpoints Used:**

   - `api/departments/list.php` - For cross-sector department data
   - `api/course_data.php` - For course/program data
   - `api/departments/create.php` - For department creation (verify mapping)

3. **Color Consistency:** All styles use brand colors:

   - `var(--darker-saturated-blue)` - Primary blue
   - `var(--deep-navy-blue)` - Darker blue
   - `var(--soft-slate-gray)` - Muted gray for faculty-only

4. **Browser Compatibility:** Test cross-sector indicators in:
   - Chrome/Edge (latest)
   - Firefox (latest)
   - Safari (latest)

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** 📋 Ready for Implementation
