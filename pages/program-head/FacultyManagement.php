<?php
// Online Clearance Website - Program Head Faculty Management

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
    <title>Faculty Management - Program Head Dashboard</title>
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
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-layout">
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-chalkboard-teacher"></i> Faculty Management</h2>
                            <p>Manage faculty within your assigned departments and sign their clearances</p>
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
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select" onchange="updateStatisticsByTerm()">
                                    <option value="">Loading Terms...</option>
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

                        <!-- Faculty Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-success" onclick="openFacultyBatchUpdateModal()">
                                        <i class="fas fa-users-cog"></i> Batch Update
                                    </button>
                                    <button class="btn btn-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <button class="selection-counter-display" id="selectionCounterPill" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-check-square"></i> <span id="selectionCounter">0 selected</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn" onclick="clearAllSelections()" disabled>
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                    <div class="bulk-buttons">
                                        <button class="btn btn-secondary" onclick="undoLastAction()" disabled>
                                            <i class="fas fa-undo"></i> Undo
                                        </button>
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
                                                    <span id="selectionCounter">0 selected</span>
                                                </th>
                                                <th>Employee Number</th>
                                                <th>Name</th>
                                                <th>Employment Status</th>
                                                <th>Account Status</th>
                                                <th>Clearance Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="facultyTableBody">
                                            <!-- Faculty data will be loaded dynamically from database -->
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
                    </div> <!-- closes content-wrapper -->
                </div> <!-- closes dashboard-main -->
                
                <!-- Activity Tracker Sidebar -->
                <div class="dashboard-sidebar">
                    <?php include '../../includes/components/activity-tracker.php'; ?>
                </div> <!-- closes dashboard-sidebar -->
            </div> <!-- closes dashboard-layout -->
        </div> <!-- closes main-content -->
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
    <script>
        let currentPage = 1;
        let entriesPerPage = 20;
        let currentSearch = '';
        let currentFilters = {};
        let CURRENT_STAFF_POSITION = '<?php echo isset($_SESSION['position']) ? addslashes($_SESSION['position']) : 'Program Head'; ?>';
        let canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.dashboard-main');
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

        // Clear all selections functionality
        function clearAllSelections() {
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('#facultyTableBody .faculty-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} shown selected`;
            } else {
                counter.textContent = `${selectedCount} selected`;
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button:not([onclick*="undo"])');
            
            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
            });
            
            updateSelectionCounter();
        }

        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }

        // Bulk Actions with Confirmation - Program Head as Signatory
        function approveSelected() {
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
                () => {
                    const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
                    const approvalPromises = Array.from(selectedCheckboxes).map(async checkbox => {
                        try {
                            const eid = checkbox.getAttribute('data-id');
                            const uid = await resolveUserIdFromEmployeeNumber(eid);
                            
                            if (uid) {
                                await sendSignatoryAction(uid, 'Approved');
                                const row = checkbox.closest('tr');
                                const badge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-rejected');
                                if (badge) {
                                    badge.textContent = 'Approved';
                                    badge.className = 'status-badge clearance-approved';
                                }
                            } else {
                                console.error('Could not resolve user ID for', eid);
                            }
                        } catch (e) {
                            console.error('Error approving clearance for', checkbox.getAttribute('data-id'), ':', e);
                        }
                    });

                    Promise.all(approvalPromises).then(() => fetchFaculty());
                    
                    showToastNotification(`✓ Successfully approved clearance for ${selectedCount} faculty`, 'success');
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
            
            // Get selected faculty IDs
            const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-id'));
            
            // Open rejection remarks modal for bulk rejection
            openRejectionRemarksModal(null, null, 'faculty', true, selectedIds);
        }

        // Individual faculty actions - Program Head as Signatory

        async function approveFacultyClearance(employeeId) {
            const row = document.querySelector(`.faculty-checkbox[data-id="${employeeId}"]`).closest('tr');
            const facultyUserId = row.getAttribute('data-faculty-id');

            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
            
            if (!clearanceBadge) {
                showToastNotification('No clearance to approve', 'warning');
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

        function rejectFacultyClearance(employeeId) {
             // Check if signatory actions are allowed
            if (!canPerformActions) {
                showToastNotification('You do not have permission to perform this action.', 'warning');
                return;
            }

            const row = document.querySelector(`.faculty-checkbox[data-id="${employeeId}"]`).closest('tr');
            const facultyUserId = row.getAttribute('data-faculty-id');
            const facultyName = row ? row.querySelector('td:nth-child(3)').textContent.trim() : 'Faculty Member';
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed');
            
            if (!clearanceBadge) {
                showToastNotification('No clearance to reject', 'warning');
                return;
            }
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(facultyUserId, facultyName, 'faculty', false);
        }

        // Filter functions
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;

            currentPage = 1; // Reset to first page on new filter/search
            currentSearch = searchTerm;
            fetchFaculty();

            const tableRows = document.querySelectorAll('#facultyTableBody tr'); // This part is now for UI feedback only
            tableRows.forEach(row => { // The actual filtering is server-side
                const employmentBadge = row.querySelector('.status-badge.employment-full-time, .status-badge.employment-part-time, .status-badge.employment-contract');
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                let shouldShow = true;
                
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
            });

            showToastNotification(`Filters applied. Fetching updated data...`, 'info');
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('employmentStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            // Apply filters to update table view
            applyFilters();
        }


        function updateStatisticsByTerm() {
            applyFilters();
        }

        function initializePagination() { // This function is now a placeholder, server-side pagination is used.
            updatePagination();
        }

        // Update pagination display
        function updatePagination() {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            const startEntry = (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, totalEntries);
            
            // Update pagination info
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${totalEntries} entries`;
            
            // Update page numbers
            updatePageNumbers(totalPages);
            
            // Update navigation buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage >= totalPages;
            
            // Show current page entries
            showCurrentPageEntries();
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
            updatePagination();
        }

        // Change page (previous/next)
        function changePage(direction) {
            const totalPages = Math.ceil(totalEntries / entriesPerPage);
            
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

        // Fetch faculty list from backend and build table body
        async function fetchFaculty() {
            const tableBody = document.getElementById('facultyTableBody');
            tableBody.innerHTML = `<tr><td colspan="7" class="loading-row"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><span>Loading faculty data...</span></div></td></tr>`;

            const accountStatus = document.getElementById('accountStatusFilter').value;
            const employmentStatus = document.getElementById('employmentStatusFilter').value;
            const search = currentSearch;

            // Force clearance_status to 'pending' to only show actionable items.
            let url = `../../api/staff/signatoryList.php?type=faculty&page=${currentPage}&limit=${entriesPerPage}&clearance_status=pending&clearance_status=rejected`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (accountStatus) url += `&account_status=${encodeURIComponent(accountStatus)}`;
            if (employmentStatus) url += `&employment_status=${encodeURIComponent(employmentStatus)}`;

            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                if (!data.success) {
                    showEmptyState('Error: ' + data.message);
                    return;
                }

                populateFacultyTable(data.faculty);
                updatePaginationUI(data.total, data.page, data.limit);
                updateStatistics(data.faculty);

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
            pageNumbersContainer.appendChild(span);
        }

        function goToPage(pageNum) {
            currentPage = pageNum;
            fetchFaculty();
        }

        function createFacultyRow(faculty) {
            const tr = document.createElement('tr');
            tr.setAttribute('data-faculty-id', faculty.user_id);
            
            const statusRaw = faculty.clearance_status;
            const clearanceKey = (statusRaw || 'unapplied').toLowerCase().replace(/ /g, '-');
            const accountStatus = (faculty.account_status || 'inactive').toLowerCase();
            
            let approveBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(faculty.clearance_status);
            let rejectBtnDisabled = !canPerformActions || !['Pending', 'Approved'].includes(faculty.clearance_status);
            let approveTitle = 'Approve Clearance';
            let rejectTitle = 'Reject Clearance';
            if (!canPerformActions) {
                approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a faculty signatory."; ?>';
            }
            
            tr.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="faculty-checkbox" data-id="${faculty.id}"></td>
                <td data-label="Employee Number:">${faculty.id}</td>
                <td data-label="Name:">${escapeHtml(faculty.name)}</td>
                <td data-label="Employment Status:"><span class="status-badge employment-${(faculty.employment_status || '').toLowerCase().replace(/ /g, '-')}">${escapeHtml(faculty.employment_status || 'N/A')}</span></td>
                <td data-label="Account Status:"><span class="status-badge account-${accountStatus}">${faculty.account_status || 'N/A'}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge clearance-${clearanceKey}">${faculty.clearance_status || 'N/A'}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
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
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <div>${message}</div>
                    </td>
                </tr> 
            `;
            updatePagination(0, 1, entriesPerPage);
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
            const checkboxes = document.querySelectorAll('.faculty-checkbox');
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

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.faculty-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const counterPill = document.getElementById('selectionCounterPill');
            const clearBtn = document.getElementById('clearSelectionBtn');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
                counterPill.classList.remove('has-selections');
                if (clearBtn) clearBtn.disabled = true;
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                counterPill.classList.add('has-selections');
                if (clearBtn) clearBtn.disabled = false;
            } else {
                counter.textContent = `${selectedCount} selected`;
                counterPill.classList.add('has-selections');
                if (clearBtn) clearBtn.disabled = false;
            }
        }

        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }

        // Edit faculty function
        function editFaculty(facultyId) {
            // Open edit faculty modal
            showToastNotification('Edit faculty functionality will be implemented', 'info');
        }

        // Delete faculty function
        function deleteFaculty(facultyId) {
            // Get faculty name from the table row
            const row = document.querySelector(`tr[data-faculty-id="${facultyId}"]`);
            const facultyName = row ? row.querySelector('td:nth-child(3)').textContent : 'Faculty Member';
            
            showConfirmationModal(
                'Delete Faculty',
                `Are you sure you want to delete ${facultyName}? This action cannot be undone.`,
                'Delete',
                'Cancel',
                async () => {
                    try {
                        // Call delete API
                        const response = await fetch('../../api/users/delete_faculty.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            credentials: 'include',
                            body: JSON.stringify({
                                user_id: facultyId
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Remove row from table
                            const row = document.querySelector(`tr[data-faculty-id="${facultyId}"]`);
                            if (row) {
                                row.remove();
                            }
                            
                            // Update statistics
                            updateStatisticsAfterDelete();
                            
                            showToastNotification(`Faculty ${facultyName} deleted successfully`, 'success');
                        } else {
                            showToastNotification('Failed to delete faculty: ' + result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error deleting faculty:', error);
                        showToastNotification('Error deleting faculty: ' + error.message, 'error');
                    }
                },
                'danger'
            );
        }

        // Update statistics after delete
        function updateStatisticsAfterDelete() {
            const totalFaculty = document.querySelectorAll('#facultyTableBody tr').length;
            const activeFaculty = document.querySelectorAll('#facultyTableBody tr .status-badge.account-active').length;
            const inactiveFaculty = document.querySelectorAll('#facultyTableBody tr .status-badge.account-inactive').length;
            
            document.getElementById('totalFaculty').textContent = totalFaculty;
            document.getElementById('activeFaculty').textContent = activeFaculty;
            document.getElementById('inactiveFaculty').textContent = inactiveFaculty;
        }

        // Check if Program Head is assigned to Faculty sector
        async function checkFacultySectorAssignment() {
            try {
                const response = await fetch('../../api/clearance/check_signatory_status.php?sector=Faculty', {
                    credentials: 'include'
                });
                const data = await response.json();
                return data.success && data.is_signatory;
            } catch (error) {
                console.error('Error checking faculty sector assignment:', error);
                return false;
            }
        }

        // Initialize sector-based access control
        async function initializeSectorAccessControl() {
            const isAssignedToFaculty = await checkFacultySectorAssignment();
            
            if (!isAssignedToFaculty) {
                // Disable Add Faculty button
                const addFacultyBtn = document.querySelector('.add-faculty-btn');
                if (addFacultyBtn) {
                    addFacultyBtn.disabled = true;
                    addFacultyBtn.title = 'You are not assigned to manage Faculty sector';
                    addFacultyBtn.style.opacity = '0.5';
                }
                
                // Show restriction message
                showToastNotification('You are not assigned to manage Faculty sector. You can view data but cannot edit or add faculty.', 'warning');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            fetchFaculty();
            loadRejectionReasons();
            loadSchoolTerms();
            loadCurrentStaffDesignation();
            
            // Add event listeners for checkboxes
            document.getElementById('facultyTableBody').addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                }
            });
            document.getElementById('searchInput').addEventListener('input', debouncedSearch);
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
            const reasonId = parseInt(rejectionReason);
            const additionalRemarks = remarksTextarea.value.trim();
            
            // Validation
            if (!rejectionReason) {
                showToastNotification('Please select a reason for rejection.', 'warning');
                return;
            }

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
            
            if (currentRejectionData.isBulk) {
                // server-side records
                try {
                    for (const id of currentRejectionData.targetIds) {
                        const uid = await resolveUserIdFromEmployeeNumber(id);
                        if (uid) { 
                            await sendSignatoryAction(uid, 'Rejected', additionalRemarks, reasonId); 
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
            } else {
                try {
                    const result = await sendSignatoryAction(currentRejectionData.targetId, 'Rejected', additionalRemarks, reasonId);
                    if (result.success) {
                        showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                        fetchFaculty();
                    } else {
                        showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    showToastNotification('An error occurred during rejection.', 'error');
                }
            }
            
            // Close modal
            closeRejectionRemarksModal();
        }

        // Helpers for backend calls
        async function resolveUserIdFromEmployeeNumber(employeeNumber){
            try{
                const r = await fetch('../../api/users/read.php?limit=5&search=' + encodeURIComponent(employeeNumber), { credentials:'include' });
                const data = await r.json();
                const arr = data.users || [];
                const match = arr.find(u => String(u.username) === String(employeeNumber));
                return match ? match.user_id : null;
            }catch(e){ return null; }
        }
        async function sendSignatoryAction(applicantUserId, action, remarks, reasonId = null) {
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
                        option.value = `${period.academic_year}-${period.semester_name}`;
                        
                        const termMap = { '1st': '1st Semester', '2nd': '2nd Semester', '3rd': '3rd Semester' };
                        const semLabel = termMap[period.semester_name] || period.semester_name || '';
                        const activeText = period.is_active ? ' (Active)' : '';
                        
                        option.textContent = `${period.academic_year} ${semLabel}${activeText}`;
                        termSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading school terms:', error);
                termSelect.innerHTML = '<option value="">Error loading terms</option>';
            }
        }

        async function loadCurrentStaffDesignation() {
            try {
                const response = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const data = await response.json();
                
                if (data.success) {
                    CURRENT_STAFF_POSITION = data.designation_name;
                    const positionInfo = document.getElementById('staffPositionInfo');
                    if (positionInfo) {
                        positionInfo.textContent = `Position: ${data.designation_name}`;
                    }
                } else {
                    const positionInfo = document.getElementById('staffPositionInfo');
                    if (positionInfo) positionInfo.textContent = 'Position: Unknown';
                }
            } catch (error) { console.error('Error loading staff designation:', error); }
        }

        function escapeHtml(unsafe) {
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
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
    
    <!-- Include Faculty Batch Update Modal -->
    <?php include '../../Modals/FacultyBatchUpdateModal.php'; ?>
</body>
</html>
