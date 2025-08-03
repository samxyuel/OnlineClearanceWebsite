<?php // Online Clearance Website - Admin Faculty Management
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$_SESSION['user_id'] = 3; $_SESSION['role_id'] = 1; $_SESSION['first_name'] = 'Admin'; $_SESSION['last_name'] = 'User';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="navbar">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="logo">
                        <h1>goSTI</h1>
                        <!--<p>Online Clearance System</p>-->
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-name">Admin User</span>
                    <div class="user-dropdown">
                        <button class="dropdown-toggle">▼</button>
                        <div class="dropdown-menu">
                            <a href="../../pages/shared/profile.php">Profile</a>
                            <a href="../../pages/shared/settings.php">Settings</a>
                            <a href="../../pages/auth/logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-container">
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <div class="main-content">
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
                            <h3 id="totalFaculty">6</h3>
                            <p>Total Faculty</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeFaculty">4</h3>
                            <p>Active</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon inactive">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="inactiveFaculty">1</h3>
                            <p>Inactive</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon graduated">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="resignedFaculty">1</h3>
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
                            <label class="select-all-checkbox">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                <span class="checkmark"></span>
                                Select All
                            </label>
                            <div class="bulk-buttons">
                                <button class="btn btn-secondary" onclick="undoLastAction()" disabled>
                                    <i class="fas fa-undo"></i> Undo
                                </button>
                                <button class="btn btn-success" onclick="activateSelected()" disabled>
                                    <i class="fas fa-user-check"></i> Activate
                                </button>
                                <button class="btn btn-warning" onclick="deactivateSelected()" disabled>
                                    <i class="fas fa-user-times"></i> Deactivate
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
                                    <!-- Sample data - will be populated by JavaScript -->
                                    <tr data-term="2024-2025-1st">
                                        <td><input type="checkbox" class="faculty-checkbox" data-id="LCA123P"></td>
                                        <td>LCA123P</td>
                                        <td>Dr. Maria Santos</td>
                                        <td><span class="status-badge employment-full-time">Full Time</span></td>
                                        <td><span class="status-badge account-active">Active</span></td>
                                        <td><span class="status-badge clearance-pending">Pending</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit-btn" onclick="editFaculty('LCA123P')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon status-toggle-btn active" onclick="toggleFacultyStatus(this)" title="Toggle Status">
                                                    <i class="fas fa-toggle-on"></i>
                                                </button>
                                                <button class="btn-icon delete-btn" onclick="deleteFaculty('LCA123P')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-term="2024-2025-1st">
                                        <td><input type="checkbox" class="faculty-checkbox" data-id="MTH456A"></td>
                                        <td>MTH456A</td>
                                        <td>Prof. Juan Dela Cruz</td>
                                        <td><span class="status-badge employment-part-time">Part Time</span></td>
                                        <td><span class="status-badge account-active">Active</span></td>
                                        <td><span class="status-badge clearance-completed">Completed</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit-btn" onclick="editFaculty('MTH456A')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon status-toggle-btn active" onclick="toggleFacultyStatus(this)" title="Toggle Status">
                                                    <i class="fas fa-toggle-on"></i>
                                                </button>
                                                <button class="btn-icon delete-btn" onclick="deleteFaculty('MTH456A')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-term="2024-2025-2nd">
                                        <td><input type="checkbox" class="faculty-checkbox" data-id="ENG789B"></td>
                                        <td>ENG789B</td>
                                        <td>Dr. Ana Rodriguez</td>
                                        <td><span class="status-badge employment-part-time-full-load">Part Time - Full Load</span></td>
                                        <td><span class="status-badge account-inactive">Inactive</span></td>
                                        <td><span class="status-badge clearance-unapplied">Unapplied</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit-btn" onclick="editFaculty('ENG789B')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon status-toggle-btn inactive" onclick="toggleFacultyStatus(this)" title="Toggle Status">
                                                    <i class="fas fa-toggle-off"></i>
                                                </button>
                                                <button class="btn-icon delete-btn" onclick="deleteFaculty('ENG789B')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-term="2023-2024-2nd">
                                        <td><input type="checkbox" class="faculty-checkbox" data-id="CSC321D"></td>
                                        <td>CSC321D</td>
                                        <td>Prof. Carlos Mendoza</td>
                                        <td><span class="status-badge employment-full-time">Full Time</span></td>
                                        <td><span class="status-badge account-resigned">Resigned</span></td>
                                        <td><span class="status-badge clearance-completed">Completed</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit-btn" onclick="editFaculty('CSC321D')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon status-toggle-btn resigned" onclick="toggleFacultyStatus(this)" title="Toggle Status">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                                <button class="btn-icon delete-btn" onclick="deleteFaculty('CSC321D')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-term="2024-2025-1st">
                                        <td><input type="checkbox" class="faculty-checkbox" data-id="BIO654E"></td>
                                        <td>BIO654E</td>
                                        <td>Prof. Sarah Johnson</td>
                                        <td><span class="status-badge employment-part-time">Part Time</span></td>
                                        <td><span class="status-badge account-active">Active</span></td>
                                        <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit-btn" onclick="editFaculty('BIO654E')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon status-toggle-btn active" onclick="toggleFacultyStatus(this)" title="Toggle Status">
                                                    <i class="fas fa-toggle-on"></i>
                                                </button>
                                                <button class="btn-icon delete-btn" onclick="deleteFaculty('BIO654E')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-term="2024-2025-2nd">
                                        <td><input type="checkbox" class="faculty-checkbox" data-id="PHY987F"></td>
                                        <td>PHY987F</td>
                                        <td>Dr. Michael Chen</td>
                                        <td><span class="status-badge employment-part-time-full-load">Part Time - Full Load</span></td>
                                        <td><span class="status-badge account-active">Active</span></td>
                                        <td><span class="status-badge clearance-completed">Completed</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit-btn" onclick="editFaculty('PHY987F')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon status-toggle-btn active" onclick="toggleFacultyStatus(this)" title="Toggle Status">
                                                    <i class="fas fa-toggle-on"></i>
                                                </button>
                                                <button class="btn-icon delete-btn" onclick="deleteFaculty('PHY987F')" title="Delete">
                                                    <i class="fas fa-trash"></i>
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
    </main>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Modals -->
    <?php include '../../Modals/FacultyRegistryModal.php'; ?>
    <?php include '../../Modals/EditFacultyModal.php'; ?>
               <?php include '../../Modals/FacultyExportModal.php'; ?>
           <?php include '../../Modals/FacultyImportModal.php'; ?>

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

        function toggleHeaderCheckbox() {
            const headerCheckbox = document.getElementById('headerCheckbox');
            const facultyCheckboxes = document.querySelectorAll('.faculty-checkbox');
            
            facultyCheckboxes.forEach(checkbox => {
                checkbox.checked = headerCheckbox.checked;
            });
            
            updateBulkButtons();
            updateSelectionCounter();
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

        // Bulk Actions with Confirmation
        function activateSelected() {
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
                () => {
                    // Perform activation
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                        const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.remove('account-inactive', 'account-resigned');
                        statusBadge.classList.add('account-active');
                        toggleBtn.classList.remove('inactive', 'resigned');
                        toggleBtn.classList.add('active');
                        toggleBtn.querySelector('i').classList.remove('fa-toggle-off', 'fa-user-slash');
                        toggleBtn.querySelector('i').classList.add('fa-toggle-on');
                    });
                    
                    // Update statistics
                    updateBulkStatistics('activate', selectedCount);
                    showToastNotification(`✓ Successfully activated ${selectedCount} faculty`, 'success');
                },
                'info'
            );
        }

        function deactivateSelected() {
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
                () => {
                    // Perform deactivation
                    const selectedRows = document.querySelectorAll('.faculty-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
                        const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        statusBadge.textContent = 'Inactive';
                        statusBadge.classList.remove('account-active', 'account-resigned');
                        statusBadge.classList.add('account-inactive');
                        toggleBtn.classList.remove('active', 'resigned');
                        toggleBtn.classList.add('inactive');
                        toggleBtn.querySelector('i').classList.remove('fa-toggle-on', 'fa-user-slash');
                        toggleBtn.querySelector('i').classList.add('fa-toggle-off');
                    });
                    
                    // Update statistics
                    updateBulkStatistics('deactivate', selectedCount);
                    showToastNotification(`✓ Successfully deactivated ${selectedCount} faculty`, 'success');
                },
                'warning'
            );
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
                        const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Resigned';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-resigned');
                            
                            toggleBtn.classList.remove('active', 'inactive');
                            toggleBtn.classList.add('resigned');
                            toggleBtn.querySelector('i').classList.remove('fa-toggle-on', 'fa-toggle-off');
                            toggleBtn.querySelector('i').classList.add('fa-user-slash');
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
            
            if (action === 'activate') {
                currentActive += count;
                currentInactive -= count;
            } else if (action === 'deactivate') {
                currentActive -= count;
                currentInactive += count;
            } else if (action === 'resigned') {
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

        // Individual faculty actions
        function editFaculty(facultyId) {
            openEditFacultyModal(facultyId);
        }

        function toggleFacultyStatus(button) {
            const row = button.closest('tr');
            const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-resigned');
            const facultyId = row.querySelector('.faculty-checkbox').getAttribute('data-id');
            
            if (statusBadge.classList.contains('account-active')) {
                // Deactivate
                showConfirmationModal(
                    'Deactivate Faculty',
                    `Are you sure you want to deactivate ${statusBadge.textContent}?`,
                    'Deactivate',
                    'Cancel',
                    () => {
                        statusBadge.textContent = 'Inactive';
                        statusBadge.classList.remove('account-active');
                        statusBadge.classList.add('account-inactive');
                        button.classList.remove('active');
                        button.classList.add('inactive');
                        button.querySelector('i').classList.remove('fa-toggle-on');
                        button.querySelector('i').classList.add('fa-toggle-off');
                        showToastNotification('Faculty deactivated successfully', 'success');
                    },
                    'warning'
                );
            } else if (statusBadge.classList.contains('account-inactive')) {
                // Activate
                showConfirmationModal(
                    'Activate Faculty',
                    `Are you sure you want to activate ${statusBadge.textContent}?`,
                    'Activate',
                    'Cancel',
                    () => {
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.remove('account-inactive');
                        statusBadge.classList.add('account-active');
                        button.classList.remove('inactive');
                        button.classList.add('active');
                        button.querySelector('i').classList.remove('fa-toggle-off');
                        button.querySelector('i').classList.add('fa-toggle-on');
                        showToastNotification('Faculty activated successfully', 'success');
                    },
                    'info'
                );
            } else if (statusBadge.classList.contains('account-resigned')) {
                // Reactivate from resigned
                showConfirmationModal(
                    'Reactivate Faculty',
                    `Are you sure you want to reactivate ${statusBadge.textContent}?`,
                    'Reactivate',
                    'Cancel',
                    () => {
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.remove('account-resigned');
                        statusBadge.classList.add('account-active');
                        button.classList.remove('resigned');
                        button.classList.add('active');
                        button.querySelector('i').classList.remove('fa-user-slash');
                        button.querySelector('i').classList.add('fa-toggle-on');
                        showToastNotification('Faculty reactivated successfully', 'success');
                    },
                    'info'
                );
            }
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
        function updateFilterPrograms() {
            // This function is not needed for faculty management
            // Keeping it for compatibility
        }

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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializePagination();
            updateSelectionCounter(); // Initialize the counter
            
            // Add event listeners for checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('faculty-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
        });
    </script>
    <script src="../../assets/js/alerts.js"></script>
</body>
</html> 