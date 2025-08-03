<?php
// Online Clearance Website - Admin Dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php
    // Demo session data for testing
    session_start();
    $_SESSION['user_id'] = 3;
    $_SESSION['role_id'] = 1; // Admin role
    $_SESSION['first_name'] = 'Admin';
    $_SESSION['last_name'] = 'User';
    ?>
    
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
                    <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
                    <p>Welcome to the admin dashboard. Monitor system status and manage clearance operations.</p>
                </div>

                <!-- Active Period Status -->
                <div class="card active-period-status">
                    <div class="status-content">
                        <div class="status-info">
                            <h3><i class="fas fa-calendar-check"></i> 2024-2025 Term 1 (ACTIVE)</h3>
                            <p>Duration: 45 days | Started: Jan 15, 2024</p>
                            <div class="period-stats">
                                <span class="stat-item">
                                    <i class="fas fa-user-graduate"></i> Students: 45 applied, 32 completed (71%)
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-chalkboard-teacher"></i> Faculty: 12 applied, 8 completed (67%)
                                </span>
                            </div>
                        </div>
                        <div class="status-actions">
                            <button class="btn btn-warning" onclick="deactivateCurrentTerm()">
                                <i class="fas fa-pause"></i> Deactivate
                            </button>
                            <button class="btn btn-danger" onclick="endCurrentTerm()">
                                <i class="fa-solid fa-clipboard-check"></i> End Term
                            </button>
                            <a href="ClearanceManagement.php" class="btn btn-primary">
                                <i class="fas fa-cog"></i> Manage Clearance
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Dashboard -->
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
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3>89</h3>
                            <p>Total Faculty</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <div class="stat-content">
                            <h3>26</h3>
                            <p>Total Staff</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3>57</h3>
                            <p>Active Clearances</p>
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
                                <p>View and manage student accounts</p>
                            </div>
                        </a>
                        <a href="FacultyManagement.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="action-content">
                                <h4>Manage Faculty</h4>
                                <p>View and manage faculty accounts</p>
                            </div>
                        </a>
                        <a href="StaffManagement.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <div class="action-content">
                                <h4>Manage Staff</h4>
                                <p>View and manage staff accounts</p>
                            </div>
                        </a>
                        <a href="ClearanceManagement.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="action-content">
                                <h4>Clearance Management</h4>
                                <p>Manage clearance periods and signatories</p>
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
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-content">
                                <h4>New Student Registration</h4>
                                <p>John Smith registered for clearance</p>
                                <span class="activity-time">2 minutes ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Clearance Completed</h4>
                                <p>Maria Garcia completed her clearance</p>
                                <span class="activity-time">15 minutes ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-pause-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Term Deactivated</h4>
                                <p>2024-2025 Term 1 was deactivated</p>
                                <span class="activity-time">1 hour ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Staff Updated</h4>
                                <p>Dr. Emily Brown's information was updated</p>
                                <span class="activity-time">2 hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../../assets/js/alerts.js"></script>
    <script>
        function deactivateCurrentTerm() {
            showConfirmation(
                'Deactivate Current Term',
                'Are you sure you want to deactivate the current term? This will pause all clearance activities.',
                'Deactivate',
                'Cancel',
                () => {
                    showToast('Term deactivated successfully!', 'success');
                    // Redirect to clearance management for further actions
                    setTimeout(() => {
                        window.location.href = 'ClearanceManagement.php';
                    }, 1500);
                },
                'warning'
            );
        }

        function endCurrentTerm() {
            showConfirmation(
                'End Current Term',
                'Are you sure you want to end the current term? This will permanently conclude all clearance activities.',
                'End Term',
                'Cancel',
                () => {
                    showToast('Term ended successfully!', 'success');
                    // Redirect to clearance management for further actions
                    setTimeout(() => {
                        window.location.href = 'ClearanceManagement.php';
                    }, 1500);
                },
                'danger'
            );
        }

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
            }
            
            if (mainContent) {
                mainContent.classList.toggle('full-width');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Dashboard loaded');
        });
    </script>
</body>
</html> 