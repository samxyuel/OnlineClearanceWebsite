<?php
// Online Clearance Website - Program Head Senior High School Student Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Page guard: Program Head must have student-sector assignment (College/SHS)
// Include the controller logic which handles all authorization and data fetching.
require_once __DIR__ . '/../../controllers/StudentManagementController.php';

// The controller function acts as a "gatekeeper". If it doesn't exit, the user is authorized.
handleStudentManagementPageRequest('Senior High School');

// For SHS, we do not filter by department ID. This is intentionally left empty.
$departmentIds = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHS Student Management - Program Head Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
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
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-graduation-cap"></i> Senior High School Student Management</h2>
                            <p>Manage senior high school students within your assigned departments and sign their clearances</p>
                            <div class="department-scope-info">
                                <i class="fas fa-shield-alt"></i>
                                <span id="departmentScopeText">Loading department scope...</span>
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
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalStudents">--</h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeStudents">--</h3>
                                    <p>Active Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveStudents">--</h3>
                                    <p>Inactive Students</p>
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
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or program...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Program Filter (Only for assigned departments) -->
                                <select id="programFilter" class="filter-select">
                                    <option value="">All Programs</option>
                                </select>
                                
                                <!-- Year Level Filter -->
                                <select id="yearLevelFilter" class="filter-select">
                                    <option value="">All Year Levels</option>
                                    <!-- Options will be loaded dynamically -->
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
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">Loading Terms...</option>
                                </select>
                                
                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
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
                                        <button class="btn btn-success" onclick="approveSelected()" disabled>
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" onclick="rejectSelected()" disabled>
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteSelected()" disabled>
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
                                            <!-- Student data will be loaded here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-section">
                            <div class="pagination-info">
                                <span id="paginationInfo">Showing 1 to 4 of 4 entries</span>
                            </div>
                            <div class="pagination-controls">
                                <button class="pagination-btn" id="prevPage" onclick="changePage(-1)" disabled>
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
                
            </div>
        </div>
    </main>

    <script>
        const DEPARTMENT_IDS = <?php echo json_encode($departmentIds); ?>;
        // This will be dynamically updated by the role selector
        let CURRENT_STAFF_POSITION = '';
        
        // Global permission flag - default to false for safety
        // Updated dynamically from is_assigned.php API (primary source for Program Head)
        let canPerformSignatoryActions = false;
        
        // Global flag to track if there's an active clearance period for this sector
        let hasActiveClearancePeriod = false;
        
        // Try to get the value from the dropdown immediately (in case it's already rendered)
        const roleSelectorElement = document.getElementById('roleSelector');
        if (roleSelectorElement && roleSelectorElement.value) {
            CURRENT_STAFF_POSITION = roleSelectorElement.value;
        } else {
            // Fallback to PHP value if dropdown isn't available yet
            CURRENT_STAFF_POSITION = '<?php echo !empty($GLOBALS['userSignatoryDesignations']) ? addslashes($GLOBALS['userSignatoryDesignations'][0]['designation_name']) : 'Program Head'; ?>';
        }
        
        // On page load, ensure CURRENT_STAFF_POSITION is synced with the dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelector = document.getElementById('roleSelector');
            if (roleSelector && roleSelector.value) {
                CURRENT_STAFF_POSITION = roleSelector.value;
            }
        });

        // Handle role changes by re-applying all filters, which triggers a fetch
        function handleRoleChange() {
            const roleSelector = document.getElementById('roleSelector');
            if (roleSelector) {
                CURRENT_STAFF_POSITION = roleSelector.value;
                
                // Update the position display
                const positionElement = document.getElementById('staffPositionInfo');
                if (positionElement) {
                    positionElement.textContent = `Position: ${CURRENT_STAFF_POSITION}`;
                }
                
                applyFilters(); // Re-fetch data from server with the new role
            }
        }

        async function fetchCanTakeActionSHS() {
            try {
                const resp = await fetch('../../api/program-head/is_assigned.php?clearance_type=Senior High School', { credentials: 'include' });
                const data = await resp.json();
                if (data && data.success) {
                    canPerformSignatoryActions = !!data.can_take_action;
                } else {
                    console.warn('is_assigned (SHS) returned no data', data);
                }
            } catch (e) {
                console.error('Error fetching is_assigned (SHS):', e);
            }
        }
    </script>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Student Registry Modal -->
    <?php include '../../Modals/SHSStudentRegistryModal.php'; ?>
    
    <!-- Include Edit Student Modal -->
    <?php include '../../Modals/SHSEditStudentModal.php'; ?>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ClearanceExportModal.php'; ?>
    
    <!-- Include Import Modal -->
    <?php include '../../Modals/ImportModal.php'; ?>

    <!-- Include Clearance Progress Modal -->
    <?php include '../../Modals/ClearanceProgressModal.php'; ?>
    
    <!-- Include Generated Credentials Modal (shared, include only once) -->
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

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const allCheckboxes = document.querySelectorAll('#studentsTableBody .student-checkbox:not(:disabled)');
            const checkedCount = document.querySelectorAll('#studentsTableBody .student-checkbox:not(:disabled):checked').length;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
            }
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateSelectAllCheckbox();
            }
        });
        // Select all functionality
        function toggleSelectAll(checked) {
            // Use the parameter if provided, otherwise get from checkbox
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const isChecked = checked !== undefined ? checked : (selectAllCheckbox ? selectAllCheckbox.checked : false);
            const studentCheckboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
            
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            updateBulkButtons();
            updateSelectionCounter();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.student-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
            } else {
                counter.textContent = `${selectedCount} selected`;
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            const hasSelection = checkedBoxes.length > 0;
            
            // Get all bulk buttons
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
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
                            button.title = 'You do not have permission to take action';
                        }
                    } else {
                        button.title = '';
                    }
                } else {
                    // Other buttons (Delete, etc.): only require selection
                    button.disabled = !hasSelection;
                    button.title = '';
                }
            });
            
            updateSelectionCounter();
        }

        // Enhanced notification function
        function showNotification(message, type = 'info') {
            showToastNotification(message, type);
        }

        // Bulk Actions with Confirmation
        function approveSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to approve', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Approve Clearances',
                `Are you sure you want to approve clearance for ${selectedCount} selected students?`,
                'Approve',
                'Cancel',
                () => {
                    bulkSignatoryAction('Approved', `Approved by Program Head`);
                },
                'success'
            );
        }

        async function bulkSignatoryAction(action, remarks, reasonId = null) {
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
                showToastNotification('Could not identify users for this action.', 'error');
                return;
            }

            // Get the currently selected school term from the filter
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            const currentSchoolTerm = schoolTermFilter ? schoolTermFilter.value : '';

            const payload = {
                applicant_user_ids: userIds,
                action: action,
                designation_name: 'Program Head',
                remarks: remarks
            };
            if (reasonId) payload.reason_id = reasonId;
            // Include school_term if a specific term is selected
            if (currentSchoolTerm && currentSchoolTerm.trim() !== '') {
                payload.school_term = currentSchoolTerm.trim();
            }

            await sendBulkAction(payload);
        }

        function rejectSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to reject', 'warning');
                return;
            }
            
            // Get selected student IDs
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-id'));
            
            // Open rejection remarks modal for bulk rejection
            openRejectionRemarksModal(null, null, null, 'Bulk Action', true, selectedIds);
        }

        function deleteSelected() {
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedCount = selectedCheckboxes.length;
            
            if (selectedCount === 0) {
                showToastNotification('Please select students to delete', 'warning');
                return;
            }
            
            // Collect all user IDs from selected rows
            const userIds = [];
            const rows = [];
            
            selectedCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const userId = row.getAttribute('data-user-id');
                if (userId) {
                    userIds.push(parseInt(userId));
                    rows.push(row);
                }
            });
            
            if (userIds.length === 0) {
                showToastNotification('Could not identify students to delete', 'error');
                return;
            }
            
            showConfirmationModal(
                'Delete Students',
                `Are you sure you want to delete <strong>${selectedCount}</strong> selected student(s)? This action will permanently remove the students' data, including all clearance forms and clearance applications. This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                async () => {
                    try {
                        // Show loading state on delete button
                        const deleteBtn = document.getElementById('bulkDeleteBtn');
                        if (deleteBtn) {
                            deleteBtn.disabled = true;
                            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                        }
                        
                        // Call API for bulk deletion
                        const response = await fetch('../../api/users/delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            credentials: 'include',
                            body: JSON.stringify({
                                user_type: 'student',
                                user_ids: userIds
                            })
                        });
                        
                        const result = await response.json();
                        
                        // Reset button state
                        if (deleteBtn) {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        }
                        
                        if (result.success) {
                            // Reload data to refresh table and statistics
                            loadStudentsData();
                            
                            // Clear selections
                            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                            if (selectAllCheckbox) selectAllCheckbox.checked = false;
                            updateBulkButtons();
                            updateSelectionCounter();
                            
                            // Show success notification
                            const message = result.errors && result.errors.length > 0
                                ? `Deleted ${result.deleted_count} student(s). ${result.failed_count} failed.`
                                : `✓ Successfully deleted ${result.deleted_count} student(s)`;
                            
                            showToastNotification(message, result.errors && result.errors.length > 0 ? 'warning' : 'success');
                            
                            if (result.errors && result.errors.length > 0) {
                                console.error('Deletion errors:', result.errors);
                            }
                        } else {
                            showToastNotification('Failed to delete students: ' + result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error in bulk delete:', error);
                        showToastNotification('Error deleting students: ' + error.message, 'error');
                        
                        // Reset button state
                        const deleteBtn = document.getElementById('bulkDeleteBtn');
                        if (deleteBtn) {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        }
                    }
                },
                'danger'
            );
        }

        // Individual Clearance Approval/Rejection
        async function approveStudent(button) {
            const row = button.closest('tr');
            const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
            
            if (!clearanceBadge) {
                console.error('Clearance badge not found');
                showToastNotification('Error: Could not find clearance status', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const currentStatus = clearanceBadge.textContent;
            
            if (currentStatus === 'Approved' || currentStatus === 'Completed' || currentStatus === 'Complete') {
                showToastNotification(`${studentName}'s clearance is already approved/completed`, 'info');
                return;
            }
            
            showConfirmationModal(
                'Approve Clearance',
                `Approve ${studentName}'s clearance?`,
                'Approve',
                'Cancel',
                async () => {
                    const applicantUserId = row.getAttribute('data-user-id');
                    const result = await sendSignatoryAction(applicantUserId, 'Approved', 'Approved by Program Head');
                    if (result.success) {
                        showToastNotification(`${studentName}'s clearance has been approved`, 'success');
                        updateSignatoryActionUI(applicantUserId, 'Approved');
                        loadStudentsData(); // Refresh data
                    } else {
                        showToastNotification('Failed to approve: ' + (result.message || 'Unknown error'), 'error');
                    }
                    
                    // Old UI-only logic
                    try {
                        const studentId = row.querySelector('.student-checkbox').getAttribute('data-id');
                        const userId = await resolveUserIdFromStudentNumber(studentId);
                        if (userId) {
                            await sendSignatoryAction(userId, 'Program Head', 'Approved');
                        }
                    } catch (e) { /* non-blocking */ }
                },
                'success'
            );
        }

        function rejectStudent(button) {
            const row = button.closest('tr');
            // Note: Clearance status badges use 'signatory-*' classes (e.g., signatory-pending, signatory-rejected)
            const clearanceBadge = row.querySelector('.status-badge-compact[class*="signatory-"]');

            if (!clearanceBadge) {
                console.error('Clearance badge not found');
                showToastNotification('Error: Could not find clearance status', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const currentStatus = clearanceBadge.textContent;
            
            if (currentStatus === 'Rejected' && !button.title.includes('Update')) {
                showToastNotification(`${studentName}'s clearance is already rejected`, 'info');
                return;
            }
            
            // Get student ID from the checkbox
            const userId = row.getAttribute('data-user-id');
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(userId, studentName, 'student', false);
        }

        // Individual Delete with Confirmation
        function deleteStudent(userId) {
            // Get student info from the table row
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (!row) {
                showToastNotification('Error: Could not find student', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)')?.textContent?.trim() || 'this student';
            
            showConfirmationModal(
                'Delete Student',
                `Are you sure you want to delete <strong>${escapeHtml(studentName)}</strong>? This action will permanently remove the student's data, including all clearance forms and clearance applications. This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                async () => {
                    try {
                        const response = await fetch('../../api/users/delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            credentials: 'include',
                            body: JSON.stringify({
                                user_type: 'student',
                                user_id: userId
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Reload data to refresh table and statistics
                            loadStudentsData();
                            showToastNotification(`✓ Student ${studentName} deleted successfully`, 'success');
                        } else {
                            showToastNotification('Failed to delete student: ' + result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error deleting student:', error);
                        showToastNotification('Error deleting student: ' + error.message, 'error');
                    }
                },
                'danger'
            );
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        
    // Individual student actions
        function editStudent(studentId) {
            if (typeof window.openEditStudentModal === 'function') {
                window.openEditStudentModal(studentId);
            } else {
                showToastNotification('Edit student modal not available. Please refresh the page.', 'error');
            }
        }

        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeStudents');
            const inactiveCount = document.getElementById('inactiveStudents');
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            
            if (action === 'activate') {
                currentActive += count;
                currentInactive -= count;
            } else if (action === 'deactivate') {
                currentActive -= count;
                currentInactive += count;
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
        }

        function updateStatistics(action) {
            const activeCount = document.getElementById('activeStudents');
            const inactiveCount = document.getElementById('inactiveStudents');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            
            if (action === 'activate') {
                currentActive++;
                currentInactive--;
            } else if (action === 'deactivate') {
                currentActive--;
                currentInactive++;
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
        }

        // Modal functions - Make globally available
        window.openAddStudentModal = function() {
            if (typeof window.openStudentRegistrationModal === 'function') {
                window.openStudentRegistrationModal();
            } else {
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Student registration modal not available. Please refresh the page.', 'error');
                }
            }
        };

        window.triggerImportModal = function() {
            try {
                if (typeof window.openImportModal === 'function') {
                    // Initialize modal with page context: SHS student import for Program Head
                    window.openImportModal('shs', 'student_import', 'Program Head');
                } else {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Import feature is not available. Please refresh the page.', 'error');
                    }
                }
            } catch (error) {
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open import modal. Please try again.', 'error');
                }
            }
        };

        window.triggerExportModal = function() {
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else {
                console.error('Export modal function not found');
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Export feature is not available. Please refresh the page.', 'error');
                }
            }
        };

        // Apply filters to the table
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
            document.getElementById('programFilter').value = '';
            document.getElementById('yearLevelFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            showToastNotification('All filters cleared', 'info');
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        
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
            button.onclick = () => {
                goToPage(pageNum);
            };
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
        // Scroll to top function
        function scrollToTop() {
            const tableWrapper = document.getElementById('studentsTableWrapper');
            if (tableWrapper) {
                tableWrapper.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }

        // Show/hide scroll to top button based on scroll position
        function handleTableScroll() {
            const tableWrapper = document.getElementById('studentsTableWrapper');
            const scrollToTopBtn = document.getElementById('scrollToTopBtn');
            
            if (tableWrapper && scrollToTopBtn) {
                if (tableWrapper.scrollTop > 100) {
                    scrollToTopBtn.style.display = 'inline-block';
                } else {
                    scrollToTopBtn.style.display = 'none';
                }
            }
        }


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
                // Note: Clearance status badges use 'signatory-*' classes for signatory action status
                const clearanceBadge = row.querySelector('.status-badge-compact[class*="signatory-"]');
                
                let accountMatch = false;
                let statusMatch = false;
                
                // Check account status filters
                const hasAccountFilter = filters.active || filters.inactive;
                if (hasAccountFilter && accountBadge) {
                    if (filters.active && accountBadge.classList.contains('account-active')) accountMatch = true;
                    if (filters.inactive && accountBadge.classList.contains('account-inactive')) accountMatch = true;
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
            document.getElementById('filterPending').checked = false;
            document.getElementById('filterApproved').checked = false;
            document.getElementById('filterRejected').checked = false;
        }

        function clearAllSelectionsAndFilters() {
            clearAllSelections();
            resetBulkSelectionFilters();
        }

        function clearAllSelections() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                if (!checkbox.disabled) checkbox.checked = false;
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
                
                // Check if there's an active period for Senior High School sector
                if (data.success && data.active_periods && data.active_periods.length > 0) {
                    const activePeriod = data.active_periods.find(p => p.sector === 'Senior High School');
                    hasActiveClearancePeriod = !!activePeriod;
                    
                    if (activePeriod) {
                        const period = activePeriod;
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
                        hasActiveClearancePeriod = false;
                        if (yearEl) yearEl.textContent = 'No active period';
                        if (semesterEl) semesterEl.textContent = 'No term';
                    }
                } else {
                    hasActiveClearancePeriod = false;
                    if (yearEl) yearEl.textContent = 'No active period';
                    if (semesterEl) semesterEl.textContent = 'No term';
                }
            } catch (error) {
                console.error('Error loading current period:', error);
                hasActiveClearancePeriod = false;
                const yearEl = document.getElementById('currentAcademicYear');
                const semesterEl = document.getElementById('currentSemester');
                if (yearEl) yearEl.textContent = 'Error loading';
                if (semesterEl) semesterEl.textContent = 'Error';
            }
            
            // Update bulk buttons after period status is loaded
            updateBulkButtons();
            updateActionButtonsState();
        }

        // Load senior high students data from API
        async function loadStudentsData() {
            const tableBody = document.getElementById('studentsTableBody');
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading students...</td></tr>`;

            const search = document.getElementById('searchInput').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const programId = document.getElementById('programFilter').value;
            const yearLevel = document.getElementById('yearLevelFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;

            const url = new URL('../../api/clearance/signatoryList.php', window.location.href);
            url.searchParams.append('type', 'student');
            url.searchParams.append('sector', 'Senior High School');
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            // Conditionally add department_ids if they exist (for consistency with College page)
            if (typeof DEPARTMENT_IDS !== 'undefined' && DEPARTMENT_IDS.length > 0) {
                url.searchParams.append('department_ids', DEPARTMENT_IDS.join(','));
            }

            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (programId) url.searchParams.append('program_id', programId);
            if (yearLevel) url.searchParams.append('year_level', yearLevel);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);
            
            // Pass the current role/designation for filtering
            if (CURRENT_STAFF_POSITION) url.searchParams.append('designation_filter', CURRENT_STAFF_POSITION);

            try {
                const response = await fetch(url.toString(), {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // If signatoryList.php says NO but is_assigned.php said YES, log warning
                if (canPerformSignatoryActions && data.can_perform_actions === false) {
                    console.warn('Permission mismatch: is_assigned.php=true, signatoryList.php=false. Using is_assigned.php as source of truth.');
                }
                
                if (data.success) {
                    populateStudentsTable(data.students);
                    updateStatisticsUI(data.stats);
                    updatePaginationUI(data.total, data.page, data.limit);
                } else {
                    showToastNotification('Failed to load students data: ' + data.message, 'error');
                    tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading senior high students:', error);
                showToastNotification('Error loading students data: ' + error.message, 'error');

            }
        }

        function updateActionButtonsState() {
            // Separate administrative actions from clearance signatory actions
            // Administrative actions - check sector assignment for Add button
            // Import/Export should always be enabled for Program Heads
            
            // Check if Program Head has Senior High School sector assignment
            // Use canPerformSignatoryActions which is set by fetchCanTakeActionSHS()
            // This checks if the Program Head has departments in the SHS sector
            
            // Handle Add Student button separately (requires SHS sector assignment)
            document.querySelectorAll('.add-student-btn').forEach(btn => {
                try {
                    if (canPerformSignatoryActions) {
                        btn.disabled = false;
                        btn.classList.remove('disabled');
                        btn.title = 'Add a new senior high school student to the system';
                    } else {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                        btn.title = 'You are not assigned to the Senior High School sector';
                    }
                } catch (e) { /* ignore */ }
            });
            
            // Import/Export buttons - always enabled
            const alwaysEnabledSelectors = ['.import-btn', '.export-btn'];
            alwaysEnabledSelectors.forEach(sel => {
                document.querySelectorAll(sel).forEach(btn => {
                    try {
                        btn.disabled = false;
                        btn.classList.remove('disabled');
                        if (btn.classList.contains('import-btn')) {
                            btn.title = btn.title || 'Import students from file';
                        } else if (btn.classList.contains('export-btn')) {
                            btn.title = btn.title || 'Export student data';
                        }
                    } catch (e) { /* ignore */ }
                });
            });
            
            // Clearance signatory actions - only Approve/Reject buttons require active period
            // Other bulk actions (Delete, Selection Filters, Clear Selection) remain enabled
            
            // Only disable Approve/Reject buttons in bulk-buttons based on clearance period
            const bulkApproveRejectButtons = document.querySelectorAll('.bulk-buttons button');
            bulkApproveRejectButtons.forEach(btn => {
                try {
                    // Check if this is Approve or Reject button by checking onclick attribute
                    const onclickAttr = btn.getAttribute('onclick') || '';
                    const isApproveOrReject = onclickAttr.includes('approveSelected') || 
                                             onclickAttr.includes('rejectSelected');
                    
                    if (isApproveOrReject) {
                        // Approve/Reject buttons require active period AND permission
                        const canAct = canPerformSignatoryActions && hasActiveClearancePeriod;
                        btn.disabled = !canAct;
                        
                        if (!canAct) {
                            btn.classList.add('disabled');
                            if (!hasActiveClearancePeriod) {
                                btn.title = 'No active clearance period for this sector';
                            } else if (!canPerformSignatoryActions) {
                            btn.title = 'You do not have permission to take action on this page.';
                            }
                        } else {
                            btn.classList.remove('disabled');
                            if (btn.title && (btn.title.includes('permission') || btn.title.includes('clearance period'))) {
                                btn.title = '';
                            }
                        }
                    }
                    // Other buttons (Delete, etc.) are handled by updateBulkButtons() based on selection only
                    } catch (e) { /* ignore */ }
                });

            // Explicitly enable bulk selection filters and clear selection buttons
            // These should always be enabled regardless of clearance period status
            document.querySelectorAll('.bulk-selection-filters-btn').forEach(btn => {
                try {
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                    if (btn.title && btn.title.includes('clearance period')) {
                        btn.title = '';
                    }
                } catch (e) { /* ignore */ }
            });
            
            document.querySelectorAll('.clear-selection-btn, .btn-outline-secondary.clear-selection-btn').forEach(btn => {
                try {
                    // Clear selection button is enabled based on selection (handled by updateBulkButtons)
                    // Just make sure it's not disabled due to clearance period
                    if (btn.title && btn.title.includes('clearance period')) {
                        btn.title = '';
                    }
                } catch (e) { /* ignore */ }
            });

            // Disable/enable row checkboxes and select-all based on permission only (not clearance period)
            // Checkboxes should remain functional even without active clearance period
            const canAct = canPerformSignatoryActions;
            document.querySelectorAll('#studentsTableBody .student-checkbox').forEach(cb => cb.disabled = !canAct);
            const selectAll = document.getElementById('selectAllCheckbox') || document.getElementById('selectAll');
            if (selectAll) selectAll.disabled = !canAct;

            // Row-level button disabling based on individual clearance status
            // Approve/Reject buttons should only be enabled for Pending/Rejected statuses (actionable)
            // Note: Clearance status badges use 'signatory-*' classes (e.g., signatory-pending, signatory-approved)
            const rows = document.querySelectorAll('#studentsTableBody tr');
            rows.forEach(row => {
                const clearanceBadge = row.querySelector('.status-badge-compact[class*="signatory-"]');
                const clearanceStatus = clearanceBadge ? clearanceBadge.textContent.trim() : 'Unapplied';
                
                // Only Pending and Rejected are actionable
                const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus);
                
                // Disable individual row action buttons if not actionable
                const rowActionButtons = row.querySelectorAll('.action-buttons button');
                rowActionButtons.forEach(btn => {
                    const btnClass = btn.className;
                    // Approve and Reject buttons should be disabled if not actionable
                    if ((btnClass.includes('approve-btn') || btnClass.includes('reject-btn')) && !isActionable) {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                    } else if (btnClass.includes('approve-btn') || btnClass.includes('reject-btn')) {
                        btn.disabled = !canAct; // Enable/disable based on permission if actionable
                        if (!canAct) btn.classList.add('disabled');
                        else btn.classList.remove('disabled');
                    }
                });
            });
        }

        // Populate students table
        async function populateStudentsTable(students) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';
            
            if (!students || students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">No SHS students with pending actions found in your assigned departments.</td></tr>`;
                updateActionButtonsState();
                return;
                
            }

            for (const student of students) {
                const row = await createStudentRow(student);
                tbody.appendChild(row);
            }
            updateActionButtonsState();
        }

        // Update statistics
        function updateStatisticsUI(stats) {
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
        }

        // Note: deleteStudent function is defined earlier in the file (around line 817)

        // Update statistics after delete
        function updateStatisticsAfterDelete() {
            const totalStudents = document.querySelectorAll('#studentsTableBody tr').length;
            const activeStudents = document.querySelectorAll('#studentsTableBody tr .status-badge.account-active').length;
            const inactiveStudents = document.querySelectorAll('#studentsTableBody tr .status-badge.account-inactive').length;
            
            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('activeStudents').textContent = activeStudents;
            document.getElementById('inactiveStudents').textContent = inactiveStudents;
        }

        // Check if Program Head is assigned to Senior High School sector (uses centralized API)
        async function checkSeniorHighSectorAssignment() {
            try {
                const resp = await fetch('../../api/program-head/is_assigned.php?clearance_type=Senior High School', { credentials: 'include' });
                const data = await resp.json();
                return data && data.success && !!data.can_take_action;
            } catch (error) {
                console.error('Error checking senior high sector assignment:', error);
                return false;
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
                    const activePeriod = data.active_periods.find(p => p.sector === 'Senior High School');

                    if (activePeriod) {
                        const termValue = `${activePeriod.school_year}|${activePeriod.semester_id}`;
                        // Check if the option exists before setting it
                        if (schoolTermFilter.querySelector(`option[value="${termValue}"]`)) {
                            schoolTermFilter.value = termValue;
                        } else {
                            console.warn('Default school term option not found in filter:', termValue);
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

        // Initialize pagination when page loads
        document.addEventListener('DOMContentLoaded', async function() {
            updateTermIndicatorBanner();
            
            const tableWrapper = document.getElementById('studentsTableWrapper');
            if (tableWrapper) {
                tableWrapper.addEventListener('scroll', handleTableScroll);
            }

            // 1. Check permissions and load user-specific data first
            await fetchCanTakeActionSHS();
            window.isAssignedToSeniorHigh = canPerformSignatoryActions;
            if (!canPerformSignatoryActions) {
                showToastNotification('You are not assigned to the Senior High School sector. You have view-only access.', 'warning');
            }
            await loadProgramHeadProfile();

            // 2. Load all independent filter options and page data in parallel
            await Promise.all([
                loadRejectionReasons(),
                loadSchoolTermsFilter(),
                loadClearanceStatuses(),
                loadAccountStatuses(),
                loadPrograms(),
                loadYearLevels(),
                loadCurrentPeriod()
            ]);

            // 3. Set default filter values and load the main table data
            await setDefaultSchoolTerm();
            await loadStudentsData();
            
            // 4. Update action buttons state after all permissions and data are loaded
            updateActionButtonsState();

            // 4. Initialize UI components
            updateSelectionCounter();
        });

        // Add event listeners for student checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateBulkButtons();
                updateSelectionCounter();
            }
        });

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
            
            if (!rejectionReason) {
                showToastNotification('Please select a reason for rejection.', 'warning');
                return;
            }

            // Demo: Show rejection summary
            let rejectionSummary = '';
            if (currentRejectionData.isBulk) {
                const studentNumbers = currentRejectionData.targetIds;
                const userIds = [];
                for (const sid of studentNumbers) {
                    const uid = await resolveUserIdFromStudentNumber(sid);
                    if (uid) userIds.push(uid);
                }

                if (userIds.length === 0) {
                    showToastNotification('Could not identify users to reject.', 'error');
                    closeRejectionRemarksModal();
                    return;
                }

                await bulkSignatoryAction('Rejected', additionalRemarks, rejectionReason);
            } else {
                try {
                    const result = await sendSignatoryAction(currentRejectionData.targetId, 'Rejected', additionalRemarks, rejectionReason);
                    if (result.success) {
                        showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                        loadStudentsData();
                    } else {
                        showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (e) { showToastNotification('An error occurred during rejection.', 'error'); }
            }
            
            // Demo: Update UI and show success message
            if (currentRejectionData.isBulk) {
                // Update student table rows
                currentRejectionData.targetIds.forEach(id => {
                    const row = document.querySelector(`.student-checkbox[data-id="${id}"]`);
                    if (row) {
                        const tableRow = row.closest('tr');
                        if (tableRow) {
                            const clearanceBadge = tableRow.querySelector('.status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-approved');
                            if (clearanceBadge) {
                                clearanceBadge.textContent = 'Rejected';
                                clearanceBadge.classList.remove('clearance-pending', 'clearance-in-progress', 'clearance-approved');
                                clearanceBadge.classList.add('clearance-rejected');
                            }
                        }
                    }
                });
            }
            
            // Close modal
            closeRejectionRemarksModal();
        }

        async function sendBulkAction(payload) {
            try {
                const response = await fetch('../../api/clearance/bulk_signatory_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    showToastNotification(`Successfully performed action for ${result.affected_rows} students.`, 'success');
                } else {
                    throw new Error(result.message || 'Bulk action failed.');
                }
            } catch (error) {
                console.error('Bulk action error:', error);
                showToastNotification(error.message, 'error');
            } finally {
                if (currentRejectionData.isBulk) closeRejectionRemarksModal();
                loadStudentsData(); // Refresh the entire table
            }
        }

        // Helper: resolve user_id from student number via users API (exact username match)
        async function resolveUserIdFromStudentNumber(studentNumber){
            try{
                const r = await fetch('../../api/users/read.php?limit=5&search=' + encodeURIComponent(studentNumber), { credentials:'include' });
                const data = await r.json();
                const arr = data.users || [];
                const match = arr.find(u => String(u.username) === String(studentNumber));
                return match ? match.user_id : null;
            }catch(e){ return null; }
        }
        // Helper: send signatory action to backend
        async function sendSignatoryAction(applicantUserId, action, remarks, reasonId = null, designationName = 'Program Head') {
            // Get the currently selected school term from the filter to ensure approval goes to the correct period
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            const currentSchoolTerm = schoolTermFilter ? schoolTermFilter.value : '';

            const payload = { 
                applicant_user_id: applicantUserId, 
                action: action,
                designation_name: designationName
            };
            if (remarks) {
                payload.remarks = remarks;
            }
            if (reasonId) {
                payload.reason_id = reasonId;
            }
            // Include school_term if a specific term is selected
            if (currentSchoolTerm && currentSchoolTerm.trim() !== '') {
                payload.school_term = currentSchoolTerm.trim();
            }

            const response = await fetch('../../api/clearance/signatory_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)
            });
            return await response.json();
        }
    </script>

    <script>
        // SHS Student Management specific logic

        // Load Program Head's profile to get department assignments for modals
        async function loadProgramHeadProfile() {
            try {
                const response = await fetch('../../api/program-head/profile.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    // Store managed departments globally for the modal to use
                    window.managedDepartments = data.data.departments;
                    const deptNames = data.data.departments.map(d => d.department_name).join(', ');
                    document.getElementById('departmentScopeText').textContent = `Scope: ${deptNames}`;
                } else {
                    throw new Error(data.message || 'Failed to load profile.');
                }
            } catch (error) {
                console.error('Error loading Program Head profile:', error);
                document.getElementById('departmentScopeText').textContent = `Scope: Error loading departments`;
            }
        }

        // Update statistics
        function updateStatistics(stats) {
            document.getElementById('totalStudents').textContent = stats.total;
            document.getElementById('activeStudents').textContent = stats.active;
            document.getElementById('inactiveStudents').textContent = stats.inactive;
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

            const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus) && userExisted;
            const rejectButtonTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Signatory';

            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.setAttribute('data-student-id', student.id);
            row.setAttribute('data-form-id', student.clearance_form_id);
            row.setAttribute('data-signatory-id', student.signatory_id);
            row.setAttribute('data-remarks', student.remarks || '');
            row.setAttribute('data-rejection-reason-id', student.reason_id || '');
            
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
                <td class="checkbox-column"><input type="checkbox" class="student-checkbox" data-id="${student.id}"></td>
                <td data-label="Student Number:">${student.id}</td>
                <td data-label="Name:">${student.name}</td>
                <td data-label="Program:">${student.program || 'N/A'}</td>
                <td data-label="Year Level:">${student.year_level || 'N/A'}</td>
                <td data-label="Section:">${student.section || 'N/A'}</td>
                <td data-label="Account Status:"><span class="status-badge ${accountStatusClass}">${accountStatusText}</span></td>
                <td data-label="Clearance Form Progress:" class="clearance-status-cell">${clearanceProgressContent}</td>
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
                        <button class="btn-icon delete-btn" onclick="deleteStudent('${student.user_id}')" title="Delete Student">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            return row;
        }

        function viewClearanceProgress(studentId, studentName, schoolTerm = '') {
            // Forward the selected school term (if any) so the modal can show
            // the clearance progress scoped to that term.
            openClearanceProgressModal(studentId, 'student', studentName, schoolTerm);
        }

        function escapeHtml(unsafe) {
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // Signatory Action Functions
        async function approveSignatory(targetUserId) {
            const row = document.querySelector(`tr[data-user-id='${targetUserId}']`);
            const studentName = row ? row.cells[2].textContent : 'Student';

            showConfirmationModal(
                'Approve Clearance',
                `Are you sure you want to approve clearance for ${studentName}?`,
                'Approve',
                'Cancel',
                async () => {
                    try {
                        const result = await sendSignatoryAction(targetUserId, 'Approved', 'Approved by Program Head');

                        if (result.success) {
                            showToastNotification('Signatory approved successfully', 'success');
                            loadStudentsData(); // Refresh the list
                        } else {
                            showToastNotification('Failed to approve signatory: ' + (result.message || 'Unknown error'), 'error');
                        }
                    } catch (error) {
                        console.error('Error approving signatory:', error);
                        showToastNotification('Error approving signatory: ' + error.message, 'error');
                    }
                },
                'success'
            );
        }

        async function rejectSignatory(targetUserId) {
            try {
                const row = document.querySelector(`tr[data-user-id='${targetUserId}']`); // Correctly find the row
                const studentName = row ? row.cells[2].textContent : 'Student';
                // Note: Clearance status badges use 'signatory-*' classes (e.g., signatory-pending, signatory-rejected)
                const clearanceBadge = row ? row.querySelector('.status-badge-compact[class*="signatory-"]') : null;
                const currentStatus = clearanceBadge ? clearanceBadge.textContent.trim() : '';
                const clearanceFormId = row ? row.getAttribute('data-form-id') : null;
                const signatoryId = row ? row.getAttribute('data-signatory-id') : null;

                let existingRemarks = '';
                let existingReasonId = '';

                // If the student is already rejected, fetch the existing details.
                if (currentStatus === 'Rejected' && signatoryId) {
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
                }
                openRejectionRemarksModal(targetUserId, studentName, 'student', false, [], clearanceFormId, signatoryId, existingRemarks, existingReasonId);
            } catch (error) {
                console.error('Error opening rejection modal:', error);
                showToastNotification('Error opening rejection modal: ' + error.message, 'error');
            }
        }

        function openRejectionRemarksModal(userId, studentName, type = 'student', isBulk = false, bulkData = [], clearanceFormId = null, signatoryId = null, existingRemarks = '', existingReasonId = '') {
            try {
                const modal = document.getElementById('rejectionRemarksModal');
                if (!modal) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Rejection feature is temporarily unavailable.', 'error');
                    }
                    return;
                }

                currentRejectionData = {
                    userId: userId,
                    studentName: studentName,
                    isBulk: isBulk,
                    bulkData: bulkData,
                    formId: clearanceFormId,
                    signatoryId: signatoryId
                };

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

                // Pre-fill form if existing data is passed, otherwise reset
                reasonSelect.value = existingReasonId || '';
                remarksTextarea.value = existingRemarks || '';

                // Update display
                if (isBulk) {
                    targetNameElement.textContent = `Rejecting: ${bulkData.length} Selected Students`;
                } else {
                    targetNameElement.textContent = `Rejecting: ${studentName}`;
                }
                targetTypeElement.textContent = 'Student';

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
            } catch (error) {
                // Silent error handling
            }
        }

        async function loadRejectionReasons() {
            const reasonSelect = document.getElementById('rejectionReason');
            if (!reasonSelect) return;

            try {
                const response = await fetch('../../api/clearance/rejection_reasons.php?category=student', { credentials: 'include' });
                const data = await response.json();
                
                reasonSelect.innerHTML = '<option value="">Select a reason...</option>';
                if (data.success && data.rejection_reasons) {
                    const studentReasons = data.rejection_reasons.filter(r => r.reason_category === 'student' || r.reason_category === 'both');
                    studentReasons.forEach(reason => {
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

        // --- DYNAMIC FILTER POPULATION ---
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
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=users&column=account_status&exclude=graduated,resigned`;
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
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

        async function loadPrograms() {
            const url = `../../api/clearance/get_filter_options.php?type=programs&sector=Senior High School`;
            await populateFilter('programFilter', url, 'All Programs', 'program_id', 'program_name');
        }

        async function loadYearLevels() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=students&column=year_level&filter_by_sector=Senior High School`;
            await populateFilter('yearLevelFilter', url, 'All Year Levels');
        }
    </script>
    
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Include Universal Modal Handler -->
    <script src="../../assets/js/modal-handler.js"></script>
</body>
</html>
