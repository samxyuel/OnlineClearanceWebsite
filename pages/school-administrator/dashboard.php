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
                            <h2><i class="fas fa-tachometer-alt"></i> School Administrator Dashboard</h2>
                            <p id="welcomeMessage">Welcome back! Monitor school-wide clearance status and manage student and faculty records.</p>
                        </div>

                        <!-- Current Term Status (Read-only for School Administrator) -->
                        <div class="card active-period-status">
                            <div class="status-content">
                                <div class="status-header">
                                    <h3><i class="fas fa-calendar-check"></i> <span id="currentAcademicYear">Loading...</span> - <span id="currentActiveTerm">Loading...</span></h3>
                                    <p id="termDuration">Loading term information...</p>
                                    
                                    <!-- School Scope Info -->
                                    <div class="school-scope-info pill-indicator">
                                        <i class="fas fa-school"></i>
                                        <span id="schoolScope">School-wide Access</span>
                                    </div>
                                    
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

                <!-- School Statistics Dashboard -->
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
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalFacultyStat">0</h3>
                            <p>Total Faculty</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="studentClearancesStat">0</h3>
                            <p>Student Clearances</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="facultyClearancesStat">0</h3>
                            <p>Faculty Clearances</p>
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
    </main>

    <!-- Scripts -->
    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
    
    <script>
        // Load dashboard data
        async function loadDashboardData() {
            try {
                // Load academic context, sector periods, and school admin summary in parallel
                const [contextResponse, sectorResponse, summaryResponse] = await Promise.all([
                    fetch('../../api/clearance/context.php', { credentials: 'include' }),
                    fetch('../../api/clearance/sector-periods.php', { credentials: 'include' }),
                    fetch('../../api/dashboard/school_admin_summary.php', { credentials: 'include' })
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
                    updateSchoolInfo(summaryResult.data);
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

        // Update school information
        function updateSchoolInfo(data) {
            // Welcome Message
            if (data.user) {
                document.getElementById('welcomeMessage').textContent = `Welcome back, ${data.user.first_name}! Monitor school-wide clearance status and manage student and faculty records.`;
            }
        }

        // Update School Statistics Dashboard
        function updateSchoolStats(data) {
            // Update Total Students
            const totalStudentsEl = document.getElementById('totalStudentsStat');
            if (totalStudentsEl) {
                totalStudentsEl.textContent = (data.total_students || 0).toLocaleString();
            }
            
            // Update Total Faculty
            const totalFacultyEl = document.getElementById('totalFacultyStat');
            if (totalFacultyEl) {
                totalFacultyEl.textContent = (data.total_faculty || 0).toLocaleString();
            }
            
            // Update Student Clearances
            const studentClearancesEl = document.getElementById('studentClearancesStat');
            if (studentClearancesEl && data.completed_clearances) {
                studentClearancesEl.textContent = (data.completed_clearances.student || 0).toLocaleString();
            }
            
            // Update Faculty Clearances
            const facultyClearancesEl = document.getElementById('facultyClearancesStat');
            if (facultyClearancesEl && data.completed_clearances) {
                facultyClearancesEl.textContent = (data.completed_clearances.faculty || 0).toLocaleString();
            }
            
            // Update Pending Signatures
            const pendingSignaturesEl = document.getElementById('pendingSignaturesStat');
            if (pendingSignaturesEl) {
                pendingSignaturesEl.textContent = (data.pending_signatures || 0).toLocaleString();
            }
        }

        // Update statistics display
        function updateStatisticsDisplay(data) {
            // Update School Statistics Dashboard
            updateSchoolStats(data);

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
            loadDashboardData();
        });
    </script>
</body>
</html>
