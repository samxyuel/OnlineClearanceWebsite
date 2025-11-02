<?php // phpcs:disable Generic.Files.LineLength.TooLong
// Online Clearance Website - Program Head College Student Management
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
    <title>College Student Management - Program Head Dashboard</title>
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
                            <h2><i class="fas fa-university"></i> College Student Management</h2>
                            <p>Manage college students within your assigned departments and sign their clearances</p>
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
                                    <h3 id="totalStudents">456</h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeStudents">420</h3>
                                    <p>Active Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveStudents">36</h3>
                                    <p>Inactive Students</p>
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
                                <button class="btn btn-primary add-student-btn" onclick="openAddStudentModal()" title="Add a new college student to the system">
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
                                <i class="fas fa-search" style="pointer-events: none;"></i>
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or program...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Program Filter (Only for assigned departments) -->
                                <select id="programFilter" class="filter-select">
                                    <option value="">All Programs</option> 
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- Year Level Filter (Cascading) -->
                                <select id="yearLevelFilter" class="filter-select">
                                    <option value="">All Year Levels</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">All School Terms</option>
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

                        <!-- Students Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <button class="btn btn-outline-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <button class="btn btn-success" onclick="openCollegeBatchUpdateModal()">
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
    <?php include '../../Modals/CollegeStudentRegistryModal.php'; ?>
    
    <!-- Include Edit Student Modal -->
    <?php include '../../Modals/CollegeEditStudentModal.php'; ?>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
    
    <!-- Include Import Modal -->
    <?php include '../../Modals/ImportModal.php'; ?>
    
    <!-- Include College Batch Update Modal -->
    <?php include '../../Modals/CollegeBatchUpdateModal.php'; ?>
    
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
        // Program Head specific variables
        const assignedDepartments = ['Information, Communication, and Technology'];
        const departmentPrograms = {
            'Information, Communication, and Technology': [
                'BS in Information Technology (BSIT)',
                'BS in Computer Science (BSCS)',
                'BS in Information Systems (BSIS)',
                'BS in Computer Engineering (BSCpE)'
            ]
        };
        const departmentYearLevels = {
            'Information, Communication, and Technology': [
                '1st Year', '2nd Year', '3rd Year', '4th Year'
            ]
        };

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
            
            if (currentStatus === 'Approved') {
                showToastNotification(`${studentName}'s clearance is already approved`, 'info');
                return;
            }
            
            showConfirmationModal(
                'Approve Clearance',
                `Approve ${studentName}'s clearance?`,
                'Approve',
                'Cancel',
                async () => {
                    try {
                        const response = await fetch('../../api/clearance/signatory_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                applicant_user_id: targetUserId,
                                clearance_signatory_id: signatoryId,
                                action: 'Approved',
                                remarks: 'Approved by Program Head'
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            showToastNotification(`${studentName}'s clearance has been approved`, 'success');
                            updateSignatoryActionUI(targetUserId, 'Approved');
                        } else {
                            showToastNotification('Failed to approve: ' + result.message, 'error');
                        }
                    } catch (e) { /* non-blocking */ }
                },
                'success'
            );
        }

        function rejectStudent(button) {
            const row = button.closest('tr');
            const clearanceBadge = row.querySelector('.status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-approved');
            
            if (!clearanceBadge || !clearanceFormId || !signatoryId) {
                console.error('Clearance badge not found');
                showToastNotification('Error: Could not find clearance status', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const currentStatus = clearanceBadge.textContent;
            
            if (currentStatus === 'Rejected') {
                showToastNotification(`${studentName}'s clearance is already rejected`, 'info');
                return;
            }
            
            // Get student ID from the checkbox
            const userId = row.getAttribute('data-user-id');
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(userId, clearanceFormId, signatoryId, studentName);
        }

        // Individual Delete with Confirmation
        function deleteStudent(studentId) {
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
                    row.remove();
                    showToastNotification(`${studentName} has been deleted`, 'success');
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
            
            // Reload data from server with cleared filters
            applyFilters();
            showToastNotification('All filters cleared', 'info');
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        let filteredEntries = [];

        function updatePagination() {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            const startEntry = (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, filteredEntries.length);
            
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${filteredEntries.length} entries`;
            
            updatePageNumbers(totalPages);
            
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;

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

        // Initialize pagination
        function initializePagination() {
            const allRows = document.querySelectorAll('#studentsTableBody tr');
            totalEntries = allRows.length;
            filteredEntries = Array.from(allRows);
            updatePagination();
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
                    credentials: 'include' // Ensure cookies are sent
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

        // Load college students data from API
        async function loadStudentsData() {
            const tableBody = document.getElementById('studentsTableBody');
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading students...</td></tr>`;

            // Get filter values
            const search = document.getElementById('searchInput').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const programId = document.getElementById('programFilter').value;
            const yearLevel = document.getElementById('yearLevelFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;

            // Program Head for College is a specific case of a signatory list.
            const url = new URL('../../api/clearance/signatoryList.php', window.location.href);
            url.searchParams.append('type', 'student'); 
            url.searchParams.append('sector', 'College');
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', entriesPerPage);

            if (search) url.searchParams.append('search', search);
            if (clearanceStatus) url.searchParams.append('clearance_status', clearanceStatus);
            if (programId) url.searchParams.append('program_id', programId);
            if (yearLevel) url.searchParams.append('year_level', yearLevel);
            if (accountStatus) url.searchParams.append('account_status', accountStatus);
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
                console.error('Error loading college students:', error);
                showToastNotification('Error loading students data: ' + error.message, 'error');
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

        // Populate students table
        async function populateStudentsTable(students) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';
            
            if (!students || students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">No college students found in your assigned departments.</td></tr>`;
                return;
            }

            for (const student of students) {
                const row = createStudentRow(student);
                tbody.appendChild(row);
            }
        }

        // Create student row
        function createStudentRow(student) {
            const accountStatusClass = `account-${student.account_status || 'inactive'}`;
            const accountStatusText = student.account_status ? student.account_status.charAt(0).toUpperCase() + student.account_status.slice(1) : 'Inactive';

            let clearanceStatus = student.clearance_status || 'Unapplied';
            const clearanceStatusClass = `clearance-${clearanceStatus.toLowerCase().replace(/ /g, '-')}`;

            // Determine button titles and states based on clearance status
            const isActionable = ['Pending', 'Rejected'].includes(clearanceStatus);
            const rejectButtonTitle = clearanceStatus === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Signatory';

            const row = document.createElement('tr');
            row.setAttribute('data-user-id', student.user_id);
            row.setAttribute('data-student-id', student.id); // Use 'id' from signatoryList response
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

        // Update statistics
        function updateStatisticsUI(stats) {
            document.getElementById('totalStudents').textContent = stats.total || 0;
            document.getElementById('activeStudents').textContent = stats.active || 0;
            document.getElementById('inactiveStudents').textContent = stats.inactive || 0;
            document.getElementById('graduatedStudents').textContent = stats.graduated || 0;
        }

        // Edit student function
        function editStudent(student_id) {
            // Open edit student modal
            showToastNotification('Edit student functionality will be implemented', 'info');
        }

        // Delete student function
        function deleteStudent(student_id) {
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
                                student_id: student_id
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Remove row from table
                            const row = document.querySelector(`tr[data-student-id="${student_id}"]`);
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

        async function loadYearLevel() {
            const url = `../../api/clearance/get_filter_options.php?type=enum&table=students&column=year_level`;
            await populateFilter('yearLevelFilter', url, 'All Year Levels');
        }

        async function loadPrograms() {
            const url = `../../api/clearance/get_filter_options.php?type=programs`;
            await populateFilter('programFilter', url, 'All Programs');
        }


        // Initialize pagination when page loads
        document.addEventListener('DOMContentLoaded', function() {
                initializePagination();
                updateSelectionCounter();
            
            const tableWrapper = document.getElementById('studentsTableWrapper');
            if (tableWrapper) {
                tableWrapper.addEventListener('scroll', handleTableScroll);
            }
            
            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker({
                    userRole: 'Program Head'
                });
            }
            
            // 1. Load user-specific data first (profile, departments, etc.)
            loadProgramHeadProfile().then(() => {
                // This is a good place for dependent calls, like loading programs for the filter
                loadPrograms();
            });

            // 2. Load general data and options for filters and modals
            loadRejectionReasons();
            loadSchoolTerms();
            loadClearanceStatuses();
            loadYearLevel();
            loadAccountStatuses();
            loadCurrentPeriod(); // For the banner

            // 3. Perform the initial data fetch for the main table
            loadStudentsData();

            // 4. Initialize UI components and event listeners
            initializePagination();
            updateSelectionCounter();

            // Add event listeners for search and filters
            document.getElementById('searchInput').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    applyFilters();
                }
            });
        });

        // Add event listeners for student checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateBulkButtons();
                updateSelectionCounter();
            }
        });

        // Load rejection reasons into the modal dropdown
        async function loadRejectionReasons() {
            const reasonSelect = document.getElementById('rejectionReason');
            if (!reasonSelect) return;

            try {
                // The API fetches all reasons, we will filter for 'student' and 'both'
                const response = await fetch('../../api/clearance/rejection_reasons.php', { credentials: 'include' });
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

        // Rejection Remarks Modal Functions (Updated)
        let currentRejectionData = {
            userId: null,
            formId: null,
            studentName: null,
            isBulk: false,
            bulkData: []
        };

        function openRejectionRemarksModal(userId, clearanceFormId, signatoryId, studentName, isBulk = false, bulkData = []) {
            currentRejectionData = {
                userId: userId, // This is the applicant_user_id
                formId: clearanceFormId,
                signatoryId: signatoryId,
                studentName: studentName, // Correctly assigned
                isBulk: isBulk,
                bulkData: bulkData
            };

            // Update modal content based on target type
            const modal = document.getElementById('rejectionRemarksModal');
            const targetNameElement = document.getElementById('rejectionTargetName');
            const targetTypeElement = document.getElementById('rejectionType');
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');

            // Reset form for new rejection or pre-fill if editing
            reasonSelect.value = '';
            remarksTextarea.value = '';

            if (!isBulk) {
                const row = document.querySelector(`tr[data-user-id='${userId}']`);
                if (row) {
                    remarksTextarea.value = row.getAttribute('data-remarks') || '';
                    reasonSelect.value = row.getAttribute('data-rejection-reason-id') || '';
                }
            }

            // Update display
            if (isBulk) {
                targetNameElement.textContent = `Rejecting: ${currentRejectionData.bulkData.length} Selected Students`;
            } else {
                targetNameElement.textContent = `Rejecting: ${currentRejectionData.studentName}`;
            }
            targetTypeElement.textContent = 'Student';

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
                userId: null,
                formId: null,
                isBulk: false,
                bulkData: []
            };
        }

        function handleReasonChange() {
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');
            
            // If "Other" is selected, focus on remarks textarea
            if (reasonSelect.options[reasonSelect.selectedIndex]?.text.toLowerCase().includes('other')) {
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
            
            if (currentRejectionData.isBulk) {
                // Bulk rejection logic here
                showToastNotification('Bulk rejection not yet fully implemented.', 'info');
            } else {
                // Individual rejection
                try {
                    const response = await fetch('../../api/clearance/signatory_action.php', {
                        method: 'POST', // Corrected to POST
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                    body: JSON.stringify({ // Match the signatory_action.php endpoint
                        applicant_user_id: currentRejectionData.userId,
                        action: 'Rejected',
                        remarks: additionalRemarks, // This becomes 'additional_remarks' on the backend for rejections
                        reason_id: rejectionReason,
                        designation_name: 'Program Head' // Important for routing
                    })
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.studentName} with remarks`, 'success'); // Corrected to use the right property
                        updateSignatoryActionUI(currentRejectionData.userId, 'Rejected');
                        // Reload student data to get fresh state
                        loadStudentsData();
                    } else {
                        showToastNotification('Failed to reject: ' + result.message, 'error');
                    }
                } catch (e) {
                    showToastNotification('An error occurred during rejection.', 'error');
                }
            }
            
            // Close modal
            closeRejectionRemarksModal();
        }

        // Signatory Action Functions
        async function approveSignatory(targetUserId, clearanceFormId, signatoryId) {
            try {
                const response = await fetch('../../api/clearance/signatory_action.php', {
                    method: 'POST', // Corrected to POST
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        applicant_user_id: targetUserId,
                        action: 'Approved',
                        remarks: 'Approved by Program Head',
                        designation_name: 'Program Head' // Add designation for routing
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToastNotification('Signatory approved successfully', 'success');
                    updateSignatoryActionUI(targetUserId, 'Approved');
                } else {
                    showToastNotification('Failed to approve signatory: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error approving signatory:', error);
                showToastNotification('Error approving signatory: ' + error.message, 'error');
            }
        }

        async function rejectSignatory(targetUserId, clearanceFormId, signatoryId) {
            try {
                const row = document.querySelector(`tr[data-user-id='${targetUserId}']`);
                const studentName = row ? row.cells[2].textContent : 'Student';
                // Open rejection modal
                openRejectionRemarksModal(targetUserId, clearanceFormId, signatoryId, studentName);
            } catch (error) {
                console.error('Error opening rejection modal:', error);
                showToastNotification('Error opening rejection modal: ' + error.message, 'error');
            }
        }

        function updateSignatoryActionUI(userId, action) {
            // Find the row for this user and update the signatory action buttons and status
            const row = document.querySelector(`tr[data-user-id='${userId}']`);
            if (!row) return;
            
            // Update the Clearance Status column (8th column, index 7)
            const statusCell = row.cells[7];
            if (statusCell) {
                const statusBadge = statusCell.querySelector('.status-badge');
                if (statusBadge) {
                    if (action === 'Approved') {
                        statusBadge.textContent = 'Approved'; // Or 'Completed' if that's the final state
                        statusBadge.className = 'status-badge clearance-approved';
                    } else if (action === 'Rejected') {
                        statusBadge.textContent = 'Rejected';
                        statusBadge.className = 'status-badge clearance-rejected';
                    }
                }
            }
            
            // Update the action buttons in the Actions column (9th column, index 8)
            const actionCell = row.cells[8];
            if (actionCell) {
                const approveBtn = actionCell.querySelector('.approve-btn');
                const rejectBtn = actionCell.querySelector('.reject-btn');
                
                if (approveBtn && rejectBtn) {
                    if (action === 'Approved') {
                        approveBtn.disabled = true;
                        rejectBtn.disabled = true;
                        approveBtn.title = 'Already Approved';
                        rejectBtn.title = 'Already Approved';
                    } else if (action === 'Rejected') {
                        approveBtn.disabled = false;
                        rejectBtn.disabled = false;
                        approveBtn.title = 'Approve Signatory';
                        rejectBtn.title = 'Update Rejection Remarks';
                    }
                }
            }
        }
    </script>
    
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/activity-tracker.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
</body>
</html>
