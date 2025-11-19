<?php
// Online Clearance Website - Student Dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
</head>
<body>
    <?php
    /*
     * Demo session block disabled – real session data is set by Auth after login.
     * If you still want to run a standalone demo, uncomment and use role_id = 3 (Student).
     */
    // session_start();
    // $_SESSION['user_id'] = 1;
    // $_SESSION['role_id'] = 3; // Student role
    // $_SESSION['first_name'] = 'John';
    // $_SESSION['last_name'] = 'Doe';
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
                <!-- Page Header -->
                <div class="page-header">
                    <h2><i class="fas fa-chart-line"></i> Student Dashboard</h2>
                    <p class="page-description">Welcome back, John Doe</p>
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
                                <h3>15</h3>
                                <p class="stat-number">Days</p>
                                <p class="stat-label">Remaining</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3>8/11</h3>
                                <p class="stat-number">Done</p>
                                <p class="stat-label">Complete</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Action Section -->
                <div class="main-action-section">
                    <div class="action-container">
                        <button class="btn btn-primary btn-large" id="applyClearanceBtn" onclick="applyForClearance()">
                            <i class="fas fa-file-alt"></i>
                            <span id="applyBtnText">Apply for Clearance</span>
                        </button>
                        <p class="action-description" id="actionDescription">Begin your clearance application for the current semester</p>
                        <div class="clearance-period-info" id="clearancePeriodInfo" style="display: none;">
                            <i class="fas fa-calendar-check"></i>
                            <span>Clearance period is now open for 2027-2028 1st Semester</span>
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
                                    <h4>Clearance Application Submitted</h4>
                                    <p>Application submitted for 2027-2028 1st Semester</p>
                                    <span class="activity-date">Dec 15, 2024</span>
                                </div>
                            </div>
                            
                            <div class="activity-item pending">
                                <div class="activity-marker"></div>
                                <div class="activity-content">
                                    <h4>Waiting for Cashier Approval</h4>
                                    <p>Financial clearance pending approval</p>
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
                    <?php include '../../includes/components/notifications.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Apply for clearance function
    function applyForClearance() {
        const btn   = document.getElementById('applyClearanceBtn');
        const text  = document.getElementById('applyBtnText');
        const desc  = document.getElementById('actionDescription');

        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        fetch('/OnlineClearanceWebsite/api/clearance/apply_all.php', {
            method: 'POST',
            headers: { 'Content-Type':'application/json' }
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success){
                text.textContent = 'Go to My Clearance';
                btn.querySelector('i').className = 'fas fa-eye';
                desc.textContent = 'View your clearance status and progress';
                showToast(res.message || 'Clearance application submitted','success');
                setTimeout(()=>window.location.href='clearance.php',1200);
            }else{
                // If form already exists, redirect immediately
                if(res.message && res.message.includes('already')){
                    window.location.href='clearance.php';
                }else{
                    showToast(res.message || 'Error','error');
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }
            }
        })
        .catch(()=>{
            showToast('Network error','error');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    // Check if clearance period is open
    async function isClearancePeriodOpenAsync() {
        try {
            const res = await fetch('/OnlineClearanceWebsite/api/clearance/periods.php', {
                credentials:'same-origin'
            });
            const json = await res.json();
            if (!json.success || json.total === 0) return false;
            const p = json.periods[0];
            const now = new Date();
            return now >= new Date(p.start_date) && now <= new Date(p.end_date);
        } catch (e) { return false; }
    }

    // Check if user has already applied for clearance
    function hasAppliedForClearance() { return false; }

    // Initialize button state on page load
    async function initializeClearanceButton() {
        const applyBtn            = document.getElementById('applyClearanceBtn');
        const applyBtnText        = document.getElementById('applyBtnText');
        const actionDescription   = document.getElementById('actionDescription');
        const clearancePeriodInfo = document.getElementById('clearancePeriodInfo');

        const periodOpen = await isClearancePeriodOpenAsync();

        // Check live clearance status
        let applied = false;
        let manualApplied = false;
        try{
            const res  = await fetch('/OnlineClearanceWebsite/api/clearance/status.php',{credentials:'same-origin'});
            const json = await res.json();
            applied    = json.success && json.applied;
            manualApplied = json.success && json.manual_applied;
        }catch(e){ applied = false; manualApplied = false; }

        // Check for manual mode in localStorage
        const manualMode = localStorage.getItem('clearance_manual_mode') === 'true';
        const manualModeTimestamp = localStorage.getItem('clearance_manual_mode_timestamp');
        
        // If manual mode was set more than 24 hours ago, clear it (allow reset)
        if (manualMode && manualModeTimestamp) {
            const hoursSinceManual = (Date.now() - parseInt(manualModeTimestamp)) / (1000 * 60 * 60);
            if (hoursSinceManual > 24) {
                localStorage.removeItem('clearance_manual_mode');
                localStorage.removeItem('clearance_manual_mode_timestamp');
            }
        }

        // Use server truth only; no local storage sync

        if(applied){
            if(manualApplied || manualMode){
                // User has manually applied to signatories - show manual mode
                applyBtnText.textContent = 'Go to My Clearance';
                applyBtn.querySelector('i').className = 'fas fa-eye';
                actionDescription.textContent = 'View your clearance status and progress (Manual Mode)';
                applyBtn.disabled = false;
                clearancePeriodInfo.style.display = 'block';
            } else {
                // User has used mass apply - show quick link
                applyBtnText.textContent = 'Go to My Clearance';
                applyBtn.querySelector('i').className = 'fas fa-eye';
                actionDescription.textContent = 'View your clearance status and progress';
                applyBtn.disabled = false;
                clearancePeriodInfo.style.display = 'block';
            }
        }else if(!periodOpen){
            // Period closed – cannot apply
            applyBtn.disabled = true;
            applyBtnText.textContent = 'Clearance Period Closed';
            applyBtn.querySelector('i').className = 'fas fa-clock';
            actionDescription.textContent = 'Clearance period is currently closed';
        }else{
            // Period open & not applied yet – show Apply button
            applyBtn.disabled = false;
            applyBtnText.textContent = 'Apply for Clearance';
            applyBtn.querySelector('i').className = 'fas fa-file-alt';
            actionDescription.textContent = 'Begin your clearance application for the current semester';
            clearancePeriodInfo.style.display = 'block';
        }
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
        initializeClearanceButton();
    });
    </script>
</body>
</html> 