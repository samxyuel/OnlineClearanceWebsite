<?php
// Online Clearance Website - Program Head Faculty Management
// Session management handled by header component

try {
    require_once __DIR__ . '/../../includes/config/database.php';
    require_once __DIR__ . '/../../includes/classes/Auth.php';
    
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../../pages/auth/login.php');
        exit;
    }
    
    $userId = (int)$auth->getUserId();
    $pdo = Database::getInstance()->getConnection();
    // Verify role Program Head
    $roleOk = false;
    $rs = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id=r.role_id WHERE ur.user_id=? LIMIT 1");
    $rs->execute([$userId]);
    $rn = strtolower((string)$rs->fetchColumn());
    if ($rn === 'program head') { $roleOk = true; }

    // Check faculty-sector assignment - COMMENTED OUT TO ALLOW ALL PROGRAM HEADS ACCESS
    // $sql = "SELECT COUNT(*) FROM signatory_assignments sa
    //         JOIN designations des ON sa.designation_id=des.designation_id
    //         JOIN departments d ON sa.department_id=d.department_id
    //         JOIN sectors s ON d.sector_id=s.sector_id
    //         WHERE sa.user_id=? AND sa.is_active=1 AND des.designation_name='Program Head' AND s.sector_name='Faculty'";
    // $st = $pdo->prepare($sql);
    // $st->execute([$userId]);
    // $hasFacultySector = ((int)$st->fetchColumn()) > 0;

    if (!$roleOk) {
        // If PH has student sector only, redirect to PH StudentManagement; else to PH dashboard
        $ss = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments sa JOIN designations des ON sa.designation_id=des.designation_id JOIN departments d ON sa.department_id=d.department_id JOIN sectors s ON d.sector_id=s.sector_id WHERE sa.user_id=? AND sa.is_active=1 AND des.designation_name='Program Head' AND s.sector_name IN ('College','Senior High School')");
        $ss->execute([$userId]);
        $hasStudentSector = ((int)$ss->fetchColumn()) > 0;
        if ($hasStudentSector) {
            header('Location: ../../pages/program-head/StudentManagement.php');
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
    <title>Faculty Management - Program Head Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                                <i class="fas fa-shield-alt"></i>
                                <span>Scope: General Education</span>
                            </div>
                        </div>

                        <!-- Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalFaculty">0</h3>
                                    <p>Total Faculty</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="activeFaculty">0</h3>
                                    <p>Active</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon inactive">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="inactiveFaculty">0</h3>
                                    <p>Inactive</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon graduated">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="resignedFaculty">0</h3>
                                    <p>Resigned</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Section -->
                        <div class="quick-actions-section">
                            <div class="action-buttons">
                                <button class="btn btn-primary add-faculty-btn" onclick="openAddFacultyModal()">
                                    <i class="fas fa-plus"></i> Add Faculty
                                </button>
                                <button class="btn btn-secondary import-btn" onclick="triggerImportModal()">
                                    <i class="fas fa-file-import"></i> Import
                                </button>
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

                        <!-- Current Period Banner -->
                        <div class="current-period-banner-wrapper">
                            <div id="currentPeriodBanner" class="current-period-banner">
                                <i class="fas fa-calendar-alt banner-icon" aria-hidden="true"></i>
                                <span id="currentPeriodText">Loading current period...</span>
                            </div>
                        </div>

                        <!-- Faculty Table with Integrated Bulk Actions -->
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
                                        <button class="btn btn-info" onclick="markResigned()" disabled>
                                            <i class="fas fa-user-slash"></i> Resigned
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
                                                <th>Actions</th>
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
                                <span id="paginationInfo">Loading faculty data...</span>
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
                
                <!-- Activity Tracker Sidebar -->
                <div class="dashboard-sidebar">
                    <?php include '../../includes/components/activity-tracker.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Modals -->
    <?php include '../../Modals/FacultyRegistryModal.php'; ?>
    <?php include '../../Modals/EditFacultyModal.php'; ?>
    <?php include '../../Modals/FacultyExportModal.php'; ?>
    <?php include '../../Modals/FacultyImportModal.php'; ?>

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
                        <select id="rejectionReason" class="form-control" onchange="handleReasonChange()">
                            <option value="">Select a reason...</option>
                            <option value="incomplete_documents">Incomplete Documents</option>
                            <option value="unpaid_obligations">Unpaid Obligations</option>
                            <option value="employment_requirements">Employment Requirements Not Met</option>
                            <option value="disciplinary_issues">Disciplinary Issues</option>
                            <option value="missing_clearance">Missing Clearance Items</option>
                            <option value="contract_issues">Contract/Employment Issues</option>
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
        // Load current clearance period for banner
        async function loadCurrentPeriod() {
            try {
                const response = await fetch('../../api/clearance/periods.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                const bannerEl = document.getElementById('currentPeriodText');
                if (!bannerEl) return;
                
                if (data.success && data.active_period) {
                    const period = data.active_period;
                    const termMap = { '1st': 'Term 1', '2nd': 'Term 2', '3rd': 'Term 3' };
                    const semLabel = termMap[period.semester_name] || period.semester_name || '';
                    bannerEl.textContent = `${period.school_year} • ${semLabel}`;
                } else {
                    bannerEl.textContent = 'No active clearance period';
                }
            } catch (error) {
                console.error('Error loading current period:', error);
                const bannerEl = document.getElementById('currentPeriodText');
                if (bannerEl) {
                    bannerEl.textContent = 'Unable to load period';
                }
            }
        }
        
        // Load periods for period selector dropdown
        async function loadPeriods() {
            try {
                console.log('Loading periods...');
                const response = await fetch('../../api/clearance/periods.php', {
                    credentials: 'include'
                });
                
                console.log('Periods API response status:', response.status);
                const data = await response.json();
                console.log('Periods API response data:', data);
                
                const periodSelect = document.getElementById('schoolTermFilter');
                if (!periodSelect) {
                    console.error('Period selector element not found');
                    return;
                }
                
                // Clear existing options except the first one
                periodSelect.innerHTML = '<option value="">All School Terms</option>';
                
                if (data.success && data.periods && data.periods.length > 0) {
                    console.log('Found periods:', data.periods.length);
                    data.periods.forEach(period => {
                        const option = document.createElement('option');
                        option.value = `${period.academic_year}-${period.semester_name}`;
                        
                        const termMap = { '1st': '1st Semester', '2nd': '2nd Semester', '3rd': '3rd Semester' };
                        const semLabel = termMap[period.semester_name] || period.semester_name || '';
                        const activeText = period.is_active ? ' (Active)' : '';
                        
                        option.textContent = `${period.academic_year} ${semLabel}${activeText}`;
                        periodSelect.appendChild(option);
                        console.log('Added period option:', option.textContent);
                    });
                } else {
                    console.log('No periods found or API failed');
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No periods available';
                    option.disabled = true;
                    periodSelect.appendChild(option);
                }
            } catch (error) {
                console.error('Error loading periods:', error);
                const periodSelect = document.getElementById('schoolTermFilter');
                if (periodSelect) {
                    periodSelect.innerHTML = '<option value="">Error loading periods</option>';
                }
            }
        }

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

        // Select all functionality
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateBulkButtons();
        }

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.faculty-checkbox').length;
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
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
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
                    // Perform approval
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-rejected');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Completed';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                            clearanceBadge.classList.add('clearance-completed');
                        }
                    });
                    
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

        function markResigned() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to mark as resigned', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Mark Faculty as Resigned',
                `Are you sure you want to mark ${selectedCount} selected faculty as Resigned? This will change their status permanently.`,
                'Mark as Resigned',
                'Cancel',
                () => {
                    // Perform resignation
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Resigned';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-resigned');
                        }
                    });
                    
                    // Update statistics
                    updateBulkStatistics('resigned', selectedCount);
                    showToastNotification(`✓ Successfully marked ${selectedCount} faculty as Resigned`, 'success');
                },
                'info'
            );
        }

        // Reset clearance status for new term
        function resetClearanceForNewTerm() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to reset clearance status', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Reset Clearance Status',
                `Are you sure you want to reset clearance status to "Unapplied" for ${selectedCount} selected faculty? This will reset their clearance progress for the new term.`,
                'Reset Clearance',
                'Cancel',
                () => {
                    // Perform clearance reset
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                        
                        if (clearanceBadge) {
                            clearanceBadge.textContent = 'Unapplied';
                            clearanceBadge.classList.remove('clearance-pending', 'clearance-completed', 'clearance-rejected', 'clearance-in-progress');
                            clearanceBadge.classList.add('clearance-unapplied');
                        }
                    });
                    
                    showToastNotification(`✓ Successfully reset clearance status for ${selectedCount} faculty`, 'success');
                },
                'warning'
            );
        }

        function deleteSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to delete', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Delete Faculty',
                `Are you sure you want to delete ${selectedCount} selected faculty? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    // Perform deletion
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        row.remove();
                    });
                    
                    // Update statistics
                    updateBulkStatistics('delete', selectedCount);
                    showToastNotification(`✓ Successfully deleted ${selectedCount} faculty`, 'success');
                },
                'danger'
            );
        }

        function getSelectedCount() {
            return document.querySelectorAll('.faculty-checkbox:checked').length;
        }

        function updateBulkStatistics(action, count) {
            const activeCount = document.getElementById('activeFaculty');
            const inactiveCount = document.getElementById('inactiveFaculty');
            const resignedCount = document.getElementById('resignedFaculty');
            
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            let currentResigned = parseInt(resignedCount.textContent.replace(',', ''));
            
            if (action === 'resigned') {
                // Move from active/inactive to resigned
                currentResigned += count;
                // We'd need to track which faculty were active vs inactive
                // For now, we'll assume they were active
                currentActive -= count;
            } else if (action === 'delete') {
                // For delete, we just need to update the total count
                // The specific counts (active, inactive, resigned) might not change
                // unless the user explicitly changes them.
                // For simplicity, we'll just update the total count.
                // If the user wants to remove from specific counts, they'd need to handle that.
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
            resignedCount.textContent = currentResigned.toLocaleString();
        }

        // Individual faculty actions - Program Head as Signatory
        function editFaculty(facultyId) {
            openEditFacultyModal(facultyId);
        }

        async function approveFacultyClearance(facultyId) {
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
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
                    try {
                        const uid = await resolveUserIdFromEmployeeNumber(facultyId);
                        if (!uid) {
                            showToastNotification('Could not find user ID for this faculty', 'error');
                            return;
                        }
                        
                        // Send approval to API
                        const response = await fetch('../../api/clearance/signatory_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                applicant_user_id: uid,
                                designation_name: 'Program Head',
                                action: 'Approved'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Update UI
                            clearanceBadge.textContent = 'Completed';
                            clearanceBadge.classList.remove('clearance-unapplied', 'clearance-pending', 'clearance-in-progress', 'clearance-rejected');
                            clearanceBadge.classList.add('clearance-completed');
                            
                            showToastNotification(`✓ Successfully approved clearance for ${facultyName}`, 'success');
                        } else {
                            showToastNotification('Failed to approve clearance: ' + data.message, 'error');
                        }
                        
                    } catch (error) {
                        console.error('Error approving clearance:', error);
                        showToastNotification('Failed to approve clearance', 'error');
                    }
                },
                'success'
            );
        }

        function rejectFacultyClearance(facultyId) {
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-in-progress, .status-badge.clearance-completed');
            
            if (!clearanceBadge) {
                showToastNotification('No clearance to reject', 'warning');
                return;
            }
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(facultyId, facultyName, 'faculty', false);
        }

        function deleteFaculty(facultyId) {
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            showConfirmationModal(
                'Delete Faculty',
                `Are you sure you want to delete ${facultyName}? This action cannot be undone.`,
                'Delete Permanently',
                'Cancel',
                () => {
                    row.remove();
                    showToastNotification('Faculty deleted successfully', 'success');
                },
                'danger'
            );
        }

        // Filter functions
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const employmentStatus = document.getElementById('employmentStatusFilter').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const facultyName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const employmentBadge = row.querySelector('.status-badge.employment-full-time, .status-badge.employment-part-time, .status-badge.employment-contract');
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                let shouldShow = true;
                
                // Search filter
                if (searchTerm && !facultyName.includes(searchTerm)) {
                    shouldShow = false;
                }
                
                // Employment status filter
                if (employmentStatus && employmentBadge) {
                    if (employmentStatus === 'part-time-full-load') {
                        if (!employmentBadge.classList.contains('employment-part-time-full-load')) {
                            shouldShow = false;
                        }
                    } else if (!employmentBadge.classList.contains(`employment-${employmentStatus}`)) {
                        shouldShow = false;
                    }
                }
                
                // Clearance status filter
                if (clearanceStatus && clearanceBadge && !clearanceBadge.classList.contains(`clearance-${clearanceStatus}`)) {
                    shouldShow = false;
                }
                
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
                if (shouldShow) visibleCount++;
            });
            
            // Update pagination with filtered results
            updateFilteredEntries();
            
            // Show results count
            showInfoToast(`Showing ${visibleCount} of ${tableRows.length} faculty`);
            
            // Update statistics if needed
            updateFilteredStatistics();
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('employmentStatusFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            // Show all rows
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            // Update pagination with all entries
            updateFilteredEntries();
            
            showInfoToast('All filters cleared');
        }

        // Update statistics based on school term selection
        function updateStatisticsByTerm() {
            const selectedTerm = document.getElementById('schoolTermFilter').value;
            const allRows = document.querySelectorAll('#facultyTableBody tr');
            
            let activeCount = 0;
            let inactiveCount = 0;
            let resignedCount = 0;
            let totalCount = 0;
            
            allRows.forEach(row => {
                const rowTerm = row.getAttribute('data-term');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                // Only count if term matches or if "All School Terms" is selected
                if (!selectedTerm || rowTerm === selectedTerm) {
                    totalCount++;
                    
                    if (accountBadge) {
                        if (accountBadge.classList.contains('account-active')) {
                            activeCount++;
                        } else if (accountBadge.classList.contains('account-inactive')) {
                            inactiveCount++;
                        } else if (accountBadge.classList.contains('account-resigned')) {
                            resignedCount++;
                        }
                    }
                }
            });
            
            // Update statistics display
            document.getElementById('totalFaculty').textContent = totalCount;
            document.getElementById('activeFaculty').textContent = activeCount;
            document.getElementById('inactiveFaculty').textContent = inactiveCount;
            document.getElementById('resignedFaculty').textContent = resignedCount;
            
            // Apply filters to update table view
            applyFilters();
        }

        // Update statistics based on filtered results
        function updateFilteredStatistics() {
            const visibleRows = document.querySelectorAll('#facultyTableBody tr:not([style*="display: none"])');
            
            let activeCount = 0;
            let inactiveCount = 0;
            let resignedCount = 0;
            
            visibleRows.forEach(row => {
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                
                if (accountBadge) {
                    if (accountBadge.classList.contains('account-active')) {
                        activeCount++;
                    } else if (accountBadge.classList.contains('account-inactive')) {
                        inactiveCount++;
                    } else if (accountBadge.classList.contains('account-resigned')) {
                        resignedCount++;
                    }
                }
            });
            
            // Update statistics display
            document.getElementById('activeFaculty').textContent = activeCount;
            document.getElementById('inactiveFaculty').textContent = inactiveCount;
            document.getElementById('resignedFaculty').textContent = resignedCount;
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;
        let filteredEntries = [];

        // Initialize pagination
        function initializePagination() {
            const allRows = document.querySelectorAll('#facultyTableBody tr');
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
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }
            
            updatePagination();
        }

        // Change entries per page
        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1; // Reset to first page
            updatePagination();
        }

        // Show current page entries
        function showCurrentPageEntries() {
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + entriesPerPage;
            
            // Hide all rows first
            filteredEntries.forEach(row => {
                row.style.display = 'none';
            });
            
            // Show only current page rows
            for (let i = startIndex; i < endIndex && i < filteredEntries.length; i++) {
                filteredEntries[i].style.display = '';
            }
        }

        // Update filtered entries for pagination
        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#facultyTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1; // Reset to first page
            updatePagination();
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
        function openAddFacultyModal() {
            openFacultyRegistrationModal();
        }

        function triggerImportModal() {
            // Call the function from FacultyImportModal.php
            if (typeof openFacultyImportModal === 'function') {
                openFacultyImportModal();
            } else {
                console.error('Faculty import modal function not found');
            }
        }

        function triggerExportModal() {
            // Call the function from FacultyExportModal.php
            if (typeof openFacultyExportModal === 'function') {
                openFacultyExportModal();
            } else {
                console.error('Faculty export modal function not found');
            }
        }

        // Fetch faculty list from backend and build table body
        async function refreshFacultyTable(){
            try{
                const res = await fetch('../../api/users/staff_faculty_list.php?limit=500',{credentials:'include'});
                const data = await res.json();
                if(!data.success){console.error(data);return;}
                const tbody=document.getElementById('facultyTableBody');
                tbody.innerHTML='';
                let total=0,active=0,inactive=0,resigned=0;
                data.faculty.forEach(f=>{
                    const tr=document.createElement('tr');
                    tr.setAttribute('data-term',''); // term unknown for now
                    const statusRaw = f.clearance_status;
                    let clearanceKey = 'unapplied';
                    if(statusRaw==='Completed' || statusRaw==='Complete') clearanceKey='completed';
                    else if(statusRaw==='Applied') clearanceKey='pending';
                    else if(statusRaw==='In Progress' || statusRaw==='Pending') clearanceKey='in-progress';
                    else if(statusRaw==='Rejected') clearanceKey='rejected';

                    const accountStatus = f.status.toLowerCase();
                    const clearanceStatus=clearanceKey;
                    tr.innerHTML=`<td><input type=\"checkbox\" class=\"faculty-checkbox\" data-id=\"${f.employee_number}\"></td>
                                <td>${f.employee_number}</td>
                                <td>${f.first_name} ${f.last_name}</td>
                                <td><span class="status-badge employment-${f.employment_status.toLowerCase().replace(/ /g,'-')}">${f.employment_status}</span></td>
                                <td><span class="status-badge account-${accountStatus}">${accountStatus.charAt(0).toUpperCase()+accountStatus.slice(1)}</span></td>
                                <td><span class="status-badge clearance-${clearanceStatus}">${statusRaw}</span></td>
                                <td><div class="action-buttons">
                                        <button class=\"btn-icon edit-btn\" onclick=\"editFaculty('${f.employee_number}')\" title=\"Edit\"><i class=\"fas fa-edit\"></i></button>
                                        <button class="btn-icon approve-btn" onclick="approveFacultyClearance('${f.employee_number}')" title="Approve Clearance"><i class="fas fa-check"></i></button>
                                        <button class="btn-icon reject-btn" onclick="rejectFacultyClearance('${f.employee_number}')" title="Reject Clearance"><i class="fas fa-times"></i></button>
                                        <button class=\"btn-icon delete-btn\" onclick=\"deleteFaculty('${f.employee_number}')\" title=\"Delete\"><i class=\"fas fa-trash\"></i></button>
                                   </div></td>`;

                    if(accountStatus!=='active'){
                        tr.classList.add('row-disabled');
                        // Keep checkbox enabled; bulk logic will govern action button states
                    }
                    tbody.appendChild(tr);
                    // stats counting
                    total++;
                    if(accountStatus==='active') active++;
                    else if(accountStatus==='inactive') inactive++;
                    else if(accountStatus==='resigned') resigned++;
                });
                // update stats dashboard
                document.getElementById('totalFaculty').textContent=total;
                document.getElementById('activeFaculty').textContent=active;
                document.getElementById('inactiveFaculty').textContent=inactive;
                document.getElementById('resignedFaculty').textContent=resigned;
            }catch(err){console.error(err);}
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Load faculty list from backend then initialize pagination
            refreshFacultyTable().then(()=>{
                showToastNotification('Faculty table refreshed','success');
                initializePagination();
                updateSelectionCounter();
            });
            
            // Load current clearance period for banner
            loadCurrentPeriod();
            
            // Load periods for period selector
            loadPeriods();
            
            // Add event listeners for checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });

            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for Program Head Faculty Management');
            }
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
                // Update faculty table rows
                currentRejectionData.targetIds.forEach(id => {
                    const row = document.querySelector(`.faculty-checkbox[data-id="${id}"]`);
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
                    const checkbox = document.querySelector(`.faculty-checkbox[data-id="${id}"]`);
                    if (checkbox) checkbox.checked = false;
                });
                updateBulkButtons();
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} faculty with remarks`, 'success');
            } else {
                // Update individual faculty row
                const row = document.querySelector(`.faculty-checkbox[data-id="${currentRejectionData.targetId}"]`);
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
                // Attempt server-side record
                try {
                    const uid = await resolveUserIdFromEmployeeNumber(currentRejectionData.targetId);
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
        async function sendSignatoryAction(applicantUserId, designationName, action, remarks){
            const payload = { applicant_user_id: applicantUserId, designation_name: designationName, action: action };
            if (remarks && remarks.length) payload.remarks = remarks;
            await fetch('../../api/clearance/signatory_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload)
            }).then(r=>r.json()).catch(()=>null);
        }
    </script>
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/activity-tracker.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
</body>
</html>
