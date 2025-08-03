<?php
// Online Clearance Website - Admin Student Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Demo session data for testing
$_SESSION['user_id'] = 3;
$_SESSION['role_id'] = 1; // Admin role
$_SESSION['first_name'] = 'Admin';
$_SESSION['last_name'] = 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top Bar -->
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

    <!-- Main Content Area -->
    <main class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Page Header -->
                <div class="page-header">
                    <h2><i class="fas fa-user-graduate"></i> Student Management</h2>
                    <p>Manage student accounts and monitor clearance status</p>
                </div>

                <!-- Statistics Dashboard - MOVED TO TOP -->
                <div class="stats-dashboard">
                    <div class="stat-card">
                        <div class="stat-icon">
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
                            <h3 id="activeStudents">1,100</h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon inactive">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="inactiveStudents">134</h3>
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

                <!-- Search and Filters Section -->
                <div class="search-filters-section">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search students by name, ID, or program...">
                    </div>
                    
                    <div class="filter-dropdowns">
                        <!-- Department Filter -->
                        <select id="departmentFilter" class="filter-select" onchange="updateFilterPrograms()">
                            <option value="">All Departments</option>
                            <option value="Tourism and Hospitality Management">Tourism and Hospitality Management</option>
                            <option value="Information, Communication, and Technology">Information, Communication, and Technology</option>
                            <option value="Business, Arts, and Science">Business, Arts, and Science</option>
                            <option value="Senior High School">Senior High School</option>
                        </select>
                        
                        <!-- Program Filter (Cascading) -->
                        <select id="programFilter" class="filter-select" onchange="updateFilterYearLevels()" disabled>
                            <option value="">Select Department First</option>
                        </select>
                        
                        <!-- Year Level Filter (Cascading) -->
                        <select id="yearFilter" class="filter-select" disabled>
                            <option value="">Select Program First</option>
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
                            <tbody id="studentsTableBody">
                                <!-- Sample data - will be populated by JavaScript -->
                                <tr data-term="2024-2025-1st">
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288322"></td>
                                    <td>02000288322</td>
                                    <td>Zinzu Chan Lee</td>
                                    <td>BS in Information Technology (BSIT)</td>
                                    <td>3rd Year</td>
                                    <td>3/2-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288322')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288322')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-term="2024-2025-1st">
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288323"></td>
                                    <td>02000288323</td>
                                    <td>Jane Smith</td>
                                    <td>BS in Computer Science (BSCS)</td>
                                    <td>2nd Year</td>
                                    <td>2/1-2</td>
                                    <td><span class="status-badge account-inactive">Inactive</span></td>
                                    <td><span class="status-badge clearance-completed">Completed</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288323')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn inactive" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-off"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288323')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-term="2024-2025-1st">
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288324"></td>
                                    <td>02000288324</td>
                                    <td>John Doe</td>
                                    <td>BS in Information Systems (BSIS)</td>
                                    <td>4th Year</td>
                                    <td>4/1-3</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-rejected">Rejected</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288324')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288324')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288325"></td>
                                    <td>02000288325</td>
                                    <td>Mary Wilson</td>
                                    <td>BS in Information Technology (BSIT)</td>
                                    <td>1st Year</td>
                                    <td>1/1-1</td>
                                    <td><span class="status-badge account-inactive">Inactive</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288325')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn inactive" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-off"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288325')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288326"></td>
                                    <td>02000288326</td>
                                    <td>Tom Brown</td>
                                    <td>BS in Computer Science (BSCS)</td>
                                    <td>3rd Year</td>
                                    <td>3/2-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-completed">Completed</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288326')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288326')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288327"></td>
                                    <td>02000288327</td>
                                    <td>Ana Sofia Reyes</td>
                                    <td>BS in Tourism Management (BSTM)</td>
                                    <td>4th Year</td>
                                    <td>4/2-1</td>
                                    <td><span class="status-badge account-graduated">Graduated</span></td>
                                    <td><span class="status-badge clearance-completed">Completed</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288327')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn graduated" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-graduation-cap"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288327')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288328"></td>
                                    <td>02000288328</td>
                                    <td>Miguel Antonio Lopez</td>
                                    <td>BS in Business Administration (BSBA)</td>
                                    <td>2nd Year</td>
                                    <td>2/2-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-rejected">Rejected</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288328')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288328')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288329"></td>
                                    <td>02000288329</td>
                                    <td>Sofia Isabel Martinez</td>
                                    <td>Accountancy, Business, and Management (ABM)</td>
                                    <td>Grade 12</td>
                                    <td>12/2-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288329')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288329')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288330"></td>
                                    <td>02000288330</td>
                                    <td>Carlos Rodriguez</td>
                                    <td>BS in Computer Engineering (BSCpE)</td>
                                    <td>3rd Year</td>
                                    <td>3/1-3</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288330')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288330')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288331"></td>
                                    <td>02000288331</td>
                                    <td>Isabella Santos</td>
                                    <td>BS in Accounting Information System (BSAIS)</td>
                                    <td>2nd Year</td>
                                    <td>2/1-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288331')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288331')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288332"></td>
                                    <td>02000288332</td>
                                    <td>Diego Martinez</td>
                                    <td>BS in Accountancy (BSA)</td>
                                    <td>4th Year</td>
                                    <td>4/2-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-completed">Completed</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288332')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288332')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288333"></td>
                                    <td>02000288333</td>
                                    <td>Valentina Gonzalez</td>
                                    <td>BA in Communication (BAComm)</td>
                                    <td>1st Year</td>
                                    <td>1/1-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288333')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288333')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288334"></td>
                                    <td>02000288334</td>
                                    <td>Mateo Hernandez</td>
                                    <td>Bachelor of Multimedia Arts (BMMA)</td>
                                    <td>3rd Year</td>
                                    <td>3/1-4</td>
                                    <td><span class="status-badge account-inactive">Inactive</span></td>
                                    <td><span class="status-badge clearance-rejected">Rejected</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288334')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn inactive" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-off"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288334')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288335"></td>
                                    <td>02000288335</td>
                                    <td>Camila Torres</td>
                                    <td>BS in Culinary Management (BSCM)</td>
                                    <td>2nd Year</td>
                                    <td>2/2-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288335')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288335')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288336"></td>
                                    <td>02000288336</td>
                                    <td>Lucas Ramirez</td>
                                    <td>Science, Technology, Engineering and Mathematics (STEM)</td>
                                    <td>Grade 11</td>
                                    <td>11/1-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288336')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288336')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288337"></td>
                                    <td>02000288337</td>
                                    <td>Emma Flores</td>
                                    <td>Humanities and Social Sciences (HUMSS)</td>
                                    <td>Grade 12</td>
                                    <td>12/1-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-completed">Completed</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288337')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288337')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288338"></td>
                                    <td>02000288338</td>
                                    <td>Daniel Cruz</td>
                                    <td>General Academic (GA)</td>
                                    <td>Grade 11</td>
                                    <td>11/2-3</td>
                                    <td><span class="status-badge account-inactive">Inactive</span></td>
                                    <td><span class="status-badge clearance-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288338')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn inactive" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-off"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288338')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288339"></td>
                                    <td>02000288339</td>
                                    <td>Natalia Morales</td>
                                    <td>IT in Mobile App and Web Development (MAWD)</td>
                                    <td>Grade 12</td>
                                    <td>12/2-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288339')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288339')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288340"></td>
                                    <td>02000288340</td>
                                    <td>Adrian Silva</td>
                                    <td>Digital Arts (DA)</td>
                                    <td>Grade 11</td>
                                    <td>11/1-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-rejected">Rejected</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288340')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288340')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                                                                </div>
                                    </td>
                                </tr>
                                <tr data-term="2024-2025-2nd">
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288325"></td>
                                    <td>02000288325</td>
                                    <td>Maria Garcia</td>
                                    <td>BS in Tourism Management (BSTM)</td>
                                    <td>1st Year</td>
                                    <td>1/2-1</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-unapplied">Unapplied</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288325')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288325')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-term="2024-2025-2nd">
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288326"></td>
                                    <td>02000288326</td>
                                    <td>Carlos Rodriguez</td>
                                    <td>BS in Business Administration (BSBA)</td>
                                    <td>3rd Year</td>
                                    <td>3/1-2</td>
                                    <td><span class="status-badge account-active">Active</span></td>
                                    <td><span class="status-badge clearance-in-progress">In Progress</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288326')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn active" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288326')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-term="2023-2024-2nd">
                                    <td><input type="checkbox" class="student-checkbox" data-id="02000288327"></td>
                                    <td>02000288327</td>
                                    <td>Sarah Johnson</td>
                                    <td>BS in Information Technology (BSIT)</td>
                                    <td>4th Year</td>
                                    <td>4/2-1</td>
                                    <td><span class="status-badge account-graduated">Graduated</span></td>
                                    <td><span class="status-badge clearance-completed">Completed</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon edit-btn" onclick="editStudent('02000288327')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon status-toggle-btn graduated" onclick="toggleStudentStatus(this)" title="Toggle Status">
                                                <i class="fas fa-graduation-cap"></i>
                                            </button>
                                            <button class="btn-icon delete-btn" onclick="deleteStudent('02000288327')" title="Delete">
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

                <!-- Pagination -->
                <div class="pagination-section">
                    <div class="pagination-info">
                        <span id="paginationInfo">Showing 1 to 20 of 1,247 entries</span>
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" id="prevPage" onclick="changePage('prev')" disabled>
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <div class="page-numbers" id="pageNumbers">
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <button class="pagination-btn">4</button>
                            <button class="pagination-btn">5</button>
                        </div>
                        <button class="pagination-btn" id="nextPage" onclick="changePage('next')">
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
    </main>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Student Registry Modal -->
    <?php include '../../Modals/StudentRegistryModal.php'; ?>
    
    <!-- Include Edit Student Modal -->
    <?php include '../../Modals/EditStudentModal.php'; ?>
    
    <!-- Include Export Modal -->
    <?php include '../../Modals/ExportModal.php'; ?>
    
    <!-- Include Import Modal -->
    <?php include '../../Modals/ImportModal.php'; ?>

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
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            const bulkButtons = document.querySelectorAll('.bulk-buttons button');
            
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

        function toggleHeaderCheckbox() {
            const headerCheckbox = document.getElementById('headerCheckbox');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = headerCheckbox.checked;
            });
            
            updateBulkButtons();
            updateSelectionCounter();
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

        // Bulk Actions with Confirmation
        function activateSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to activate', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Activate Students',
                `Are you sure you want to activate ${selectedCount} selected students?`,
                'Activate',
                'Cancel',
                () => {
                                         // Perform activation
                     const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                     selectedRows.forEach(checkbox => {
                         const row = checkbox.closest('tr');
                         const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
                         const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.remove('account-inactive');
                        statusBadge.classList.add('account-active');
                        toggleBtn.classList.remove('inactive');
                        toggleBtn.classList.add('active');
                        toggleBtn.querySelector('i').classList.remove('fa-toggle-off');
                        toggleBtn.querySelector('i').classList.add('fa-toggle-on');
                    });
                    
                    // Update statistics
                    updateBulkStatistics('activate', selectedCount);
                    showToastNotification(`✓ Successfully activated ${selectedCount} students`, 'success');
                },
                'info'
            );
        }

        function deactivateSelected() {
            const selectedCount = getSelectedCount();
            if (selectedCount === 0) {
                showToastNotification('Please select students to deactivate', 'warning');
                return;
            }
            
            showConfirmationModal(
                'Deactivate Students',
                `Are you sure you want to deactivate ${selectedCount} selected students?`,
                'Deactivate',
                'Cancel',
                () => {
                                         // Perform deactivation
                     const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                     selectedRows.forEach(checkbox => {
                         const row = checkbox.closest('tr');
                         const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
                         const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        statusBadge.textContent = 'Inactive';
                        statusBadge.classList.remove('account-active');
                        statusBadge.classList.add('account-inactive');
                        toggleBtn.classList.remove('active');
                        toggleBtn.classList.add('inactive');
                        toggleBtn.querySelector('i').classList.remove('fa-toggle-on');
                        toggleBtn.querySelector('i').classList.add('fa-toggle-off');
                    });
                    
                    // Update statistics
                    updateBulkStatistics('deactivate', selectedCount);
                    showToastNotification(`✓ Successfully deactivated ${selectedCount} students`, 'success');
                },
                'warning'
            );
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
                    // Perform deletion
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        row.remove();
                    });
                    
                    // Update statistics
                    updateBulkStatistics('delete', selectedCount);
                    showToastNotification(`✓ Successfully deleted ${selectedCount} students`, 'success');
                },
                'danger'
            );
        }

        // Individual Status Toggle with Confirmation
        function toggleStudentStatus(button) {
            const row = button.closest('tr');
            const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
            
            if (!statusBadge) {
                console.error('Status badge not found');
                showToastNotification('Error: Could not find status badge', 'error');
                return;
            }
            
            const studentName = row.querySelector('td:nth-child(3)').textContent;
            const currentStatus = statusBadge.textContent;
            const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
            
            showConfirmationModal(
                'Change Student Status',
                `Change ${studentName}'s status from ${currentStatus} to ${newStatus}?`,
                'Confirm',
                'Cancel',
                () => {
                    if (currentStatus === 'Active') {
                        // Change to Inactive
                        statusBadge.textContent = 'Inactive';
                        statusBadge.classList.remove('account-active');
                        statusBadge.classList.add('account-inactive');
                        button.classList.remove('active');
                        button.classList.add('inactive');
                        button.querySelector('i').classList.remove('fa-toggle-on');
                        button.querySelector('i').classList.add('fa-toggle-off');
                        
                        // Update statistics
                        updateStatistics('deactivate');
                    } else {
                        // Change to Active
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.remove('account-inactive');
                        statusBadge.classList.add('account-active');
                        button.classList.remove('inactive');
                        button.classList.add('active');
                        button.querySelector('i').classList.remove('fa-toggle-off');
                        button.querySelector('i').classList.add('fa-toggle-on');
                        
                        // Update statistics
                        updateStatistics('activate');
                    }
                    
                    // Show confirmation
                    showToastNotification(`${studentName} is now ${newStatus}`, 'success');
                },
                'warning'
            );
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

        // Select All with Confirmation
        function selectAllStudents() {
            const totalStudents = document.querySelectorAll('.student-checkbox').length;
            
            showConfirmationModal(
                'Select All Students',
                `Are you sure you want to select all ${totalStudents} students?`,
                'Select All',
                'Cancel',
                () => {
                    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                        checkbox.checked = true;
                    });
                    updateBulkButtons();
                    showToastNotification(`✓ All ${totalStudents} students selected`, 'success');
                },
                'info'
            );
        }

        // Undo with Confirmation
        function undoLastAction() {
            showConfirmationModal(
                'Undo Last Action',
                'Are you sure you want to undo the last action?',
                'Undo',
                'Cancel',
                () => {
                    // Implement undo functionality
                    showToastNotification('✓ Last action undone successfully', 'success');
                },
                'warning'
            );
        }

        // Helper function to get selected count
        function getSelectedCount() {
            return document.querySelectorAll('.student-checkbox:checked').length;
        }
        
        // Helper function to update bulk buttons state
        function updateBulkButtons() {
            const selectedCount = getSelectedCount();
            const bulkButtons = document.querySelectorAll('.bulk-buttons .btn');
            const undoButton = document.querySelector('.bulk-buttons .btn:nth-child(1)');
            
            bulkButtons.forEach(button => {
                if (button !== undoButton) {
                    button.disabled = selectedCount === 0;
                }
            });
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
                `Are you sure you want to mark ${selectedCount} selected students as Graduated? This will change their status permanently.`,
                'Mark as Graduated',
                'Cancel',
                () => {
                    // Perform graduation
                    const selectedRows = document.querySelectorAll('.student-checkbox:checked');
                    selectedRows.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive');
                        const toggleBtn = row.querySelector('.status-toggle-btn');
                        
                        if (statusBadge) {
                            statusBadge.textContent = 'Graduated';
                            statusBadge.classList.remove('account-active', 'account-inactive');
                            statusBadge.classList.add('account-graduated');
                            
                            toggleBtn.style.display = 'none'; // Hide toggle for graduated students
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
                // Move from active/inactive to graduated
                currentGraduated += count;
                // We'd need to track which students were active vs inactive
                // For now, we'll assume they were active
                currentActive -= count;
            } else if (action === 'delete') {
                // For delete, we just need to update the total count
                // The specific counts (active, inactive, graduated) might not change
                // unless the user explicitly changes them.
                // For simplicity, we'll just update the total count.
                // If the user wants to remove from specific counts, they'd need to handle that.
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
            // Call the function from ImportModal.php
            if (typeof window.openImportModal === 'function') {
                window.openImportModal();
            } else {
                console.error('Import modal function not found');
            }
        }

        function triggerExportModal() {
            // Call the function from ExportModal.php
            if (typeof window.openExportModal === 'function') {
                window.openExportModal();
            } else {
                console.error('Export modal function not found');
            }
        }

        // Filter data structure (using the same structure as registration modal)
        // Note: These are declared in StudentRegistryModal.php to avoid duplicates

        // Update program dropdown based on department selection
        function updateFilterPrograms() {
            const departmentSelect = document.getElementById('departmentFilter');
            const programSelect = document.getElementById('programFilter');
            const yearSelect = document.getElementById('yearFilter');
            
            const selectedDepartment = departmentSelect.value;
            
            console.log('Department selected:', selectedDepartment);
            console.log('Available departments:', Object.keys(departmentPrograms));
            
            // Reset program and year dropdowns
            programSelect.innerHTML = '<option value="">All Programs</option>';
            yearSelect.innerHTML = '<option value="">All Year Levels</option>';
            
            // Check if a specific department is selected (not "All Departments")
            if (selectedDepartment && selectedDepartment !== '' && departmentPrograms[selectedDepartment]) {
                console.log('Enabling program dropdown for department:', selectedDepartment);
                // Enable program dropdown
                programSelect.disabled = false;
                
                // Add program options
                departmentPrograms[selectedDepartment].forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.textContent = program;
                    programSelect.appendChild(option);
                });
            } else {
                console.log('Disabling program dropdown - no valid department selected');
                // Disable program and year dropdowns when "All Departments" is selected
                programSelect.disabled = true;
                yearSelect.disabled = true;
            }
        }

        // Update year level dropdown based on program selection
        function updateFilterYearLevels() {
            const departmentSelect = document.getElementById('departmentFilter');
            const programSelect = document.getElementById('programFilter');
            const yearSelect = document.getElementById('yearFilter');
            
            const selectedDepartment = departmentSelect.value;
            const selectedProgram = programSelect.value;
            
            console.log('Program selected:', selectedProgram);
            console.log('Available year levels for department:', selectedDepartment, departmentYearLevels[selectedDepartment]);
            
            // Reset year dropdown
            yearSelect.innerHTML = '<option value="">All Year Levels</option>';
            
            // Check if both department and program are selected (not "All")
            if (selectedDepartment && selectedDepartment !== '' && selectedProgram && selectedProgram !== '' && departmentYearLevels[selectedDepartment]) {
                console.log('Enabling year dropdown for program:', selectedProgram);
                // Enable year dropdown
                yearSelect.disabled = false;
                
                // Add year level options
                departmentYearLevels[selectedDepartment].forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearSelect.appendChild(option);
                });
            } else {
                console.log('Disabling year dropdown - no valid program selected');
                // Disable year dropdown when "All" options are selected
                yearSelect.disabled = true;
            }
        }

        // Apply filters to the table
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const department = document.getElementById('departmentFilter').value;
            const program = document.getElementById('programFilter').value;
            const yearLevel = document.getElementById('yearFilter').value;
            const clearanceStatus = document.getElementById('clearanceStatusFilter').value;
            const accountStatus = document.getElementById('accountStatusFilter').value;
            const schoolTerm = document.getElementById('schoolTermFilter').value;
            
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const studentName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const studentProgram = row.querySelector('td:nth-child(4)').textContent;
                const studentYear = row.querySelector('td:nth-child(5)').textContent;
                const clearanceBadge = row.querySelector('.status-badge.clearance-unapplied, .status-badge.clearance-pending, .status-badge.clearance-completed, .status-badge.clearance-rejected, .status-badge.clearance-in-progress');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                
                let shouldShow = true;
                
                // Search filter
                if (searchTerm && !studentName.includes(searchTerm)) {
                    shouldShow = false;
                }
                
                // Program filter
                if (program && studentProgram !== program) {
                    shouldShow = false;
                }
                
                // Year level filter
                if (yearLevel && studentYear !== yearLevel) {
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
            
            // Update pagination with filtered results
            updateFilteredEntries();
            
            // Show results count
            showInfoToast(`Showing ${visibleCount} of ${tableRows.length} students`);
            
            // Update statistics if needed
            updateFilteredStatistics();
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('departmentFilter').value = '';
            document.getElementById('programFilter').value = '';
            document.getElementById('yearFilter').value = '';
            document.getElementById('clearanceStatusFilter').value = '';
            document.getElementById('accountStatusFilter').value = '';
            document.getElementById('schoolTermFilter').value = '';
            
            // Reset dropdowns
            updateFilterPrograms();
            
            // Show all rows
            const tableRows = document.querySelectorAll('#studentsTableBody tr');
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
            const allRows = document.querySelectorAll('#studentsTableBody tr');
            
            let activeCount = 0;
            let inactiveCount = 0;
            let graduatedCount = 0;
            let totalCount = 0;
            
            allRows.forEach(row => {
                const rowTerm = row.getAttribute('data-term');
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                
                // Only count if term matches or if "All School Terms" is selected
                if (!selectedTerm || rowTerm === selectedTerm) {
                    totalCount++;
                    
                    if (accountBadge) {
                        if (accountBadge.classList.contains('account-active')) {
                            activeCount++;
                        } else if (accountBadge.classList.contains('account-inactive')) {
                            inactiveCount++;
                        } else if (accountBadge.classList.contains('account-graduated')) {
                            graduatedCount++;
                        }
                    }
                }
            });
            
            // Update statistics display
            document.getElementById('totalStudents').textContent = totalCount;
            document.getElementById('activeStudents').textContent = activeCount;
            document.getElementById('inactiveStudents').textContent = inactiveCount;
            document.getElementById('graduatedStudents').textContent = graduatedCount;
            
            // Apply filters to update table view
            applyFilters();
        }

        // Update statistics based on filtered results
        function updateFilteredStatistics() {
            const visibleRows = document.querySelectorAll('#studentsTableBody tr:not([style*="display: none"])');
            
            let activeCount = 0;
            let inactiveCount = 0;
            let graduatedCount = 0;
            
            visibleRows.forEach(row => {
                const accountBadge = row.querySelector('.status-badge.account-active, .status-badge.account-inactive, .status-badge.account-graduated');
                if (accountBadge) {
                    if (accountBadge.classList.contains('account-active')) activeCount++;
                    else if (accountBadge.classList.contains('account-inactive')) inactiveCount++;
                    else if (accountBadge.classList.contains('account-graduated')) graduatedCount++;
                }
            });
            
            // Optional: Update statistics display
            // document.getElementById('activeStudents').textContent = activeCount.toLocaleString();
            // document.getElementById('inactiveStudents').textContent = inactiveCount.toLocaleString();
            // document.getElementById('graduatedStudents').textContent = graduatedCount.toLocaleString();
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
            
            // Scroll to top of table when page changes
            const tableWrapper = document.querySelector('.students-table-wrapper');
            if (tableWrapper) {
                tableWrapper.scrollTop = 0;
            }
        }

        // Update filtered entries when filters are applied
        function updateFilteredEntries() {
            const visibleRows = document.querySelectorAll('#studentsTableBody tr:not([style*="display: none"])');
            filteredEntries = Array.from(visibleRows);
            currentPage = 1; // Reset to first page
            updatePagination();
        }

        // Search functionality (real-time)
        document.getElementById('searchInput').addEventListener('input', function() {
            // Real-time search can be implemented here if needed
            console.log('Searching for:', this.value);
        });

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

        // Initialize pagination when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializePagination();
            updateSelectionCounter(); // Initialize the counter
            
            // Add scroll event listener to table wrapper
            const tableWrapper = document.getElementById('studentsTableWrapper');
            if (tableWrapper) {
                tableWrapper.addEventListener('scroll', handleTableScroll);
            }
        });

        // Add event listeners for student checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateBulkButtons();
                updateSelectionCounter();
            }
        });
    </script>
    
    <!-- Include Alert System JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
</body>
</html> 