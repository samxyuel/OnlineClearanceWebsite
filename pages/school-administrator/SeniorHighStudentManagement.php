<?php
// Online Clearance Website - School Administrator Senior High School Student Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the controller logic which handles all authorization and data fetching.
require_once __DIR__ . '/../../controllers/StudentManagementController.php';

// The controller function acts as a "gatekeeper". If it doesn't exit, the user is authorized.
handleStudentManagementPageRequest('Senior High School');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior High School Student Management - School Administrator Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
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
                            <h2><i class="fas fa-graduation-cap"></i> Senior High School Student Management</h2>
                            <p>Manage senior high school students and sign their clearances across all departments</p>
                            <div class="department-scope-info">
                                <i class="fas fa-shield-alt"></i>
                                <span>Scope: Senior High School Departments (School-wide Access)</span>
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
                        </div>

                        <!-- Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalStudents">1,234</h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeStudents">1,156</h3>
                                    <p>Active</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveStudents">78</h3>
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
                                <i class="fas fa-search" style="pointer-events: none;"></i>
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or department...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Department Filter -->
                                <select id="departmentFilter" class="filter-select">
                                    <option value="">All Departments</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- Program Filter -->
                                <select id="programFilter" class="filter-select">
                                    <option value="">All Programs</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- Year Level Filter -->
                                <select id="yearLevelFilter" class="filter-select">
                                    <option value="">All Year Levels</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>

                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">All School Terms</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
                                    <!-- Options will be loaded dynamically -->
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
                                    <button class="btn btn-outline-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
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
                                        <button class="btn btn-success" onclick="bulkApproveSignatories()" disabled>
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" onclick="bulkRejectSignatories()" disabled>
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
                                <div class="students-table-wrapper" id="studentsTableWrapper">
                                    <table id="studentsTable" class="students-table">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-column">
                                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this.checked)" title="Select all visible">
                                                </th>
                                                <th>Student Number</th>
                                                <th>Name</th>
                                                <th>Program</th>
                                                <th>Year Level</th>
                                                <th>Section</th>
                                                <th>Account Status</th>
                                                <th>Clearance Form Progress</th>
                                                <th>Clearance Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentsTableBody">
                                            <!-- Data will be populated by JavaScript from API -->
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

            </div>
        </div>
    </main>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Modals -->
    <?php include '../../Modals/SHSStudentRegistryModal.php'; ?>
    <?php include '../../Modals/SHSEditStudentModal.php'; ?>
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
                    <h4 id="rejectionTargetName">Rejecting: [Student Name]</h4>
                    <p class="rejection-type">Type: <span id="rejectionType">Student</span></p>
                </div>
                
                <div class="remarks-section">
                    <div class="form-group">
                        <label for="rejectionReason">Reason for Rejection:</label>
                        <select id="rejectionReason" class="form-control" onchange="handleReasonChange()">
                            <option value="">Select a reason...</option>
                            <option value="incomplete_documents">Incomplete Documents</option>
                            <option value="unpaid_fees">Unpaid Fees</option>
                            <option value="academic_requirements">Academic Requirements Not Met</option>
                            <option value="disciplinary_issues">Disciplinary Issues</option>
                            <option value="missing_clearance">Missing Clearance Items</option>
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

    <script>
        // --- State Management ---
        // This will be dynamically updated by the role selector
        // For School Administrator, default to 'School Administrator' designation
        let CURRENT_STAFF_POSITION = <?php echo json_encode(!empty($GLOBALS['userSignatoryDesignations']) ? $GLOBALS['userSignatoryDesignations'][0]['designation_name'] : 'School Administrator'); ?>;
        // Flag to track if user can perform signatory actions (for view-only mode)
        let canPerformSignatoryActions = false; // Default to false for safety
        
        // Global flag to track if there's an active clearance period for this sector
        let hasActiveClearancePeriod = false;

        // Handle role changes by re-applying all filters, which triggers a fetch
        function handleRoleChange() {
            const roleSelector = document.getElementById('roleSelector');
            if (roleSelector) {
                CURRENT_STAFF_POSITION = roleSelector.value;
                applyFilters(); // Re-fetch data from server with the new role
            }
        }

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

        // Select all functionality
        function toggleSelectAll(checked) {
            // Use the parameter if provided, otherwise get from checkbox
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const isChecked = checked !== undefined ? checked : (selectAllCheckbox ? selectAllCheckbox.checked : false);
            const studentCheckboxes = document.querySelectorAll('#studentsTableBody .student-checkbox:not(:disabled)');
            
            studentCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                // Only toggle visible and enabled rows, respecting current filters
                if (row && row.style.display !== 'none') {
                    checkbox.checked = isChecked;
                }
            });
            
            updateBulkButtons();
            updateSelectionCounter();
        }


        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            const hasSelection = checkedBoxes.length > 0;
            
            // Get all bulk buttons (only Approve and Reject for School Administrator)
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            bulkButtons.forEach(button => {
                // Check if this is Approve or Reject button by checking onclick attribute
                const onclickAttr = button.getAttribute('onclick') || '';
                const isApproveOrReject = onclickAttr.includes('bulkApproveSignatories') || 
                                         onclickAttr.includes('bulkRejectSignatories');
                
                if (isApproveOrReject) {
                    // Approve/Reject buttons: require selection, active period, AND permission
                    const canEnable = hasSelection && hasActiveClearancePeriod && canPerformSignatoryActions;
                    button.disabled = !canEnable;
                    
                    if (!canEnable && hasSelection) {
                        // If there's selection but buttons are disabled, add tooltip
                        if (!hasActiveClearancePeriod) {
                            button.title = 'No active clearance period for this sector';
                        } else if (!canPerformSignatoryActions) {
                            button.title = 'You do not have permission to take action';
                        }
                    } else {
                        button.title = '';
                    }
                } else {
                    // Other buttons (if any): only require selection
                    button.disabled = !hasSelection;
                    button.title = '';
                }
            });
            
            updateSelectionCounter();
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

        // Enhanced notification function (using external alert system)
        function showNotification(message, type = 'info') {
            showToastNotification(message, type);
        }

        // Undo last action function
        function undoLastAction() {
            showToastNotification('Undo functionality will be implemented in the next version', 'info');
        }

        // Show info toast function
        function showInfoToast(message) {
            showToastNotification(message, 'info');
        }


        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeStudents');
            const inactiveCount = document.getElementById('inactiveStudents');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
        }

        // Individual student actions - School Administrator as Signatory
        function editStudent(studentId) {
            openEditStudentModal(studentId);
        }

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
            loadStudentsData();
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('departmentFilter').value = '';
            document.getElementById('programFilter').value = '';
            document.getElementById('yearLevelFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            showInfoToast('All filters cleared');
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;

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
                currentPage--;
            } else if (direction === 'next') {
                currentPage++;
            }
            loadStudentsData();
        }

        // Change entries per page
        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
            loadStudentsData();
        }

        // Scroll to top functionality
        function scrollToTop() {
            const tableWrapper = document.getElementById('studentsTableWrapper');
            tableWrapper.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show scroll to top button when scrolled
        document.getElementById('studentsTableWrapper').addEventListener('scroll', function() {
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

        // Basic CSV export function (kept for backwards compatibility if needed)
        function exportStudentsToCSV() {
            const table = document.getElementById('studentsTable');
            const rows = table.querySelectorAll('tbody tr');
            
            let csvContent = 'Student Number,Name,Program,Year Level,Section,Account Status,Clearance Form Progress,Clearance Status\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 8) {
                    const studentNumber = cells[1].textContent.trim();
                    const name = cells[2].textContent.trim();
                    const program = cells[3].textContent.trim();
                    const yearLevel = cells[4].textContent.trim();
                    const section = cells[5].textContent.trim();
                    const accountStatus = cells[6].textContent.trim();
                    const clearanceProgress = cells[7].textContent.trim();
                    const clearanceStatus = cells[8].textContent.trim();
                    
                    csvContent += `"${studentNumber}","${name}","${program}","${yearLevel}","${section}","${accountStatus}","${clearanceProgress}","${clearanceStatus}"\n`;
                }
            });
            
            // Create and download the CSV file
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'senior_high_students_export.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showToastNotification('Senior High students exported to CSV successfully', 'success');
        }

        // Load senior high students data from API
         async function loadStudentsData() {
            const tableBody = document.getElementById('studentsTableBody');
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading students...</td></tr>`;

            // Get filter values
            const search = document.getElementById('searchInput').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const programId = document.getElementById('programFilter').value;
            const yearLevel = document.getElementById('yearLevelFilter').value;
            const departments = document.getElementById('departmentFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;

            // Program Head for College is a specific case of a signatory list.
            // We use the central signatoryList API.
            const url = new URL('../../api/clearance/signatoryList.php', window.location.href);
            url.searchParams.append('type', 'student'); 
            url.searchParams.append('sector', 'Senior High School'); 
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (programId) url.searchParams.append('program_id', programId);
            if (yearLevel) url.searchParams.append('year_level', yearLevel);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (departments) url.searchParams.append('departments', departments);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);

            // Pass the current role/designation for filtering
            if (CURRENT_STAFF_POSITION) url.searchParams.append('designation_filter', CURRENT_STAFF_POSITION);

            try {
                const response = await fetch(url.toString(), {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    // Update can_perform_actions flag from API response
                    canPerformSignatoryActions = data.can_perform_actions === true; // Only enable if explicitly true
                    
                    populateStudentsTable(data.students);
                    updateStatisticsUI(data.stats);
                    updatePaginationUI(data.total, data.page, data.limit);
                    updateActionButtonsState(); // Update button states based on can_perform_actions
                    updateViewOnlyIndicator(); // Show/hide view-only indicator
                } else {
                    showToastNotification('Failed to load students data: ' + data.message, 'error');
                    tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading Senior Highschool students:', error);
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

            // Check if user existed during the selected term
            const userExisted = student.user_existed_during_term !== false; // Default to true if not provided
            
            // Clearance Form Progress (end user's overall progress)
            let clearanceProgress = student.clearance_form_progress || 'Unapplied';
            if (!userExisted) {
                clearanceProgress = 'N/A';
            }
            const clearanceProgressClass = `clearance-${clearanceProgress.toLowerCase().replace(/ /g, '-')}`;
            
            // Clearance Status (signatory's action status)
            let clearanceStatus = student.clearance_status || 'Unapplied';
            if (!userExisted) {
                clearanceStatus = 'N/A';
            }
            const clearanceStatusClass = `signatory-${clearanceStatus.toLowerCase().replace(/ /g, '-')}`;
            
            // Capture the currently selected school term from the filters so we can
            // display clearance progress scoped to that term when the user opens the modal.
            const currentSchoolTerm = document.getElementById('schoolTermFilter') ? document.getElementById('schoolTermFilter').value : '';

            // Determine button titles and states based on clearance status
            // Disable buttons if user cannot perform signatory actions (view-only mode)
            const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus) && userExisted && canPerformSignatoryActions;
            const rejectButtonTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Signatory';

            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.setAttribute('data-student-id', student.id);
            row.setAttribute('data-form-id', student.clearance_form_id);
            row.setAttribute('data-signatory-id', student.signatory_id);
            
            // Add class for non-existent users
            if (!userExisted) {
                row.classList.add('user-not-existed');
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

            row.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="student-checkbox" data-id="${student.id}" data-user-id="${student.user_id}" ${!userExisted ? 'disabled' : ''}></td>
                <td data-label="Student Number:">${student.id}</td>
                <td data-label="Name:">${student.name}</td>
                <td data-label="Program:">${student.program || 'N/A'}</td>
                <td data-label="Year Level:">${student.year_level || 'N/A'}</td>
                <td data-label="Section:">${student.section || 'N/A'}</td>
                <td data-label="Account Status:"><span class="status-badge ${accountStatusClass}">${accountStatusText}</span></td>
                <td data-label="Clearance Progress:" class="clearance-status-cell">${clearanceProgressContent}</td>
                <td data-label="Clearance Status:" class="clearance-status-cell">${clearanceStatusContent}</td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('${student.id}', '${escapeHtml(student.name)}', '${escapeHtml(currentSchoolTerm)}')" title="View Clearance Progress">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button class="btn-icon edit-btn" onclick="editStudent('${student.user_id}')" title="Edit Student">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon approve-btn" onclick="approveSignatory('${student.user_id}')" title="Approve Signatory" ${!isActionable ? 'disabled' : ''}>
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon reject-btn" onclick="rejectSignatory('${student.user_id}')" title="${rejectButtonTitle}" ${!isActionable ? 'disabled' : ''}>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            `;
            return row;
        }

        function escapeHtml(unsafe) {
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }


        // Update statistics
        function updateStatisticsUI(stats) {
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
        }

        // Map database status values to display values
        function mapClearanceStatus(status) {
            if (!status || status === 'Unapplied' || status === 'unapplied') {
                return 'unapplied';
            } else if (status === 'Pending' || status === 'Processing' || status === 'Approved' || status === 'Rejected') {
                return 'in-progress';
            } else if (status === 'Complete' || status === 'complete') {
                return 'complete';
            }
            return 'unapplied'; // default
        }

        function mapClearanceStatusDisplay(status) {
            if (!status || status === 'Unapplied' || status === 'unapplied') {
                return 'Unapplied';
            } else if (status === 'Pending' || status === 'Processing' || status === 'Approved' || status === 'Rejected') {
                return 'In Progress';
            } else if (status === 'Complete' || status === 'complete') {
                return 'Complete';
            }
            return 'Unapplied'; // default
        }

        // Check if current user is signatory for this sector
        async function checkSignatoryStatus(sector) {
            try {
                const response = await fetch(`../../api/clearance/check_signatory_status.php?sector=${encodeURIComponent(sector)}`, {
                    credentials: 'include'
                });
                const data = await response.json();
                return data.success && data.is_signatory;
            } catch (error) {
                console.error('Error checking signatory status:', error);
                return false;
            }
        }

        function viewClearanceProgress(studentId, studentName, schoolTerm = '') {
            // Forward the selected school term (if any) so the modal can show
            // the clearance progress scoped to that term.
            openClearanceProgressModal(studentId, 'student', studentName, schoolTerm);
        }
        
        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            targetId: null,
            targetName: null,
            targetType: 'student',
            isBulk: false,
            targetIds: []
        };

        function openRejectionRemarksModal(targetId, targetName, targetType = 'student', isBulk = false, targetIds = []) {
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
                    targetIds: targetIds
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
                    targetType: 'student',
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
                        // id is user_id in this context
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
                            showToastNotification('No records were updated. You may not be assigned as a signatory for the Senior High School sector\'s clearance period.', 'warning');
                        } else {
                            showToastNotification(`✓ Successfully rejected clearance for ${result.affected_rows} students with remarks`, 'success');
                        }
                        loadStudentsData(); // Refresh table
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
                    const row = document.querySelector(`tr[data-user-id="${currentRejectionData.targetId}"]`);
                    if (row) {
                        const userId = currentRejectionData.targetId; // targetId is already user_id
                        const result = await sendSignatoryAction(userId, 'Rejected', additionalRemarks, rejectionReason);
                        if (result.success) {
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                            loadStudentsData(); // Refresh table
                } else {
                            showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                        }
                    } else {
                        showToastNotification('Student record not found.', 'error');
                    }
                } catch (e) {
                    console.error('Rejection error:', e);
                    showToastNotification('Error during rejection: ' + e.message, 'error');
                }
            }
            
            // Close modal
            closeRejectionRemarksModal();
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
    </script>
    <script src="../../assets/js/alerts.js"></script>
    
    <script>
        // Bulk selection functions
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

        function applyBulkSelection() {
            const filters = {
                active: document.getElementById('filterActive').checked,
                inactive: document.getElementById('filterInactive').checked,
                unapplied: document.getElementById('filterUnapplied').checked,
                applied: document.getElementById('filterApplied').checked,
                inProgress: document.getElementById('filterInProgress').checked,
                complete: document.getElementById('filterComplete').checked,
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
                // Only select if row is visible (respects current table filters/search)
                if (row.style.display !== 'none') {
                    checkbox.checked = true;
                    selectedCount++;
                }
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
                const clearanceProgressBadge = row.querySelector('.status-badge[class*="clearance-"]');
                
                let accountMatch = false;
                let progressMatch = false;
                let statusMatch = false;
                
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
                if (hasProgressFilter && clearanceProgressBadge) {
                    if (filters.unapplied && clearanceProgressBadge.classList.contains('clearance-unapplied')) progressMatch = true;
                    if (filters.applied && clearanceProgressBadge.classList.contains('clearance-applied')) progressMatch = true;
                    if (filters.inProgress && clearanceProgressBadge.classList.contains('clearance-in-progress')) progressMatch = true;
                    if (filters.complete && clearanceProgressBadge.classList.contains('clearance-complete')) progressMatch = true;
                } else if (!hasProgressFilter) {
                    progressMatch = true; // No progress filter = wildcard
                }
                
                // Check clearance status filters (signatory perspective)
                const hasStatusFilter = filters.pending || filters.approved || filters.rejected;
                if (hasStatusFilter && clearanceProgressBadge) {
                    if (filters.pending && clearanceProgressBadge.classList.contains('clearance-pending')) statusMatch = true;
                    if (filters.approved && clearanceProgressBadge.classList.contains('clearance-approved')) statusMatch = true;
                    if (filters.rejected && clearanceProgressBadge.classList.contains('clearance-rejected')) statusMatch = true;
                } else if (!hasStatusFilter) {
                    statusMatch = true; // No status filter = wildcard
                }
                
                // Select if all filter categories match
                const shouldSelect = accountMatch && progressMatch && statusMatch;
                checkbox.checked = shouldSelect;
                if (shouldSelect) selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            showToastNotification(`Selected ${selectedCount} students based on filters`, 'success');
        }

        function resetBulkSelectionFilters() {
            const checkboxes = ['filterActive', 'filterInactive', 'filterUnapplied', 'filterApplied', 'filterInProgress', 'filterComplete', 'filterPending', 'filterApproved', 'filterRejected'];
            checkboxes.forEach(id => {
                document.getElementById(id).checked = false;
            });
        }

        function clearAllSelectionsAndFilters() {
            clearAllSelections();
            resetBulkSelectionFilters();
        }

        function clearAllSelections() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            updateSelectionCounter();
            updateBulkButtons();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.student-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const counterPill = document.getElementById('selectionCounterPill');
            const clearBtn = document.getElementById('clearSelectionBtn');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
                counterPill.classList.remove('has-selections');
                clearBtn.disabled = true;
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                counterPill.classList.add('has-selections');
                clearBtn.disabled = false;
            } else {
                counter.textContent = `${selectedCount} selected`;
                counterPill.classList.add('has-selections');
                clearBtn.disabled = false;
            }
        }

        function getSelectedCount() {
            return document.querySelectorAll('.student-checkbox:checked').length;
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
                
                // Check if there's an active period specifically for "Senior High School" sector
                let hasActivePeriod = false;
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    // Check if any active period is for Senior High School sector
                    hasActivePeriod = data.active_periods.some(period => 
                        period.sector === 'Senior High School' || 
                        period.clearance_type === 'Senior High School'
                    );
                    
                    if (hasActivePeriod) {
                        const period = data.active_periods.find(p => 
                            p.sector === 'Senior High School' || 
                            p.clearance_type === 'Senior High School'
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

        async function loadClearanceStatusesFilter() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=clearance_signatories&column=action`;
            await populateFilter('clearanceStatusFilter', url, 'All Clearance Statuses');
        }

        async function loadAccountStatusesFilter() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=users&column=account_status&exclude=resigned`;
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
        }

        async function loadYearLevelFilter() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=students&column=year_level&sector=Senior High School`;
            await populateFilter('yearLevelFilter', url, 'All Year Levels');
        }

        async function loadProgramsFilter(departmentId = '') {
            const programFilter = document.getElementById('programFilter');
            programFilter.innerHTML = '<option value="">Loading programs...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'programs');
            url.searchParams.append('sector', 'Senior High School');
            if (departmentId) url.searchParams.append('department_id', departmentId);
            await populateFilter('programFilter', url, 'All Programs');
        }
        
        async function loadDepartmentsFilter() {
            const departmentFilter = document.getElementById('departmentFilter');
            departmentFilter.innerHTML = '<option value="">Loading departments...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'departments');
            url.searchParams.append('sector', 'Senior High School');
            await populateFilter('departmentFilter', url, 'All Departments');
        }

        // Signatory Action Functions
        async function approveSignatory(userId) {
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Senior High School sector\'s clearance period. Approving clearances is not available.', 'warning');
                return;
            }
            
            // Get student name for confirmation
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (!row) {
                showToastNotification('Student record not found.', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)')?.textContent || 'Unknown';
            const clearanceBadge = row.querySelector('.status-badge-compact.signatory-pending, .status-badge-compact.signatory-rejected');
            
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
                        // Use the same approach as Regular Staff
                        const result = await sendSignatoryAction(
                            userId, 
                            'Approved', 
                            'Approved by School Administrator'
                        );
                
                if (result.success) {
                            showToastNotification('Student clearance approved successfully', 'success');
                            loadStudentsData(); // Refresh table
                } else {
                            showToastNotification('Failed to approve: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                        console.error('Approval error:', error);
                        showToastNotification('An error occurred during approval.', 'error');
            }
                },
                'success'
            );
        }

        async function rejectSignatory(userId) {
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Senior High School sector\'s clearance period. Rejecting clearances is not available.', 'warning');
                return;
            }
            
            // Get student name for confirmation
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (!row) {
                showToastNotification('Student record not found.', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)')?.textContent || 'Unknown';
            const clearanceBadge = row.querySelector('.status-badge-compact.signatory-pending, .status-badge-compact.signatory-rejected, .status-badge-compact.signatory-approved');
            
            if (!clearanceBadge) {
                showToastNotification('No clearance to reject', 'warning');
                return;
            }
            
            // Open rejection remarks modal (using the existing modal system)
            openRejectionRemarksModal(userId, studentName, 'student', false);
        }

        function updateSignatoryActionUI(userId, action) {
            // Find the row for this user and update the signatory action buttons and status
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (!row) return;
            
            // Update the Clearance Status column (8th column)
            const statusCell = row.children[7]; // Clearance Status column
            if (statusCell) {
                const statusBadge = statusCell.querySelector('.status-badge');
                if (statusBadge) {
                    if (action === 'Approved') {
                        statusBadge.textContent = 'Approved';
                        statusBadge.className = 'status-badge clearance-approved';
                    } else if (action === 'Rejected') {
                        statusBadge.textContent = 'Rejected';
                        statusBadge.className = 'status-badge clearance-rejected';
                    }
                }
            }
            
            // Update the action buttons in the Actions column (9th column)
            const actionCell = row.children[8]; // Actions column
            if (actionCell) {
                const approveBtn = actionCell.querySelector('.approve-btn');
                const rejectBtn = actionCell.querySelector('.reject-btn');
                
                if (approveBtn && rejectBtn) {
                    if (action === 'Approved') {
                        approveBtn.disabled = true;
                        approveBtn.classList.add('approved');
                        rejectBtn.disabled = true;
                    } else if (action === 'Rejected') {
                        approveBtn.disabled = false;
                        approveBtn.title = 'Re-approve Signatory';
                        rejectBtn.disabled = true;
                        rejectBtn.classList.add('rejected');
                    }
                }
            }
        }

        // Bulk Signatory Actions
        async function bulkApproveSignatories() {
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Senior High School sector\'s clearance period. Approving clearances is not available.', 'warning');
                return;
            }
            
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                showToastNotification('Please select students to approve', 'warning');
                return;
            }
            
            const userIds = [];
            for (const checkbox of selectedCheckboxes) {
                const row = checkbox.closest('tr');
                if (row) {
                    const userId = row.getAttribute('data-user-id');
                    if (userId) {
                        userIds.push(parseInt(userId));
                    }
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
                    remarks: 'Bulk approved by School Administrator'
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
                        showToastNotification('No records were updated. You may not be assigned as a signatory for the Senior High School sector\'s clearance period.', 'warning');
                    } else {
                        showToastNotification(`Successfully approved clearance for ${result.affected_rows} students.`, 'success');
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
                loadStudentsData(); // Refresh the entire table
            }
        }

        async function bulkRejectSignatories() {
            // Guard: Check if user is assigned as signatory
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned as a signatory for the Senior High School sector\'s clearance period. Rejecting clearances is not available.', 'warning');
                return;
            }
            
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                showToastNotification('Please select students to reject', 'warning');
                return;
            }
            
            // Get selected user IDs
            const selectedIds = [];
            for (const checkbox of selectedCheckboxes) {
                const row = checkbox.closest('tr');
                if (row) {
                    const userId = row.getAttribute('data-user-id');
                    if (userId) {
                        selectedIds.push(userId);
                    }
                }
            }
            
            // Open rejection remarks modal for bulk rejection
            openRejectionRemarksModal(null, null, 'student', true, selectedIds);
        }

        // Check if current user is signatory for this sector
        async function checkSignatoryStatus(sector) {
            try {
                const response = await fetch(`../../api/clearance/check_signatory_status.php?sector=${encodeURIComponent(sector)}`, {
                    credentials: 'include'
                });
                const data = await response.json();
                return data.success && data.is_signatory;
            } catch (error) {
                console.error('Error checking signatory status:', error);
                return false;
            }
        }

        // Initialize signatory buttons based on user's signatory status
        async function initializeSignatoryButtons() {
            const isSignatory = await checkSignatoryStatus('Senior High School');
            
            if (!isSignatory) {
                // Hide only the approve/reject buttons if user is not a signatory
                // Keep all data visible, only hide action buttons
                document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
                    btn.style.display = 'none';
                });
                
                // Update the Clearance Status column to show "Not Assigned" instead of hiding it
                document.querySelectorAll('#studentsTableBody tr').forEach(row => {
                    const cells = row.children;
                    // Update the Clearance Status column (8th column, index 7) to show "Not Assigned"
                    if (cells[7]) {
                        const statusBadge = cells[7].querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Not Assigned';
                            statusBadge.className = 'status-badge clearance-not-assigned';
                        }
                    }
                });
            }
        }

        async function setDefaultSchoolTerm() {
            try {
                const response = await fetch('../../api/clearance/periods.php', { credentials: 'include' });
                const data = await response.json();
                
                const schoolTermFilter = document.getElementById('schoolTermFilter');
                if (!schoolTermFilter) return;
                
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    // Find the active period specifically for the 'Senior High School' sector
                    const activePeriod = data.active_periods.find(p => 
                        p.sector === 'Senior High School' || 
                        p.clearance_type === 'Senior High School'
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


        document.addEventListener('DOMContentLoaded', async function() {
            updateTermIndicatorBanner();

            // 1. Load general data and options for filters and modals
            await Promise.all([
                loadRejectionReasons(),
                loadClearanceStatusesFilter(),
                loadYearLevelFilter(),
                loadProgramsFilter(),
                loadDepartmentsFilter(),
                loadSchoolTermsFilter(),
                loadAccountStatusesFilter(),
                loadCurrentPeriod() // For the banner
            ]);

            // 2. Perform the initial data fetch for the main table and Default Filters
            await setDefaultSchoolTerm();
            loadStudentsData();

            // 3. Initialize UI components and event listeners
            initializeSignatoryButtons();
            updateSelectionCounter();

            // Add event listeners for search and filters
            document.getElementById('searchInput').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    applyFilters();
                }
            });

            // Add event listener for department filter change
            const departmentFilter = document.getElementById('departmentFilter');
            if (departmentFilter) {
                departmentFilter.addEventListener('change', handleDepartmentChange);
            }
        });

        // Handle department filter change to update programs
        function handleDepartmentChange() {
            const departmentId = document.getElementById('departmentFilter').value;
            loadProgramsFilter(departmentId);
        }

        // Add event listeners for student checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateBulkButtons();
                updateSelectionCounter();
            }
        });
    </script>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
</body>
</html>
