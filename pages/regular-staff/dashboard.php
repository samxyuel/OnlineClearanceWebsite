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
                                    <h3><i class="fas fa-calendar-check"></i> <span id="currentAcademicYear">Loading...</span> - <span id="currentActiveTerm">Loading...</span></h3>
                                    <p id="termDuration">Loading term information...</p>
                                    
                                    <!-- Sector Status Indicators -->
                                    <div class="sector-status-indicators">
                                        <div class="sector-indicator college-sector">
                                            <i class="fas fa-university"></i>
                                            <span class="sector-name">College</span>
                                            <span class="sector-status" id="college-status">Loading...</span>
                                        </div>
                                        <div class="sector-indicator shs-sector">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span class="sector-name">Senior High School</span>
                                            <span class="sector-status" id="shs-status">Loading...</span>
                                        </div>
                                        <div class="sector-indicator faculty-sector">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <span class="sector-name">Faculty</span>
                                            <span class="sector-status" id="faculty-status">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-university"></i>
                                        <span id="college-stats">College: 0 applied, 0 completed (0%)</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span id="shs-stats">Senior High School: 0 applied, 0 completed (0%)</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span id="faculty-stats">Faculty: 0 applied, 0 completed (0%)</span>
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

            // Load dashboard data
            loadDashboardData();
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

        // Load dashboard data
        async function loadDashboardData() {
            try {
                // Load academic context and sector periods in parallel
                const [contextResponse, sectorResponse, summaryResponse] = await Promise.all([
                    fetch('../../api/clearance/context.php', { credentials: 'include' }),
                    fetch('../../api/clearance/sector-periods.php', { credentials: 'include' }),
                    fetch('../../api/dashboard/staff_summary.php', { credentials: 'include' })
                ]);

                const [contextResult, sectorResult, summaryResult] = await Promise.all([
                    contextResponse.json(),
                    sectorResponse.json(),
                    summaryResponse.json()
                ]);

                // Update academic year and term display
                updateAcademicYearDisplay(contextResult);
                
                // Update sector status display
                if (sectorResult.success) {
                    updateSectorStatusDisplay(sectorResult.data);
                }

                // Update dashboard statistics
                if (summaryResult.success) {
                    updateStatisticsDisplay(summaryResult.data);
                }

            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showToast('An error occurred while loading dashboard data.', 'error');
            }
        }

        // Update academic year and term display
        function updateAcademicYearDisplay(contextResult) {
            const academicYearEl = document.getElementById('currentAcademicYear');
            const activeTermEl = document.getElementById('currentActiveTerm');
            const termDurationEl = document.getElementById('termDuration');

            if (contextResult.success && contextResult.data) {
                const data = contextResult.data;
                
                if (academicYearEl) academicYearEl.textContent = data.academic_year || 'No Academic Year';
                if (activeTermEl) activeTermEl.textContent = data.active_term || 'No Active Term';
                
                if (data.active_term && data.term_duration) {
                    if (termDurationEl) {
                        termDurationEl.textContent = `Duration: ${data.term_duration} days | Started: ${new Date(data.term_start_date).toLocaleDateString()}`;
                    }
                } else {
                    if (termDurationEl) termDurationEl.textContent = 'No active term information available.';
                }
            } else {
                if (academicYearEl) academicYearEl.textContent = 'No Academic Year';
                if (activeTermEl) activeTermEl.textContent = 'No Active Term';
                if (termDurationEl) termDurationEl.textContent = 'No active term information available.';
            }
        }

        // Update sector status display
        function updateSectorStatusDisplay(sectorData) {
            const sectors = [
                { id: 'college-status', name: 'College' },
                { id: 'shs-status', name: 'Senior High School' },
                { id: 'faculty-status', name: 'Faculty' }
            ];

            sectors.forEach(sector => {
                const statusElement = document.getElementById(sector.id);
                if (statusElement && sectorData[sector.name.toLowerCase().replace(' ', '_')]) {
                    const sectorInfo = sectorData[sector.name.toLowerCase().replace(' ', '_')];
                    statusElement.textContent = sectorInfo.status || 'Not Started';
                    statusElement.className = `sector-status status-${(sectorInfo.status || 'not-started').toLowerCase().replace(' ', '-')}`;
                }
            });
        }

        // Update statistics display
        function updateStatisticsDisplay(data) {
            // Update Staff Statistics Dashboard
            if (data.signing_stats) {
                const totalSignedEl = document.getElementById('totalSigned');
                const totalApprovedEl = document.getElementById('totalApproved');
                const totalRejectedEl = document.getElementById('totalRejected');
                const totalPendingEl = document.getElementById('totalPending');

                if (totalSignedEl) totalSignedEl.textContent = data.signing_stats.total_signed?.toLocaleString() || '0';
                if (totalApprovedEl) totalApprovedEl.textContent = data.signing_stats.approved?.toLocaleString() || '0';
                if (totalRejectedEl) totalRejectedEl.textContent = data.signing_stats.rejected?.toLocaleString() || '0';
                if (totalPendingEl) totalPendingEl.textContent = data.pending_clearances?.total?.toLocaleString() || '0';
            }

            // Update sector statistics
            if (data.sector_stats) {
                const collegeStatsEl = document.getElementById('college-stats');
                const shsStatsEl = document.getElementById('shs-stats');
                const facultyStatsEl = document.getElementById('faculty-stats');

                if (collegeStatsEl && data.sector_stats.college) {
                    const college = data.sector_stats.college;
                    const collegePercentage = college.applied > 0 ? Math.round((college.completed / college.applied) * 100) : 0;
                    collegeStatsEl.textContent = `College: ${college.applied} applied, ${college.completed} completed (${collegePercentage}%)`;
                }

                if (shsStatsEl && data.sector_stats.shs) {
                    const shs = data.sector_stats.shs;
                    const shsPercentage = shs.applied > 0 ? Math.round((shs.completed / shs.applied) * 100) : 0;
                    shsStatsEl.textContent = `Senior High School: ${shs.applied} applied, ${shs.completed} completed (${shsPercentage}%)`;
                }

                if (facultyStatsEl && data.sector_stats.faculty) {
                    const faculty = data.sector_stats.faculty;
                    const facultyPercentage = faculty.applied > 0 ? Math.round((faculty.completed / faculty.applied) * 100) : 0;
                    facultyStatsEl.textContent = `Faculty: ${faculty.applied} applied, ${faculty.completed} completed (${facultyPercentage}%)`;
                }
            }
        }
    </script>
</body>
</html>
