<?php
// Online Clearance Website - Regular Staff Student Management

// Include the controller logic which handles all authorization and data fetching.
require_once __DIR__ . '/../../controllers/StudentManagementController.php';

// The controller function acts as a "gatekeeper". If it doesn't exit, the user is authorized.
handleStudentManagementPageRequest();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Staff Dashboard</title>
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
                            <h2><i class="fas fa-user-graduate"></i> Student Management</h2>
                            <p>Review and sign student clearance requests as a Cashier signatory</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>
                                <span>Position: Cashier - Clearance Signatory</span>
                            </div>

                            <!-- Permission Status Alerts -->
                            <?php if (!$GLOBALS['hasActivePeriod']): ?>
                            <div class="alert alert-warning" style="margin-top: 10px;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>No Active Clearance Period:</strong> You can view student data but cannot perform signatory actions until a clearance period is activated.
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!$GLOBALS['hasStudentSignatoryAccess']): ?>
                            <div class="alert alert-info" style="margin-top: 10px;">
                                <i class="fas fa-info-circle"></i>
                                <strong>View-Only Access:</strong> You can view student data but are not currently assigned as a student signatory.
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($GLOBALS['canPerformSignatoryActions']): ?>
                            <div class="alert alert-success" style="margin-top: 10px;">
                                <i class="fas fa-check-circle"></i>
                                <strong>Signatory Actions Available:</strong> You can approve and reject student clearance requests.
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
                                    <i class="fas fa-file-export"></i> Export Report
                                </button>
                            </div>
                        </div>

                        <!-- Search and Filters Section -->
                        <div class="search-filters-section">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search students by name, ID, or department...">
                            </div>
                            
                            <div class="filter-dropdowns">
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

                        <!-- Student Table with Integrated Bulk Actions -->
                        <div class="table-container">
                            <!-- Table Header with Bulk Actions -->
                            <div class="table-header-section">
                                <div class="bulk-controls">
                                    <label class="select-all-checkbox">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <span class="checkmark"></span>
                                        Select All
                                    </label>
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
                                <div class="students-table-wrapper" id="studentTableWrapper">
                                    <table id="studentTable" class="students-table">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-column">
                                                    <span id="selectionCounter">0 selected</span>
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
                                        <tbody id="studentTableBody">
                                            <!-- Student data will be loaded here dynamically -->
                                            <!-- Fallback data in case of no data -->                                        
                                            <tr data-term="2024-2025-1st">
                                                <td><input type="checkbox" class="student-checkbox" data-id="02000288322"></td>
                                                <td>02000288322</td>
                                                <td>Zinzu Chan Lee</td>
                                                <td>BSIT</td>
                                                <td>4th Year</td>
                                                <td>4/1-1</td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-pending">Pending</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon approve-btn" onclick="approveStudentClearance('02000288322')" title="Approve Clearance">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-icon reject-btn" onclick="rejectStudentClearance('02000288322')" title="Reject Clearance">
                                                            <i class="fas fa-times"></i>
                                                        </button>
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

        const CURRENT_STAFF_POSITION = '<?php echo isset($_SESSION['position']) ? addslashes($_SESSION['position']) : 'Staff'; ?>';
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
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateBulkButtons();
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
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    for (const checkbox of selectedRows) {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Completed';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                            clearanceBadge.classList.add('clearance-completed');
                        }
                        // server-side record
                        try {
                            const sid = checkbox.getAttribute('data-id');
                            const uid = await resolveUserIdFromStudentNumber(sid);
                            if (uid) { await sendSignatoryAction(uid, 'Approved'); }
                        } catch (e) {}
                    }
                    
                    showToastNotification(`✓ Successfully approved clearance for ${selectedCount} students`, 'success');
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
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
            
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
                    clearanceBadge.textContent = 'Approved';
                    clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected', 'clearance-completed');
                    clearanceBadge.classList.add('clearance-approved');
                    try {
                        const uid = await resolveUserIdFromStudentNumber(studentId);
                        if (uid) {
                            const result = await sendSignatoryAction(uid, 'Approved', 'Approved by Staff');
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

            const currentStatus = clearanceBadge ? clearanceBadge.textContent.trim() : '';
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

            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(studentId, studentName, 'student', false, [], existingRemarks, existingReasonId);
        }

        // --- Data Fetching and Rendering ---
        async function fetchStudents() {
            const tableBody = document.getElementById('studentTableBody');
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading students...</td></tr>`;

            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const search = document.getElementById('searchInput').value;

            let url = `../../api/staff/signatoryList.php?page=${currentPage}&limit=${entriesPerPage}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (clearanceStatus) url += `&clearance_status=${encodeURIComponent(clearanceStatus)}`;
            if (accountStatus) url += `&account_status=${encodeURIComponent(accountStatus)}`;

            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                if (!data.success) {
                    tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                    return;
                }

                renderStudentTable(data.students);
                renderPagination(data.total, data.page, data.limit);

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

            tableBody.innerHTML = students.map(student => {
                const clearanceStatusClass = `clearance-${student.clearance_status.toLowerCase().replace(' ', '-')}`;
                const accountStatusClass = `account-${(student.account_status || '').toLowerCase()}`;

                const canPerformActions = <?php echo $GLOBALS['canPerformSignatoryActions'] ? 'true' : 'false'; ?>;
                // Enable approve button for 'Pending' and 'Rejected' statuses.
                let approveBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(student.clearance_status);
                // Enable reject button for 'Pending' and 'Rejected' statuses to allow for edits.
                let rejectBtnDisabled = !canPerformActions || !['Pending', 'Rejected'].includes(student.clearance_status);
                let approveTitle = 'Approve Clearance';
                // Change button title if the student is already rejected.
                let rejectTitle = student.clearance_status === 'Rejected' ? 'Update Rejection Remarks' : 'Reject Clearance';
                if (!canPerformActions) {
                    approveTitle = rejectTitle = '<?php echo !$GLOBALS["hasActivePeriod"] ? "No active clearance period." : "Not assigned as a student signatory."; ?>';
                }

                return `
                    <tr data-signatory-id="${student.signatory_id}">
                        <td><input type="checkbox" class="student-checkbox" data-id="${student.id}"></td>
                        <td>${student.id}</td>
                        <td>${escapeHtml(student.name)}</td>
                        <td>${escapeHtml(student.program)}</td>
                        <td>${escapeHtml(student.year_level)}</td>
                        <td>${escapeHtml(student.section)}</td>
                        <td><span class="status-badge ${accountStatusClass}">${escapeHtml(student.account_status || 'N/A')}</span></td>
                        <td><span class="status-badge ${clearanceStatusClass}">${escapeHtml(student.clearance_status || 'N/A')}</span></td>
                        <td>
                            <div class="action-buttons">
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
                button.textContent = i;
                button.onclick = () => {
                    currentPage = i;
                    fetchStudents();
                };
                pageNumbersContainer.appendChild(button);
            }

            document.getElementById('prevPage').disabled = page === 1;
            document.getElementById('nextPage').disabled = page === totalPages;
        }

        // Filter functions
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            currentPage = 1; // Reset to first page on new filter/search
            fetchStudents();
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            applyFilters();
            showToastNotification('All filters cleared', 'info');
        }

        function updateStatisticsByTerm() {
            applyFilters();
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
            showConfirmationModal(
                'Export Student Clearance Report',
                'Generate a PDF report of your student clearance signing activities?',
                'Export',
                'Cancel',
                () => {
                    showToastNotification('Report generation started...', 'info');
                    setTimeout(() => {
                        showToastNotification('Student clearance report exported successfully!', 'success');
                    }, 2000);
                },
                'info'
            );
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectionCounter();
            fetchStudents();
            loadRejectionReasons();
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('student-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
            
            
            
            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();
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
            const modal = document.getElementById('rejectionRemarksModal');
            const targetNameElement = document.getElementById('rejectionTargetName');
            const targetTypeElement = document.getElementById('rejectionType');
            const reasonSelect = document.getElementById('rejectionReason');
            const remarksTextarea = document.getElementById('additionalRemarks');

            // Reset form
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
                            const clearanceBadge = tableRow.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed, .status-badge.clearance-approved');
                            if (clearanceBadge) {
                                clearanceBadge.textContent = 'Rejected'; // Use 'Rejected' to match the backend
                                clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed', 'clearance-approved');
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
                // server-side records
                try {
                    for (const id of currentRejectionData.targetIds) {
                        const uid = await resolveUserIdFromStudentNumber(id);
                        if (uid) { await sendSignatoryAction(uid, 'Rejected', additionalRemarks, rejectionReason); }
                    }
                } catch (e) {}
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} students with remarks`, 'success');
            } else {
                // Update individual student row
                const row = document.querySelector(`.student-checkbox[data-id="${currentRejectionData.targetId}"]`);
                if (row) {
                    const tableRow = row.closest('tr');
                    if (tableRow) {
                        const clearanceBadge = tableRow.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed, .status-badge.clearance-approved');
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Rejected'; // Use 'Rejected' to match the backend
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-completed', 'clearance-approved');
                            clearanceBadge.classList.add('clearance-rejected');
                        }
                    }
                }
                // server-side record
                try {
                    const uid = await resolveUserIdFromStudentNumber(currentRejectionData.targetId);
                    if (uid) {
                        const result = await sendSignatoryAction(uid, 'Rejected', additionalRemarks, rejectionReason);
                        if (result.success) {
                            showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetName} with remarks`, 'success');
                            fetchStudents(); // Refresh the table to update button states
                        } else {
                            showToastNotification('Failed to reject: ' + (result.message || 'Unknown error'), 'error');
                        }
                    }
                } catch (e) {
                    console.error("Error during individual rejection:", e);
                    showToastNotification('An error occurred during rejection.', 'error');
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
            let designationName = CURRENT_STAFF_POSITION; // Fallback
            try {
                const desigResponse = await fetch('../../api/users/get_current_staff_designation.php', { credentials: 'include' });
                const desigData = await desigResponse.json();
                if (desigData.success) { designationName = desigData.designation_name; }
            } catch (e) { /* Ignore error, use fallback */ }

            const payload = { 
                applicant_user_id: applicantUserId, 
                action: action,
                designation_name: designationName 
            };
            if (remarks && remarks.length) payload.remarks = remarks;
            if (reasonId) payload.reason_id = reasonId;

            const response = await fetch('../../api/clearance/signatory_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)
            });
            return await response.json(); // Ensure the promise is returned
        }
    </script>
    <script src="../../assets/js/alerts.js"></script>
</body>
</html>
