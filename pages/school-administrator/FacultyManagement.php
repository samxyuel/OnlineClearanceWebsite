<?php
// Online Clearance Website - School Administrator Faculty Management

// Include the controller logic which handles all authorization and data fetching.
require_once __DIR__ . '/../../controllers/FacultyManagementController.php';

// The controller function acts as a "gatekeeper". If it doesn't exit, the user is authorized.
handleFacultyManagementPageRequest();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - School Administrator Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles to support the new tabbed interface */
        .tab-banner-wrapper { margin-bottom: 1rem; }
        .selection-counter-pill { display: none; } /* Hide by default */
    </style>
    <style>
        /* Disabled button styling for signatory actions */
        .btn-icon:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f8f9fa !important;
            color: #6c757d !important;
            border-color: #dee2e6 !important;
        }
        
        .btn-icon:disabled:hover {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
            border-color: #dee2e6 !important;
            transform: none !important;
        }
        
        /* Bulk action buttons disabled styling */
        .bulk-buttons button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .bulk-buttons button:disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Loading spinner styles */
        .loading-row {
            text-align: center;
            padding: 40px 20px;
        }
        
        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--medium-muted-blue);
            font-size: 14px;
        }
        
        .loading-spinner i {
            font-size: 18px;
        }
        
        /* Empty state styles */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--medium-muted-blue);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Selection Counter Display Styles */
        .selection-counter-display {
            display: none; /* Hidden by default, shown via JS */
        }
    </style>
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
                            <p>Edit faculty records and sign their clearances across all departments</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>
                                <span id="staffPositionInfo">Scope: All Departments (School-wide Access)</span>
                            </div>

                            <!-- Permission Status Alerts -->
                            <?php if (!$GLOBALS['hasActivePeriod']): ?>
                            <div class="alert alert-warning" style="margin-top: 10px;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>No Active Clearance Period:</strong> You can view faculty data but cannot perform signatory actions until a clearance period is activated.
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!$GLOBALS['hasFacultySignatoryAccess']): ?>
                            <div class="alert alert-info" style="margin-top: 10px;">
                                <i class="fas fa-info-circle"></i>
                                <strong>View-Only Access:</strong> You can view faculty data but are not currently assigned as a faculty signatory for your department(s).
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($GLOBALS['canPerformSignatoryActions']): ?>
                            <div class="alert alert-success" style="margin-top: 10px;">
                                <i class="fas fa-check-circle"></i>
                                <strong>Signatory Actions Available:</strong> You can approve and reject faculty clearance requests.
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalFaculty">--</h3>
                                    <p>Total Faculty</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeFaculty">--</h3>
                                    <p>Active</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveFaculty">--</h3>
                                    <p>Inactive</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="resignedFaculty">--</h3>
                                    <p>Resigned</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Section -->
                        <div class="quick-actions-section">
                            <div class="action-buttons">
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

                        <!-- Tab Banner Wrapper -->
                        <div class="tab-banner-wrapper">
                            <!-- Tab Navigation for quick status views -->
                            <div class="tab-nav" id="facultyTabNav">
                                <button class="tab-pill active" data-status="" onclick="switchFacultyTab(this)">
                                    Overall
                                </button>
                                <button class="tab-pill" data-status="active" onclick="switchFacultyTab(this)">
                                    Active
                                </button>
                                <button class="tab-pill" data-status="inactive" onclick="switchFacultyTab(this)">
                                    Inactive
                                </button>
                                <button class="tab-pill" data-status="resigned" onclick="switchFacultyTab(this)">
                                    Resigned
                                </button>
                            </div>
                            <!-- Mobile dropdown alternative -->
                            <div class="tab-nav-mobile" id="facultyTabSelectWrapper">
                                <select id="facultyTabSelect" class="tab-select" onchange="handleTabSelectChange(this)">
                                    <option value="" selected>Overall</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="resigned">Resigned</option>
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
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search faculty by name or ID...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Employment Status Filter -->
                                <select id="employmentStatusFilter" class="filter-select">
                                    <option value="">All Employment Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">Loading Terms...</option>
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

                        <!-- Faculty Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <div class="selection-counter-pill" onclick="clearAllSelectionsAndFilters()" id="selectionCounterPill">
                                        <span id="selectionCounter">0 selected</span>
                                        <i class="fas fa-times" id="clearSelectionIcon"></i>
                                    </div>
                                    <button class="btn btn-outline-secondary clear-selection-btn" onclick="clearAllSelections()" id="clearSelectionBtn" disabled>
                                        <i class="fas fa-times"></i> Clear All Selection
                                    </button>
                                    <div class="bulk-buttons">
                                        <button class="btn btn-success" onclick="approveSelected()" disabled>
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" onclick="rejectSelected()" disabled>
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                        
                                        <button class="btn btn-outline-warning" onclick="resetClearanceForNewTerm()" disabled>
                                            <i class="fas fa-redo"></i> Reset Clearance
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
                                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this.checked)" title="Select all visible">
                                                </th>
                                                <th>Employee Number</th>
                                                <th>Name</th>
                                                <th>Employment Status</th>
                                                <th>Account Status</th>
                                                <th>Clearance Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="facultyTableBody">
                                            <!-- Faculty data will be loaded dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination Section -->
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
    
    <!-- Include Modals -->
    <?php include '../../Modals/EditFacultyModal.php'; ?>
    <?php include '../../Modals/ClearanceProgressModal.php'; ?>
    
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
                                <input type="checkbox" id="filterApplied" value="applied">
                                <span class="checkmark"></span>
                                with "applied"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterInProgress" value="in-progress">
                                <span class="checkmark"></span>
                                with "in progress"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterComplete" value="complete">
                                <span class="checkmark"></span>
                                with "complete"
                            </label>
                        </div>
                    </div>
                    
                    <!-- Clearance Status Section (Signatory Perspective) -->
                    <div class="form-group">
                        <label class="filter-section-label">Clearance Status:</label>
                        <div class="checkbox-group">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterPending" value="pending">
                                <span class="checkmark"></span>
                                with "pending" (for my approval)
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterApproved" value="approved">
                                <span class="checkmark"></span>
                                with "approved" (by me)
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterRejected" value="rejected">
                                <span class="checkmark"></span>
                                with "rejected" (by me)
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

    <!-- Rejection Remarks Modal -->
    <div id="rejectionRemarksModal" class="modal-overlay" style="display: none;">
        <div class="modal-window rejection-remarks-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-comment-slash"></i> Rejection Remarks</h3>
                <button class="modal-close" onclick="closeRejectionRemarksModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content-area">
                <div class="rejection-info">
                    <h4 id="rejectionTargetName">Rejecting: [Faculty Name]</h4>
                    <p class="rejection-type">Type: <span id="rejectionType">Faculty</span></p>
                </div>
                
                <div class="remarks-section">
                    <div class="form-group">
                        <label for="rejectionReason">Reason for Rejection:</label>
                        <select id="rejectionReason" class="form-control" onchange="handleReasonChange()"><option value="">Loading reasons...</option></select>
                    </div>
                    
                    <div class="form-group">
                        <label for="additionalRemarks">Additional Remarks (Optional):</label>
                        <textarea id="additionalRemarks" class="form-control" rows="4" 
                                placeholder="Provide additional details or specific instructions..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-action-secondary" onclick="closeRejectionRemarksModal()">Cancel</button>
                <button class="modal-action-primary" onclick="submitRejection()">Reject Clearance</button>
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

    <script src="../../assets/js/activity-tracker.js"></script>
    <script src="../../assets/js/clearance-button-manager.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <script>
        let currentPage = 1;
        let entriesPerPage = 20;
        let currentSearch = '';
        let totalEntries = 0;
        let currentTabStatus = '';
        let CURRENT_STAFF_POSITION = '<?php echo isset($_SESSION['position']) ? addslashes($_SESSION['position']) : 'School Administrator'; ?>';
        let canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar'); // or '.sidebar'
            const mainContent = document.querySelector('.dashboard-main'); // or '.main-content'
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

        // Select all functionality
        function toggleSelectAll(checked) {
            const facultyCheckboxes = document.querySelectorAll('#facultyTableBody .faculty-checkbox');
            facultyCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row && row.style.display !== 'none' && !checkbox.disabled) {
                    checkbox.checked = checked;
                }
            });
            updateBulkButtons();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const counterPill = document.getElementById('selectionCounterPill');
            const counterSpan = counterPill.querySelector('span');

            if (selectedCount === 0) {
                counterSpan.textContent = '0 selected';
                counterPill.style.display = 'none';
            } else {
                counterSpan.textContent = `${selectedCount} selected`;
                counterPill.style.display = 'flex';
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button:not([onclick*="undo"])');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const totalCheckboxes = document.querySelectorAll('.faculty-checkbox').length;

            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
            });

            if (selectAllCheckbox) {
                if (checkedBoxes.length > 0 && checkedBoxes.length === totalCheckboxes) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else if (checkedBoxes.length > 0) {
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            }
            updateSelectionCounter();
        }

        // Bulk Actions with Confirmation - School Administrator as Signatory
        async function approveSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to approve clearance', 'warning');
                return;
            }

            showConfirmationModal(
                'Approve Faculty Clearances',
                `Are you sure you want to approve clearance for ${selectedCount} selected faculty?`,
                'Approve',
                'Cancel',
                async () => {
                    const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
                    const userIds = [];
                    for (const checkbox of selectedCheckboxes) {
                        const eid = checkbox.getAttribute('data-id');
                        const uid = await resolveUserIdFromEmployeeNumber(eid);
                        if (uid) userIds.push(uid);
                    }

                    if (userIds.length === 0) {
                        showToastNotification('Could not identify users to approve.', 'error');
                        return;
                    }

                    try {
                        const response = await fetch('../../api/clearance/bulk_signatory_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                applicant_user_ids: userIds,
                                action: 'Approved',
                                designation_name: CURRENT_STAFF_POSITION,
                                remarks: `Approved by ${CURRENT_STAFF_POSITION}`
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            showToastNotification(`Successfully approved clearance for ${result.affected_rows} faculty.`, 'success');
                        } else {
                            throw new Error(result.message || 'Bulk approval failed.');
                        }
                    } catch (error) {
                        console.error('Bulk approval error:', error);
                        showToastNotification(error.message, 'error');
                    } finally {
                        fetchFaculty();
                    }
                },
                'success'
            );
        }

        function rejectSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to reject clearance', 'warning');
                return;
            }
            const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-id'));
            openRejectionRemarksModal(null, null, 'faculty', true, selectedIds);
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
                    updateBulkStatistics('resigned', selectedCount);
                    showToastNotification(`✓ Successfully marked ${selectedCount} faculty as Resigned`, 'success');
                },
                'info'
            );
        }

        // Reset clearance status for new term
        function resetClearanceForNewTerm() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to reset clearance status', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Reset Clearance Status',
                `Are you sure you want to reset clearance status to "Unapplied" for ${selectedCount} selected faculty? This will reset their clearance progress for the new term.`,
                'Reset Clearance',
                'Cancel',
                () => {
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Unapplied';
                            clearanceBadge.classList.remove('clearance-pending', 'clearance-completed', 'clearance-rejected', 'clearance-in-progress');
                            clearanceBadge.classList.add('clearance-unapplied');
                        }
                    });
                    showToastNotification(`✓ Successfully reset clearance status for ${selectedCount} faculty`, 'success');
                },
                'warning'
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
            if (action === 'resigned') {
                currentResigned += count;
                currentActive -= count;
            }
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
            resignedCount.textContent = currentResigned.toLocaleString();
        }

        // Individual faculty actions - School Administrator as Signatory

        async function approveFacultyClearance(employeeId) {
            const row = document.querySelector(`.faculty-checkbox[data-id="${employeeId}"]`).closest('tr');
            const facultyUserId = row.getAttribute('data-faculty-id');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-rejected');
            
            if (!clearanceBadge) {
                showToastNotification('Invalid clearance status to approve', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Approve Faculty Clearance',
                `Are you sure you want to approve clearance for ${facultyName}?`,
                'Approve',
                'Cancel',
                async () => {
                    const result = await sendSignatoryAction(facultyUserId, 'Approved');
                    if (result.success) {
                        showToastNotification('Faculty clearance approved successfully', 'success');
                        fetchFaculty(); // Refresh data
                    } else {
                        showToastNotification('Failed to approve: ' + (result.message || 'Unknown error'), 'error');
                    }
                },
                'success'
            );
        }

        async function rejectFacultyClearance(employeeId) {
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
                return;
            }

            const row = document.querySelector(`.faculty-checkbox[data-id="${employeeId}"]`).closest('tr');
            const facultyUserId = row.getAttribute('data-faculty-id');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-rejected, .status-badge.clearance-pending');

            if (!clearanceBadge) {
                showToastNotification('Invalid clearance status to reject', 'warning');
                return;
            }

            let existingRemarks = '';
            let existingReasonId = '';
            const signatoryId = row.getAttribute('data-signatory-id');

            try {
                const response = await fetch(`../../api/clearance/rejection_reasons.php?signatory_id=${signatoryId}`, { credentials: 'include' });
                const data = await response.json();
                if (data.success && data.details) {
                    existingRemarks = data.details.additional_remarks || '';
                    existingReasonId = data.details.reason_id || '';
                }
            } catch (error) {
                console.error("Error fetching rejection details:", error);
                showToastNotification('Could not load existing rejection details.', 'error');
            }
        
            console.log('Opening rejection modal for', facultyName);
            console.log('Existing reason ID:', existingReasonId);
            console.log('Existing remarks:', existingRemarks);
            
            openRejectionRemarksModal(facultyUserId, facultyName, 'faculty', false, [], existingRemarks, existingReasonId);
        }

        // Filter functions
        function applyFilters() {
            currentPage = 1;
            fetchFaculty();

            showToastNotification(`Filters applied. Fetching updated data...`, 'info');
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('employmentStatusFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            applyFilters();
            showToastNotification('All filters cleared', 'info');
        }

        // Pagination variables
        let filteredEntries = [];

        // Initialize pagination
        function initializePagination() {
            updatePagination();
        }

        // Update pagination display
        function updatePagination() {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            const startEntry = (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, filteredEntries.length);
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${filteredEntries.length} entries`;
            updatePageNumbers(totalPages);
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
            showCurrentPageEntries();
        }

        // Update page number buttons
        function updatePageNumbers(totalPages) {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';
            
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    addPageButton(i, i === currentPage);
                }
            } else {
                if (currentPage <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        addPageButton(i, i === currentPage);
                    }
                    addEllipsis();
                    addPageButton(totalPages, false);
                } else if (currentPage >= totalPages - 3) {
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        addPageButton(i, i === currentPage);
                    }
                } else {
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
            fetchFaculty();
        }

        // Change page (previous/next)
        function changePage(direction) {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }
            fetchFaculty();
        }

        // Change entries per page
        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1; // Reset to first page
            fetchFaculty();
        }

        // Show current page entries
        function showCurrentPageEntries() {
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + entriesPerPage;
            filteredEntries.forEach(row => {
                row.style.display = 'none';
            });
            for (let i = startIndex; i < endIndex && i < filteredEntries.length; i++) {
                filteredEntries[i].style.display = '';
            }
        }

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
        function triggerExportModal() {
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else {
                console.error('Export modal function not found');
                showToastNotification('Export modal not available', 'error');
            }
        }

        function escapeHtml(unsafe) {
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }


        // Fetch faculty list from backend and build table body
        async function fetchFaculty() {
            const tableBody = document.getElementById('facultyTableBody');
            tableBody.innerHTML = `<tr><td colspan="7" class="loading-row"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><span>Loading faculty data...</span></div></td></tr>`;

            const accountStatus = document.getElementById('accountStatusFilter').value;
            const employmentStatus = document.getElementById('employmentStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            const search = document.getElementById('searchInput').value.trim();
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;

            const url = new URL('../../api/clearance/signatoryList.php', window.location.href);
            url.searchParams.append('type', 'faculty'); 
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            if (search) url.searchParams.append('search', search);
            if (employmentStatus) url.searchParams.append('employment_status', employmentStatus);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);


            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                if (!data.success) {
                    showEmptyState('Error: ' + data.message);
                    return;
                }

                populateFacultyTable(data.faculty);
                updatePaginationUI(data.total, data.page, data.limit);
                updateStatistics(data.stats);

            } catch (error) {
                showEmptyState('A network error occurred.');
                console.error("Fetch error:", error);
            }
        }

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

        function createFacultyRow(faculty) {
            const tr = document.createElement('tr');
            tr.setAttribute('data-faculty-id', faculty.user_id);
            tr.setAttribute('data-signatory-id', faculty.signatory_id);
            
            const statusRaw = faculty.clearance_status;
            const clearanceKey = (statusRaw || 'unapplied').toLowerCase().replace(/ /g, '-');
            const accountStatus = (faculty.account_status || 'inactive').toLowerCase();
            
            let approveBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(faculty.clearance_status);
            // Enable reject button for 'Pending' and 'Rejected' statuses to allow for edits.
            let rejectBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(faculty.clearance_status);
            let checkboxDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(faculty.clearance_status);
            let approveTitle = 'Approve Clearance';
            // Change button title if the faculty member is already rejected.
            let rejectTitle = faculty.clearance_status === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Clearance';

            if (!canPerformActions) {
                approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a faculty signatory."; ?>';
            }
            
            tr.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="faculty-checkbox" data-id="${faculty.id}"  onchange="updateBulkButtons()" ${checkboxDisabled ? 'disabled' : ''}></td>
                <td data-label="Employee Number:">${faculty.id}</td>
                <td data-label="Name:">${escapeHtml(faculty.name)}</td>
                <td data-label="Employment Status:"><span class="status-badge employment-${(faculty.employment_status || '').toLowerCase().replace(/ /g, '-')}">${escapeHtml(faculty.employment_status || 'N/A')}</span></td>
                <td data-label="Account Status:"><span class="status-badge account-${accountStatus}">${faculty.account_status || 'N/A'}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge clearance-${clearanceKey}">${faculty.clearance_status || 'N/A'}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('${faculty.id}')" title="View Clearance Progress"><i class="fas fa-tasks"></i></button>
                        <button class="btn-icon approve-btn" onclick="approveFacultyClearance('${faculty.id}')" title="${approveTitle}" ${approveBtnDisabled ? 'disabled' : ''}>
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon reject-btn" onclick="rejectFacultyClearance('${faculty.id}')" title="${rejectTitle}" ${rejectBtnDisabled ? 'disabled' : ''}>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            `;
            
            if (accountStatus !== 'active') {
                tr.classList.add('row-disabled');
            }
            
            return tr;
        }

        function populateFacultyTable(facultyList) {
            const tbody = document.getElementById('facultyTableBody');
            tbody.innerHTML = '';

            if (!facultyList || facultyList.length === 0) {
                showEmptyState('No faculty data found matching your criteria.');
                return;
            }

            facultyList.forEach(faculty => {
                const row = createFacultyRow(faculty);
                tbody.appendChild(row);
            });
        }

        function showEmptyState(message) {
            const tbody = document.getElementById('facultyTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <div>${message}</div>
                    </td>
                </tr> 
            `;
            updatePaginationUI(0, 1, entriesPerPage);
            updateStatistics({});
        }

        function updateStatistics(stats) {
            document.getElementById('totalFaculty').textContent = stats.total || 0;
            document.getElementById('activeFaculty').textContent = stats.active || 0;
            document.getElementById('inactiveFaculty').textContent = stats.inactive || 0;
            document.getElementById('resignedFaculty').textContent = stats.resigned || 0;
        }

        // Tab navigation functions
        function switchFacultyTab(button) {
            const status = button.getAttribute('data-status');
            currentTabStatus = status;
            
            // Update tab appearance
            document.querySelectorAll('#facultyTabNav .tab-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            button.classList.add('active');
            
            // Update mobile select
            const mobileSelect = document.getElementById('facultyTabSelect');
            if (mobileSelect) mobileSelect.value = status;
            
            // Reset main filters and fetch data for the tab
            document.getElementById('accountStatusFilter').value = '';
            currentPage = 1;
            fetchFaculty();
        }

        function handleTabSelectChange(select) {
            const status = select.value;
            currentTabStatus = status;
            
            // Update tab pills
            document.querySelectorAll('#facultyTabNav .tab-pill').forEach(pill => {
                pill.classList.toggle('active', pill.getAttribute('data-status') === status);
            });
            
            document.getElementById('accountStatusFilter').value = '';
            currentPage = 1;
            fetchFaculty();
        }

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
        }

        function resetBulkSelectionFilters() {
            const checkboxes = ['filterFullTime', 'filterPartTime', 'filterPartTimeFullLoad', 'filterActive', 'filterInactive', 'filterResigned', 'filterUnapplied', 'filterApplied', 'filterInProgress', 'filterComplete', 'filterPending', 'filterApproved', 'filterRejected'];
            checkboxes.forEach(id => {
                const element = document.getElementById(id);
                if (element) element.checked = false;
            });
        }

        function applyBulkSelection() {
            const filters = {
                fullTime: document.getElementById('filterFullTime')?.checked || false,
                partTime: document.getElementById('filterPartTime')?.checked || false,
                partTimeFullLoad: document.getElementById('filterPartTimeFullLoad')?.checked || false,
                active: document.getElementById('filterActive')?.checked || false,
                inactive: document.getElementById('filterInactive')?.checked || false,
                resigned: document.getElementById('filterResigned')?.checked || false,
                unapplied: document.getElementById('filterUnapplied')?.checked || false,
                applied: document.getElementById('filterApplied')?.checked || false,
                inProgress: document.getElementById('filterInProgress')?.checked || false,
                complete: document.getElementById('filterComplete')?.checked || false,
                pending: document.getElementById('filterPending')?.checked || false,
                approved: document.getElementById('filterApproved')?.checked || false,
                rejected: document.getElementById('filterRejected')?.checked || false
            };
            
            // Check if any filter is selected
            const anyFilterChecked = Object.values(filters).some(val => val === true);
            
            if (!anyFilterChecked) {
                // No filters checked - select all visible rows
                selectAllVisibleFacultyRows();
            } else {
                // Filters are checked - select only matching rows
                selectFacultyByFilters(filters);
            }
            
            closeBulkSelectionModal();
        }
        
        function selectAllVisibleFacultyRows() {
            const checkboxes = document.querySelectorAll('.faculty-checkbox');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                // Only select if row is visible (respects current table filters/search)
                if (row && row.style.display !== 'none') {
                    checkbox.checked = true;
                    selectedCount++;
                }
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            showToastNotification(`Selected all ${selectedCount} visible faculty`, 'success');
        }

        function selectFacultyByFilters(filters) {
            const checkboxes = document.querySelectorAll('.faculty-checkbox');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                
                // Skip hidden rows (respects table filters)
                if (!row || row.style.display === 'none') {
                    checkbox.checked = false;
                    return;
                }
                
                const employmentBadge = row.querySelector('.status-badge[class*="employment-"]');
                const accountBadge = row.querySelector('.status-badge[class*="account-"]');
                const clearanceBadge = row.querySelector('.status-badge[class*="clearance-"]');
                
                let employmentMatch = false;
                let accountMatch = false;
                let progressMatch = false;
                let statusMatch = false;
                
                // Check employment status filters
                const hasEmploymentFilter = filters.fullTime || filters.partTime || filters.partTimeFullLoad;
                if (hasEmploymentFilter && employmentBadge) {
                    if (filters.fullTime && employmentBadge.classList.contains('employment-full-time')) employmentMatch = true;
                    if (filters.partTime && employmentBadge.classList.contains('employment-part-time')) employmentMatch = true;
                    if (filters.partTimeFullLoad && employmentBadge.classList.contains('employment-part-time-full-load')) employmentMatch = true;
                } else if (!hasEmploymentFilter) {
                    employmentMatch = true; // No employment filter = wildcard
                }
                
                // Check account status filters
                const hasAccountFilter = filters.active || filters.inactive || filters.resigned;
                if (hasAccountFilter && accountBadge) {
                    if (filters.active && accountBadge.classList.contains('account-active')) accountMatch = true;
                    if (filters.inactive && accountBadge.classList.contains('account-inactive')) accountMatch = true;
                    if (filters.resigned && accountBadge.classList.contains('account-resigned')) accountMatch = true;
                } else if (!hasAccountFilter) {
                    accountMatch = true; // No account filter = wildcard
                }
                
                // Check clearance progress filters
                const hasProgressFilter = filters.unapplied || filters.applied || filters.inProgress || filters.complete;
                if (hasProgressFilter && clearanceBadge) {
                    if (filters.unapplied && clearanceBadge.classList.contains('clearance-unapplied')) progressMatch = true;
                    if (filters.applied && clearanceBadge.classList.contains('clearance-applied')) progressMatch = true;
                    if (filters.inProgress && clearanceBadge.classList.contains('clearance-in-progress')) progressMatch = true;
                    if (filters.complete && clearanceBadge.classList.contains('clearance-complete')) progressMatch = true;
                } else if (!hasProgressFilter) {
                    progressMatch = true; // No progress filter = wildcard
                }
                
                // Check clearance status filters (signatory perspective)
                const hasStatusFilter = filters.pending || filters.approved || filters.rejected;
                if (hasStatusFilter && clearanceBadge) {
                    if (filters.pending && clearanceBadge.classList.contains('clearance-pending')) statusMatch = true;
                    if (filters.approved && clearanceBadge.classList.contains('clearance-approved')) statusMatch = true;
                    if (filters.rejected && clearanceBadge.classList.contains('clearance-rejected')) statusMatch = true;
                } else if (!hasStatusFilter) {
                    statusMatch = true; // No status filter = wildcard
                }
                
                // Select if all filter categories match
                const shouldSelect = employmentMatch && accountMatch && progressMatch && statusMatch;
                checkbox.checked = shouldSelect;
                if (shouldSelect) selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            showToastNotification(`Selected ${selectedCount} faculty based on filters`, 'success');
        }

        function clearAllSelections() {
            document.querySelectorAll('.faculty-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateBulkButtons();
        }

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

        async function loadEmploymentStatuses() {
            const select = document.getElementById('employmentStatusFilter');
            select.innerHTML = `<option value="">Loading Employment Statuses...</option>`;
            const url = new URL('../../api/clearance/get_filter_options.php', window.location.href);
            url.searchParams.append('type', 'employment_statuses');
            await populateFilter('employmentStatusFilter', url.toString(), 'All Employment Statuses');
        }

        async function loadClearanceStatuses() {
            const select = document.getElementById('clearanceStatusFilter');
            select.innerHTML = `<option value="">Loading Clearance Statuses...</option>`;
            const url = new URL('../../api/clearance/get_filter_options.php', window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'clearance_signatories');
            url.searchParams.append('column', 'action');
            await populateFilter('clearanceStatusFilter', url.toString(), 'All Clearance Statuses');
        }

        async function loadAccountStatuses() {
            const accountStatus = document.getElementById('accountStatusFilter');
            accountStatus.innerHTML = '<option value="">Loading Account Statuses...</option>';
            const url = new URL('../../api/clearance/get_filter_options.php', window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'users');
            url.searchParams.append('column', 'account_status');
            url.searchParams.append('exclude', 'graduated');
            await populateFilter('accountStatusFilter', url.toString(), 'All Account Statuses');
        }

        
        async function setDefaultSchoolTerm() {
            try {
                const response = await fetch('../../api/clearance/periods.php', { credentials: 'include' });
                const data = await response.json();
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    // Find the active period specifically for the 'College' sector
                    const activeCollegePeriod = data.active_periods.find(p => p.sector === 'College');

                    if (activeCollegePeriod) {
                        const schoolTermFilter = document.getElementById('schoolTermFilter');
                        // The value format for the filter is 'YYYY-YYYY|period_id'
                        const termValue = `${activeCollegePeriod.school_year}|${activeCollegePeriod.semester_id}`;
                        // Check if the option exists before setting it
                        if (schoolTermFilter.querySelector(`option[value="${termValue}"]`)) {
                            schoolTermFilter.value = termValue;
                            console.log('Default school term set to:', termValue);
                        } else {
                            console.warn('Default school term option not found in filter:', termValue);
                        }
                    }
                }
            } catch (error) {
                console.error('Error setting default school term:', error);
            }
        }

        // Load current clearance period for banner
        async function loadCurrentPeriod() {
            try {
                const response = await fetch('../../api/clearance/periods.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                const yearEl = document.getElementById('currentAcademicYear');
                const semesterEl = document.getElementById('currentSemester');
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    const period = data.active_periods[0];
                    const termMap = {
                        '1st': '1st Semester',
                        '2nd': '2nd Semester',
                        '3rd': '3rd Semester',
                        '1st Semester': '1st Semester',
                        '2nd Semester': '2nd Semester',
                        '3rd Semester': '3rd Semester',
                        'Summer': 'Summer'
                    };
                    const semLabel = termMap[period.semester_name] || period.semester_name || '';
                    if (yearEl) yearEl.textContent = period.school_year;
                    if (semesterEl) semesterEl.textContent = semLabel;
                } else {
                    if (yearEl) yearEl.textContent = 'No active period';
                    if (semesterEl) semesterEl.textContent = 'No term';
                }
            } catch (error) {
                console.error('Error loading current period:', error);
                const yearEl = document.getElementById('currentAcademicYear');
                const semesterEl = document.getElementById('currentSemester');
                if (yearEl) yearEl.textContent = 'Error loading';
                if (semesterEl) semesterEl.textContent = 'Error';
            }
        }


        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {

            await Promise.all ([
                loadRejectionReasons(),
                loadSchoolTermsFilter(),
                loadEmploymentStatuses(),
                loadClearanceStatuses(),
                loadAccountStatuses(),
                loadCurrentPeriod()
            ]);

            await setDefaultSchoolTerm();
            fetchFaculty();

            document.getElementById('facultyTableBody').addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                }
            });

            // Add event listener for search input (Enter key)
            document.getElementById('searchInput').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    applyFilters();
                }
            });
        });

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
            document.querySelector('.dashboard-main').style.display = 'none';
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
                    document.querySelector('.dashboard-main').style.display = 'block';
                    
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
                        <span class="status-badge clearance-pending">Pending</span>
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
                <div class="preview-item">Dr. Maria Santos - ICT (Pending)</div>
                <div class="preview-item">Prof. Juan Dela Cruz - Business (In Progress)</div>
                <div class="preview-item">Dr. Ana Rodriguez - Engineering (Pending)</div>
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
            const approvedCount = document.querySelectorAll('.status-badge.clearance-completed').length;
            const rejectedCount = document.querySelectorAll('.status-badge.clearance-rejected').length;
            
            document.getElementById('overrideStats').textContent = 
                `Selected: ${selectedCount} | Approved: ${approvedCount} | Rejected: ${rejectedCount}`;
        }

        function approveOverrideClearance(id) {
            const clearanceItem = document.querySelector(`.override-checkbox[data-id="${id}"]`).closest('.clearance-item');
            const statusBadge = clearanceItem.querySelector('.status-badge');
            
            statusBadge.textContent = 'Completed';
            statusBadge.classList.remove('clearance-pending', 'clearance-in-progress');
            statusBadge.classList.add('clearance-completed');
            
            showToastNotification('Clearance approved successfully', 'success');
            updateOverrideStats();
        }

        function rejectOverrideClearance(id) {
            const clearanceItem = document.querySelector(`.override-checkbox[data-id="${id}"]`).closest('.clearance-item');
            const facultyName = clearanceItem.querySelector('h4').textContent;
            
            // Open rejection remarks modal for override rejection
            openRejectionRemarksModal(id, facultyName, 'faculty', false);
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
            openRejectionRemarksModal(null, null, 'faculty', true, selectedIds);
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

        function exportOverrideReport() {
            showToastNotification('Override report export functionality will be implemented', 'info');
        }

        // New function for viewing clearance progress
        function viewClearanceProgress(facultyId) {
            // Get faculty name from the table row
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            // Open the clearance progress modal
            openClearanceProgressModal(facultyId, 'faculty', facultyName, schoolTerm);
        }

        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            targetId: null,
            targetName: null,
            targetType: 'faculty',
            isBulk: false,
            targetIds: []
        };

        function openRejectionRemarksModal(targetId, targetName, targetType = 'faculty', isBulk = false, targetIds = [], existingRemarks = '', existingReasonId = '') {
            currentRejectionData = {
                targetId: targetId,
                targetName: targetName,
                targetType: targetType,
                isBulk: isBulk,
                targetIds: targetIds,
                existingRemarks: existingRemarks,
                existingReasonId: existingReasonId
            };

            // Update modal content based on target type
            const modal = document.getElementById('rejectionRemarksModal');
            const targetNameElement = document.getElementById('rejectionTargetName');
            const targetTypeElement = document.getElementById('rejectionType');
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');

            // Reset form
            reasonSelect.value = existingReasonId || '';
            remarksTextarea.value = existingRemarks || '';

            // Update display
            if (isBulk) {
                targetNameElement.textContent = `Rejecting: ${targetIds.length} Selected ${targetType === 'student' ? 'Students' : 'Faculty'}`;
            } else {
                targetNameElement.textContent = `Rejecting: ${targetName}`;
            }
            targetTypeElement.textContent = targetType === 'student' ? 'Student' : 'Faculty';

            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeRejectionRemarksModal() {
            const modal = document.getElementById('rejectionRemarksModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Reset current rejection data
            currentRejectionData = {
                targetId: null,
                targetName: null,
                targetType: 'faculty',
                isBulk: false,
                targetIds: []
            };
        }

        function handleReasonChange() {
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');
            
            // If "Other" is selected, focus on remarks textarea
            if (reasonSelect.value === 'other') {
                remarksTextarea.focus();
                remarksTextarea.placeholder = 'Please specify the reason for rejection...';
            } else {
                remarksTextarea.placeholder = 'Provide additional details or specific instructions...';
            }
        }

        async function submitRejection() {
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');
            
            // Get rejection data
            const rejectionReason = reasonSelect.value;
            const additionalRemarks = remarksTextarea.value.trim();
            
            // Demo: Show rejection summary
            let rejectionSummary = '';
            if (currentRejectionData.isBulk) {
                rejectionSummary = `Rejected ${currentRejectionData.targetIds.length} ${currentRejectionData.targetType === 'student' ? 'students' : 'faculty'}`;
            } else {
                rejectionSummary = `Rejected ${currentRejectionData.targetName}`;
            }
            
            if (rejectionReason) {
                const reasonText = reasonSelect.options[reasonSelect.selectedIndex].text;
                rejectionSummary += `\nReason: ${reasonText}`;
            }
            
            if (additionalRemarks) {
                rejectionSummary += `\nAdditional Remarks: ${additionalRemarks}`;
            }
            
            // Demo: Update UI and show success message
            if (currentRejectionData.isBulk) {
                const isOverrideMode = document.getElementById('overrideSessionInterface').style.display !== 'none';
                
                if (isOverrideMode) {
                    currentRejectionData.targetIds.forEach(id => {
                        const clearanceItem = document.querySelector(`.override-checkbox[data-id="${id}"]`);
                        if (clearanceItem) {
                            const clearanceItemRow = clearanceItem.closest('.clearance-item');
                            if (clearanceItemRow) {
                                const statusBadge = clearanceItemRow.querySelector('.status-badge');
                                if (statusBadge) {
                                    statusBadge.textContent = 'Rejected';
                                    statusBadge.classList.remove('clearance-pending', 'clearance-in-progress');
                                    statusBadge.classList.add('clearance-rejected');
                                }
                            }
                        }
                    });
                    document.getElementById('overrideSelectAll').checked = false;
                    currentRejectionData.targetIds.forEach(id => {
                        const checkbox = document.querySelector(`.override-checkbox[data-id="${id}"]`);
                        if (checkbox) checkbox.checked = false;
                    });
                    updateOverrideBulkButtons();
                    
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} faculty with remarks`, 'success');
                } else {
                    try {
                        for (const id of currentRejectionData.targetIds) {
                            const uid = await resolveUserIdFromEmployeeNumber(id);
                            if (uid) { 
                                await sendSignatoryAction(uid, 'Rejected', additionalRemarks, rejectionReason); 
                                const row = document.querySelector(`.faculty-checkbox[data-id="${id}"]`).closest('tr');
                                const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-approved');
                                if (clearanceBadge) {
                                    clearanceBadge.textContent = 'Rejected';
                                    clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed');
                                    clearanceBadge.classList.add('clearance-rejected');
                                }
                            }
                        }
                    } catch (e) {}
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} faculty with remarks`, 'success');
                    fetchFaculty();
                }
            } else {
                try {
                    const result = await sendSignatoryAction(currentRejectionData.targetId, 'Rejected', additionalRemarks, rejectionReason);
                    if (result.success) {
                        showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                        fetchFaculty();
                    } else {
                        showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (e) {}
            }
            
            closeRejectionRemarksModal();
            console.log('Rejection Data:', {
                target: currentRejectionData,
                reason: rejectionReason,
                additionalRemarks: additionalRemarks,
                timestamp: new Date().toISOString()
            });
        }

        async function resolveUserIdFromEmployeeNumber(employeeNumber){
            try{
                const r = await fetch('../../api/users/read.php?limit=5&search=' + encodeURIComponent(employeeNumber), { credentials:'include' });
                const data = await r.json();
                const arr = data.users || [];
                const match = arr.find(u => String(u.username) === String(employeeNumber));
                return match ? match.user_id : null;
            }catch(e){ return null; }
        }
        async function sendSignatoryAction(applicantUserId, action, remarks, reasonId = null){
            const payload = { 
                applicant_user_id: applicantUserId, 
                action: action,
                designation_name: CURRENT_STAFF_POSITION
            };
            if (remarks && remarks.length) payload.remarks = remarks;
            if (reasonId) payload.reason_id = reasonId;

            const response = await fetch('../../api/clearance/signatory_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)
            });
            return await response.json();
        }

        let searchTimeout;
        function debouncedSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = document.getElementById('searchInput').value;
                currentPage = 1;
                fetchFaculty();
            }, 300);
        }

        async function loadRejectionReasons() {
            const reasonSelect = document.getElementById('rejectionReason');
            if (!reasonSelect) return;

            try {
                const response = await fetch('../../api/clearance/rejection_reasons.php?category=faculty', { credentials: 'include' });
                const data = await response.json();

                reasonSelect.innerHTML = '<option value="">Select a reason...</option>';
                if (data.success && data.rejection_reasons) {
                    data.rejection_reasons.forEach(reason => {
                        const option = document.createElement('option');
                        option.value = reason.reason_id;
                        option.textContent = reason.reason_name;
                        reasonSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading rejection reasons:', error);
            }
        }

        async function loadSchoolTermsFilter() {
            const termSelect = document.getElementById('schoolTermFilter');
            try {
                const response = await fetch('../../api/clearance/periods.php', { credentials: 'include' });
                const data = await response.json();

                termSelect.innerHTML = '<option value="">All School Terms</option>';
                if (data.success && data.periods) {
                    const uniqueTerms = [...new Map(data.periods.map(item => [`${item.academic_year}-${item.semester_name}`, item])).values()];
                    
                    uniqueTerms.forEach(period => {
                        const option = document.createElement('option');
                        option.value = `${period.academic_year}|${period.semester_id}`; // Use a format the backend can parse
                        option.textContent = `${period.academic_year} - ${period.semester_name}`;
                        termSelect.appendChild(option);
                    });
                }
            } catch (error) { console.error('Error loading school terms:', error); }
        }
    </script>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
    
    <script src="../../assets/js/alerts.js"></script>
</body>
</html>
