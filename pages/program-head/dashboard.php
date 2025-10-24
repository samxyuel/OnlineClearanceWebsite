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
                                <div class="status-info">
                                    <h3><i class="fas fa-calendar-check"></i> <span id="currentPeriodDisplay">Loading current period...</span></h3>
                                    <p id="periodDuration">Loading period information...</p>
                                    <div class="period-stats">
                                        <span class="stat-item">
                                            <i class="fas fa-user-graduate"></i> <span id="studentStats">Students: 0 applied, 0 completed (0%)</span>
                                        </span>
                                        <span class="stat-item">
                                            <i class="fas fa-clock"></i> <span id="pendingStats">Pending: 0 students, 0 faculty</span>
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
                                    <h3 id="totalStudentsStat">0</h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon active">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="totalFacultyStat">0</h3>
                                    <p>Total Faculty</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="pendingSignaturesStat">0</h3>
                                    <p>Pending Signatures</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 id="completedClearancesStat">0</h3>
                                    <p>Completed Clearances</p>
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
                                <h3><i class="fas fa-building"></i> <span id="departmentOverviewTitle">Department Overview</span></h3>
                            </div>
                            <div class="department-overview">
                                <div class="overview-card">
                                    <h4><i class="fas fa-graduation-cap"></i> Programs</h4>
                                    <div class="program-stats">
                                        <!-- Program stats will be loaded dynamically -->
                                    </div>
                                </div>
                                <div class="overview-card">
                                    <h4><i class="fas fa-clipboard-check"></i> Clearance Status</h4>
                                    <div class="clearance-stats">
                                        <!-- Clearance stats will be loaded dynamically -->
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

        async function loadDashboardSummary() {
            try {
                const response = await fetch('../../api/dashboard/program_head_summary.php', {
                    credentials: 'include'
                });
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    updateUI(data);
                } else {
                    showToast(result.message || 'Failed to load dashboard data.', 'error');
                }
            } catch (error) {
                console.error('Error loading dashboard summary:', error);
                showToast('An error occurred while loading dashboard data.', 'error');
            }
        }

        function updateUI(data) {
            // Welcome Message and Scope
            if (data.user) {
                document.getElementById('welcomeMessage').textContent = `Welcome back, ${data.user.first_name}! Monitor your department's clearance status and manage records.`;
            }
            if (data.departments && data.departments.length > 0) {
                const deptNames = data.departments.map(d => d.department_name).join(', ');
                document.getElementById('departmentScope').textContent = `Scope: ${deptNames}`;
                document.getElementById('departmentOverviewTitle').textContent = `${deptNames} Overview`;
            } else {
                document.getElementById('departmentScope').textContent = 'Scope: No departments assigned';
            }

            // Active Period Card
            if (data.active_period) {
                const period = data.active_period;
                document.getElementById('currentPeriodDisplay').textContent = `${period.academic_year} ${period.semester_name} (ACTIVE)`;
                
                const startDate = new Date(period.start_date);
                const endDate = new Date(period.end_date);
                const totalDays = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24));
                document.getElementById('periodDuration').textContent = `Duration: ${totalDays} days | Started: ${startDate.toLocaleDateString()}`;

                const studentApplied = data.clearance_stats.student.applied || 0;
                const studentCompleted = data.clearance_stats.student.completed || 0;
                const studentCompletionRate = studentApplied > 0 ? ((studentCompleted / studentApplied) * 100).toFixed(0) : 0;
                document.getElementById('studentStats').textContent = `Students: ${studentApplied} applied, ${studentCompleted} completed (${studentCompletionRate}%)`;
                
                const pendingStudents = data.pending_signatures.student || 0;
                const pendingFaculty = data.pending_signatures.faculty || 0;
                document.getElementById('pendingStats').textContent = `Pending: ${pendingStudents} students, ${pendingFaculty} faculty`;
            } else {
                document.getElementById('currentPeriodDisplay').textContent = 'No Active Period';
                document.getElementById('periodDuration').textContent = 'Clearance activities are currently paused.';
            }

            // Statistics Dashboard
            document.getElementById('totalStudentsStat').textContent = data.total_students.toLocaleString();
            document.getElementById('totalFacultyStat').textContent = data.total_faculty.toLocaleString();
            document.getElementById('pendingSignaturesStat').textContent = (data.pending_signatures.student + data.pending_signatures.faculty).toLocaleString();
            document.getElementById('completedClearancesStat').textContent = (data.clearance_stats.student.completed + data.clearance_stats.faculty.completed).toLocaleString();

            // Department Overview - Programs
            const programStatsContainer = document.querySelector('.program-stats');
            programStatsContainer.innerHTML = '';
            if (data.programs && data.programs.length > 0) {
                data.programs.forEach(prog => {
                    const item = document.createElement('div');
                    item.className = 'program-item';
                    item.innerHTML = `
                        <span class="program-name">${prog.program_code}</span>
                        <span class="program-count">${prog.student_count} students</span>
                    `;
                    programStatsContainer.appendChild(item);
                });
            } else {
                programStatsContainer.innerHTML = '<p>No programs found for your department(s).</p>';
            }

            // Department Overview - Clearance Status
            const clearanceStatsContainer = document.querySelector('.clearance-stats');
            clearanceStatsContainer.innerHTML = '';
            const statuses = ['completed', 'pending', 'rejected', 'in-progress'];
            statuses.forEach(status => {
                const count = (data.clearance_stats.student[status] || 0) + (data.clearance_stats.faculty[status] || 0);
                const item = document.createElement('div');
                item.className = `status-item ${status}`;
                item.innerHTML = `
                    <span class="status-label">${status.charAt(0).toUpperCase() + status.slice(1).replace('-', ' ')}</span>
                    <span class="status-count">${count.toLocaleString()}</span>
                `;
                clearanceStatsContainer.appendChild(item);
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Program Head Dashboard loaded');
            
            // Load dynamic content
            loadDashboardSummary();

            
            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for Program Head Dashboard');
            }
        });
    </script>
</body>
</html>
