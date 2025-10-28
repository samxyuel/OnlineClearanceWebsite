<?php
// Online Clearance Website - Regular Staff Senior High School Student Management

// Permission-based access control: Regular Staff can always view student data
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
    
    // Check if user is a staff member (more robust check)
    $staffCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM staff s 
        WHERE s.user_id = ? AND s.is_active = 1
    ");
    $staffCheck->execute([$userId]);
    $isStaff = (int)$staffCheck->fetchColumn() > 0;
    
    // If not staff, check if user has any role that should have access
    if (!$isStaff) {
        $roleCheck = $pdo->prepare("
            SELECT r.role_name 
            FROM users u 
            JOIN user_roles ur ON u.user_id = ur.user_id 
            JOIN roles r ON ur.role_id = r.role_id 
            WHERE u.user_id = ? AND r.role_name IN ('Admin', 'Program Head', 'School Administrator')
        ");
        $roleCheck->execute([$userId]);
        $hasAdminRole = $roleCheck->fetchColumn();
        
        // TEMPORARILY DISABLED FOR TESTING - ALLOW ANY LOGGED IN USER
        // if (!$hasAdminRole) {
        //     header('HTTP/1.1 403 Forbidden'); 
        //     echo 'Access denied. Regular staff access required.'; 
        //     exit; 
        // }
    }
    
    // Check permission flags (for conditional UI behavior)
    $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE is_active=1")->fetchColumn() > 0;
    
    $studentSignatoryCheck = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments sa JOIN designations d ON sa.designation_id=d.designation_id WHERE sa.user_id=? AND sa.clearance_type='student' AND sa.is_active=1");
    $studentSignatoryCheck->execute([$userId]);
    $hasStudentSignatoryAccess = (int)$studentSignatoryCheck->fetchColumn() > 0;
    
    $canPerformSignatoryActions = $hasActivePeriod && $hasStudentSignatoryAccess;
    
    // Store permission flags for use in the page
    $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
    $GLOBALS['hasStudentSignatoryAccess'] = $hasStudentSignatoryAccess;
    $GLOBALS['canPerformSignatoryActions'] = $canPerformSignatoryActions;
    
} catch (Throwable $e) {
    header('HTTP/1.1 500 Internal Server Error'); 
    echo 'System error. Please try again later.'; 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior High School Student Management - Staff Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                            <h2><i class="fas fa-graduation-cap"></i> Senior High School Student Management</h2>
                            <p>Review and sign senior high school student clearance requests as a staff signatory</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>
                                <span>Scope: Senior High School Students Only</span>
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
                                    <i class="fas fa-file-export"></i> Export Report
                                </button>
                            </div>
                        </div>

                        <!-- Tabs + Current Period Wrapper -->
                        <div class="tab-banner-wrapper">
                            <!-- Tab Navigation for quick status views -->
                            <div class="tab-nav" id="studentTabNav">
                                <button class="tab-pill active" data-status="" onclick="switchStudentTab(this)">Overall</button>
                                <button class="tab-pill" data-status="active" onclick="switchStudentTab(this)">Active</button>
                                <button class="tab-pill" data-status="inactive" onclick="switchStudentTab(this)">Inactive</button>
                                <button class="tab-pill" data-status="graduated" onclick="switchStudentTab(this)">Graduated</button>
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
                                    <button class="btn btn-primary bulk-selection-filters-btn" onclick="openBulkSelectionModal()">
                                        <i class="fas fa-filter"></i> Bulk Selection Filters
                                    </button>
                                    <button class="selection-counter-display" id="selectionCounterPill" onclick="openBulkSelectionModal()">
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
                                            <!-- Sample data -->
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
                                                        <button class="btn-icon approve-btn" onclick="approveSignatory('02000288322', 'CF-2025-00001', 1)" title="Approve Signatory">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-icon reject-btn" onclick="rejectSignatory('02000288322', 'CF-2025-00001', 1)" title="Reject Signatory">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-1st">
                                                <td><input type="checkbox" class="student-checkbox" data-id="02000288323"></td>
                                                <td>02000288323</td>
                                                <td>John Doe</td>
                                                <td>BSBA</td>
                                                <td>3rd Year</td>
                                                <td>3/1-2</td>
                                                <td><span class="status-badge account-inactive">Inactive</span></td>
                                                <td><span class="status-badge clearance-unapplied">Unapplied</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon approve-btn" onclick="approveStudentClearance('02000288323')" title="Approve Clearance">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-icon reject-btn" onclick="rejectStudentClearance('02000288323')" title="Reject Clearance">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-1st">
                                                <td><input type="checkbox" class="student-checkbox" data-id="02000288324"></td>
                                                <td>02000288324</td>
                                                <td>Maria Garcia</td>
                                                <td>BSE</td>
                                                <td>2nd Year</td>
                                                <td>2/1-1</td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-completed">Completed</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon approve-btn" onclick="approveStudentClearance('02000288324')" title="Approve Clearance">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-icon reject-btn" onclick="rejectStudentClearance('02000288324')" title="Reject Clearance">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr data-term="2024-2025-2nd">
                                                <td><input type="checkbox" class="student-checkbox" data-id="02000288330"></td>
                                                <td>02000288330</td>
                                                <td>Carlos Rodriguez</td>
                                                <td>BSCE</td>
                                                <td>1st Year</td>
                                                <td>1/1-3</td>
                                                <td><span class="status-badge account-active">Active</span></td>
                                                <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-icon approve-btn" onclick="approveStudentClearance('02000288330')" title="Approve Clearance">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-icon reject-btn" onclick="rejectStudentClearance('02000288330')" title="Reject Clearance">
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
    <?php include '../../Modals/SHSStudentRegistryModal.php'; ?>
    <?php include '../../Modals/SHSEditStudentModal.php'; ?>
    
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

    <script src="../../assets/js/activity-tracker.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <script>
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

        // Bulk selection modal functions
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
                            if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Approved'); }
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
                        if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Approved'); }
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
            
            // Open rejection remarks modal for individual rejection
            openRejectionRemarksModal(studentId, studentName, 'student', false);
        }

        // Filter functions
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            const tableRows = document.querySelectorAll('#studentTableBody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const studentName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                
                let shouldShow = true;
                
                // Search filter
                if (searchTerm && !studentName.includes(searchTerm)) {
                    shouldShow = false;
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
            
            updateFilteredEntries();
            showToastNotification(`Showing ${visibleCount} of ${tableRows.length} students`, 'info');
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            const tableRows = document.querySelectorAll('#studentTableBody tr');
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            updateFilteredEntries();
            showToastNotification('All filters cleared', 'info');
        }

        function updateStatisticsByTerm() {
            applyFilters();
        }

        // Pagination functions
        let currentPage = 1;
        let entriesPerPage = 20;
        let filteredEntries = [];

        function initializePagination() {
            const allRows = document.querySelectorAll('#studentTableBody tr');
            filteredEntries = Array.from(allRows);
            updatePagination();
        }

        function updatePagination() {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            const startEntry = (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, filteredEntries.length);
            
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startEntry} to ${endEntry} of ${filteredEntries.length} entries`;
            
            updatePageNumbers(totalPages);
            
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
            
            showCurrentPageEntries();
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
            updatePagination();
        }

        function changePage(direction) {
            const totalPages = Math.ceil(filteredEntries.length / entriesPerPage);
            
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }
            
            updatePagination();
        }

        function changeEntriesPerPage() {
            const newEntriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            entriesPerPage = newEntriesPerPage;
            currentPage = 1;
            updatePagination();
        }

        function showCurrentPageEntries() {
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + entriesPerPage;
            
            filteredEntries.forEach(row => {
                row.style.display = 'none';
            });
            
            for (let i = startIndex; i < endIndex && i < filteredEntries.length; i++) {
                filteredEntries[i].style.display = '';
            }
        }

        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#studentTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1;
            updatePagination();
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Load current clearance period for banner
            loadCurrentPeriod();
            
            updateSelectionCounter();
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('student-checkbox')) {
                    updateBulkButtons();
                    updateSelectionCounter();
                }
            });
            
            initializePagination();
            
            // Check signatory status and initialize buttons
            initializeSignatoryButtons();
            
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
            
            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();
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
                // server-side records
                try {
                    for (const id of currentRejectionData.targetIds) {
                        const uid = await resolveUserIdFromStudentNumber(id);
                        if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Rejected', additionalRemarks); }
                    }
                } catch (e) {}
                
                showToastNotification(`✓ Successfully rejected clearance for ${currentRejectionData.targetIds.length} students with remarks`, 'success');
            } else {
                // Update individual student row
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
                // server-side record
                try {
                    const uid = await resolveUserIdFromStudentNumber(currentRejectionData.targetId);
                    if (uid) { await sendSignatoryAction(uid, CURRENT_STAFF_POSITION, 'Rejected', additionalRemarks); }
                } catch (e) {}
                
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
                    const currentPeriod = data.active_periods[0];
                    const termMap = {
                        '1st': '1st Semester',
                        '2nd': '2nd Semester',
                        '3rd': '3rd Semester',
                        '1st Semester': '1st Semester',
                        '2nd Semester': '2nd Semester',
                        '3rd Semester': '3rd Semester',
                        'Summer': 'Summer'
                    };
                    const semLabel = termMap[currentPeriod.semester_name] || currentPeriod.semester_name || '';
                    if (yearEl) yearEl.textContent = currentPeriod.school_year;
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
                        remarks: 'Approved by Regular Staff'
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
            try {
                // Open rejection modal
                openRejectionModal(userId, clearanceFormId, signatoryId);
            } catch (error) {
                console.error('Error opening rejection modal:', error);
                showToastNotification('Error opening rejection modal: ' + error.message, 'error');
            }
        }

        function openRejectionModal(userId, clearanceFormId, signatoryId) {
            // Store rejection data for later use
            window.pendingRejection = {
                userId: userId,
                clearanceFormId: clearanceFormId,
                signatoryId: signatoryId
            };

            // Show rejection modal (you may need to implement this modal)
            showToastNotification('Rejection modal functionality needs to be implemented', 'info');
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
    </script>
    <script src="../../assets/js/alerts.js"></script>
</body>
</html>
