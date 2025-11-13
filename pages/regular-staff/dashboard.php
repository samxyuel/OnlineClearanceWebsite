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
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
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


                        <!-- Content Grid -->
                        <div class="content-grid">
                            <!-- Recent Activity Section -->
                            <?php include '../../includes/components/recent-activity.php'; ?>

                            <!-- Notifications Panel -->
                            <?php include '../../includes/components/notifications.php'; ?>
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
                    updateSectorStatusDisplay(sectorResult.periods_by_sector || {});
                }

                // Update dashboard statistics
                if (summaryResult.success) {
                    updateStatisticsDisplay(summaryResult.data);
                } else {
                    // Use fallback data if API fails
                    updateStatisticsDisplay({
                        signing_stats: {
                            total_signed: 0,
                            approved: 0,
                            rejected: 0
                        },
                        pending_clearances: {
                            total: 0
                        },
                        sector_stats: {
                            college: { applied: 0, completed: 0 },
                            shs: { applied: 0, completed: 0 },
                            faculty: { applied: 0, completed: 0 }
                        }
                    });
                }

            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showToast('An error occurred while loading dashboard data.', 'error');
                // Use fallback data on error
                updateStatisticsDisplay({
                    signing_stats: {
                        total_signed: 0,
                        approved: 0,
                        rejected: 0
                    },
                    pending_clearances: {
                        total: 0
                    },
                    sector_stats: {
                        college: { applied: 0, completed: 0 },
                        shs: { applied: 0, completed: 0 },
                        faculty: { applied: 0, completed: 0 }
                    }
                });
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
        function updateSectorStatusDisplay(periodsBySector) {
            const sectors = [
                { key: 'College', id: 'college-status' },
                { key: 'Senior High School', id: 'shs-status' },
                { key: 'Faculty', id: 'faculty-status' }
            ];
            
            sectors.forEach(sector => {
                const statusElement = document.getElementById(sector.id);
                if (!statusElement) return;
                
                const sectorPeriods = periodsBySector[sector.key];
                
                if (sectorPeriods && sectorPeriods.length > 0) {
                    const latestPeriod = sectorPeriods[0];
                    const status = latestPeriod.status || 'Not Started';
                    statusElement.textContent = status;
                    statusElement.className = `sector-status status-${status.toLowerCase().replace(/\s+/g, '-')}`;
                } else {
                    statusElement.textContent = 'Not Started';
                    statusElement.className = 'sector-status status-not-started';
                }
            });
        }

        // Update statistics display
        function updateStatisticsDisplay(data) {
            // Update Staff Statistics Dashboard
            const totalSignedEl = document.getElementById('totalSigned');
            const totalApprovedEl = document.getElementById('totalApproved');
            const totalRejectedEl = document.getElementById('totalRejected');
            const totalPendingEl = document.getElementById('totalPending');

            // Update Total Signed
            if (totalSignedEl) {
                const totalSigned = data.signing_stats?.total_signed || 0;
                totalSignedEl.textContent = totalSigned.toLocaleString();
            }

            // Update Approved
            if (totalApprovedEl) {
                const approved = data.signing_stats?.approved || 0;
                totalApprovedEl.textContent = approved.toLocaleString();
            }

            // Update Rejected
            if (totalRejectedEl) {
                const rejected = data.signing_stats?.rejected || 0;
                totalRejectedEl.textContent = rejected.toLocaleString();
            }

            // Update Pending
            if (totalPendingEl) {
                const pending = data.pending_clearances?.total || 0;
                totalPendingEl.textContent = pending.toLocaleString();
            }

            // Update sector statistics
            const collegeStatsEl = document.getElementById('college-stats');
            const shsStatsEl = document.getElementById('shs-stats');
            const facultyStatsEl = document.getElementById('faculty-stats');

            // Update College stats
            if (collegeStatsEl) {
                if (data.sector_stats?.college) {
                    const college = data.sector_stats.college;
                    const applied = college.applied || 0;
                    const completed = college.completed || 0;
                    const percentage = applied > 0 ? Math.round((completed / applied) * 100) : 0;
                    collegeStatsEl.textContent = `College: ${applied} applied, ${completed} completed (${percentage}%)`;
                } else {
                    collegeStatsEl.textContent = 'College: 0 applied, 0 completed (0%)';
                }
            }

            // Update SHS stats
            if (shsStatsEl) {
                if (data.sector_stats?.shs) {
                    const shs = data.sector_stats.shs;
                    const applied = shs.applied || 0;
                    const completed = shs.completed || 0;
                    const percentage = applied > 0 ? Math.round((completed / applied) * 100) : 0;
                    shsStatsEl.textContent = `Senior High School: ${applied} applied, ${completed} completed (${percentage}%)`;
                } else {
                    shsStatsEl.textContent = 'Senior High School: 0 applied, 0 completed (0%)';
                }
            }

            // Update Faculty stats
            if (facultyStatsEl) {
                if (data.sector_stats?.faculty) {
                    const faculty = data.sector_stats.faculty;
                    const applied = faculty.applied || 0;
                    const completed = faculty.completed || 0;
                    const percentage = applied > 0 ? Math.round((completed / applied) * 100) : 0;
                    facultyStatsEl.textContent = `Faculty: ${applied} applied, ${completed} completed (${percentage}%)`;
                } else {
                    facultyStatsEl.textContent = 'Faculty: 0 applied, 0 completed (0%)';
                }
            }
        }
    </script>
</body>
</html>
