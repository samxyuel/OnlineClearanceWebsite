<?php
// Online Clearance Website - Admin Senior High Student Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session data is handled by authentication system
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior High Student Management - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-graduation-cap"></i> Senior High Student Management</h2>
                            <p>Manage senior high school student accounts and monitor clearance status</p>
                        </div>

                        <!-- Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalStudents">0</h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeStudents">0</h3>
                                    <p>Active Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveStudents">0</h3>
                                    <p>Inactive Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="graduatedStudents">0</h3>
                                    <p>Graduated</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Section -->
                        <div class="quick-actions-section">
                            <div class="action-buttons">
                                <button class="btn btn-primary add-student-btn" onclick="openAddStudentModal()">
                                    <i class="fas fa-plus"></i> Add Student
                                </button>
                                <button class="btn btn-secondary import-btn" onclick="triggerImportModal()">
                                    <i class="fas fa-file-import"></i> Import
                                </button>
                                <button class="btn btn-secondary export-btn" onclick="triggerExportModal()">
                                    <i class="fas fa-file-export"></i> Export
                                </button>
                            </div>
                            <div class="override-actions">
                                <button class="btn btn-warning signatory-override-btn" onclick="openSignatoryOverrideModal()">
                                    <i class="fas fa-user-shield"></i> Signatory Override
                                </button>
                            </div>
                        </div>

                        <!-- Tabs + Current Period Wrapper -->
                        <div class="tab-banner-wrapper">
                            <!-- Tab Navigation for quick status views -->
                            <div class="tab-nav" id="studentTabNav">
                                <button class="tab-pill active" data-status="" onclick="switchStudentTab(this)">Overall</button>
                                <button class="tab-pill" data-status="active" onclick="switchStudentTab(this)">Active</button>
                                <button class="tab-pill" data-status="inactive" onclick="switchStudentTab(this)">Inactive</button>
                                <button class="tab-pill" data-status="graduated" onclick="switchStudentTab(this)">Graduated</button>
                            </div>
                            <!-- Mobile dropdown alternative -->
                            <div class="tab-nav-mobile" id="studentTabSelectWrapper">
                                <select id="studentTabSelect" class="tab-select" onchange="handleTabSelectChange(this)">
                                    <option value="" selected>Overall</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="graduated">Graduated</option>
                                </select>
                            </div>
                            <!-- Current Period Banner -->
                            <span class="academic-year-semester">
                                <i class="fas fa-calendar-check"></i> 
                                <span id="currentAcademicYear">Loading...</span> - <span id="currentSemester">Loading...</span>
                            </span>
                        </div>

                        <!-- Search and Filters Section -->
                        <div class="search-filters-section">
                            <div class="search-box">
                                <i class="fas fa-search" style="pointer-events: none;"></i>
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or program...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Program Filter (SHS only) -->
                                <select id="programFilter" class="filter-select"">
                                    <option value="">All Programs</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- Year Level Filter (Cascading) -->
                                <select id="yearLevelFilter" class="filter-select">
                                    <option value="">All Year levels</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">All School Terms</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            
                            <!-- Apply Filters Button -->
                            <div class="filter-actions">
                                <button class="btn btn-primary apply-filters-btn" onclick="applyFilters()">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button class="btn btn-secondary clear-filters-btn" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Students Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <button class="btn btn-success" onclick="openSeniorHighBatchUpdateModal()">
                                        <i class="fas fa-users-cog"></i> Batch Update
                                    </button>
                                    <button class="selection-counter-display" id="selectionCounterPill" type="button" title="">
                                        <span id="selectionCounter">0 selected</span>
                                    </button>
                                    <div class="bulk-buttons">
                                        <button id="bulkActivateBtn" class="btn btn-success" onclick="activateSelected()" disabled>
                                            <i class="fas fa-user-check"></i> Activate
                                        </button>
                                        <button id="bulkDeactivateBtn" class="btn btn-warning" onclick="deactivateSelected()" disabled>
                                            <i class="fas fa-user-times"></i> Deactivate
                                        </button>
                                        <button class="btn btn-info" onclick="markGraduated()" disabled id="bulkGraduatedBtn">
                                            <i class="fas fa-graduation-cap"></i> Graduated
                                        </button>
                                        <button id="bulkResetBtn" class="btn btn-outline-warning" onclick="resetClearanceForNewTerm()" disabled>
                                            <i class="fas fa-redo"></i> Reset Clearance
                                        </button>
                                        <button id="bulkDeleteBtn" class="btn btn-danger" onclick="deleteSelected()" disabled>
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="table-controls">
                                    <button class="btn btn-outline-primary scroll-to-top-btn" onclick="scrollToTop()" id="scrollToTopBtn" style="display: none;">
                                        <i class="fas fa-arrow-up"></i> Top
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <div class="students-table-wrapper" id="studentsTableWrapper">
                                    <table id="studentsTable" class="students-table">
                                    <thead>
                                        <tr>
                                                <th class="checkbox-column">
                                                    <button class="btn btn-outline-secondary clear-selection-btn" onclick="clearAllSelections()" id="clearSelectionBtn" disabled>
                                                        <i class="fas fa-times"></i> Clear All Selection
                                                    </button>
                                                </th>
                                            <th>Student Number</th>
                                            <th>Name</th>
                                            <th>Program</th>
                                            <th>Year Level</th>
                                            <th>Section</th>
                                            <th>Account Status</th>
                                            <th>Clearance Form Progress</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentsTableBody">
                                        <!-- Data will be populated by JavaScript -->
                                        <tr>
                                            <td><input type="checkbox" class="student-checkbox" data-id="02000288336"></td>
                                            <td>02000288336</td>
                                            <td>Lucas Ramirez</td>
                                            <td>Science, Technology, Engineering and Mathematics (STEM)</td>
                                            <td>Grade 11</td>
                                            <td>11/1-1</td>
                                            <td><span class="status-badge account-active">Active</span></td>
                                            <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('02000288336')" title="View Clearance Progress">
                                                        <i class="fas fa-tasks"></i>
                                                    </button>
                                                    <button class="btn-icon edit-btn" onclick="editStudent('02000288336')" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                    <button class="btn-icon delete-btn" onclick="deleteStudent('02000288336')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><input type="checkbox" class="student-checkbox" data-id="02000288337"></td>
                                            <td>02000288337</td>
                                            <td>Emma Flores</td>
                                            <td>Humanities and Social Sciences (HUMSS)</td>
                                            <td>Grade 12</td>
                                            <td>12/1-2</td>
                                            <td><span class="status-badge account-active">Active</span></td>
                                            <td><span class="status-badge clearance-complete">Complete</span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('02000288337')" title="View Clearance Progress">
                                                        <i class="fas fa-tasks"></i>
                                                    </button>
                                                    <button class="btn-icon edit-btn" onclick="editStudent('02000288337')" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                    <button class="btn-icon delete-btn" onclick="deleteStudent('02000288337')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><input type="checkbox" class="student-checkbox" data-id="02000288338"></td>
                                            <td>02000288338</td>
                                            <td>Daniel Cruz</td>
                                            <td>General Academic (GA)</td>
                                            <td>Grade 11</td>
                                            <td>11/2-3</td>
                                            <td><span class="status-badge account-inactive">Inactive</span></td>
                                            <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('02000288338')" title="View Clearance Progress">
                                                        <i class="fas fa-tasks"></i>
                                                    </button>
                                                    <button class="btn-icon edit-btn" onclick="editStudent('02000288338')" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon status-toggle-btn inactive" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                        <i class="fas fa-toggle-off"></i>
                                                    </button>
                                                    <button class="btn-icon delete-btn" onclick="deleteStudent('02000288338')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-section">
                            <div class="pagination-info">
                                <span id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                            </div>
                            <div class="pagination-controls">
                                <button class="pagination-btn" id="prevPage" onclick="changePage('prev')" disabled>
                                    <i class="fas fa-chevron-left"></i> Previous
                                </button>
                                <div class="page-numbers" id="pageNumbers">
                                    <button class="pagination-btn active">1</button>
                                </div>
                                <button class="pagination-btn" id="nextPage" onclick="changePage('next')" disabled>
                                    Next <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="entries-per-page">
                                <label for="entriesPerPage">Entries per page:</label>
                                <select id="entriesPerPage" class="entries-select" onchange="changeEntriesPerPage()">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20" selected>20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: Activity Tracker -->
                <div class="dashboard-sidebar">
                    <?php include '../../includes/components/activity-tracker.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include SHS Edit Student Modal -->
    <?php include '../../Modals/SHSEditStudentModal.php'; ?>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
    
    <!-- Include Import Modal -->
    <?php include '../../Modals/ImportModal.php'; ?>
    
    <!-- Include Clearance Progress Modal -->
    <?php include '../../Modals/ClearanceProgressModal.php'; ?>
    
    <!-- Include Senior High School Batch Update Modal -->
    <?php include '../../Modals/SeniorHighSchoolBatchUpdateModal.php'; ?>

    <!-- Include SHS Student Registry Modal -->
    <?php include '../../Modals/SHSStudentRegistryModal.php'; ?>

    <!-- Include Generated Credentials Modal -->
    <?php include '../../Modals/GeneratedCredentialsModal.php'; ?>

    <!-- Bulk Selection Filters Modal -->
    <div id="bulkSelectionModal" class="modal-overlay" style="display: none;">
        <div class="modal-window bulk-selection-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-filter"></i> Bulk Selection Filters</h3>
                <button class="modal-close" onclick="closeBulkSelectionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content-area">
                <div class="filter-sections">
                    <!-- Account Status Section -->
                    <div class="form-group">
                        <label class="filter-section-label">Account Status:</label>
                        <div class="checkbox-group">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterActive" value="active">
                                <span class="checkmark"></span>
                                with "active"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterInactive" value="inactive">
                                <span class="checkmark"></span>
                                with "inactive"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterGraduated" value="graduated">
                                <span class="checkmark"></span>
                                with "graduated"
                            </label>
                        </div>
                    </div>
                    
                    <!-- Clearance Progress Section -->
                    <div class="form-group">
                        <label class="filter-section-label">Clearance Progress:</label>
                        <div class="checkbox-group">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterUnapplied" value="unapplied">
                                <span class="checkmark"></span>
                                with "unapplied"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterInProgress" value="in-progress">
                                <span class="checkmark"></span>
                                with "in progress"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterComplete" value="complete">
                                <span class="checkmark"></span>
                                with "Complete"
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-action-secondary" onclick="closeBulkSelectionModal()">Cancel</button>
                <button class="modal-action-primary" onclick="applyBulkSelection()">
                    <i class="fas fa-check"></i> Select All
                </button>
            </div>
        </div>
    </div>


    <script>
        // Senior High School specific configuration
        const studentType = 'Senior High School';
        const strandYearLevels = {
            'Accountancy, Business, and Management (ABM)': ['Grade 11', 'Grade 12'],
            'Science, Technology, Engineering and Mathematics (STEM)': ['Grade 11', 'Grade 12'],
            'Humanities and Social Sciences (HUMSS)': ['Grade 11', 'Grade 12'],
            'General Academic (GA)': ['Grade 11', 'Grade 12'],
            'IT in Mobile App and Web Development (MAWD)': ['Grade 11', 'Grade 12'],
            'Digital Arts (DA)': ['Grade 11', 'Grade 12']
        };

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    if (backdrop) backdrop.style.display = 'none';
                } else {
                    sidebar.classList.add('active');
                    if (backdrop) backdrop.style.display = 'block';
                }
            } else {
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                } else {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }
        }

        // Load SHS students data from API
        async function loadStudentsData() {
            const tableBody = document.getElementById('studentsTableBody');
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading students...</td></tr>`;

            // Get filter values
            const search = document.getElementById('searchInput').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const programId = document.getElementById('programFilter').value;
            const yearLevel = document.getElementById('yearLevelFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;

            // Use the general-purpose user list API for admin views.
            const url = new URL('../../api/users/studentList.php', window.location.href);
            url.searchParams.append('type', 'student'); 
            url.searchParams.append('sector', 'Senior High School'); 
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (programId) url.searchParams.append('program_id', programId);
            if (yearLevel) url.searchParams.append('year_level', yearLevel);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);

            try {
                const response = await fetch(url.toString(), {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Senior High students API response:', data); // For debugging
                
                if (data.success) {
                    populateStudentsTable(data.students);
                    updateStatisticsUI(data.stats);
                    updatePaginationUI(data.total, data.page, data.limit);
                } else {
                    showToastNotification('Failed to load students data: ' + data.message, 'error');
                    tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading SHS students:', error);
                showToastNotification('Error loading students data: ' + error.message, 'error');
            }
        }

        // Populate students table
        function populateStudentsTable(students) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';
            
            students.forEach(student => {
                const row = createStudentRow(student);
                tbody.appendChild(row);
            });
        }

        // Create student row
        function createStudentRow(student) {
            const accountStatusClass = `account-${student.account_status || 'inactive'}`;
            const accountStatusText = student.account_status ? student.account_status.charAt(0).toUpperCase() + student.account_status.slice(1) : 'Inactive';

            let clearanceStatus = student.clearance_status || 'Unapplied';
            const clearanceStatusClass = `clearance-${clearanceStatus.toLowerCase().replace(/ /g, '-')}`;
            
            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.setAttribute('data-student-id', student.id);
            row.setAttribute('data-form-id', student.clearance_form_id);

            row.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="student-checkbox" data-id="${student.id}"></td>
                <td data-label="Student Number:">${student.id}</td>
                <td data-label="Name:">${student.name}</td>
                <td data-label="Program:">${student.program || 'N/A'}</td>
                <td data-label="Year Level:">${student.year_level || 'N/A'}</td>
                <td data-label="Section:">${student.section || 'N/A'}</td>
                <td data-label="Account Status:"><span class="status-badge ${accountStatusClass}">${accountStatusText}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge ${clearanceStatusClass}">${clearanceStatus}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('${student.user_id}')" title="View Clearance Progress">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button class="btn-icon edit-btn" onclick="editStudent('${student.user_id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete-btn" onclick="deleteStudent('${student.user_id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            return row;
        }

        // Update statistics
        function updateStatisticsUI(stats) {
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
            document.getElementById('graduatedStudents').textContent = stats.graduated || 0;
        }

        // Load current clearance period
        function loadCurrentPeriod() {
            fetch('../../api/clearance/periods.php', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    const yearEl = document.getElementById('currentAcademicYear');
                    const semesterEl = document.getElementById('currentSemester');
                    if (data.success && data.active_periods && data.active_periods.length > 0) {
                        const p = data.active_periods[0];
                        // Map semester name to full format
                        const termMap = { 
                            '1st': '1st Semester', 
                            '2nd': '2nd Semester', 
                            '3rd': '3rd Semester',
                            '1st Semester': '1st Semester',
                            '2nd Semester': '2nd Semester',
                            '3rd Semester': '3rd Semester'
                        };
                        const semLabel = termMap[p.semester_name] || p.semester_name || '';
                        if (yearEl) yearEl.textContent = p.school_year;
                        if (semesterEl) semesterEl.textContent = semLabel;
                    } else {
                        if (yearEl) yearEl.textContent = 'No active period';
                        if (semesterEl) semesterEl.textContent = 'No term';
                    }
                })
                .catch(() => {
                    const yearEl = document.getElementById('currentAcademicYear');
                    const semesterEl = document.getElementById('currentSemester');
                    if (yearEl) yearEl.textContent = 'Unable to load';
                    if (semesterEl) semesterEl.textContent = 'Error';
                });
        }

        // Load periods for selector
        function loadPeriods() {
            // Period information is now handled by School Term Filter
            updateCurrentPeriodBanner();
        }

        // Change period
        // Tab Navigation Functions
        function switchStudentTab(btn){
            const newTabStatus = btn.getAttribute('data-status');
            const currentTabStatus = window.currentTabStatus || '';
            
            // If switching to the same tab, do nothing
            if (newTabStatus === currentTabStatus) {
                return;
            }
            
            // Check if there are any active selections or filters
            const hasSelections = getSelectedCount() > 0;
            const hasFilters = hasActiveFilters();
            
            if (hasSelections || hasFilters) {
                // Show confirmation dialog
                showConfirmationModal(
                    'Switch Tab',
                    'Switching tabs will clear your current selection and bulk selection filters. Continue?',
                    'Continue',
                    'Cancel',
                    () => {
                        // User confirmed - proceed with tab switch
                        performTabSwitch(btn, newTabStatus);
                    },
                    'warning'
                );
            } else {
                // No selections or filters - switch immediately
                performTabSwitch(btn, newTabStatus);
            }
        }
        
        function performTabSwitch(btn, newTabStatus) {
            // Update tab UI
            document.querySelectorAll('#studentTabNav .tab-pill').forEach(p=>p.classList.remove('active'));
            btn.classList.add('active');
            window.currentTabStatus = newTabStatus;
            
            // Clear all selections and filters
            clearAllSelectionsAndFilters();
            
            // Apply filters for new tab context
            applyTabFilter();
            
            // Show confirmation message
            showToastNotification('Selection and filters cleared for new tab view', 'info');
        }

        // track currently selected account-status cohort from tab nav
        window.currentTabStatus = '';

        // dropdown handler for mobile tab select
        function handleTabSelectChange(sel){
            const newTabStatus = sel.value;
            const currentTabStatus = window.currentTabStatus || '';
            
            // If switching to the same tab, do nothing
            if (newTabStatus === currentTabStatus) {
                return;
            }
            
            // Check if there are any active selections or filters
            const hasSelections = getSelectedCount() > 0;
            const hasFilters = hasActiveFilters();
            
            if (hasSelections || hasFilters) {
                // Show confirmation dialog
                showConfirmationModal(
                    'Switch Tab',
                    'Switching tabs will clear your current selection and bulk selection filters. Continue?',
                    'Continue',
                    'Cancel',
                    () => {
                        // User confirmed - proceed with tab switch
                        performMobileTabSwitch(sel, newTabStatus);
                    },
                    'warning'
                );
            } else {
                // No selections or filters - switch immediately
                performMobileTabSwitch(sel, newTabStatus);
            }
        }
        
        function performMobileTabSwitch(sel, newTabStatus) {
            // Update tab state
            window.currentTabStatus = newTabStatus;
            
            // Sync pill active state for when user switches back to desktop
            document.querySelectorAll('#studentTabNav .tab-pill').forEach(btn=>{
                btn.classList.toggle('active', btn.getAttribute('data-status')===newTabStatus);
            });
            
            // Clear all selections and filters
            clearAllSelectionsAndFilters();
            
            // Apply filters for new tab context
            applyTabFilter();
            
            // Show confirmation message
            showToastNotification('Selection and filters cleared for new tab view', 'info');
        }

        // Apply tab-based filter
        function applyTabFilter() {
            const currentStatus = window.currentTabStatus || '';
            
            // Update account status filter based on current tab
            const accountStatusFilter = document.getElementById('accountStatusFilter');
            if (accountStatusFilter) {
                if (currentStatus === '') {
                    accountStatusFilter.value = ''; // Show all
                } else {
                    accountStatusFilter.value = currentStatus;
                }
            }
            
            // Apply filters to show the filtered results
            applyFilters();
        }

        // Helper function to check if there are any active filters
        function hasActiveFilters() {
            const searchInput = document.getElementById('searchInput');
            const programFilter = document.getElementById('programFilter');
            const yearFilter = document.getElementById('yearLevelFilter');
            const clearanceFilter = document.getElementById('clearanceStatusFilter');
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            const accountStatusFilter = document.getElementById('accountStatusFilter');
            
            return (searchInput && searchInput.value.trim() !== '') ||
                   (programFilter && programFilter.value !== '') ||
                   (yearFilter && yearFilter.value !== '') ||
                   (clearanceFilter && clearanceFilter.value !== '') ||
                   (schoolTermFilter && schoolTermFilter.value !== '') ||
                   (accountStatusFilter && accountStatusFilter.value !== '');
        }

        // Bulk selection functions
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateBulkButtons();
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
            });
            
            updateSelectionCounter();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.student-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            const selectionDisplay = document.getElementById('selectionCounterPill');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
                // Disable clear selection button when no selections
                if (clearSelectionBtn) clearSelectionBtn.disabled = true;
                // Reset selection counter styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections', 'all-selected');
                    selectionDisplay.setAttribute('aria-disabled','true');
                    selectionDisplay.title = '';
                }
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                // Enable clear selection button when there are selections
                if (clearSelectionBtn) clearSelectionBtn.disabled = false;
                // Apply all selected styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections');
                    selectionDisplay.classList.add('all-selected');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
            } else {
                counter.textContent = `${selectedCount} selected`;
                // Enable clear selection button when there are selections
                if (clearSelectionBtn) clearSelectionBtn.disabled = false;
                // Apply partial selection styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('all-selected');
                    selectionDisplay.classList.add('has-selections');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
            }
        }

        function getSelectedCount() {
            return document.querySelectorAll('.student-checkbox:checked').length;
        }

        // Bulk actions

        function deleteSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to delete', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Delete Students',
                `Are you sure you want to delete ${selectedCount} selected students? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        row.remove();
                    });
                    
                    showToastNotification(`✓ Successfully deleted ${selectedCount} students`, 'success');
                },
                'danger'
            );
        }

        function markGraduated() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to mark as graduated', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Mark Students as Graduated',
                `Are you sure you want to mark ${selectedCount} selected students as Graduated?`,
                'Mark as Graduated',
                'Cancel',
                () => {
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Graduated';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-graduated');
                        }
                    });
                    
                    showToastNotification(`✓ Successfully marked ${selectedCount} students as Graduated`, 'success');
                },
                'info'
            );
        }

        function resetClearanceForNewTerm() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to reset clearance status', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Reset Clearance Status',
                `Are you sure you want to reset clearance status to "Unapplied" for ${selectedCount} selected students?`,
                'Reset Clearance',
                'Cancel',
                () => {
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-applied, .status-badge.clearance-complete, .status-badge.clearance-in-progress');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Unapplied';
                            clearanceBadge.classList.remove('clearance-applied', 'clearance-complete', 'clearance-in-progress');
                            clearanceBadge.classList.add('clearance-unapplied');
                        }
                    });
                    
                    showToastNotification(`✓ Successfully reset clearance status for ${selectedCount} students`, 'success');
                },
                'warning'
            );
        }

        // Individual student actions
        function editStudent(studentId) {
            openEditStudentModal(studentId);
        }


        function deleteStudent(studentId) {
            showConfirmationModal(
                'Delete Student',
                'Are you sure you want to delete this student? This action cannot be undone.',
                'Delete Permanently',
                'Cancel',
                () => {
                    const row = document.querySelector(`.student-checkbox[data-id="${studentId}"]`).closest('tr');
                    row.remove();
                    showToastNotification('Student has been deleted', 'success');
                },
                'danger'
            );
        }

        function viewClearanceProgress(studentId) {
            openClearanceProgressModal(studentId, 'student', 'Student Name');
        }

        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            currentPage = 1;
            loadStudentsData();
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('programFilter').value = '';
            document.getElementById('yearLevelFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });

            loadStudentsData();
            showToastNotification('All filters cleared', 'info');
        }

        // Modal functions
        function openAddStudentModal() {
            openStudentRegistrationModal();
        }

        function triggerImportModal() {
            if (typeof window.openImportModal === 'function') {
                window.openImportModal();
            } else {
                console.error('Import modal function not found');
            }
        }

        function triggerExportModal() {
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else {
                console.error('Export modal function not found');
            }
        }


        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        let filteredEntries = [];

        // Initialize pagination
        function updatePaginationUI(total, page, limit) {
            totalEntries = total;
            currentPage = page;
            entriesPerPage = limit;
            updatePagination(); // This will now use the global variables
        }

        // Update pagination display
        function updatePagination() {
            const totalPages = Math.ceil(totalEntries / entriesPerPage);
            const startEntry = totalEntries === 0 ? 0 : (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, totalEntries);
            
            // Update pagination info
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${totalEntries} entries`;
            
            // Update page numbers
            updatePageNumbers(totalPages);
            
            // Update navigation buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
        }

        // Update page number buttons
        function updatePageNumbers(totalPages) {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';
            
            if (totalPages <= 7) {
                // Show all page numbers
                for (let i = 1; i <= totalPages; i++) {
                    addPageButton(i, i === currentPage);
                }
            } else {
                // Show smart pagination with ellipsis
                if (currentPage <= 4) {
                    // Show first 5 pages + ellipsis + last page
                    for (let i = 1; i <= 5; i++) {
                        addPageButton(i, i === currentPage);
                    }
                    addEllipsis();
                    addPageButton(totalPages, false);
                } else if (currentPage >= totalPages - 3) {
                    // Show first page + ellipsis + last 5 pages
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        addPageButton(i, i === currentPage);
                    }
                } else {
                    // Show first page + ellipsis + current-1, current, current+1 + ellipsis + last page
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                        addPageButton(i, i === currentPage);
                    }
                    addEllipsis();
                    addPageButton(totalPages, false);
                }
            }
        }

        // Add page button
        function addPageButton(pageNum, isActive) {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            const button = document.createElement('button');
            button.className = `pagination-btn ${isActive ? 'active' : ''}`;
            button.textContent = pageNum;
            button.onclick = () => goToPage(pageNum);
            pageNumbersContainer.appendChild(button);
        }

        // Add ellipsis
        function addEllipsis() {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            const span = document.createElement('span');
            span.className = 'pagination-dots';
            span.textContent = '...';
            span.style.padding = '8px 12px';
            span.style.color = 'var(--medium-muted-blue)';
            pageNumbersContainer.appendChild(span);
        }

        // Go to specific page
        function goToPage(pageNum) {
            currentPage = pageNum;
            loadStudentsData();
        }

        // Change page (previous/next)
        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                loadStudentsData(currentPage - 1);
            } else if (direction === 'next') {
                const totalPages = Math.ceil(totalEntries / entriesPerPage);
                if (currentPage < totalPages) {
                    loadStudentsData(currentPage + 1);
                }
            }
        }

        // Change entries per page
        function changeEntriesPerPage() {
            loadStudentsData(1); // Go back to page 1
        }

        // Update filtered entries for pagination
        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#studentsTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1; // Reset to first page
            updatePagination();
        }

        function scrollToTop() {
            const tableWrapper = document.getElementById('studentsTableWrapper');
            if (tableWrapper) {
                tableWrapper.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }

        // --- Dynamic Filter Population ---
        async function populateFilter(selectId, url, placeholder, valueField = 'value', textField = 'text') {
            const select = document.getElementById(selectId);
            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                select.innerHTML = `<option value="">${placeholder}</option>`;
                if (data.success && data.options) {
                    data.options.forEach(option => {
                        const optionElement = document.createElement('option');
                        optionElement.value = typeof option === 'object' ? option[valueField] : option;
                        optionElement.textContent = typeof option === 'object' ? option[textField] : option;
                        select.appendChild(optionElement);
                    });
                }
            } catch (error) {
                console.error(`Error loading options for ${selectId}:`, error);
                select.innerHTML = `<option value="">Error loading options</option>`;
            }
        }

        async function loadYearLevels() {
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'students');
            url.searchParams.append('column', 'year_level');
            url.searchParams.append('sector', 'Senior High School');
            await populateFilter('yearLevelFilter', url, 'All Year Levels');
        }

        async function loadPrograms() {
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'programs');
            url.searchParams.append('sector', 'Senior High School');
            await populateFilter('programFilter', url, 'All Programs');
        }

        async function loadSchoolTerms() {
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'school_terms');
            await populateFilter('schoolTermFilter', url, 'All School Terms');
        }

        async function loadClearanceStatuses() {
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'clearance_forms');
            url.searchParams.append('column', 'clearance_form_progress');
            await populateFilter('clearanceStatusFilter', url, 'All Clearance Statuses');
        }

        async function loadAccountStatuses() {
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'users');
            url.searchParams.append('column', 'account_status');
            url.searchParams.append('exclude', 'resigned');
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentsData();
            loadCurrentPeriod();
            loadYearLevels();
            loadPeriods();
            loadPrograms();
            loadSchoolTerms();
            loadClearanceStatuses();
            loadAccountStatuses();  
            
            // Add event listeners for checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('student-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
            
            // Make selection counter pill act as Clear Selection when active
            const pill = document.getElementById('selectionCounterPill');
            if (pill) {
                pill.addEventListener('click', function() {
                    if (!pill.classList.contains('has-selections') && !pill.classList.contains('all-selected')) return;
                    clearAllSelections();
                });
                pill.addEventListener('keydown', function(e){
                    if ((e.key === 'Enter' || e.key === ' ') && (pill.classList.contains('has-selections') || pill.classList.contains('all-selected'))){
                        e.preventDefault();
                        clearAllSelections();
                    }
                });
            }
            
            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for Senior High Student Management');
            }
            
            // Initialize current period banner
            updateCurrentPeriodBanner();
        });

        // Bulk Selection Modal Functions
        function openBulkSelectionModal() {
            const modal = document.getElementById('bulkSelectionModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBulkSelectionModal() {
            const modal = document.getElementById('bulkSelectionModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Reset all checkboxes
            const checkboxes = modal.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);
        }

        function resetBulkSelectionFilters() {
            // Reset all filter checkboxes
            const filterActive = document.getElementById('filterActive');
            const filterInactive = document.getElementById('filterInactive');
            const filterUnapplied = document.getElementById('filterUnapplied');
            const filterInProgress = document.getElementById('filterInProgress');
            const filterComplete = document.getElementById('filterComplete');
            
            if (filterActive) filterActive.checked = false;
            if (filterInactive) filterInactive.checked = false;
            if (filterUnapplied) filterUnapplied.checked = false;
            if (filterInProgress) filterInProgress.checked = false;
            if (filterComplete) filterComplete.checked = false;
        }

        function applyBulkSelection() {
            const selectedFilters = {
                accountStatus: [],
                clearanceProgress: []
            };
            
            // Collect account status filters
            if (document.getElementById('filterActive').checked) selectedFilters.accountStatus.push('active');
            if (document.getElementById('filterInactive').checked) selectedFilters.accountStatus.push('inactive');
            if (document.getElementById('filterGraduated').checked) selectedFilters.accountStatus.push('graduated');
            
            // Collect clearance progress filters  
            if (document.getElementById('filterUnapplied').checked) selectedFilters.clearanceProgress.push('unapplied');
            if (document.getElementById('filterInProgress').checked) selectedFilters.clearanceProgress.push('in-progress');
            if (document.getElementById('filterComplete').checked) selectedFilters.clearanceProgress.push('complete');
            
            // Apply bulk selection based on filters
            selectStudentsByFilters(selectedFilters);
            
            // Close modal
            closeBulkSelectionModal();
        }

        function selectStudentsByFilters(filters) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const accountBadge = row.querySelector('.status-badge[class*="account-"]');
                const clearanceBadge = row.querySelector('.status-badge[class*="clearance-"]');
                
                let shouldSelect = false;
                
                // Check account status filter
                if (filters.accountStatus.length > 0 && accountBadge) {
                    const accountStatus = accountBadge.textContent.toLowerCase();
                    if (filters.accountStatus.includes(accountStatus)) {
                        shouldSelect = true;
                    }
                }
                
                // Check clearance progress filter
                if (filters.clearanceProgress.length > 0 && clearanceBadge) {
                    const clearanceStatus = clearanceBadge.textContent.toLowerCase().replace(' ', '-');
                    if (filters.clearanceProgress.includes(clearanceStatus)) {
                        shouldSelect = true;
                    }
                }
                
                // Select if matches any filter or if no filters selected (select all)
                if (shouldSelect || (filters.accountStatus.length === 0 && filters.clearanceProgress.length === 0)) {
                    checkbox.checked = true;
                    selectedCount++;
                }
            });
            
            updateBulkButtons();
            updateSelectionCounter();
            
            showToastNotification(`Selected ${selectedCount} students matching filters`, 'success');
        }

        function clearAllSelectionsAndFilters() {
            // Clear all student checkboxes
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Clear search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.value = '';
            
            // Clear filter dropdowns
            const programFilter = document.getElementById('programFilter');
            const yearFilter = document.getElementById('yearFilter');
            const clearanceFilter = document.getElementById('clearanceStatusFilter');
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            const accountStatusFilter = document.getElementById('accountStatusFilter');
            
            if (programFilter) programFilter.value = '';
            if (yearFilter) yearFilter.value = '';
            if (clearanceFilter) clearanceFilter.value = '';
            if (schoolTermFilter) schoolTermFilter.value = '';
            if (accountStatusFilter) accountStatusFilter.value = '';
            
            // Reset bulk selection modal filters (if modal is open)
            resetBulkSelectionFilters();
            
            // Update UI states
            updateSelectionCounter();
            updateBulkButtons();
            
            // Show all rows (remove any filter-based hiding)
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            // Disable clear selection button (since no selections)
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            if (clearSelectionBtn) clearSelectionBtn.disabled = true;
        }

        function clearAllSelections() {
            const selectedCount = getSelectedCount();
            
            if (selectedCount === 0) {
                showToastNotification('No selections to clear', 'info');
                return;
            }
            
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkButtons();
            updateSelectionCounter();
            showToastNotification('All selections cleared', 'info');
        }


        // Update current period banner
        function updateCurrentPeriodBanner() {
            // Update period banner based on selected school term
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            const yearEl = document.getElementById('currentAcademicYear');
            const semesterEl = document.getElementById('currentSemester');
            
            if (schoolTermFilter && schoolTermFilter.value && yearEl && semesterEl) {
                const selectedOption = schoolTermFilter.options[schoolTermFilter.selectedIndex];
                const text = selectedOption.text;
                // Extract year and term from text like "2027-2028 - 1st Semester"
                if (text.includes(' - ')) {
                    const [year, term] = text.split(' - ');
                    yearEl.textContent = year || '';
                    semesterEl.textContent = term || '';
                } else {
                    yearEl.textContent = text;
                    semesterEl.textContent = '';
                }
            } else if (yearEl && semesterEl) {
                yearEl.textContent = 'Select a term';
                semesterEl.textContent = 'N/A';
            }
        }
    </script>
    
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Include Activity Tracker JavaScript -->
    <script src="../../assets/js/activity-tracker.js"></script>
    
    <!-- Include Clearance Button Manager -->
    <script src="../../assets/js/clearance-button-manager.js"></script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
</body>
</html>
