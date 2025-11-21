<?php
// Online Clearance Website - Regular Staff College Student Management

// Include the controller logic which handles all authorization and data fetching.
require_once __DIR__ . '/../../controllers/StudentManagementController.php';

// The controller function acts as a "gatekeeper". If it doesn't exit, the user is authorized.
handleStudentManagementPageRequest('College');
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
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
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

                        <!-- Current Period Wrapper -->
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
        
        // Initialize CURRENT_STAFF_POSITION from the role selector dropdown
        // This ensures we use the correct filtered role, not the primary designation
        let CURRENT_STAFF_POSITION = '';
        
        // Try to get the value from the dropdown immediately (in case it's already rendered)
        const roleSelectorElement = document.getElementById('roleSelector');
        if (roleSelectorElement && roleSelectorElement.value) {
            CURRENT_STAFF_POSITION = roleSelectorElement.value;
        } else {
            // Fallback to PHP value if dropdown isn't available yet
            CURRENT_STAFF_POSITION = '<?php echo !empty($GLOBALS['userSignatoryDesignations']) ? addslashes($GLOBALS['userSignatoryDesignations'][0]['designation_name']) : 'Staff'; ?>';
        }
        
        // On page load, ensure CURRENT_STAFF_POSITION is synced with the dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelector = document.getElementById('roleSelector');
            if (roleSelector && roleSelector.value) {
                CURRENT_STAFF_POSITION = roleSelector.value;
                console.log('Initialized CURRENT_STAFF_POSITION to:', CURRENT_STAFF_POSITION);
            }
        });

        // Handle role changes by re-applying all filters, which triggers a fetch
        function handleRoleChange() {
            const roleSelector = document.getElementById('roleSelector');
            if (roleSelector) {
                CURRENT_STAFF_POSITION = roleSelector.value;
                console.log("Role changed to:", CURRENT_STAFF_POSITION);
                
                // Update the position display
                const positionElement = document.getElementById('positionInfo');
                if (positionElement) {
                    positionElement.textContent = `Position: ${CURRENT_STAFF_POSITION} - Clearance Signatory`;
                }
                
                applyFilters(); // Re-fetch data from server with the new role
            }
        }

        // Select all functionality
        function toggleSelectAll(checked) {
            const studentCheckboxes = document.querySelectorAll('#studentTableBody .student-checkbox');
            studentCheckboxes.forEach(checkbox => {
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
            const allCheckboxes = document.querySelectorAll('#studentTableBody .student-checkbox:not(:disabled)');
            const checkedCount = document.querySelectorAll('#studentTableBody .student-checkbox:not(:disabled):checked').length;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
            }
        }

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
            const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                
                // Skip hidden rows (respects table filters)
                if (row && row.style.display === 'none') {
                    checkbox.checked = false;
                    return;
                }
                
                checkbox.checked = true;
                selectedCount++;
            });
            
            updateSelectionCounter();
            updateBulkButtons();
            updateSelectAllCheckbox();
            showToastNotification(`Selected all ${selectedCount} visible students`, 'success');
        }

        function selectStudentsByFilters(filters) {
            const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
            let selectedCount = 0;
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                
                // Skip hidden rows (respects table filters)
                if (row && row.style.display === 'none') {
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
            updateSelectAllCheckbox();
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
                    const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
                    const userIds = [];
                    for (const checkbox of selectedCheckboxes) {
                        const studentNumber = checkbox.getAttribute('data-id');
                        const userId = await resolveUserIdFromStudentNumber(studentNumber);
                        if (userId) {
                            userIds.push(userId);
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
                            designation_name: CURRENT_STAFF_POSITION,
                            remarks: `Approved by ${CURRENT_STAFF_POSITION}`
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
                            showToastNotification(`Successfully approved clearance for ${result.affected_rows} students.`, 'success');
                        } else {
                            throw new Error(result.message || 'Bulk approval failed.');
                        }
                    } catch (error) {
                        console.error('Bulk approval error:', error);
                        showToastNotification(error.message, 'error');
                    } finally {
                        fetchStudents(); // Refresh the entire table
                    }
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
            const clearanceFormId = row.getAttribute('data-clearance-form-id');
            
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
                            // Corrected: Pass remarks in the 4th argument, not concatenated with the designation.
                            const result = await sendSignatoryAction(uid, 'Approved', 'Approved by ' + CURRENT_STAFF_POSITION);
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
                },
                'success'
            );
        }

        async function rejectStudentClearance(studentId) {
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
        
            console.log('Opening rejection modal for', studentName);
            console.log('Existing reason ID:', existingReasonId);
            console.log('Existing remarks:', existingRemarks);
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(studentId, studentName, 'student', false, [], existingRemarks, existingReasonId);
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
            url.searchParams.append('type', 'student'); 
            url.searchParams.append('sector', 'College'); 
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            // Optional filters
            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);
            
            // Pass the current role/designation for filtering
            if (CURRENT_STAFF_POSITION) url.searchParams.append('designation_filter', CURRENT_STAFF_POSITION);

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

            // Capture the currently selected school term from the filters so we can
            // display clearance progress scoped to that term when the user opens the modal.
            const currentSchoolTerm = document.getElementById('schoolTermFilter') ? document.getElementById('schoolTermFilter').value : '';

            tableBody.innerHTML = students.map(student => {
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
                
                const accountStatusClass = `account-${(student.account_status || '').toLowerCase()}`;

                const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
                // Enable approve button for 'Pending' and 'Rejected' statuses.
                let approveBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(clearanceStatus) || !userExisted;
                // Enable reject button for 'Pending' and 'Rejected' statuses to allow for edits.
                let rejectBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(clearanceStatus) || !userExisted;
                // Disable checkbox for 'Unapplied' and 'Approved' statuses (same logic as buttons)
                let checkboxDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(clearanceStatus) || !userExisted;
                let approveTitle = 'Approve Clearance';
                // Change button title if the student is already rejected.
                let rejectTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Clearance';
                if (!canPerformActions) {
                    approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a student signatory."; ?>';
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

                const rowClass = !userExisted ? 'user-not-existed' : '';

                return `
                    <tr data-user-id="${student.user_id}" data-clearance-form-id="${student.clearance_form_id}" data-student-name="${escapeHtml(student.name)}" data-signatory-id="${student.signatory_id}" class="${rowClass}">
                        <td><input type="checkbox" class="student-checkbox" data-id="${student.id}" ${checkboxDisabled ? 'disabled' : ''}></td>
                        <td>${student.id}</td>
                        <td>${escapeHtml(student.name)}</td>
                        <td>${escapeHtml(student.program)}</td>
                        <td>${escapeHtml(student.year_level)}</td>
                        <td>${escapeHtml(student.section)}</td>
                        <td><span class="status-badge ${accountStatusClass}">${escapeHtml(student.account_status || 'N/A')}</span></td>
                        <td class="clearance-status-cell">${clearanceProgressContent}</td>
                        <td class="clearance-status-cell">${clearanceStatusContent}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('${student.id}', '${escapeHtml(student.name)}', '${escapeHtml(currentSchoolTerm)}')" title="View Clearance Progress">
                                    <i class="fas fa-tasks"></i>
                                </button>
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

        function viewClearanceProgress(studentId, studentName, schoolTerm = '') {
            // Forward the selected school term (if any) so the modal can show
            // the clearance progress scoped to that term.
            openClearanceProgressModal(studentId, 'student', studentName, schoolTerm);
        }

        function renderPagination(total, page, limit) {
            const totalPages = Math.ceil(total / limit);
            const startEntry = total === 0 ? 0 : (page - 1) * limit + 1;
            const endEntry = Math.min(page * limit, total);

            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${total} entries`;
            
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';

            // Smart pagination logic (max 7 page numbers shown)
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    addPageButton(i, i === page);
                }
            } else {
                if (page <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        addPageButton(i, i === page);
                    }
                    addEllipsis();
                    addPageButton(totalPages, false);
                } else if (page >= totalPages - 3) {
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        addPageButton(i, i === page);
                    }
                } else {
                    addPageButton(1, false);
                    addEllipsis();
                    for (let i = page - 1; i <= page + 1; i++) {
                        addPageButton(i, i === page);
                    }
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

        function updateStatistics(stats) {
            if (!stats) return;
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
            document.getElementById('graduatedStudents').textContent = stats.graduated || 0;
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
            currentPage = 1; // Reset to first page on new filter/search
            updateTermIndicatorBanner();
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
                const positionElement = document.getElementById('positionInfo');
                const roleSelector = document.getElementById('roleSelector');
                
                if (positionElement) {
                    // Display the currently selected role from the dropdown
                    if (roleSelector && roleSelector.value) {
                        positionElement.textContent = `Position: ${roleSelector.value} - Clearance Signatory`;
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
        document.addEventListener('DOMContentLoaded', async function() { // Make this async
            // Load current clearance period for banner
            
            updateTermIndicatorBanner();
            updateSelectionCounter();
            loadCurrentPeriod();
            
            document.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('student-checkbox')) {
                    updateBulkButtons();
                    updateSelectAllCheckbox();
                    updateSelectionCounter();
                }
            });

            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();

            // --- New Initializer Logic ---
            // Wait for all filters to be populated before setting defaults and loading data.
            await Promise.all([
                loadRejectionReasons(),
                loadSchoolTerms(),
                loadClearanceStatuses(),
                loadAccountStatuses(),
                loadStaffPosition()
            ]);

            await setDefaultSchoolTerm();
            await fetchStudents();

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
            
            // Demo: Update UI and show success message
            if (currentRejectionData.isBulk) {
                const studentNumbers = currentRejectionData.targetIds; // These are the student IDs

                const userIds = [];
                for (const sid of studentNumbers) {
                    // Assuming resolveUserIdFromStudentNumber exists and works correctly
                    const uid = await resolveUserIdFromStudentNumber(sid);
                    if (uid) userIds.push(uid);
                }

                if (studentNumbers.length === 0) {
                    showToastNotification('Could not identify users to reject.', 'error');
                    closeRejectionRemarksModal();
                    return;
                }

                // Get the currently selected school term from the filter
                const schoolTermFilter = document.getElementById('schoolTermFilter');
                const currentSchoolTerm = schoolTermFilter ? schoolTermFilter.value : '';

                try {
                    const bulkPayload = {
                        applicant_user_ids: userIds,
                        action: 'Rejected',
                        designation_name: CURRENT_STAFF_POSITION,
                        remarks: additionalRemarks,
                        reason_id: rejectionReason
                    };
                    // Include school_term if a specific term is selected
                    if (currentSchoolTerm && currentSchoolTerm.trim() !== '') {
                        bulkPayload.school_term = currentSchoolTerm.trim();
                    }

                    const response = await fetch('../../api/clearance/bulk_signatory_action.php', { // Use the bulk endpoint
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify(bulkPayload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToastNotification(`Successfully rejected clearance for ${result.affected_rows} students.`, 'success');
                    } else {
                        throw new Error(result.message || 'Bulk rejection failed.');
                    } 
                } catch (error) {
                    console.error('Bulk rejection error:', error);
                    showToastNotification(error.message, 'error');
                } finally {
                    closeRejectionRemarksModal(); // Close the modal
                    fetchStudents(); // Refresh the table
                }

            } else {
                // Update individual student row
                const row = document.querySelector(`.student-checkbox[data-id="${currentRejectionData.targetId}"]`);
                if (row) {

                    }
                }
                // server-side record
                try {
                    const uid = await resolveUserIdFromStudentNumber(currentRejectionData.targetId); // targetId is student number
                    if (uid) { 
                        const result = await sendSignatoryAction(uid, 'Rejected', additionalRemarks, rejectionReason); 
                        if (result.success) {
                            showToastNotification('Student clearance rejected successfully', 'success');
                            fetchStudents(); // Refresh the table to update button states
                        } else {
                            showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                        }
                    }
                } catch (e) {
                    console.error("Error during individual rejection:", e);
                    showToastNotification('An error occurred during rejection.', 'error');
                } finally {
                    closeRejectionRemarksModal(); // Close the modal
                }
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
            // Fetch the current staff's actual designation from the API to ensure accuracy.
            let currentDesignation = CURRENT_STAFF_POSITION; // Fallback
            try {
                const desigResponse = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const desigData = await desigResponse.json();
                if (desigData.success && desigData.designation_name) { 
                    currentDesignation = desigData.designation_name; 
                }
            } catch (e) { console.warn("Could not fetch designation, using fallback."); }

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
