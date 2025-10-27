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
                            <div id="currentPeriodBanner" class="current-period-banner">
                                <i class="fas fa-calendar-alt banner-icon" aria-hidden="true"></i>
                                <span id="currentPeriodText">Loading current period...</span>
                            </div>
                        </div>

                        <!-- Search and Filters Section -->
                        <div class="search-filters-section">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or program...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Program Filter (SHS only) -->
                                <select id="programFilter" class="filter-select" onchange="updateFilterYearLevels()">
                                    <option value="">All Programs</option>
                                    <option value="Accountancy, Business, and Management (ABM)">Accountancy, Business, and Management (ABM)</option>
                                    <option value="Science, Technology, Engineering and Mathematics (STEM)">Science, Technology, Engineering and Mathematics (STEM)</option>
                                    <option value="Humanities and Social Sciences (HUMSS)">Humanities and Social Sciences (HUMSS)</option>
                                    <option value="General Academic (GA)">General Academic (GA)</option>
                                    <option value="IT in Mobile App and Web Development (MAWD)">IT in Mobile App and Web Development (MAWD)</option>
                                    <option value="Digital Arts (DA)">Digital Arts (DA)</option>
                                </select>
                                
                                <!-- Year Level Filter (Cascading) -->
                                <select id="yearFilter" class="filter-select" disabled>
                                    <option value="">Select Program First</option>
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <option value="unapplied">Unapplied</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="complete">Complete</option>
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select" onchange="updateStatisticsByTerm(); updateCurrentPeriodBanner();">
                                    <option value="">All School Terms</option>
                                    <option value="2024-2025-1st">2024-2025 1st Semester</option>
                                    <option value="2024-2025-2nd">2024-2025 2nd Semester</option>
                                    <option value="2024-2025-summer">2024-2025 Summer</option>
                                    <option value="2023-2024-1st">2023-2024 1st Semester</option>
                                    <option value="2023-2024-2nd">2023-2024 2nd Semester</option>
                                    <option value="2023-2024-summer">2023-2024 Summer</option>
                                </select>
                                
                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
                                    <option value="graduated">Graduated Only</option>
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
    
    <!-- Include SHS Student Registry Modal -->
    <?php include '../../Modals/SHSStudentRegistryModal.php'; ?>
    
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
            try {
                const response = await fetch('../../api/users/students.php?type=senior_high', {
                    credentials: 'include'
                });
                
                // Check if response is ok and content type is JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    populateStudentsTable(data.students);
                    updateStatistics(data.students);
                } else {
                    showToastNotification('Failed to load students data', 'error');
                }
            } catch (error) {
                console.error('Error loading students:', error);
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
            // Map enrollment status to display status
            const displayStatus = student.status === 'Enrolled' ? 'active' : 
                                 student.status === 'Graduated' ? 'graduated' : 
                                 student.status === 'Transferred' ? 'transferred' : 
                                 student.status === 'Dropped' ? 'dropped' : 'inactive';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="checkbox" class="student-checkbox" data-id="${student.user_id}"></td>
                <td>${student.student_id || student.username}</td>
                <td>${student.last_name}, ${student.first_name} ${student.middle_name || ''}</td>
                <td>${student.program || student.strand || 'N/A'}</td>
                <td>${student.year_level || student.grade_level || 'N/A'}</td>
                <td>${student.section || 'N/A'}</td>
                <td><span class="status-badge account-${displayStatus}">${student.status}</span></td>
                <td><span class="status-badge clearance-${student.clearance_status.toLowerCase().replace(' ', '-')}">${student.clearance_status}</span></td>
                <td>
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
        function updateStatistics(students) {
            const stats = {
                total: students.length,
                active: students.filter(s => s.status === 'Enrolled').length,
                inactive: students.filter(s => ['Transferred', 'Dropped'].includes(s.status)).length,
                graduated: students.filter(s => s.status === 'Graduated').length
            };
            
            document.getElementById('totalStudents').textContent = stats.total;
            document.getElementById('activeStudents').textContent = stats.active;
            document.getElementById('inactiveStudents').textContent = stats.inactive;
            document.getElementById('graduatedStudents').textContent = stats.graduated;
        }

        // Load current clearance period
        function loadCurrentPeriod() {
            fetch('../../api/clearance/periods.php', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    const bannerEl = document.getElementById('currentPeriodText');
                    if (data.success && data.active_period) {
                        const p = data.active_period;
                        const termMap = { '1st': 'Term 1', '2nd': 'Term 2', '3rd': 'Term 3' };
                        const semLabel = termMap[p.semester_name] || p.semester_name || '';
                        bannerEl.textContent = `${p.school_year} • ${semLabel}`;
                    } else {
                        bannerEl.textContent = 'No active clearance period';
                    }
                })
                .catch(() => {
                    document.getElementById('currentPeriodText').textContent = 'Unable to load period';
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
            const yearFilter = document.getElementById('yearFilter');
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

        // Filter functions
        function updateFilterYearLevels() {
            const programSelect = document.getElementById('programFilter');
            const yearSelect = document.getElementById('yearFilter');
            
            const selectedProgram = programSelect.value;
            
            yearSelect.innerHTML = '<option value="">All Year Levels</option>';
            
            if (selectedProgram && selectedProgram !== '' && window.shsDepartmentYearLevels && window.shsDepartmentYearLevels['Senior High School']) {
                yearSelect.disabled = false;
                
                window.shsDepartmentYearLevels['Senior High School'].forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearSelect.appendChild(option);
                });
            } else {
                yearSelect.disabled = true;
            }
        }

        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const program = document.getElementById('programFilter').value;
            const yearLevel = document.getElementById('yearFilter').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const studentName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const studentProgram = row.querySelector('td:nth-child(4)').textContent;
                const studentYear = row.querySelector('td:nth-child(5)').textContent;
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-applied, .status-badge.clearance-complete, .status-badge.clearance-in-progress');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                
                let shouldShow = true;
                
                if (searchTerm && !studentName.includes(searchTerm)) {
                    shouldShow = false;
                }
                
                if (program && studentProgram !== program) {
                    shouldShow = false;
                }
                
                if (yearLevel && studentYear !== yearLevel) {
                    shouldShow = false;
                }
                
                if (clearanceStatus && clearanceBadge && !clearanceBadge.classList.contains(`clearance-${clearanceStatus}`)) {
                    shouldShow = false;
                }
                
                if (accountStatus && accountBadge && !accountBadge.classList.contains(`account-${accountStatus}`)) {
                    shouldShow = false;
                }
                
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleCount++;
            });
            
            showToastNotification(`Showing ${visibleCount} of ${tableRows.length} students`, 'info');
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('programFilter').value = '';
            document.getElementById('yearFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            
            updateFilterYearLevels();
            
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
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


        // Pagination functions (simplified)
        function changePage(direction) {
            // Implementation for pagination
        }

        function changeEntriesPerPage() {
            // Implementation for changing entries per page
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentsData();
            loadCurrentPeriod();
            loadPeriods();
            
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
            const currentPeriodText = document.getElementById('currentPeriodText');
            
            if (currentPeriodText) {
                if (schoolTermFilter && schoolTermFilter.value) {
                    const selectedOption = schoolTermFilter.options[schoolTermFilter.selectedIndex];
                    currentPeriodText.textContent = `Current Term: ${selectedOption.text}`;
                } else {
                    currentPeriodText.textContent = 'Select a school term to view current period';
                }
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
