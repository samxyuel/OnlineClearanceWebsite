<?php
// Online Clearance Website - School Administrator Senior High School Student Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session data is handled by authentication system
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
                            <h2><i class="fas fa-graduation-cap"></i> Senior High School Student Management</h2>
                            <p>Manage senior high school students and sign their clearances across all departments</p>
                            <div class="department-scope-info">
                                <i class="fas fa-shield-alt"></i>
                                <span>Scope: Senior High School Departments (School-wide Access)</span>
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
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="graduatedStudents">156</h3>
                                    <p>Graduated</p>
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
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterGraduated" value="graduated">
                                <span class="checkmark"></span>
                                with "graduated"
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
                        <h5><i class="fas fa-list"></i> Pending Clearances for Selected Position</h5>
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
                <input type="text" id="overrideSearchInput" class="form-control" placeholder="Search students...">
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
                <select id="overrideYearFilter" class="form-control">
                    <option value="">All Years</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
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

        // Select all functionality
        function toggleSelectAll(checked) {
            const studentCheckboxes = document.querySelectorAll('#studentsTableBody .student-checkbox');
            studentCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                // Only toggle visible and enabled rows, respecting current filters
                if (row && row.style.display !== 'none' && !checkbox.disabled) {
                    checkbox.checked = checked;
                }
            });
            updateBulkButtons();
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
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
            });
            
            updateSelectionCounter();
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

        // Bulk Actions with Confirmation - School Administrator as Signatory
        async function approveSelected() {
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
                        if (userId) userIds.push(userId);
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
                            showToastNotification(`Successfully approved clearance for ${result.affected_rows} students.`, 'success');
                        } else {
                            throw new Error(result.message || 'Bulk approval failed.');
                        }
                    } catch (error) {
                        console.error('Bulk approval error:', error);
                        showToastNotification(error.message, 'error');
                    } finally {
                        loadStudentsData();
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
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-id'));
            openRejectionRemarksModal(null, null, 'student', true, selectedIds);
        }

        function markGraduated() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to mark as graduated', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Mark Students as Graduated',
                `Are you sure you want to mark ${selectedCount} selected students as Graduated? This will change their status permanently.`,
                'Mark as Graduated',
                'Cancel',
                () => {
                    // Perform graduation
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Graduated';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-graduated');
                        }
                    });
                    
                    // Update statistics
                    updateBulkStatistics('graduated', selectedCount);
                    showToastNotification(`✓ Successfully marked ${selectedCount} students as Graduated`, 'success');
                },
                'info'
            );
        }

        // Reset clearance status for new term
        function resetClearanceForNewTerm() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to reset clearance status', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Reset Clearance Status',
                `Are you sure you want to reset clearance status to "Unapplied" for ${selectedCount} selected students? This will reset their clearance progress for the new term.`,
                'Reset Clearance',
                'Cancel',
                () => {
                    // Perform clearance reset
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Unapplied';
                            clearanceBadge.classList.remove('clearance-pending', 'clearance-completed', 'clearance-rejected', 'clearance-in-progress');
                            clearanceBadge.classList.add('clearance-unapplied');
                        }
                    });
                    
                    showToastNotification(`✓ Successfully reset clearance status for ${selectedCount} students`, 'success');
                },
                'warning'
            );
        }

        // getSelectedCount consolidated later in the file

        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeStudents');
            const inactiveCount = document.getElementById('inactiveStudents');
            const graduatedCount = document.getElementById('graduatedStudents');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            let currentGraduated = parseInt(graduatedCount.textContent.replace(',', ''));
            
            if (action === 'graduated') {
                // Move from active/inactive to graduated
                currentGraduated += count;
                // We'd need to track which students were active vs inactive
                // For now, we'll assume they were active
                currentActive -= count;
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
            graduatedCount.textContent = currentGraduated.toLocaleString();
        }

        // Individual student actions - School Administrator as Signatory
        function editStudent(studentId) {
            openEditStudentModal(studentId);
        }

        async function approveStudentClearance(studentId) {
            const row = document.querySelector(`.student-checkbox[data-id="${studentId}"]`).closest('tr');
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
            
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
                    clearanceBadge.textContent = 'Completed';
                    clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                    clearanceBadge.classList.add('clearance-completed');
                    try {
                        const uid = await resolveUserIdFromStudentNumber(studentId);
                        if (uid) { await sendSignatoryAction(uid, 'School Administrator', 'Approved'); }
                    } catch (e) {}
                    showToastNotification('Student clearance approved successfully', 'success');
                },
                'success'
            );
        }

        function rejectStudentClearance(studentId) {
            const row = document.querySelector(`.student-checkbox[data-id="${studentId}"]`).closest('tr');
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed');
            
            if (!clearanceBadge) {
                showToastNotification('No clearance to reject', 'warning');
                return;
            }
            
            // Open rejection remarks modal instead of confirmation
            openRejectionRemarksModal(studentId, studentName, 'student', false);
        }

        function applyFilters() {
            currentPage = 1;
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
            document.getElementById('schoolTermFilter').checked = false;
            
            // Show all rows
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            // Update pagination with all entries
            updateFilteredEntries();
            
            showInfoToast('All filters cleared');
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        let filteredEntries = [];

        // Initialize pagination
        function initializePagination() {
            const allRows = document.querySelectorAll('#studentsTableBody tr');
            totalEntries = allRows.length;
            filteredEntries = Array.from(allRows);
            updatePagination();
        }

        // Update pagination display
        function updatePagination() {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            const startEntry = (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, filteredEntries.length);
            
            // Update pagination info
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${filteredEntries.length} entries`;
            
            // Update page numbers
            updatePageNumbers(totalPages);
            
            // Update navigation buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
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
        
        // Update filtered entries when filters are applied
        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#studentsTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1;
            updatePagination();
        }

        // Update filtered entries when filters are applied
        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#studentsTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1;
            updatePagination();
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

            try {
                const response = await fetch(url.toString(), {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();
                console.log('College students API response:', data);
                
                if (data.success) {
                    populateStudentsTable(data.students);
                    updateStatisticsUI(data.stats);
                    updatePaginationUI(data.total, data.page, data.limit);
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

            let clearanceStatus = student.clearance_status || 'Unapplied';
            const clearanceStatusClass = `clearance-${clearanceStatus.toLowerCase().replace(/ /g, '-')}`;
            
            // Capture the currently selected school term from the filters so we can
            // display clearance progress scoped to that term when the user opens the modal.
            const currentSchoolTerm = document.getElementById('schoolTermFilter') ? document.getElementById('schoolTermFilter').value : '';

            // Determine button titles and states based on clearance status
            const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus);
            const rejectButtonTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Signatory';

            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.setAttribute('data-student-id', student.id);
            row.setAttribute('data-form-id', student.clearance_form_id);
            row.setAttribute('data-signatory-id', student.signatory_id);

            row.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="student-checkbox" data-id="${student.id}"  ${!isActionable ? 'disabled' : ''}></td>
                <td data-label="Student Number:">${student.id}</td>
                <td data-label="Name:">${student.name}</td>
                <td data-label="Program:">${student.program || 'N/A'}</td>
                <td data-label="Year Level:">${student.year_level || 'N/A'}</td>
                <td data-label="Section:">${student.section || 'N/A'}</td>
                <td data-label="Account Status:"><span class="status-badge ${accountStatusClass}">${accountStatusText}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge clearance-unapplied">Unapplied</span></td>
                <td data-label="Clearance Status:"><span class="status-badge ${clearanceStatusClass}">${clearanceStatus}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        <button class="btn-icon view-progress-btn" onclick="viewClearanceProgress('${student.user_id}', '${escapeHtml(student.name)}', '${escapeHtml(currentSchoolTerm)}')" title="View Clearance Progress">
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
        function updateStatistics(students) {
            const stats = {
                total: students.length,
                active: students.filter(s => s.status === 'active').length,
                inactive: students.filter(s => s.status === 'inactive').length,
                graduated: 0 // No graduated status in account_status
            };
            
            document.getElementById('totalStudents').textContent = stats.total;
            document.getElementById('activeStudents').textContent = stats.active;
            document.getElementById('inactiveStudents').textContent = stats.inactive;
            document.getElementById('graduatedStudents').textContent = stats.graduated;
        }

        // Update statistics
        function updateStatisticsUI(stats) {
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
            document.getElementById('graduatedStudents').textContent = stats.graduated || 0;
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

        // Initialize signatory buttons based on user's signatory status
        async function initializeSignatoryButtons() {
            const isSignatory = await checkSignatoryStatus('Senior High School');
            console.log('User is signatory for Senior High School:', isSignatory);
            
            if (!isSignatory) {
                // Hide only the approve/reject buttons if user is not a signatory
                document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
                    btn.style.display = 'none';
                });
                
                // Update the Clearance Status column to show "Not Assigned"
                document.querySelectorAll('#studentsTableBody tr').forEach(row => {
                    const cells = row.children;
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

        // Signatory Override Modal Function
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
                        <h4>Zinzu Chan Lee</h4>
                        <p>BSIT - 3rd Year | ICT Department</p>
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
                        <h4>John Doe</h4>
                        <p>BSCS - 4th Year | ICT Department</p>
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
                <div class="preview-item">Zinzu Chan Lee - BSIT (Pending)</div>
                <div class="preview-item">John Doe - BSCS (In Progress)</div>
                <div class="preview-item">Sarah Smith - BSIS (Pending)</div>
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
            const studentName = clearanceItem.querySelector('h4').textContent;
            
            // Open rejection remarks modal for override rejection
            openRejectionRemarksModal(id, studentName, 'student', false);
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
            openRejectionRemarksModal(null, null, 'student', true, selectedIds);
        }

        function searchOverrideClearances() {
            const searchTerm = document.getElementById('overrideSearchInput').value.toLowerCase();
            const clearanceItems = document.querySelectorAll('.clearance-item');
            
            clearanceItems.forEach(item => {
                const studentName = item.querySelector('h4').textContent.toLowerCase();
                if (studentName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function exportOverrideReport() {
            showToastNotification('Override report export functionality will be implemented', 'info');
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
                // Check if we're in signatory override mode
                const isOverrideMode = document.getElementById('overrideSessionInterface').style.display !== 'none';
                
                if (isOverrideMode) {
                    // Update override clearance items
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
                    
                    // Uncheck all override checkboxes
                    document.getElementById('overrideSelectAll').checked = false;
                    currentRejectionData.targetIds.forEach(id => {
                        const checkbox = document.querySelector(`.override-checkbox[data-id="${id}"]`);
                        if (checkbox) checkbox.checked = false;
                    });
                    updateOverrideBulkButtons();
                    
                    try {
                        for (const id of currentRejectionData.targetIds) {
                            const uid = await resolveUserIdFromStudentNumber(id);
                            if (uid) { await sendSignatoryAction(uid, 'School Administrator', 'Rejected', additionalRemarks); }
                        }
                    } catch (e) {}
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} students with remarks`, 'success');
                } else {
                    // Update regular student table
                    currentRejectionData.targetIds.forEach(id => {
                        const row = document.querySelector(`.student-checkbox[data-id="${id}"]`);
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
                        const checkbox = document.querySelector(`.student-checkbox[data-id="${id}"]`);
                        if (checkbox) checkbox.checked = false;
                    });
                    updateBulkButtons();
                    
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} students with remarks`, 'success');
                }
            } else {
                // Check if we're in signatory override mode
                const isOverrideMode = document.getElementById('overrideSessionInterface').style.display !== 'none';
                
                if (isOverrideMode) {
                    // Update override clearance item
                    const clearanceItem = document.querySelector(`.override-checkbox[data-id="${currentRejectionData.targetId}"]`);
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
                    
                    try {
                        const uid = await resolveUserIdFromStudentNumber(currentRejectionData.targetId);
                        if (uid) { await sendSignatoryAction(uid, 'School Administrator', 'Rejected', additionalRemarks); }
                    } catch (e) {}
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                } else {
                    // Update regular student table
                    const row = document.querySelector(`.student-checkbox[data-id="${currentRejectionData.targetId}"]`);
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
                    
                    showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                }
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
        async function sendSignatoryAction(applicantUserId, designationName, action, remarks){
            const payload = { applicant_user_id: applicantUserId, designation_name: designationName, action: action };
            if (remarks && remarks.length) payload.remarks = remarks;
            await fetch('../../api/clearance/signatory_action.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)}).then(r=>r.json()).catch(()=>null);
        }
    </script>
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/activity-tracker.js"></script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
    
    <!-- Initialize Activity Tracker -->
    <script>


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
                active: document.getElementById('filterActive').checked,
                inactive: document.getElementById('filterInactive').checked,
                graduated: document.getElementById('filterGraduated').checked,
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
                const hasAccountFilter = filters.active || filters.inactive || filters.graduated;
                if (hasAccountFilter && accountBadge) {
                    if (filters.active && accountBadge.classList.contains('account-active')) accountMatch = true;
                    if (filters.inactive && accountBadge.classList.contains('account-inactive')) accountMatch = true;
                    if (filters.graduated && accountBadge.classList.contains('account-graduated')) accountMatch = true;
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
            const checkboxes = ['filterActive', 'filterInactive', 'filterGraduated', 'filterUnapplied', 'filterApplied', 'filterInProgress', 'filterComplete', 'filterPending', 'filterApproved', 'filterRejected'];
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
        async function approveSignatory(userId, clearanceFormId, signatoryId) {
            try {
                const response = await fetch('../../api/clearance/apply_signatory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        operation: 'approve',
                        target_user_id: userId,
                        signatory_id: signatoryId,
                        clearance_form_id: clearanceFormId,
                        remarks: 'Approved by School Administrator'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToastNotification('Signatory approved successfully', 'success');
                    updateSignatoryActionUI(userId, 'Approved');
                } else {
                    showToastNotification('Failed to approve signatory: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error approving signatory:', error);
                showToastNotification('Error approving signatory: ' + error.message, 'error');
            }
        }

        async function rejectSignatory(userId, clearanceFormId, signatoryId) {
            openRejectionModal(userId, clearanceFormId, signatoryId);
        }

        function openRejectionModal(userId, clearanceFormId, signatoryId) {
            window.pendingRejection = {
                userId: userId,
                clearanceFormId: clearanceFormId,
                signatoryId: signatoryId
            };
            
            showConfirmationModal(
                'Reject Signatory',
                'Please provide a reason for rejection:',
                'Reject',
                'Cancel',
                () => {
                    const remarks = prompt('Please provide rejection remarks:');
                    if (remarks !== null) {
                        submitRejection(remarks);
                    }
                },
                'danger'
            );
        }

        async function submitRejection(remarks) {
            if (!window.pendingRejection) return;
            
            const { userId, clearanceFormId, signatoryId } = window.pendingRejection;
            
            try {
                const response = await fetch('../../api/clearance/apply_signatory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        operation: 'reject',
                        target_user_id: userId,
                        signatory_id: signatoryId,
                        clearance_form_id: clearanceFormId,
                        remarks: remarks
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToastNotification('Signatory rejected successfully', 'success');
                    updateSignatoryActionUI(userId, 'Rejected');
                } else {
                    showToastNotification('Failed to reject signatory: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error rejecting signatory:', error);
                showToastNotification('Error rejecting signatory: ' + error.message, 'error');
            } finally {
                window.pendingRejection = null;
            }
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
            console.log('User is signatory for Senior High School:', isSignatory);
            
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


        document.addEventListener('DOMContentLoaded', async function() {
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for School Administrator Student Management');
            }

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
            initializePagination();
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
            loadPrograms(departmentId);
        }

        // Add event listeners for student checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateBulkButtons();
                updateSelectionCounter();
            }

            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('student-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });

            window.currentTabStatus = '';
        });
    </script>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
</body>
</html>
