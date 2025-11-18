<?php // Online Clearance Website - Admin Faculty Management
// Session management handled by header component

// Start output buffering to prevent headers being sent before session_start()
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/components/header.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-container">
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-layout">
                <!-- LEFT SIDE: Main Content -->
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-chalkboard-teacher"></i> Faculty Management</h2>
                            <p>Manage faculty accounts and monitor employment status</p>
                        </div>

                        <!-- Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalFaculty">0</h3>
                                    <p>Total Faculty</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeFaculty">0</h3>
                                    <p>Active</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveFaculty">0</h3>
                                    <p>Inactive</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="resignedFaculty">0</h3>
                                    <p>Resigned</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Section -->
                        <div class="quick-actions-section">
                            <div class="action-buttons">
                                <button class="btn btn-primary add-faculty-btn" onclick="openAddFacultyModal()">
                                    <i class="fas fa-plus"></i> Add Faculty
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

                        <!-- Current Period Wrapper -->
                        <div class="tab-banner-wrapper">
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
                                <input type="text" id="searchInput" placeholder="Search faculty by name, ID, or department...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Employment Status Filter -->
                                <select id="employmentStatusFilter" class="filter-select">
                                    <option value="">All Employment Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>

                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
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

                        <!-- Faculty Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <button class="btn btn-success" onclick="openFacultyBatchUpdateModal()">
                                        <i class="fas fa-users-cog"></i> Batch Update
                                    </button>
                                    <button class="selection-counter-display" id="selectionCounterPill" type="button" title="">
                                        <span id="selectionCounter">0 selected</span>
                                    </button>
                                    <div class="bulk-buttons">
                                        <!-- Undo button removed as per workflow update -->
                                        <button id="bulkActivateBtn" class="btn btn-success" onclick="activateSelected()" disabled>
                                            <i class="fas fa-user-check"></i> Activate
                                        </button>
                                        <button id="bulkDeactivateBtn" class="btn btn-warning" onclick="deactivateSelected()" disabled>
                                            <i class="fas fa-user-times"></i> Deactivate
                                        </button>
                                        <button class="btn btn-info" onclick="markResigned()" disabled id="bulkResignedBtn">
                                            <i class="fas fa-user-slash"></i> Resigned
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
                                <div class="students-table-wrapper" id="facultyTableWrapper">
                                    <table id="facultyTable" class="students-table">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-column">
                                                    <button class="btn btn-outline-secondary clear-selection-btn" onclick="clearAllSelections()" id="clearSelectionBtn" disabled>
                                                        <i class="fas fa-times"></i> Clear All Selection
                                                    </button>
                                                </th>
                                                <th>Employee Number</th>
                                                <th>Name</th>
                                                <th>Employment Status</th>
                                                <th>Account Status</th>
                                                <th>Clearance Form Progress</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="facultyTableBody">
                                            <!-- Sample data - will be populated by JavaScript -->
                                            <tr data-term="2024-2025-1st">
                                                <td><input type="checkbox" class="faculty-checkbox" data-id="LCA123P"></td>
                                                <td>LCA123P</td>
                                                <td>Dr. Maria Santos</td>
                                                <td><span class="status-badge employment-full-time">Full Time</span></td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('LCA123P')" title="View Clearance Progress">
                                                            <i class="fas fa-tasks"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" onclick="editFaculty('LCA123P')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon delete-btn" onclick="deleteFaculty('LCA123P')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-1st">
                                                <td><input type="checkbox" class="faculty-checkbox" data-id="MTH456A"></td>
                                                <td>MTH456A</td>
                                                <td>Prof. Juan Dela Cruz</td>
                                                <td><span class="status-badge employment-part-time">Part Time</span></td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-complete">Complete</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('MTH456A')" title="View Clearance Progress">
                                                            <i class="fas fa-tasks"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" onclick="editFaculty('MTH456A')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon delete-btn" onclick="deleteFaculty('MTH456A')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-2nd">
                                                <td><input type="checkbox" class="faculty-checkbox" data-id="ENG789B"></td>
                                                <td>ENG789B</td>
                                                <td>Dr. Ana Rodriguez</td>
                                                <td><span class="status-badge employment-part-time-full-load">Part Time - Full Load</span></td>
                                                <td><span class="status-badge account-inactive">Inactive</span></td>
                                                <td><span class="status-badge clearance-unapplied">Unapplied</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('ENG789B')" title="View Clearance Progress">
                                                            <i class="fas fa-tasks"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" onclick="editFaculty('ENG789B')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon delete-btn" onclick="deleteFaculty('ENG789B')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2023-2024-2nd">
                                                <td><input type="checkbox" class="faculty-checkbox" data-id="CSC321D"></td>
                                                <td>CSC321D</td>
                                                <td>Prof. Carlos Mendoza</td>
                                                <td><span class="status-badge employment-full-time">Full Time</span></td>
                                                <td><span class="status-badge account-resigned">Resigned</span></td>
                                                <td><span class="status-badge clearance-complete">Complete</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('CSC321D')" title="View Clearance Progress">
                                                            <i class="fas fa-tasks"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" onclick="editFaculty('CSC321D')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon delete-btn" onclick="deleteFaculty('CSC321D')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-1st">
                                                <td><input type="checkbox" class="faculty-checkbox" data-id="BIO654E"></td>
                                                <td>BIO654E</td>
                                                <td>Prof. Sarah Johnson</td>
                                                <td><span class="status-badge employment-part-time">Part Time</span></td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('BIO654E')" title="View Clearance Progress">
                                                            <i class="fas fa-tasks"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" onclick="editFaculty('BIO654E')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon delete-btn" onclick="deleteFaculty('BIO654E')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-2nd">
                                                <td><input type="checkbox" class="faculty-checkbox" data-id="PHY987F"></td>
                                                <td>PHY987F</td>
                                                <td>Dr. Michael Chen</td>
                                                <td><span class="status-badge employment-part-time-full-load">Part Time - Full Load</span></td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-complete">Complete</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('PHY987F')" title="View Clearance Progress">
                                                            <i class="fas fa-tasks"></i>
                                                        </button>
                                                        <button class="btn-icon edit-btn" onclick="editFaculty('PHY987F')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon delete-btn" onclick="deleteFaculty('PHY987F')" title="Delete">
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

                        <!-- Pagination Section -->
                        <div class="pagination-section">
                            <div class="pagination-info">
                                <span id="paginationInfo">Showing 1 to 4 of 4 entries</span>
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
                                <label for="entriesPerPage">Show:</label>
                                <select id="entriesPerPage" onchange="changeEntriesPerPage()">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20" selected>20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span>entries</span>
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
    
    <!-- Include Modals (moved after session start) -->
    <?php include '../../Modals/FacultyRegistryModal.php'; ?>
    <?php include '../../Modals/EditFacultyModal.php'; ?>
    <?php include '../../Modals/FacultyExportModal.php'; ?>
    <?php include '../../Modals/ExportModal.php'; ?>
    <?php include '../../Modals/ImportModal.php'; ?>
    <?php include '../../Modals/ClearanceProgressModal.php'; ?>
    
    <!-- Include Faculty Batch Update Modal -->
    <?php include '../../Modals/FacultyBatchUpdateModal.php'; ?>

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
                    <!-- Employment Status Section -->
                    <div class="form-group">
                        <label class="filter-section-label">Employment Status:</label>
                        <div class="checkbox-group">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterFullTime" value="full-time">
                                <span class="checkmark"></span>
                                with "Full Time"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterPartTime" value="part-time">
                                <span class="checkmark"></span>
                                with "Part Time"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterPartTimeFullLoad" value="part-time-full-load">
                                <span class="checkmark"></span>
                                with "Part Time - Full Load"
                            </label>
                        </div>
                    </div>
                    
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
                                <input type="checkbox" id="filterResigned" value="resigned">
                                <span class="checkmark"></span>
                                with "resigned"
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


    <!-- Signatory Override Modal -->
    <div id="signatoryOverrideModal" class="modal-overlay" style="display: none;">
        <div class="modal-window override-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-shield"></i> Signatory Override</h3>
                <button class="modal-close" onclick="closeSignatoryOverrideModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content-area">
                <div class="position-selection-section">
                    <h4>Select Staff Position to Override</h4>
                    <div class="position-selector">
                        <select id="staffPositionSelect" class="form-control">
                            <option value="">Choose position...</option>
                            <option value="mis_it">MIS/IT</option>
                            <option value="cashier">Cashier</option>
                            <option value="registrar">Registrar</option>
                            <option value="library">Library</option>
                            <option value="accounting">Accounting</option>
                            <option value="student_affairs">Student Affairs</option>
                        </select>
                        <div class="custom-position-input">
                            <input type="text" id="customPositionInput" class="form-control" placeholder="Or type custom position...">
                        </div>
                    </div>
                    
                    <div class="position-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="warningText">Staff account will be temporarily disabled during override session</span>
                    </div>
                    
                    <div class="pending-clearances-preview">
                        <h5><i class="fas fa-list"></i> Pending Faculty Clearances for Selected Position</h5>
                        <div id="pendingClearancesList" class="clearances-preview-list">
                            <!-- Dynamic content will be populated -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-action-secondary" onclick="closeSignatoryOverrideModal()">Cancel</button>
                <button class="modal-action-primary" onclick="proceedWithOverride()">Proceed with Override</button>
            </div>
        </div>
    </div>

    <!-- Override Session Interface -->
    <div id="overrideSessionInterface" class="override-session" style="display: none;">
        <div class="override-header">
            <div class="override-status">
                <i class="fas fa-user-shield"></i>
                <span id="overridePositionDisplay">Signing as: [Position]</span>
                <span class="session-timer" id="sessionTimer">Session: 00:00:00</span>
            </div>
            <button class="btn btn-danger" onclick="endOverrideSession()">
                <i class="fas fa-times"></i> End Session
            </button>
        </div>
        
        <div class="override-search-filters">
            <div class="search-section">
                <input type="text" id="overrideSearchInput" class="form-control" placeholder="Search faculty...">
                <button class="btn btn-primary" onclick="searchOverrideClearances()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="filter-section">
                <select id="overrideDepartmentFilter" class="form-control">
                    <option value="">All Departments</option>
                    <option value="ICT">ICT Department</option>
                    <option value="Business">Business Department</option>
                    <option value="Engineering">Engineering Department</option>
                </select>
                <select id="overrideEmploymentFilter" class="form-control">
                    <option value="">All Employment Types</option>
                    <option value="full-time">Full Time</option>
                    <option value="part-time">Part Time</option>
                    <option value="part-time-full-load">Part Time - Full Load</option>
                </select>
                <select id="overrideStatusFilter" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in-progress">In Progress</option>
                </select>
            </div>
        </div>
        
        <div class="override-bulk-actions">
            <div class="bulk-controls">
                <label class="select-all-checkbox">
                    <input type="checkbox" id="overrideSelectAll" onchange="toggleOverrideSelectAll()">
                    <span class="checkmark"></span>
                    Select All
                </label>
                <div class="bulk-buttons">
                    <button class="btn btn-success" onclick="bulkApproveOverride()" disabled>
                        <i class="fas fa-check"></i> Bulk Approve
                    </button>
                    <button class="btn btn-danger" onclick="bulkRejectOverride()" disabled>
                        <i class="fas fa-times"></i> Bulk Reject
                    </button>
                    <button class="btn btn-info" onclick="exportOverrideReport()" disabled>
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </div>
            </div>
            <div class="override-stats">
                <span id="overrideStats">Selected: 0 | Approved: 0 | Rejected: 0</span>
            </div>
        </div>
        
        <div class="override-clearances-list" id="overrideClearancesList">
            <!-- Dynamic content will be populated -->
        </div>
    </div>

    <script>
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            // Check if we're on mobile (screen width <= 768px)
            if (window.innerWidth <= 768) {
                // Mobile behavior - use 'active' class
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    if (backdrop) backdrop.style.display = 'none';
                } else {
                    sidebar.classList.add('active');
                    if (backdrop) backdrop.style.display = 'block';
                }
            } else {
                // Desktop behavior - use 'collapsed' class
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                } else {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }
        }

        // Bulk selection functionality (replaces old select all)
        // The new bulk selection modal handles all selection logic

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.faculty-checkbox').length;
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

        function toggleHeaderCheckbox() {
            const headerCheckbox = document.getElementById('headerCheckbox');
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = headerCheckbox.checked;
            });
            
            updateBulkButtons();
            updateSelectionCounter();
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedCount = checkedBoxes.length;
            // Counters
            let activeCount = 0, inactiveCount = 0, resignedCount = 0, eligibleReset = 0;
            checkedBoxes.forEach(cb=>{
                const row = cb.closest('tr');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-applied, .status-badge.clearance-in-progress, .status-badge.clearance-complete');
                if(accountBadge){
                    if(accountBadge.classList.contains('account-active')) activeCount++;
                    else if(accountBadge.classList.contains('account-inactive')) inactiveCount++;
                    else if(accountBadge.classList.contains('account-resigned')) resignedCount++;
                }
                if(clearanceBadge && (clearanceBadge.classList.contains('clearance-applied') || clearanceBadge.classList.contains('clearance-in-progress') || clearanceBadge.classList.contains('clearance-complete'))){
                    eligibleReset++;
                }
            });
            // Tab context
            const tabStatus = window.currentTabStatus || '';
            const activateBtn   = document.getElementById('bulkActivateBtn');
            const deactivateBtn = document.getElementById('bulkDeactivateBtn');
            const resignedBtn   = document.getElementById('bulkResignedBtn');
            const resetBtn      = document.getElementById('bulkResetBtn');
            const deleteBtn     = document.getElementById('bulkDeleteBtn');

            // Default disable all
            activateBtn.disabled = deactivateBtn.disabled = resignedBtn.disabled = resetBtn.disabled = deleteBtn.disabled = true;
            deleteBtn.disabled = selectedCount === 0;
            
            // Smart button enablement based on selection and tab context
            if(tabStatus === 'active'){
                // In Active tab: only deactivate and other actions enabled
                deactivateBtn.disabled = selectedCount === 0;
                resignedBtn.disabled = selectedCount === 0;
            } else if(tabStatus === 'inactive'){
                // In Inactive tab: only activate and other actions enabled
                activateBtn.disabled = selectedCount === 0;
                resignedBtn.disabled = selectedCount === 0;
            } else { // overall tab
                // Smart logic for mixed selections
                if(activeCount > 0 && inactiveCount === 0 && resignedCount === 0){
                    // All selected are active - can only deactivate
                    deactivateBtn.disabled = false;
                } else if(inactiveCount > 0 && activeCount === 0 && resignedCount === 0){
                    // All selected are inactive - can only activate
                    activateBtn.disabled = false;
                } else if(activeCount > 0 && inactiveCount > 0){
                    // Mixed active/inactive - both buttons disabled
                    activateBtn.disabled = true;
                    deactivateBtn.disabled = true;
                }
                // Resigned button always available in overall tab
                resignedBtn.disabled = selectedCount === 0;
            }
            
            // Reset clearance enable - works across all tabs
            resetBtn.disabled = !(selectedCount > 0 && eligibleReset > 0);

            updateSelectionCounter();
        }

        // Enhanced notification function (using external alert system)
        function showNotification(message, type = 'info') {
            showToastNotification(message, type);
        }

        // Show info toast function
        function showInfoToast(message) {
            showToastNotification(message, 'info');
        }

        // Bulk Actions with Confirmation
        function activateSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to activate', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Activate Faculty',
                `Are you sure you want to activate ${selectedCount} selected faculty?`,
                'Activate',
                'Cancel',
                () => {
                    // Perform activation
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                        
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.remove('account-inactive', 'account-resigned');
                        statusBadge.classList.add('account-active');
                    });
                    
                    // Update statistics
                    updateBulkStatistics('activate', selectedCount);
                    showToastNotification(`✓ Successfully activated ${selectedCount} faculty`, 'success');
                },
                'info'
            );
        }

        function deactivateSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to deactivate', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Deactivate Faculty',
                `Are you sure you want to deactivate ${selectedCount} selected faculty?`,
                'Deactivate',
                'Cancel',
                () => {
                    // Perform deactivation
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                        
                        statusBadge.textContent = 'Inactive';
                        statusBadge.classList.remove('account-active', 'account-resigned');
                        statusBadge.classList.add('account-inactive');
                    });
                    
                    // Update statistics
                    updateBulkStatistics('deactivate', selectedCount);
                    showToastNotification(`✓ Successfully deactivated ${selectedCount} faculty`, 'success');
                },
                'warning'
            );
        }

        function markResigned() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to mark as resigned', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Mark Faculty as Resigned',
                `Are you sure you want to mark ${selectedCount} selected faculty as Resigned? This will change their status permanently.`,
                'Mark as Resigned',
                'Cancel',
                () => {
                    // Perform resignation
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Resigned';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-resigned');
                        }
                    });
                    
                    // Update statistics
                    updateBulkStatistics('resigned', selectedCount);
                    showToastNotification(`✓ Successfully marked ${selectedCount} faculty as Resigned`, 'success');
                },
                'info'
            );
        }

        // Reset clearance status for new term
        function resetClearanceForNewTerm() {
            const selectedCheckboxes=document.querySelectorAll('.faculty-checkbox:checked');
            if(selectedCheckboxes.length===0){showToastNotification('Please select faculty to reset clearance','warning');return;}
            // Filter eligible rows (applied/in-progress/complete)
            const eligible=[], ignored=[];
            selectedCheckboxes.forEach(cb=>{
                const row=cb.closest('tr');
                const badge=row.querySelector('.status-badge.clearance-applied, .status-badge.clearance-in-progress, .status-badge.clearance-complete, .status-badge.clearance-unapplied');
                if(badge && (badge.classList.contains('clearance-applied')||badge.classList.contains('clearance-in-progress')||badge.classList.contains('clearance-complete'))){
                    eligible.push(cb.getAttribute('data-id'));
                }else{
                    ignored.push(cb.getAttribute('data-id'));
                }
            });
            if(eligible.length===0){showToastNotification('No eligible faculty selected for clearance reset','info');return;}

            showConfirmationModal(
                'Reset Clearance Status',
                `The clearance status for ${eligible.length} faculty will be reverted to "Unapplied". Proceed?`,
                'Reset Clearance',
                'Cancel',
                ()=>{
                    fetch('../../api/clearance/reset_selected.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({employee_ids:eligible})})
                    .then(r=>r.json()).then(res=>{
                        if(!res.success) throw new Error(res.message||'Reset failed');
                        // update eligible rows UI
                        eligible.forEach(id=>{
                            const row=document.querySelector(`.faculty-checkbox[data-id="${id}"]`).closest('tr');
                            const badge=row.querySelector('.status-badge.clearance-applied, .status-badge.clearance-in-progress, .status-badge.clearance-complete');
                            if(badge){
                                badge.textContent='Unapplied';
                                badge.classList.remove('clearance-applied','clearance-in-progress','clearance-complete');
                                badge.classList.add('clearance-unapplied');
                            }
                        });
                        showToastNotification(`✓ Clearance reset for ${res.reset_count} faculty`, 'success');
                        refreshFacultyTable().then(()=>initializePagination());
                    }).catch(err=>{console.error(err);showToastNotification(err.message,'error');});
                },
                'warning'
            );
        }

        function deleteSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to delete', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Delete Faculty',
                `Are you sure you want to delete ${selectedCount} selected faculty? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    // Perform deletion
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        row.remove();
                    });
                    
                    // Update statistics
                    updateBulkStatistics('delete', selectedCount);
                    showToastNotification(`✓ Successfully deleted ${selectedCount} faculty`, 'success');
                },
                'danger'
            );
        }

        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }

        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeFaculty');
            const inactiveCount = document.getElementById('inactiveFaculty');
            const resignedCount = document.getElementById('resignedFaculty');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            let currentResigned = parseInt(resignedCount.textContent.replace(',', ''));
            
            if (action === 'activate') {
                currentActive += count;
                currentInactive -= count;
            } else if (action === 'deactivate') {
                currentActive -= count;
                currentInactive += count;
            } else if (action === 'resigned') {
                // Move from active/inactive to resigned
                currentResigned += count;
                // We'd need to track which faculty were active vs inactive
                // For now, we'll assume they were active
                currentActive -= count;
            } else if (action === 'delete') {
                // For delete, we just need to update the total count
                // The specific counts (active, inactive, resigned) might not change
                // unless the user explicitly changes them.
                // For simplicity, we'll just update the total count.
                // If the user wants to remove from specific counts, they'd need to handle that.
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
            resignedCount.textContent = currentResigned.toLocaleString();
        }

        // Individual faculty actions
        async function populateEditFormLive(empId){
            try{
                const res = await fetch(`../../api/users/facultyList.php?employee_number=${encodeURIComponent(empId)}`,{credentials:'include'});
                const data = await res.json();
                if(!data.success){showToastNotification(data.message||'Failed to load faculty','error');return;}
                const f = data.faculty;
                document.getElementById('editFacultyForm').dataset.userId = f.user_id; // Store user_id
                document.getElementById('editFacultyId').value = empId;
                document.getElementById('editEmployeeNumber').value = empId;
                document.getElementById('editEmploymentStatus').value = f.employment_status.toLowerCase().replace(/ /g,'-');
                document.getElementById('editLastName').value = f.last_name;
                document.getElementById('editFirstName').value = f.first_name;
                document.getElementById('editMiddleName').value = f.middle_name||'';
                document.getElementById('editEmail').value = f.email||'';
                document.getElementById('editContactNumber').value = f.contact_number||'';
                document.getElementById('editAccountStatus').value = f.account_status;
            }catch(err){console.error(err);showToastNotification('Network error','error');}
        }

        function editFaculty(facultyId) {
            openEditFacultyModal(facultyId);
            populateEditFormLive(facultyId);
        }

        // intercept update faculty submit
        window.submitEditFacultyForm = function(){
            const btn=document.getElementById('editSubmitBtn');
            const form=document.getElementById('editFacultyForm');
            const data={
                employee_number: form.editEmployeeNumber.value,
                email: form.editEmail.value,
                contact_number: form.editContactNumber.value,
                status: form.editAccountStatus.value
            };

            // Only include employment_status if the dropdown has a value (admin actually selected or populated)
            const empVal = form.editEmploymentStatus.value;
            if(empVal!=='' && empVal!==null){
                data.employment_status = empVal;
            }

            btn.disabled=true;btn.textContent='Updating...';
            fetch('../../api/users/update_faculty.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
            .then(r=>r.json())
            .then(res=>{
                if(res.success){showToastNotification('Faculty updated','success');closeEditFacultyModal();refreshFacultyTable().then(()=>initializePagination());}
                else showToastNotification(res.message,'error');
            })
            .catch(err=>{console.error(err);showToastNotification('Network error', 'error');})
            .finally(()=>{btn.disabled=false;btn.textContent='Update Faculty';});
        }


        function deleteFaculty(facultyId) {
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            showConfirmationModal(
                'Delete Faculty',
                `Are you sure you want to delete ${facultyName}? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    // call backend
                    fetch('../../api/users/delete_faculty.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({employee_number:facultyId})})
                    .then(r=>r.json())
                    .then(res=>{
                        if(!res.success){throw new Error(res.message||'Delete failed');}
                        // remove row and refresh stats
                        row.remove(); // This is a UI-only change. A table refresh is better.
                        showToastNotification('Faculty deleted successfully','success');
                        refreshFacultyTable().then(()=>initializePagination());
                    })
                    .catch(err=>{console.error(err);showToastNotification(err.message,'error');});
                },
                'danger'
            );
        }

        // Filter functions
        function updateFilterPrograms() {
            // This function is not needed for faculty management
            // Keeping it for compatibility if the file is copied.
        }

        function applyFilters() {
            currentPage = 1;
            refreshFacultyTable();
        }

        // Clear all filters
        function clearFilters() {
            // Use the comprehensive clearing function
            clearAllSelectionsAndFilters();
            refreshFacultyTable();
            showToastNotification('All filters cleared', 'info');
        }

        // Update statistics based on school term selection
        function updateStatisticsByTerm() {
            const selectedTerm = document.getElementById('schoolTermFilter').value;
            const allRows = document.querySelectorAll('#facultyTableBody tr');
            
            let activeCount = 0;
            let inactiveCount = 0;
            let resignedCount = 0;
            let totalCount = 0;
            
            allRows.forEach(row => {
                const rowTerm = row.getAttribute('data-term');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                // Only count if term matches or if "All School Terms" is selected
                if (!selectedTerm || rowTerm === selectedTerm) {
                    totalCount++;
                    
                    if (accountBadge) {
                        if (accountBadge.classList.contains('account-active')) {
                            activeCount++;
                        } else if (accountBadge.classList.contains('account-inactive')) {
                            inactiveCount++;
                        } else if (accountBadge.classList.contains('account-resigned')) {
                            resignedCount++;
                        }
                    }
                }
            });
            
            // Update statistics display
            document.getElementById('totalFaculty').textContent = totalCount;
            document.getElementById('activeFaculty').textContent = activeCount;
            document.getElementById('inactiveFaculty').textContent = inactiveCount;
            document.getElementById('resignedFaculty').textContent = resignedCount;
            
            // Apply filters to update table view
            applyFilters();
        }

        // Fetch faculty list from backend and build table body
        async function refreshFacultyTable(){
            try{
                const tbody=document.getElementById('facultyTableBody');
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading faculty...</td></tr>`;

                const url = new URL('../../api/users/facultyList.php', window.location.href);
                url.searchParams.append('limit', entriesPerPage);
                url.searchParams.append('page', currentPage);
                if (document.getElementById('searchInput').value) url.searchParams.append('search', document.getElementById('searchInput').value);
                if (document.getElementById('employmentStatusFilter').value) url.searchParams.append('employment_status', document.getElementById('employmentStatusFilter').value);
                if (document.getElementById('accountStatusFilter').value) url.searchParams.append('account_status', document.getElementById('accountStatusFilter').value);
                if (document.getElementById('clearanceStatusFilter').value) url.searchParams.append('clearance_status', document.getElementById('clearanceStatusFilter').value);
                if (document.getElementById('schoolTermFilter').value) url.searchParams.append('school_term', document.getElementById('schoolTermFilter').value);

                const res = await fetch(url.toString(),{credentials:'include'});
                const data = await res.json();
                if(!data.success){
                    console.error(data);
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                    return;
                }

                if (!data.faculty || data.faculty.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;">No faculty members found.</td></tr>`;
                    updateStatistics(data.stats || { total: 0, active: 0, inactive: 0, resigned: 0 });
                    updatePaginationUI(0, 1, entriesPerPage);
                    return;
                }

                tbody.innerHTML='';
                data.faculty.forEach(f=>{
                    const tr=document.createElement('tr');
                    tr.setAttribute('data-term',''); // term unknown for now
                    tr.setAttribute('data-faculty-id', f.user_id); // Add faculty ID for button manager
                    const statusRaw = f.clearance_status;
                    const clearanceKey = (statusRaw || 'unapplied').toLowerCase().replace(/ /g, '-');
                    const accountStatus = (f.account_status || 'inactive').toLowerCase();

                    tr.innerHTML=`<td class="checkbox-column"><input type=\"checkbox\" class=\"faculty-checkbox\" data-id=\"${f.employee_number}\"></td>
                                <td data-label="Employee Number:">${f.employee_number}</td>
                                <td data-label="Name:">${f.first_name} ${f.last_name}</td>
                                <td data-label="Employment Status:"><span class="status-badge employment-${(f.employment_status || '').toLowerCase().replace(/ /g,'-')}">${f.employment_status}</span></td>
                                <td data-label="Account Status:"><span class="status-badge account-${accountStatus}">${f.account_status || 'N/A'}</span></td>
                                <td data-label="Clearance Progress:"><span class="status-badge clearance-${clearanceKey}">${statusRaw}</span></td>
                                <td class="action-buttons"><div class="action-buttons">
                                        <button class=\"btn-icon view-progress-btn\" onclick=\"viewClearanceProgress('${f.employee_number}')\" title=\"View Clearance Progress\"><i class=\"fas fa-tasks\"></i></button>
                                        <button class=\"btn-icon edit-btn\" onclick=\"editFaculty('${f.employee_number}')\" title=\"Edit\"><i class=\"fas fa-edit\"></i></button>
                                        <button class=\"btn-icon delete-btn\" onclick=\"deleteFaculty('${f.employee_number}')\" title=\"Delete\"><i class=\"fas fa-trash\"></i></button>
                                   </div></td>`;

                    if(accountStatus!=='active'){
                        tr.classList.add('row-disabled');
                    }
                    tbody.appendChild(tr);
                });
                updateStatistics(data.stats);
                updatePaginationUI(data.total, data.page, data.limit);
            }catch(err){
                console.error(err);
                document.getElementById('facultyTableBody').innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Error loading data.</td></tr>`;
            }
        }

        // Update statistics helper function
        function updateStatistics(statsOrction) {
            const totalCount = document.getElementById('totalFaculty');
            const activeCount = document.getElementById('activeFaculty');
            const inactiveCount = document.getElementById('inactiveFaculty');
            const resignedCount = document.getElementById('resignedFaculty');
            
            if (typeof statsOrction === 'object' && statsOrction !== null) {
                // If an object is passed, set the stats directly
                totalCount.textContent = (statsOrction.total || 0).toLocaleString();
                activeCount.textContent = (statsOrction.active || 0).toLocaleString();
                inactiveCount.textContent = (statsOrction.inactive || 0).toLocaleString();
                resignedCount.textContent = (statsOrction.resigned || 0).toLocaleString();
            }
        }

        /*
        // Update statistics helper function
        function updateStatistics(statsOrction) {
            const totalCount = document.getElementById('totalFaculty');
            const activeCount = document.getElementById('activeFaculty');
            const inactiveCount = document.getElementById('inactiveFaculty');
            const resignedCount = document.getElementById('resignedFaculty');
            
            if (typeof statsOrAction === 'object' && statsOrAction !== null) {
                // If an object is passed, set the stats directly
                totalCount.textContent = (statsOrAction.total || 0).toLocaleString();
                activeCount.textContent = (statsOrAction.active || 0).toLocaleString();
                inactiveCount.textContent = (statsOrAction.inactive || 0).toLocaleString();
                resignedCount.textContent = (statsOrAction.resigned || 0).toLocaleString();
            } else if (typeof statsOrAction === 'string') {
                // Fallback for simple string actions (incremental updates)
                let currentActive = parseInt(activeCount.textContent.replace(/,/g, ''));
                let currentInactive = parseInt(inactiveCount.textContent.replace(/,/g, ''));
                if (statsOrAction === 'activate') {
                    currentActive++;
                    currentInactive--;
                } else if (statsOrAction === 'deactivate') {
                    currentActive--;
                    currentInactive++;
                }
                activeCount.textContent = currentActive.toLocaleString();
                inactiveCount.textContent = currentInactive.toLocaleString();
            }
        }
        */


        function updatePaginationUI(total, page, limit) {
            totalEntries = total;
            currentPage = page;
            entriesPerPage = limit;
            const totalPages = Math.ceil(total / limit);
            const startEntry = total === 0 ? 0 : (page - 1) * limit + 1;
            const endEntry = Math.min(page * limit, total);

            document.getElementById('paginationInfo').textContent = `Showing ${startEntry} to ${endEntry} of ${total} entries`;

            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) addPageButton(i, i === page);
            } else {
                if (page <= 4) {
                    for (let i = 1; i <= 5; i++) addPageButton(i, i === page);
                    addEllipsis();
                    addPageButton(totalPages, false);
                } else if (page >= totalPages - 3) {
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = totalPages - 4; i <= totalPages; i++) addPageButton(i, i === page);
                } else {
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = page - 1; i <= page + 1; i++) addPageButton(i, i === page);
                    addEllipsis();
                    addPageButton(totalPages, false);
                }
            }

            document.getElementById('prevPage').disabled = page === 1;
            document.getElementById('nextPage').disabled = page >= totalPages;
        }

        function addPageButton(pageNum, isActive) {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            const button = document.createElement('button');
            button.className = `pagination-btn ${isActive ? 'active' : ''}`;
            button.textContent = pageNum;
            button.onclick = () => goToPage(pageNum);
            pageNumbersContainer.appendChild(button);
        }

        function addEllipsis() {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            const span = document.createElement('span');
            span.className = 'pagination-dots';
            span.textContent = '...';
            span.style.padding = '8px 12px';
            span.style.color = 'var(--medium-muted-blue)';
            pageNumbersContainer.appendChild(span);
        }

        function goToPage(pageNum) {
            currentPage = pageNum;
            refreshFacultyTable();
        }

        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next') {
                const totalPages = Math.ceil(totalEntries / entriesPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                }
            }
            refreshFacultyTable();
        }

        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
            refreshFacultyTable();
        }

        // Update statistics based on filtered results
        function updateFilteredStatistics() {
            const visibleRows = document.querySelectorAll('#facultyTableBody tr:not([style*="display: none"])');
            
            let activeCount = 0;
            let inactiveCount = 0;
            let resignedCount = 0;
            
            visibleRows.forEach(row => {
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                if (accountBadge) {
                    if (accountBadge.classList.contains('account-active')) {
                        activeCount++;
                    } else if (accountBadge.classList.contains('account-inactive')) {
                        inactiveCount++;
                    } else if (accountBadge.classList.contains('account-resigned')) {
                        resignedCount++;
                    }
                }
            });
            
            // Update statistics display
            document.getElementById('activeFaculty').textContent = activeCount;
            document.getElementById('inactiveFaculty').textContent = inactiveCount;
            document.getElementById('resignedFaculty').textContent = resignedCount;
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        let filteredEntries = []; // This can be removed, but let's keep for now if other logic depends on it.

        // Scroll to top functionality
        function scrollToTop() {
            const tableWrapper = document.getElementById('facultyTableWrapper');
            tableWrapper.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show scroll to top button when scrolled
        document.getElementById('facultyTableWrapper').addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollToTopBtn');
            if (this.scrollTop > 200) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        // Modal functions
        function openAddFacultyModal() {
            openFacultyRegistrationModal();
        }

        function triggerImportModal() {
            console.log('triggerImportModal function called (Admin - Faculty)');
            console.log('Checking window.openImportModal:', typeof window.openImportModal);
            
            // Wait a bit if function not immediately available (script loading race condition)
            if (typeof window.openImportModal !== 'function') {
                console.warn('window.openImportModal not found immediately, waiting 100ms...');
                setTimeout(() => {
                    if (typeof window.openImportModal === 'function') {
                        window.openImportModal('faculty', 'faculty_import', 'Admin');
                        console.log('Import modal opened successfully (delayed)');
                    } else {
                        console.error('Import modal function still not found after delay');
                        console.error('Debug - window object keys:', Object.keys(window).filter(k => k.includes('Import') || k.includes('Modal')).slice(0, 20));
                        showToastNotification('Import modal not available. Please refresh the page.', 'error');
                    }
                }, 100);
                return;
            }
            
            // Initialize modal with page context: faculty import for Admin
            window.openImportModal('faculty', 'faculty_import', 'Admin');
            console.log('Import modal opened successfully');
        }

        function triggerExportModal() {
            // For Admin, we have two export modals:
            // 1. ExportModal.php - for clearance reports (progress, applicant status)
            // 2. FacultyExportModal.php - for faculty data exports
            
            // Check if ExportModal (clearance reports) is available first
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else if (typeof openFacultyExportModal === 'function') {
                // Fallback to FacultyExportModal for faculty data exports
                openFacultyExportModal();
            } else {
                console.error('Export modal function not found');
                showToastNotification('Export modal not available', 'error');
            }
        }

        // Signatory Override Modal Functions
        function openSignatoryOverrideModal() {
            const modal = document.getElementById('signatoryOverrideModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSignatoryOverrideModal() {
            const modal = document.getElementById('signatoryOverrideModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function proceedWithOverride() {
            const positionSelect = document.getElementById('staffPositionSelect');
            const customInput = document.getElementById('customPositionInput');
            
            let selectedPosition = positionSelect.value;
            if (!selectedPosition && customInput.value.trim()) {
                selectedPosition = customInput.value.trim();
            }
            
            if (!selectedPosition) {
                showToastNotification('Please select or enter a position', 'warning');
                return;
            }
            
            // Close modal and start override session
            closeSignatoryOverrideModal();
            startOverrideSession(selectedPosition);
        }

        function startOverrideSession(position) {
            // Hide main content and show override interface
            document.querySelector('.main-content').style.display = 'none';
            document.getElementById('overrideSessionInterface').style.display = 'block';
            
            // Update position display
            document.getElementById('overridePositionDisplay').textContent = `Signing as: ${position}`;
            
            // Start session timer
            startSessionTimer();
            
            // Load pending clearances for the position
            loadOverrideClearances(position);
            
            showToastNotification(`Override session started for ${position} position`, 'success');
        }

        function endOverrideSession() {
            showConfirmationModal(
                'End Override Session',
                'Are you sure you want to end the override session? All unsaved changes will be lost.',
                'End Session',
                'Cancel',
                () => {
                    // Hide override interface and show main content
                    document.getElementById('overrideSessionInterface').style.display = 'none';
                    document.querySelector('.main-content').style.display = 'block';
                    
                    // Stop session timer
                    stopSessionTimer();
                    
                    // Reset override interface
                    resetOverrideInterface();
                    
                    showToastNotification('Override session ended', 'info');
                },
                'warning'
            );
        }

        function loadOverrideClearances(position) {
            // Simulate loading clearances for the selected position
            const clearancesList = document.getElementById('overrideClearancesList');
            clearancesList.innerHTML = `
                <div class="clearance-item">
                    <div class="clearance-info">
                        <h4>Dr. Maria Santos</h4>
                        <p>ICT Department - Full Time</p>
                        <span class="status-badge clearance-in-progress">In Progress</span>
                    </div>
                    <div class="clearance-actions">
                        <input type="checkbox" class="override-checkbox" data-id="1">
                        <button class="btn btn-success btn-sm" onclick="approveOverrideClearance(1)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="rejectOverrideClearance(1)">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
                <div class="clearance-item">
                    <div class="clearance-info">
                        <h4>Prof. Juan Dela Cruz</h4>
                        <p>Business Department - Part Time</p>
                        <span class="status-badge clearance-in-progress">In Progress</span>
                    </div>
                    <div class="clearance-actions">
                        <input type="checkbox" class="override-checkbox" data-id="2">
                        <button class="btn btn-success btn-sm" onclick="approveOverrideClearance(2)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="rejectOverrideClearance(2)">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            `;
            
            // Update pending clearances preview
            updatePendingClearancesPreview(position);
        }

        function updatePendingClearancesPreview(position) {
            const previewList = document.getElementById('pendingClearancesList');
            previewList.innerHTML = `
                <div class="preview-item">Dr. Maria Santos - ICT (In Progress)</div>
                <div class="preview-item">Prof. Juan Dela Cruz - Business (In Progress)</div>
                <div class="preview-item">Dr. Ana Rodriguez - Engineering (In Progress)</div>
            `;
        }

        function startSessionTimer() {
            let seconds = 0;
            window.sessionTimer = setInterval(() => {
                seconds++;
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                document.getElementById('sessionTimer').textContent = 
                    `Session: ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }, 1000);
        }

        function stopSessionTimer() {
            if (window.sessionTimer) {
                clearInterval(window.sessionTimer);
                window.sessionTimer = null;
            }
        }

        function resetOverrideInterface() {
            document.getElementById('staffPositionSelect').value = '';
            document.getElementById('customPositionInput').value = '';
            document.getElementById('overrideSelectAll').checked = false;
            document.getElementById('overrideStats').textContent = 'Selected: 0 | Approved: 0 | Rejected: 0';
        }

        function toggleOverrideSelectAll() {
            const selectAllCheckbox = document.getElementById('overrideSelectAll');
            const overrideCheckboxes = document.querySelectorAll('.override-checkbox');
            
            overrideCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateOverrideBulkButtons();
        }

        function updateOverrideBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.override-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.override-bulk-actions .bulk-buttons .btn');
            
            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
            });
            
            updateOverrideStats();
        }

        function updateOverrideStats() {
            const selectedCount = document.querySelectorAll('.override-checkbox:checked').length;
            const approvedCount = document.querySelectorAll('.status-badge.clearance-complete').length;
            const rejectedCount = document.querySelectorAll('.status-badge.clearance-in-progress').length;
            
            document.getElementById('overrideStats').textContent = 
                `Selected: ${selectedCount} | Approved: ${approvedCount} | Rejected: ${rejectedCount}`;
        }

        function bulkApproveOverride() {
            const selectedCheckboxes = document.querySelectorAll('.override-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                showToastNotification('Please select clearances to approve', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Bulk Approve Clearances',
                `Are you sure you want to approve ${selectedCheckboxes.length} selected clearances?`,
                'Approve All',
                'Cancel',
                () => {
                    selectedCheckboxes.forEach(checkbox => {
                        const id = checkbox.getAttribute('data-id');
                        approveOverrideClearance(id);
                    });
                    showToastNotification(`${selectedCheckboxes.length} clearances approved successfully`, 'success');
                },
                'success'
            );
        }

        function bulkRejectOverride() {
            const selectedCheckboxes = document.querySelectorAll('.override-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                showToastNotification('Please select clearances to reject', 'warning');
                return;
            }
            
            // Get selected clearance IDs
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-id'));
            
            // Open rejection remarks modal for bulk override rejection
            // Rejection functionality removed - admins don't reject clearances
        }

        function approveOverrideClearance(id) {
            const clearanceItem = document.querySelector(`.override-checkbox[data-id="${id}"]`).closest('.clearance-item');
            const statusBadge = clearanceItem.querySelector('.status-badge');
            
            statusBadge.textContent = 'Complete';
            statusBadge.classList.remove('clearance-in-progress', 'clearance-applied');
            statusBadge.classList.add('clearance-complete');
            
            showToastNotification('Clearance approved successfully', 'success');
            updateOverrideStats();
        }

        function rejectOverrideClearance(id) {
            const clearanceItem = document.querySelector(`.override-checkbox[data-id="${id}"]`).closest('.clearance-item');
            const facultyName = clearanceItem.querySelector('h4').textContent;
            
            // Rejection functionality removed - admins don't reject clearances
        }

        function exportOverrideReport() {
            showToastNotification('Override report export functionality will be implemented', 'info');
        }

        function searchOverrideClearances() {
            const searchTerm = document.getElementById('overrideSearchInput').value.toLowerCase();
            const clearanceItems = document.querySelectorAll('.clearance-item');
            
            clearanceItems.forEach(item => {
                const facultyName = item.querySelector('h4').textContent.toLowerCase();
                if (facultyName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Clearance Progress Modal Function
        function viewClearanceProgress(facultyId) {
            // Get faculty name from the table row
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            // Open the clearance progress modal
            openClearanceProgressModal(facultyId, 'faculty', facultyName);
        }

        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            targetId: null,
            targetName: null,
            targetType: 'faculty',
            isBulk: false,
            targetIds: []
        };

        async function populateFilter(selectId, url, placeholder, valueField = 'value', textField = 'text') {
            const select = document.getElementById(selectId);
            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                select.innerHTML = `<option value="">${placeholder}</option>`;
                if (data.success && data.options) {
                    const termMap = {
                        '1st': '1st Semester',
                        '2nd': '2nd Semester',
                        '3rd': '3rd Semester',
                        'Summer': 'Summer'
                    };

                    data.options.forEach(option => {
                        const optionElement = document.createElement('option');
                        optionElement.value = typeof option === 'object' ? option[valueField] : option;
                        let textContent = typeof option === 'object' ? option[textField] : option;

                        // Apply term mapping for school terms filter
                        if (selectId === 'schoolTermFilter' && typeof option === 'object') {
                            const [year, term] = option.text.split(' - ');
                            textContent = `${year} - ${termMap[term] || term}`;
                        }
                        
                        optionElement.textContent = textContent;
                        select.appendChild(optionElement);
                    });
                }
            } catch (error) {
                console.error(`Error loading options for ${selectId}:`, error);
                select.innerHTML = `<option value="">Error loading options</option>`;
            }
        }

        async function loadAccountStatuses() {
            const accountStatusesFilter = document.getElementById('accountStatusFilter');
            accountStatusesFilter.innerHTML = '<option value="">Loading account statuses...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'users');
            url.searchParams.append('column', 'account_status');
            url.searchParams.append('exclude', 'graduated');
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
        }

        async function loadEmploymentStatuses() {
            const employmentStatusesFilter = document.getElementById('employmentStatusFilter');
            employmentStatusesFilter.innerHTML = '<option value="">Loading employment statuses...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'faculty');
            url.searchParams.append('column', 'employment_status');
            await populateFilter('employmentStatusFilter', url, 'All Employment Statuses');
        }

        async function loadClearanceStatuses() {
            const clearanceStatusesFilter = document.getElementById('clearanceStatusFilter');
            clearanceStatusesFilter.innerHTML = '<option value="">Loading clearance statuses...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'clearance_forms');
            url.searchParams.append('column', 'clearance_form_progress');
            await populateFilter('clearanceStatusFilter', url, 'All Clearance Statuses');
        }

        async function loadSchoolTerms() {
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            schoolTermFilter.innerHTML = '<option value="">Loading school terms...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'school_terms');
            await populateFilter('schoolTermFilter', url, 'All School Terms');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {

            // Load dynamic filters
            loadEmploymentStatuses();
            loadAccountStatuses();
            loadClearanceStatuses();
            loadSchoolTerms();

            // load faculty table
            refreshFacultyTable();

            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized');
            }

            // Add event listeners for checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
            
            // Add event listeners for filter checkboxes in bulk selection modal
            document.addEventListener('change', function(e) {
                if (e.target.id && e.target.id.startsWith('filter')) {
                    // Update any real-time feedback if needed
                }
            });

            // Listen for new faculty event from modal
            document.addEventListener('faculty-added',function(e){
                refreshFacultyTable().then(()=>{
                    showToastNotification('Faculty table refreshed', 'success');
                });
            });


            // load current clearance period for banner
            fetch('../../api/clearance/periods.php', { credentials: 'include' })
                .then(r => r.json())
                .then(per => {
                    const yearEl = document.getElementById('currentAcademicYear');
                    const semesterEl = document.getElementById('currentSemester');
                    if (!yearEl || !semesterEl) return;
                    if (per.success && per.active_periods && per.active_periods.length > 0) {
                        const p = per.active_periods[0];
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
                        yearEl.textContent = p.school_year;
                        semesterEl.textContent = semLabel;
                    } else {
                        yearEl.textContent = 'No active period';
                        semesterEl.textContent = 'No term';
                    }
                })
                .catch(() => {
                    const yearEl = document.getElementById('currentAcademicYear');
                    const semesterEl = document.getElementById('currentSemester');
                    if (yearEl) yearEl.textContent = 'Unable to load';
                    if (semesterEl) semesterEl.textContent = 'Error';
                });
              
            // Add click outside modal functionality for bulk selection modal
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('bulkSelectionModal');
                if (modal && modal.style.display === 'flex') {
                    if (e.target === modal) {
                        closeBulkSelectionModal();
                    }
                }
            });
            
            // Add keyboard support for bulk selection modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('bulkSelectionModal');
                    if (modal && modal.style.display === 'flex') {
                        closeBulkSelectionModal();
                    }
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
        });
        

        // Bulk Selection Modal Functions
        function openBulkSelectionModal() {
            const modal = document.getElementById('bulkSelectionModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Reset all checkboxes
            resetBulkSelectionFilters();
        }

        function closeBulkSelectionModal() {
            const modal = document.getElementById('bulkSelectionModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function resetBulkSelectionFilters() {
            // Reset all filter checkboxes
            document.getElementById('filterFullTime').checked = false;
            document.getElementById('filterPartTime').checked = false;
            document.getElementById('filterPartTimeFullLoad').checked = false;
            document.getElementById('filterActive').checked = false;
            document.getElementById('filterInactive').checked = false;
            document.getElementById('filterResigned').checked = false;
            document.getElementById('filterUnapplied').checked = false;
            document.getElementById('filterInProgress').checked = false;
            document.getElementById('filterComplete').checked = false;
        }


        function applyBulkSelection() {
            const selectedFilters = getSelectedFilters();
            
            if (Object.values(selectedFilters).every(filter => !filter)) {
                // No filters means select all visible rows (wildcard)
                selectAllVisibleRows();
                closeBulkSelectionModal();
                updateBulkButtons();
                updateSelectionCounter();
                showToastNotification('All visible rows selected', 'success');
                return;
            }
            
            // Apply the filters to select faculty
            selectFacultyByFilters(selectedFilters);
            
            // Close the modal
            closeBulkSelectionModal();
            
            // Update bulk buttons based on new selection
            updateBulkButtons();
            updateSelectionCounter();
            
            showToastNotification('Bulk selection applied successfully', 'success');
        }

        // Select all visible rows; if include-all-pages is desired in future, we can extend this
        function selectAllVisibleRows(){
            const rows = document.querySelectorAll('#facultyTableBody tr');
            let count = 0;
            rows.forEach(r=>{
                if (r.style.display === 'none') return; // only visible scope
                const cb = r.querySelector('.faculty-checkbox');
                if (cb){ cb.checked = true; count++; }
            });
            showInfoToast(`Selected ${count} faculty`);
        }

        function getSelectedFilters() {
            return {
                fullTime: document.getElementById('filterFullTime').checked,
                partTime: document.getElementById('filterPartTime').checked,
                partTimeFullLoad: document.getElementById('filterPartTimeFullLoad').checked,
                active: document.getElementById('filterActive').checked,
                inactive: document.getElementById('filterInactive').checked,
                resigned: document.getElementById('filterResigned').checked,
                unapplied: document.getElementById('filterUnapplied').checked,
                inProgress: document.getElementById('filterInProgress').checked,
                complete: document.getElementById('filterComplete').checked
            };
        }

        function selectFacultyByFilters(filters) {
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            let selectedCount = 0;
            
            tableRows.forEach(row => {
                const shouldSelect = shouldRowBeSelected(row, filters);
                const checkbox = row.querySelector('.faculty-checkbox');
                
                if (checkbox) {
                    checkbox.checked = shouldSelect;
                    if (shouldSelect) selectedCount++;
                }
            });
            
            // Update selection counter
            updateSelectionCounter();
            
            // Show results count
            showInfoToast(`Selected ${selectedCount} faculty based on filters`);
        }

        function shouldRowBeSelected(row, filters) {
            // Get row data
            const employmentBadge = row.querySelector('.status-badge[class*="employment-"]');
            const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-applied, .status-badge.clearance-in-progress, .status-badge.clearance-complete');
            
            let employmentMatch = false;
            let accountMatch = false;
            let clearanceMatch = false;
            
            // Check employment status filters
            if (filters.fullTime || filters.partTime || filters.partTimeFullLoad) {
                if (filters.fullTime && employmentBadge && employmentBadge.classList.contains('employment-full-time')) {
                    employmentMatch = true;
                }
                if (filters.partTime && employmentBadge && employmentBadge.classList.contains('employment-part-time')) {
                    employmentMatch = true;
                }
                if (filters.partTimeFullLoad && employmentBadge && employmentBadge.classList.contains('employment-part-time-full-load')) {
                    employmentMatch = true;
                }
            } else {
                // If no employment filters selected, consider it a match (wildcard)
                employmentMatch = true;
            }
            
            // Check account status filters
            if (filters.active || filters.inactive || filters.resigned) {
                if (filters.active && accountBadge && accountBadge.classList.contains('account-active')) {
                    accountMatch = true;
                }
                if (filters.inactive && accountBadge && accountBadge.classList.contains('account-inactive')) {
                    accountMatch = true;
                }
                if (filters.resigned && accountBadge && accountBadge.classList.contains('account-resigned')) {
                    accountMatch = true;
                }
            } else {
                // If no account filters selected, consider it a match (wildcard)
                accountMatch = true;
            }
            
            // Check clearance progress filters
            if (filters.unapplied || filters.inProgress || filters.complete) {
                if (filters.unapplied && clearanceBadge && clearanceBadge.classList.contains('clearance-unapplied')) {
                    clearanceMatch = true;
                }
                if (filters.inProgress && clearanceBadge && clearanceBadge.classList.contains('clearance-in-progress')) {
                    clearanceMatch = true;
                }
                if (filters.complete && clearanceBadge && clearanceBadge.classList.contains('clearance-complete')) {
                    clearanceMatch = true;
                }
            } else {
                // If no clearance filters selected, consider it a match (wildcard)
                clearanceMatch = true;
            }
            
            // Row should be selected if it matches all three filter categories
            return employmentMatch && accountMatch && clearanceMatch;
        }
        
        // Helper function to check if there are any active filters
        function hasActiveFilters() {
            const searchInput = document.getElementById('searchInput');
            const employmentFilter = document.getElementById('employmentStatusFilter');
            const clearanceFilter = document.getElementById('clearanceStatusFilter');
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            
            return (searchInput && searchInput.value.trim() !== '') ||
                   (employmentFilter && employmentFilter.value !== '') ||
                   (clearanceFilter && clearanceFilter.value !== '') ||
                   (schoolTermFilter && schoolTermFilter.value !== '');
        }
        
        // Function to clear all selections and filters
        function clearAllSelectionsAndFilters() {
            // Clear all faculty checkboxes
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Clear search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.value = '';
            
            // Clear filter dropdowns
            const employmentFilter = document.getElementById('employmentStatusFilter');
            const clearanceFilter = document.getElementById('clearanceStatusFilter');
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            
            if (employmentFilter) employmentFilter.value = '';
            if (clearanceFilter) clearanceFilter.value = '';
            if (schoolTermFilter) schoolTermFilter.value = '';
            
            // Reset bulk selection modal filters (if modal is open)
            resetBulkSelectionFilters();
            
            // Update UI states
            updateSelectionCounter();
            updateBulkButtons();
            
            // Show all rows (remove any filter-based hiding)
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            // Disable clear selection button (since no selections)
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            if (clearSelectionBtn) clearSelectionBtn.disabled = true;
        }

        // Function to clear all faculty selections only (keeps filters)
        function clearAllSelections() {
            const selectedCount = getSelectedCount();
            
            if (selectedCount === 0) {
                showToastNotification('No selections to clear', 'info');
                return;
            }
            
            // Clear all faculty checkboxes
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Update UI states
            updateSelectionCounter();
            updateBulkButtons();
            
            showToastNotification(`Cleared ${selectedCount} selections`, 'success');
        }
    </script>
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Include Activity Tracker JavaScript -->
    <script src="../../assets/js/activity-tracker.js"></script>
    
    <!-- Include Clearance Button Manager -->
    <script src="../../assets/js/clearance-button-manager.js"></script>
    
    <!-- TEMPORARILY DISABLED: Include Audit Functions -->
    <!-- <?php include '../../includes/functions/audit_functions.php'; ?> -->
</body>
</html> 