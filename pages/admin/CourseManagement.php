<?php
// Session management handled by header component
// Temporarily disable session check for interface development
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../../login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/components/header.php'; ?>

    <!-- Main Content Area -->
    <main class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-layout">
                <!-- LEFT SIDE: Main Content -->
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <div class="course-management-container">
                            <!-- Page Header -->
                            <div class="page-header">
                                <h2 class="page-title"><i class="fa-solid fa-book"></i>  Course Management</h2>
                                <p class="page-description">Manage departments and courses for student registration</p>
                            </div>

                            <!-- Quick Statistics Card (Compact Style) -->
                            <div class="management-card quick-stats-card">
                                <div class="card-header">
                                    <h3><i class="fas fa-chart-bar"></i> Quick Statistics</h3>
                                </div>
                                <div class="card-content">
                                    <div class="compact-stats-grid">
                                        <div class="stat-item">
                                            <i class="fas fa-building"></i>
                                            <span class="stat-value" id="totalDepartments">5</span>
                                            <span class="stat-label">Departments</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-book"></i>
                                            <span class="stat-value" id="totalCourses">15</span>
                                            <span class="stat-label">Courses</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-users"></i>
                                            <span class="stat-value" id="totalStudents">0</span>
                                            <span class="stat-label">Students</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span class="stat-value" id="activeDepartments">5</span>
                                            <span class="stat-label">Active</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Bar Card (Compact Style) -->
                            <div class="management-card action-bar-card">
                                <div class="card-header">
                                    <h3><i class="fas fa-search"></i> Search & Actions</h3>
                                </div>
                                <div class="card-content">
                                    <div class="compact-search-actions">
                                        <div class="search-section">
                                            <div class="search-wrapper">
                                                <i class="fas fa-search search-icon"></i>
                                                <input type="text" class="search-input" placeholder="Search departments..." id="searchInput">
                                            </div>
                                        </div>
                                        <div class="actions-section">
                                            <div class="include-inactive-toggle">
                                                <label class="course-toggle-label">
                                                    <input type="checkbox" id="includeInactiveToggle">
                                                    <span>Include Inactive</span>
                                                </label>
                                            </div>
                                            <div class="left-actions">
                                            <button class="btn btn-outline btn-compact" onclick="openImportModal()">
                                                <i class="fas fa-download"></i>
                                                <span>Import</span>
                                            </button>
                                            </div>
                                            <div class="right-actions">
                                                <button class="btn btn-primary btn-compact" onclick="openAddDepartmentModal()">
                                                    <i class="fas fa-plus"></i>
                                                    <span>Add Department</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Navigation Section (Fixed at Top) -->
                            <div class="management-card tab-navigation-card">
                                <div class="card-header">
                                    <h3><i class="fas fa-folder"></i> Department Categories</h3>
                                </div>
                                    <div class="compact-tab-navigation">
                                        <button class="compact-tab-button active" onclick="switchTab('college', this)">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>College</span>
                                        </button>
                                        <button class="compact-tab-button" onclick="switchTab('senior-high', this)">
                                            <i class="fas fa-school"></i>
                                            <span>Senior High School</span>
                                        </button>
                                    <button class="compact-tab-button" onclick="switchTab('faculty', this)">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Faculty</span>
                                    </button>
                                </div>
                                    </div>
                                    
                            <!-- Department Cards Container (Scrollable) -->
                            <div class="management-card departments-scrollable-card">
                                <div class="card-content">
                                    <!-- College Departments -->
                                    <div class="departments-container active" id="college-departments">
                                        <div class="departments-placeholder">
                                            <i class="fas fa-spinner fa-spin"></i> Loading departments...
                                </div>
                            </div>

                            <!-- Senior High School Departments -->
                            <div class="departments-container" id="senior-high-departments">
                                        <div class="departments-placeholder">
                                            <i class="fas fa-spinner fa-spin"></i> Loading departments...
                                </div>
                            </div>

                            <!-- Faculty Departments -->
                            <div class="departments-container" id="faculty-departments">
                                        <div class="departments-placeholder">
                                            <i class="fas fa-spinner fa-spin"></i> Loading departments...
                                    </div>
                                    </div>
                        </div>
                    </div>
                </div> <!-- Close departments-scrollable-card -->
            </div> <!-- Close content-wrapper -->
        </div> <!-- Close dashboard-main or dashboard-layout rather -->

       

                <!-- Include Modal Files -->
                <?php include '../../Modals/AddDepartmentModal.php'; ?>
                <?php include '../../Modals/EditDepartmentModal.php'; ?>
                <?php include '../../Modals/AddCourseModal.php'; ?>
                <?php include '../../Modals/EditCourseModal.php'; ?>
                <?php include '../../Modals/CourseImportModal.php'; ?>
                <?php // include '../../Modals/AssignProgramHeadModal.php'; ?>
                
                <!-- Add Modal Function Wrappers -->
                <script>
                    // Modal function wrappers - use standardized approach
                    function openAddDepartmentModal() {
                        try {
                            if (typeof window.openAddDepartmentModalInternal === 'function') {
                                window.openAddDepartmentModalInternal();
                            } else {
                                // Fallback to window.openModal or direct manipulation
                                const modal = document.getElementById('addDepartmentModal');
                                if (modal) {
                                    if (typeof window.openModal === 'function') {
                                        window.openModal('addDepartmentModal');
                                    } else {
                                        modal.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';
                                        document.body.classList.add('modal-open');
                                    }
                                }
                            }
                        } catch (error) {
                            if (typeof showToastNotification === 'function') {
                                showToastNotification('Unable to open add department modal. Please try again.', 'error');
                            }
                        }
                    }
                    
                    function openEditDepartmentModal(departmentId) {
                        try {
                            if (typeof window.openEditDepartmentModalInternal === 'function') {
                                window.openEditDepartmentModalInternal(departmentId);
                            } else {
                                // Fallback to window.openModal or direct manipulation
                                const modal = document.getElementById('editDepartmentModal');
                                if (modal) {
                                    if (typeof window.openModal === 'function') {
                                        window.openModal('editDepartmentModal');
                                    } else {
                                        modal.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';
                                        document.body.classList.add('modal-open');
                                    }
                                }
                            }
                        } catch (error) {
                            if (typeof showToastNotification === 'function') {
                                showToastNotification('Unable to open edit department modal. Please try again.', 'error');
                            }
                        }
                    }
                    
                    function openAddCourseModal(departmentId) {
                        try {
                            if (typeof window.openAddCourseModalInternal === 'function') {
                                window.openAddCourseModalInternal(departmentId);
                            } else {
                                // Fallback to window.openModal or direct manipulation
                                const modal = document.getElementById('addCourseModal');
                                if (modal) {
                                    if (typeof window.openModal === 'function') {
                                        window.openModal('addCourseModal');
                                    } else {
                                        modal.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';
                                        document.body.classList.add('modal-open');
                                    }
                                }
                            }
                        } catch (error) {
                            if (typeof showToastNotification === 'function') {
                                showToastNotification('Unable to open add course modal. Please try again.', 'error');
                            }
                        }
                    }
                    
                    function openEditCourseModal(courseCode) {
                        try {
                            if (typeof window.openEditCourseModalInternal === 'function') {
                                window.openEditCourseModalInternal(courseCode);
                            } else {
                                // Fallback to window.openModal or direct manipulation
                                const modal = document.getElementById('editCourseModal');
                                if (modal) {
                                    if (typeof window.openModal === 'function') {
                                        window.openModal('editCourseModal');
                                    } else {
                                        modal.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';
                                        document.body.classList.add('modal-open');
                                    }
                                }
                            }
                        } catch (error) {
                            if (typeof showToastNotification === 'function') {
                                showToastNotification('Unable to open edit course modal. Please try again.', 'error');
                            }
                        }
                    }
                    
                    function openImportModal() {
                        try {
                            if (typeof window.openImportModalInternal === 'function') {
                                window.openImportModalInternal();
                            } else {
                                // Fallback to window.openModal or direct manipulation
                                const modal = document.getElementById('courseImportModal');
                                if (modal) {
                                    if (typeof window.openModal === 'function') {
                                        window.openModal('courseImportModal');
                                    } else {
                                        modal.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';
                                        document.body.classList.add('modal-open');
                                    }
                                }
                            }
                        } catch (error) {
                            if (typeof showToastNotification === 'function') {
                                showToastNotification('Unable to open import modal. Please try again.', 'error');
                            }
                        }
                    }
                </script>
            </main>
            </div> <!-- Close dashboard-layout -->

            <script src="../../assets/js/alerts.js"></script>
            
            <!-- Include Audit Functions -->
            <?php include '../../includes/functions/audit_functions.php'; ?>
            
            <!-- Include Alerts Component -->
            <?php include '../../includes/components/alerts.php'; ?>
            
            <!-- Alerts system initialization -->
            <script>
                // Initialize alerts system
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('Alerts system initialized successfully');
                });
            </script>
            
            <script>
                const courseManagementState = {
                    includeInactive: false,
                    activeSector: 'college',
                    searchTerm: '',
                    data: null,
                    loading: false
                };

                const sectorIdMap = {
                    college: 'college-departments',
                    senior_high: 'senior-high-departments',
                    faculty: 'faculty-departments'
                };

                const sectorOrder = ['college', 'senior_high', 'faculty'];

                document.addEventListener('DOMContentLoaded', function() {
                    const includeInactiveCheckbox = document.getElementById('includeInactiveToggle');
                    if (includeInactiveCheckbox) {
                        includeInactiveCheckbox.addEventListener('change', function() {
                            courseManagementState.includeInactive = this.checked;
                            fetchCourseData();
                        });
                    }

                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.addEventListener('input', function(event) {
                            courseManagementState.searchTerm = event.target.value.trim();
                            renderDepartments(courseManagementState.activeSector);
                        });
                    }

                    fetchCourseData();
                });

                function normalizeTabName(tabName) {
                    if (tabName === 'senior-high') {
                        return 'senior_high';
                    }
                    return tabName;
                }

                function switchTab(tabName, buttonElement) {
                    const normalized = normalizeTabName(tabName);
                    courseManagementState.activeSector = normalized;

                    document.querySelectorAll('.compact-tab-button').forEach(btn => btn.classList.remove('active'));
                    if (buttonElement) {
                        buttonElement.classList.add('active');
                    }

                    Object.values(sectorIdMap).forEach(id => {
                        const container = document.getElementById(id);
                        if (container) {
                        container.classList.remove('active');
                        }
                    });

                    const activeContainer = getSectorContainer(normalized);
                    if (activeContainer) {
                        activeContainer.classList.add('active');
                    }

                    updateStatistics(normalized);
                    renderDepartments(normalized);
                    updateTabCrossSectorBadges(); // ADD: Update badges when switching tabs
                }

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
                    const container = document.createElement("span");
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

                        if (!coursePayload || coursePayload.success !== true) {
                            throw new Error((coursePayload && coursePayload.message) || 'Unable to load course data.');
                        }

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

                function setLoadingState(isLoading) {
                    courseManagementState.loading = isLoading;
                    sectorOrder.forEach(sectorKey => {
                        const container = getSectorContainer(sectorKey);
                        if (!container) {
                            return;
                        }

                        if (isLoading) {
                            showPlaceholder(container, 'fas fa-spinner fa-spin', 'Loading departments...');
                        } else if (!courseManagementState.data) {
                            showPlaceholder(container, 'fas fa-circle-exclamation', 'No data available.');
                        }
                    });
                }

                function showPlaceholder(container, iconClass, message) {
                    container.innerHTML = `
                        <div class="departments-placeholder">
                            <i class="${iconClass}"></i> ${message}
                        </div>
                    `;
                }

                function showErrorState(message) {
                    const activeContainer = getSectorContainer(courseManagementState.activeSector);
                    if (activeContainer) {
                        showPlaceholder(activeContainer, 'fas fa-triangle-exclamation', message);
                    }
                }

                function getSectorContainer(sectorKey) {
                    const elementId = sectorIdMap[sectorKey];
                    return elementId ? document.getElementById(elementId) : null;
                }

                function getDepartmentsForSector(sectorKey) {
                    if (!courseManagementState.data || !courseManagementState.data.departments) {
                        return [];
                    }
                    return courseManagementState.data.departments[sectorKey] || [];
                }

                function renderDepartments(sectorKey) {
                    const container = getSectorContainer(sectorKey);
                    if (!container) {
                        return;
                    }

                    if (courseManagementState.loading) {
                        showPlaceholder(container, 'fas fa-spinner fa-spin', 'Loading departments...');
                        return;
                    }

                    const departments = filterDepartments(
                        getDepartmentsForSector(sectorKey),
                        courseManagementState.searchTerm
                    );

                    if (!departments.length) {
                        const message = courseManagementState.searchTerm
                            ? 'No departments match your search.'
                            : 'No departments available.';
                        const icon = courseManagementState.searchTerm ? 'fas fa-search' : 'fas fa-inbox';
                        showPlaceholder(container, icon, message);
                        return;
                    }

                    const fragment = document.createDocumentFragment();
                    departments.forEach(department => {
                        fragment.appendChild(createDepartmentCard(department, sectorKey));
                    });
                    container.innerHTML = '';
                    container.appendChild(fragment);
                }

                function filterDepartments(departments, searchTerm) {
                    if (!searchTerm) {
                        return departments;
                    }

                    const term = searchTerm.toLowerCase();
                    return departments.filter(department => {
                        const nameMatch = (department.department_name || '').toLowerCase().includes(term);
                        const codeMatch = (department.department_code || '').toLowerCase().includes(term);
                        const statusMatch = (department.status || '').toLowerCase().includes(term);

                        const programMatch = (department.programs || []).some(program => {
                            const programName = (program.program_name || '').toLowerCase();
                            const programCode = (program.program_code || '').toLowerCase();
                            return programName.includes(term) || programCode.includes(term);
                        });

                        return nameMatch || codeMatch || statusMatch || programMatch;
                    });
                }

                function updateStatistics(sectorKey) {
                    const departments = getDepartmentsForSector(sectorKey);
                    const totalDepartments = departments.length;
                    const totalCourses = departments.reduce((sum, department) => {
                        return sum + ((department.programs || []).length);
                    }, 0);
                    const totalStudents = departments.reduce((sum, department) => {
                        return sum + (department.total_students || 0);
                    }, 0);
                    const activeDepartments = departments.filter(dept => dept.is_active).length;

                    const totalDepartmentsEl = document.getElementById('totalDepartments');
                    const totalCoursesEl = document.getElementById('totalCourses');
                    const totalStudentsEl = document.getElementById('totalStudents');
                    const activeDepartmentsEl = document.getElementById('activeDepartments');

                    if (totalDepartmentsEl) totalDepartmentsEl.textContent = totalDepartments;
                    if (totalCoursesEl) totalCoursesEl.textContent = totalCourses;
                    if (totalStudentsEl) totalStudentsEl.textContent = totalStudents;
                    if (activeDepartmentsEl) activeDepartmentsEl.textContent = activeDepartments;
                }

                function createDepartmentCard(department, sectorKey) {
                    const card = document.createElement('div');
                    card.className = 'department-card';
                    card.dataset.departmentId = department.department_id;
                    card.dataset.departmentName = (department.department_name || '').toLowerCase();
                    card.dataset.departmentCode = (department.department_code || '').toLowerCase();

                    // Add shared class if cross-sector
                    if (department.is_shared && department.sectors && department.sectors.length > 1) {
                        card.classList.add('shared-department');
                    }

                    const header = document.createElement('div');
                    header.className = 'department-card-header';

                    const iconWrapper = document.createElement('span');
                    iconWrapper.className = 'department-icon';
                    iconWrapper.innerHTML = `<i class="${getDepartmentIconClass(sectorKey)}"></i>`;
                    header.appendChild(iconWrapper);

                    const idSpan = document.createElement('span');
                    idSpan.className = 'department-id';
                    idSpan.textContent = department.department_code || `ID-${department.department_id}`;
                    header.appendChild(idSpan);

                    // ADD: Cross-sector badge if shared
                    if (department.is_shared && department.sectors && department.sectors.length > 1) {
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

                    const body = document.createElement('div');
                    body.className = 'department-card-body';

                    const nameEl = document.createElement('h4');
                    nameEl.className = 'department-name';
                    nameEl.textContent = department.department_name || 'Unnamed Department';
                    body.appendChild(nameEl);

                    // ADD: Faculty-only indicator if applicable
                    if (
                        !department.canAddCourses &&
                        department.sectors &&
                        department.sectors.length === 1 &&
                        department.sectors[0].sector_id === 3
                    ) {
                        const facultyOnlyIndicator = document.createElement('div');
                        facultyOnlyIndicator.className = 'faculty-only-indicator';
                        facultyOnlyIndicator.innerHTML = `
                            <i class="fas fa-info-circle"></i>
                            <span>Faculty-only (No courses)</span>
                        `;
                        body.appendChild(facultyOnlyIndicator);
                    }

                    const programCount = (department.programs || []).length;
                    const coursesEl = document.createElement('p');
                    coursesEl.className = 'department-courses';
                    coursesEl.textContent = `${programCount} ${programCount === 1 ? 'Course' : 'Courses'}`;
                    body.appendChild(coursesEl);

                    const statusEl = document.createElement('p');
                    statusEl.className = 'department-status';
                    statusEl.textContent = department.status || (department.is_active ? 'Active' : 'Inactive');
                    body.appendChild(statusEl);

                    const spacer = document.createElement('div');
                    spacer.className = 'department-content-spacer';
                    body.appendChild(spacer);

                    card.appendChild(body);

                    const actions = document.createElement('div');
                    actions.className = 'department-card-actions';
                    const departmentId = String(department.department_id);
                    const departmentCode = department.department_code || departmentId;
                    const sectorLabel = department.sector_label || getSectorLabel(sectorKey);

                    // MODIFY: Only show "Add Course" if department is course-eligible
                    if (department.canAddCourses && sectorKey !== 'faculty') {
                        actions.appendChild(createActionButton(
                            'btn btn-sm btn-outline-primary',
                            '<i class="fas fa-plus"></i> Add Course',
                            () => openAddCourseModal(departmentId)
                        ));
                    }

                    actions.appendChild(createActionButton(
                        'btn btn-sm btn-outline-secondary',
                        '<i class="fas fa-edit"></i> Edit',
                        () => openEditDepartmentModal(departmentId)
                    ));

                    // actions.appendChild(createActionButton(
                    //     'btn btn-sm btn-outline-primary',
                    //     '<i class="fas fa-user-tie"></i> Assign/Change Program Head',
                    //     () => openAssignPHModal(sectorLabel, department.department_name, departmentCode)
                    // ));

                    actions.appendChild(createActionButton(
                        'btn btn-sm btn-outline-danger',
                        '<i class="fas fa-trash"></i> Delete',
                        () => deleteDepartment(departmentId, department.department_name)
                    ));

                    card.appendChild(actions);

                    const coursePreview = document.createElement('div');
                    coursePreview.className = 'course-preview';

                    const previewContent = document.createElement('div');
                    previewContent.className = 'course-preview-content';

                    const previewTitle = document.createElement('div');
                    previewTitle.className = 'course-preview-title';
                    previewTitle.innerHTML = '<i class="fas fa-book"></i> Courses';
                    previewContent.appendChild(previewTitle);

                    const previewList = document.createElement('div');
                    previewList.className = 'course-preview-list';

                    if (programCount === 0) {
                        const emptyItem = document.createElement('div');
                        emptyItem.className = 'course-preview-item empty';
                        emptyItem.innerHTML = '<i class="fas fa-circle-info"></i> No courses yet';
                        previewList.appendChild(emptyItem);
                    } else {
                        department.programs.forEach(program => {
                            const item = document.createElement('div');
                            item.className = 'course-preview-item';
                            item.dataset.programName = (program.program_name || '').toLowerCase();
                            item.dataset.programCode = (program.program_code || '').toLowerCase();

                            const icon = document.createElement('i');
                            icon.className = 'fas fa-graduation-cap';
                            item.appendChild(icon);

                            if (program.program_code) {
                                const codeSpan = document.createElement('span');
                                codeSpan.className = 'course-code';
                                codeSpan.textContent = program.program_code;
                                item.appendChild(codeSpan);
                            }

                            const nameSpan = document.createElement('span');
                            nameSpan.className = 'course-name';
                            nameSpan.textContent = program.program_name || 'Unnamed Program';
                            item.appendChild(nameSpan);

                            previewList.appendChild(item);
                        });
                    }

                    previewContent.appendChild(previewList);
                    coursePreview.appendChild(previewContent);
                    card.appendChild(coursePreview);

                    attachPreviewInteractions(card);

                    return card;
                }

                function createActionButton(className, html, handler) {
                    const button = document.createElement('button');
                    button.className = className;
                    button.innerHTML = html;
                    button.addEventListener('click', function(event) {
                        event.stopPropagation();
                        if (typeof handler === 'function') {
                            handler();
                        }
                    });
                    return button;
                }

                function getDepartmentIconClass(sectorKey) {
                    switch (sectorKey) {
                        case 'college':
                            return 'fas fa-university';
                        case 'senior_high':
                            return 'fas fa-school';
                        case 'faculty':
                            return 'fas fa-chalkboard-teacher';
                        default:
                            return 'fas fa-building';
                    }
                }

                function getSectorLabel(sectorKey) {
                    switch (sectorKey) {
                        case 'college':
                            return 'College';
                        case 'senior_high':
                            return 'Senior High School';
                        case 'faculty':
                            return 'Faculty';
                        default:
                            return 'Department';
                    }
                }

                function attachPreviewInteractions(card) {
                    const preview = card.querySelector('.course-preview');
                    if (!preview) {
                        return;
                    }

                    card.addEventListener('click', function(event) {
                        if (event.target.closest('.department-card-actions')) {
                            return;
                        }

                        const isExpanded = preview.classList.contains('expanded');

                        document.querySelectorAll('.department-card.expanded').forEach(otherCard => {
                            if (otherCard !== card) {
                                otherCard.classList.remove('expanded');
                                const otherPreview = otherCard.querySelector('.course-preview');
                                if (otherPreview) {
                                    otherPreview.classList.remove('expanded');
                                }
                            }
                        });

                        if (!isExpanded) {
                            card.classList.add('expanded');
                            preview.classList.add('expanded');
                        } else {
                            card.classList.remove('expanded');
                            preview.classList.remove('expanded');
                        }
                    });
                }

                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.department-card')) {
                        document.querySelectorAll('.course-preview.expanded').forEach(preview => {
                                preview.classList.remove('expanded');
                            });
                            document.querySelectorAll('.department-card.tapped').forEach(card => {
                                card.classList.remove('tapped');
                            });
                    }
                });

                function deleteCourse(courseCode, courseName) {
                    const targetLabel = courseName ? `${courseCode} — ${courseName}` : courseCode;
                    showConfirmationModal(
                        `Are you sure you want to delete the course "${targetLabel}"?`,
                        'This action cannot be undone.',
                        () => {
                            showToastNotification('Deleting course...', 'info', 1500);
                            setTimeout(() => {
                                showToastNotification(`Course ${targetLabel} deleted successfully`, 'success', 3000);
                            }, 1000);
                        },
                        'Delete Course'
                    );
                }

                function deleteDepartment(departmentId, departmentName) {
                    const label = departmentName ? `${departmentName} (ID: ${departmentId})` : departmentId;
                    showConfirmationModal(
                        `Are you sure you want to delete the department "${label}"?`,
                        'This will also delete all courses under this department. This action cannot be undone.',
                        'Delete Department',
                        'Cancel',
                        () => {
                            showToastNotification('Deleting department...', 'info', 2000);
                            
                            // Call API to delete department
                            fetch('../../api/departments/delete.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'include',
                                body: JSON.stringify({ department_id: departmentId })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    let message = data.message || `Department ${label} deleted successfully`;
                                    if (data.is_cross_sector) {
                                        message += ` (Removed from ${data.deleted_records} sectors)`;
                                    }
                                    showToastNotification(message, 'success', 3000);
                                    
                                    // Refresh the data
                                    setTimeout(() => {
                                        fetchCourseData();
                                    }, 1000);
                                } else {
                                    showToastNotification(
                                        data.message || 'Failed to delete department', 
                                        'error', 
                                        4000
                                    );
                                }
                            })
                            .catch(error => {
                                console.error('Error deleting department:', error);
                                showToastNotification('An error occurred while deleting the department', 'error', 4000);
                            });
                        },
                        'danger'
                    );
                }

                // Initialize sidebar state on page load
                document.addEventListener('DOMContentLoaded', function() {
                    // Mark this page as having initialized sidebar functionality
                    window.sidebarInitialized = true;
                    
                    // Check if user has a saved preference (desktop only)
                    const sidebarState = localStorage.getItem('sidebarCollapsed');
                    const sidebar = document.getElementById('sidebar');
                    const mainContent = document.querySelector('.main-content');
                    
                    // Ensure sidebar starts in correct state on desktop
                    if (window.innerWidth > 768 && sidebar) {
                        // Remove any existing collapsed class first
                        sidebar.classList.remove('collapsed');
                        if (mainContent) {
                            mainContent.classList.remove('full-width');
                        }
                    }
                    
                    // On desktop: start expanded by default, unless user has explicitly collapsed it
                    if (window.innerWidth > 768 && sidebar) {
                        // Force expanded state on page load (user can collapse if they want)
                        sidebar.classList.remove('collapsed');
                        if (mainContent) {
                            mainContent.classList.remove('full-width');
                        }
                        
                        // Only apply saved collapsed state if user has explicitly set it
                        if (sidebarState === 'true') {
                            sidebar.classList.add('collapsed');
                            if (mainContent) {
                                mainContent.classList.add('full-width');
                            }
                        }
                    }
                    
                    // Ensure sidebar backdrop functionality for mobile
                    const backdrop = document.getElementById('sidebar-backdrop');
                    if (backdrop) {
                        backdrop.addEventListener('click', function() {
                            const sidebar = document.querySelector('.sidebar');
                            if (sidebar) {
                                sidebar.classList.remove('active');
                                this.style.display = 'none';
                            }
                        });
                    }
                    
                    // Close sidebar on window resize
                    window.addEventListener('resize', function() {
                        const sidebar = document.querySelector('.sidebar');
                        const backdrop = document.getElementById('sidebar-backdrop');
                        
                        if (window.innerWidth > 768) {
                            if (sidebar) sidebar.classList.remove('active');
                            if (backdrop) backdrop.style.display = 'none';
                        }
                    });
                });

                // Save sidebar state when toggled (desktop only)
                function saveSidebarState() {
                    // Only save state on desktop
                    if (window.innerWidth > 768) {
                        const sidebar = document.getElementById('sidebar');
                        const isCollapsed = sidebar.classList.contains('collapsed');
                        localStorage.setItem('sidebarCollapsed', isCollapsed);
                    }
                }
                
                // Reset sidebar state to default (expanded)
                function resetSidebarState() {
                    if (window.innerWidth > 768) {
                        localStorage.removeItem('sidebarCollapsed');
                        const sidebar = document.getElementById('sidebar');
                        const mainContent = document.querySelector('.main-content');
                        
                        if (sidebar) {
                            sidebar.classList.remove('collapsed');
                        }
                        if (mainContent) {
                            mainContent.classList.remove('full-width');
                        }
                    }
                }
                
                // Debug function to check sidebar state
                function debugSidebarState() {
                    const sidebar = document.getElementById('sidebar');
                    const mainContent = document.querySelector('.main-content');
                    const sidebarState = localStorage.getItem('sidebarCollapsed');
                    
                    console.log('Sidebar Debug Info:');
                    console.log('- Window width:', window.innerWidth);
                    console.log('- Sidebar element:', sidebar);
                    console.log('- Sidebar classes:', sidebar ? sidebar.className : 'N/A');
                    console.log('- Main content classes:', mainContent ? mainContent.className : 'N/A');
                    console.log('- localStorage sidebarCollapsed:', sidebarState);
                    console.log('- Is mobile:', window.innerWidth <= 768);
                }

                // Enhanced toggle function with state saving
                function toggleSidebar() {
                    const sidebar = document.getElementById('sidebar');
                    const backdrop = document.getElementById('sidebar-backdrop');
                    const mainContent = document.querySelector('.main-content');
                    
                    // Check if we're on mobile (screen width <= 768px)
                    const isMobile = window.innerWidth <= 768;
                    
                    if (isMobile) {
                        // Mobile behavior: toggle active class for overlay
                        if (sidebar) {
                            sidebar.classList.toggle('active');
                            
                            // Show/hide backdrop
                            if (backdrop) {
                                if (sidebar.classList.contains('active')) {
                                    backdrop.style.display = 'block';
                                } else {
                                    backdrop.style.display = 'none';
                                }
                            }
                        }
                    } else {
                        // Desktop behavior: toggle collapsed class
                        if (sidebar.classList.contains('collapsed')) {
                            // Expand sidebar
                            sidebar.classList.remove('collapsed');
                            if (backdrop) {
                                backdrop.style.display = 'none';
                            }
                            if (mainContent) {
                                mainContent.classList.remove('full-width');
                            }
                        } else {
                            // Collapse sidebar
                            sidebar.classList.add('collapsed');
                            if (backdrop) {
                                backdrop.style.display = 'none';
                            }
                            if (mainContent) {
                                mainContent.classList.add('full-width');
                            }
                        }
                        
                        // Save the state only for desktop
                        saveSidebarState();
                    }
                }

                // Keyboard shortcut for sidebar toggle (Ctrl/Cmd + B) - desktop only
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'b' && window.innerWidth > 768) {
                        e.preventDefault();
                        toggleSidebar();
                    }
                });
            </script>
        </body>
        </html> 