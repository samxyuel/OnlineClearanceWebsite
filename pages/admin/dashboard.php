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
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
</head>
<body>
    <?php
    // Session management handled by header component
    ?>
    
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
                            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
                            <p>Welcome to the admin dashboard. Monitor system status and manage clearance operations.</p>
                        </div>

                    <!-- Active Period Status -->
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
                            
                            <div class="status-actions">
                                <button class="btn btn-success" onclick="showAddSchoolYearModal()" id="addSchoolYearBtn">
                                    <i class="fas fa-plus"></i> Add School Year
                                </button>
                                <button class="btn btn-primary" onclick="toggleTermStatus()" id="termToggleBtn">
                                    <i class="fas fa-play"></i> <span id="termToggleText">Activate Term</span>
                                </button>
                                <a href="ClearanceManagement.php" class="btn btn-outline-primary">
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
                            <div class="stat-icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="totalStaffStat">0</h3>
                                <p>Total Staff</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="activeClearancesStat">0</h3>
                                <p>Active Clearances</p>
                            </div>
                        </div>
                    </div>


                    <!-- Recent Activity -->
                    <?php include '../../includes/components/recent-activity.php'; ?>
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
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
    
    <script>
        // Load dashboard data on page load
        async function loadDashboardData() {
            try {
                // Load current academic context
                const contextResponse = await fetch('../../api/clearance/context.php', {
                    credentials: 'include'
                });
                const contextData = await contextResponse.json();
                
                if (contextData.success) {
                    updateAcademicYearDisplay(contextData);
                }
                
                // Load sector periods data
                const periodsResponse = await fetch('../../api/clearance/sector-periods.php', {
                    credentials: 'include'
                });
                const periodsData = await periodsResponse.json();
                
                if (periodsData.success) {
                    updateSectorStatusDisplay(periodsData.periods_by_sector || {});
                }
                
                // Load statistics
                await loadDashboardStatistics();
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showToast('Failed to load dashboard data', 'error');
            }
        }
        
        // Update academic year and term display
        function updateAcademicYearDisplay(contextData) {
            const academicYear = contextData.academic_year;
            const activeTerm = contextData.terms?.find(term => term.is_active === 1);
            
            if (academicYear) {
                document.getElementById('currentAcademicYear').textContent = academicYear.year;
            }
            
            if (activeTerm) {
                document.getElementById('currentActiveTerm').textContent = activeTerm.semester_name;
                document.getElementById('termDuration').textContent = `Active since: ${new Date(activeTerm.created_at).toLocaleDateString()}`;
                
                // Update term toggle button
                const termToggleBtn = document.getElementById('termToggleBtn');
                const termToggleText = document.getElementById('termToggleText');
                
                termToggleBtn.className = 'btn btn-danger';
                termToggleBtn.innerHTML = '<i class="fas fa-stop"></i> <span id="termToggleText">End Term</span>';
                termToggleBtn.onclick = () => endCurrentTerm();
            } else {
                document.getElementById('currentActiveTerm').textContent = 'No Active Term';
                document.getElementById('termDuration').textContent = 'No active term found';
                
                // Update term toggle button
                const termToggleBtn = document.getElementById('termToggleBtn');
                termToggleBtn.className = 'btn btn-success';
                termToggleBtn.innerHTML = '<i class="fas fa-play"></i> <span id="termToggleText">Activate Term</span>';
                termToggleBtn.onclick = () => activateCurrentTerm();
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
                const sectorPeriods = periodsBySector[sector.key];
                
                if (sectorPeriods && sectorPeriods.length > 0) {
                    const latestPeriod = sectorPeriods[0];
                    const status = latestPeriod.status || 'Not Started';
                    statusElement.textContent = status;
                    statusElement.className = `sector-status status-${status.toLowerCase().replace(' ', '-')}`;
                } else {
                    statusElement.textContent = 'Not Started';
                    statusElement.className = 'sector-status status-not-started';
                }
            });
        }
        
        // Load dashboard statistics
        async function loadDashboardStatistics() {
            try {
                const response = await fetch('../../api/dashboard/admin_summary.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    updateStatisticsDisplay(data.data);
                    updateQuickStats(data.data);
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
                // Use fallback data
                updateStatisticsDisplay({
                    college: { applied: 0, completed: 0 },
                    shs: { applied: 0, completed: 0 },
                    faculty: { applied: 0, completed: 0 }
                });
                updateQuickStats({
                    total_users: {
                        total_students: 0,
                        total_faculty: 0,
                        total_staff: 0,
                        active_clearances: 0
                    }
                });
            }
        }
        
        // Update statistics display
        function updateStatisticsDisplay(stats) {
            // College stats
            const collegeApplied = stats.college?.applied || 0;
            const collegeCompleted = stats.college?.completed || 0;
            const collegeRate = collegeApplied > 0 ? Math.round((collegeCompleted / collegeApplied) * 100) : 0;
            document.getElementById('college-stats').textContent = 
                `College: ${collegeApplied} applied, ${collegeCompleted} completed (${collegeRate}%)`;
            
            // SHS stats
            const shsApplied = stats.shs?.applied || 0;
            const shsCompleted = stats.shs?.completed || 0;
            const shsRate = shsApplied > 0 ? Math.round((shsCompleted / shsApplied) * 100) : 0;
            document.getElementById('shs-stats').textContent = 
                `Senior High School: ${shsApplied} applied, ${shsCompleted} completed (${shsRate}%)`;
            
            // Faculty stats
            const facultyApplied = stats.faculty?.applied || 0;
            const facultyCompleted = stats.faculty?.completed || 0;
            const facultyRate = facultyApplied > 0 ? Math.round((facultyCompleted / facultyApplied) * 100) : 0;
            document.getElementById('faculty-stats').textContent = 
                `Faculty: ${facultyApplied} applied, ${facultyCompleted} completed (${facultyRate}%)`;
        }
        
        // Update Quick Stats Dashboard
        function updateQuickStats(data) {
            const totalUsers = data.total_users || {};
            
            // Update Total Students
            const totalStudentsEl = document.getElementById('totalStudentsStat');
            if (totalStudentsEl) {
                totalStudentsEl.textContent = (totalUsers.total_students || 0).toLocaleString();
            }
            
            // Update Total Faculty
            const totalFacultyEl = document.getElementById('totalFacultyStat');
            if (totalFacultyEl) {
                totalFacultyEl.textContent = (totalUsers.total_faculty || 0).toLocaleString();
            }
            
            // Update Total Staff
            const totalStaffEl = document.getElementById('totalStaffStat');
            if (totalStaffEl) {
                totalStaffEl.textContent = (totalUsers.total_staff || 0).toLocaleString();
            }
            
            // Update Active Clearances
            const activeClearancesEl = document.getElementById('activeClearancesStat');
            if (activeClearancesEl) {
                activeClearancesEl.textContent = (totalUsers.active_clearances || 0).toLocaleString();
            }
        }
        
        // Toggle term status (activate or end)
        function toggleTermStatus() {
            // This function will be overridden by the specific activate/end functions
            console.log('Toggle term status called');
        }
        
        // Activate current term
        async function activateCurrentTerm() {
            try {
                showToast('Activating term...', 'info');
                
                // Get available terms
                const response = await fetch('../../api/clearance/context.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.terms) {
                    // Find the first inactive term
                    const inactiveTerm = data.terms.find(term => term.is_active === 0);
                    
                    if (inactiveTerm) {
                        // Activate the term
                        const activateResponse = await fetch('../../api/clearance/periods.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                semester_id: inactiveTerm.semester_id,
                                action: 'activate_semester'
                            })
                        });
                        
                        const activateData = await activateResponse.json();
                        
                        if (activateData.success) {
                            showToast('Term activated successfully!', 'success');
                            // Reload dashboard data
                            await loadDashboardData();
                        } else {
                            throw new Error(activateData.message || 'Failed to activate term');
                        }
                    } else {
                        showToast('No inactive terms available to activate', 'warning');
                    }
                } else {
                    throw new Error('Failed to load term data');
                }
            } catch (error) {
                console.error('Error activating term:', error);
                showToast(error.message || 'Failed to activate term', 'error');
            }
        }
        
        // End current term
        async function endCurrentTerm() {
            const confirmed = await showConfirmationModal(
                'End Current Term',
                'Are you sure you want to end the current term? This will permanently conclude all clearance activities.',
                'End Term',
                'Cancel',
                'danger'
            );
            
            if (confirmed) {
                try {
                    showToast('Ending term...', 'info');
                    
                    // Get current active term
                    const response = await fetch('../../api/clearance/context.php', {
                        credentials: 'include'
                    });
                    const data = await response.json();
                    
                    if (data.success && data.terms) {
                        const activeTerm = data.terms.find(term => term.is_active === 1);
                        
                        if (activeTerm) {
                            // End the term
                            const endResponse = await fetch('../../api/clearance/periods.php', {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                credentials: 'include',
                                body: JSON.stringify({
                                    semester_id: activeTerm.semester_id,
                                    action: 'end_semester'
                                })
                            });
                            
                            const endData = await endResponse.json();
                            
                            if (endData.success) {
                                showToast('Term ended successfully!', 'success');
                                // Reload dashboard data
                                await loadDashboardData();
                            } else {
                                throw new Error(endData.message || 'Failed to end term');
                            }
                        } else {
                            showToast('No active term found to end', 'warning');
                        }
                    } else {
                        throw new Error('Failed to load term data');
                    }
                } catch (error) {
                    console.error('Error ending term:', error);
                    showToast(error.message || 'Failed to end term', 'error');
                }
            }
        }

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
            console.log('Admin Dashboard loaded');

            // Mark that this page handles sidebar functionality
            window.sidebarHandledByPage = true;

            // Initialize Activity Tracker (only if not already initialized)
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized');
            }
            
            // Load dashboard data
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