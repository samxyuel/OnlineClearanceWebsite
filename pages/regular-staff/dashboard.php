<?php
// Online Clearance Website - Regular Staff Dashboard
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Demo session data for testing - Regular Staff
$_SESSION['user_id'] = 7;
$_SESSION['role_id'] = 3; // Regular Staff role
$_SESSION['first_name'] = 'Sarah';
$_SESSION['last_name'] = 'Wilson';
$_SESSION['position'] = 'Cashier'; // Staff position for clearance signing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Online Clearance System</title>
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
                    <span class="user-name">Sarah Wilson (Regular Staff - Cashier)</span>
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
                <!-- LEFT SIDE: Main Content -->
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-tachometer-alt"></i> Staff Dashboard</h2>
                            <p>Welcome back, Sarah Wilson. Review and sign pending clearances for students and faculty.</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>
                                <span>Position: Cashier - Clearance Signatory</span>
                            </div>
                        </div>

                        <!-- Current Term Status (Read-only for Staff) -->
                        <div class="card active-period-status">
                            <div class="status-content">
                                <div class="status-info">
                                    <h3><i class="fas fa-calendar-check"></i> 2024-2025 Term 1 (ACTIVE)</h3>
                                    <p>Duration: 45 days | Started: Jan 15, 2024</p>
                                    <div class="period-stats">
                                        <span class="stat-item">
                                            <i class="fas fa-user-graduate"></i> Students: 156 pending signatures
                                        </span>
                                        <span class="stat-item">
                                            <i class="fas fa-chalkboard-teacher"></i> Faculty: 23 pending signatures
                                        </span>
                                        <span class="stat-item">
                                            <i class="fas fa-clock"></i> Your Pending: 34 total
                                        </span>
                                    </div>
                                </div>
                                <div class="status-actions">
                                    <button class="btn btn-success" onclick="viewPendingClearances()">
                                        <i class="fas fa-signature"></i> Sign Clearances
                                    </button>
                                    <button class="btn btn-info" onclick="exportStaffReport()">
                                        <i class="fas fa-file-export"></i> Export Report
                                    </button>
                                    <a href="StudentManagement.php" class="btn btn-primary">
                                        <i class="fas fa-users"></i> Manage Students
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Staff Statistics Dashboard -->
                        <div class="stats-dashboard">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-signature"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>1,245</h3>
                                    <p>Total Signed</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>1,156</h3>
                                    <p>Approved</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>89</h3>
                                    <p>Rejected</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>34</h3>
                                    <p>Pending</p>
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
                                        <h4>Student Clearances</h4>
                                        <p>Review and sign student clearance requests</p>
                                    </div>
                                </a>
                                <a href="FacultyManagement.php" class="action-card">
                                    <div class="action-icon">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <div class="action-content">
                                        <h4>Faculty Clearances</h4>
                                        <p>Review and sign faculty clearance requests</p>
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
                                <a href="#" class="action-card" onclick="exportStaffReport()">
                                    <div class="action-icon">
                                        <i class="fas fa-file-export"></i>
                                    </div>
                                    <div class="action-content">
                                        <h4>Export Report</h4>
                                        <p>Generate your clearance signing report</p>
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
                                        <i class="fas fa-file-export"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Report Exported</h4>
                                        <p>Monthly clearance signing report was generated</p>
                                        <span class="activity-time">2 hours ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-signature"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Bulk Approval</h4>
                                        <p>Approved 15 student clearances in batch</p>
                                        <span class="activity-time">3 hours ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Position Overview -->
                        <div class="management-section">
                            <div class="section-header">
                                <h3><i class="fas fa-user-shield"></i> Position Overview</h3>
                            </div>
                            <div class="department-overview">
                                <div class="overview-card">
                                    <h4><i class="fas fa-signature"></i> Clearance Signing Stats</h4>
                                    <div class="clearance-stats">
                                        <div class="status-item completed">
                                            <span class="status-label">Approved</span>
                                            <span class="status-count">1,156</span>
                                        </div>
                                        <div class="status-item pending">
                                            <span class="status-label">Pending</span>
                                            <span class="status-count">34</span>
                                        </div>
                                        <div class="status-item rejected">
                                            <span class="status-label">Rejected</span>
                                            <span class="status-count">89</span>
                                        </div>
                                        <div class="status-item in-progress">
                                            <span class="status-label">In Progress</span>
                                            <span class="status-count">12</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="overview-card">
                                    <h4><i class="fas fa-chart-line"></i> This Month's Performance</h4>
                                    <div class="performance-stats">
                                        <div class="performance-item">
                                            <span class="performance-label">Average Response Time</span>
                                            <span class="performance-value">2.3 hours</span>
                                        </div>
                                        <div class="performance-item">
                                            <span class="performance-label">Approval Rate</span>
                                            <span class="performance-value">92.8%</span>
                                        </div>
                                        <div class="performance-item">
                                            <span class="performance-label">Clearances Processed</span>
                                            <span class="performance-value">156</span>
                                        </div>
                                        <div class="performance-item">
                                            <span class="performance-label">Satisfaction Score</span>
                                            <span class="performance-value">4.7/5.0</span>
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

        function exportStaffReport() {
            showConfirmationModal(
                'Export Staff Report',
                'Generate a PDF report of your clearance signing activities?',
                'Export',
                'Cancel',
                () => {
                    showToast('Report generation started...', 'info');
                    setTimeout(() => {
                        showToast('Staff report exported successfully!', 'success');
                    }, 2000);
                },
                'info'
            );
        }

        function viewStaffStats() {
            showToast('Opening staff statistics...', 'info');
            // Could redirect to a detailed stats page
            setTimeout(() => {
                showToast('Staff statistics loaded', 'success');
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
            console.log('Regular Staff Dashboard loaded');
            
            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();
        });
    </script>
</body>
</html>
