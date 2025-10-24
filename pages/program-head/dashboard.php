<?php
// Online Clearance Website - Program Head Dashboard
// Session management handled by header component
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Head Dashboard - Online Clearance System</title>
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
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-tachometer-alt"></i> Program Head Dashboard</h2>
                            <p id="welcomeMessage">Welcome back! Monitor your department's clearance status and manage records.</p>
                            <div class="department-scope-info">
                                <i class="fas fa-shield-alt"></i>
                                <span id="departmentScope">Loading department information...</span>
                            </div>
                        </div>

                        <!-- Current Term Status (Read-only for Program Head) -->
                        <div class="card active-period-status">
                            <div class="status-content">
                                <div class="status-header">
                                    <h3><i class="fas fa-calendar-check"></i> <span id="currentPeriodDisplay">Loading current period...</span></h3>
                                    <p id="periodDuration">Loading period information...</p>
                                </div>
                                
                                <div class="status-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-user-graduate"></i>
                                        <span id="studentStats">Loading student statistics...</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        <span id="pendingStats">Loading pending signatures...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Department Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>456</h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>28</h3>
                                    <p>Total Faculty</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>142</h3>
                                    <p>Completed Clearances</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>14</h3>
                                    <p>Pending Signatures</p>
                                </div>
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
                                        <h4>Clearance Approved</h4>
                                        <p>Zinzu Chan Lee's clearance was approved</p>
                                        <span class="activity-time">5 minutes ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Clearance Rejected</h4>
                                        <p>John Doe's clearance was rejected - missing requirements</p>
                                        <span class="activity-time">15 minutes ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>New Student Registered</h4>
                                        <p>Carlos Rodriguez registered for clearance</p>
                                        <span class="activity-time">1 hour ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-file-export"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Report Exported</h4>
                                        <p>Weekly clearance report was generated</p>
                                        <span class="activity-time">2 hours ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Department Overview -->
                        <div class="management-section">
                            <div class="section-header">
                                <h3><i class="fas fa-building"></i> <span id="departmentOverviewTitle">Department Overview</span></h3>
                            </div>
                            <div class="department-overview">
                                <div class="overview-card">
                                    <h4><i class="fas fa-graduation-cap"></i> Programs</h4>
                                    <div class="program-stats">
                                        <div class="program-item">
                                            <span class="program-name">BSIT</span>
                                            <span class="program-count">156 students</span>
                                        </div>
                                        <div class="program-item">
                                            <span class="program-name">BSCS</span>
                                            <span class="program-count">142 students</span>
                                        </div>
                                        <div class="program-item">
                                            <span class="program-name">BSIS</span>
                                            <span class="program-count">98 students</span>
                                        </div>
                                        <div class="program-item">
                                            <span class="program-name">BSCpE</span>
                                            <span class="program-count">60 students</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="overview-card">
                                    <h4><i class="fas fa-clipboard-check"></i> Clearance Status</h4>
                                    <div class="clearance-stats">
                                        <div class="status-item completed">
                                            <span class="status-label">Completed</span>
                                            <span class="status-count">142</span>
                                        </div>
                                        <div class="status-item pending">
                                            <span class="status-label">Pending</span>
                                            <span class="status-count">14</span>
                                        </div>
                                        <div class="status-item rejected">
                                            <span class="status-label">Rejected</span>
                                            <span class="status-count">8</span>
                                        </div>
                                        <div class="status-item in-progress">
                                            <span class="status-label">In Progress</span>
                                            <span class="status-count">12</span>
                                        </div>
                                    </div>
                                </div>
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

    <!-- Scripts -->
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/activity-tracker.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <script>
        // Load user and department information
        async function loadUserInfo() {
            try {
                const response = await fetch('../../api/users/read.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.user) {
                    const user = data.user;
                    const welcomeMessage = document.getElementById('welcomeMessage');
                    if (welcomeMessage) {
                        welcomeMessage.textContent = `Welcome back, ${user.first_name} ${user.last_name}! Monitor your department's clearance status and manage records.`;
                    }
                }
            } catch (error) {
                console.error('Error loading user info:', error);
            }
        }
        
        // Load department information
        async function loadDepartmentInfo() {
            try {
                const response = await fetch('../../api/users/get_current_staff_designation.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.designation_name) {
                    const departmentScope = document.getElementById('departmentScope');
                    const departmentOverviewTitle = document.getElementById('departmentOverviewTitle');
                    
                    if (departmentScope) {
                        departmentScope.textContent = `Scope: ${data.designation_name}`;
                    }
                    if (departmentOverviewTitle) {
                        departmentOverviewTitle.textContent = `${data.designation_name} Overview`;
                    }
                }
            } catch (error) {
                console.error('Error loading department info:', error);
                const departmentScope = document.getElementById('departmentScope');
                if (departmentScope) {
                    departmentScope.textContent = 'Scope: Faculty Department';
                }
            }
        }
        
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
                    const periodDuration = document.getElementById('periodDuration');
                    
                    if (currentPeriodDisplay) {
                        const termMap = { '1st': 'Term 1', '2nd': 'Term 2', '3rd': 'Term 3' };
                        const semLabel = termMap[period.semester_name] || period.semester_name || '';
                        currentPeriodDisplay.textContent = `${period.school_year} ${semLabel} (ACTIVE)`;
                    }
                    
                    if (periodDuration) {
                        const startDate = new Date(period.start_date);
                        const endDate = new Date(period.end_date);
                        const today = new Date();
                        const daysElapsed = Math.floor((today - startDate) / (1000 * 60 * 60 * 24));
                        const totalDays = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24));
                        
                        periodDuration.textContent = `Duration: ${totalDays} days | Started: ${startDate.toLocaleDateString()}`;
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
        
        // Load department statistics
        async function loadDepartmentStats() {
            try {
                // This would be replaced with actual API calls to get real statistics
                const studentStats = document.getElementById('studentStats');
                const pendingStats = document.getElementById('pendingStats');
                
                if (studentStats) {
                    studentStats.textContent = 'Faculty: 0 applied, 0 completed (0%)';
                }
                if (pendingStats) {
                    pendingStats.textContent = 'Pending Signatures: 0 faculty';
                }
            } catch (error) {
                console.error('Error loading department stats:', error);
            }
        }

        function viewPendingClearances() {
            showToast('Opening pending clearances...', 'info');
            setTimeout(() => {
                window.location.href = 'StudentManagement.php';
            }, 1000);
        }

        function exportClearanceReport() {
            showConfirmationModal(
                'Export Clearance Report',
                'Generate a comprehensive clearance report for your department?',
                'Export',
                'Cancel',
                () => {
                    showToast('Report generation started...', 'info');
                    setTimeout(() => {
                        showToast('Report exported successfully!', 'success');
                    }, 2000);
                },
                'info'
            );
        }

        function viewDepartmentStats() {
            showToast('Opening department statistics...', 'info');
            // Could redirect to a detailed stats page
            setTimeout(() => {
                showToast('Department statistics loaded', 'success');
            }, 1500);
        }

        // Sidebar toggle function
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Program Head Dashboard loaded');
            
            // Load dynamic content
            loadUserInfo();
            loadDepartmentInfo();
            loadCurrentPeriod();
            loadDepartmentStats();
            
            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for Program Head Dashboard');
            }
        });
    </script>
</body>
</html>
