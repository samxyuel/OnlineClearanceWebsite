<?php
// Online Clearance Website - Faculty Dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php
    /* Demo session data commented out – use real login session
    session_start();
    $_SESSION['user_id'] = 2;
    $_SESSION['role_id'] = 2; // Faculty role
    $_SESSION['first_name'] = 'Jane';
    $_SESSION['last_name'] = 'Smith';
    */
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
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-name">Dr. Jane Smith</span>
                    <div class="user-dropdown">
                        <button class="dropdown-toggle">▼</button>
                        <div class="dropdown-menu">
                            <a href="profile.php">Profile</a>
                            <a href="settings.php">Settings</a>
                            <a href="logout.php">Logout</a>
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
                    <h2><i class="fas fa-chart-line"></i> Faculty Dashboard</h2>
                    <p class="page-description">Welcome back, Dr. Jane Smith</p>
                </div>

                <!-- Quick Stats Bar -->
                <div class="quick-stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Pending</h3>
                                <p class="stat-number">Clearance</p>
                                <p class="stat-label">Status</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3>1st Sem</h3>
                                <p class="stat-number">2027-2028</p>
                                <p class="stat-label">Current</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="stat-content">
                                <h3>12</h3>
                                <p class="stat-number">Days</p>
                                <p class="stat-label">Remaining</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3>6/8</h3>
                                <p class="stat-number">Done</p>
                                <p class="stat-label">Complete</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Action Section -->
                <div class="main-action-section">
                    <div class="action-container">
                        <button class="btn btn-primary btn-large" id="applyClearanceBtn" onclick="applyForFacultyClearance()">
                            <i class="fas fa-file-alt"></i>
                            <span id="applyBtnText">Apply for Clearance</span>
                        </button>
                        <p class="action-description" id="actionDescription">Begin your faculty clearance application for the current semester</p>
                        <div class="clearance-period-info" id="clearancePeriodInfo" style="display: none;">
                            <i class="fas fa-calendar-check"></i>
                            <span>Clearance period is now open for 2027-2028 1st Semester</span>
                        </div>
                        
                        <!-- Debug Section -->
                        <div class="debug-section" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <h4>Debug Information</h4>
                            <button class="btn btn-sm btn-outline" onclick="testAPIs()">Test APIs</button>
                            <button class="btn btn-sm btn-outline" onclick="checkPeriodStatus()">Check Period Status</button>
                            <div id="debugOutput" style="margin-top: 1rem; font-family: monospace; font-size: 12px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div class="quick-actions-section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="actions-grid">
                        <div class="action-card" onclick="navigateTo('clearance')">
                            <div class="card-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="card-content">
                                <h4>View Clearance</h4>
                                <p>Check your clearance status and progress</p>
                            </div>
                        </div>
                        
                        <div class="action-card" onclick="navigateTo('requirements')">
                            <div class="card-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="card-content">
                                <h4>Check Requirements</h4>
                                <p>View detailed clearance requirements</p>
                            </div>
                        </div>
                        
                        <div class="action-card" onclick="navigateTo('calendar')">
                            <div class="card-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="card-content">
                                <h4>Academic Calendar</h4>
                                <p>Important dates and deadlines</p>
                            </div>
                        </div>
                        
                        <div class="action-card" onclick="navigateTo('support')">
                            <div class="card-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="card-content">
                                <h4>Contact Support</h4>
                                <p>Get help and support</p>
                            </div>
                        </div>
                        
                        <div class="action-card" onclick="navigateTo('settings')">
                            <div class="card-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="card-content">
                                <h4>Settings</h4>
                                <p>Account and notification preferences</p>
                            </div>
                        </div>
                        
                        <div class="action-card" onclick="navigateTo('records')">
                            <div class="card-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="card-content">
                                <h4>Academic Records</h4>
                                <p>Grades, transcripts, and records</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Recent Activity Section -->
                    <div class="recent-activity-section">
                        <div class="section-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        </div>
                        <div class="activity-timeline">
                            <div class="activity-item pending">
                                <div class="activity-marker"></div>
                                <div class="activity-content">
                                    <h4>Faculty Clearance Application Submitted</h4>
                                    <p>Application submitted for 2027-2028 1st Semester</p>
                                    <span class="activity-date">Dec 15, 2024</span>
                                </div>
                            </div>
                            
                            <div class="activity-item pending">
                                <div class="activity-marker"></div>
                                <div class="activity-content">
                                    <h4>Waiting for Department Head Approval</h4>
                                    <p>Faculty clearance pending approval</p>
                                    <span class="activity-date">Dec 14, 2024</span>
                                </div>
                            </div>
                            
                            <div class="activity-item completed">
                                <div class="activity-marker"></div>
                                <div class="activity-content">
                                    <h4>Library Clearance Completed</h4>
                                    <p>All library requirements fulfilled</p>
                                    <span class="activity-date">Dec 13, 2024</span>
                                </div>
                            </div>
                            
                            <div class="activity-item completed">
                                <div class="activity-marker"></div>
                                <div class="activity-content">
                                    <h4>Financial Clearance Submitted</h4>
                                    <p>Payment verification submitted</p>
                                    <span class="activity-date">Dec 12, 2024</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Panel -->
                    <div class="notifications-section">
                        <div class="section-header">
                            <h3><i class="fas fa-bell"></i> Notifications & Alerts</h3>
                        </div>
                        <div class="notifications-list">
                            <div class="notification-item warning">
                                <div class="notification-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="notification-content">
                                    <h4>Deadline Reminder</h4>
                                    <p>Faculty clearance due in 2 days</p>
                                    <span class="notification-time">1 hour ago</span>
                                </div>
                            </div>
                            
                            <div class="notification-item info">
                                <div class="notification-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <h4>Academic Calendar Updated</h4>
                                    <p>New schedule for 2028 semester</p>
                                    <span class="notification-time">1 day ago</span>
                                </div>
                            </div>
                            
                            <div class="notification-item success">
                                <div class="notification-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <h4>Medical Clearance Approved</h4>
                                    <p>Approved by Health Services</p>
                                    <span class="notification-time">2 days ago</span>
                                </div>
                            </div>
                            
                            <div class="notification-item info">
                                <div class="notification-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div class="notification-content">
                                    <h4>Faculty Meeting</h4>
                                    <p>Department meeting on Dec 20</p>
                                    <span class="notification-time">3 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // SIMPLIFIED: Navigate to faculty clearance page (no mass apply)
    function applyForFacultyClearance() {
        // Simply redirect to clearance page
        window.location.href = 'clearance.php';
    }

    /* COMMENTED OUT - MASS APPLY FUNCTIONALITY (REMOVED FOR SIMPLICITY)
    
    // Apply for faculty clearance function - ORIGINAL MASS APPLY VERSION
    function applyForFacultyClearance() {
        const btn   = document.getElementById('applyClearanceBtn');
        const text  = document.getElementById('applyBtnText');
        const desc  = document.getElementById('actionDescription');

        btn.disabled = true;
        const origHTML = btn.innerHTML;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        fetch('/OnlineClearanceWebsite/api/clearance/apply_all.php', {
            method:'POST', headers:{'Content-Type':'application/json'}
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success){
                text.textContent='Go to My Clearance';
                btn.querySelector('i').className='fas fa-eye';
                desc.textContent='View your faculty clearance status and progress';
                showToast(res.message||'Application submitted','success');
                setTimeout(()=>window.location.href='clearance.php',1200);
            }else if(res.message && res.message.includes('already')){
                window.location.href='clearance.php';
            }else{
                showToast(res.message||'Error','error');
                btn.innerHTML=origHTML; btn.disabled=false;
            }
        })
        .catch(()=>{showToast('Network error','error'); btn.innerHTML=origHTML; btn.disabled=false;});
    }
    
    */

    async function isClearancePeriodOpenAsync() {
        try{
            const res = await fetch('/OnlineClearanceWebsite/api/clearance/periods.php',{credentials:'same-origin'});
            const json = await res.json();
            if(!json.success||json.total===0) return false;
            const p=json.periods[0];
            const now=new Date();
            return now>=new Date(p.start_date)&&now<=new Date(p.end_date);
        }catch(e){return false;}
    }
    // const clearanceStartDate = new Date('2024-12-01');
    // const clearanceEndDate   = new Date('2024-12-31');

    // SIMPLIFIED BUTTON INITIALIZATION - NO MASS APPLY LOGIC
    async function initializeClearanceButton() {
        const applyBtn  = document.getElementById('applyClearanceBtn');
        const text      = document.getElementById('applyBtnText');
        const desc      = document.getElementById('actionDescription');
        const info      = document.getElementById('clearancePeriodInfo');

        const periodOpen = await isClearancePeriodOpenAsync();

        if (periodOpen) {
            // Period is open - always show "Go to My Clearance"
            text.textContent = 'Go to My Clearance';
            applyBtn.querySelector('i').className = 'fas fa-eye';
            desc.textContent = 'View your faculty clearance status and progress';
            applyBtn.disabled = false;
            info.style.display = 'block';
        } else {
            // Period is closed
            applyBtn.disabled = true;
            text.textContent = 'Clearance Period Closed';
            applyBtn.querySelector('i').className = 'fas fa-clock';
            desc.textContent = 'Clearance period is currently closed';
        }
    }

    /* COMMENTED OUT - COMPLEX MASS APPLY BUTTON LOGIC (REMOVED FOR SIMPLICITY)
    
    // Check if user has already applied for clearance
    function hasAppliedForClearance() { return false; }

    // Original complex button initialization - COMMENTED OUT
    async function initializeClearanceButton() {
        const applyBtn  = document.getElementById('applyClearanceBtn');
        const text      = document.getElementById('applyBtnText');
        const desc      = document.getElementById('actionDescription');
        const info      = document.getElementById('clearancePeriodInfo');

        const periodOpen = await isClearancePeriodOpenAsync();

        // Live clearance status
        let applied = false;
        let manualApplied = false;
        try{
            const res = await fetch('/OnlineClearanceWebsite/api/clearance/status.php',{credentials:'same-origin'});
            const js  = await res.json();
            applied   = js.success && js.applied;
            
            // Check if user has manually applied to any signatories
            if (js.success && js.signatories) {
                manualApplied = js.signatories.some(s => s.action !== null && s.action !== '');
            }
        }catch(e){ applied=false; }

        if(applied){
            if(manualApplied){
                // User has manually applied to signatories - show manual mode
                text.textContent='Go to My Clearance';
                applyBtn.querySelector('i').className='fas fa-eye';
                desc.textContent='View your faculty clearance status and progress (Manual Mode)';
                applyBtn.disabled=false;
                info.style.display='block';
            } else {
                // User has used mass apply - show quick link
                text.textContent='Go to My Clearance';
                applyBtn.querySelector('i').className='fas fa-eye';
                desc.textContent='View your faculty clearance status and progress';
                applyBtn.disabled=false;
                info.style.display='block';
            }
        }else if(!periodOpen){
            applyBtn.disabled=true;
            text.textContent='Clearance Period Closed';
            applyBtn.querySelector('i').className='fas fa-clock';
            desc.textContent='Clearance period is currently closed';
        }else{
            // Ready to apply
            text.textContent='Apply for Clearance';
            applyBtn.querySelector('i').className='fas fa-file-alt';
            desc.textContent='Begin your faculty clearance application for the current semester';
            applyBtn.disabled=false;
            info.style.display='block';
        }
    }
    
    */
    
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

        // Initialize button state on page load
        initializeClearanceButton();
    });

    // SIMPLIFIED DEBUG FUNCTIONS - ONLY PERIOD CHECKING
    async function testAPIs() {
        const debugOutput = document.getElementById('debugOutput');
        debugOutput.innerHTML = '<p>Testing APIs...</p>';
        
        try {
            // Test periods API only
            const periodsRes = await fetch('/OnlineClearanceWebsite/api/clearance/periods.php', {credentials: 'same-origin'});
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
            const res = await fetch('/OnlineClearanceWebsite/api/clearance/periods.php', {credentials: 'same-origin'});
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

    /* COMMENTED OUT - MASS APPLY DEBUG FUNCTIONS (REMOVED FOR SIMPLICITY)
    
    // Test all APIs to see what's working - ORIGINAL VERSION WITH MASS APPLY APIS
    async function testAPIs() {
        const debugOutput = document.getElementById('debugOutput');
        debugOutput.innerHTML = '<p>Testing APIs...</p>';
        
        try {
            // Test periods API
            const periodsRes = await fetch('/OnlineClearanceWebsite/api/clearance/periods.php', {credentials: 'same-origin'});
            const periodsData = await periodsRes.json();
            debugOutput.innerHTML += `<p><strong>Periods API:</strong> ${JSON.stringify(periodsData, null, 2)}</p>`;
            
            // Test user periods API
            const userPeriodsRes = await fetch('/OnlineClearanceWebsite/api/clearance/user_periods.php', {credentials: 'same-origin'});
            const userPeriodsData = await userPeriodsRes.json();
            debugOutput.innerHTML += `<p><strong>User Periods API:</strong> ${JSON.stringify(userPeriodsData, null, 2)}</p>`;
            
            // Test status API
            const statusRes = await fetch('/OnlineClearanceWebsite/api/clearance/status.php', {credentials: 'same-origin'});
            const statusData = await statusRes.json();
            debugOutput.innerHTML += `<p><strong>Status API:</strong> ${JSON.stringify(statusData, null, 2)}</p>`;
            
        } catch (error) {
            debugOutput.innerHTML += `<p><strong>Error:</strong> ${error.message}</p>`;
        }
    }
    
    */
    </script>
</body>
</html> 