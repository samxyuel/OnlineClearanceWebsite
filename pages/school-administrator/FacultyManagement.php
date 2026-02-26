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
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
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
                                <span id="staffPositionInfo">Loading position...</span>
                            </div>

                            <!-- Role Selector for Multi-Designation Users -->
                            <div class="role-selector-container">
                                <i class="fas fa-user-tag"></i>
                                <label for="roleSelector">Viewing as:</label>
                                <?php
                                $signatoryDesignations = $GLOBALS['userSignatoryDesignations'];
                                if (count($signatoryDesignations) > 1): ?>
                                    <select id="roleSelector" class="filter-select" onchange="handleRoleChange()">
                                        <?php foreach ($signatoryDesignations as $designation): ?>
                                            <option value="<?php echo htmlspecialchars($designation['designation_name']); ?>">
                                                <?php echo htmlspecialchars($designation['designation_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif (count($signatoryDesignations) === 1): ?>
                                    <span class="single-role-display"><?php echo htmlspecialchars($signatoryDesignations[0]['designation_name']); ?></span>
                                    <input type="hidden" id="roleSelector" value="<?php echo htmlspecialchars($signatoryDesignations[0]['designation_name']); ?>">
                                <?php else: ?>
                                    <span class="single-role-display">No active signatory roles</span>
                                <?php endif; ?>
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
                        </div>

                        <!-- Quick Actions Section -->
                        <div class="quick-actions-section">
                            <div class="action-buttons">
                                <button class="btn btn-secondary export-btn" onclick="triggerExportModal()">
                                    <i class="fas fa-file-export"></i> Export
                                </button>
                            </div>
                        </div>

                        <!-- Tab Banner Wrapper -->
                        <div class="tab-banner-wrapper">
                            <!-- Current Period Banner -->
                            <span class="academic-year-semester">
                                <i class="fas fa-calendar-check"></i> 
                                <span id="currentAcademicYear">Loading...</span> - <span id="currentSemester">Loading...</span>
                            </span>
                        </div>

                        <!-- Term Indicator Banner (shown when historical term is selected) -->
                        <div id="termIndicatorBanner" class="term-indicator-banner" style="display: none;"></div>

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
                                                <th>Department(s)</th>
                                                <th>Employment Status</th>
                                                <th>Account Status</th>
                                                <th>Clearance Form Progress</th>
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

    <script>
        let currentPage = 1;
        let entriesPerPage = 20;
        let currentSearch = '';
        let totalEntries = 0;
        // For School Administrator, default to 'School Administrator' designation
        let CURRENT_STAFF_POSITION = <?php echo json_encode(!empty($GLOBALS['userSignatoryDesignations']) ? $GLOBALS['userSignatoryDesignations'][0]['designation_name'] : 'School Administrator'); ?>;
        let canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
        // Flag to track if user can perform signatory actions (for view-only mode) - will be updated from API
        let canPerformSignatoryActions = false; // Default to false for safety
        
        // Global flag to track if there's an active clearance period for this sector
        let hasActiveClearancePeriod = false;

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
            // Use the parameter if provided, otherwise get from checkbox
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const isChecked = checked !== undefined ? checked : (selectAllCheckbox ? selectAllCheckbox.checked : false);
            const facultyCheckboxes = document.querySelectorAll('#facultyTableBody .faculty-checkbox:not(:disabled)');
            
            facultyCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                // Only toggle visible and enabled rows, respecting current filters
                if (row && row.style.display !== 'none') {
                    checkbox.checked = isChecked;
                }
            });
            
            updateBulkButtons();
            updateSelectionCounter();
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
            const hasSelection = checkedBoxes.length > 0;
            const bulkButtons = document.querySelectorAll('.bulk-buttons button:not([onclick*="undo"])');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const totalCheckboxes = document.querySelectorAll('.faculty-checkbox').length;

            bulkButtons.forEach(button => {
                // Check if this is Approve or Reject button by checking onclick attribute
                const onclickAttr = button.getAttribute('onclick') || '';
                const isApproveOrReject = onclickAttr.includes('approveSelected') || 
                                         onclickAttr.includes('rejectSelected');
                
                if (isApproveOrReject) {
                    // Approve/Reject buttons: require selection, active period, AND permission
                    const canEnable = hasSelection && hasActiveClearancePeriod && canPerformSignatoryActions;
                    button.disabled = !canEnable;
                    
                    if (!canEnable && hasSelection) {
                        // If there's selection but buttons are disabled, add tooltip
                        if (!hasActiveClearancePeriod) {
                            button.title = 'No active clearance period for this sector';
                        } else if (!canPerformSignatoryActions) {
                            button.title = 'You are not assigned as a signatory for this sector';
                        }
                    } else {
                        button.title = '';
                    }
                } else {
                    // Other buttons: only require selection
                    button.disabled = !hasSelection;
                    button.title = '';
                }
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
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Faculty sector\'s clearance period. Approving clearances is not available.', 'warning');
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
                    const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
                    const userIds = [];
                    for (const checkbox of selectedCheckboxes) {
                        // Try to get user_id from checkbox first, then fallback to row
                        const userId = checkbox.getAttribute('data-user-id') || checkbox.closest('tr')?.getAttribute('data-faculty-id');
                        if (userId) {
                            userIds.push(parseInt(userId));
                        }
                    }

                    if (userIds.length === 0) {
                        showToastNotification('Could not identify users to approve.', 'error');
                        return;
                    }

                    // Get the currently selected school term from the filter
                    const schoolTermFilter = document.getElementById('schoolTermFilter');
                    const currentSchoolTerm = schoolTermFilter ? schoolTermFilter.value : '';

                    try {
                        const bulkPayload = {
                            applicant_user_ids: userIds,
                            action: 'Approved',
                            designation_name: CURRENT_STAFF_POSITION || 'School Administrator',
                            remarks: `Approved by ${CURRENT_STAFF_POSITION || 'School Administrator'}`
                        };
                        // Include school_term if a specific term is selected
                        if (currentSchoolTerm && currentSchoolTerm.trim() !== '') {
                            bulkPayload.school_term = currentSchoolTerm.trim();
                        }

                        const response = await fetch('../../api/clearance/bulk_signatory_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify(bulkPayload)
                        });
                        const result = await response.json();
                        if (result.success) {
                            if (result.affected_rows === 0) {
                                showToastNotification('No records were updated. You may not be assigned as a signatory for the Faculty sector\'s clearance period.', 'warning');
                            } else {
                                showToastNotification(`Successfully approved clearance for ${result.affected_rows} faculty.`, 'success');
                            }
                        } else {
                            throw new Error(result.message || 'Bulk approval failed.');
                        }
                    } catch (error) {
                        console.error('Bulk approval error:', error);
                        if (typeof window.showToastNotification === 'function') {
                            window.showToastNotification(error.message, 'error');
                        } else if (typeof showToastNotification === 'function') {
                            showToastNotification(error.message, 'error');
                        } else {
                            alert('Error: ' + error.message);
                        }
                    } finally {
                        fetchFaculty();
                    }
                },
                'success'
            );
        }

        function rejectSelected() {
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Faculty sector\'s clearance period. Rejecting clearances is not available.', 'warning');
                return;
            }
            
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to reject clearance', 'warning');
                return;
            }
            // Get selected user IDs from data-user-id attribute (or fallback to data-faculty-id)
            const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedIds = [];
            for (const checkbox of selectedCheckboxes) {
                // Try to get user_id from checkbox first, then fallback to row
                const userId = checkbox.getAttribute('data-user-id') || checkbox.closest('tr')?.getAttribute('data-faculty-id');
                if (userId) {
                    selectedIds.push(userId);
                }
            }
            openRejectionRemarksModal(null, null, 'faculty', true, selectedIds);
        }



        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }

        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeFaculty');
            const inactiveCount = document.getElementById('inactiveFaculty');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
        }

        // Individual faculty actions - School Administrator as Signatory

        async function approveFacultyClearance(employeeId) {
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Faculty sector\'s clearance period. Approving clearances is not available.', 'warning');
                return;
            }
            
            const row = document.querySelector(`.faculty-checkbox[data-id="${employeeId}"]`).closest('tr');
            if (!row) {
                showToastNotification('Could not find faculty record', 'error');
                return;
            }
            
            const facultyUserId = row.getAttribute('data-faculty-id');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            // Use correct selector - status badge has class "status-badge-compact signatory-pending" not "status-badge clearance-pending"
            const clearanceBadge = row.querySelector('.status-badge-compact.signatory-pending, .status-badge-compact.signatory-rejected');
            
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
                    const result = await sendSignatoryAction(facultyUserId, 'Approved', 'Approved by School Administrator');
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
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Faculty sector\'s clearance period. Rejecting clearances is not available.', 'warning');
                return;
            }
            
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
                return;
            }

            const row = document.querySelector(`.faculty-checkbox[data-id="${employeeId}"]`).closest('tr');
            if (!row) {
                showToastNotification('Could not find faculty record', 'error');
                return;
            }
            
            const facultyUserId = row.getAttribute('data-faculty-id');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            // Use correct selector - status badge has class "status-badge-compact signatory-pending" not "status-badge clearance-pending"
            const clearanceBadge = row.querySelector('.status-badge-compact.signatory-rejected, .status-badge-compact.signatory-pending');

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
        
            openRejectionRemarksModal(facultyUserId, facultyName, 'faculty', false, [], existingRemarks, existingReasonId);
        }

        // Filter functions
        // Update term indicator banner
        function updateTermIndicatorBanner() {
            const banner = document.getElementById('termIndicatorBanner');
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            
            if (!banner || !schoolTermFilter) return;
            
            const selectedValue = schoolTermFilter.value;
            
            if (!selectedValue) {
                banner.style.display = 'none';
                return;
            }
            
            const selectedOption = schoolTermFilter.options[schoolTermFilter.selectedIndex];
            const termText = selectedOption.text;
            
            // Check if this is a historical term (not current/ongoing)
            const isHistorical = true; // TODO: Implement logic to check if term is historical
            
            banner.className = isHistorical ? 'term-indicator-banner historical' : 'term-indicator-banner';
            banner.innerHTML = `
                <i class="fas fa-calendar-alt term-icon"></i>
                <div class="term-text">
                    <strong>Viewing:</strong> ${termText}
                </div>
                <div class="term-label">
                    ${isHistorical ? 'Historical Term' : 'Current Term'}
                </div>
            `;
            banner.style.display = 'flex';
        }

        function applyFilters() {
            currentPage = 1;
            updateTermIndicatorBanner();
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
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
                fetchFaculty();
            } else if (direction === 'next') {
                // Get total pages from server-side pagination
                const totalPages = Math.ceil(totalEntries / entriesPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    fetchFaculty();
                }
            }
        }

        // Change entries per page
        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1; // Reset to first page
            fetchFaculty();
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
            tableBody.innerHTML = `<tr><td colspan="9" class="loading-row"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><span>Loading faculty data...</span></div></td></tr>`;

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

            // Pass the current role/designation for filtering
            if (CURRENT_STAFF_POSITION) url.searchParams.append('designation_filter', CURRENT_STAFF_POSITION);

            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                if (!data.success) {
                    showEmptyState('Error: ' + data.message);
                    return;
                }

                // Update can_perform_actions flag from API response
                canPerformSignatoryActions = data.can_perform_actions === true; // Only enable if explicitly true

                populateFacultyTable(data.faculty);
                updatePaginationUI(data.total, data.page, data.limit);
                updateStatistics(data.stats);
                updateActionButtonsState(); // Update button states based on can_perform_actions
                updateViewOnlyIndicator(); // Show/hide view-only indicator

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
            
            // Check if user existed during the selected term
            const userExisted = faculty.user_existed_during_term !== false; // Default to true if not provided
            
            // Clearance Form Progress (end user's overall progress)
            let clearanceProgress = faculty.clearance_form_progress || 'Unapplied';
            if (!userExisted) {
                clearanceProgress = 'N/A';
            }
            const clearanceProgressClass = `clearance-${clearanceProgress.toLowerCase().replace(/ /g, '-')}`;
            
            // Clearance Status (signatory's action status)
            let clearanceStatus = faculty.clearance_status || 'Unapplied';
            if (!userExisted) {
                clearanceStatus = 'N/A';
            }
            const clearanceStatusClass = `signatory-${clearanceStatus.toLowerCase().replace(/ /g, '-')}`;
            
            const accountStatus = (faculty.account_status || 'inactive').toLowerCase();
            
            // Determine button titles and states based on clearance status
            // Disable buttons if user cannot perform signatory actions (view-only mode)
            const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus) && userExisted && canPerformSignatoryActions;
            let approveBtnDisabled = !isActionable;
            // Enable reject button for 'Pending' and 'Rejected' statuses to allow for edits.
            let rejectBtnDisabled = !isActionable;
            // Checkboxes should only be disabled if user didn't exist during term (match College/SHS behavior)
            let checkboxDisabled = !userExisted;
            let approveTitle = 'Approve Clearance';
            // Change button title if the faculty member is already rejected.
            let rejectTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Clearance';

            if (!canPerformActions) {
                approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a faculty signatory."; ?>';
            }
            
            // Add class for non-existent users
            if (!userExisted) {
                tr.classList.add('user-not-existed');
            }

            // Build clearance progress cell content (end user's form progress)
            let clearanceProgressContent = '';
            if (!userExisted) {
                clearanceProgressContent = `
                        <div class="clearance-status-primary">
                            <span class="status-badge-compact ${clearanceProgressClass}">N/A</span>
                        </div>
                        <div class="clearance-status-secondary">User did not exist during this term</div>
                `;
            } else {
                clearanceProgressContent = `
                        <div class="clearance-status-primary">
                            <span class="status-badge-compact ${clearanceProgressClass}">${clearanceProgress}</span>
                        </div>
                `;
            }

            // Build clearance status cell content (signatory's action)
            let clearanceStatusContent = '';
            if (!userExisted) {
                clearanceStatusContent = `
                        <div class="clearance-status-primary">
                            <span class="status-badge-compact ${clearanceStatusClass}">N/A</span>
                        </div>
                        <div class="clearance-status-secondary">User did not exist during this term</div>
                `;
            } else {
                clearanceStatusContent = `
                        <div class="clearance-status-primary">
                            <span class="status-badge-compact ${clearanceStatusClass}">${clearanceStatus}</span>
                        </div>
                `;
            }
            
            tr.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="faculty-checkbox" data-id="${faculty.id}" data-user-id="${faculty.user_id}" onchange="updateBulkButtons()" ${checkboxDisabled ? 'disabled' : ''}></td>
                <td data-label="Employee Number:">${faculty.id}</td>
                <td data-label="Name:">${escapeHtml(faculty.name)}</td>
                <td data-label="Department(s):">${escapeHtml(faculty.departments || 'N/A')}</td>
                <td data-label="Employment Status:"><span class="status-badge employment-${(faculty.employment_status || '').toLowerCase().replace(/ /g, '-')}">${escapeHtml(faculty.employment_status || 'N/A')}</span></td>
                <td data-label="Account Status:"><span class="status-badge account-${accountStatus}">${faculty.account_status || 'N/A'}</span></td>
                <td data-label="Clearance Form Progress:" class="clearance-status-cell">${clearanceProgressContent}</td>
                <td data-label="Clearance Status:" class="clearance-status-cell">${clearanceStatusContent}</td>
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
                    <td colspan="9" class="empty-state">
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
        }

        // Bulk Selection Modal Functions
        function openBulkSelectionModal() {
            try {
                if (typeof window.openModal === "function") {
                    window.openModal("bulkSelectionModal");
                } else {
                    // Fallback to direct manipulation if openModal not available
                    const modal = document.getElementById('bulkSelectionModal');
                    if (modal) {
                        modal.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                        document.body.classList.add('modal-open');
                        requestAnimationFrame(() => {
                            modal.classList.add('active');
                        });
                    } else {
                        if (typeof showToastNotification === 'function') {
                            showToastNotification('Selection filters are temporarily unavailable.', 'error');
                        }
                    }
                }
            } catch (error) {
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open selection filters. Please try again.', 'error');
                }
            }
        }

        function closeBulkSelectionModal() {
            try {
                if (typeof window.closeModal === "function") {
                    window.closeModal("bulkSelectionModal");
                } else {
                    // Fallback to direct manipulation if closeModal not available
                    const modal = document.getElementById('bulkSelectionModal');
                    if (modal) {
                        modal.classList.remove('active');
                        setTimeout(() => {
                            modal.style.display = 'none';
                            document.body.style.overflow = 'auto';
                            document.body.classList.remove('modal-open');
                        }, 300);
                    }
                }
            } catch (error) {
                // Silent error handling
            }
        }

        function resetBulkSelectionFilters() {
            const checkboxes = ['filterFullTime', 'filterPartTime', 'filterPartTimeFullLoad', 'filterActive', 'filterInactive', 'filterUnapplied', 'filterApplied', 'filterInProgress', 'filterComplete', 'filterPending', 'filterApproved', 'filterRejected'];
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
                const hasAccountFilter = filters.active || filters.inactive;
                if (hasAccountFilter && accountBadge) {
                    if (filters.active && accountBadge.classList.contains('account-active')) accountMatch = true;
                    if (filters.inactive && accountBadge.classList.contains('account-inactive')) accountMatch = true;
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
                
                const schoolTermFilter = document.getElementById('schoolTermFilter');
                if (!schoolTermFilter) return;
                
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    // Find the active period specifically for the 'Faculty' sector
                    const activePeriod = data.active_periods.find(p => 
                        p.sector === 'Faculty' || 
                        p.clearance_type === 'Faculty'
                    );

                    if (activePeriod) {
                        const termValue = `${activePeriod.school_year}|${activePeriod.semester_id}`;
                        // Check if the option exists before setting it
                        if (schoolTermFilter.querySelector(`option[value="${termValue}"]`)) {
                            schoolTermFilter.value = termValue;
                        }
                    }
                } else {
                    // No active clearance period - try to set to most recent term with data
                    // This helps when viewing historical data after a new term is activated
                    const termsToUse = data.all_terms || data.periods || [];
                    if (termsToUse.length > 0) {
                        // Find the most recent term that has clearance_periods (has actual data)
                        const termsWithData = termsToUse.filter(t => t.has_clearance_period !== false);
                        if (termsWithData.length > 0) {
                            const mostRecentTerm = termsWithData[0]; // Already sorted DESC
                            const termValue = `${mostRecentTerm.academic_year}|${mostRecentTerm.semester_id}`;
                            if (schoolTermFilter.querySelector(`option[value="${termValue}"]`)) {
                                schoolTermFilter.value = termValue;
                            }
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
                
                // Check if there's an active period specifically for "Faculty" sector
                let hasActivePeriod = false;
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    // Check if any active period is for Faculty sector
                    hasActivePeriod = data.active_periods.some(period => 
                        period.sector === 'Faculty' || 
                        period.clearance_type === 'Faculty'
                    );
                    
                    if (hasActivePeriod) {
                        const period = data.active_periods.find(p => 
                            p.sector === 'Faculty' || 
                            p.clearance_type === 'Faculty'
                        ) || data.active_periods[0];
                        
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
                } else {
                    if (yearEl) yearEl.textContent = 'No active period';
                    if (semesterEl) semesterEl.textContent = 'No term';
                }
                
                // Update global flag
                hasActiveClearancePeriod = hasActivePeriod;
                
                // Update button states after loading period
                updateBulkButtons();
            } catch (error) {
                console.error('Error loading current period:', error);
                const yearEl = document.getElementById('currentAcademicYear');
                const semesterEl = document.getElementById('currentSemester');
                if (yearEl) yearEl.textContent = 'Error loading';
                if (semesterEl) semesterEl.textContent = 'Error';
                hasActiveClearancePeriod = false;
                updateBulkButtons();
            }
        }


        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {
            
            updateTermIndicatorBanner();

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
            try {
                const modal = document.getElementById('rejectionRemarksModal');
                if (!modal) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Rejection feature is temporarily unavailable.', 'error');
                    }
                    return;
                }

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
                const targetNameElement = document.getElementById('rejectionTargetName');
                const targetTypeElement = document.getElementById('rejectionType');
                const reasonSelect = document.getElementById('rejectionReason');
                const remarksTextarea = document.getElementById('additionalRemarks');

                if (!targetNameElement || !targetTypeElement || !reasonSelect || !remarksTextarea) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Rejection modal elements not found. Please refresh the page.', 'error');
                    }
                    return;
                }

                // Reset form (use existing values if provided)
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
                if (typeof window.openModal === "function") {
                    window.openModal("rejectionRemarksModal");
                } else {
                    // Fallback to direct manipulation if openModal not available
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    document.body.classList.add('modal-open');
                    requestAnimationFrame(() => {
                        modal.classList.add('active');
                    });
                }
            } catch (error) {
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open rejection modal. Please try again.', 'error');
                }
            }
        }

        function closeRejectionRemarksModal() {
            try {
                if (typeof window.closeModal === "function") {
                    window.closeModal("rejectionRemarksModal");
                } else {
                    // Fallback to direct manipulation if closeModal not available
                    const modal = document.getElementById('rejectionRemarksModal');
                    if (modal) {
                        modal.classList.remove('active');
                        setTimeout(() => {
                            modal.style.display = 'none';
                            document.body.style.overflow = 'auto';
                            document.body.classList.remove('modal-open');
                        }, 300);
                    }
                }
                
                // Reset current rejection data
                currentRejectionData = {
                    targetId: null,
                    targetName: null,
                    targetType: 'faculty',
                    isBulk: false,
                    targetIds: []
                };
            } catch (error) {
                // Silent error handling
            }
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
            
            // Handle bulk rejection
            if (currentRejectionData.isBulk) {
                try {
                    const userIds = [];
                    for (const id of currentRejectionData.targetIds) {
                        // id is user_id in this context (from data-faculty-id)
                        if (id) {
                            userIds.push(parseInt(id));
                        }
                    }
                    
                    if (userIds.length === 0) {
                        showToastNotification('Could not identify users to reject.', 'error');
                        closeRejectionRemarksModal();
                        return;
                    }
                    
                    // Get the currently selected school term from the filter
                    const schoolTermFilter = document.getElementById('schoolTermFilter');
                    const currentSchoolTerm = schoolTermFilter ? schoolTermFilter.value : '';
                    
                    // Use bulk_signatory_action.php for bulk rejections
                    const bulkPayload = {
                        applicant_user_ids: userIds,
                        action: 'Rejected',
                        designation_name: CURRENT_STAFF_POSITION || 'School Administrator',
                        remarks: additionalRemarks
                    };
                    if (rejectionReason) bulkPayload.reason_id = rejectionReason;
                    if (currentSchoolTerm && currentSchoolTerm.trim() !== '') {
                        bulkPayload.school_term = currentSchoolTerm.trim();
                    }
                    
                    const response = await fetch('../../api/clearance/bulk_signatory_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify(bulkPayload)
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        if (result.affected_rows === 0) {
                            showToastNotification('No records were updated. You may not be assigned as a signatory for the Faculty sector\'s clearance period.', 'warning');
                        } else {
                            showToastNotification(`✓ Successfully rejected clearance for ${result.affected_rows} faculty with remarks`, 'success');
                        }
                        fetchFaculty(); // Refresh table
            } else {
                        showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    console.error('Bulk rejection error:', e);
                    showToastNotification('Error during bulk rejection: ' + e.message, 'error');
                }
            } else {
                // Handle single rejection
                try {
                    // targetId is already user_id (from data-faculty-id)
                    const result = await sendSignatoryAction(currentRejectionData.targetId, 'Rejected', additionalRemarks, rejectionReason);
                    if (result.success) {
                        showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                        fetchFaculty(); // Refresh table
                    } else {
                        showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    console.error('Rejection error:', e);
                    showToastNotification('Error during rejection: ' + e.message, 'error');
                }
            }
            
            closeRejectionRemarksModal();
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
        
        // Update action buttons state based on can_perform_actions flag
        function updateActionButtonsState() {
            // Update individual row buttons
            const approveButtons = document.querySelectorAll('.approve-btn');
            const rejectButtons = document.querySelectorAll('.reject-btn');
            
            approveButtons.forEach(btn => {
                if (!canPerformSignatoryActions) {
                    btn.disabled = true;
                    btn.title = 'View Only Mode: You are not assigned as a signatory for this clearance period';
                }
            });
            
            rejectButtons.forEach(btn => {
                if (!canPerformSignatoryActions) {
                    btn.disabled = true;
                    btn.title = 'View Only Mode: You are not assigned as a signatory for this clearance period';
                }
            });
            
            // Update bulk buttons
            updateBulkButtons();
        }
        
        // Show/hide view-only indicator banner
        function updateViewOnlyIndicator() {
            // Remove existing indicator if any
            const existingIndicator = document.getElementById('viewOnlyIndicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            if (!canPerformSignatoryActions) {
                // Create and show view-only indicator
                const indicator = document.createElement('div');
                indicator.id = 'viewOnlyIndicator';
                indicator.className = 'alert alert-info';
                indicator.style.cssText = 'margin: 1rem 0; padding: 1rem; background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; color: #0c5460;';
                indicator.innerHTML = `<i class="fas fa-eye"></i> <strong>View Only Mode:</strong> You are viewing this sector's clearance data, but you are not assigned as a signatory for this clearance period. Approve/Reject actions are disabled.`;
                
                // Insert after the filters section or at the top of the content area
                const filtersSection = document.querySelector('.filters-section');
                if (filtersSection && filtersSection.nextSibling) {
                    filtersSection.parentNode.insertBefore(indicator, filtersSection.nextSibling);
                } else {
                    const contentArea = document.querySelector('.main-content');
                    if (contentArea) {
                        contentArea.insertBefore(indicator, contentArea.firstChild);
                    }
                }
            }
        }
        async function sendSignatoryAction(applicantUserId, action, remarks, reasonId = null){
            // Get the selected designation from the roleSelector dropdown
            const roleSelector = document.getElementById('roleSelector');
            let currentDesignation = CURRENT_STAFF_POSITION || 'School Administrator'; // Fallback
            
            if (roleSelector) {
                currentDesignation = roleSelector.value;
            }

            // Get the currently selected school term from the filter to ensure approval goes to the correct period
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            const currentSchoolTerm = schoolTermFilter ? schoolTermFilter.value : '';

            const payload = { 
                applicant_user_id: applicantUserId, 
                designation_name: currentDesignation, 
                action: action 
            };
            if (remarks && remarks.length) payload.remarks = remarks;
            if (reasonId) payload.reason_id = reasonId;
            // Include school_term if a specific term is selected
            if (currentSchoolTerm && currentSchoolTerm.trim() !== '') {
                payload.school_term = currentSchoolTerm.trim();
            }

            try {
                const response = await fetch('../../api/clearance/signatory_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    return {
                        success: false,
                        message: `Server error: ${response.status} ${response.statusText}`
                    };
                }
                
                return await response.json();
            } catch (error) {
                return {
                    success: false,
                    message: error.message || 'Network error: Failed to communicate with server'
                };
            }
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
                
                // Use all_terms if available (includes terms without clearance_periods), 
                // otherwise fall back to periods (for backward compatibility)
                const termsToUse = data.all_terms || data.periods || [];
                
                if (data.success && termsToUse.length > 0) {
                    const uniqueTerms = [...new Map(termsToUse.map(item => [
                        `${item.academic_year}-${item.semester_name}`, 
                        item
                    ])).values()];
                    
                    uniqueTerms.forEach(period => {
                        const option = document.createElement('option');
                        option.value = `${period.academic_year}|${period.semester_id}`; // Use a format the backend can parse
                        option.textContent = `${period.academic_year} - ${period.semester_name}`;
                        termSelect.appendChild(option);
                    });
                }
            } catch (error) { 
                console.error('Error loading school terms:', error); 
            }
        }
    </script>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
    
    <script src="../../assets/js/alerts.js"></script>
</body>
</html>
