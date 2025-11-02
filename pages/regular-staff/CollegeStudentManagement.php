<?php
// Online Clearance Website - Regular Staff College Student Management

// Include necessary files for authentication and database connection
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../pages/auth/login.php');
    exit;
}
$userId = (int)$auth->getUserId();

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Get the staff member's designation ID
    $staffDesignationStmt = $pdo->prepare("SELECT designation_id FROM staff WHERE user_id = ? AND is_active = 1");
    $staffDesignationStmt->execute([$userId]);
    $designationId = $staffDesignationStmt->fetchColumn();

    // Check permission flags (for conditional UI behavior)
    $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'College'")->fetchColumn() > 0;

    $hasStudentSignatoryAccess = false;
    if ($designationId) {
        // 2. Check if this designation is assigned to sign for 'College' students
        $studentSignatoryCheck = $pdo->prepare("SELECT COUNT(*) FROM sector_signatory_assignments WHERE designation_id = ? AND clearance_type = 'College' AND is_active = 1");
        $studentSignatoryCheck->execute([$designationId]);
        $hasStudentSignatoryAccess = (int)$studentSignatoryCheck->fetchColumn() > 0;
    }
    
    $canPerformSignatoryActions = $hasActivePeriod && $hasStudentSignatoryAccess;

    // Store permission flags for use in the page
    $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
    $GLOBALS['hasStudentSignatoryAccess'] = $hasStudentSignatoryAccess;
    $GLOBALS['canPerformSignatoryActions'] = $canPerformSignatoryActions;
    
} catch (Throwable $e) {
    header('HTTP/1.1 500 Internal Server Error'); 
    echo 'System error. Please try again later.'; 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>College Student Management - Staff Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Selection Counter Display Styles (matching admin's page) */
        .selection-counter-display {
            display: flex;
            align-items: center;
            margin-left: 15px;
            margin-right: 20px;
            padding: 8px 20px;
            background-color: var(--very-light-cool-white, #e7eff4);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            color: var(--dark-primary, #1c3faa);
            min-width: 180px;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(231, 239, 244, 0.3);
            transition: all 0.2s ease;
        }
        
        .selection-counter-display span {
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        
        /* Hover effect for subtle interactivity */
        .selection-counter-display:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(231, 239, 244, 0.4);
        }
        
        /* State-based styling */
        .selection-counter-display.has-selections {
            background-color: var(--darker-saturated-blue, #175d97);
            box-shadow: 0 2px 4px rgba(23, 93, 151, 0.2);
            cursor: pointer;
        }
        
        .selection-counter-display.has-selections:hover {
            box-shadow: 0 3px 8px rgba(23, 93, 151, 0.25);
        }
        
        .selection-counter-display.all-selected {
            background-color: var(--bright-golden-yellow, #e7c01d);
            color: var(--deep-navy-blue, #0c5591);
            box-shadow: 0 2px 4px rgba(231, 192, 29, 0.2);
            cursor: pointer;
        }
        
        .selection-counter-display.all-selected:hover {
            box-shadow: 0 3px 8px rgba(231, 192, 29, 0.25);
        }
        
        /* Select All Button */
        .select-all-btn {
            margin-right: 15px;
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
                            <h2><i class="fas fa-user-graduate"></i> College Student Management</h2>
                            <p>Review and sign student clearance requests for the College sector.</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>                                
                                <span id="positionInfo">Loading position...</span>
                            </div>

                            <!-- Permission Status Alerts -->
                            <?php if (!$GLOBALS['hasActivePeriod']): ?>
                            <div class="alert alert-warning" style="margin-top: 10px;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>No Active Clearance Period for College:</strong> You can view student data but cannot perform signatory actions until a clearance period is activated for the College sector.
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!$GLOBALS['hasStudentSignatoryAccess']): ?>
                            <div class="alert alert-info" style="margin-top: 10px;">
                                <i class="fas fa-info-circle"></i>
                                <strong>View-Only Access:</strong> You can view student data but are not currently assigned as a signatory for the College sector.
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($GLOBALS['canPerformSignatoryActions']): ?>
                            <div class="alert alert-success" style="margin-top: 10px;">
                                <i class="fas fa-check-circle"></i>
                                <strong>Signatory Actions Available:</strong> You can approve and reject student clearance requests for the College sector.
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
                                    <p>Active</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveStudents">0</h3>
                                    <p>Inactive</p>
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
                                <button class="btn btn-secondary export-btn" onclick="triggerExportModal()">
                                    <i class="fas fa-file-export"></i> Export Report
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
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or program..." onchange="">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Clearance Status Filter -->
                                <!-- Add this inside onchange="applyFilters()" for selection based changes-->
                                <select id="clearanceStatusFilter" class="filter-select" >
                                    <option value="">All Clearance Status</option>
                                    <option value="">Loading...</option>
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">All School Terms</option>
                                    <option value="">Loading...</option>
                                </select>
                                
                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
                                    <option value="">Loading...</option>
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

                        <!-- Student Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <button class="selection-counter-display" id="selectionCounterPill" onclick="clearAllSelections()">
                                        <i class="fas fa-check-square"></i> <span id="selectionCounter">0 selected</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn" onclick="clearAllSelections()" disabled>
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                    <div class="bulk-buttons">
                                        <button class="btn btn-success" onclick="approveSelected()" disabled>
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" onclick="rejectSelected()" disabled>
                                            <i class="fas fa-times"></i> Reject
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
                                <div class="students-table-wrapper" id="studentTableWrapper">
                                    <table id="studentTable" class="students-table">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-column">
                                                </th>
                                                <th>Student Number</th>
                                                <th>Name</th>
                                                <th>Program</th>
                                                <th>Year Level</th>
                                                <th>Section</th>
                                                <th>Account Status</th>
                                                <th>Clearance Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody"></tbody>
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
    
    <!-- Include Modals -->
    <?php include '../../Modals/CollegeStudentRegistryModal.php'; ?>
    <?php include '../../Modals/CollegeEditStudentModal.php'; ?>
    
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
    <?php include '../../Modals/ClearanceExportModal.php'; ?>
    <?php include '../../Modals/ExportModal.php'; ?>

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
                    <h4 id="rejectionTargetName">Rejecting: [Student Name]</h4>
                    <p class="rejection-type">Type: <span id="rejectionType">Student</span></p>
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

    <script src="../../assets/js/activity-tracker.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <script>
        // --- State Management ---
        let currentPage = 1;
        let entriesPerPage = 20;
        let currentSearch = '';
        let currentFilters = {};
        const CURRENT_STAFF_POSITION = '<?php echo isset($_SESSION['position']) ? addslashes($_SESSION['position']) : 'Staff'; ?>';

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.dashboard-main');
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

        // Bulk selection modal functions
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

        function applyBulkSelection() {
            const filters = {
                active: document.getElementById('filterActive').checked,
                inactive: document.getElementById('filterInactive').checked,
                graduated: document.getElementById('filterGraduated').checked,
                pending: document.getElementById('filterPending').checked,
                approved: document.getElementById('filterApproved').checked,
                rejected: document.getElementById('filterRejected').checked
            };
            
            // Check if any filter is selected
            const anyFilterChecked = Object.values(filters).some(val => val === true);
            
            if (!anyFilterChecked) {
                // No filters checked - select all visible rows
                selectAllVisibleRows();
            } else {
                // Filters are checked - select only matching rows
                selectStudentsByFilters(filters);
            }
            
            closeBulkSelectionModal();
        }

        function selectAllVisibleRows() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                
                // Skip hidden rows (respects table filters)
                if (row.style.display === 'none') {
                    checkbox.checked = false;
                    return;
                }
                
                checkbox.checked = true;
                selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            showToastNotification(`Selected all ${selectedCount} visible students`, 'success');
        }

        function selectStudentsByFilters(filters) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                
                // Skip hidden rows (respects table filters)
                if (row.style.display === 'none') {
                    checkbox.checked = false;
                    return;
                }
                
                const accountBadge = row.querySelector('.status-badge[class*="account-"]');
                const clearanceBadge = row.querySelector('.status-badge[class*="clearance-"]');
                
                let accountMatch = false;
                let statusMatch = false;
                
                // Check account status filters
                const hasAccountFilter = filters.active || filters.inactive || filters.graduated;
                if (hasAccountFilter && accountBadge) {
                    if (filters.active && accountBadge.classList.contains('account-active')) accountMatch = true;
                    if (filters.inactive && accountBadge.classList.contains('account-inactive')) accountMatch = true;
                    if (filters.graduated && accountBadge.classList.contains('account-graduated')) accountMatch = true;
                } else if (!hasAccountFilter) {
                    accountMatch = true; // No account filter = wildcard
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
                const shouldSelect = accountMatch && statusMatch;
                checkbox.checked = shouldSelect;
                if (shouldSelect) selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            showToastNotification(`Selected ${selectedCount} students based on filters`, 'success');
        }

        function resetBulkSelectionFilters() {
            document.getElementById('filterActive').checked = false;
            document.getElementById('filterInactive').checked = false;
            document.getElementById('filterGraduated').checked = false;
            document.getElementById('filterPending').checked = false;
            document.getElementById('filterApproved').checked = false;
            document.getElementById('filterRejected').checked = false;
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.student-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
            } else if (selectedCount > 0 && selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
            } else {
                counter.textContent = `${selectedCount} selected`;
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            // Check if signatory actions are allowed from PHP
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;

            bulkButtons.forEach(button => {
                // Disable if no selections OR if signatory actions are not allowed
                button.disabled = checkedBoxes.length === 0 || !canPerformActions;

                // Add tooltip for disabled state due to permissions
                if (!canPerformActions && checkedBoxes.length > 0) {
                    button.title = 'Cannot perform action: ' + ('<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a student signatory."; ?>');
                } else if (checkedBoxes.length === 0) {
                    button.title = 'Select students to perform actions';
                } else {
                    button.title = '';
                }
            });
            
            updateSelectionCounter();
        }

        // Bulk Actions - Staff can only approve/reject clearances
        function approveSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to approve clearance', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Approve Student Clearances',
                `Are you sure you want to approve clearance for ${selectedCount} selected students?`,
                'Approve',
                'Cancel',
                async () => {
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    for (const checkbox of selectedRows) {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Completed';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                            clearanceBadge.classList.add('clearance-approved');
                        }
                        // server-side record
                        try {
                            const sid = checkbox.getAttribute('data-id'); // This is student number
                            const uid = await resolveUserIdFromStudentNumber(sid);
                            if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Approved'); }
                        } catch (e) {}
                    }
                    
                    showToastNotification(`✓ Successfully approved clearance for ${selectedCount} students`, 'success');
                },
                'success'
            );
        }

        function rejectSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to reject clearance', 'warning');
                return;
            }
            
            // Get selected student IDs
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-id'));
            
            // Open rejection remarks modal for bulk rejection
            openRejectionRemarksModal(null, null, 'student', true, selectedIds);
        }

        function getSelectedCount() {
            return document.querySelectorAll('.student-checkbox:checked').length;
        }

        // Individual student actions - Staff can only approve/reject clearances
        async function approveStudentClearance(studentId) {
            const row = document.querySelector(`.student-checkbox[data-id="${studentId}"]`).closest('tr');
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-rejected');
            
            // Check if signatory actions are allowed
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
                return;
            }

            if (!clearanceBadge) {
                showToastNotification('No clearance to approve', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Approve Student Clearance',
                `Are you sure you want to approve clearance for ${studentName}?`,
                'Approve',
                'Cancel',
                async () => {
                    try {
                        const uid = await resolveUserIdFromStudentNumber(studentId);
                        if (uid) {
                            const result = await sendSignatoryAction(uid, 'Approved', 'Approved by Staff');
                            if (result.success) {
                                showToastNotification('Student clearance approved successfully', 'success');
                                fetchStudents(); // Refresh the table to update button states
                            } else {
                                showToastNotification('Failed to approve: ' + (result.message || 'Unknown error'), 'error');
                            }
                        }
                    } catch (e) {
                        console.error("Error during individual approval:", e);
                        showToastNotification('An error occurred during approval.', 'error');
                    }
                    showToastNotification('Student clearance approved successfully', 'success');
                },
                'success'
            );
        }

        function rejectStudentClearance(studentId) {
            // Check if signatory actions are allowed
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
                return;
            }
            
            const row = document.querySelector(`.student-checkbox[data-id="${studentId}"]`).closest('tr');
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-rejected');
            
            if (!clearanceBadge) {
                showToastNotification('Invalid clearance status to reject', 'warning');
                return;
            }
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(studentId, studentName, 'student', false);
        }

        // --- Data Fetching and Rendering ---
        async function fetchStudents() {
            const tableBody = document.getElementById('studentTableBody');
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading students...</td></tr>`;

            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            const search = document.getElementById('searchInput').value;

            const url = new URL('../../api/clearance/signatoryList.php', window.location.href);

            // Base parameters
            url.searchParams.append('sector', 'College');
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            // Optional filters
            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);

            try {
                const response = await fetch(url.toString(), { credentials: 'include' });
                const data = await response.json();

                if (!data.success) {
                    tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                    return;
                }

                renderStudentTable(data.students);
                renderPagination(data.total, data.page, data.limit);
                updateStatistics(data.stats);

            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">A network error occurred.</td></tr>`;
                console.error("Fetch error:", error);
            }
        }

        function renderStudentTable(students) {
            const tableBody = document.getElementById('studentTableBody');
            if (students.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">No students found matching your criteria.</td></tr>`;
                return;
            }

            tableBody.innerHTML = students.map(student => {
                const clearanceStatusClass = `clearance-${student.clearance_status.toLowerCase().replace(' ', '-')}`;
                const accountStatusClass = `account-${(student.account_status || '').toLowerCase()}`;

                const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
                // Enable approve button for 'Pending' and 'Rejected' statuses.
                let approveBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(student.clearance_status);
                // Enable reject button for 'Pending' and 'Rejected' statuses to allow for edits.
                let rejectBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(student.clearance_status);
                let approveTitle = 'Approve Clearance';
                // Change button title if the student is already rejected.
                let rejectTitle = student.clearance_status === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Clearance';
                if (!canPerformActions) {
                    approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a student signatory."; ?>';
                }

                return `
                    <tr data-signatory-id="${student.signatory_id}">
                        <td><input type="checkbox" class="student-checkbox" data-id="${student.id}"></td>
                        <td>${student.id}</td>
                        <td>${escapeHtml(student.name)}</td>
                        <td>${escapeHtml(student.program)}</td>
                        <td>${escapeHtml(student.year_level)}</td>
                        <td>${escapeHtml(student.section)}</td>
                        <td><span class="status-badge ${accountStatusClass}">${escapeHtml(student.account_status || 'N/A')}</span></td>
                        <td><span class="status-badge ${clearanceStatusClass}">${escapeHtml(student.clearance_status || 'N/A')}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon approve-btn" onclick="approveStudentClearance('${student.id}')" title="${approveTitle}" ${approveBtnDisabled ? 'disabled' : ''}>
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-icon reject-btn" onclick="rejectStudentClearance('${student.id}')" title="${rejectTitle}" ${rejectBtnDisabled ? 'disabled' : ''}>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderPagination(total, page, limit) {
            const totalPages = Math.ceil(total / limit);
            const startEntry = (page - 1) * limit + 1;
            const endEntry = Math.min(page * limit, total);

            document.getElementById('paginationInfo').textContent = `Showing ${total > 0 ? startEntry : 0} to ${endEntry} of ${total} entries`;
            
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = ''; // Clear old page numbers

            // Simplified pagination buttons for this example
            for (let i = 1; i <= totalPages; i++) {
                const button = document.createElement('button');
                button.className = `pagination-btn ${i === page ? 'active' : ''}`;
                button.textContent = i;                button.onclick = () => goToPage(i);
                pageNumbersContainer.appendChild(button);
            }

            document.getElementById('prevPage').disabled = page === 1;
            document.getElementById('nextPage').disabled = page === totalPages;
        }

        function updateStatistics(stats) {
            if (!stats) return;
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
            document.getElementById('graduatedStudents').textContent = stats.graduated || 0;
        }

        // Filter functions
        function applyFilters() {
            currentPage = 1; // Reset to first page on new filter/search
            fetchStudents();
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            fetchStudents();
            showToastNotification('All filters cleared', 'info');
        }

        // Go to a specific page
        function goToPage(pageNum) {
            currentPage = pageNum;
            fetchStudents();
        }

        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next') {
                currentPage++;
            }
            fetchStudents();
        }

        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
            fetchStudents();
        }

        function scrollToTop() {
            const tableWrapper = document.getElementById('studentTableWrapper');
            tableWrapper.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show scroll to top button when scrolled
        document.getElementById('studentTableWrapper').addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollToTopBtn');
            if (this.scrollTop > 200) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        // Export functionality
        function triggerExportModal() {
            // For Regular Staff, we should use the ClearanceExportModal which is already included
            // But check if ExportModal is available for general reports
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else if (typeof window.openClearanceExportModal === 'function') {
                window.openClearanceExportModal();
            } else {
                console.error('Export modal function not found');
                showToastNotification('Export modal not available', 'error');
            }
        }

        // Load staff position information
        async function loadStaffPosition() {
            try {
                const response = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const data = await response.json();
                const positionElement = document.getElementById('positionInfo');
                
                if (positionElement) {
                    if (data.success && data.designation_name) {
                        positionElement.textContent = `Position: ${data.designation_name} - Clearance Signatory`;
                    } else {
                        positionElement.textContent = 'Position: Staff - Clearance Signatory';
                    }
                }
            } catch (error) {
                console.error('Error loading staff position:', error);
                document.getElementById('positionInfo').textContent = 'Position: Staff - Clearance Signatory';
            }
        }

        // Load staff position information
        async function loadStaffPosition() {
            try {
                const response = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const data = await response.json();
                const positionElement = document.getElementById('positionInfo');
                
                if (positionElement) {
                    if (data.success && data.designation_name) {
                        positionElement.textContent = `Position: ${data.designation_name} - Clearance Signatory`;
                    } else {
                        positionElement.textContent = 'Position: Staff - Clearance Signatory';
                    }
                }
            } catch (error) {
                console.error('Error loading staff position:', error);
                document.getElementById('positionInfo').textContent = 'Position: Staff - Clearance Signatory';
            }
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

        async function loadClearanceStatuses() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=clearance_signatories&column=action`;
            await populateFilter('clearanceStatusFilter', url, 'All Clearance Statuses');
        }

        async function loadAccountStatuses() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=users&column=account_status&exclude=resigned`;
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
        }

        async function loadSchoolTerms() {
            const url = `../../api/clearance/get_filter_options.php?type=school_terms`;
            await populateFilter('schoolTermFilter', url, 'All School Terms');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Load current clearance period for banner
            loadCurrentPeriod();
            
            updateSelectionCounter();
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('student-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });

            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();

            // Initial data fetch
            fetchStudents();
            loadRejectionReasons();
            loadSchoolTerms();
            loadClearanceStatuses();
            loadAccountStatuses();
            loadStaffPosition();

            document.getElementById('searchInput').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault(); // Prevent form submission if it's in a form
                    applyFilters();
                }
            });
        });

        function escapeHtml(unsafe) {
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            targetId: null,
            targetName: null,
            targetType: 'student',
            isBulk: false,
            targetIds: []
        };
        
        function openRejectionRemarksModal(targetId, targetName, targetType = 'student', isBulk = false, targetIds = [], existingRemarks = '', existingReasonId = '') {
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

            // Reset form or pre-fill
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
                targetType: 'student',
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
                // Update student table rows
                currentRejectionData.targetIds.forEach(id => {
                    const row = document.querySelector(`.student-checkbox[data-id="${id}"]`);
                    if (row) {
                        const tableRow = row.closest('tr');
                        if (tableRow) {
                            const clearanceBadge = tableRow.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed, .status-badge.clearance-approved');
                            if (clearanceBadge) {
                                clearanceBadge.textContent = 'Rejected';
                                clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed', 'clearance-approved');
                                clearanceBadge.classList.add('clearance-rejected');
                            }
                        }
                    }
                });
                
                // Uncheck all checkboxes
                document.getElementById('selectAll').checked = false;
                currentRejectionData.targetIds.forEach(id => {
                    const checkbox = document.querySelector(`.student-checkbox[data-id="${id}"]`);
                    if (checkbox) checkbox.checked = false;
                });
                updateBulkButtons();
                // server-side records
                try {
                    for (const id of currentRejectionData.targetIds) {
                        const uid = await resolveUserIdFromStudentNumber(id); // id is student number
                        if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Rejected', additionalRemarks); }
                    }
                } catch (e) {}
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} students with remarks`, 'success');
            } else {
                // Update individual student row
                const row = document.querySelector(`.student-checkbox[data-id="${currentRejectionData.targetId}"]`);
                if (row) {
                    const tableRow = row.closest('tr');
                    if (tableRow) {
                        const clearanceBadge = tableRow.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed, .status-badge.clearance-approved');
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Rejected';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed', 'clearance-approved');
                            clearanceBadge.classList.add('clearance-rejected');
                        }
                    }
                }
                // server-side record
                try {
                    const uid = await resolveUserIdFromStudentNumber(currentRejectionData.targetId); // targetId is student number
                    if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Rejected', additionalRemarks); }
                } catch (e) {}
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
            }
            
            // Close modal
            closeRejectionRemarksModal();
            
            // Demo: Log rejection data (in real implementation, this would be sent to server)
            console.log('Rejection Data:', {
                target: currentRejectionData,
                reason: rejectionReason,
                additionalRemarks: additionalRemarks,
                timestamp: new Date().toISOString()
            });
        }

        async function resolveUserIdFromStudentNumber(studentNumber){
            try{
                const r = await fetch('../../api/users/read.php?limit=5&search=' + encodeURIComponent(studentNumber), { credentials:'include' });
                const data = await r.json();
                const arr = data.users || [];
                const match = arr.find(u => String(u.username) === String(studentNumber));
                return match ? match.user_id : null;
            }catch(e){ return null; }
        }
        async function sendSignatoryAction(applicantUserId, designationName, action, remarks, reasonId = null){
            // Fetch the current staff's actual designation from the API to ensure accuracy.
            let currentDesignation = designationName; // Fallback
            try {
                const desigResponse = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const desigData = await desigResponse.json();
                if (desigData.success && desigData.designation_name) { 
                    currentDesignation = desigData.designation_name; 
                }
            } catch (e) { /* Ignore error, use fallback */ }

            const payload = { 
                applicant_user_id: applicantUserId, 
                designation_name: currentDesignation, 
                action: action 
            };
            if (remarks && remarks.length) payload.remarks = remarks;
            if (reasonId) payload.reason_id = reasonId;

            const response = await fetch('../../api/clearance/signatory_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)
            });
            return await response.json();
        }

        // This function seems to be a duplicate and can be removed. The one above is more robust.
        /* async function sendSignatoryAction(applicantUserId, designationName, action, remarks, reasonId = null){
            const payload = { 
                applicant_user_id: applicantUserId, 
                designation_name: designationName, 
                action: action 
            };
            if (remarks && remarks.length) payload.remarks = remarks;
            if (reasonId) payload.reason_id = reasonId;

            const response = await fetch('../../api/clearance/signatory_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)
            });
            return await response.json();
        } */

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
                    const currentPeriod = data.active_periods.find(p => p.sector === 'College');
                    if (currentPeriod) {
                        if (yearEl) yearEl.textContent = currentPeriod.school_year;
                        if (semesterEl) semesterEl.textContent = currentPeriod.semester_name;
                    } else {
                        if (yearEl) yearEl.textContent = 'No active period';
                        if (semesterEl) semesterEl.textContent = 'for College';
                    }
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

        async function loadSchoolTerms() {
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

        async function loadRejectionReasons() {
            const reasonSelect = document.getElementById('rejectionReason');
            if (!reasonSelect) return;

            try {
                // Fetch reasons relevant for students
                const response = await fetch('../../api/clearance/rejection_reasons.php?category=student', { credentials: 'include' });
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
                reasonSelect.innerHTML = '<option value="">Error loading reasons</option>';
            }
        }
        
        // Clear all selections functionality
        function clearAllSelections() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkButtons();
            updateSelectionCounter();
        }

        // Update selection counter with styling
        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.student-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const selectionDisplay = document.getElementById('selectionCounterPill');
            const clearBtn = document.getElementById('clearSelectionBtn');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
                // Reset selection counter styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections', 'all-selected');
                    selectionDisplay.setAttribute('aria-disabled', 'true');
                    selectionDisplay.title = '';
                }
                if (clearBtn) clearBtn.disabled = true;
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                // Apply all selected styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections');
                    selectionDisplay.classList.add('all-selected');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
                if (clearBtn) clearBtn.disabled = false;
            } else {
                counter.textContent = `${selectedCount} selected`;
                // Apply partial selection styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('all-selected');
                    selectionDisplay.classList.add('has-selections');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
                if (clearBtn) clearBtn.disabled = false;
            }
        }

        // Update bulk buttons with permission checking
        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
             
            // Check if signatory actions are allowed
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            
            bulkButtons.forEach(button => {
                // Disable if no selections OR if signatory actions are not allowed
                button.disabled = checkedBoxes.length === 0 || !canPerformActions;
                
                // Add tooltip for disabled state
                if (!canPerformActions && checkedBoxes.length > 0) {
                    if (button.classList.contains('btn-success')) {
                        button.title = 'Cannot approve: <?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period" : "Not assigned as student signatory"; ?>';
                    } else if (button.classList.contains('btn-danger')) {
                        button.title = 'Cannot reject: <?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period" : "Not assigned as student signatory"; ?>';
                    }
                } else if (checkedBoxes.length === 0) {
                    button.title = 'Select students to perform actions';
                } else {
                    button.title = '';
                }
            });
            
            updateSelectionCounter();
        }
    </script>
    <script src="../../assets/js/alerts.js"></script>
</body>
</html>
