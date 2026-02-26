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
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../../assets/js/alerts.js"></script>
    <?php include '../../includes/functions/audit_functions.php'; ?>
    <script>
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar) {
                if (window.innerWidth <= 768) {
                    // Mobile: toggle sidebar overlay with backdrop
                    sidebar.classList.toggle('active');
                    
                    // Handle backdrop only on mobile
                    if (backdrop) {
                        if (sidebar.classList.contains('active')) {
                            backdrop.style.display = 'block';
                            backdrop.classList.add('active');
                        } else {
                            backdrop.style.display = 'none';
                            backdrop.classList.remove('active');
                        }
                    }
                } else {
                    // Desktop: toggle sidebar collapsed state without backdrop
                    sidebar.classList.toggle('collapsed');
                    if (mainContent) {
                        mainContent.classList.toggle('full-width');
                    }
                    
                    // Ensure backdrop is hidden on desktop
                    if (backdrop) {
                        backdrop.style.display = 'none';
                        backdrop.classList.remove('active');
                    }
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
                    updateSectorStatusDisplay(sectorResult.periods_by_sector || {});
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

            if (contextResult.success) {
                // API returns data directly, not nested in 'data'
                const academicYear = contextResult.academic_year;
                const terms = contextResult.terms || [];
                const activeTerm = terms.find(term => term.is_active === 1);
                
                if (academicYear) {
                    if (academicYearEl) {
                        academicYearEl.textContent = academicYear.year || 'No Academic Year';
                    }
                } else {
                    if (academicYearEl) {
                        academicYearEl.textContent = 'No Academic Year';
                    }
                }
                
                if (activeTerm) {
                    if (activeTermEl) {
                        activeTermEl.textContent = activeTerm.semester_name || 'No Active Term';
                    }
                    if (termDurationEl) {
                        termDurationEl.textContent = `Active Term: ${activeTerm.semester_name}`;
                    }
                } else {
                    if (activeTermEl) {
                        activeTermEl.textContent = 'No Active Term';
                    }
                    if (termDurationEl) {
                        termDurationEl.textContent = 'No active term information available.';
                    }
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
                { key: 'College', id: 'college-status' },
                { key: 'Senior High School', id: 'shs-status' },
                { key: 'Faculty', id: 'faculty-status' }
            ];

            sectors.forEach(sector => {
                const statusElement = document.getElementById(sector.id);
                if (!statusElement) return;
                
                const sectorPeriods = sectorData[sector.key];
                
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

        // Update department information
        function updateDepartmentInfo(data) {
            // Welcome Message and Department Scope
            const welcomeMessageEl = document.getElementById('welcomeMessage');
            if (welcomeMessageEl && data.user) {
                welcomeMessageEl.textContent = `Welcome back, ${data.user.first_name || 'User'}! Monitor your department's clearance status and manage records.`;
            }
            
            const departmentScopeEl = document.getElementById('departmentScope');
            if (departmentScopeEl) {
                if (data.departments && data.departments.length > 0) {
                    const deptNames = data.departments.map(d => d.department_name).join(', ');
                    departmentScopeEl.textContent = `Scope: ${deptNames}`;
                } else {
                    departmentScopeEl.textContent = 'Scope: No departments assigned';
                }
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
            const totalStudentsEl = document.getElementById('totalStudentsStat');
            if (totalStudentsEl) {
                totalStudentsEl.textContent = (data.total_students || 0).toLocaleString();
            }
            
            const totalFacultyEl = document.getElementById('totalFacultyStat');
            if (totalFacultyEl) {
                totalFacultyEl.textContent = (data.total_faculty || 0).toLocaleString();
            }
            
            const pendingSignaturesEl = document.getElementById('pendingSignaturesStat');
            if (pendingSignaturesEl) {
                const totalPending = (data.pending_signatures?.student || 0) + (data.pending_signatures?.faculty || 0);
                pendingSignaturesEl.textContent = totalPending.toLocaleString();
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
                
                // Check if programs data exists
                if (data.programs && Array.isArray(data.programs) && data.programs.length > 0) {
                    data.programs.forEach(prog => {
                        const item = document.createElement('div');
                        item.className = 'program-item';
                        const studentCount = prog.student_count || 0;
                        item.innerHTML = `
                            <span class="program-name">${prog.program_code || 'Unknown Program'}</span>
                            <span class="program-count">${studentCount} ${studentCount === 1 ? 'student' : 'students'}</span>
                        `;
                        programStatsContainer.appendChild(item);
                    });
                } else {
                    programStatsContainer.innerHTML = '<p class="no-data-message">No programs found for your department(s).</p>';
                }
            }
        }


        // Add backdrop click handler for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const backdrop = document.getElementById('sidebar-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        const sidebar = document.querySelector('.sidebar');
                        if (sidebar) {
                            sidebar.classList.remove('active');
                        }
                        this.style.display = 'none';
                        this.classList.remove('active');
                    }
                });
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Program Head Dashboard loaded');
            
            // Mark that this page handles sidebar functionality
            window.sidebarHandledByPage = true;
            
            // Load dynamic content
            loadDashboardData();
            
            // Handle responsive sidebar behavior
            function handleResize() {
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.getElementById('sidebar-backdrop');
                
                if (window.innerWidth > 768) {
                    // Desktop: remove mobile active state
                    if (sidebar) {
                        sidebar.classList.remove('active');
                    }
                    if (backdrop) {
                        backdrop.style.display = 'none';
                        backdrop.classList.remove('active');
                    }
                }
            }
            
            // Add resize listener
            window.addEventListener('resize', handleResize);
            
            // Initial call
            handleResize();
        });
    </script>
</body>
</html>
