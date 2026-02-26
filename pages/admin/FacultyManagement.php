<?php // Online Clearance Website - Admin Faculty Management
// Session management handled by header component

// Start output buffering to prevent headers being sent before session_start()
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - Online Clearance System</title>
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
                            <h2><i class="fas fa-chalkboard-teacher"></i> Faculty Management</h2>
                            <p>Manage faculty accounts and monitor employment status</p>
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
                                <input type="text" id="searchInput" placeholder="Search faculty by name, ID, or department...">
                            </div>
                            
                            <div class="filter-dropdowns">
                                <!-- Employment Status Filter -->
                                <select id="employmentStatusFilter" class="filter-select">
                                    <option value="">All Employment Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>

                                <!-- Account Status Filter -->
                                <select id="accountStatusFilter" class="filter-select">
                                    <option value="">All Account Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- Clearance Status Filter -->
                                <select id="clearanceStatusFilter" class="filter-select">
                                    <option value="">All Clearance Status</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                
                                <!-- School Term Filter -->
                                <select id="schoolTermFilter" class="filter-select">
                                    <option value="">All School Terms</option> 
                                    <!-- Options will be populated dynamically -->                                   
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
                                    <button class="selection-counter-display" id="selectionCounterPill" type="button" title="">
                                        <span id="selectionCounter">0 selected</span>
                                    </button>
                                    <div class="bulk-buttons">
                                        <!-- Undo button removed as per workflow update -->
                                        <button id="bulkActivateBtn" class="btn btn-success" onclick="activateSelected()" disabled>
                                            <i class="fas fa-user-check"></i> Activate
                                        </button>
                                        <button id="bulkDeactivateBtn" class="btn btn-warning" onclick="deactivateSelected()" disabled>
                                            <i class="fas fa-user-times"></i> Deactivate
                                        </button>
                                        <button id="bulkDeleteBtn" class="btn btn-danger" onclick="deleteSelected()" disabled>
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
                                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this.checked)" title="Select all visible">
                                                </th>
                                                <th>Employee Number</th>
                                                <th>Name</th>
                                                <th>Department(s)</th>
                                                <th>Employment Status</th>
                                                <th>Account Status</th>
                                                <th>Clearance Form Progress</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="facultyTableBody">
                                            <!-- Data will be populated by JavaScript -->
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
    
    <!-- Include Modals (moved after session start) -->
    <?php include '../../Modals/FacultyRegistryModal.php'; ?>
    <?php include '../../Modals/EditFacultyModal.php'; ?>
    <?php include '../../Modals/ExportModal.php'; ?>
    <?php include '../../Modals/ImportModal.php'; ?>
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
                                <input type="checkbox" id="filterInProgress" value="in-progress">
                                <span class="checkmark"></span>
                                with "in progress"
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="filterComplete" value="complete">
                                <span class="checkmark"></span>
                                with "Complete"
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

        // Bulk selection functionality (replaces old select all)
        // The new bulk selection modal handles all selection logic

        function updateSelectionCounter() {
            const selectedCount = getSelectedCount();
            const totalCount = document.querySelectorAll('.faculty-checkbox').length;
            const counter = document.getElementById('selectionCounter');
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            const selectionDisplay = document.getElementById('selectionCounterPill');
            
            if (selectedCount === 0) {
                counter.textContent = '0 selected';
                // Disable clear selection button when no selections
                if (clearSelectionBtn) clearSelectionBtn.disabled = true;
                // Reset selection counter styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections', 'all-selected');
                    selectionDisplay.setAttribute('aria-disabled','true');
                    selectionDisplay.title = '';
                }
            } else if (selectedCount === totalCount) {
                counter.textContent = `All ${totalCount} selected`;
                // Enable clear selection button when there are selections
                if (clearSelectionBtn) clearSelectionBtn.disabled = false;
                // Apply all selected styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('has-selections');
                    selectionDisplay.classList.add('all-selected');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
            } else {
                counter.textContent = `${selectedCount} selected`;
                // Enable clear selection button when there are selections
                if (clearSelectionBtn) clearSelectionBtn.disabled = false;
                // Apply partial selection styling
                if (selectionDisplay) {
                    selectionDisplay.classList.remove('all-selected');
                    selectionDisplay.classList.add('has-selections');
                    selectionDisplay.removeAttribute('aria-disabled');
                    selectionDisplay.title = 'Clear selection';
                }
            }
        }

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
            updateSelectionCounter();
        }

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const allCheckboxes = document.querySelectorAll('#facultyTableBody .faculty-checkbox');
            const checkedCount = document.querySelectorAll('#facultyTableBody .faculty-checkbox:checked').length;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
            }
        }

        function updateBulkButtons() {
            const checkedBoxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedCount = checkedBoxes.length;
            updateSelectAllCheckbox();
            // Counters for account status
            let activeCount = 0, inactiveCount = 0;
            checkedBoxes.forEach(cb=>{
                const row = cb.closest('tr');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
                if(accountBadge){
                    if(accountBadge.classList.contains('account-active')) activeCount++;
                    else if(accountBadge.classList.contains('account-inactive')) inactiveCount++;
                }
            });
            
            // Get button references
            const activateBtn   = document.getElementById('bulkActivateBtn');
            const deactivateBtn = document.getElementById('bulkDeactivateBtn');
            const deleteBtn     = document.getElementById('bulkDeleteBtn');
            
            // Enable/disable buttons based on selection
            if (selectedCount === 0) {
                // No selection - disable all buttons
                activateBtn.disabled = true;
                deactivateBtn.disabled = true;
                deleteBtn.disabled = true;
            } else {
                // Delete button always enabled when there are selections
                deleteBtn.disabled = false;
                
                // Activate button: enabled if there are inactive items
                activateBtn.disabled = inactiveCount === 0;
                
                // Deactivate button: enabled if there are active items
                deactivateBtn.disabled = activeCount === 0;
            }

            updateSelectionCounter();
        }

        // Enhanced notification function (using external alert system)
        function showNotification(message, type = 'info') {
            showToastNotification(message, type);
        }

        // Show info toast function
        function showInfoToast(message) {
            showToastNotification(message, 'info');
        }

        // Bulk Actions with Confirmation
        async function activateSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to activate', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Activate Faculty',
                `Are you sure you want to activate ${selectedCount} selected faculty?`,
                'Activate',
                'Cancel',
                async () => {
                    // Collect user IDs from selected checkboxes
                    const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
                    const userIds = [];
                    selectedCheckboxes.forEach(checkbox => {
                        const userId = checkbox.getAttribute('data-user-id');
                        if (userId) {
                            userIds.push(parseInt(userId));
                        }
                    });
                    
                    if (userIds.length === 0) {
                        showToastNotification('No valid faculty selected', 'error');
                        return;
                    }
                    
                    try {
                        const response = await fetch('../../api/users/account_status.php', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                action: 'activate',
                                user_ids: userIds
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            showToastNotification(`✓ Successfully activated ${result.affected_count} faculty`, 'success');
                            
                            // Update UI for each activated faculty
                            selectedCheckboxes.forEach(checkbox => {
                                const row = checkbox.closest('tr');
                                const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                                
                                if (statusBadge) {
                                    statusBadge.textContent = 'Active';
                                    statusBadge.classList.remove('account-inactive', 'account-resigned');
                                    statusBadge.classList.add('account-active');
                                }
                            });
                            
                            // Update statistics
                            updateBulkStatistics('activate', result.affected_count);
                            
                            // Refresh table to ensure data consistency
                            refreshFacultyTable();
                        } else {
                            showToastNotification(result.message || 'Failed to activate faculty', 'error');
                        }
                    } catch (error) {
                        console.error('Activation error:', error);
                        showToastNotification('An error occurred while activating faculty', 'error');
                    }
                },
                'info'
            );
        }

        async function deactivateSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to deactivate', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Deactivate Faculty',
                `Are you sure you want to deactivate ${selectedCount} selected faculty?`,
                'Deactivate',
                'Cancel',
                async () => {
                    // Collect user IDs from selected checkboxes
                    const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
                    const userIds = [];
                    selectedCheckboxes.forEach(checkbox => {
                        const userId = checkbox.getAttribute('data-user-id');
                        if (userId) {
                            userIds.push(parseInt(userId));
                        }
                    });
                    
                    if (userIds.length === 0) {
                        showToastNotification('No valid faculty selected', 'error');
                        return;
                    }
                    
                    try {
                        const response = await fetch('../../api/users/account_status.php', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                action: 'deactivate',
                                user_ids: userIds
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            showToastNotification(`✓ Successfully deactivated ${result.affected_count} faculty`, 'success');
                            
                            // Update UI for each deactivated faculty
                            selectedCheckboxes.forEach(checkbox => {
                                const row = checkbox.closest('tr');
                                const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                                
                                if (statusBadge) {
                                    statusBadge.textContent = 'Inactive';
                                    statusBadge.classList.remove('account-active', 'account-resigned');
                                    statusBadge.classList.add('account-inactive');
                                }
                            });
                            
                            // Update statistics
                            updateBulkStatistics('deactivate', result.affected_count);
                            
                            // Refresh table to ensure data consistency
                            refreshFacultyTable();
                        } else {
                            showToastNotification(result.message || 'Failed to deactivate faculty', 'error');
                        }
                    } catch (error) {
                        console.error('Deactivation error:', error);
                        showToastNotification('An error occurred while deactivating faculty', 'error');
                    }
                },
                'warning'
            );
        }

        function deleteSelected() {
            const selectedCheckboxes = document.querySelectorAll('.faculty-checkbox:checked');
            const selectedCount = selectedCheckboxes.length;
            
            if (selectedCount === 0) {
                showToastNotification('Please select faculty to delete', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Delete Faculty',
                `<strong>Warning:</strong> You are about to permanently delete <strong>${selectedCount} faculty member(s)</strong>.<br><br>
                This will remove all their data including:<br>
                • User accounts<br>
                • Clearance forms and applications<br>
                • Department assignments<br><br>
                <strong>This action cannot be undone.</strong>`,
                'Delete Permanently',
                'Cancel',
                async () => {
                    // Collect user IDs from selected checkboxes
                    const userIds = [];
                    selectedCheckboxes.forEach(checkbox => {
                        const userId = checkbox.getAttribute('data-user-id');
                        if (userId) {
                            userIds.push(parseInt(userId));
                        }
                    });
                    
                    if (userIds.length === 0) {
                        showToastNotification('No valid faculty selected', 'error');
                        return;
                    }
                    
                    // Delete each faculty member (API handles one at a time for faculty)
                    let successCount = 0;
                    let failCount = 0;
                    
                    for (const userId of userIds) {
                        try {
                            const response = await fetch('../../api/users/delete.php', {
                                method: 'POST',
                                credentials: 'include',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({
                                    user_type: 'faculty',
                                    user_id: userId
                                })
                            });
                            const result = await response.json();
                            
                            if (result.success) {
                                successCount++;
                            } else {
                                failCount++;
                                console.error(`Failed to delete user ${userId}: ${result.message}`);
                            }
                        } catch (err) {
                            failCount++;
                            console.error(`Error deleting user ${userId}:`, err);
                        }
                    }
                    
                    // Show result notification
                    if (failCount === 0) {
                        showToastNotification(`✓ Successfully deleted ${successCount} faculty member(s)`, 'success');
                    } else if (successCount > 0) {
                        showToastNotification(`Deleted ${successCount} faculty, ${failCount} failed`, 'warning');
                    } else {
                        showToastNotification('Failed to delete faculty members', 'error');
                    }
                    
                    // Refresh table
                    refreshFacultyTable();
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
            let currentActive = parseInt(activeCount.textContent.replace(',', ''));
            let currentInactive = parseInt(inactiveCount.textContent.replace(',', ''));
            
            if (action === 'activate') {
                currentActive += count;
                currentInactive -= count;
            } else if (action === 'deactivate') {
                currentActive -= count;
                currentInactive += count;
            } else if (action === 'delete') {
                // For delete, we just need to update the total count
                // The specific counts (active, inactive) might not change
                // unless the user explicitly changes them.
                // For simplicity, we'll just update the total count.
                // If the user wants to remove from specific counts, they'd need to handle that.
            }
            
            activeCount.textContent = currentActive.toLocaleString();
            inactiveCount.textContent = currentInactive.toLocaleString();
        }

        // Individual faculty actions
        function editFaculty(facultyId) {
            if (typeof window.openEditFacultyModal === 'function') {
                window.openEditFacultyModal(facultyId);
                // Modal now handles its own data loading (populateEditFacultyForm)
            } else {
                console.error('openEditFacultyModal function not found');
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Edit faculty modal is not available. Please refresh the page.', 'error');
                }
                return;
            }
        }

        // Listen for faculty updates from the EditFacultyModal
        // The modal handles its own form submission and dispatches this event on success
        document.addEventListener('faculty-updated', function(e) {
            refreshFacultyTable();
        });


        function deleteFaculty(userId) {
            const row = document.querySelector(`.faculty-checkbox[data-user-id="${userId}"]`).closest('tr');
            const facultyName = row.querySelector('td:nth-child(3)').textContent;
            
            showConfirmationModal(
                'Delete Faculty',
                `<strong>Warning:</strong> You are about to permanently delete <strong>${facultyName}</strong>.<br><br>
                This will remove all their data including:<br>
                • User account<br>
                • Clearance forms and applications<br>
                • Department assignments<br><br>
                <strong>This action cannot be undone.</strong>`,
                'Delete Permanently',
                'Cancel',
                () => {
                    // Call unified delete API with user_id
                    fetch('../../api/users/delete.php', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            user_type: 'faculty',
                            user_id: userId
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) {
                            throw new Error(res.message || 'Delete failed');
                        }
                        showToastNotification('Faculty deleted successfully', 'success');
                        refreshFacultyTable();
                    })
                    .catch(err => {
                        console.error(err);
                        showToastNotification(err.message, 'error');
                    });
                },
                'danger'
            );
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
            refreshFacultyTable();
        }

        // Clear all filters
        function clearFilters() {
            // Use the comprehensive clearing function
            clearAllSelectionsAndFilters();
            refreshFacultyTable();
            showToastNotification('All filters cleared', 'info');
        }

        // Fetch faculty list from backend and build table body
        async function refreshFacultyTable(){
            try{
                const tbody=document.getElementById('facultyTableBody');
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;">Loading faculty...</td></tr>`;

                const url = new URL('../../api/users/facultyList.php', window.location.href);
                url.searchParams.append('limit', entriesPerPage);
                url.searchParams.append('page', currentPage);
                if (document.getElementById('searchInput').value) url.searchParams.append('search', document.getElementById('searchInput').value);
                if (document.getElementById('employmentStatusFilter').value) url.searchParams.append('employment_status', document.getElementById('employmentStatusFilter').value);
                if (document.getElementById('accountStatusFilter').value) url.searchParams.append('account_status', document.getElementById('accountStatusFilter').value);
                if (document.getElementById('clearanceStatusFilter').value) url.searchParams.append('clearance_status', document.getElementById('clearanceStatusFilter').value);
                if (document.getElementById('schoolTermFilter').value) url.searchParams.append('school_term', document.getElementById('schoolTermFilter').value);

                const res = await fetch(url.toString(),{credentials:'include'});
                const data = await res.json();
                if(!data.success){
                    console.error(data);
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:red;">Error: ${data.message}</td></tr>`;
                    return;
                }

                if (!data.faculty || data.faculty.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;">No faculty members found.</td></tr>`;
                    updateStatistics(data.stats || { total: 0, active: 0, inactive: 0, resigned: 0 });
                    updatePaginationUI(0, 1, entriesPerPage);
                    return;
                }

                tbody.innerHTML='';
                data.faculty.forEach(f=>{
                    const tr=document.createElement('tr');
                    tr.setAttribute('data-term',''); // term unknown for now
                    tr.setAttribute('data-faculty-id', f.user_id); // Add faculty ID for button manager
                    
                    // Check if user existed during the selected term
                    const userExisted = f.user_existed_during_term !== false; // Default to true if not provided
                    let statusRaw = f.clearance_status;
                    
                    // If user didn't exist during term, show N/A
                    if (!userExisted) {
                        statusRaw = 'N/A';
                    }
                    
                    const clearanceKey = (statusRaw || 'unapplied').toLowerCase().replace(/ /g, '-');
                    const accountStatus = (f.account_status || 'inactive').toLowerCase();
                    
                    // Add class for non-existent users
                    if (!userExisted) {
                        tr.classList.add('user-not-existed');
                    }

                    // Build clearance status cell content (without td tags)
                    let clearanceStatusContent = '';
                    if (!userExisted) {
                        clearanceStatusContent = `
                                <div class="clearance-status-primary">
                                    <span class="status-badge-compact clearance-${clearanceKey}">N/A</span>
                                </div>
                                <div class="clearance-status-secondary">User did not exist during this term</div>
                        `;
                    } else {
                        clearanceStatusContent = `
                                <div class="clearance-status-primary">
                                    <span class="status-badge-compact clearance-${clearanceKey}">${statusRaw}</span>
                                </div>
                        `;
                    }

                    tr.innerHTML=`<td class="checkbox-column"><input type=\"checkbox\" class=\"faculty-checkbox\" data-id=\"${f.employee_number}\" data-user-id=\"${f.user_id}\"></td>
                                <td data-label="Employee Number:">${f.employee_number}</td>
                                <td data-label="Name:">${f.first_name} ${f.last_name}</td>
                                <td data-label="Department(s):">${f.departments || 'N/A'}</td>
                                <td data-label="Employment Status:"><span class="status-badge employment-${(f.employment_status || '').toLowerCase().replace(/ /g,'-')}">${f.employment_status}</span></td>
                                <td data-label="Account Status:"><span class="status-badge account-${accountStatus}">${f.account_status || 'N/A'}</span></td>
                                <td data-label="Clearance Progress:" class="clearance-status-cell">${clearanceStatusContent}</td>
                                <td class="action-buttons"><div class="action-buttons">
                                        <button class=\"btn-icon view-progress-btn\" onclick=\"viewClearanceProgress('${f.employee_number}')\" title=\"View Clearance Progress\"><i class=\"fas fa-tasks\"></i></button>
                                        <button class=\"btn-icon edit-btn\" onclick=\"editFaculty('${f.employee_number}')\" title=\"Edit\"><i class=\"fas fa-edit\"></i></button>
                                        <button class=\"btn-icon delete-btn\" onclick=\"deleteFaculty(${f.user_id})\" title=\"Delete\"><i class=\"fas fa-trash\"></i></button>
                                   </div></td>`;

                    if(accountStatus!=='active'){
                        tr.classList.add('row-disabled');
                    }
                    tbody.appendChild(tr);
                });
                updateStatistics(data.stats);
                updatePaginationUI(data.total, data.page, data.limit);
            }catch(err){
                console.error(err);
                document.getElementById('facultyTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:red;">Error loading data.</td></tr>`;
            }
        }

        // Update statistics helper function
        function updateStatistics(statsOrction) {
            const totalCount = document.getElementById('totalFaculty');
            const activeCount = document.getElementById('activeFaculty');
            const inactiveCount = document.getElementById('inactiveFaculty');
            
            if (typeof statsOrction === 'object' && statsOrction !== null) {
                // If an object is passed, set the stats directly
                totalCount.textContent = (statsOrction.total || 0).toLocaleString();
                activeCount.textContent = (statsOrction.active || 0).toLocaleString();
                inactiveCount.textContent = (statsOrction.inactive || 0).toLocaleString();
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
            span.style.padding = '8px 12px';
            span.style.color = 'var(--medium-muted-blue)';
            pageNumbersContainer.appendChild(span);
        }

        function goToPage(pageNum) {
            currentPage = pageNum;
            refreshFacultyTable();
        }

        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next') {
                const totalPages = Math.ceil(totalEntries / entriesPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                }
            }
            refreshFacultyTable();
        }

        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
            refreshFacultyTable();
        }

        // Pagination variables
        let currentPage = 1;
        let entriesPerPage = 20;
        let totalEntries = 0;

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
            try {
                if (typeof window.openFacultyRegistrationModal === 'function') {
                    window.openFacultyRegistrationModal();
                } else {
                    // Function not available - show error immediately
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Faculty registration feature is not available. Please refresh the page.', 'error');
                    }
                }
            } catch (error) {
                // Silent error handling - no console output
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open faculty registration modal. Please try again.', 'error');
                }
            }
        }

        function triggerImportModal() {
            try {
                if (typeof window.openImportModal === 'function') {
                    window.openImportModal('faculty', 'faculty_import', 'Admin');
                } else {
                    // Function not available - show error immediately
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Import feature is not available. Please refresh the page.', 'error');
                    }
                }
            } catch (error) {
                // Silent error handling - no console output
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open import modal. Please try again.', 'error');
                }
            }
        }

        function triggerExportModal() {
            try {
                // Check if ExportModal is available
                if (typeof window.openExportModal === 'function') {
                    window.openExportModal();
                } else {
                    console.error('[FacultyManagement] window.openExportModal is not a function');
                    // Try to find the modal directly
                    const modal = document.getElementById('exportModal');
                    if (modal) {
                        if (typeof window.openModal === 'function') {
                            window.openModal('exportModal');
                            // Trigger data loading after modal opens
                            setTimeout(() => {
                                if (typeof window.openExportModal === 'function') {
                                    window.openExportModal();
                                } else {
                                    // Manually trigger data loading via modal:open event
                                    const event = new CustomEvent('modal:open', { detail: { modal: modal } });
                                    modal.dispatchEvent(event);
                                }
                            }, 100);
                        } else {
                            modal.style.display = 'flex';
                            document.body.classList.add('modal-open');
                            requestAnimationFrame(() => {
                                modal.classList.add('active');
                            });
                            // Trigger data loading
                            setTimeout(() => {
                                if (typeof window.openExportModal === 'function') {
                                    window.openExportModal();
                                } else {
                                    // Manually trigger data loading via modal:open event
                                    const event = new CustomEvent('modal:open', { detail: { modal: modal } });
                                    modal.dispatchEvent(event);
                                }
                            }, 100);
                        }
                    } else {
                        console.error('[FacultyManagement] Export modal element not found');
                        if (typeof showToastNotification === 'function') {
                            showToastNotification('Export modal not found. Please refresh the page.', 'error');
                        }
                    }
                }
            } catch (error) {
                console.error('[FacultyManagement] Error in triggerExportModal:', error);
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open export modal. Please try again.', 'error');
                }
            }
        }

        // Clearance Progress Modal Function
        function viewClearanceProgress(facultyId) {
            // Get faculty name from the table row
            const row = document.querySelector(`.faculty-checkbox[data-id="${facultyId}"]`).closest('tr');
            const facultyName = row ? row.querySelector('td:nth-child(3)').textContent : 'Faculty';
            
            // Get the current school term filter value
            const schoolTerm = document.getElementById('schoolTermFilter')?.value || '';
            
            // Open the clearance progress modal with school term
            openClearanceProgressModal(facultyId, 'faculty', facultyName, schoolTerm);
        }

        // Rejection Remarks Modal Functions
        let currentRejectionData = {
            targetId: null,
            targetName: null,
            targetType: 'faculty',
            isBulk: false,
            targetIds: []
        };

        async function populateFilter(selectId, url, placeholder, valueField = 'value', textField = 'text') {
            const select = document.getElementById(selectId);
            if (!select) {
                console.error(`Element with id "${selectId}" not found`);
                return;
            }
            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();

                select.innerHTML = `<option value="">${placeholder}</option>`;
                if (data.success && data.options) {
                    const termMap = {
                        '1st': '1st Semester',
                        '2nd': '2nd Semester',
                        '3rd': '3rd Semester',
                        'Summer': 'Summer'
                    };

                    data.options.forEach(option => {
                        const optionElement = document.createElement('option');
                        optionElement.value = typeof option === 'object' ? option[valueField] : option;
                        let textContent = typeof option === 'object' ? option[textField] : option;

                        // Apply term mapping for school terms filter
                        if (selectId === 'schoolTermFilter' && typeof option === 'object') {
                            const [year, term] = option.text.split(' - ');
                            textContent = `${year} - ${termMap[term] || term}`;
                        }
                        
                        optionElement.textContent = textContent;
                        select.appendChild(optionElement);
                    });
                }
            } catch (error) {
                console.error(`Error loading options for ${selectId}:`, error);
                select.innerHTML = `<option value="">Error loading options</option>`;
            }
        }

        async function loadAccountStatuses() {
            const accountStatusesFilter = document.getElementById('accountStatusFilter');
            accountStatusesFilter.innerHTML = '<option value="">Loading account statuses...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'users');
            url.searchParams.append('column', 'account_status');
            url.searchParams.append('exclude', 'graduated');
            await populateFilter('accountStatusFilter', url, 'All Account Statuses');
        }

        async function loadEmploymentStatuses() {
            const employmentStatusesFilter = document.getElementById('employmentStatusFilter');
            employmentStatusesFilter.innerHTML = '<option value="">Loading employment statuses...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'faculty');
            url.searchParams.append('column', 'employment_status');
            await populateFilter('employmentStatusFilter', url, 'All Employment Statuses');
        }

        async function loadClearanceStatuses() {
            const clearanceStatusesFilter = document.getElementById('clearanceStatusFilter');
            clearanceStatusesFilter.innerHTML = '<option value="">Loading clearance statuses...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'enum');
            url.searchParams.append('table', 'clearance_forms');
            url.searchParams.append('column', 'clearance_form_progress');
            await populateFilter('clearanceStatusFilter', url, 'All Clearance Statuses');
        }

        async function loadSchoolTerms() {
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            schoolTermFilter.innerHTML = '<option value="">Loading school terms...</option>';
            const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
            url.searchParams.append('type', 'school_terms');
            await populateFilter('schoolTermFilter', url, 'All School Terms');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {

            // Load dynamic filters
            loadEmploymentStatuses();
            loadAccountStatuses();
            loadClearanceStatuses();
            updateTermIndicatorBanner();
            loadSchoolTerms();

            // load faculty table
            refreshFacultyTable();


            // Add event listeners for checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
            
            // Add event listeners for filter checkboxes in bulk selection modal
            document.addEventListener('change', function(e) {
                if (e.target.id && e.target.id.startsWith('filter')) {
                    // Update any real-time feedback if needed
                }
            });

            // Listen for new faculty event from modal
            document.addEventListener('faculty-added',function(e){
                refreshFacultyTable().then(()=>{
                    showToastNotification('Faculty table refreshed', 'success');
                });
            });


            // load current clearance period for banner
            fetch('../../api/clearance/periods.php', { credentials: 'include' })
                .then(r => r.json())
                .then(per => {
                    const yearEl = document.getElementById('currentAcademicYear');
                    const semesterEl = document.getElementById('currentSemester');
                    if (!yearEl || !semesterEl) return;
                    if (per.success && per.active_periods && per.active_periods.length > 0) {
                        const p = per.active_periods[0];
                        // Map semester name to full format
                        const termMap = { 
                            '1st': '1st Semester', 
                            '2nd': '2nd Semester', 
                            '3rd': '3rd Semester',
                            '1st Semester': '1st Semester',
                            '2nd Semester': '2nd Semester',
                            '3rd Semester': '3rd Semester'
                        };
                        const semLabel = termMap[p.semester_name] || p.semester_name || '';
                        yearEl.textContent = p.school_year;
                        semesterEl.textContent = semLabel;
                    } else {
                        yearEl.textContent = 'No active period';
                        semesterEl.textContent = 'No term';
                    }
                })
                .catch(() => {
                    const yearEl = document.getElementById('currentAcademicYear');
                    const semesterEl = document.getElementById('currentSemester');
                    if (yearEl) yearEl.textContent = 'Unable to load';
                    if (semesterEl) semesterEl.textContent = 'Error';
                });
              
            // Add click outside modal functionality for bulk selection modal
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('bulkSelectionModal');
                if (modal && modal.style.display === 'flex') {
                    if (e.target === modal) {
                        closeBulkSelectionModal();
                    }
                }
            });
            
            // Add keyboard support for bulk selection modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('bulkSelectionModal');
                    if (modal && modal.style.display === 'flex') {
                        closeBulkSelectionModal();
                    }
                }
            });

            // Make selection counter pill act as Clear Selection when active
            const pill = document.getElementById('selectionCounterPill');
            if (pill) {
                pill.addEventListener('click', function() {
                    if (!pill.classList.contains('has-selections') && !pill.classList.contains('all-selected')) return;
                    clearAllSelections();
                });
                pill.addEventListener('keydown', function(e){
                    if ((e.key === 'Enter' || e.key === ' ') && (pill.classList.contains('has-selections') || pill.classList.contains('all-selected'))){
                        e.preventDefault();
                        clearAllSelections();
                    }
                });
            }
        });
        

        // Bulk Selection Modal Functions
        function openBulkSelectionModal() {
            try {
                if (typeof window.openModal === 'function') {
                    window.openModal('bulkSelectionModal');
                    // Reset all checkboxes after opening
                    setTimeout(() => {
                        resetBulkSelectionFilters();
                    }, 100);
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
                        
                        // Reset all checkboxes
                        resetBulkSelectionFilters();
                    } else {
                        // Modal not found - show error
                        if (typeof showToastNotification === 'function') {
                            showToastNotification('Selection filters feature is temporarily unavailable.', 'error');
                        }
                    }
                }
            } catch (error) {
                // Silent error handling
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open selection filters. Please try again.', 'error');
                }
            }
        }

        window.closeBulkSelectionModal = function() {
            try {
                const modal = document.getElementById('bulkSelectionModal');
                if (!modal) {
                    console.warn('[FacultyManagement] Bulk selection modal not found');
                    return;
                }

                // Use window.closeModal if available, otherwise fallback
                if (typeof window.closeModal === 'function') {
                    window.closeModal('bulkSelectionModal');
                } else {
                    // Fallback to direct manipulation
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    document.body.classList.remove('modal-open');
                    modal.classList.remove('active');
                }
            } catch (error) {
                // Silent error handling
            }
        }

        function resetBulkSelectionFilters() {
            // Reset all filter checkboxes
            document.getElementById('filterFullTime').checked = false;
            document.getElementById('filterPartTime').checked = false;
            document.getElementById('filterPartTimeFullLoad').checked = false;
            document.getElementById('filterActive').checked = false;
            document.getElementById('filterInactive').checked = false;
            document.getElementById('filterUnapplied').checked = false;
            document.getElementById('filterInProgress').checked = false;
            document.getElementById('filterComplete').checked = false;
        }


        function applyBulkSelection() {
            const selectedFilters = getSelectedFilters();
            
            if (Object.values(selectedFilters).every(filter => !filter)) {
                // No filters means select all visible rows (wildcard)
                selectAllVisibleRows();
                closeBulkSelectionModal();
                updateBulkButtons();
                updateSelectionCounter();
                showToastNotification('All visible rows selected', 'success');
                return;
            }
            
            // Apply the filters to select faculty
            selectFacultyByFilters(selectedFilters);
            
            // Close the modal
            closeBulkSelectionModal();
            
            // Update bulk buttons based on new selection
            updateBulkButtons();
            updateSelectionCounter();
            
            showToastNotification('Bulk selection applied successfully', 'success');
        }

        // Select all visible rows; if include-all-pages is desired in future, we can extend this
        function selectAllVisibleRows(){
            const rows = document.querySelectorAll('#facultyTableBody tr');
            let count = 0;
            rows.forEach(r=>{
                if (r.style.display === 'none') return; // only visible scope
                const cb = r.querySelector('.faculty-checkbox');
                if (cb){ cb.checked = true; count++; }
            });
            showInfoToast(`Selected ${count} faculty`);
        }

        function getSelectedFilters() {
            return {
                fullTime: document.getElementById('filterFullTime').checked,
                partTime: document.getElementById('filterPartTime').checked,
                partTimeFullLoad: document.getElementById('filterPartTimeFullLoad').checked,
                active: document.getElementById('filterActive').checked,
                inactive: document.getElementById('filterInactive').checked,
                unapplied: document.getElementById('filterUnapplied').checked,
                inProgress: document.getElementById('filterInProgress').checked,
                complete: document.getElementById('filterComplete').checked
            };
        }

        function selectFacultyByFilters(filters) {
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            let selectedCount = 0;
            
            tableRows.forEach(row => {
                const shouldSelect = shouldRowBeSelected(row, filters);
                const checkbox = row.querySelector('.faculty-checkbox');
                
                if (checkbox) {
                    checkbox.checked = shouldSelect;
                    if (shouldSelect) selectedCount++;
                }
            });
            
            // Update selection counter
            updateSelectionCounter();
            
            // Show results count
            showInfoToast(`Selected ${selectedCount} faculty based on filters`);
        }

        function shouldRowBeSelected(row, filters) {
            // Get row data
            const employmentBadge = row.querySelector('.status-badge[class*="employment-"]');
            const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
            const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-applied, .status-badge.clearance-in-progress, .status-badge.clearance-complete');
            
            let employmentMatch = false;
            let accountMatch = false;
            let clearanceMatch = false;
            
            // Check employment status filters
            if (filters.fullTime || filters.partTime || filters.partTimeFullLoad) {
                if (filters.fullTime && employmentBadge && employmentBadge.classList.contains('employment-full-time')) {
                    employmentMatch = true;
                }
                if (filters.partTime && employmentBadge && employmentBadge.classList.contains('employment-part-time')) {
                    employmentMatch = true;
                }
                if (filters.partTimeFullLoad && employmentBadge && employmentBadge.classList.contains('employment-part-time-full-load')) {
                    employmentMatch = true;
                }
            } else {
                // If no employment filters selected, consider it a match (wildcard)
                employmentMatch = true;
            }
            
            // Check account status filters
            if (filters.active || filters.inactive) {
                if (filters.active && accountBadge && accountBadge.classList.contains('account-active')) {
                    accountMatch = true;
                }
                if (filters.inactive && accountBadge && accountBadge.classList.contains('account-inactive')) {
                    accountMatch = true;
                }
            } else {
                // If no account filters selected, consider it a match (wildcard)
                accountMatch = true;
            }
            
            // Check clearance progress filters
            if (filters.unapplied || filters.inProgress || filters.complete) {
                if (filters.unapplied && clearanceBadge && clearanceBadge.classList.contains('clearance-unapplied')) {
                    clearanceMatch = true;
                }
                if (filters.inProgress && clearanceBadge && clearanceBadge.classList.contains('clearance-in-progress')) {
                    clearanceMatch = true;
                }
                if (filters.complete && clearanceBadge && clearanceBadge.classList.contains('clearance-complete')) {
                    clearanceMatch = true;
                }
            } else {
                // If no clearance filters selected, consider it a match (wildcard)
                clearanceMatch = true;
            }
            
            // Row should be selected if it matches all three filter categories
            return employmentMatch && accountMatch && clearanceMatch;
        }
        
        // Function to clear all selections and filters
        function clearAllSelectionsAndFilters() {
            // Clear all faculty checkboxes
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Clear search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.value = '';
            
            // Clear filter dropdowns
            const employmentFilter = document.getElementById('employmentStatusFilter');
            const clearanceFilter = document.getElementById('clearanceStatusFilter');
            const schoolTermFilter = document.getElementById('schoolTermFilter');
            
            if (employmentFilter) employmentFilter.value = '';
            if (clearanceFilter) clearanceFilter.value = '';
            if (schoolTermFilter) schoolTermFilter.value = '';
            
            // Reset bulk selection modal filters (if modal is open)
            resetBulkSelectionFilters();
            
            // Update UI states
            updateSelectionCounter();
            updateBulkButtons();
            
            // Show all rows (remove any filter-based hiding)
            const tableRows = document.querySelectorAll('#facultyTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            // Disable clear selection button (since no selections)
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            if (clearSelectionBtn) clearSelectionBtn.disabled = true;
        }

        // Function to clear all faculty selections only (keeps filters)
        function clearAllSelections() {
            const selectedCount = getSelectedCount();
            
            if (selectedCount === 0) {
                showToastNotification('No selections to clear', 'info');
                return;
            }
            
            // Clear all faculty checkboxes
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Update UI states
            updateSelectionCounter();
            updateBulkButtons();
            
            showToastNotification(`Cleared ${selectedCount} selections`, 'success');
        }
    </script>
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Include Clearance Button Manager -->
    <script src="../../assets/js/clearance-button-manager.js"></script>
    
</body>
</html> 