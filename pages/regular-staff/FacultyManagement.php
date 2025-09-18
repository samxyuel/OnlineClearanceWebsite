<?php
// Online Clearance Website - Regular Staff Faculty Management

// Permission-based access control: Regular Staff can always view faculty data
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
    
    // Check if user is a staff member (more robust check)
    $staffCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM staff s 
        WHERE s.user_id = ? AND s.is_active = 1
    ");
    $staffCheck->execute([$userId]);
    $isStaff = (int)$staffCheck->fetchColumn() > 0;
    
    // If not staff, check if user has any role that should have access
    if (!$isStaff) {
        $roleCheck = $pdo->prepare("
            SELECT r.role_name 
            FROM users u 
            JOIN user_roles ur ON u.user_id = ur.user_id 
            JOIN roles r ON ur.role_id = r.role_id 
            WHERE u.user_id = ? AND r.role_name IN ('Admin', 'Program Head', 'School Administrator')
        ");
        $roleCheck->execute([$userId]);
        $hasAdminRole = $roleCheck->fetchColumn();
        
        // TEMPORARILY DISABLED FOR TESTING - ALLOW ANY LOGGED IN USER
        // if (!$hasAdminRole) {
        //     header('HTTP/1.1 403 Forbidden'); 
        //     echo 'Access denied. Regular staff access required.'; 
        //     exit; 
        // }
    }
    
    // Check permission flags (for conditional UI behavior)
    $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE is_active=1")->fetchColumn() > 0;
    
    $facultySignatoryCheck = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments sa JOIN designations d ON sa.designation_id=d.designation_id WHERE sa.user_id=? AND sa.clearance_type='faculty' AND sa.is_active=1");
    $facultySignatoryCheck->execute([$userId]);
    $hasFacultySignatoryAccess = (int)$facultySignatoryCheck->fetchColumn() > 0;
    
    $canPerformSignatoryActions = $hasActivePeriod && $hasFacultySignatoryAccess;
    
    // Store permission flags for use in the page
    $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
    $GLOBALS['hasFacultySignatoryAccess'] = $hasFacultySignatoryAccess;
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
    <title>Faculty Management - Staff Dashboard</title>
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
                            <h2><i class="fas fa-chalkboard-teacher"></i> Faculty Management</h2>
                            <p>Review and sign faculty clearance requests</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>
                                <span id="staffPositionInfo">Loading position...</span>
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
                                <strong>View-Only Access:</strong> You can view faculty data but are not currently assigned as a faculty signatory.
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
                                    <h3 id="totalFaculty">89</h3>
                                    <p>Total Faculty</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeFaculty">76</h3>
                                    <p>Active</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveFaculty">8</h3>
                                    <p>Inactive</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="resignedFaculty">5</h3>
                                    <p>Resigned</p>
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

                        <!-- Search and Filters Section -->
                        <div class="search-filters-section">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search faculty by name, ID, or department...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Employment Status Filter -->
                                <select id="employmentStatusFilter" class="filter-select">
                                    <option value="">All Employment Status</option>
                                    <option value="full-time">Full Time</option>
                                    <option value="part-time">Part Time</option>
                                    <option value="part-time-full-load">Part Time - Full Load</option>
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <option value="unapplied">Unapplied</option>
                                    <option value="pending">Pending</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select" onchange="updateStatisticsByTerm()">
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
                                    <option value="resigned">Resigned Only</option>
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

                        <!-- Current Period Banner -->
                        <div class="current-period-banner-wrapper">
                            <div id="currentPeriodBanner" class="current-period-banner">
                                <i class="fas fa-calendar-alt banner-icon" aria-hidden="true"></i>
                                <span id="currentPeriodText">Loading current period...</span>
                            </div>
                        </div>

                        <!-- Faculty Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-primary select-all-btn" onclick="toggleSelectAll()">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                    <button class="selection-counter-display" id="selectionCounterPill" type="button" title="" aria-disabled="true">
                                        <span id="selectionCounter">0 selected</span>
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
                                <div class="students-table-wrapper" id="facultyTableWrapper">
                                    <table id="facultyTable" class="students-table">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-column">
                                                    <input type="checkbox" id="headerCheckbox" onchange="toggleHeaderCheckbox()">
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
                                            <tr>
                                                <td colspan="7" class="loading-row">
                                                    <div class="loading-spinner">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                        <span>Loading faculty data...</span>
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
                                <span id="paginationInfo">Showing 1 to 2 of 2 entries</span>
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
    <?php include '../../Modals/ClearanceExportModal.php'; ?>
    
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
                        <select id="rejectionReason" class="form-control" onchange="handleReasonChange()">
                            <option value="">Select a reason...</option>
                            <option value="incomplete_documents">Incomplete Documents</option>
                            <option value="unpaid_obligations">Unpaid Obligations</option>
                            <option value="employment_requirements">Employment Requirements Not Met</option>
                            <option value="disciplinary_issues">Disciplinary Issues</option>
                            <option value="missing_clearance">Missing Clearance Items</option>
                            <option value="contract_issues">Contract/Employment Issues</option>
                            <option value="other">Other (Please specify below)</option>
                        </select>
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
    
    <!-- Include Clearance Button Manager -->
    <script src="../../assets/js/clearance-button-manager.js"></script>
    
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <script>
        let CURRENT_STAFF_POSITION = 'Staff'; // Will be loaded dynamically
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

        // Select all functionality
        function toggleSelectAll() {
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            const headerCheckbox = document.getElementById('headerCheckbox');
            const allChecked = Array.from(facultyCheckboxes).every(cb => cb.checked);
            
            // If all are checked, uncheck all; otherwise check all
            const newState = !allChecked;
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = newState;
            });
            headerCheckbox.checked = newState;
            
            updateBulkButtons();
            updateSelectionCounter();
        }
        
        // Header checkbox functionality
        function toggleHeaderCheckbox() {
            const headerCheckbox = document.getElementById('headerCheckbox');
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = headerCheckbox.checked;
            });
            
            updateBulkButtons();
            updateSelectionCounter();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.faculty-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const selectionDisplay = document.getElementById('selectionCounterPill');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
                // Reset selection counter styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections', 'all-selected');
                    selectionDisplay.setAttribute('aria-disabled', 'true');
                    selectionDisplay.title = '';
                }
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                // Apply all selected styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections');
                    selectionDisplay.classList.add('all-selected');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
            } else {
                counter.textContent = `${selectedCount} selected`;
                // Apply partial selection styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('all-selected');
                    selectionDisplay.classList.add('has-selections');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
             
             // Check if signatory actions are allowed
             const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            
            bulkButtons.forEach(button => {
                 // Disable if no selections OR if signatory actions are not allowed
                 button.disabled = checkedBoxes.length === 0 || !canPerformActions;
                 
                 // Add tooltip for disabled state
                 if (!canPerformActions && checkedBoxes.length > 0) {
                     if (button.classList.contains('btn-success')) {
                         button.title = 'Cannot approve: <?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period" : "Not assigned as faculty signatory"; ?>';
                     } else if (button.classList.contains('btn-danger')) {
                         button.title = 'Cannot reject: <?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period" : "Not assigned as faculty signatory"; ?>';
                     }
                 } else if (checkedBoxes.length === 0) {
                     button.title = 'Select faculty to perform actions';
                 } else {
                     button.title = '';
                 }
            });
            
            updateSelectionCounter();
        }

        // Bulk Actions - Staff can only approve/reject clearances
        function approveSelected() {
            // Check if signatory actions are allowed
            if (!canPerformSignatoryActions()) {
                return;
            }
            
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
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    let successCount = 0;
                    let errorCount = 0;
                    
                    for (const checkbox of selectedRows) {
                        try {
                            const eid = checkbox.getAttribute('data-id');
                            const uid = await resolveUserIdFromEmployeeNumber(eid);
                            
                            if (uid) {
                                const result = await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Approved');
                                
                                if (result.success) {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Completed';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                            clearanceBadge.classList.add('clearance-completed');
                        }
                                    successCount++;
                                } else {
                                    errorCount++;
                                    console.error('Failed to approve clearance for', eid, ':', result.message);
                                }
                            } else {
                                errorCount++;
                                console.error('Could not resolve user ID for', eid);
                            }
                        } catch (e) {
                            errorCount++;
                            console.error('Error approving clearance for', eid, ':', e);
                        }
                    }
                    
                    if (successCount > 0) {
                        showToastNotification(`✓ Successfully approved clearance for ${successCount} faculty`, 'success');
                    }
                    if (errorCount > 0) {
                        showToastNotification(`Failed to approve clearance for ${errorCount} faculty`, 'error');
                    }
                },
                'success'
            );
        }

        function rejectSelected() {
            // Check if signatory actions are allowed
            if (!canPerformSignatoryActions()) {
                return;
            }
            
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to reject clearance', 'warning');
                return;
            }
            
            // Get selected faculty IDs
            const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-id'));
            
            // Open rejection remarks modal for bulk rejection
            openRejectionRemarksModal(null, null, 'faculty', true, selectedIds);
        }

        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }

        // Load faculty data from API
        async function loadFacultyData() {
            try {
                const response = await fetch('../../api/users/staff_faculty_list.php?limit=500', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (!data.success) {
                    showEmptyState('Failed to load faculty data: ' + data.message);
                    showToastNotification('Failed to load faculty data: ' + data.message, 'error');
                    return;
                }
                
                populateFacultyTable(data.faculty);
                updateStatistics(data.faculty);
                
            } catch (error) {
                console.error('Error loading faculty data:', error);
                showEmptyState('Error loading faculty data');
                showToastNotification('Failed to load faculty data', 'error');
            }
        }
        
        // Load current clearance period for banner
        async function loadCurrentPeriod() {
            try {
                const response = await fetch('../../api/clearance/periods.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                const bannerEl = document.getElementById('currentPeriodText');
                if (!bannerEl) return;
                
                if (data.success && data.active_period) {
                    const period = data.active_period;
                    const termMap = { '1st': 'Term 1', '2nd': 'Term 2', '3rd': 'Term 3' };
                    const semLabel = termMap[period.semester_name] || period.semester_name || '';
                    bannerEl.textContent = `${period.school_year} • ${semLabel}`;
                } else {
                    bannerEl.textContent = 'No active clearance period';
                }
            } catch (error) {
                console.error('Error loading current period:', error);
                const bannerEl = document.getElementById('currentPeriodText');
                if (bannerEl) {
                    bannerEl.textContent = 'Unable to load period';
                }
            }
        }
        
        // Load periods for period selector dropdown
        async function loadPeriods() {
            try {
                console.log('Loading periods...');
                const response = await fetch('../../api/clearance/periods.php', {
                    credentials: 'include'
                });
                
                console.log('Periods API response status:', response.status);
                const data = await response.json();
                console.log('Periods API response data:', data);
                
                const periodSelect = document.getElementById('schoolTermFilter');
                if (!periodSelect) {
                    console.error('Period selector element not found');
                    return;
                }
                
                // Clear existing options except the first one
                periodSelect.innerHTML = '<option value="">All School Terms</option>';
                
                if (data.success && data.periods && data.periods.length > 0) {
                    console.log('Found periods:', data.periods.length);
                    data.periods.forEach(period => {
                        const option = document.createElement('option');
                        option.value = `${period.academic_year}-${period.semester_name}`;
                        
                        const termMap = { '1st': '1st Semester', '2nd': '2nd Semester', '3rd': '3rd Semester' };
                        const semLabel = termMap[period.semester_name] || period.semester_name || '';
                        const activeText = period.is_active ? ' (Active)' : '';
                        
                        option.textContent = `${period.academic_year} ${semLabel}${activeText}`;
                        periodSelect.appendChild(option);
                        console.log('Added period option:', option.textContent);
                    });
                } else {
                    console.log('No periods found or API failed');
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No periods available';
                    option.disabled = true;
                    periodSelect.appendChild(option);
                }
            } catch (error) {
                console.error('Error loading periods:', error);
                const periodSelect = document.getElementById('schoolTermFilter');
                if (periodSelect) {
                    periodSelect.innerHTML = '<option value="">Error loading periods</option>';
                }
            }
        }
        
        function populateFacultyTable(facultyList) {
            const tbody = document.getElementById('facultyTableBody');
            tbody.innerHTML = '';
            
            if (facultyList && facultyList.length > 0) {
                facultyList.forEach(faculty => {
                    const row = createFacultyRow(faculty);
                    tbody.appendChild(row);
                });
            } else {
                showEmptyState('No faculty data found');
            }
            
            // Update pagination
            updatePagination();
        }
        
        // Show empty state
        function showEmptyState(message) {
            const tbody = document.getElementById('facultyTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-users"></i>
                        <div>${message}</div>
                    </td>
                </tr>
            `;
        }
        
        function createFacultyRow(faculty) {
            const tr = document.createElement('tr');
            // Set data-term based on current active period (for now, we'll use a default)
            // TODO: Enhance API to include period information in faculty data
            tr.setAttribute('data-term', '2025-2026-1st'); // Default to current active period
            tr.setAttribute('data-faculty-id', faculty.user_id); // Add faculty ID for button manager
            
            const statusRaw = faculty.clearance_status;
            let clearanceKey = 'unapplied';
            if (statusRaw === 'Completed' || statusRaw === 'Complete') clearanceKey = 'completed';
            else if (statusRaw === 'Applied') clearanceKey = 'pending';
            else if (statusRaw === 'In Progress' || statusRaw === 'Pending') clearanceKey = 'in-progress';
            else if (statusRaw === 'Rejected') clearanceKey = 'rejected';
            
            const accountStatus = faculty.status.toLowerCase();
            const clearanceStatus = clearanceKey;
            
            // Check if signatory actions are allowed
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            const hasActivePeriod = <?php echo $GLOBALS['hasActivePeriod'] ? 'true' : 'false'; ?>;
            const hasFacultySignatoryAccess = <?php echo $GLOBALS['hasFacultySignatoryAccess'] ? 'true' : 'false'; ?>;
            
            // Determine tooltip messages
            let approveTooltip = 'Approve Clearance';
            let rejectTooltip = 'Reject Clearance';
            
            if (!canPerformActions) {
                if (!hasActivePeriod) {
                    approveTooltip = 'Cannot approve: No active clearance period';
                    rejectTooltip = 'Cannot reject: No active clearance period';
                } else if (!hasFacultySignatoryAccess) {
                    approveTooltip = 'Cannot approve: Not assigned as faculty signatory';
                    rejectTooltip = 'Cannot reject: Not assigned as faculty signatory';
                }
            }
            
            tr.innerHTML = `
                <td><input type="checkbox" class="faculty-checkbox" data-id="${faculty.employee_number}"></td>
                <td>${faculty.employee_number}</td>
                <td>${faculty.first_name} ${faculty.last_name}</td>
                <td><span class="status-badge employment-${faculty.employment_status.toLowerCase().replace(/ /g, '-')}">${faculty.employment_status}</span></td>
                <td><span class="status-badge account-${accountStatus}">${accountStatus.charAt(0).toUpperCase() + accountStatus.slice(1)}</span></td>
                <td><span class="status-badge clearance-${clearanceStatus}">${statusRaw}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon approve-btn" onclick="approveFacultyClearance('${faculty.user_id}')" title="${approveTooltip}" disabled>
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon reject-btn" onclick="rejectFacultyClearance('${faculty.user_id}')" title="${rejectTooltip}" disabled>
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
        
        function updateStatistics(facultyList) {
            let total = facultyList.length;
            let active = 0, inactive = 0, resigned = 0;
            
            facultyList.forEach(faculty => {
                const status = faculty.status.toLowerCase();
                if (status === 'active') active++;
                else if (status === 'inactive') inactive++;
                else if (status === 'resigned') resigned++;
            });
            
            document.getElementById('totalFaculty').textContent = total;
            document.getElementById('activeFaculty').textContent = active;
            document.getElementById('inactiveFaculty').textContent = inactive;
            document.getElementById('resignedFaculty').textContent = resigned;
        }

        // Individual faculty actions - Staff can only approve/reject clearances
        async function approveFacultyClearance(facultyId) {
            // Check if signatory actions are allowed
            if (!canPerformSignatoryActions()) {
                return;
            }
            
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
            
            if (!clearanceBadge) {
                showToastNotification('No clearance to approve for this faculty', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Approve Faculty Clearance',
                `Are you sure you want to approve clearance for ${facultyName}?`,
                'Approve',
                'Cancel',
                async () => {
                    try {
                        // Get user ID from employee number
                        const userId = await resolveUserIdFromEmployeeNumber(facultyId);
                        if (!userId) {
                            showToastNotification('Could not find user ID for this faculty', 'error');
                            return;
                        }
                        
                        // Send approval to API
                        const response = await fetch('../../api/clearance/signatory_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                applicant_user_id: userId,
                                designation_name: CURRENT_STAFF_POSITION,
                                action: 'Approved'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Update UI
                    clearanceBadge.textContent = 'Completed';
                    clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                    clearanceBadge.classList.add('clearance-completed');
                            
                            showToastNotification(`✓ Successfully approved clearance for ${facultyName}`, 'success');
                        } else {
                            showToastNotification('Failed to approve clearance: ' + data.message, 'error');
                        }
                        
                    } catch (error) {
                        console.error('Error approving clearance:', error);
                        showToastNotification('Failed to approve clearance', 'error');
                    }
                },
                'success'
            );
        }

        async function rejectFacultyClearance(facultyId) {
            // Check if signatory actions are allowed
            if (!canPerformSignatoryActions()) {
                return;
            }
            
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            // Open rejection remarks modal
            openRejectionRemarksModal(facultyId, facultyName, 'faculty', false);
        }
        
        // Helper function to check if signatory actions are allowed
        function canPerformSignatoryActions() {
            const hasActivePeriod = <?php echo $GLOBALS['hasActivePeriod'] ? 'true' : 'false'; ?>;
            const hasFacultySignatoryAccess = <?php echo $GLOBALS['hasFacultySignatoryAccess'] ? 'true' : 'false'; ?>;
            
            if (!hasActivePeriod) {
                showToastNotification('Cannot perform signatory actions: No active clearance period', 'warning');
                return false;
            }
            
            if (!hasFacultySignatoryAccess) {
                showToastNotification('Cannot perform signatory actions: You are not assigned as a faculty signatory', 'warning');
                return false;
            }
            
            return true;
        }
        
        // Helper function to resolve user ID from employee number
        async function resolveUserIdFromEmployeeNumber(employeeNumber) {
            try {
                const response = await fetch(`../../api/users/get_faculty.php?employee_number=${encodeURIComponent(employeeNumber)}`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.faculty) {
                    return data.faculty.user_id;
                }
                return null;
            } catch (error) {
                console.error('Error resolving user ID:', error);
                return null;
            }
        }
        
        // Send signatory action to API
        async function sendSignatoryAction(userId, designationName, action, remarks = null) {
            try {
                const response = await fetch('../../api/clearance/signatory_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        applicant_user_id: userId,
                        designation_name: designationName,
                        action: action,
                        remarks: remarks
                    })
                });
                
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error sending signatory action:', error);
                return { success: false, message: 'Network error' };
            }
        }
        
        // Clear all selections function
        function clearAllSelections() {
            const selectedCount = getSelectedCount();
            
            if (selectedCount === 0) {
                showToastNotification('No selections to clear', 'info');
                return;
            }
            
            // Clear all faculty checkboxes
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            const headerCheckbox = document.getElementById('headerCheckbox');
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            headerCheckbox.checked = false;
            
            // Update UI states
            updateSelectionCounter();
            updateBulkButtons();
            
            showToastNotification(`Cleared ${selectedCount} selections`, 'success');
        }
        
        // Undo last action function (placeholder for now)
        function undoLastAction() {
            showToastNotification('Undo functionality not implemented yet', 'info');
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {
            // Load faculty data on page load
            await loadFacultyData();
            
            // Update button states after table is loaded
            if (window.clearanceButtonManager) {
                await window.clearanceButtonManager.updateAllButtons('Faculty', 'faculty');
            }
            
            // Load current clearance period for banner
            loadCurrentPeriod();
            
            // Load periods for period selector
            loadPeriods();
            
            // Add event listeners for checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
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
        });
        
        // Pagination functions (simplified for now)
        function updatePagination() {
            const totalRows = document.querySelectorAll('#facultyTableBody tr').length;
            document.getElementById('paginationInfo').textContent = `Showing 1 to ${totalRows} of ${totalRows} entries`;
        }
        
        function changePage(direction) {
            // Simplified pagination - could be enhanced later
            console.log('Page change:', direction);
        }
        
        function changeEntriesPerPage() {
            // Simplified pagination - could be enhanced later
            console.log('Entries per page changed');
        }
        
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
        
        // Filter functions (simplified for now)
        function applyFilters() {
            // Simplified filtering - could be enhanced later
            console.log('Applying filters');
        }
        
        function clearFilters() {
            // Simplified filtering - could be enhanced later
            console.log('Clearing filters');
        }
        
        function updateStatisticsByTerm() {
            // Simplified statistics - could be enhanced later
            console.log('Updating statistics by term');
        }
        
        function triggerExportModal() {
            showToastNotification('Export functionality not implemented yet', 'info');
        }
        
        // Global variable for current staff position (set by PHP above)
        // Note: CURRENT_STAFF_POSITION is set by PHP and cannot be reassigned in JavaScript
        
        // Load current staff designation
        async function loadCurrentStaffDesignation() {
            try {
                const response = await fetch('../../api/users/get_current_staff_designation.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    console.log('Current staff position:', data.designation_name);
                    
                    // Update the global variable
                    CURRENT_STAFF_POSITION = data.designation_name;
                    
                    // Update the position info in the header
                    const positionInfo = document.getElementById('staffPositionInfo');
                    if (positionInfo) {
                        positionInfo.textContent = `Position: ${data.designation_name}`;
                    }
                } else {
                    console.error('Failed to load staff designation:', data.message);
                    
                    const positionInfo = document.getElementById('staffPositionInfo');
                    if (positionInfo) {
                        positionInfo.textContent = 'Position: Unknown';
                    }
                }
            } catch (error) {
                console.error('Error loading staff designation:', error);
            }
        }
        
        // Initialize staff position on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load current staff designation
            loadCurrentStaffDesignation();
        });

        // Filter functions
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const employmentStatus = document.getElementById('employmentStatusFilter').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const facultyName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const employmentBadge = row.querySelector('.status-badge.employment-full-time, .status-badge.employment-part-time, .status-badge.employment-part-time-full-load');
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                let shouldShow = true;
                
                // Search filter
                if (searchTerm && !facultyName.includes(searchTerm)) {
                    shouldShow = false;
                }
                
                // Employment status filter
                if (employmentStatus && employmentBadge) {
                    if (employmentStatus === 'part-time-full-load') {
                        if (!employmentBadge.classList.contains('employment-part-time-full-load')) {
                            shouldShow = false;
                        }
                    } else if (!employmentBadge.classList.contains(`employment-${employmentStatus}`)) {
                        shouldShow = false;
                    }
                }
                
                // Clearance status filter
                if (clearanceStatus && clearanceBadge && !clearanceBadge.classList.contains(`clearance-${clearanceStatus}`)) {
                    shouldShow = false;
                }
                
                // Account status filter
                if (accountStatus && accountBadge && !accountBadge.classList.contains(`account-${accountStatus}`)) {
                    shouldShow = false;
                }
                
                // School term filter
                if (schoolTerm && row.getAttribute('data-term') !== schoolTerm) {
                    shouldShow = false;
                }
                
                // Show/hide row
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleCount++;
            });
            
            updateFilteredEntries();
            showToastNotification(`Showing ${visibleCount} of ${tableRows.length} faculty`, 'info');
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('employmentStatusFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            updateFilteredEntries();
            showToastNotification('All filters cleared', 'info');
        }

        function updateStatisticsByTerm() {
            applyFilters();
        }

        // Pagination functions
        let currentPage = 1;
        let entriesPerPage = 20;
        let filteredEntries = [];

        function initializePagination() {
            const allRows = document.querySelectorAll('#facultyTableBody tr');
            filteredEntries = Array.from(allRows);
            updatePagination();
        }

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
            updatePagination();
        }

        function changePage(direction) {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }
            
            updatePagination();
        }

        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
            updatePagination();
        }

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

        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#facultyTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1;
            updatePagination();
        }

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

        // Export functionality
        function triggerExportModal() {
            showConfirmationModal(
                'Export Faculty Clearance Report',
                'Generate a PDF report of your faculty clearance signing activities?',
                'Export',
                'Cancel',
                () => {
                    showToastNotification('Report generation started...', 'info');
                    setTimeout(() => {
                        showToastNotification('Faculty clearance report exported successfully!', 'success');
                    }, 2000);
                },
                'info'
            );
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectionCounter();
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
            
            initializePagination();
            
            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();
        });

        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            targetId: null,
            targetName: null,
            targetType: 'faculty',
            isBulk: false,
            targetIds: []
        };

        function openRejectionRemarksModal(targetId, targetName, targetType = 'faculty', isBulk = false, targetIds = []) {
            currentRejectionData = {
                targetId: targetId,
                targetName: targetName,
                targetType: targetType,
                isBulk: isBulk,
                targetIds: targetIds
            };

            // Update modal content based on target type
            const modal = document.getElementById('rejectionRemarksModal');
            const targetNameElement = document.getElementById('rejectionTargetName');
            const targetTypeElement = document.getElementById('rejectionType');
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');

            // Reset form
            reasonSelect.value = '';
            remarksTextarea.value = '';

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
                // Update faculty table rows
                currentRejectionData.targetIds.forEach(id => {
                    const row = document.querySelector(`.faculty-checkbox[data-id="${id}"]`);
                    if (row) {
                        const tableRow = row.closest('tr');
                        if (tableRow) {
                            const clearanceBadge = tableRow.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed');
                            if (clearanceBadge) {
                                clearanceBadge.textContent = 'Rejected';
                                clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed');
                                clearanceBadge.classList.add('clearance-rejected');
                            }
                        }
                    }
                });
                
                // Uncheck all checkboxes
                document.getElementById('selectAll').checked = false;
                currentRejectionData.targetIds.forEach(id => {
                    const checkbox = document.querySelector(`.faculty-checkbox[data-id="${id}"]`);
                    if (checkbox) checkbox.checked = false;
                });
                updateBulkButtons();
                // server-side records
                try {
                    for (const id of currentRejectionData.targetIds) {
                        const uid = await resolveUserIdFromEmployeeNumber(id);
                        if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Rejected', additionalRemarks); }
                    }
                } catch (e) {}
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} faculty with remarks`, 'success');
            } else {
                // Update individual faculty row
                const row = document.querySelector(`.faculty-checkbox[data-id="${currentRejectionData.targetId}"]`);
                if (row) {
                    const tableRow = row.closest('tr');
                    if (tableRow) {
                        const clearanceBadge = tableRow.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed');
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Rejected';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed');
                            clearanceBadge.classList.add('clearance-rejected');
                        }
                    }
                }
                // server-side record
                try {
                    const uid = await resolveUserIdFromEmployeeNumber(currentRejectionData.targetId);
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

        async function resolveUserIdFromEmployeeNumber(employeeNumber){
            try{
                const r = await fetch('../../api/users/read.php?limit=5&search=' + encodeURIComponent(employeeNumber), { credentials:'include' });
                const data = await r.json();
                const arr = data.users || [];
                const match = arr.find(u => String(u.username) === String(employeeNumber));
                return match ? match.user_id : null;
            }catch(e){ return null; }
        }
        async function sendSignatoryAction(applicantUserId, designationName, action, remarks){
            const payload = { applicant_user_id: applicantUserId, designation_name: designationName, action: action };
            if (remarks && remarks.length) payload.remarks = remarks;
            await fetch('../../api/clearance/signatory_action.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)}).then(r=>r.json()).catch(()=>null);
        }
    </script>
    <script src="../../assets/js/alerts.js"></script>
</body>
</html>
