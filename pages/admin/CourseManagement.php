<?php
// Session management handled by header component
// Temporarily disable session check for interface development
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../../login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
                <!-- LEFT SIDE: Main Content -->
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <div class="course-management-container">
                            <!-- Page Header -->
                            <div class="page-header">
                                <h2 class="page-title"><i class="fa-solid fa-book"></i>  Course Management</h2>
                                <p class="page-description">Manage departments and courses for student registration</p>
                            </div>

                            <!-- Quick Statistics Card (Compact Style) -->
                            <div class="management-card quick-stats-card">
                                <div class="card-header">
                                    <h3><i class="fas fa-chart-bar"></i> Quick Statistics</h3>
                                </div>
                                <div class="card-content">
                                    <div class="compact-stats-grid">
                                        <div class="stat-item">
                                            <i class="fas fa-building"></i>
                                            <span class="stat-value" id="totalDepartments">5</span>
                                            <span class="stat-label">Departments</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-book"></i>
                                            <span class="stat-value" id="totalCourses">15</span>
                                            <span class="stat-label">Courses</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-users"></i>
                                            <span class="stat-value" id="totalStudents">0</span>
                                            <span class="stat-label">Students</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span class="stat-value" id="activeDepartments">5</span>
                                            <span class="stat-label">Active</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Bar Card (Compact Style) -->
                            <div class="management-card action-bar-card">
                                <div class="card-header">
                                    <h3><i class="fas fa-search"></i> Search & Actions</h3>
                                </div>
                                <div class="card-content">
                                    <div class="compact-search-actions">
                                        <div class="search-section">
                                            <div class="search-wrapper">
                                                <i class="fas fa-search search-icon"></i>
                                                <input type="text" class="search-input" placeholder="Search departments..." id="searchInput">
                                            </div>
                                        </div>
                                        <div class="actions-section">
                                            <div class="left-actions">
                                            <button class="btn btn-outline btn-compact" onclick="openImportModal()">
                                                <i class="fas fa-download"></i>
                                                <span>Import</span>
                                            </button>
                                            <button class="btn btn-outline btn-compact" onclick="openExportModal()">
                                                <i class="fas fa-upload"></i>
                                                <span>Export</span>
                                            </button>
                                            </div>
                                            <div class="right-actions">
                                                <button class="btn btn-primary btn-compact" onclick="openAddDepartmentModal()">
                                                    <i class="fas fa-plus"></i>
                                                    <span>Add Department</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Navigation Section (Fixed at Top) -->
                            <div class="management-card tab-navigation-card">
                                <div class="card-header">
                                    <h3><i class="fas fa-folder"></i> Department Categories</h3>
                                </div>
                                    <div class="compact-tab-navigation">
                                        <button class="compact-tab-button active" onclick="switchTab('college')">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>College</span>
                                        </button>
                                        <button class="compact-tab-button" onclick="switchTab('senior-high')">
                                            <i class="fas fa-school"></i>
                                            <span>Senior High School</span>
                                        </button>
                                    <button class="compact-tab-button" onclick="switchTab('faculty')">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Faculty</span>
                                    </button>
                                </div>
                                    </div>
                                    
                            <!-- Department Cards Container (Scrollable) -->
                            <div class="management-card departments-scrollable-card">
                                <div class="card-content">
                                    <!-- College Departments -->
                                    <div class="departments-container active" id="college-departments">
                                <!-- ICT Department -->
                                <div class="department-card" data-department="ICT">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-laptop-code"></i></span>
                                        <span class="department-id">ICT</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">Information & Communication Technology</h4>
                                        <p class="department-courses">3 Courses</p>
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('ICT')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('ICT')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('College','Information & Communication Technology','ICT')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('ICT')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Information Technology
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Computer Science
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Computer Engineering
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Business & Management Department -->
                                <div class="department-card" data-department="BSA">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-briefcase"></i></span>
                                        <span class="department-id">BSA</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">Business & Management, Arts, and Sciences</h4>
                                        <p class="department-courses">5 Courses</p>
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('BSA')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('BSA')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('College','Business, Arts, & Science','BSA')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('BSA')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Business Administration
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Accountancy
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Accounting Information System
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> Bachelor of Multimedia Arts
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BA in Communication
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tourism and Hospitality Management Department -->
                                <div class="department-card" data-department="THM">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-hotel"></i></span>
                                        <span class="department-id">THM</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">Tourism and Hospitality Management</h4>
                                        <p class="department-courses">3 Courses</p>
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('THM')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('THM')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('College','Tourism & Hospitality Management','THM')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('THM')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Hospitality Management
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Culinary Management
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> BS in Tourism Management
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Senior High School Departments -->
                            <div class="departments-container" id="senior-high-departments">
                                <!-- Academic Track Department -->
                                <div class="department-card" data-department="ACADEMIC">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-graduation-cap"></i></span>
                                        <span class="department-id">ACADEMIC</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">Academic Track</h4>
                                        <p class="department-courses">4 Courses</p>
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('ACADEMIC')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('ACADEMIC')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('Senior High School','Academic Track','ACADEMIC')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('ACADEMIC')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> ABM (Accountancy, Business, Management)
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> STEM (Science, Technology, Engineering, Mathematics)
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> HUMSS (Humanities and Social Sciences)
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> GA (General Academic)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Technical-Vocational Livelihood Track Department -->
                                <div class="department-card" data-department="TVL">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-tools"></i></span>
                                        <span class="department-id">TVL</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">Technical-Vocational Livelihood Track</h4>
                                        <p class="department-courses">2 Courses</p>
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('TVL')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('TVL')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('Senior High School','Technological-Vocational Livelihood','TVL')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('TVL')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> Digital Arts
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> IT in Mobile app and Web development (MAWD)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Home Economics Department -->
                                <div class="department-card" data-department="HOME_ECON">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-home"></i></span>
                                        <span class="department-id">HOME_ECON</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">Home Economics</h4>
                                        <p class="department-courses">3 Courses</p>
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('HOME_ECON')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('HOME_ECON')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('Senior High School','Home Economics','HOME_ECON')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('HOME_ECON')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> Tourism Operations
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> Restaurant and Cafe Operations
                                                </div>
                                                <div class="course-preview-item">
                                                    <i class="fas fa-graduation-cap"></i> Culinary Arts
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Faculty Departments -->
                            <div class="departments-container" id="faculty-departments">
                                <!-- General Education Department -->
                                <div class="department-card" data-department="GENERAL_EDUCATION">
                                    <div class="department-card-header">
                                        <span class="department-icon"><i class="fas fa-graduation-cap"></i></span>
                                        <span class="department-id">GENERAL_EDUCATION</span>
                                    </div>
                                    <div class="department-card-body">
                                        <h4 class="department-name">General Education</h4>
                                    <!--    <p class="department-courses">0 Courses</p> -->
                                        <p class="department-status">Active</p>
                                        <div class="department-content-spacer"></div>
                                    </div>
                                    <div class="department-card-actions">
                                    <!--    <button class="btn btn-sm btn-outline-primary" onclick="openAddCourseModal('GENERAL_EDUCATION')">
                                            <i class="fas fa-plus"></i> Add Course
                                        </button> -->
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditDepartmentModal('GENERAL_EDUCATION')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openAssignPHModal('Faculty','General Education','GENERAL_EDUCATION')">
                                            <i class="fas fa-user-tie"></i> Assign/Change Program Head
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment('GENERAL_EDUCATION')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <!-- Course Preview Section -->
                                <!--    <div class="course-preview">
                                        <div class="course-preview-content">
                                            <div class="course-preview-title">
                                                <i class="fas fa-book"></i> Courses
                                            </div>
                                            <div class="course-preview-list">
                                                <!- No courses initially ->
                                                </div>
                                            </div>
                                        </div> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- Close departments-scrollable-card -->
            </div> <!-- Close content-wrapper -->
             <!-- RIGHT SIDE: Activity Tracker -->
             <div class="dashboard-sidebar">
                        <?php include '../../includes/components/activity-tracker.php'; ?>
                </div>
        </div> <!-- Close dashboard-main or dashboard-layout rather -->

       

                <!-- Include Modal Files -->
                <?php include '../../Modals/AddDepartmentModal.php'; ?>
                <?php include '../../Modals/EditDepartmentModal.php'; ?>
                <?php include '../../Modals/AddCourseModal.php'; ?>
                <?php include '../../Modals/EditCourseModal.php'; ?>
                <?php include '../../Modals/CourseImportModal.php'; ?>
                <?php include '../../Modals/CourseExportModal.php'; ?>
                <?php include '../../Modals/AssignProgramHeadModal.php'; ?>
                
                <!-- Add Modal Function Wrappers -->
                <script>
                    // Modal function wrappers - silent opening for better UX
                    function openAddDepartmentModal() {
                        if (typeof window.openAddDepartmentModalInternal === 'function') {
                            window.openAddDepartmentModalInternal();
                        } else {
                            document.getElementById('addDepartmentModal').style.display = 'flex';
                        }
                    }
                    
                    function openEditDepartmentModal(departmentId) {
                        if (typeof window.openEditDepartmentModalInternal === 'function') {
                            window.openEditDepartmentModalInternal(departmentId);
                        } else {
                            // Fallback to direct modal opening
                            document.getElementById('editDepartmentModal').style.display = 'flex';
                        }
                    }
                    
                    function openAddCourseModal(departmentId) {
                        if (typeof window.openAddCourseModalInternal === 'function') {
                            window.openAddCourseModalInternal(departmentId);
                        } else {
                            // Fallback to direct modal opening
                            document.getElementById('addCourseModal').style.display = 'flex';
                        }
                    }
                    
                    function openEditCourseModal(courseCode) {
                        if (typeof window.openEditCourseModalInternal === 'function') {
                            window.openEditCourseModalInternal(courseCode);
                        } else {
                            // Fallback to direct modal opening
                            document.getElementById('editCourseModal').style.display = 'flex';
                        }
                    }
                    
                    function openImportModal() {
                        if (typeof window.openImportModalInternal === 'function') {
                            window.openImportModalInternal();
                        } else {
                            // Fallback to direct modal opening
                            document.getElementById('courseImportModal').style.display = 'flex';
                        }
                    }
                    
                    function openExportModal() {
                        if (typeof window.openExportModalInternal === 'function') {
                            window.openExportModalInternal();
                        } else {
                            // Fallback to direct modal opening
                            document.getElementById('courseExportModal').style.display = 'flex';
                        }
                    }
                </script>
            </main>
            </div> <!-- Close dashboard-layout -->

            <script src="../../assets/js/alerts.js"></script>
            <script src="../../assets/js/activity-tracker.js"></script>
            
            <!-- Include Audit Functions -->
            <?php include '../../includes/functions/audit_functions.php'; ?>
            
            <!-- Include Alerts Component -->
            <?php include '../../includes/components/alerts.php'; ?>
            
            <!-- Alerts system initialization -->
            <script>
                // Initialize alerts system
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('Alerts system initialized successfully');
                    
                    // Initialize Activity Tracker (only if not already initialized)
                    if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                        window.activityTrackerInstance = new ActivityTracker();
                        console.log('Activity Tracker initialized');
                    }
                });
            </script>
            
            <script>
                // Sample data for demonstration
                const departmentsData = {
                    college: [
                        {
                            id: 'ICT',
                            name: 'INFORMATION & COMMUNICATION TECHNOLOGY (ICT)',
                            courses: ['BSIT', 'BSCS', 'BSCpE'],
                            students: 0,
                            status: 'Active'
                        },
                        {
                            id: 'BSA',
                            name: 'BUSINESS & MANAGEMENT, Arts, and Sciences (BSA)',
                            courses: ['BSBA', 'BSA', 'BSAIS', 'BMMA', 'BAC'],
                            students: 0,
                            status: 'Active'
                        },
                        {
                            id: 'THM',
                            name: 'Tourism and Hospitality Management (THM)',
                            courses: ['BSHM', 'BSCM', 'BSTM'],
                            students: 0,
                            status: 'Active'
                        }
                    ],
                    seniorHigh: [
                        {
                            id: 'ACADEMIC',
                            name: 'ACADEMIC TRACK',
                            courses: ['ABM', 'STEM', 'HUMSS', 'GA'],
                            students: 0,
                            status: 'Active'
                        },
                        {
                            id: 'TVL',
                            name: 'TECHNICAL-VOCATIONAL LIVELIHOOD TRACK',
                            courses: ['DIGITAL_ARTS', 'MAWD'],
                            students: 0,
                            status: 'Active'
                        },
                        {
                            id: 'HOME_ECON',
                            name: 'HOME ECONOMICS',
                            courses: ['TOURISM_OPS', 'RESTAURANT_OPS', 'CULINARY_ARTS'],
                            students: 0,
                            status: 'Active'
                        }
                    ],
                    faculty: [
                        {
                            id: 'GENERAL_EDUCATION',
                            name: 'General Education',
                            courses: [], // Empty array - no courses initially
                            students: 0,
                            status: 'Active'
                        }
                    ]
                };

                // Tab switching functionality
                function switchTab(tabName) {
                    // Update tab buttons
                    const tabButtons = document.querySelectorAll('.compact-tab-button');
                    tabButtons.forEach(button => {
                        button.classList.remove('active');
                    });
                    event.target.classList.add('active');

                    // Update content containers
                    const containers = document.querySelectorAll('.departments-container');
                    containers.forEach(container => {
                        container.classList.remove('active');
                    });

                    if (tabName === 'college') {
                        document.getElementById('college-departments').classList.add('active');
                    } else if (tabName === 'senior-high') {
                        document.getElementById('senior-high-departments').classList.add('active');
                    } else if (tabName === 'faculty') {
                        document.getElementById('faculty-departments').classList.add('active');
                    }

                    // Update statistics
                    updateStatistics(tabName);
                }

                // Update statistics based on active tab
                function updateStatistics(tabName) {
                    try {
                        let data;
                        if (tabName === 'college') {
                            data = departmentsData.college;
                        } else if (tabName === 'senior-high') {
                            data = departmentsData.seniorHigh;
                        } else if (tabName === 'faculty') {
                            data = departmentsData.faculty;
                        } else {
                            data = departmentsData.college; // Default fallback
                        }
                        
                    const totalDepartments = data.length;
                    const totalCourses = data.reduce((sum, dept) => sum + dept.courses.length, 0);
                    const totalStudents = data.reduce((sum, dept) => sum + dept.students, 0);
                    const activeDepartments = data.filter(dept => dept.status === 'Active').length;

                    document.getElementById('totalDepartments').textContent = totalDepartments;
                    document.getElementById('totalCourses').textContent = totalCourses;
                    document.getElementById('totalStudents').textContent = totalStudents;
                    document.getElementById('activeDepartments').textContent = activeDepartments;
                    } catch (error) {
                        console.error('Error updating statistics:', error);
                    }
                }

                // Search functionality
                document.getElementById('searchInput').addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const activeContainer = document.querySelector('.departments-container.active');
                    const departmentCards = activeContainer.querySelectorAll('.department-card');
                    
                    let visibleCount = 0;
                    let totalCount = departmentCards.length;

                    departmentCards.forEach(card => {
                        const departmentName = card.querySelector('.department-name').textContent.toLowerCase();
                        const courseItems = card.querySelectorAll('.course-item');

                        let hasMatch = departmentName.includes(searchTerm);
                        
                        // Check course matches
                        courseItems.forEach(course => {
                            const courseCode = course.querySelector('.course-code').textContent.toLowerCase();
                            const courseName = course.querySelector('.course-name').textContent.toLowerCase();
                            const courseMatch = courseCode.includes(searchTerm) || courseName.includes(searchTerm);
                            
                            course.style.display = courseMatch ? 'flex' : 'none';
                            if (courseMatch) hasMatch = true;
                        });

                        card.style.display = hasMatch ? 'block' : 'none';
                        if (hasMatch) visibleCount++;
                    });
                });

                // Delete functions with confirmation
                function deleteCourse(courseCode) {
                    showConfirmationModal(
                        `Are you sure you want to delete the course "${courseCode}"?`,
                        'This action cannot be undone.',
                        () => {
                            // Show loading notification
                            showToastNotification('Deleting course...', 'info', 1500);
                            
                            // Simulate API call
                            setTimeout(() => {
                                showToastNotification(`Course ${courseCode} deleted successfully`, 'success', 3000);
                            }, 1000);
                        },
                        'Delete Course'
                    );
                }

                function deleteDepartment(departmentId) {
                    showConfirmationModal(
                        `Are you sure you want to delete the department "${departmentId}"?`,
                        'This will also delete all courses under this department. This action cannot be undone.',
                        'Delete Department',
                        'Cancel',
                        () => {
                            // Show loading notification
                            showToastNotification('Deleting department and all associated courses...', 'info', 2000);
                            
                            // Simulate API call
                            setTimeout(() => {
                                showToastNotification(`Department ${departmentId} and all courses deleted successfully`, 'success', 3000);
                            }, 1500);
                        },
                        'danger'
                    );
                }

                // Hybrid Course Preview Functionality
                document.addEventListener('DOMContentLoaded', function() {
                    try {
                        // Initialize statistics
                    updateStatistics('college');
                        
                        // Initialize course preview
                    initializeCoursePreview();
                        
                    } catch (error) {
                        console.error('Error initializing Course Management page:', error);
                    }
                });
                


                // Initialize course preview functionality
                function initializeCoursePreview() {
                    try {
                    const departmentCards = document.querySelectorAll('.department-card');
                        
                        if (departmentCards.length === 0) {
                            return;
                        }
                    
                    departmentCards.forEach(card => {
                        // Desktop: Hover functionality
                        if (window.matchMedia('(hover: hover)').matches) {
                            card.addEventListener('mouseenter', function() {
                                const preview = this.querySelector('.course-preview');
                                if (preview) {
                                    preview.classList.add('expanded');
                                }
                            });
                            
                            card.addEventListener('mouseleave', function() {
                                const preview = this.querySelector('.course-preview');
                                if (preview) {
                                    preview.classList.remove('expanded');
                                }
                            });
                        }
                        
                        // Mobile: Tap functionality
                        if (window.matchMedia('(hover: none)').matches) {
                            card.addEventListener('click', function(e) {
                                // Don't trigger if clicking on buttons
                                if (e.target.closest('.department-card-actions')) {
                                    return;
                                }
                                
                                const preview = this.querySelector('.course-preview');
                                if (preview) {
                                    // Close other expanded cards
                                    document.querySelectorAll('.course-preview.expanded').forEach(p => {
                                        if (p !== preview) {
                                            p.classList.remove('expanded');
                                        }
                                    });
                                    
                                    // Toggle current card
                                    preview.classList.toggle('expanded');
                                    this.classList.toggle('tapped');
                                }
                            });
                        }
                    });
                    
                    // Close previews when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.department-card')) {
                                const expandedPreviews = document.querySelectorAll('.course-preview.expanded');
                                if (expandedPreviews.length > 0) {
                                    expandedPreviews.forEach(preview => {
                                preview.classList.remove('expanded');
                            });
                            document.querySelectorAll('.department-card.tapped').forEach(card => {
                                card.classList.remove('tapped');
                            });
                                }
                            }
                        });
                    } catch (error) {
                        console.error('Error initializing course preview:', error);
                    }
                }

                // Initialize sidebar state on page load
                document.addEventListener('DOMContentLoaded', function() {
                    // Mark this page as having initialized sidebar functionality
                    window.sidebarInitialized = true;
                    
                    // Check if user has a saved preference (desktop only)
                    const sidebarState = localStorage.getItem('sidebarCollapsed');
                    const sidebar = document.getElementById('sidebar');
                    const mainContent = document.querySelector('.main-content');
                    
                    // Ensure sidebar starts in correct state on desktop
                    if (window.innerWidth > 768 && sidebar) {
                        // Remove any existing collapsed class first
                        sidebar.classList.remove('collapsed');
                        if (mainContent) {
                            mainContent.classList.remove('full-width');
                        }
                    }
                    
                    // On desktop: start expanded by default, unless user has explicitly collapsed it
                    if (window.innerWidth > 768 && sidebar) {
                        // Force expanded state on page load (user can collapse if they want)
                        sidebar.classList.remove('collapsed');
                        if (mainContent) {
                            mainContent.classList.remove('full-width');
                        }
                        
                        // Only apply saved collapsed state if user has explicitly set it
                        if (sidebarState === 'true') {
                            sidebar.classList.add('collapsed');
                            if (mainContent) {
                                mainContent.classList.add('full-width');
                            }
                        }
                    }
                    
                    // Ensure sidebar backdrop functionality for mobile
                    const backdrop = document.getElementById('sidebar-backdrop');
                    if (backdrop) {
                        backdrop.addEventListener('click', function() {
                            const sidebar = document.querySelector('.sidebar');
                            if (sidebar) {
                                sidebar.classList.remove('active');
                                this.style.display = 'none';
                            }
                        });
                    }
                    
                    // Close sidebar on window resize
                    window.addEventListener('resize', function() {
                        const sidebar = document.querySelector('.sidebar');
                        const backdrop = document.getElementById('sidebar-backdrop');
                        
                        if (window.innerWidth > 768) {
                            if (sidebar) sidebar.classList.remove('active');
                            if (backdrop) backdrop.style.display = 'none';
                        }
                    });
                });

                // Save sidebar state when toggled (desktop only)
                function saveSidebarState() {
                    // Only save state on desktop
                    if (window.innerWidth > 768) {
                        const sidebar = document.getElementById('sidebar');
                        const isCollapsed = sidebar.classList.contains('collapsed');
                        localStorage.setItem('sidebarCollapsed', isCollapsed);
                    }
                }
                
                // Reset sidebar state to default (expanded)
                function resetSidebarState() {
                    if (window.innerWidth > 768) {
                        localStorage.removeItem('sidebarCollapsed');
                        const sidebar = document.getElementById('sidebar');
                        const mainContent = document.querySelector('.main-content');
                        
                        if (sidebar) {
                            sidebar.classList.remove('collapsed');
                        }
                        if (mainContent) {
                            mainContent.classList.remove('full-width');
                        }
                    }
                }
                
                // Debug function to check sidebar state
                function debugSidebarState() {
                    const sidebar = document.getElementById('sidebar');
                    const mainContent = document.querySelector('.main-content');
                    const sidebarState = localStorage.getItem('sidebarCollapsed');
                    
                    console.log('Sidebar Debug Info:');
                    console.log('- Window width:', window.innerWidth);
                    console.log('- Sidebar element:', sidebar);
                    console.log('- Sidebar classes:', sidebar ? sidebar.className : 'N/A');
                    console.log('- Main content classes:', mainContent ? mainContent.className : 'N/A');
                    console.log('- localStorage sidebarCollapsed:', sidebarState);
                    console.log('- Is mobile:', window.innerWidth <= 768);
                }

                // Enhanced toggle function with state saving
                function toggleSidebar() {
                    const sidebar = document.getElementById('sidebar');
                    const backdrop = document.getElementById('sidebar-backdrop');
                    const mainContent = document.querySelector('.main-content');
                    
                    // Check if we're on mobile (screen width <= 768px)
                    const isMobile = window.innerWidth <= 768;
                    
                    if (isMobile) {
                        // Mobile behavior: toggle active class for overlay
                        if (sidebar) {
                            sidebar.classList.toggle('active');
                            
                            // Show/hide backdrop
                            if (backdrop) {
                                if (sidebar.classList.contains('active')) {
                                    backdrop.style.display = 'block';
                                } else {
                                    backdrop.style.display = 'none';
                                }
                            }
                        }
                    } else {
                        // Desktop behavior: toggle collapsed class
                        if (sidebar.classList.contains('collapsed')) {
                            // Expand sidebar
                            sidebar.classList.remove('collapsed');
                            if (backdrop) {
                                backdrop.style.display = 'none';
                            }
                            if (mainContent) {
                                mainContent.classList.remove('full-width');
                            }
                        } else {
                            // Collapse sidebar
                            sidebar.classList.add('collapsed');
                            if (backdrop) {
                                backdrop.style.display = 'none';
                            }
                            if (mainContent) {
                                mainContent.classList.add('full-width');
                            }
                        }
                        
                        // Save the state only for desktop
                        saveSidebarState();
                    }
                }

                // Keyboard shortcut for sidebar toggle (Ctrl/Cmd + B) - desktop only
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'b' && window.innerWidth > 768) {
                        e.preventDefault();
                        toggleSidebar();
                    }
                });
            </script>
        </body>
        </html> 