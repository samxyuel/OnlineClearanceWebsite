<?php
// Online Clearance Website - Program Head Dashboard
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Demo session data for testing - Program Head
$_SESSION['user_id'] = 4;
$_SESSION['role_id'] = 2; // Program Head role
$_SESSION['first_name'] = 'Dr. Maria';
$_SESSION['last_name'] = 'Santos';
$_SESSION['assigned_departments'] = ['Information, Communication, and Technology']; // Program Head's assigned departments
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
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-name">Dr. Maria Santos (Program Head)</span>
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
            <div class="dashboard-layout">
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-tachometer-alt"></i> Program Head Dashboard</h2>
                            <p>Welcome back, Dr. Maria Santos. Monitor your department's clearance status and manage student records.</p>
                            <div class="department-scope-info">
                                <i class="fas fa-shield-alt"></i>
                                <span>Scope: Information, Communication, and Technology Department</span>
                            </div>
                        </div>

                        <!-- Current Term Status (Read-only for Program Head) -->
                        <div class="card active-period-status">
                            <div class="status-content">
                                <div class="status-info">
                                    <h3><i class="fas fa-calendar-check"></i> 2024-2025 Term 1 (ACTIVE)</h3>
                                    <p>Duration: 45 days | Started: Jan 15, 2024</p>
                                    <div class="period-stats">
                                        <span class="stat-item">
                                            <i class="fas fa-user-graduate"></i> ICT Students: 156 applied, 142 completed (91%)
                                        </span>
                                        <span class="stat-item">
                                            <i class="fas fa-clock"></i> Pending Signatures: 14 students
                                        </span>
                                    </div>
                                </div>
                                <div class="status-actions">
                                    <button class="btn btn-success" onclick="viewPendingClearances()">
                                        <i class="fas fa-signature"></i> Sign Clearances
                                    </button>
                                    <button class="btn btn-info" onclick="exportClearanceReport()">
                                        <i class="fas fa-file-export"></i> Export Report
                                    </button>
                                    <a href="StudentManagement.php" class="btn btn-primary">
                                        <i class="fas fa-users"></i> Manage Students
                                    </a>
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
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>23</h3>
                                    <p>This Week's Reports</p>
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
                                        <p>View and manage student records</p>
                                    </div>
                                </a>
                                <a href="#" class="action-card" onclick="viewPendingClearances()">
                                    <div class="action-icon">
                                        <i class="fas fa-signature"></i>
                                    </div>
                                    <div class="action-content">
                                        <h4>Sign Clearances</h4>
                                        <p>Approve or reject student clearances</p>
                                    </div>
                                </a>
                                <a href="#" class="action-card" onclick="exportClearanceReport()">
                                    <div class="action-icon">
                                        <i class="fas fa-file-export"></i>
                                    </div>
                                    <div class="action-content">
                                        <h4>Export Reports</h4>
                                        <p>Generate clearance reports</p>
                                    </div>
                                </a>
                                <a href="#" class="action-card" onclick="viewDepartmentStats()">
                                    <div class="action-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="action-content">
                                        <h4>Department Stats</h4>
                                        <p>View detailed statistics</p>
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
                                <h3><i class="fas fa-building"></i> ICT Department Overview</h3>
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
        function viewPendingClearances() {
            showToast('Opening pending clearances...', 'info');
            setTimeout(() => {
                window.location.href = 'StudentManagement.php';
            }, 1000);
        }

        function exportClearanceReport() {
            showConfirmationModal(
                'Export Clearance Report',
                'Generate a comprehensive clearance report for the ICT Department?',
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
            
            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for Program Head Dashboard');
            }
        });
    </script>
</body>
</html>
