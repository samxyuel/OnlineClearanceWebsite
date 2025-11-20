<?php
// Online Clearance Website - Unified End-User Dashboard
// Handles both Students (College & SHS) and Faculty dynamically
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
</head>
<body>
    <?php
    // Start session and get user information
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    // TODO: Uncomment when login authentication is integrated
    /*
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../pages/auth/login.php');
        exit();
    }
    */
    
    // Get user information
    // TODO: Remove fallback values when login authentication is integrated
    $user_id = $_SESSION['user_id'] ?? 118; // Fallback to demo user
    $user_type = $_SESSION['user_type'] ?? 'student'; // 'student' or 'faculty'
    $first_name = $_SESSION['first_name'] ?? 'Alex'; // Fallback for demo
    $last_name = $_SESSION['last_name'] ?? 'Garcia'; // Fallback for demo
    
    // Set up session variables for header and sidebar components
    // This ensures the header and sidebar work properly
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $user_id;
    }
    if (!isset($_SESSION['first_name'])) {
        $_SESSION['first_name'] = $first_name;
    }
    if (!isset($_SESSION['last_name'])) {
        $_SESSION['last_name'] = $last_name;
    }
    if (!isset($_SESSION['role_name'])) {
        $_SESSION['role_name'] = ucfirst($user_type);
    }
    if (!isset($_SESSION['user_type'])) {
        $_SESSION['user_type'] = $user_type;
    }
    
    // Determine user sector dynamically
    $user_sector = 'College'; // Default, will be determined by API
    
    // Demo session for testing - using SHS student Alex Garcia
    // TODO: Remove this when real authentication is working
    if ($user_id == 118) {
        $user_sector = 'Senior High School';
    }
    
    // Set user display name
    $display_name = trim($first_name . ' ' . $last_name);
    if (empty($display_name)) {
        $display_name = ucfirst($user_type);
    }
    ?>
    
    <!-- Include Dynamic Header -->
    <?php include '../../includes/components/header.php'; ?>

    <!-- Main Content Area -->
    <main class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Page Header (Minimal) -->
                <div class="page-header-compact">
                    <h2><i class="fas fa-chart-line"></i><!-- < ?php echo ucfirst($user_type); ?> --> Dashboard - Welcome back, <?php echo $display_name; ?></h2>
                </div>

                <!-- User Clearance Status Card (Compact) -->
                <div class="card-compact">
                    <!-- Status Header (Condensed) -->
                    <div class="status-header-compact">
                        <div class="academic-info">
                            <span class="academic-year-semester">
                                <i class="fas fa-calendar-check"></i> 
                                <span id="currentAcademicYear">Loading...</span> - <span id="currentSemester">Loading...</span>
                            </span>
                            <span class="term-duration" id="termDuration">Loading term information...</span>
                            </div>
                        </div>
                        
                    <!-- User Context Block (Inline text) -->
                    <div class="user-context-inline">
                        <span class="context-item">
                            <i class="fas fa-graduation-cap"></i> Sector: <span id="userSector"><?php echo $user_sector; ?></span>
                        </span>
                        <span class="context-separator">|</span>
                        <span class="context-item">
                            <i class="fas fa-building"></i> Department: <span id="userDepartment">Loading...</span>
                        </span>
                        <span class="context-separator">|</span>
                        <span class="context-item">
                            <i class="fas fa-book"></i> Program: <span id="userProgram">Loading...</span>
                        </span>
                    </div>

                    <!-- Priority Action (Central focus) -->
                    <div class="priority-action-compact">
                        <button class="btn-primary-compact" id="applyClearanceBtn" onclick="handleClearanceAction()">
                            <i class="fas fa-file-alt"></i>
                            <span id="applyBtnText">Apply for Clearance</span>
                        </button>
                        <div class="period-info-compact" id="clearancePeriodInfo" style="display: none;">
                            <i class="fas fa-calendar-check"></i>
                            <span>Clearance period is now open</span>
                        </div>
                    </div>
                    
                    <!-- Status Row (Compact horizontal cards) -->
                    <div class="status-row-compact">
                        <div class="status-card-compact">
                            <i class="fas fa-clock"></i>
                            <div class="status-info">
                                <span class="status-value" id="clearanceStatus">Loading...</span>
                                <span class="status-label">Status</span>
                            </div>
                        </div>
                        <div class="status-card-compact">
                            <i class="fas fa-check-circle"></i>
                            <div class="status-info">
                                <span class="status-value" id="clearanceProgress">--/--</span>
                                <span class="status-label">Progress</span>
                            </div>
                        </div>
                        <div class="status-card-compact">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="status-info">
                                <span class="status-value" id="periodStatus">Active</span>
                                <span class="status-label">Period</span>
                            </div>
                        </div>
                        </div>
                        
                        <!-- Debug Section (only for faculty) -->
                        <?php if ($user_type === 'faculty'): ?>
                    <div class="debug-section-compact">
                            <h4>Debug Information</h4>
                            <button class="btn btn-sm btn-outline" onclick="testAPIs()">Test APIs</button>
                            <button class="btn btn-sm btn-outline" onclick="checkPeriodStatus()">Check Period Status</button>
                        <div id="debugOutput"></div>
                        </div>
                        <?php endif; ?>
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

    <script>
    // --- Dashboard Data Loading ---
    async function loadDashboardData() {
        try {
            const response = await fetch('../../api/dashboard/summary.php', { credentials: 'include' });
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to load dashboard data.');
            }

            const data = result.data;

            // Update Academic Info
            if (data.period) {
                document.getElementById('currentSemester').textContent = data.period.semester_name || '--';
                document.getElementById('currentAcademicYear').textContent = data.period.academic_year || '--';
                document.getElementById('termDuration').textContent = `Duration: ${data.period.start_date} to ${data.period.end_date}`;
                const periodName = `${data.period.academic_year} ${data.period.semester_name}`;
                document.querySelector('#clearancePeriodInfo span').textContent = `Clearance period is now open for ${periodName}`;
                document.getElementById('clearancePeriodInfo').style.display = 'block';
            } else {
                document.getElementById('currentSemester').textContent = 'N/A';
                document.getElementById('currentAcademicYear').textContent = 'No Active Period';
                document.getElementById('termDuration').textContent = 'No active clearance period';
                document.getElementById('clearancePeriodInfo').style.display = 'none';
            }

            // Update Status Cards
            document.getElementById('clearanceStatus').textContent = data.clearance.status || 'Not Started';
            document.getElementById('clearanceProgress').textContent = data.clearance.progress_text || '--/--';
            
            // Update Period Status
            const periodStatusEl = document.getElementById('periodStatus');
            if (data.period) {
                periodStatusEl.textContent = 'Active';
            } else {
                periodStatusEl.textContent = 'Inactive';
            }

            // Update Main Action Button
            updateMainActionButton(data);

            // Update Recent Activity
            updateRecentActivity(data.recent_activity);

            // Update User Information Indicators
            updateUserInfoIndicators(data);

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            showToast('Could not load dashboard data.', 'error');
        }
    }

    function updateMainActionButton(data) {
        const btn = document.getElementById('applyClearanceBtn');
        const text = document.getElementById('applyBtnText');

        const icon = btn.querySelector('i');

        if (!data.period) { // No active period
            btn.disabled = true;
            text.textContent = 'Clearance Period Closed';
            icon.className = 'fas fa-clock';
            btn.title = 'There is no active clearance period.';
        } else if (data.clearance.status !== 'Not Started' && data.clearance.status !== 'Unapplied') {
            // Already applied
            text.textContent = 'Go to My Clearance';
            icon.className = 'fas fa-eye';
            btn.title = 'View your clearance status and progress.';
            btn.disabled = false;
        } else {
            // Can apply
            text.textContent = 'Apply for Clearance';
            icon.className = 'fas fa-file-alt';
            btn.title = 'Begin your clearance application for the current semester.';
            btn.disabled = false;
        }
    }

    function updateRecentActivity(activities) {
        const timeline = document.getElementById('activityTimeline');
        if (!activities || activities.length === 0) {
            timeline.innerHTML = '<div class="activity-item"><div class="activity-content"><p>No recent activity.</p></div></div>';
            return;
        }

        timeline.innerHTML = activities.map(activity => {
            let iconClass = 'fas fa-info-circle';
            let statusClass = 'pending';
            let title = `Update from ${activity.designation_name}`;

            if (activity.action === 'Approved') {
                iconClass = 'fas fa-check-circle';
                statusClass = 'completed';
                title = `${activity.designation_name} Approved`;
            } else if (activity.action === 'Rejected') {
                iconClass = 'fas fa-times-circle';
                statusClass = 'rejected';
                title = `${activity.designation_name} Rejected`;
            }

            const date = new Date(activity.date_signed);
            const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            return `
                <div class="activity-item ${statusClass}">
                    <div class="activity-marker"></div>
                    <div class="activity-content">
                        <h4>${title}</h4>
                        <p>Action recorded for your clearance form.</p>
                        <span class="activity-date">${formattedDate}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateUserInfoIndicators(data) {
        // Update Department
        const departmentEl = document.getElementById('userDepartment');
        if (departmentEl && data.department) {
            departmentEl.textContent = data.department;
        } else if (departmentEl) {
            departmentEl.textContent = 'Not Assigned';
        }

        // Update Program
        const programEl = document.getElementById('userProgram');
        if (programEl && data.program) {
            programEl.textContent = data.program;
        } else if (programEl) {
            programEl.textContent = 'Not Assigned';
        }

        // Sector is already set from PHP, but we can update it if needed
        const sectorEl = document.getElementById('userSector');
        if (sectorEl && data.sector) {
            sectorEl.textContent = data.sector;
        }
    }


    // User information from PHP
    const userInfo = {
        id: <?php echo $user_id; ?>,
        type: '<?php echo $user_type; ?>',
        sector: '<?php echo $user_sector; ?>',
        firstName: '<?php echo $first_name; ?>',
        lastName: '<?php echo $last_name; ?>'
    };

    // Handle clearance action based on user type
    function handleClearanceAction() {
        window.location.href = 'clearance.php';
    }

    // Apply for student clearance function (mass apply)
    function applyForStudentClearance() {
        // This function is now deprecated. The main action button directly navigates
        // to the clearance page.
        console.warn('applyForStudentClearance() is deprecated. Navigating directly.');
        window.location.href = 'clearance.php';
    }
    
    // Navigation function
    function navigateTo(page) {
        const routes = {
            'clearance': 'clearance.php',
            'requirements': 'requirements.php',
            'calendar': 'calendar.php',
            'support': 'support.php',
            'settings': 'settings.php',
            'records': 'records.php'
        };
        
        if (routes[page]) {
            showToast(`Navigating to ${page}...`, 'info');
            setTimeout(() => {
                window.location.href = routes[page];
            }, 500);
        } else {
            showToast('Page under development', 'info');
        }
    }
    
    // Toast notification function
    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Sidebar toggle function
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const mainContent = document.querySelector('.main-content');
        
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            if (sidebar) {
                sidebar.classList.toggle('active');
                if (backdrop) {
                    if (sidebar.classList.contains('active')) {
                        backdrop.style.display = 'block';
                    } else {
                        backdrop.style.display = 'none';
                    }
                }
            }
        } else {
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                if (backdrop) {
                    backdrop.style.display = 'none';
                }
                if (mainContent) {
                    mainContent.classList.remove('full-width');
                }
            } else {
                sidebar.classList.add('collapsed');
                if (backdrop) {
                    backdrop.style.display = 'none';
                }
                if (mainContent) {
                    mainContent.classList.add('full-width');
                }
            }
        }
    }

    // Debug functions (only for faculty)
    async function testAPIs() {
        const debugOutput = document.getElementById('debugOutput');
        debugOutput.innerHTML = '<p>Testing APIs...</p>';
        
        try {
            // Test periods API only
            const periodsRes = await fetch(`/OnlineClearanceWebsite/api/clearance/periods.php?sector=${userInfo.sector}`, {credentials: 'same-origin'});
            const periodsData = await periodsRes.json();
            debugOutput.innerHTML += `<p><strong>Periods API:</strong> ${JSON.stringify(periodsData, null, 2)}</p>`;
            
        } catch (error) {
            debugOutput.innerHTML += `<p><strong>Error:</strong> ${error.message}</p>`;
        }
    }

    // Check period status specifically
    async function checkPeriodStatus() {
        const debugOutput = document.getElementById('debugOutput');
        debugOutput.innerHTML = '<p>Checking period status...</p>';
        
        try {
            const res = await fetch(`/OnlineClearanceWebsite/api/clearance/periods.php?sector=${userInfo.sector}`, {credentials: 'same-origin'});
            const json = await res.json();
            
            if (json.success && json.total > 0) {
                const p = json.periods[0];
                const now = new Date();
                const startDate = new Date(p.start_date);
                const endDate = new Date(p.end_date);
                const isOpen = now >= startDate && now <= endDate;
                
                debugOutput.innerHTML += `
                    <p><strong>Period:</strong> ${p.year} ${p.semester_name}</p>
                    <p><strong>Start:</strong> ${p.start_date}</p>
                    <p><strong>End:</strong> ${p.end_date}</p>
                    <p><strong>Current Time:</strong> ${now.toISOString()}</p>
                    <p><strong>Is Open:</strong> ${isOpen}</p>
                `;
            } else {
                debugOutput.innerHTML += '<p>No periods found</p>';
            }
        } catch (error) {
            debugOutput.innerHTML += `<p><strong>Error:</strong> ${error.message}</p>`;
        }
    }
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        
        // Close sidebar when clicking backdrop
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    this.style.display = 'none';
                }
            });
        }
        
        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                if (backdrop) {
                    backdrop.style.display = 'none';
                }
            }
        });

        // Initialize clearance button state
        loadDashboardData();
    });
    </script>
</body>
</html>
