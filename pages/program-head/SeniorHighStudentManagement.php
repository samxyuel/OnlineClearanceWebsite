<?php
// Online Clearance Website - Program Head Senior High School Student Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Page guard: Program Head must have student-sector assignment (College/SHS)
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
    // Verify role Program Head (TEMPORARILY RELAXED FOR TESTING)
    $roleOk = false;
    $rs = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id=r.role_id WHERE ur.user_id=? LIMIT 1");
    $rs->execute([$userId]);
    $rn = strtolower((string)$rs->fetchColumn());
    // Allow Admin or Program Head access
    if ($rn === 'program head' || $rn === 'admin') { $roleOk = true; }

    // Check student-sector assignment - COMMENTED OUT TO ALLOW ALL PROGRAM HEADS ACCESS
    // $sql = "SELECT COUNT(*) FROM signatory_assignments sa
    //         JOIN designations des ON sa.designation_id=des.designation_id
    //         JOIN departments d ON sa.department_id=d.department_id
    //         JOIN sectors s ON d.sector_id=s.sector_id
    //         WHERE sa.user_id=? AND sa.is_active=1 AND des.designation_name='Program Head' AND s.sector_name IN ('College','Senior High School')";
    // $st = $pdo->prepare($sql);
    // $st->execute([$userId]);
    // $hasStudentSector = ((int)$st->fetchColumn()) > 0;

    if (!$roleOk) {
        // If PH has faculty only, redirect to PH FacultyManagement; else to PH dashboard
        $sf = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments sa JOIN designations des ON sa.designation_id=des.designation_id JOIN departments d ON sa.department_id=d.department_id JOIN sectors s ON d.sector_id=s.sector_id WHERE sa.user_id=? AND sa.is_active=1 AND des.designation_name='Program Head' AND s.sector_name='Faculty'");
        $sf->execute([$userId]);
        $hasFacultySector = ((int)$sf->fetchColumn()) > 0;
        if ($hasFacultySector) {
            header('Location: ../../pages/program-head/FacultyManagement.php');
        } else {
            header('Location: ../../pages/program-head/dashboard.php');
        }
        exit;
    }
} catch (Throwable $e) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}
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
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="graduatedStudents">--</h3>
                                    <p>Graduated</p>
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
                            <!-- Tab Navigation for quick status views -->
                            <div class="tab-nav" id="studentTabNav">
                                <button class="tab-pill active" data-status="" onclick="switchStudentTab(this)">
                                    Overall
                                </button>
                                <button class="tab-pill" data-status="active" onclick="switchStudentTab(this)">
                                    Active
                                </button>
                                <button class="tab-pill" data-status="inactive" onclick="switchStudentTab(this)">
                                    Inactive
                                </button>
                                <button class="tab-pill" data-status="graduated" onclick="switchStudentTab(this)">
                                    Graduated
                                </button>
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
                                    <option value="graduated">Graduated Only</option>
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
                                    <button class="btn btn-success" onclick="openSeniorHighBatchUpdateModal()">
                                        <i class="fas fa-users-cog"></i> Batch Update
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
                                        <button class="btn btn-info" onclick="markGraduated()" disabled>
                                            <i class="fas fa-graduation-cap"></i> Graduated
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="resetClearanceForNewTerm()" disabled>
                                            <i class="fas fa-redo"></i> Reset Clearance
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
                
                <!-- Activity Tracker Sidebar -->
                <div class="dashboard-sidebar">
                    <?php include '../../includes/components/activity-tracker.php'; ?>
                </div>
            </div>
        </div>
    </main>

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
    
    <!-- Include Senior High School Batch Update Modal -->
    <?php include '../../Modals/SeniorHighSchoolBatchUpdateModal.php'; ?>
    
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

        // Select all functionality
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
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
            
            // Enable/disable bulk action buttons
            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
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
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Approved';
                            clearanceBadge.classList.remove('clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                            clearanceBadge.classList.add('clearance-approved');
                        }
                    });
                    
                    showToastNotification(`✓ Successfully approved ${selectedCount} students' clearances`, 'success');
                },
                'success'
            );
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
            openRejectionRemarksModal(null, null, 'student', true, selectedIds);
        }

        function deleteSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to delete', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Delete Students',
                `Are you sure you want to delete ${selectedCount} selected students? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        row.remove();
                    });
                    
                    updateBulkStatistics('delete', selectedCount);
                    showToastNotification(`✓ Successfully deleted ${selectedCount} students`, 'success');
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
            const clearanceBadge = row.querySelector('.status-badge[class*="clearance-"]');

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
            const checkbox = document.querySelector(`.student-checkbox[data-id="${studentId}"]`);
            
            if (!checkbox) {
                console.error('Student checkbox not found for ID:', studentId);
                showToastNotification('Error: Could not find student', 'error');
                return;
            }
            
            const row = checkbox.closest('tr');
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            
            showConfirmationModal(
                'Delete Student',
                `Are you sure you want to delete ${studentName}? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    fetch('../../api/users/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include', // Important for session
                        body: JSON.stringify({ student_id: studentId })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            row.remove();
                            showToastNotification(`${studentName} has been deleted`, 'success');
                            loadStudentsData(); // Refresh stats and table
                        } else {
                            showToastNotification('Failed to delete student: ' + result.message, 'error');
                        }
                    })
                    .catch(error => {
                        showToastNotification('Error deleting student: ' + error.message, 'error');
                    });
                },
                'danger'
            );
        }

        // Helper function to get selected count
        function getSelectedCount() {
            return document.querySelectorAll('.student-checkbox:checked').length;
        }
        
        // Individual student actions
        function editStudent(studentId) {
            openEditStudentModal(studentId);
        }

        function markGraduated() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to mark as graduated', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Mark Students as Graduated',
                `Are you sure you want to mark ${selectedCount} selected students as Graduated?`,
                'Mark as Graduated',
                'Cancel',
                () => {
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
                        const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Graduated';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-graduated');
                            toggleBtn.style.display = 'none';
                        }
                    });
                    
                    updateBulkStatistics('graduated', selectedCount);
                    showToastNotification(`✓ Successfully marked ${selectedCount} students as Graduated`, 'success');
                },
                'info'
            );
        }

        function resetClearanceForNewTerm() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to reset clearance status', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Reset Clearance Status',
                `Are you sure you want to reset clearance status to "Unapplied" for ${selectedCount} selected students?`,
                'Reset Clearance',
                'Cancel',
                () => {
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

        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeStudents');
            const inactiveCount = document.getElementById('inactiveStudents');
            const graduatedCount = document.getElementById('graduatedStudents');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            let currentGraduated = parseInt(graduatedCount.textContent.replace(',', ''));
            
            if (action === 'activate') {
                currentActive += count;
                currentInactive -= count;
            } else if (action === 'deactivate') {
                currentActive -= count;
                currentInactive += count;
            } else if (action === 'graduated') {
                currentGraduated += count;
                currentActive -= count;
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
            graduatedCount.textContent = currentGraduated.toLocaleString();
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

        // Modal functions
        function openAddStudentModal() {
            openStudentRegistrationModal();
        }

        function triggerImportModal() {
            if (typeof window.openImportModal === 'function') {
                window.openImportModal();
            } else {
                console.error('Import modal function not found');
            }
        }

        function triggerExportModal() {
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else {
                console.error('Export modal function not found');
            }
        }

        // Apply filters to the table
        function applyFilters() {
            currentPage = 1;
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
            
            updateFilteredEntries();
            showToastNotification('All filters cleared', 'info');
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        let filteredEntries = [];
        
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
        
        // Update filtered entries when filters are applied
        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#studentsTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1;
            updatePagination();
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

        // Tab navigation functions
        function switchStudentTab(button) {
            const status = button.getAttribute('data-status');
            window.currentTabStatus = status;
            
            // Update tab appearance
            document.querySelectorAll('.tab-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            button.classList.add('active');
            
            // Update mobile select
            const mobileSelect = document.getElementById('studentTabSelect');
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
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            
            tableRows.forEach(row => {
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                
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
            
            // Update pagination
            updateFilteredEntries();
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

            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
            if (programId) url.searchParams.append('program_id', programId);
            if (yearLevel) url.searchParams.append('year_level', yearLevel);
            if (schoolTerm) url.searchParams.append('school_term', schoolTerm);

            try {
                console.log('Loading senior high students data...');
                const response = await fetch(url.toString(), {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Senior high students API response:', data);
                
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

        // Populate students table
        async function populateStudentsTable(students) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';
            
            if (!students || students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">No SHS students with pending actions found in your assigned departments.</td></tr>`;
                return;
            }

            for (const student of students) {
                const row = await createStudentRow(student);
                tbody.appendChild(row);
            }
        }

        // Create student row
        async function createStudentRow(student) {
            // Map account status to display status
            const displayStatus = student.status === 'active' ? 'active' : 'inactive';
            
            // Check if Program Head is assigned to Senior High School sector
            const isAssignedToSeniorHigh = await checkSeniorHighSectorAssignment();
            
            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="student-checkbox" data-id="${student.user_id}"></td>
                <td data-label="Student Number:">${student.student_id || student.username}</td>
                <td data-label="Name:">${student.last_name}, ${student.first_name} ${student.middle_name || ''}</td>
                <td data-label="Program:">${student.program || 'N/A'}</td>
                <td data-label="Year Level:">${student.year_level || 'N/A'}</td>
                <td data-label="Section:">${student.section || 'N/A'}</td>
                <td data-label="Account Status:"><span class="status-badge account-${displayStatus}">${student.status === 'active' ? 'Active' : 'Inactive'}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge clearance-${(student.clearance_status || 'unapplied').toLowerCase().replace(' ', '-')}">${student.clearance_status || 'Unapplied'}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        ${isAssignedToSeniorHigh ? 
                            `<button class="btn-icon edit-btn" onclick="editStudent('${student.user_id}')" title="Edit Student">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon delete-btn" onclick="deleteStudent('${student.user_id}')" title="Delete Student">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="btn-icon approve-btn" onclick="approveSignatory('${student.user_id}', 'CF-2025-00001', 1)" title="Approve Signatory">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn-icon reject-btn" onclick="rejectSignatory('${student.user_id}', 'CF-2025-00001', 1)" title="Reject Signatory">
                                <i class="fas fa-times"></i>
                            </button>` :
                            `<span class="text-muted" style="font-size: 0.85rem; color: #6c757d;">Not Assigned</span>`
                        }
                    </div>
                </td>
            `;
            return row;
        }

        // Update statistics
        function updateStatisticsUI(stats) {
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
            document.getElementById('graduatedStudents').textContent = stats.graduated || 0;
        }

        // Edit student function
        function editStudent(user_id) {
            // Open edit student modal
            openEditStudentModal(user_id);
        }

        // Delete student function
        function deleteStudent(studentId) {
            // Get student name from the table row
            const row = document.querySelector(`tr[data-user-id="${studentId}"]`);
            const studentName = row ? row.querySelector('td:nth-child(3)').textContent : 'Student';
            
            showConfirmationModal(
                'Delete Student',
                `Are you sure you want to delete ${studentName}? This action cannot be undone.`,
                'Delete',
                'Cancel',
                async () => {
                    try {
                        // Call delete API
                        const response = await fetch('../../api/users/delete_student.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            credentials: 'include',
                            body: JSON.stringify({
                                student_id: studentId
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Remove row from table
                            const row = document.querySelector(`tr[data-user-id="${studentId}"]`);
                            if (row) {
                                row.remove();
                            }
                            
                            // Update statistics
                            updateStatisticsAfterDelete();
                            
                            showToastNotification(`Student ${studentName} deleted successfully`, 'success');
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

        // Update statistics after delete
        function updateStatisticsAfterDelete() {
            const totalStudents = document.querySelectorAll('#studentsTableBody tr').length;
            const activeStudents = document.querySelectorAll('#studentsTableBody tr .status-badge.account-active').length;
            const inactiveStudents = document.querySelectorAll('#studentsTableBody tr .status-badge.account-inactive').length;
            
            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('activeStudents').textContent = activeStudents;
            document.getElementById('inactiveStudents').textContent = inactiveStudents;
        }

        // Check if Program Head is assigned to Senior High School sector
        async function checkSeniorHighSectorAssignment() {
            try {
                const response = await fetch('../../api/clearance/check_signatory_status.php?sector=Senior High School', {
                    credentials: 'include'
                });
                const data = await response.json();
                return data.success && data.is_signatory;
            } catch (error) {
                console.error('Error checking senior high sector assignment:', error);
                return false;
            }
        }

        // Initialize pagination when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const tableWrapper = document.getElementById('studentsTableWrapper');
            window.isAssignedToSeniorHigh = false;
            if (tableWrapper) {
                tableWrapper.addEventListener('scroll', handleTableScroll);
            }

            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker({
                    userRole: 'Program Head'
                });
            }

            // Load current clearance period
            loadCurrentPeriod();
            
            // Initialize tab status
            window.currentTabStatus = '';
            
            // Load Program Head profile and then other data
            checkSeniorHighSectorAssignment().then(isAssigned => {
                window.isAssignedToSeniorHigh = isAssigned;
                if (!isAssigned) {
                    showToastNotification('You are not assigned to the Senior High School sector. You have view-only access.', 'warning');
                }
                loadProgramHeadProfile().then(() => {
                    loadStudentsData();
                    loadRejectionReasons();
                    loadSchoolTerms();
                    loadClearanceStatuses();
                    loadAccountStatuses();
                    loadPrograms();
                    loadYearLevels();
                });
            });

            // Initialize UI components
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
                // Attempt server-side record for each
                try {
                    for (const id of currentRejectionData.targetIds) {
                        const uid = await resolveUserIdFromStudentNumber(id);
                        if (uid) { await sendSignatoryAction(uid, 'Program Head', 'Rejected', additionalRemarks); }
                    }
                } catch (e) { /* ignore */ }
                
                // Uncheck all checkboxes
                document.getElementById('selectAll').checked = false;
                currentRejectionData.targetIds.forEach(id => {
                    const checkbox = document.querySelector(`.student-checkbox[data-id="${id}"]`);
                    if (checkbox) checkbox.checked = false;
                });
                updateBulkButtons();
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} students with remarks`, 'success');
            } else {
                // Update individual student row
                const row = document.querySelector(`.student-checkbox[data-id="${currentRejectionData.targetId}"]`);
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
                // Attempt server-side record
                try {
                    const uid = await resolveUserIdFromStudentNumber(currentRejectionData.targetId);
                    if (uid) { await sendSignatoryAction(uid, 'Program Head', 'Rejected', additionalRemarks); }
                } catch (e) { /* ignore */ }
                
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

        // Populate students table
        function populateStudentsTable(students) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';
            
            if (!students || students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">No SHS students with pending actions found in your assigned departments.</td></tr>`;
                return;
            }

            for (const student of students) {
                const row = createStudentRow(student);
                tbody.appendChild(row);
            }
        }

        // Update statistics
        function updateStatistics(stats) {
            document.getElementById('totalStudents').textContent = stats.total;
            document.getElementById('activeStudents').textContent = stats.active;
            document.getElementById('inactiveStudents').textContent = stats.inactive;
            document.getElementById('graduatedStudents').textContent = stats.graduated;
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

            let clearanceStatus = student.clearance_status || 'Unapplied';
            const clearanceStatusClass = `clearance-${clearanceStatus.toLowerCase().replace(/ /g, '-')}`;

            const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus);
            const rejectButtonTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Signatory';

            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.setAttribute('data-student-id', student.id);
            row.setAttribute('data-form-id', student.clearance_form_id);
            row.setAttribute('data-signatory-id', student.signatory_id);
            row.setAttribute('data-remarks', student.remarks || '');
            row.setAttribute('data-rejection-reason-id', student.reason_id || '');

            row.innerHTML = `
                <td class="checkbox-column"><input type="checkbox" class="student-checkbox" data-id="${student.id}"></td>
                <td data-label="Student Number:">${student.id}</td>
                <td data-label="Name:">${student.name}</td>
                <td data-label="Program:">${student.program || 'N/A'}</td>
                <td data-label="Year Level:">${student.year_level || 'N/A'}</td>
                <td data-label="Section:">${student.section || 'N/A'}</td>
                <td data-label="Account Status:"><span class="status-badge ${accountStatusClass}">${accountStatusText}</span></td>
                <td data-label="Clearance Progress:"><span class="status-badge ${clearanceStatusClass}">${clearanceStatus}</span></td>
                <td class="action-buttons">
                    <div class="action-buttons">
                        <button class="btn-icon edit-btn" onclick="editStudent('${student.id}')" title="Edit Student">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon approve-btn" onclick="approveSignatory('${student.user_id}')" title="Approve Signatory" ${!isActionable ? 'disabled' : ''}>
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon reject-btn" onclick="rejectSignatory('${student.user_id}', '${student.clearance_form_id}', '${student.signatory_id}')" title="${rejectButtonTitle}" ${!isActionable ? 'disabled' : ''}>
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="btn-icon delete-btn" onclick="deleteStudent('${student.id}')" title="Delete Student">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            return row;
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
                const clearanceBadge = row ? row.querySelector('.status-badge[class*="clearance-"]') : null;
                const currentStatus = clearanceBadge ? clearanceBadge.textContent.trim() : '';

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
            currentRejectionData = {
                userId: userId,
                studentName: studentName,
                isBulk: isBulk,
                bulkData: bulkData,
                formId: clearanceFormId,
                signatoryId: signatoryId
            };

            const modal = document.getElementById('rejectionRemarksModal');
            const targetNameElement = document.getElementById('rejectionTargetName');
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');

            // Pre-fill form if existing data is passed, otherwise reset
            reasonSelect.value = existingReasonId || '';
            remarksTextarea.value = existingRemarks || '';

            // Update display
            if (isBulk) {
                targetNameElement.textContent = `Rejecting: ${bulkData.length} Selected Students`;
            } else {
                targetNameElement.textContent = `Rejecting: ${studentName}`;
            }
            document.getElementById('rejectionType').textContent = 'Student';

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeRejectionRemarksModal() {
            const modal = document.getElementById('rejectionRemarksModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
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
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=users&column=account_status&exclude=resigned`;
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
        }

        async function loadSchoolTerms() {
            const url = `../../api/clearance/get_filter_options.php?type=school_terms`;
            await populateFilter('schoolTermFilter', url, 'All School Terms');
        }

        async function loadPrograms() {
            const url = `../../api/clearance/get_filter_options.php?type=programs&sector=Senior High School`;
            await populateFilter('programFilter', url, 'All Programs', 'program_id', 'program_name');
        }

        async function loadYearLevels() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=students&column=year_level&filter_by_sector=Senior High School`;
            await populateFilter('yearLevelFilter', url, 'All Year Levels');
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadProgramHeadProfile();
            loadStudentsData();
            loadRejectionReasons();
            loadSchoolTerms();
            loadClearanceStatuses();
            loadAccountStatuses();
            loadPrograms();
            loadYearLevels();
        });
    </script>
    
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/activity-tracker.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
</body>
</html>
