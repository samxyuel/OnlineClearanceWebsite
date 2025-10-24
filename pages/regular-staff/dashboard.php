<?php
// Online Clearance Website - Regular Staff Dashboard
// Session management handled by header component
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
                            <h2><i class="fas fa-tachometer-alt"></i> Staff Dashboard</h2>
                            <p id="welcomeMessage">Welcome back! Review and sign pending clearances for students and faculty.</p>
                            <div class="department-scope-info">
                                <i class="fas fa-user-shield"></i>
                                <span id="positionInfo">Loading position information...</span>
                            </div>
                        </div>

                        <!-- Current Term Status (Read-only for Staff) -->
                        <div class="card active-period-status">
                            <div class="status-content">
                                <div class="status-header">
                                    <h3><i class="fas fa-calendar-check"></i> 2024-2025 Term 1 (ACTIVE)</h3>
                                    <p>Duration: 45 days | Started: Jan 15, 2024</p>
                                </div>
                                
                                <div class="status-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-user-graduate"></i>
                                        <span>Students: 156 pending signatures</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Faculty: 23 pending signatures</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Your Pending: 34 total</span>
                                    </div>
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
                                    <h3 id="totalSigned">0</h3>
                                    <p>Total Signed</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalApproved">0</h3>
                                    <p>Approved</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalRejected">0</h3>
                                    <p>Rejected</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalPending">0</h3>
                                    <p>Pending</p>
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
            
            // Load user information dynamically
            loadUserInfo();
            
            // Initialize Activity Tracker
            window.sidebarHandledByPage = true;
            window.activityTrackerInstance = new ActivityTracker();
        });

        // Load user information from session/API
        function loadUserInfo() {
            // Get user info from the header component (it's already loaded)
            const userNameElement = document.querySelector('.user-name');
            
            if (userNameElement) {
                const fullName = userNameElement.textContent.trim();
                const welcomeMessage = document.getElementById('welcomeMessage');
                if (welcomeMessage) {
                    welcomeMessage.textContent = `Welcome back, ${fullName}! Review and sign pending clearances for students and faculty.`;
                }
            }

            // Load staff position information
            loadStaffPosition();

            // Load dashboard summary data
            loadDashboardSummary();
        }

        // Load staff position information
        function loadStaffPosition() {
            fetch('../../api/users/get_current_staff_designation.php', {
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                const positionElement = document.getElementById('positionInfo');
                if (positionElement) {
                    if (data.success && data.designation) {
                        positionElement.textContent = `Position: ${data.designation} - Clearance Signatory`;
                    } else {
                        positionElement.textContent = 'Position: Staff - Clearance Signatory';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading staff position:', error);
                const positionElement = document.getElementById('positionInfo');
                if (positionElement) {
                    positionElement.textContent = 'Position: Staff - Clearance Signatory';
                }
            });
        }

        // Load dashboard summary data
        async function loadDashboardSummary() {
            try {
                const response = await fetch('../../api/dashboard/staff_summary.php', {
                    credentials: 'include'
                });
                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    // Update Active Period Card
                    const activePeriodNameEl = document.getElementById('activePeriodName');
                    const activePeriodDetailsEl = document.getElementById('activePeriodDetails');
                    if (data.active_period) {
                        activePeriodNameEl.innerHTML = `<i class="fas fa-calendar-check"></i> ${data.active_period.academic_year} ${data.active_period.semester_name} (ACTIVE)`;
                        activePeriodDetailsEl.textContent = `Started: ${new Date(data.active_period.start_date).toLocaleDateString()}`;
                    } else {
                        activePeriodNameEl.innerHTML = `<i class="fas fa-calendar-times"></i> No Active Period`;
                        activePeriodDetailsEl.textContent = 'Clearance activities are currently paused.';
                    }

                    // Update Pending Counts
                    document.getElementById('pendingStudentsStat').innerHTML = `<i class="fas fa-user-graduate"></i> Students: ${data.pending_clearances.student} pending signatures`;
                    document.getElementById('pendingFacultyStat').innerHTML = `<i class="fas fa-chalkboard-teacher"></i> Faculty: ${data.pending_clearances.faculty} pending signatures`;
                    document.getElementById('yourTotalPendingStat').innerHTML = `<i class="fas fa-clock"></i> Your Pending: ${data.pending_clearances.total} total`;

                    // Update Staff Statistics Dashboard
                    document.getElementById('totalSigned').textContent = data.signing_stats.total_signed.toLocaleString();
                    document.getElementById('totalApproved').textContent = data.signing_stats.approved.toLocaleString();
                    document.getElementById('totalRejected').textContent = data.signing_stats.rejected.toLocaleString();
                    document.getElementById('totalPending').textContent = data.pending_clearances.total.toLocaleString();

                } else {
                    showToast(result.message || 'Failed to load dashboard data.', 'error');
                }
            } catch (error) {
                console.error('Error loading dashboard summary:', error);
                showToast('An error occurred while loading dashboard data.', 'error');
            }
        }
    </script>
</body>
</html>
