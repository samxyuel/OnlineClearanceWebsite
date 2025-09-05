<?php
// Online Clearance Website - School Administrator Dashboard
// Session management handled by header component
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Administrator Dashboard - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
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
                <!-- LEFT SIDE: Main Content -->
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-tachometer-alt"></i> School Administrator Dashboard</h2>
                            <p>Welcome back, Dr. Robert Johnson. Monitor school-wide clearance status and manage student and faculty records.</p>
                            <div class="department-scope-info">
                                <i class="fas fa-shield-alt"></i>
                                <span>Scope: All Departments (School-wide Access)</span>
                            </div>
                        </div>

                <!-- Current Term Status (Read-only for School Administrator) -->
                <div class="card active-period-status">
                    <div class="status-content">
                        <div class="status-info">
                            <h3><i class="fas fa-calendar-check"></i> <span id="currentPeriodDisplay">Loading current period...</span></h3>
                            <p>Duration: 45 days | Started: Jan 15, 2024</p>
                            <div class="period-stats">
                                <span class="stat-item">
                                    <i class="fas fa-user-graduate"></i> Students: 1,234 applied, 1,156 completed (94%)
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-chalkboard-teacher"></i> Faculty: 89 applied, 76 completed (85%)
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-clock"></i> Pending Signatures: 78 total
                                </span>
                            </div>
                        </div>
                        <div class="status-actions">
                            <button class="btn btn-success" onclick="viewPendingClearances()">
                                <i class="fas fa-signature"></i> Sign Clearances
                            </button>
                            <button class="btn btn-info" onclick="exportSchoolReport()">
                                <i class="fas fa-file-export"></i> Export Report
                            </button>
                            <a href="StudentManagement.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Manage Students
                            </a>
                        </div>
                    </div>
                </div>

                <!-- School Statistics Dashboard -->
                <div class="stats-dashboard">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3>1,234</h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>1,156</h3>
                            <p>Student Clearances</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>78</h3>
                            <p>Pending Signatures</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3>89</h3>
                            <p>Total Faculty</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="management-section">
                    <div class="section-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="quick-actions-grid">
                        <a href="StudentManagement.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="action-content">
                                <h4>Manage Students</h4>
                                <p>Edit student records and sign clearances</p>
                            </div>
                        </a>
                        <a href="FacultyManagement.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="action-content">
                                <h4>Manage Faculty</h4>
                                <p>Edit faculty records and sign clearances</p>
                            </div>
                        </a>
                        <a href="#" class="action-card" onclick="viewPendingClearances()">
                            <div class="action-icon">
                                <i class="fas fa-signature"></i>
                            </div>
                            <div class="action-content">
                                <h4>Sign Clearances</h4>
                                <p>Approve or reject pending clearances</p>
                            </div>
                        </a>
                        <a href="#" class="action-card" onclick="exportSchoolReport()">
                            <div class="action-icon">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <div class="action-content">
                                <h4>Export Reports</h4>
                                <p>Generate school-wide clearance reports</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="management-section">
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    </div>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Student Clearance Approved</h4>
                                <p>Zinzu Chan Lee's clearance was approved</p>
                                <span class="activity-time">5 minutes ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Faculty Clearance Rejected</h4>
                                <p>Dr. Ana Rodriguez's clearance was rejected - missing requirements</p>
                                <span class="activity-time">15 minutes ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Student Record Updated</h4>
                                <p>Carlos Rodriguez's information was modified</p>
                                <span class="activity-time">1 hour ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <div class="activity-content">
                                <h4>School Report Exported</h4>
                                <p>Monthly school-wide clearance report was generated</p>
                                <span class="activity-time">2 hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- School Overview -->
                <div class="management-section">
                    <div class="section-header">
                        <h3><i class="fas fa-building"></i> School Overview</h3>
                    </div>
                    <div class="department-overview">
                        <div class="overview-card">
                            <h4><i class="fas fa-graduation-cap"></i> Departments</h4>
                            <div class="program-stats">
                                <div class="program-item">
                                    <span class="program-name">Faculty Department</span>
                                    <span class="program-count">456 students</span>
                                </div>
                                <div class="program-item">
                                    <span class="program-name">Business Department</span>
                                    <span class="program-count">342 students</span>
                                </div>
                                <div class="program-item">
                                    <span class="program-name">Engineering Department</span>
                                    <span class="program-count">298 students</span>
                                </div>
                                <div class="program-item">
                                    <span class="program-name">Education Department</span>
                                    <span class="program-count">138 students</span>
                                </div>
                            </div>
                        </div>
                        <div class="overview-card">
                            <h4><i class="fas fa-clipboard-check"></i> Clearance Status</h4>
                            <div class="clearance-stats">
                                <div class="status-item completed">
                                    <span class="status-label">Completed</span>
                                    <span class="status-count">1,156</span>
                                </div>
                                <div class="status-item pending">
                                    <span class="status-label">Pending</span>
                                    <span class="status-count">78</span>
                                </div>
                                <div class="status-item rejected">
                                    <span class="status-label">Rejected</span>
                                    <span class="status-count">45</span>
                                </div>
                                <div class="status-item in-progress">
                                    <span class="status-label">In Progress</span>
                                    <span class="status-count">23</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGHT SIDE: Activity Tracker -->
        <div class="dashboard-sidebar">
            <?php include '../../includes/components/activity-tracker.php'; ?>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/activity-tracker.js"></script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
    
    <script>
        // Load current period information
        async function loadCurrentPeriod() {
            try {
                const response = await fetch('../../api/clearance/periods.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.active_period) {
                    const period = data.active_period;
                    const currentPeriodDisplay = document.getElementById('currentPeriodDisplay');
                    
                    if (currentPeriodDisplay) {
                        const termMap = { '1st': 'Term 1', '2nd': 'Term 2', '3rd': 'Term 3' };
                        const semLabel = termMap[period.semester_name] || period.semester_name || '';
                        currentPeriodDisplay.textContent = `${period.school_year} ${semLabel} (ACTIVE)`;
                    }
                } else {
                    const currentPeriodDisplay = document.getElementById('currentPeriodDisplay');
                    if (currentPeriodDisplay) {
                        currentPeriodDisplay.textContent = 'No active clearance period';
                    }
                }
            } catch (error) {
                console.error('Error loading current period:', error);
                const currentPeriodDisplay = document.getElementById('currentPeriodDisplay');
                if (currentPeriodDisplay) {
                    currentPeriodDisplay.textContent = 'Unable to load period';
                }
            }
        }

        function viewPendingClearances() {
            showToast('Opening pending clearances...', 'info');
            setTimeout(() => {
                window.location.href = 'StudentManagement.php';
            }, 1000);
        }

        function exportSchoolReport() {
            showConfirmationModal(
                'Export School Report',
                'Generate a comprehensive school-wide clearance report?',
                'Export',
                'Cancel',
                () => {
                    showToast('Report generation started...', 'info');
                    setTimeout(() => {
                        showToast('School report exported successfully!', 'success');
                    }, 2000);
                },
                'info'
            );
        }

        function viewSchoolStats() {
            showToast('Opening school statistics...', 'info');
            // Could redirect to a detailed stats page
            setTimeout(() => {
                showToast('School statistics loaded', 'success');
            }, 1500);
        }

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('School Administrator Dashboard loaded');
            
            // Load dynamic content
            loadCurrentPeriod();
            
            // Initialize Activity Tracker with singleton pattern
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for School Administrator Dashboard');
            }
        });
    </script>
</body>
</html>
