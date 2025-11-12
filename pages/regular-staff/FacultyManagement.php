<?php
// Online Clearance Website - Regular Staff Faculty Management

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
                                <input type="text" id="searchInput" placeholder="Search faculty by name, ID, or department...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Employment Status Filter -->
                                <select id="employmentStatusFilter" class="filter-select">
                                    <option value="">All Employment Status</option>
                                    <option value="">Loading...</option>
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <option value="">Loading...</option>
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select" >
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

                        <!-- Faculty Table with Integrated Bulk Actions -->
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
                                        <!--
                                        <button class="btn btn-secondary" onclick="undoLastAction()" disabled>
                                            <i class="fas fa-undo"></i> Undo
                                        </button>
                                        -->
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
                            <div class="pagination-info" id="paginationInfoContainer">
                                <span id="paginationInfo">Showing 1 to 20 of 0 entries</span>
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

    <script src="../../assets/js/activity-tracker.js"></script>
    
    <!-- Include Clearance Button Manager -->
    <script src="../../assets/js/clearance-button-manager.js"></script>
    
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <?php include '../../Modals/ClearanceProgressModal.php'; ?>
    <script>
        let currentPage = 1;
        let entriesPerPage = 20;
        let currentSearch = '';
        let currentFilters = {};
        let totalEntries = 0;

        let CURRENT_STAFF_POSITION = '<?php echo isset($_SESSION['position']) ? addslashes($_SESSION['position']) : 'Staff'; ?>';
        let canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
        
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
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
        function toggleSelectAll(checked) {
            const facultyCheckboxes = document.querySelectorAll('#facultyTableBody .faculty-checkbox');
            facultyCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                // Only toggle visible and enabled rows, respecting current filters
                if (row && row.style.display !== 'none' && !checkbox.disabled) {
                    checkbox.checked = checked;
                }
            });
            updateBulkButtons();
        }

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const allCheckboxes = document.querySelectorAll('#facultyTableBody .faculty-checkbox:not(:disabled)');
            const checkedCount = document.querySelectorAll('#facultyTableBody .faculty-checkbox:not(:disabled):checked').length;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
            }
        }

        // Bulk Actions - Staff can only approve/reject clearances
        function approveSelected() {
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
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
                    const applicantUserIds = Array.from(selectedCheckboxes).map(cb => {
                        const row = cb.closest('tr');
                        return row ? parseInt(row.getAttribute('data-faculty-id')) : null;
                    }).filter(id => id !== null);

                    if (applicantUserIds.length === 0) {
                        showToastNotification('Could not identify users to approve.', 'error');
                        return;
                    }

                    // Single API call to the new bulk endpoint
                    try {
                        const response = await fetch('../../api/clearance/bulk_signatory_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                applicant_user_ids: applicantUserIds,
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
                        fetchFaculty(); // Refresh the entire table with a single call
                    }
                },
                'success'
            );
        }

        function rejectSelected() {
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
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

        async function fetchFaculty() {
            const tableBody = document.getElementById('facultyTableBody');
            tableBody.innerHTML = `<tr><td colspan="7" class="loading-row"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><span>Loading faculty data...</span></div></td></tr>`;

            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const employmentStatus = document.getElementById('employmentStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            const search = document.getElementById('searchInput').value;

            const url = new URL('../../api/clearance/signatoryList.php', window.location.href);

            // Base parameters
            url.searchParams.append('type', 'faculty');
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            // Optional filters
            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);
            if (employmentStatus) url.searchParams.append('employment_status', employmentStatus);
            
            try {
                const response = await fetch(url.toString(), { credentials: 'include' });
                const data = await response.json();

                if (!data.success) {
                    showEmptyState('Error: ' + data.message);
                    return;
                }

                populateFacultyTable(data.faculty);
                renderPagination(data.total, data.page, data.limit);
                updateStatistics(data.faculty);

            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">A network error occurred.</td></tr>`;
                console.error("Fetch error:", error);
            }
        }

        function createFacultyRow(faculty) {
            const tr = document.createElement('tr');
            tr.setAttribute('data-faculty-id', faculty.user_id);
            tr.setAttribute('data-signatory-id', faculty.signatory_id);
            
            const statusRaw = faculty.clearance_status;
            const clearanceKey = (statusRaw || 'unapplied').toLowerCase().replace(/ /g, '-');
            const accountStatus = (faculty.account_status || 'inactive').toLowerCase();
            
            let approveBtnDisabled = faculty.clearance_status === 'Unapplied' || faculty.clearance_status === 'Approved' || faculty.clearance_status === '' || !canPerformActions;
            let rejectBtnDisabled = faculty.clearance_status === 'Unapplied' || faculty.clearance_status === 'Approved' || faculty.clearance_status === '' || !canPerformActions;
            // Disable checkbox for 'Unapplied' and 'Approved' statuses (same logic as buttons)
            let checkboxDisabled = faculty.clearance_status === 'Unapplied' || faculty.clearance_status === 'Approved' || faculty.clearance_status === '' || !canPerformActions;
            let approveTitle = 'Approve Clearance';
            let rejectTitle = 'Reject Clearance';
            if (!canPerformActions) {
                approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a faculty signatory."; ?>';
            }
            
            tr.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="faculty-checkbox" data-id="${faculty.id}" ${checkboxDisabled ? 'disabled' : ''}></td>
                <td data-label="Employee Number:">${faculty.id}</td>
                <td data-label="Name:">${escapeHtml(faculty.name)}</td>
                <td data-label="Employment Status:"><span class="status-badge employment-${(faculty.employment_status || '').toLowerCase().replace(/ /g, '-')}">${escapeHtml(faculty.employment_status || 'N/A')}</span></td>
                <td data-label="Account Status:"><span class="status-badge account-${accountStatus}">${faculty.account_status || 'N/A'}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge clearance-${clearanceKey}">${faculty.clearance_status || 'N/A'}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        <button class="bt   n-icon view-progress-btn" onclick="viewClearanceProgress('${faculty.id}')" title="View Clearance Progress">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button class="btn-icon approve-btn" onclick="approveFacultyClearance(this)" title="${approveTitle}" ${approveBtnDisabled ? 'disabled' : ''}>
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon reject-btn" onclick="rejectFacultyClearance(this)" title="${rejectTitle}" ${rejectBtnDisabled ? 'disabled' : ''}>
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
        
        function escapeHtml(unsafe) {
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        
        function viewClearanceProgress(facultyId) {
            // Get faculty name from the table row
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            // Open the clearance progress modal
            openClearanceProgressModal(facultyId, 'faculty', facultyName, schoolTerm);
        }

        function populateFacultyTable(facultyList) {
            const tbody = document.getElementById('facultyTableBody');
            tbody.innerHTML = '';

            if (!facultyList || facultyList.length === 0) {
                showEmptyState('No faculty data found matching your criteria.');
                updateBulkButtons(); // Update buttons even when empty
                return;
            }

            facultyList.forEach(faculty => {
                const row = createFacultyRow(faculty);
                tbody.appendChild(row);
            });
            
            updateBulkButtons(); // Update buttons after populating table
        }

        async function approveFacultyClearance(button) {
            const row = button.closest('tr');
            const userId = row.getAttribute('data-faculty-id');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;

            // Fetch the designation to create a dynamic remark.
            let designationName = CURRENT_STAFF_POSITION; // Fallback
            try {
                const desigResponse = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const desigData = await desigResponse.json();
                if (desigData.success) {
                    designationName = desigData.designation_name;
                }
            } catch (e) { /* Ignore error, use fallback */ }
            const approvalRemark = `Approved by ${designationName}`;

            showConfirmationModal('Approve Clearance', `Approve clearance for ${facultyName}?`, 'Approve', 'Cancel', async () => {
                const result = await sendSignatoryAction(userId, 'Approved', approvalRemark);
                if (result.success) {
                    showToastNotification('Faculty clearance approved successfully', 'success');
                    fetchFaculty(); // Refresh data
                } else {
                    showToastNotification('Failed to approve: ' + (result.message || 'Unknown error'), 'error');
                }
            }, 'success');
        }

        function showEmptyState(message) {
            const tbody = document.getElementById('facultyTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <div>${message}</div>
                    </td>
                </tr>
            `;
            updateStatistics([]);
        }

        function updateStatistics(facultyList) {
            let total = facultyList.length;
            let active = 0, inactive = 0, resigned = 0;
            
            facultyList.forEach(faculty => {
                const status = (faculty.account_status || 'inactive').toLowerCase();
                if (status === 'active') active++;
                else if (status === 'inactive') inactive++;
                else if (status === 'resigned') resigned++;
            });
            
            document.getElementById('totalFaculty').textContent = total;
            document.getElementById('activeFaculty').textContent = active;
            document.getElementById('inactiveFaculty').textContent = inactive;
            document.getElementById('resignedFaculty').textContent = resigned;
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
        
        // Filter functions (simplified for now)
        function applyFilters() {
            currentPage = 1;
            fetchFaculty();
        }
        
        function triggerExportModal() {
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else {
                console.error('Export modal function not found');
                showToastNotification('Export modal not available', 'error');
            }
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
                        // Update the global variable
                        CURRENT_STAFF_POSITION = data.designation_name;
                        
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

        async function loadSchoolTerms() {
            const url = `../../api/clearance/get_filter_options.php?type=school_terms`;
            await populateFilter('schoolTermFilter', url, 'All School Terms');
        }

        async function loadEmploymentStatuses() {
            const employementStatus = document.getElementById('employmentStatusFilter');
            employementStatus.innerHTML = '<option value="">Loading Employement Statuses...</option>';
            const url = new URL('../../api/clearance/get_filter_options.php', window.location.href);
            url.searchParams.append('type', 'employment_statuses');
            await populateFilter('employmentStatusFilter', url.toString(), 'All Employment Statuses');
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

        // Tab navigation functions
        function switchFacultyTab(button) {
            const status = button.getAttribute('data-status');
            window.currentTabStatus = status;
            
            // Update tab appearance
            document.querySelectorAll('.tab-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            button.classList.add('active');
            
            // Update mobile select
            const mobileSelect = document.getElementById('facultyTabSelect');
            if (mobileSelect) {
                mobileSelect.value = status;
            }
            
            // Apply tab filter
            applyTabFilter(status);
        }

        function handleTabSelectChange(select) {
            const status = select.value;
            window.currentTabStatus = status;
            
            // Update tab pills
            document.querySelectorAll('.tab-pill').forEach(pill => {
                pill.classList.remove('active');
                if (pill.getAttribute('data-status') === status) {
                    pill.classList.add('active');
                }
            });
            
            // Apply tab filter
            applyTabFilter(status);
        }

        function applyTabFilter(status) {
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            
            tableRows.forEach(row => {
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                if (!status || status === '') {
                    // Show all rows
                    row.style.display = '';
                } else {
                    // Filter by status
                    if (accountBadge && accountBadge.classList.contains(`account-${status}`)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        // Bulk selection functions
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
                fullTime: document.getElementById('filterFullTime').checked,
                partTime: document.getElementById('filterPartTime').checked,
                partTimeFullLoad: document.getElementById('filterPartTimeFullLoad').checked,
                active: document.getElementById('filterActive').checked,
                inactive: document.getElementById('filterInactive').checked,
                resigned: document.getElementById('filterResigned').checked,
                pending: document.getElementById('filterPending').checked,
                approved: document.getElementById('filterApproved').checked,
                rejected: document.getElementById('filterRejected').checked
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
            const checkboxes = document.querySelectorAll('.faculty-checkbox:not(:disabled)');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                
                // Skip hidden rows (respects table filters)
                if (!row || row.style.display === 'none') {
                    checkbox.checked = false;
                    return;
                }
                
                checkbox.checked = true;
                selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            updateSelectAllCheckbox(); // Update select all checkbox state
            showToastNotification(`Selected all ${selectedCount} visible faculty`, 'success');
        }

        function selectFacultyByFilters(filters) {
            const checkboxes = document.querySelectorAll('.faculty-checkbox:not(:disabled)');
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
                const shouldSelect = employmentMatch && accountMatch && statusMatch;
                checkbox.checked = shouldSelect;
                if (shouldSelect) selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            updateSelectAllCheckbox(); // Update select all checkbox state
            showToastNotification(`Selected ${selectedCount} faculty based on filters`, 'success');
        }

        function resetBulkSelectionFilters() {
            document.getElementById('filterFullTime').checked = false;
            document.getElementById('filterPartTime').checked = false;
            document.getElementById('filterPartTimeFullLoad').checked = false;
            document.getElementById('filterActive').checked = false;
            document.getElementById('filterInactive').checked = false;
            document.getElementById('filterResigned').checked = false;
            document.getElementById('filterPending').checked = false;
            document.getElementById('filterApproved').checked = false;
            document.getElementById('filterRejected').checked = false;
        }
        
        function clearAllSelections() {
            const checkboxes = document.querySelectorAll('.faculty-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectionCounter();
            updateBulkButtons();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.faculty-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const counterPill = document.getElementById('selectionCounterPill');
            const clearBtn = document.getElementById('clearSelectionBtn');
            
            if (selectedCount === 0) { 
                counter.textContent = '0 selected';
                if (counterPill) counterPill.classList.remove('has-selections');
                if (clearBtn) clearBtn.disabled = true;
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                if (counterPill) counterPill.classList.add('has-selections');
                if (clearBtn) clearBtn.disabled = false;
            } else {
                counter.textContent = `${selectedCount} selected`;
                if (counterPill) counterPill.classList.add('has-selections');
                if (clearBtn) clearBtn.disabled = false;
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            // Check if signatory actions are allowed from PHP
            const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;

            bulkButtons.forEach(button => {
                // Disable if no selections OR if signatory actions are not allowed
                button.disabled = checkedBoxes.length === 0 || !canPerformActions;

                // Add tooltip for disabled state due to permissions
                if (!canPerformActions && checkedBoxes.length > 0) {
                    button.title = 'Cannot perform action: ' + ('<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a faculty signatory."; ?>');
                } else if (checkedBoxes.length === 0) {
                    button.title = 'Select faculty to perform actions';
                } else {
                    button.title = '';
                }
            });
            
            updateSelectionCounter();
        }

        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            document.getElementById('employmentStatusFilter').value = '';
            fetchFaculty();
            showToastNotification('All filters cleared', 'info');
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
            fetchFaculty();
        }

        function changePage(direction) {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
             } else if (direction === 'next') {
                currentPage++;
            }
            
            fetchFaculty();
        }

        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
        }
        function scrollToTop() {
            const tableWrapper = document.getElementById('facultyTableWrapper');
            tableWrapper.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show scroll to top button when scrolled
        document.getElementById('facultyTableWrapper')?.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollToTopBtn');
            if (this.scrollTop > 200) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        // Export functionality - Remove duplicate, use the one above

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
                    const currentPeriod = data.active_periods[0];
                    const termMap = {
                        '1st': '1st Semester',
                        '2nd': '2nd Semester',
                        '3rd': '3rd Semester',
                        '1st Semester': '1st Semester',
                        '2nd Semester': '2nd Semester',
                        '3rd Semester': '3rd Semester',
                        'Summer': 'Summer'
                    };
                    const semLabel = termMap[currentPeriod.semester_name] || currentPeriod.semester_name || '';
                    if (yearEl) yearEl.textContent = currentPeriod.school_year;
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize bulk buttons state
            updateBulkButtons();
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                    updateSelectAllCheckbox();
                }
            });

            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();
            
            loadCurrentStaffDesignation();
            loadCurrentPeriod();

            
            await Promise.all([
            loadSchoolTerms(),
            loadClearanceStatuses(),
            loadAccountStatuses(),
            loadEmploymentStatuses(),
            loadRejectionReasons()
            ])

            await setDefaultSchoolTerm();
            fetchFaculty();
            
            // Load current clearance period


            document.getElementById('searchInput').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    applyFilters();
                }
            });

            // Initialize tab status
            window.currentTabStatus = '';
        });

        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            userId: null,
            targetName: null,
            targetType: 'faculty',
            isBulk: false,
            targetIds: []
        };

        function openRejectionRemarksModal(targetId, targetName, targetType = 'faculty', isBulk = false, targetIds = [], existingRemarks = '', existingReasonId = '') {
            currentRejectionData = { // Note: targetId is now userId for individual, or null for bulk
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
            const submitButton = modal.querySelector('.modal-action-primary');
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');

            // Pre-fill form if data exists, otherwise reset
            reasonSelect.value = existingReasonId || '';
            remarksTextarea.value = existingRemarks || '';

            // Update display
            if (isBulk) {
                targetNameElement.textContent = `Rejecting: ${targetIds.length} Selected ${targetType === 'student' ? 'Students' : 'Faculty'}`;
            } else {
                targetNameElement.textContent = `Rejecting: ${targetName}`;
            }

            // Update button text if editing
            if (existingReasonId || existingRemarks) {
                submitButton.textContent = 'Update Rejection';
            } else {
                submitButton.textContent = 'Close Clearance';
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
            currentRejectionData = { // Reset to initial state
                userId: null,
                targetName: null,
                targetType: 'faculty',
                isBulk: false,
                targetIds: []
            };
        }

        async function rejectFacultyClearance(button) {
            // Check if signatory actions are allowed
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
                return;
            }
            
            const row = button.closest('tr');
            const userId = row.getAttribute('data-faculty-id');
            const facultyName = row ? row.querySelector('td:nth-child(3)').textContent : 'Faculty Member';
            // Open rejection remarks modal for individual rejection

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

            openRejectionRemarksModal(userId, facultyName, 'faculty', false, [], existingRemarks, existingReasonId);




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
            const reasonId = reasonSelect.value;
            
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
            
            // Use the new bulk endpoint for bulk rejections
            if (currentRejectionData.isBulk) {
                const employeeNumbers = currentRejectionData.targetIds;
                const userIds = [];
                for (const eid of employeeNumbers) {
                    const uid = await resolveUserIdFromEmployeeNumber(eid);
                    if (uid) userIds.push(uid);
                }

                if (userIds.length === 0) {
                    showToastNotification('Could not identify users to reject.', 'error');
                    closeRejectionRemarksModal();
                    return;
                }

                try {
                    const response = await fetch('../../api/clearance/bulk_signatory_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({
                            applicant_user_ids: userIds,
                            action: 'Rejected',
                            designation_name: CURRENT_STAFF_POSITION,
                            remarks: additionalRemarks,
                            reason_id: reasonId
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToastNotification(`Successfully rejected clearance for ${result.affected_rows} faculty.`, 'success');
                    } else {
                        throw new Error(result.message || 'Bulk rejection failed.');
                    }
                } catch (error) {
                    console.error('Bulk rejection error:', error);
                    showToastNotification(error.message, 'error');
                } finally {
                    closeRejectionRemarksModal(); // Close the modal
                    fetchFaculty(); // Refresh the table
                }
            } else {
                // Individual rejection - no need to resolve ID, it's already the user_id
                try {
                    const result = await sendSignatoryAction(currentRejectionData.targetId, 'Rejected', additionalRemarks, reasonId);
                    if (result.success) {
                        showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                        fetchFaculty(); // Refresh the table to update button states
                    } else {
                        showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    console.error("Error during individual rejection:", e);
                    showToastNotification('An error occurred during rejection.', 'error');
                } finally {
                    closeRejectionRemarksModal(); // Close the modal
                }
            }
        }

        async function resolveUserIdFromEmployeeNumber(identifier){
            try {
                const r = await fetch('../../api/users/read.php?limit=5&search=' + encodeURIComponent(identifier), { credentials:'include' });
                const data = await r.json();
                const arr = data.users || [];
                const match = arr.find(u => String(u.username) === String(identifier));
                return match ? match.user_id : null;
            }catch(e){ return null; }
        }
        async function sendSignatoryAction(applicantUserId, action, remarks, reasonId = null) {
            // Fetch the current staff's actual designation from the API to ensure accuracy.
            let designationName = CURRENT_STAFF_POSITION; // Fallback
            try {
                const desigResponse = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const desigData = await desigResponse.json();
                if (desigData.success) {
                    designationName = desigData.designation_name;
                }
            } catch (e) { /* Ignore error, use fallback */ }

            const payload = { 
                applicant_user_id: applicantUserId, 
                action: action,
                designation_name: designationName
            };
            if (remarks && remarks.length) payload.remarks = remarks;
            if (reasonId) payload.reason_id = reasonId;

            const response = await fetch('../../api/clearance/signatory_action.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)});
            return await response.json();
        }

        // Load rejection reasons into the modal dropdown
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
    </script>
    <script src="../../assets/js/alerts.js"></script>
    
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
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
</body>
</html>
