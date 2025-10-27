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
                        </div>

                        <!-- Current Term Status (Read-only for Program Head) -->
                        <div class="card active-period-status">
                            <div class="status-content">
                                <div class="status-header">
                                    <h3><i class="fas fa-calendar-check"></i> <span id="currentAcademicYear">Loading...</span> - <span id="currentActiveTerm">Loading...</span></h3>
                                    <p id="termDuration">Loading term information...</p>
                                    
                                    <!-- Department Scope Info -->
                                    <div class="department-scope-info pill-indicator">
                                        <i class="fas fa-shield-alt"></i>
                                        <span id="departmentScope">Loading department information...</span>
                                    </div>
                                    
                                    <!-- Handled Sector Info -->
                                    <div class="handled-sector-info pill-indicator">
                                        <i class="fas fa-users-cog"></i>
                                        <span id="handledSector">Loading handled sector...</span>
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

        // Load dashboard data
        async function loadDashboardData() {
            try {
                // Load academic context, sector periods, and program head summary in parallel
                const [contextResponse, sectorResponse, summaryResponse] = await Promise.all([
                    fetch('../../api/clearance/context.php', { credentials: 'include' }),
                    fetch('../../api/clearance/sector-periods.php', { credentials: 'include' }),
                    fetch('../../api/dashboard/program_head_summary.php', { credentials: 'include' })
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

                // Update dashboard statistics and department info
                if (summaryResult.success) {
                    updateStatisticsDisplay(summaryResult.data);
                    updateDepartmentInfo(summaryResult.data);
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

        // Update department information
        function updateDepartmentInfo(data) {
            // Welcome Message and Department Scope
            if (data.user) {
                document.getElementById('welcomeMessage').textContent = `Welcome back, ${data.user.first_name}! Monitor your department's clearance status and manage records.`;
            }
            if (data.departments && data.departments.length > 0) {
                const deptNames = data.departments.map(d => d.department_name).join(', ');
                document.getElementById('departmentScope').textContent = `Scope: ${deptNames}`;
            } else {
                document.getElementById('departmentScope').textContent = 'Scope: No departments assigned';
            }

            // Update handled sector information
            updateHandledSectorInfo(data);
        }

        // Update handled sector information
        function updateHandledSectorInfo(data) {
            const handledSectorEl = document.getElementById('handledSector');
            if (!handledSectorEl) return;

            // Determine handled sectors based on department data and clearance stats
            const handledSectors = [];
            
            if (data.sector_stats) {
                if (data.sector_stats.college && (data.sector_stats.college.applied > 0 || data.sector_stats.college.completed > 0)) {
                    handledSectors.push('College');
                }
                if (data.sector_stats.shs && (data.sector_stats.shs.applied > 0 || data.sector_stats.shs.completed > 0)) {
                    handledSectors.push('Senior High School');
                }
                if (data.sector_stats.faculty && (data.sector_stats.faculty.applied > 0 || data.sector_stats.faculty.completed > 0)) {
                    handledSectors.push('Faculty');
                }
            }

            if (handledSectors.length > 0) {
                handledSectorEl.textContent = `Handled: ${handledSectors.join(', ')}`;
            } else {
                handledSectorEl.textContent = 'Handled: No active sectors';
            }
        }

        // Update statistics display
        function updateStatisticsDisplay(data) {
            // Update Department Statistics Dashboard
            if (data.total_students !== undefined) {
                document.getElementById('totalStudentsStat').textContent = data.total_students.toLocaleString();
            }
            if (data.total_faculty !== undefined) {
                document.getElementById('totalFacultyStat').textContent = data.total_faculty.toLocaleString();
            }
            if (data.pending_signatures) {
                const totalPending = (data.pending_signatures.student || 0) + (data.pending_signatures.faculty || 0);
                document.getElementById('pendingSignaturesStat').textContent = totalPending.toLocaleString();
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

            // Update Department Overview sections
            updateDepartmentOverview(data);
        }

        // Update department overview sections
        function updateDepartmentOverview(data) {
            // Department Overview - Programs
            const programStatsContainer = document.querySelector('.program-stats');
            if (programStatsContainer) {
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
            }

            // Department Overview - Clearance Status
            const clearanceStatsContainer = document.querySelector('.clearance-stats');
            if (clearanceStatsContainer) {
                clearanceStatsContainer.innerHTML = '';
                const statuses = ['completed', 'pending', 'rejected', 'in-progress'];
                statuses.forEach(status => {
                    const count = (data.clearance_stats?.student?.[status] || 0) + (data.clearance_stats?.faculty?.[status] || 0);
                    const item = document.createElement('div');
                    item.className = `status-item ${status}`;
                    item.innerHTML = `
                        <span class="status-label">${status.charAt(0).toUpperCase() + status.slice(1).replace('-', ' ')}</span>
                        <span class="status-count">${count.toLocaleString()}</span>
                    `;
                    clearanceStatsContainer.appendChild(item);
                });
            }
        }


        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Program Head Dashboard loaded');
            
            // Load dynamic content
            loadDashboardData();

            // Initialize Activity Tracker
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized for Program Head Dashboard');
            }
        });
    </script>
</body>
</html>
