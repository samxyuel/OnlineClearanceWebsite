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
                    <h2><i class="fas fa-chart-line"></i> Dashboard - Welcome back, <?php echo $display_name; ?></h2>
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
                            <div class="user-identifiers">
                                <span class="identifier-item" id="userIdentifier" style="display: none;">
                                    <span class="identifier-label" id="identifierLabel"></span>
                                    <span class="identifier-value" id="identifierValue"></span>
                                </span>
                                <span class="identifier-item" id="formIdIndicator" style="display: none;">
                                    <span class="identifier-label">Form ID:</span>
                                    <span class="identifier-value" id="clearanceFormId">--</span>
                                </span>
                            </div>
                        </div>
                    </div>
                        
                    <!-- User Context Block (Card layout) -->
                    <div class="user-context-inline">
                        <div class="context-item">
                            <span class="context-label"><i class="fas fa-graduation-cap"></i> Sector</span>
                            <span class="context-value" id="userSector"><?php echo $user_sector; ?></span>
                        </div>
                        <div class="context-item">
                            <span class="context-label"><i class="fas fa-building"></i> Department</span>
                            <span class="context-value" id="userDepartment">Loading...</span>
                        </div>
                        <div class="context-item">
                            <span class="context-label"><i class="fas fa-book"></i> Program</span>
                            <span class="context-value" id="userProgram">Loading...</span>
                        </div>
                    </div>

                    <!-- Priority Action (Central focus) -->
                    <div class="priority-action-compact">
                        <button class="btn-primary-compact" id="applyClearanceBtn" onclick="handleClearanceAction()">
                            <i class="fas fa-file-alt"></i>
                            <span id="applyBtnText">Apply for Clearance</span>
                        </button>
                        <div class="action-status-text" id="actionStatusIndicator">
                            <span id="actionStatusText">Checking clearance period...</span>
                        </div>
                    </div>
                    
                    <!-- Clearance Status Summary (simplified) -->
                    <div class="clearance-status-inline">
                        <div class="status-item">
                            <span class="status-label">Status:</span>
                            <span class="status-value" id="clearanceStatus">Loading...</span>
                        </div>
                        <span class="status-divider">•</span>
                        <div class="status-item">
                            <span class="status-label">Progress:</span>
                            <span class="status-value" id="clearanceProgress">--/--</span>
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
            </div>
        </div>
    </main>

    <script src="../../assets/js/base-path.js"></script>
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
            const currentSemesterEl = document.getElementById('currentSemester');
            const currentAcademicYearEl = document.getElementById('currentAcademicYear');
            const termDurationEl = document.getElementById('termDuration');
            
            if (data.period) {
                if (currentSemesterEl) currentSemesterEl.textContent = data.period.semester_name || '--';
                if (currentAcademicYearEl) currentAcademicYearEl.textContent = data.period.academic_year || '--';
                if (termDurationEl) termDurationEl.textContent = `Duration: ${data.period.start_date} to ${data.period.end_date}`;
            } else {
                if (currentSemesterEl) currentSemesterEl.textContent = 'N/A';
                if (currentAcademicYearEl) currentAcademicYearEl.textContent = 'No Active Period';
                if (termDurationEl) termDurationEl.textContent = 'No active clearance period';
            }

            // Update User Identifiers
            const userIdentifier = document.getElementById('userIdentifier');
            const identifierLabel = document.getElementById('identifierLabel');
            const identifierValue = document.getElementById('identifierValue');
            const formIdIndicator = document.getElementById('formIdIndicator');
            const formIdValue = document.getElementById('clearanceFormId');

            if (userIdentifier && identifierLabel && identifierValue) {
                if (data.student_number) {
                    identifierLabel.textContent = 'Student #:';
                    identifierValue.textContent = data.student_number;
                    userIdentifier.style.display = 'flex';
                } else if (data.employee_number) {
                    identifierLabel.textContent = 'Employee #:';
                    identifierValue.textContent = data.employee_number;
                    userIdentifier.style.display = 'flex';
                } else {
                    userIdentifier.style.display = 'none';
                }
            }

            if (formIdIndicator && formIdValue) {
                if (data.clearance_form_id) {
                    formIdValue.textContent = data.clearance_form_id;
                    formIdIndicator.style.display = 'flex';
                } else {
                    formIdIndicator.style.display = 'none';
                }
            }

            // Update Status Cards
            const clearanceStatusEl = document.getElementById('clearanceStatus');
            const clearanceProgressEl = document.getElementById('clearanceProgress');
            if (clearanceStatusEl) {
                clearanceStatusEl.textContent = data.clearance?.status || 'Not Started';
            }
            if (clearanceProgressEl) {
                clearanceProgressEl.textContent = data.clearance?.progress_text || '--/--';
            }
            
            // Update Main Action Button
            updateMainActionButton(data);

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
        const statusText = document.getElementById('actionStatusText');
        
        if (!btn || !text) return;
        
        const icon = btn.querySelector('i');

        // Check if there's an active period that has actually started
        const hasActivePeriod = data.period && data.period.start_date;
        let periodHasStarted = false;
        
        if (hasActivePeriod) {
            // Check if the period has actually started by parsing the start_date
            try {
                const startDate = new Date(data.period.start_date);
                const now = new Date();
                // Check if start_date is valid and not in the far past (like -0001)
                if (!isNaN(startDate.getTime()) && startDate.getFullYear() > 1900 && startDate <= now) {
                    periodHasStarted = true;
                }
            } catch (e) {
                // Invalid date, treat as not started
                periodHasStarted = false;
            }
        }

        if (!hasActivePeriod || !periodHasStarted) {
            // No active period or period hasn't started yet
            btn.disabled = false;
            text.textContent = 'See My Clearance page.';
            if (icon) icon.className = 'fas fa-eye';
            btn.title = 'View your clearance records.';
            
            if (statusText) {
                statusText.textContent = 'View your Clearances';
            }
        } else {
            // Active period exists and has started
            text.textContent = 'Apply for Clearance';
            if (icon) icon.className = 'fas fa-file-alt';
            btn.title = 'Begin your clearance application for the current semester.';
            btn.disabled = false;
            
            if (statusText) {
                statusText.textContent = 'Clearance period is open — Apply now!';
            }
        }
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
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const mainContent = document.querySelector('.main-content');
        
        if (!sidebar || !mainContent) return;
        
        if (window.innerWidth <= 768) {
            // Mobile: toggle active class and backdrop
            sidebar.classList.toggle('active');
            if (backdrop) {
                backdrop.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
            }
        } else {
            // Desktop: toggle collapsed class
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Ensure backdrop is hidden on desktop
            if (backdrop) {
                backdrop.style.display = 'none';
            }
        }
    }

    // Debug functions (only for faculty)
    async function testAPIs() {
        const debugOutput = document.getElementById('debugOutput');
        if (!debugOutput) return;
        
        debugOutput.innerHTML = '<p>Testing APIs...</p>';
        
        try {
            // Test periods API only
            const periodsRes = await fetch(`${getApiUrl('api/clearance/periods.php')}?sector=${userInfo.sector}`, {credentials: 'same-origin'});
            const periodsData = await periodsRes.json();
            debugOutput.innerHTML += `<p><strong>Periods API:</strong> ${JSON.stringify(periodsData, null, 2)}</p>`;
            
        } catch (error) {
            debugOutput.innerHTML += `<p><strong>Error:</strong> ${error.message}</p>`;
        }
    }

    // Check period status specifically
    async function checkPeriodStatus() {
        const debugOutput = document.getElementById('debugOutput');
        if (!debugOutput) return;
        
        debugOutput.innerHTML = '<p>Checking period status...</p>';
        
        try {
            const res = await fetch(`${getApiUrl('api/clearance/periods.php')}?sector=${userInfo.sector}`, {credentials: 'same-origin'});
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
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        
        // Close sidebar when clicking backdrop
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                if (sidebar && window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    this.style.display = 'none';
                }
            });
        }
        
        // Handle window resize for sidebar with debounce
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 768) {
                    // Desktop: ensure backdrop is hidden and sidebar is not active
                    if (sidebar) sidebar.classList.remove('active');
                    if (backdrop) backdrop.style.display = 'none';
                }
            }, 250);
        });

        // Initialize clearance button state
        loadDashboardData();
    });
    </script>
</body>
</html>
