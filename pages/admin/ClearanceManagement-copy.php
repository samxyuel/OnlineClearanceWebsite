<?php
// Online Clearance Website - Admin Clearance Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session data is handled by authentication system
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Management - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="../../assets/css/sector-clearance.css">
    <link rel="stylesheet" href="../../assets/css/grace-period-monitoring.css">
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
                            <h2><i class="fas fa-clipboard-check"></i> Clearance Management</h2>
                            <p>Manage clearance periods, signatories, and monitor clearance statistics</p>
                        </div>

                        <!-- Grace Period Monitoring Section -->
                        <!-- TODO: Uncomment when grace period functionality is ready -->
                        <!--
                        <div class="grace-period-monitoring" id="grace-period-monitoring">
                            <div class="monitoring-header">
                                <h3><i class="fas fa-clock"></i> Grace Period Monitoring</h3>
                                <p>Monitor system transitions and grace periods across all clearance sectors</p>
                            </div>
                            <div class="grace-period-grid" id="grace-period-grid">
                                <-- Grace period cards will be populated by JavaScript --
                            </div>
                        </div
                        -->

                <!-- Sector-Based Clearance Management -->
                <div class="sector-clearance-management">
                    <!-- School Years & Terms Card -->
                    <div class="sector-period-card academic-year-sector" id="academic-year-card">
                        <div class="sector-card-header">
                            <div class="sector-info">
                                <h3><i class="fas fa-calendar-alt"></i> Academic Year & Terms</h3>
                                <div class="sector-status">
                                    <span class="status-badge" id="academic-year-status-badge">Current</span>
                                </div>
                            </div>
                            <div class="sector-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="showViewPastClearancesModal()">
                                    <i class="fas fa-history"></i> View Past Clearances
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="showAddSchoolYearModal()">
                                    <i class="fas fa-plus"></i> Add Year
                                </button>
                            </div>
                        </div>
                        
                        <div class="sector-card-content">
                            <!-- Academic Year Details -->
                            <div class="period-details">
                                <div class="detail-item">
                                    <span class="detail-label">Current Year:</span>
                                    <span class="detail-value" id="currentYearName">2024-2025</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" id="currentYearStatus">Active</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Terms:</span>
                                    <span class="detail-value" id="total-terms">2</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Active Terms:</span>
                                    <span class="detail-value" id="active-terms">1</span>
                                </div>
                            </div>
                            
                            <!-- Terms List -->
                            <div class="sector-signatories">
                                <div class="signatories-header">
                                    <h4><i class="fas fa-list"></i> Terms Overview</h4>
                                </div>
                                <div class="terms-list" id="terms-list">
                                    <!-- Terms will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Statistics Card -->
                    <div class="sector-period-card statistics-sector" id="statistics-card">
                        <div class="sector-card-header">
                            <div class="sector-info">
                                <h3><i class="fas fa-chart-bar"></i> System Statistics</h3>
                                <div class="sector-status">
                                    <span class="status-badge" id="statistics-status-badge">Live</span>
                                </div>
                            </div>
                            <div class="sector-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshStatistics()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="openExportModal()">
                                    <i class="fas fa-file-export"></i> Export
                                </button>
                            </div>
                            </div>
                            
                        <div class="sector-card-content">
                            <!-- Statistics Details -->
                            <div class="period-details">
                                <div class="detail-item">
                                    <span class="detail-label">Total Students:</span>
                                    <span class="detail-value" id="total-students">45</span>
                            </div>
                                <div class="detail-item">
                                    <span class="detail-label">Total Faculty:</span>
                                    <span class="detail-value" id="total-faculty">12</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Applied:</span>
                                    <span class="detail-value" id="total-applied">32</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Completed:</span>
                                    <span class="detail-value" id="total-completed">28</span>
                        </div>
                    </div>

                            <!-- Sector Breakdown -->
                            <div class="sector-signatories">
                                <div class="signatories-header">
                                    <h4><i class="fas fa-chart-pie"></i> Sector Breakdown</h4>
                        </div>
                                <div class="statistics-breakdown" id="statistics-breakdown">
                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            <i class="fas fa-university"></i> College
                                </div>
                                        <div class="breakdown-stats">
                                            <span class="breakdown-stat">Students: <strong>25</strong></span>
                                            <span class="breakdown-stat">Applied: <strong>18</strong></span>
                                            <span class="breakdown-stat">Completed: <strong>15</strong></span>
                                </div>
                            </div>
                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            <i class="fas fa-graduation-cap"></i> Senior High School
                                        </div>
                                        <div class="breakdown-stats">
                                            <span class="breakdown-stat">Students: <strong>20</strong></span>
                                            <span class="breakdown-stat">Applied: <strong>14</strong></span>
                                            <span class="breakdown-stat">Completed: <strong>13</strong></span>
                                        </div>
                                    </div>
                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            <i class="fas fa-chalkboard-teacher"></i> Faculty
                                        </div>
                                        <div class="breakdown-stats">
                                            <span class="breakdown-stat">Faculty: <strong>12</strong></span>
                                            <span class="breakdown-stat">Applied: <strong>8</strong></span>
                                            <span class="breakdown-stat">Completed: <strong>7</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sector-Based Clearance Periods -->
                    <div class="sector-clearance-periods">
                        <!-- College Clearance Period Card -->
                        <div class="sector-period-card college-sector" id="college-sector-card">
                            <div class="sector-card-header">
                                <div class="sector-info">
                                    <h3><i class="fas fa-university"></i> College Clearance Period</h3>
                                    <div class="sector-status">
                                        <span class="status-badge" id="college-status-badge">Not Started</span>
                        </div>
                                </div>
                                <div class="sector-actions">
                                    <button class="btn btn-sm btn-success" id="college-start-btn" onclick="startSectorPeriod('College')">
                                        <i class="fas fa-play"></i> Start Clearance Period
                                    </button>
                                    <button class="btn btn-sm btn-warning" id="college-pause-btn" onclick="pauseSectorPeriod('College')" style="display: none;">
                                        <i class="fas fa-pause"></i> Pause Clearance Period
                                    </button>
                                    <button class="btn btn-sm btn-danger" id="college-close-btn" onclick="closeSectorPeriod('College')" style="display: none;">
                                        <i class="fas fa-stop"></i> End Clearance Period
                                    </button>
                                </div>
                            </div>
                            
                            <div class="sector-card-content">
                                <!-- Period Details -->
                                <div class="period-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Start Date:</span>
                                        <span class="detail-value" id="college-start-date">-</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">End Date:</span>
                                        <span class="detail-value" id="college-end-date">-</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Applications:</span>
                                        <span class="detail-value" id="college-applications">0</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Completed:</span>
                                        <span class="detail-value" id="college-completed">0</span>
                                    </div>
                                </div>
                                
                                <!-- Integrated Signatories -->
                                <div class="sector-signatories">
                                    <div class="signatories-header">
                                        <h4><i class="fas fa-signature"></i> Clearance Signatories</h4>
                                        <div class="signatory-actions">
                                            <button class="btn btn-xs btn-primary" onclick="openAddScopeModal('College')">
                                                <i class="fas fa-plus"></i> Add Signatory
                                            </button>
                                            <button class="btn btn-xs btn-outline-primary" onclick="openSignatorySettingsModal('College')" title="Configure signatory settings">
                                                <i class="fas fa-cog"></i> Settings
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger" onclick="clearAllSignatories('College')">
                                        <i class="fas fa-trash"></i> Clear All
                                    </button>
                                        </div>
                                    </div>
                                    <div class="signatory-list" id="collegeSignatoryList">
                                        <div class="loading-text">Loading College signatories...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Senior High School Clearance Period Card -->
                        <div class="sector-period-card shs-sector" id="shs-sector-card">
                            <div class="sector-card-header">
                                <div class="sector-info">
                                    <h3><i class="fas fa-graduation-cap"></i> Senior High School Clearance Period</h3>
                                    <div class="sector-status">
                                        <span class="status-badge" id="shs-status-badge">Not Started</span>
                        </div>
                                </div>
                                <div class="sector-actions">
                                    <button class="btn btn-sm btn-success" id="shs-start-btn" onclick="startSectorPeriod('Senior High School')">
                                        <i class="fas fa-play"></i> Start Clearance Period
                                    </button>
                                    <button class="btn btn-sm btn-warning" id="shs-pause-btn" onclick="pauseSectorPeriod('Senior High School')" style="display: none;">
                                        <i class="fas fa-pause"></i> Pause Clearance Period
                                    </button>
                                    <button class="btn btn-sm btn-danger" id="shs-close-btn" onclick="closeSectorPeriod('Senior High School')" style="display: none;">
                                        <i class="fas fa-stop"></i> End Clearance Period
                                    </button>
                                </div>
                            </div>
                            
                            <div class="sector-card-content">
                                <!-- Period Details -->
                                <div class="period-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Start Date:</span>
                                        <span class="detail-value" id="shs-start-date">-</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">End Date:</span>
                                        <span class="detail-value" id="shs-end-date">-</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Applications:</span>
                                        <span class="detail-value" id="shs-applications">0</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Completed:</span>
                                        <span class="detail-value" id="shs-completed">0</span>
                                    </div>
                                </div>
                                
                                <!-- Integrated Signatories -->
                                <div class="sector-signatories">
                                    <div class="signatories-header">
                                        <h4><i class="fas fa-signature"></i> Clearance Signatories</h4>
                                        <div class="signatory-actions">
                                            <button class="btn btn-xs btn-primary" onclick="openAddScopeModal('Senior High School')">
                                                <i class="fas fa-plus"></i> Add Signatory
                                            </button>
                                            <button class="btn btn-xs btn-outline-primary" onclick="openSignatorySettingsModal('Senior High School')" title="Configure signatory settings">
                                                <i class="fas fa-cog"></i> Settings
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger" onclick="clearAllSignatories('Senior High School')">
                                        <i class="fas fa-trash"></i> Clear All
                                    </button>
                                        </div>
                                    </div>
                                    <div class="signatory-list" id="shsSignatoryList">
                                        <div class="loading-text">Loading SHS signatories...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Faculty Clearance Period Card -->
                        <div class="sector-period-card faculty-sector" id="faculty-sector-card">
                            <div class="sector-card-header">
                                <div class="sector-info">
                                    <h3><i class="fas fa-chalkboard-teacher"></i> Faculty Clearance Period</h3>
                                    <div class="sector-status">
                                        <span class="status-badge" id="faculty-status-badge">Not Started</span>
                        </div>
                                    </div>
                                <div class="sector-actions">
                                    <button class="btn btn-sm btn-success" id="faculty-start-btn" onclick="startSectorPeriod('Faculty')">
                                        <i class="fas fa-play"></i> Start Clearance Period
                                    </button>
                                    <button class="btn btn-sm btn-warning" id="faculty-pause-btn" onclick="pauseSectorPeriod('Faculty')" style="display: none;">
                                        <i class="fas fa-pause"></i> Pause Clearance Period
                                    </button>
                                    <button class="btn btn-sm btn-danger" id="faculty-close-btn" onclick="closeSectorPeriod('Faculty')" style="display: none;">
                                        <i class="fas fa-stop"></i> End Clearance Period
                                        </button>
                                    </div>
                                    </div>
                            
                            <div class="sector-card-content">
                                <!-- Period Details -->
                                <div class="period-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Start Date:</span>
                                        <span class="detail-value" id="faculty-start-date">-</span>
                                </div>
                                    <div class="detail-item">
                                        <span class="detail-label">End Date:</span>
                                        <span class="detail-value" id="faculty-end-date">-</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Applications:</span>
                                        <span class="detail-value" id="faculty-applications">0</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Completed:</span>
                                        <span class="detail-value" id="faculty-completed">0</span>
                                    </div>
                                </div>
                                
                                <!-- Integrated Signatories -->
                                <div class="sector-signatories">
                                    <div class="signatories-header">
                                        <h4><i class="fas fa-signature"></i> Clearance Signatories</h4>
                                <div class="signatory-actions">
                                            <button class="btn btn-xs btn-primary" onclick="openAddScopeModal('Faculty')">
                                                <i class="fas fa-plus"></i> Add Signatory
                                    </button>
                                            <button class="btn btn-xs btn-outline-primary" onclick="openSignatorySettingsModal('Faculty')" title="Configure signatory settings">
                                                <i class="fas fa-cog"></i> Settings
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger" onclick="clearAllSignatories('Faculty')">
                                        <i class="fas fa-trash"></i> Clear All
                                    </button>
                                        </div>
                                    </div>
                                    <div class="signatory-list" id="facultySignatoryList">
                                        <div class="loading-text">Loading Faculty signatories...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Button -->
                    <div class="export-section">
                        <button class="btn btn-primary export-btn" onclick="openExportModal()">
                            <i class="fas fa-file-export"></i> Export Clearance Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGHT SIDE: Activity Tracker -->
        <div class="dashboard-sidebar">
            <?php include '../../includes/components/activity-tracker.php'; ?>
        </div>
    </div>
    </main>

    <!-- Include Modals -->
    <?php include '../../Modals/ClearanceExportModal.php'; ?>
    <?php include '../../Modals/AddSignatoryModal.php'; ?>
    <?php include '../../Modals/AddSchoolYearModal.php'; ?>

    <!-- Add Scope Signatory Modal (externalized) -->
    <?php include '../../Modals/AddScopeSignatoryModal.php'; ?>

    <!-- Scripts -->
    <script src="../../assets/js/activity-tracker.js"></script>
    <script src="../../assets/js/grace-period-manager.js"></script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
    
    <script>
        // Clearance Management Functions
        async function fetchJSON(url, options = {}) {
            const res = await fetch(url, { credentials: 'include', ...options });
            
            // Check if response is ok first
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            
            // Get response text first to debug any JSON parsing issues
            const responseText = await res.text();
            console.log(`🔍 DEBUG: Raw response for ${url}:`, responseText);
            
            // Try to parse JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error(`❌ JSON Parse Error for ${url}:`, parseError);
                console.error(`❌ Raw response:`, responseText);
                throw new Error(`Invalid JSON response: ${parseError.message}`);
            }
            
            // Check if the parsed data indicates failure
            if (data.success === false) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        }

        // Confirmation Modal Functions
        window.confirmationResolve = null;
        
        function showConfirmationModal(title, message, confirmText = 'Confirm', cancelText = 'Cancel', type = 'info') {
            return new Promise((resolve) => {
                window.confirmationResolve = resolve;
                
                const modal = document.getElementById('confirmationModal');
                const header = document.getElementById('alertHeader');
                const icon = document.getElementById('alertIcon');
                const titleEl = document.getElementById('alertTitle');
                const messageEl = document.getElementById('alertMessage');
                const confirmBtn = document.getElementById('confirmBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                
                // Set content
                titleEl.textContent = title;
                messageEl.textContent = message;
                confirmBtn.textContent = confirmText;
                cancelBtn.textContent = cancelText;
                
                // Set styling based on type
                header.className = `alert-modal-header alert-${type}`;
                
                // Set icon based on type
                const icons = {
                    'info': 'fas fa-info-circle',
                    'warning': 'fas fa-exclamation-triangle',
                    'danger': 'fas fa-exclamation-circle',
                    'success': 'fas fa-check-circle'
                };
                icon.className = icons[type] || icons['info'];
                
                // Set button styling
                if (type === 'danger') {
                    confirmBtn.className = 'btn btn-danger';
                } else if (type === 'warning') {
                    confirmBtn.className = 'btn btn-warning';
                } else {
                    confirmBtn.className = 'btn btn-primary';
                }
                
                // Show modal
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('active'), 10);
            });
        }
        
        function closeConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
            
            if (window.confirmationResolve) {
                window.confirmationResolve(false);
                window.confirmationResolve = null;
            }
        }
        
        // Override will be set after alerts.js is loaded

        async function loadScopeSignatories(type){
            // Normalize clearance type to proper case
            const normalizedType = type === 'faculty' ? 'Faculty' : type;
            
            // Map clearance types to their corresponding list elements
            const listElementMap = {
                'College': 'collegeSignatoryList',
                'Senior High School': 'shsSignatoryList',
                'Faculty': 'facultySignatoryList'
            };
            
            const listEl = document.getElementById(listElementMap[normalizedType]);
            if (!listEl) {
                console.error(`List element not found for type: ${type} (normalized: ${normalizedType})`);
                return;
            }
            
            try {
                // Fetch signatories and settings in parallel using new sector-based API
                const [signatoriesData, settingsData] = await Promise.all([
                    fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`),
                    fetchJSON(`../../api/signatories/sector_settings.php?clearance_type=${encodeURIComponent(normalizedType)}`)
                ]);
                
                const items = signatoriesData.signatories || [];
                const settings = settingsData.settings?.[0] || {};
                
                if (items.length === 0) {
                    listEl.innerHTML = '<div style="color:#6c757d;padding:6px 0;">No signatories assigned to this sector yet</div>';
                    return;
                }
                
                // Enhanced render with required signatory styling
                const html = items.map(it => {
                    let itemClass = 'signatory-item optional';
                    let requirementText = '';
                    
                    // Check if this signatory is Required First
                    if (settings.required_first_enabled && settings.required_first_designation_id) {
                        const isRequiredFirst = it.designation_id === settings.required_first_designation_id;
                        if (isRequiredFirst) {
                            itemClass = 'signatory-item required-first';
                            requirementText = '<span class="signatory-requirement">(Required First)</span>';
                        }
                    }
                    
                    // Check if this signatory is Required Last
                    if (settings.required_last_enabled && settings.required_last_designation_id) {
                        const isRequiredLast = it.designation_id === settings.required_last_designation_id;
                        if (isRequiredLast) {
                            itemClass = 'signatory-item required-last';
                            requirementText = '<span class="signatory-requirement">(Required Last)</span>';
                        }
                    }
                    
                    // Add department info for Program Heads
                    let departmentInfo = '';
                    if (it.is_program_head && it.department_name) {
                        departmentInfo = ` <span style="color:#6c757d;font-size:12px;">(${it.department_name})</span>`;
                    }
                    
                    return `
                        <div class="${itemClass}">
                            <span class="signatory-name">${it.designation_name} — ${[it.first_name, it.last_name].filter(Boolean).join(' ')}${departmentInfo}</span>
                            ${requirementText}
                            <button class="remove-signatory" onclick="removeScope('${type}', ${it.user_id}, '${it.designation_name.replace(/'/g, "\'")}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                }).join('');
                
                listEl.innerHTML = html;
                
            } catch (error) {
                console.error('Error loading scope signatories:', error);
                listEl.innerHTML = '<div style="color:#dc3545;padding:6px 0;">Error loading signatories. Please try again.</div>';
            }
        }

        async function removeScope(type, userId, designation){
            try {
                // Normalize clearance type
                const normalizedType = type === 'faculty' ? 'Faculty' : type;
                
                console.log(`🔧 Attempting to remove signatory: ${designation} (User ID: ${userId}) from ${normalizedType} scope`);
                
                // First, check if this signatory is currently required
                const settingsData = await fetchJSON(`../../api/signatories/sector_settings.php?clearance_type=${encodeURIComponent(normalizedType)}`);
                const settings = settingsData.settings?.[0] || {};
                
                // Check if trying to remove Required First signatory
                if (settings.required_first_enabled && settings.required_first_designation_id) {
                    const signatoryData = await fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`);
                    const signatory = signatoryData.signatories?.find(s => s.user_id === userId);
                    
                    if (signatory && signatory.designation_id === settings.required_first_designation_id) {
                        showToast('This signatory is currently set as Required First. Please disable this feature in Settings before removing the signatory.', 'warning');
                        return;
                    }
                }
                
                // Check if trying to remove Required Last signatory
                if (settings.required_last_enabled && settings.required_last_designation_id) {
                    const signatoryData = await fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`);
                    const signatory = signatoryData.signatories?.find(s => s.user_id === userId);
                    
                    if (signatory && signatory.designation_id === settings.required_last_designation_id) {
                        showToast('This signatory is currently set as Required Last. Please disable this feature in Settings before removing the signatory.', 'warning');
                        return;
                    }
                }
                
                console.log(`🔧 Proceeding with removal of non-required signatory: ${designation}`);
                
                // If not required, proceed with removal using new sector-based API
                const response = await fetchJSON(`../../api/signatories/sector_assignments.php`,{
                    method:'DELETE', 
                    headers:{'Content-Type':'application/json'}, 
                    credentials:'include',
                    body: JSON.stringify({ 
                        clearance_type: normalizedType,
                        user_id: userId,
                        designation: designation
                    })
                });
                
                console.log(`🔧 Removal successful, response:`, response);
                showToast('Removed sector signatory', 'success');
                
                // Refresh the signatory list
                await loadScopeSignatories(type);
                
            } catch (error) {
                console.error('Error removing scope signatory:', error);
                showToast('Failed to remove signatory: ' + (error.message || 'Unknown error'), 'error');
            }
        }

        // Clear all signatories for a specific scope
        async function clearAllSignatories(type) {
            try {
                // Normalize clearance type
                const normalizedType = type === 'faculty' ? 'Faculty' : type;
                
                console.log(`🔧 Attempting to clear all signatories from ${normalizedType} scope`);
                
                // Check if there are any required signatories
                const settingsData = await fetchJSON(`../../api/signatories/sector_settings.php?clearance_type=${encodeURIComponent(normalizedType)}`);
                const settings = settingsData.settings?.[0] || {};
                
                let warningMessage = '';
                if (settings.required_first_enabled || settings.required_last_enabled) {
                    warningMessage = '\n\nNote: Some signatories are currently set as required. They will also be removed.';
                }
                
                // Show confirmation dialog
                const confirmed = confirm(`Are you sure you want to remove ALL signatories from ${normalizedType} clearance? This action cannot be undone.${warningMessage}`);
                
                if (!confirmed) {
                    return;
                }
                
                // Fetch current signatories to get their IDs
                const signatoriesData = await fetchJSON(`../../api/signatories/sector_assignments.php?clearance_type=${encodeURIComponent(normalizedType)}`);
                const signatories = signatoriesData.signatories || [];
                
                if (signatories.length === 0) {
                    showToast('No signatories to remove', 'info');
                    return;
                }
                
                console.log(`🔧 Found ${signatories.length} signatories to remove`);
                
                // Remove all signatories
                let successCount = 0;
                let errorCount = 0;
                
                for (const signatory of signatories) {
                    try {
                        await fetchJSON(`../../api/signatories/sector_assignments.php`, {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                clearance_type: normalizedType,
                                user_id: signatory.user_id,
                                designation: signatory.designation_name
                            })
                        });
                        successCount++;
                    } catch (error) {
                        console.error(`Error removing signatory ${signatory.designation_name}:`, error);
                        errorCount++;
                    }
                }
                
                // Show results
                if (successCount > 0 && errorCount === 0) {
                    showToast(`Successfully removed all ${successCount} signatories`, 'success');
                } else if (successCount > 0 && errorCount > 0) {
                    showToast(`Removed ${successCount} signatories, ${errorCount} failed`, 'warning');
                } else {
                    showToast('Failed to remove any signatories', 'error');
                }
                
                // Refresh the signatory list
                await loadScopeSignatories(type);
                
            } catch (error) {
                console.error('Error clearing all signatories:', error);
                showToast('Failed to clear signatories: ' + (error.message || 'Unknown error'), 'error');
            }
        }

        let scopeSearchTimer = null;
        window.scopeSelectedIds = new Set();
        window.scopeSelectedLabels = new Map();
        window.scopeStaffData = new Map(); // Store staff data for designation lookup
        async function openAddScopeModal(type){
            // Normalize clearance type
            const normalizedType = type === 'faculty' ? 'Faculty' : type;
            
            document.getElementById('scopeTypeField').value = normalizedType;
            document.getElementById('scopeSearchInput').value = '';
            document.getElementById('scopeSearchResults').innerHTML = '';
            renderScopeSelectedChips();
            
            // Load include PH toggle using new sector-based API
            try{
                const data = await fetchJSON(`../../api/signatories/sector_settings.php?clearance_type=${encodeURIComponent(normalizedType)}`);
                const on = !!(data.settings?.[0] && (data.settings[0].include_program_head==1 || data.settings[0].include_program_head===true));
                const cb = document.getElementById('includeProgramHeadCheckbox');
                if (cb) {
                    cb.checked = on;
                    // Initialize preview visibility based on checkbox state
                    toggleProgramHeadPreview();
                }
            }catch(e){ /* ignore */ }
            
            const modal = document.getElementById('addScopeModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            const addBtn = modal.querySelector('.modal-action-primary');
            if (addBtn){ addBtn.disabled = isPeriodLocked(); }
            
            // Load Program Head preview for this sector
            await loadProgramHeadPreview(normalizedType);
            
            // Load all staff table (excluding PH)
            try{
                const data = await fetchJSON('../../api/staff/list.php?limit=200&exclude_program_head=1');
                
                // Store staff data for designation lookup
                window.scopeStaffData.clear();
                (data.staff||[]).forEach(s => {
                    window.scopeStaffData.set(s.user_id, s);
                });
                
                const tb = document.getElementById('scopeAllStaffTable');
                if (tb){
                    const rows = (data.staff||[]).map(s => {
                        const uid = s.user_id;
                        const label = `${(s.first_name||'').trim()} ${(s.last_name||'').trim()} • ${(s.employee_number||s.username||'')}`.trim();
                        const checked = window.scopeSelectedIds.has(uid) ? 'checked' : '';
                        return `
                        <tr data-user-id=\"${uid}\"> 
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;text-align:center;\"><input type=\"checkbox\" ${checked} onchange=\"toggleScopeUser(${uid}, '${label.replace(/'/g, "\'")}')\"></td>
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;\">${[s.first_name||'', s.last_name||''].join(' ').trim()}</td>
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;\">${s.employee_number||s.username||''}</td>
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;\">${s.designation_name||''}</td>
                        </tr>`;
                    }).join('');
                    tb.innerHTML = rows || '<tr><td style="padding:10px 10px;color:#6c757d;" colspan="4">No staff found</td></tr>';
                }
            }catch(e){
                const tb = document.getElementById('scopeAllStaffTable');
                if (tb){ tb.innerHTML = '<tr><td style="padding:10px 10px;color:#dc3545;" colspan="3">Failed to load staff</td></tr>'; }
            }
        }

        // Load Program Head preview for the selected sector
        async function loadProgramHeadPreview(sector) {
            try {
                console.log(`🔍 Loading Program Head preview for sector: ${sector}`);
                const data = await fetchJSON(`../../api/signatories/sector_settings.php?action=program_heads&clearance_type=${encodeURIComponent(sector)}`);
                const programHeads = data.program_heads || [];
                
                console.log(`🔍 Found ${programHeads.length} Program Heads for ${sector}:`, programHeads);
                
                // Get the Program Head preview container from the modal
                const previewContainer = document.getElementById('programHeadPreviewContainer');
                const previewTitle = document.getElementById('programHeadPreviewTitle');
                const previewList = document.getElementById('programHeadPreviewList');
                
                if (previewContainer && previewTitle && previewList) {
                    // Update title
                    previewTitle.textContent = `Program Heads in ${sector}`;
                    
                    if (programHeads.length === 0) {
                        previewList.innerHTML = '<div style="color:#6c757d;padding:8px;font-style:italic;">No Program Heads assigned to departments in this sector</div>';
                        previewContainer.style.display = 'block'; // Show even when empty for user awareness
                    } else {
                        const html = programHeads.map(ph => `
                            <div class="program-head-preview-item">
                                <div class="ph-info">
                                    <strong>${ph.first_name} ${ph.last_name}</strong>
                                    <span class="ph-employee">(${ph.employee_number})</span>
                                </div>
                                <div class="ph-departments">
                                    <i class="fas fa-building"></i> ${ph.department_name}
                                </div>
                            </div>
                        `).join('');
                        previewList.innerHTML = html;
                        previewContainer.style.display = 'block';
                    }
                    
                    console.log(`✅ Program Head preview updated for ${sector}`);
                } else {
                    console.error('❌ Program Head preview elements not found in DOM');
                }
            } catch (error) {
                console.error('❌ Error loading Program Head preview:', error);
                // Show error message in preview
                const previewContainer = document.getElementById('programHeadPreviewContainer');
                const previewList = document.getElementById('programHeadPreviewList');
                if (previewContainer && previewList) {
                    previewList.innerHTML = '<div style="color:#dc3545;padding:8px;">Error loading Program Head information</div>';
                    previewContainer.style.display = 'block';
                }
            }
        }

        // Toggle Program Head preview visibility based on checkbox
        function toggleProgramHeadPreview() {
            const checkbox = document.getElementById('includeProgramHeadCheckbox');
            const previewContainer = document.getElementById('programHeadPreviewContainer');
            
            if (checkbox && previewContainer) {
                if (checkbox.checked) {
                    previewContainer.style.display = 'block';
                    // Add visual indicator that this will be auto-assigned
                    const previewTitle = document.getElementById('programHeadPreviewTitle');
                    if (previewTitle) {
                        previewTitle.innerHTML = `Program Heads in ${document.getElementById('scopeTypeField').value} <span style="color:#28a745;font-size:12px;">(Will be auto-assigned)</span>`;
                    }
                } else {
                    previewContainer.style.display = 'none';
                }
            }
        }

        function closeAddScopeModal(){
            const modal = document.getElementById('addScopeModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            // clear selection marker
            const sel = document.querySelector('#scopeSearchResults .selected');
            if (sel) sel.classList.remove('selected');
            document.getElementById('scopeSearchResults').dataset.selectedUserId = '';
        }

        function debouncedScopeSearch(){
            if (scopeSearchTimer) clearTimeout(scopeSearchTimer);
            scopeSearchTimer = setTimeout(runScopeSearch, 300);
        }
        async function runScopeSearch(){
            const q = document.getElementById('scopeSearchInput').value.trim();
            const box = document.getElementById('scopeSearchResults');
            if (!q){ box.innerHTML = ''; return; }
            try{
                const data = await fetchJSON(`../../api/staff/list.php?limit=20&exclude_program_head=1&search=${encodeURIComponent(q)}`);
                const users = data.staff || [];
                if (!users.length){ box.innerHTML='<div style="color:#6c757d;">No results</div>'; return; }
                box.innerHTML = users.map(u => {
                    const uid = u.user_id;
                    const emp = (u.employee_number||u.username||'');
                    const label = `${(u.first_name||'').trim()} ${(u.last_name||'').trim()} • ${emp}`.trim();
                    const checked = window.scopeSelectedIds.has(uid) ? 'checked' : '';
                    const desig = u.designation_name || '';
                    return `
                    <div class=\"result-row\" data-user-id=\"${uid}\" style=\"display:grid;grid-template-columns:28px 1fr;gap:10px;padding:8px 10px;border-bottom:1px solid #eef2f6;align-items:center;\">
                        <div style=\"display:flex;justify-content:center;\"><input type=\"checkbox\" ${checked} onchange=\"toggleScopeUser(${uid}, '${label.replace(/'/g, "\'")}')\"></div>
                        <div style=\"display:flex;flex-direction:column;\">
                            <div style=\"display:flex;align-items:center;gap:8px;\">
                                <div style=\"font-weight:600;color:#2f3a4b;\">${(u.first_name||'')} ${(u.last_name||'')}</div>
                                <div style=\"color:#6c757d;\">${emp}</div>
                            </div>
                            <div style=\"color:#708090;font-size:12px;\">${desig}</div>
                        </div>
                    </div>`;
                }).join('');
            }catch(e){ box.innerHTML='<div style="color:#dc3545;">Search failed</div>'; }
        }
        function toggleScopeUser(userId, label){
            if (window.scopeSelectedIds.has(userId)){
                window.scopeSelectedIds.delete(userId);
                window.scopeSelectedLabels.delete(userId);
            } else {
                window.scopeSelectedIds.add(userId);
                window.scopeSelectedLabels.set(userId, label);
            }
            renderScopeSelectedChips();
        }
        function renderScopeSelectedChips(){
            const wrap = document.getElementById('scopeSelectedChips');
            if (!wrap) return;
            const items = Array.from(window.scopeSelectedIds);
            wrap.innerHTML = items.map(id => {
                const text = window.scopeSelectedLabels.get(id) || `User ${id}`;
                return `<span class=\"chip\" style=\"display:inline-flex;align-items:center;gap:6px;background:#eef3f8;border:1px solid #d7dee7;border-radius:16px;padding:4px 10px;\">${text}<button type=\"button\" aria-label=\"remove\" onclick=\"removeScopeSelected(${id})\" style=\"border:none;background:transparent;cursor:pointer;color:#6b7785;\">×</button></span>`;
            }).join('');
        }
        function removeScopeSelected(userId){
            window.scopeSelectedIds.delete(userId);
            window.scopeSelectedLabels.delete(userId);
            renderScopeSelectedChips();
            // also uncheck in results if present
            const el = document.querySelector(`#scopeSearchResults [data-user-id=\"${userId}\"] input[type=checkbox]`);
            if (el) el.checked = false;
        }
        function clearScopeSelection(){
            window.scopeSelectedIds.clear();
            window.scopeSelectedLabels.clear();
            renderScopeSelectedChips();
            document.querySelectorAll('#scopeSearchResults input[type=checkbox]').forEach(cb => cb.checked = false);
        }
        async function submitAddScope(){
            const type = document.getElementById('scopeTypeField').value;
            const includePH = !!document.getElementById('includeProgramHeadCheckbox')?.checked;
            const ids = Array.from(window.scopeSelectedIds);
            if (!ids.length && !includePH){ showToast('Select at least one staff or toggle Program Head','warning'); return; }
            
            // Save scope setting first using new sector-based API
            try{
                await fetchJSON('../../api/signatories/sector_settings.php',{
                    method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include',
                    body: JSON.stringify({ clearance_type:type, include_program_head: includePH })
                });
            }catch(e){ /* surface but continue adds */ showToast('Saved PH setting with warnings','warning'); }
            
            // Add staff in parallel (limit fanout) using new sector-based API
            let ok = 0, fail = 0;
            for (const uid of ids){
                try{
                    // Get staff designation_id from stored data
                    const staff = window.scopeStaffData.get(uid);
                    
                    if (!staff || !staff.designation_id) {
                        console.error(`No designation found for user ${uid}`);
                        fail++;
                        continue;
                    }
                    
                    await fetchJSON('../../api/signatories/sector_assignments.php',{
                        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                        body: JSON.stringify({ 
                            user_id: uid, 
                            clearance_type: type,
                            designation_id: staff.designation_id
                        })
                    });
                    ok++;
                }catch(e){ 
                    console.error(`Error adding signatory ${uid}:`, e);
                    fail++; 
                }
            }
            
            // Handle Program Head auto-assignment if checkbox is checked
            if (includePH) {
                try {
                    const phResponse = await fetchJSON('../../api/signatories/sector_assignments.php', {
                        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                        body: JSON.stringify({ 
                            action: 'assign_program_heads',
                            clearance_type: type 
                        })
                    });
                    if (phResponse.assigned_count > 0) {
                        showToast(`Auto-assigned ${phResponse.assigned_count} Program Heads`, 'info');
                    }
                } catch (e) {
                    console.error('Error auto-assigning Program Heads:', e);
                    showToast('Failed to auto-assign Program Heads', 'warning');
                }
            }
            
            closeAddScopeModal();
            if (ok && !fail) showToast(`Added ${ok} signator${ok===1?'y':'ies'}`,'success');
            else if (ok && fail) showToast(`Added ${ok}, skipped ${fail}`,'warning');
            else if (!ok && fail) showToast('No signatories added','error');
            loadScopeSignatories(type);
        }

        async function addScope(type, userId, designation){
            try{
                await fetchJSON('../../api/signatories/assign.php',{
                    method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                    body: JSON.stringify({ user_id:userId, designation:designation, clearance_type:type })
                });
                showToast('Scope signatory added', 'success');
                loadScopeSignatories(type);
            }catch(e){ showToast(e.message,'error'); }
        }
        function toggleAccordion(sectionId) {
            const content = document.getElementById(sectionId);
            const header = content.previousElementSibling;
            const icon = header.querySelector('.accordion-icon');
            
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                icon.textContent = '▼';
                header.classList.add('active');
            } else {
                content.style.display = 'none';
                icon.textContent = '▶';
                header.classList.remove('active');
            }
        }


        async function removeSignatory(type, position) {
            const confirmed = await showConfirmationModal(
                'Remove Signatory',
                `Are you sure you want to remove ${position} from ${type} clearance signatories?`,
                'Remove',
                'Cancel',
                'warning'
            );
            
            if (confirmed) {
                // Find and remove the signatory item
                const signatoryItems = document.querySelectorAll(`.signatory-item.optional[data-position="${position}"]`);
                let removed = false;
                
                signatoryItems.forEach(item => {
                    const section = item.closest('.accordion-content');
                    const isCorrectSection = (type === 'student' && section.id === 'student-signatories') || 
                                           (type === 'faculty' && section.id === 'faculty-signatories');
                    
                    if (isCorrectSection) {
                        item.remove();
                        removed = true;
                    }
                });
                
                if (removed) {
                    showToast(`Removed ${position} from ${type} clearance signatories.`, 'success');
                } else {
                    showToast(`${position} not found in ${type} clearance signatories.`, 'warning');
                }
            }
        }

        // School Year Navigation System (backend-driven)
        let currentSchoolYearIndex = 0; // single current year for now
        let schoolYears = [];
        const API_BASE = '../../api/clearance';

        function mapPeriodStatusToTermStatus(periodStatus, periodData = null) {
            // Map database status to term status
            if (periodStatus === 'Ongoing') return 'active';
            if (periodStatus === 'Closed') return 'completed';
            if (periodStatus === 'Paused') return 'deactivated';
            if (periodStatus === 'Not Started') return 'inactive';
            return 'inactive';
        }

        async function fetchJSON(url, options = {}) {
            const res = await fetch(url, { credentials: 'include', ...options });
            
            // Check if response is ok first
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            
            // Get response text first to debug any JSON parsing issues
            const responseText = await res.text();
            console.log(`🔍 DEBUG: Raw response for ${url}:`, responseText);
            
            // Try to parse JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error(`❌ JSON Parse Error for ${url}:`, parseError);
                console.error(`❌ Raw response:`, responseText);
                throw new Error(`Invalid JSON response: ${parseError.message}`);
            }
            
            // Check if the parsed data indicates failure
            if (data.success === false) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        }

        async function loadCurrentYearAndTerms() {
            const ctx = await fetchJSON(`${API_BASE}/context.php`);
            if (!ctx.academic_year) { schoolYears = []; return; }

            const ayId = ctx.academic_year.academic_year_id;
            const term1SemId = ctx.terms.find(t => t.semester_name === '1st')?.semester_id || null;
            const term2SemId = ctx.terms.find(t => t.semester_name === '2nd')?.semester_id || null;

            const periodsResp = await fetchJSON(`${API_BASE}/periods.php`);
            const periods = periodsResp.periods || [];
            
            function findPeriodForSemester(semId) {
                return periods.find(p => p.academic_year_id === ayId && p.semester_id === semId) || null;
            }
            const p1 = term1SemId ? findPeriodForSemester(term1SemId) : null;
            const p2 = term2SemId ? findPeriodForSemester(term2SemId) : null;

            // Status mapping for terms

            const yearObj = {
                id: ctx.academic_year.year,
                name: ctx.academic_year.year,
                status: 'current',
                terms: [
                    { id: 'term1', name: 'Term 1', status: mapPeriodStatusToTermStatus(p1?.status, p1), periodId: p1?.period_id || null, semesterId: term1SemId, students: '0/0' },
                    { id: 'term2', name: 'Term 2', status: mapPeriodStatusToTermStatus(p2?.status, p2), periodId: p2?.period_id || null, semesterId: term2SemId, students: '0/0' }
                ],
                canAddSchoolYear: true,
                academicYearId: ayId
            };

            schoolYears = [yearObj];
            currentSchoolYearIndex = 0;
        }

        // Operation Queue System
        class TermOperationQueue {
            constructor() {
                this.queue = [];
                this.currentOperation = null;
                this.isProcessing = false;
            }
            
            async enqueue(operation) {
                // Cancel any pending operations of the same type for the same term
                this.cancelPendingOperations(operation.type, operation.termId);
                
                // Add to queue
                this.queue.push(operation);
                
                // Process queue
                this.processQueue();
            }
            
            cancelPendingOperations(operationType, termId) {
                // Remove pending operations of the same type for the same term
                this.queue = this.queue.filter(op => 
                    !(op.type === operationType && op.termId === termId && op.state === 'pending')
                );
            }
            
            async processQueue() {
                if (this.isProcessing || this.queue.length === 0) {
                    return;
                }
                
                this.isProcessing = true;
                
                while (this.queue.length > 0) {
                    const operation = this.queue.shift();
                    if (operation.state === 'cancelled') {
                        continue;
                    }
                    
                    try {
                        operation.state = 'processing';
                        this.currentOperation = operation;
                        
                        // Execute the operation
                        await this.executeOperation(operation);
                        
                        operation.state = 'completed';
                    } catch (error) {
                        operation.state = 'failed';
                        console.error('Operation failed:', error);
                        if (operation.reject) {
                            operation.reject(error);
                        }
                    } finally {
                        this.currentOperation = null;
                    }
                }
                
                this.isProcessing = false;
            }
            
            async executeOperation(operation) {
                // This will be implemented with the actual term operations
                return new Promise((resolve, reject) => {
                    operation.resolve = resolve;
                    operation.reject = reject;
                    
                    // The actual operation will be handled by the specific term functions
                    // This is just a placeholder for the queue structure
                    resolve();
                });
            }
        }
        
        // Debounced Operations Manager
        class DebouncedTermOperations {
            constructor() {
                this.debounceTimeouts = new Map();
                this.debounceDelay = 300; // 300ms debounce
            }
            
            debounceOperation(termId, operationType, callback) {
                const key = `${termId}-${operationType}`;
                
                // Clear existing timeout
                if (this.debounceTimeouts.has(key)) {
                    clearTimeout(this.debounceTimeouts.get(key));
                }
                
                // Set new timeout
                const timeout = setTimeout(() => {
                    callback();
                    this.debounceTimeouts.delete(key);
                }, this.debounceDelay);
                
                this.debounceTimeouts.set(key, timeout);
            }
        }
        
        // Initialize queue and debounce managers
        const operationQueue = new TermOperationQueue();
        const debouncedOperations = new DebouncedTermOperations();
        
        // Debounce updateTermsList to prevent excessive calls
        let updateTermsListTimeout = null;
        function debouncedUpdateTermsList(schoolYear) {
            if (updateTermsListTimeout) {
                clearTimeout(updateTermsListTimeout);
            }
            updateTermsListTimeout = setTimeout(() => {
                updateTermsList(schoolYear);
                updateTermsListTimeout = null;
            }, 100); // 100ms debounce
        }

        // Ensure fresh data before any term operation
        let isRefreshing = false;
        async function ensureFreshData() {
            if (isRefreshing) {
                console.log('🔄 ensureFreshData: Already refreshing, skipping...');
                return true;
            }
            
            try {
                isRefreshing = true;
                console.log('🔄 ensureFreshData: Starting data refresh...');
                await loadCurrentYearAndTerms();
                console.log('🔄 ensureFreshData: Data loaded, updating display...');
                console.log('🔄 ensureFreshData: Current schoolYears:', schoolYears);
                updateSchoolYearDisplay();
                console.log('✅ ensureFreshData: Data refresh completed');
                return true;
            } catch (error) {
                console.error('Failed to refresh data:', error);
                showToast('Failed to refresh data. Please try again.', 'error');
                return false;
            } finally {
                isRefreshing = false;
            }
        }

        // Enhanced Loading States Manager
        class EnhancedLoadingStatesManager {
            constructor() {
                this.operationQueue = [];
                this.isProcessing = false;
                this.minLoadingDuration = 3000; // 3 seconds minimum
            }

            async performTermOperation(operation, termId, operationType) {
                if (this.isProcessing) {
                    showToast('Another operation is in progress. Please wait.', 'warning');
                    return;
                }

                this.isProcessing = true;
                const startTime = Date.now();

                try {
                    // Show initial notification for auto-ending
                    if (operationType === 'activate' && termId === 'term2') {
                        showToast('Term 1 will end to start Term 2', 'info');
                    }

                    // Show loading state
                    this.showTermLoading(termId, operationType);

                    // Perform the operation
                    const result = await operation();

                    // Calculate remaining time for minimum duration
                    const elapsed = Date.now() - startTime;
                    const remainingTime = Math.max(0, this.minLoadingDuration - elapsed);

                    // Wait for minimum duration
                    await new Promise(resolve => setTimeout(resolve, remainingTime));

                    // Show success state
                    this.showTermSuccess(termId, operationType);

                    return result;
                } catch (error) {
                    this.showTermError(termId, operationType, error);
                    throw error;
                } finally {
                    this.isProcessing = false;
                }
            }

            showTermLoading(termId, operationType) {
                const termItem = document.querySelector(`.term-item[data-term="${termId}"]`);
                if (!termItem) return;

                const actionsDiv = termItem.querySelector('.term-actions');
                if (!actionsDiv) return;

                // Disable all buttons
                const buttons = actionsDiv.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('loading');
                });

                // Show loading message
                let loadingMessage = '';
                if (operationType === 'activate') {
                    loadingMessage = 'Configuring Term...';
                } else if (operationType === 'deactivate') {
                    loadingMessage = 'Pausing...';
                } else if (operationType === 'end') {
                    loadingMessage = 'Ending...';
                }

                // Add loading indicator
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'term-loading-state';
                loadingDiv.innerHTML = `
                    <div class="loading-spinner"></div>
                    <span class="loading-text">${loadingMessage}</span>
                `;
                actionsDiv.appendChild(loadingDiv);
            }

            showTermSuccess(termId, operationType) {
                const termItem = document.querySelector(`.term-item[data-term="${termId}"]`);
                if (!termItem) return;

                const actionsDiv = termItem.querySelector('.term-actions');
                if (!actionsDiv) return;

                // Remove loading state
                const loadingDiv = actionsDiv.querySelector('.term-loading-state');
                if (loadingDiv) {
                    loadingDiv.remove();
                }

                // Re-enable buttons
                const buttons = actionsDiv.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                });

                // Show success message
                let successMessage = '';
                if (operationType === 'activate' && termId === 'term2') {
                    successMessage = 'Term 2 activated successfully';
                } else if (operationType === 'activate') {
                    successMessage = 'Term activated successfully';
                } else if (operationType === 'deactivate') {
                    successMessage = 'Term paused successfully';
                } else if (operationType === 'end') {
                    successMessage = 'Term ended successfully';
                }

                showToast(successMessage, 'success');
            }

            showTermError(termId, operationType, error) {
                const termItem = document.querySelector(`.term-item[data-term="${termId}"]`);
                if (!termItem) return;

                const actionsDiv = termItem.querySelector('.term-actions');
                if (!actionsDiv) return;

                // Remove loading state
                const loadingDiv = actionsDiv.querySelector('.term-loading-state');
                if (loadingDiv) {
                    loadingDiv.remove();
                }

                // Re-enable buttons
                const buttons = actionsDiv.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                });

                // Show error message
                let errorMessage = '';
                if (operationType === 'activate' && termId === 'term2') {
                    errorMessage = 'Failed to end Term 1. Please end Term 1 manually first.';
                } else if (operationType === 'activate') {
                    errorMessage = 'Failed to activate term. Please try again.';
                } else if (operationType === 'deactivate') {
                    errorMessage = 'Failed to pause term. Please try again.';
                } else if (operationType === 'end') {
                    errorMessage = 'Failed to end term. Please try again.';
                }

                showToast(errorMessage, 'error');
            }
        }

        // Initialize enhanced loading states manager
        const enhancedLoadingManager = new EnhancedLoadingStatesManager();

        // Helper function to ensure Term 1 is ended before Term 2 activation
        async function ensureTerm1Ended() {
            try {
                // Refresh data first
                await loadCurrentYearAndTerms();
                
                // Check if Term 1 is already ended using global schoolYears
                const currentYear = schoolYears[currentSchoolYearIndex];
                if (!currentYear) {
                    throw new Error('No current year data available');
                }
                
                const term1 = currentYear.terms.find(t => t.id === 'term1');
                
                if (term1 && term1.status === 'completed') {
                    return; // Term 1 is already ended
                }

                // If Term 1 is not ended, end it automatically
                console.log('Auto-ending Term 1 to start Term 2...');
                
                // Find the Term 1 period ID
                const term1PeriodId = term1?.periodId;
                if (!term1PeriodId) {
                    throw new Error('No period exists for Term 1');
                }
                
                const response = await fetchJSON(`${API_BASE}/periods.php`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ period_id: term1PeriodId, action: 'end' })
                });

                if (!response.success) {
                    throw new Error(`Failed to auto-end Term 1: ${response.message}`);
                }

                console.log('Term 1 auto-ended successfully');
            } catch (error) {
                console.error('Error auto-ending Term 1:', error);
                throw new Error('Failed to end Term 1. Please end Term 1 manually first.');
            }
        }
        
        // UI State Management
        function setTermButtonsState(enabled) {
            document.querySelectorAll('.term-actions button').forEach(btn => {
                btn.disabled = !enabled;
            });
        }
        
        function showGlobalLoading(message = 'Processing...') {
            setTermButtonsState(false);
            // Show global loading indicator if needed
        }
        
        function hideGlobalLoading() {
            setTermButtonsState(true);
            // Hide global loading indicator if needed
        }
        function isPeriodLocked(){
            const cy = schoolYears[currentSchoolYearIndex];
            if (!cy) return false;
            return (cy.terms||[]).some(t => t.status === 'active' || t.status === 'deactivated');
        }

        function updateLockUI(){
            const locked = isPeriodLocked();
            // Disable Add New buttons in both accordions
            document.querySelectorAll('.signatory-actions .btn').forEach(btn => {
                if (btn && /Add New/i.test(btn.textContent)) {
                    btn.disabled = locked;
                }
            });
            // Disable remove icons
            document.querySelectorAll('.remove-signatory').forEach(btn => {
                btn.disabled = locked;
                btn.style.pointerEvents = locked ? 'none' : 'auto';
                btn.style.opacity = locked ? '0.5' : '1';
            });
            // Insert or remove lock note
            ['student-signatories','faculty-signatories'].forEach(id => {
                const container = document.getElementById(id);
                if (!container) return;
                let note = container.querySelector('.lock-note');
                if (locked) {
                    if (!note) {
                        note = document.createElement('div');
                        note.className = 'lock-note';
                        note.style.color = '#6c757d';
                        note.style.fontSize = '12px';
                        note.style.margin = '6px 0';
                        note.innerText = 'Changes locked during active/paused period';
                        const card = container.querySelector('.signatory-card');
                        if (card) card.insertBefore(note, card.firstChild);
                    }
                } else if (note) {
                    note.remove();
                }
            });
        }

        function navigateSchoolYear(direction) {
            // prev/next disabled for now
            updateSchoolYearDisplay();
            updateNavigationButtons();
        }

        function updateSchoolYearDisplay() {
            const currentYear = schoolYears[currentSchoolYearIndex];

            // Guard: if data not yet loaded
            if (!currentYear) {
                const nameEl = document.getElementById('currentYearName');
                const statusEl = document.getElementById('currentYearStatus');
                const termsList = document.getElementById('terms-list');
                if (nameEl) nameEl.textContent = 'No current year';
                if (statusEl) {
                    statusEl.textContent = 'None';
                }
                if (termsList) {
                    termsList.innerHTML = `
                        <div class="term-item inactive">
                            <div class="term-info">
                                <span class="term-name">No terms</span>
                                <span class="term-status inactive">INACTIVE</span>
                            </div>
                        </div>
                    `;
                }
                // Enable Add Year button when no current year
                updateAddYearButton(true);
                return;
            }

            // Update navigation display
            document.getElementById('currentYearName').textContent = currentYear.name;
            document.getElementById('currentYearStatus').textContent = currentYear.status === 'current' ? 'Active' : 'Completed';

            // Update year actions
            updateYearActions(currentYear);

            // Update terms list with debouncing
            debouncedUpdateTermsList(currentYear);
            
            // Update Add Year button based on term status
            updateAddYearButton(false);
            
            // Update lock UI after status refresh
            try { updateLockUI(); } catch (e) {}
        }

        function updateYearActions(schoolYear) {
            // Year actions removed - no edit/delete functionality needed
            // This function is kept for compatibility but does nothing
        }

        function updateAddYearButton(enable) {
            const addYearBtn = document.querySelector('button[onclick="showAddSchoolYearModal()"]');
            if (!addYearBtn) return;
            
            if (enable) {
                // Enable Add Year button
                addYearBtn.disabled = false;
                addYearBtn.className = 'btn btn-sm btn-primary';
                addYearBtn.title = 'Add new school year';
            } else {
                // Check if all terms are ended
                const currentYear = schoolYears[currentSchoolYearIndex];
                if (!currentYear) {
                    addYearBtn.disabled = true;
                    addYearBtn.className = 'btn btn-sm btn-secondary';
                    addYearBtn.title = 'No school year data available';
                    return;
                }
                
                const allTermsEnded = currentYear.terms.every(term => term.status === 'completed');
                
                if (allTermsEnded) {
                    // Enable Add Year button when all terms are ended
                    addYearBtn.disabled = false;
                    addYearBtn.className = 'btn btn-sm btn-primary';
                    addYearBtn.title = 'Add new school year';
                } else {
                    // Disable Add Year button when terms are still active
                    addYearBtn.disabled = true;
                    addYearBtn.className = 'btn btn-sm btn-secondary';
                    addYearBtn.title = 'Cannot add new school year until all terms are ended';
                }
            }
        }

        function updateTermsList(schoolYear) {
            console.log('🔄 updateTermsList: Called with schoolYear:', schoolYear);
            const termsList = document.getElementById('terms-list');
            if (!termsList) return;
            
            termsList.innerHTML = '';
            
            schoolYear.terms.forEach((term, index) => {
                console.log(`🔄 updateTermsList: Processing term ${index + 1}:`, term);
                const termItem = document.createElement('div');
                termItem.className = `term-item ${term.status}`;
                termItem.setAttribute('data-term', term.id); // Add data attribute for targeting
                
                // Check if term is ended/completed - if so, show "Clearance Period Ended"
                if (term.status === 'completed') {
                    const endedHTML = `
                        <div class="term-info">
                            <span class="term-name">${term.name}</span>
                            <span class="term-status completed">Clearance Period Ended</span>
                        </div>
                        <div class="term-actions">
                            <!-- No action buttons for ended terms -->
                        </div>
                    `;
                    termItem.innerHTML = endedHTML;
                } else if (schoolYear.status === 'current') {
                    // Current year and non-ended term - check term dependencies
                    let termActions = '';
                    let statusText = term.status.toUpperCase();
                    
                    // Check if this is Term 2 and Term 1 is not ended
                    if (index === 1) { // Term 2 (index 1)
                        const term1 = schoolYears[currentSchoolYearIndex]?.terms[0];
                        if (term1 && term1.status !== 'completed') {
                            // Term 1 not ended - block Term 2 actions
                            termActions = '';
                            statusText = 'Term 1 ended Required.';
                        } else {
                            // Term 1 is ended - allow Term 2 actions
                            termActions = getTermActions(term);
                        }
                    } else {
                        // Term 1 - no dependency check needed
                        termActions = getTermActions(term);
                    }
                    
                    termItem.innerHTML = `
                        <div class="term-info">
                            <span class="term-name">${term.name}</span>
                            <span class="term-status ${term.status}">${statusText}</span>
                        </div>
                        <div class="term-actions">
                            ${termActions}
                        </div>
                    `;
                } else {
                    // Completed year - read-only
                    termItem.innerHTML = `
                        <div class="term-info">
                            <span class="term-name">${term.name}</span>
                            <span class="term-status ${term.status}">${term.status.toUpperCase()}</span>
                        </div>
                        <div class="term-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTerm('${term.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportTerm('${term.id}')">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    `;
                }
                
                termsList.appendChild(termItem);
            });
        }
        
        // Helper function to get term actions based on status
        function getTermActions(term) {
            if (term.status === 'active') {
                // Term has been activated (active) → Show only "End Term" button
                return `
                    <button class="btn btn-sm btn-danger" onclick="endTerm('${term.id}')" title="End Term">
                        <i class="fa-solid fa-clipboard-check"></i> End Term
                    </button>
                `;
            } else if (term.status === 'inactive') {
                // Term hasn't started → Show "Activate Term" and "Skip/End Term" buttons
                return `
                    <button class="btn btn-sm btn-success" onclick="activateTerm('${term.id}')" title="Activate Term">
                        <i class="fas fa-play"></i> Activate Term
                    </button>
                    <button class="btn btn-sm btn-outline-danger" title="Skip/End Term" onclick="skipEndTerm('${term.id}')">
                        <i class="fa-solid fa-forward"></i> Skip/End Term
                    </button>
                `;
            } else if (term.status === 'completed') {
                // Term has ended → Show only a disabled button that says "Term Ended"
                return `
                    <button class="btn btn-sm btn-outline-secondary" disabled title="Term Ended">
                        <i class="fa-solid fa-check"></i> Term Ended
                    </button>
                `;
            }
            return '';
        }

        function updateNavigationButtons() {
            // Navigation buttons removed from UI - function kept for compatibility
            const prevBtn = document.getElementById('prevYearBtn');
            const nextBtn = document.getElementById('nextYearBtn');
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
        }

        async function activateTerm(termId) {
            const buttonElement = document.querySelector(`[onclick="activateTerm('${termId}')"]`);
            
            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `activate-${termId}`,
                    async () => {
                    // Special handling for Term 2 activation
                    if (termId === 'term2') {
                        // First, ensure Term 1 is ended
                        await ensureTerm1Ended();
                    }

                    // Then activate the requested term
            const currentYear = schoolYears[currentSchoolYearIndex];
                    if (!currentYear) { 
                        throw new Error('Data not loaded yet.');
                    }
                    
            const term = currentYear.terms.find(t => t.id === termId);
                    if (!term) { 
                        throw new Error('Term not found.');
                    }

                        // Check if there's already an active term and handle it
                        try {
                            const activeTermCheck = await fetchJSON(`${API_BASE}/context.php`);
                            const activeSemester = activeTermCheck.terms?.find(t => t.is_active === 1);
                            
                            if (activeSemester && activeSemester.semester_id !== term.semesterId) {
                                // There's another active term, show confirmation
                                const confirmed = await showConfirmationModal(
                                    'Activate Term',
                                    `There is already an active term (${activeSemester.semester_name}). Activating this term will deactivate the current one. Do you want to proceed?`,
                                    'Activate',
                                    'Cancel',
                                    'warning'
                                );
                                
                                if (!confirmed) {
                                    throw new Error('Term activation cancelled by user');
                                }
                            }
                        } catch (checkError) {
                            console.warn('Could not check for active terms:', checkError);
                            // Continue with activation attempt
                        }

                        // Activate the semester (this will also create clearance periods for all sectors)
                        const response = await fetchJSON(`${API_BASE}/periods.php`, { 
                            method: 'PUT', 
                            headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                semester_id: term.semesterId, 
                                action: 'activate_semester' 
                            }) 
                        });
                        
                        if (!response.success) {
                            throw new Error(response.message || 'Failed to activate term');
                    }

                // Refresh data after successful operation
                await ensureFreshData();
                        
                        // Refresh sector buttons to reflect new active term status
                        await initializeSectorButtons();
                        
                        showToast(`Term ${termId} activated successfully`, 'success');
                    },
                    buttonElement
                );
            } catch (error) {
                console.error('Error activating term:', error);
                showToast(error.message || 'Failed to activate term', 'error');
            }
        }
        

        // deactivateTerm function removed - no longer needed per requirements
        

        async function endTerm(termId) {
            const buttonElement = document.querySelector(`[onclick="endTerm('${termId}')"]`);
            
            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `end-${termId}`,
                    async () => {
                    const currentYear = schoolYears[currentSchoolYearIndex];
                    if (!currentYear) { 
                        throw new Error('Data not loaded yet.');
                    }
                    
                    const term = currentYear.terms.find(t => t.id === termId);
                    if (!term) { 
                        throw new Error('Term not found.');
                    }

                        // Check if there are any ongoing clearance periods for this term
                        const ongoingPeriods = await checkOngoingClearancePeriods(term.semesterId);
                        
                        if (ongoingPeriods.length > 0) {
                            // Show styled confirmation modal
                            const confirmed = await showConfirmationModal(
                                'End Term',
                                `Ending this term will also close all clearance periods under it, including ongoing ones (${ongoingPeriods.length} active periods). Do you want to proceed?`,
                                'End Term',
                                'Cancel',
                                'warning'
                            );
                            
                            if (!confirmed) {
                                throw new Error('Term ending cancelled by user');
                            }
                        }

                        // First, end the semester
                        const semesterResponse = await fetchJSON(`${API_BASE}/periods.php`, { 
                        method: 'PUT', 
                        headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                semester_id: term.semesterId, 
                                action: 'end_semester' 
                            }) 
                        });
                        
                        if (!semesterResponse.success) {
                            throw new Error(semesterResponse.message || 'Failed to end semester');
                        }

                        // Then cascade close all clearance periods for this semester
                        const cascadeResponse = await fetchJSON(`${API_BASE}/periods.php`, { 
                            method: 'PUT', 
                            headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                semester_id: term.semesterId, 
                                action: 'cascade_close_periods' 
                            }) 
                        });
                        
                        if (!cascadeResponse.success) {
                            throw new Error(cascadeResponse.message || 'Failed to close clearance periods');
                        }

                // Refresh data after successful operation
                await ensureFreshData();
                        
                        // Refresh sector buttons to reflect new active term status
                        await initializeSectorButtons();
                        
                        // Update Add Year button status
                        updateAddYearButton(false);
                        
                        showToast(`Term ${termId} ended successfully`, 'success');
                    },
                    buttonElement
                );
            } catch (error) {
                console.error('Error ending term:', error);
                showToast(error.message || 'Failed to end term', 'error');
            }
        }
        
        // Skip/End Term function - allows closing term without activation
        async function skipEndTerm(termId) {
            const buttonElement = document.querySelector(`[onclick="skipEndTerm('${termId}')"]`);

            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `skip-end-${termId}`,
                    async () => {
                    const currentYear = schoolYears[currentSchoolYearIndex];
                    if (!currentYear) { 
                        throw new Error('Data not loaded yet.');
                    }
                    
                    const term = currentYear.terms.find(t => t.id === termId);
                    if (!term) { 
                        throw new Error('Term not found.');
                    }

                        // Show confirmation for skipping term
                        const confirmed = await showConfirmationModal(
                            'Skip/End Term',
                            `This will close the term without activation and conclude all clearance periods for this term. Do you want to proceed?`,
                            'Skip/End Term',
                            'Cancel',
                            'warning'
                        );
                        
                        if (!confirmed) {
                            throw new Error('Term skip/end cancelled by user');
                        }

                        // First, end the semester
                        const semesterResponse = await fetchJSON(`${API_BASE}/periods.php`, { 
                        method: 'PUT', 
                        headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                semester_id: term.semesterId, 
                                action: 'end_semester' 
                            }) 
                        });
                        
                        if (!semesterResponse.success) {
                            throw new Error(semesterResponse.message || 'Failed to end semester');
                        }

                        // Then cascade close all clearance periods for this semester
                        const cascadeResponse = await fetchJSON(`${API_BASE}/periods.php`, { 
                            method: 'PUT', 
                            headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                semester_id: term.semesterId, 
                                action: 'cascade_close_periods' 
                            }) 
                        });
                        
                        if (!cascadeResponse.success) {
                            throw new Error(cascadeResponse.message || 'Failed to close clearance periods');
                        }

                // Refresh data after successful operation
                await ensureFreshData();
                        
                        // Refresh sector buttons to reflect new active term status
                        await initializeSectorButtons();
                        
                        // Update Add Year button status
                        updateAddYearButton(false);
                        
                        showToast(`Term ${termId} skipped/ended successfully`, 'success');
                    },
                    buttonElement
                );
            } catch (error) {
                console.error('Error skipping/ending term:', error);
                showToast(error.message || 'Failed to skip/end term', 'error');
            }
        }
        

        // Helper function to check for ongoing clearance periods
        async function checkOngoingClearancePeriods(semesterId) {
            try {
                const response = await fetchJSON(`${API_BASE}/periods.php?semester_id=${semesterId}&status=Ongoing`);
                return response.periods || [];
            } catch (error) {
                console.error('Error checking ongoing clearance periods:', error);
                return [];
            }
        }

        // Grace Period Management System
        // Use the GracePeriodManager from grace-period-manager.js
        let gracePeriodManager;
        
        // Wait for the grace-period-manager.js to load and create a simple wrapper
        function createGracePeriodManager() {
            return {
                activeOperations: new Set(),
                minGracePeriod: 3000,

            async executeWithGracePeriod(operationId, operation, buttonElement) {
                if (this.activeOperations.has(operationId)) {
                    showToast('Operation already in progress', 'warning');
                    return;
                }

                this.activeOperations.add(operationId);
                const startTime = Date.now();

                try {
                    // Set loading state
                    this.setButtonLoadingState(buttonElement, 'Processing...');

                    // Execute the operation
                    const result = await operation();

                    // Calculate remaining grace period
                    const elapsed = Date.now() - startTime;
                    const remainingTime = Math.max(0, this.minGracePeriod - elapsed);

                    // Wait for remaining grace period
                    if (remainingTime > 0) {
                        await new Promise(resolve => setTimeout(resolve, remainingTime));
                    }

                    return result;
                } catch (error) {
                    throw error;
                } finally {
                    // Clear loading state
                    this.clearButtonLoadingState(buttonElement);
                    this.activeOperations.delete(operationId);
                }
                },

            setButtonLoadingState(buttonElement, text = 'Processing...') {
                if (!buttonElement) return;
                
                buttonElement.disabled = true;
                buttonElement.dataset.originalText = buttonElement.innerHTML;
                buttonElement.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    ${text}
                `;
                buttonElement.classList.add('loading');
                },

            clearButtonLoadingState(buttonElement) {
                if (!buttonElement) return;
                
                buttonElement.disabled = false;
                if (buttonElement.dataset.originalText) {
                    buttonElement.innerHTML = buttonElement.dataset.originalText;
                    delete buttonElement.dataset.originalText;
                }
                buttonElement.classList.remove('loading');
            }
            };
        }

        // Initialize grace period manager
        gracePeriodManager = createGracePeriodManager();

        // Sector-based clearance period functions with grace period
        async function startSectorPeriod(sector) {
            const sectorKey = sector === 'Senior High School' ? 'shs' : sector.toLowerCase();
            const buttonElement = document.getElementById(`${sectorKey}-start-btn`);
            
            console.log(`🚀 DEBUG: Starting ${sector} clearance period...`);
            console.log(`🚀 DEBUG: Button element:`, buttonElement);
            
            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `start-${sector}`,
                    async () => {
                        // Check if there's an active term first
                        const activeTerm = await getActiveTerm();
                        console.log(`🚀 DEBUG: Active term data:`, activeTerm);
                        
                        if (!activeTerm) {
                            throw new Error('Cannot start clearance period: No active term found');
                        }

                        const requestData = {
                            sector: sector,
                            academic_year_id: activeTerm.academic_year_id,
                            semester_id: activeTerm.semester_id,
                            start_date: new Date().toISOString().slice(0, 10),
                            action: 'start'
                        };
                        
                        console.log(`🚀 DEBUG: Sending request to API:`, requestData);

                        // Try to update existing period first, then create if needed
                        const response = await fetchJSON(`${API_BASE}/periods.php`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(requestData)
                        });

                        console.log(`🚀 DEBUG: API response:`, response);

                        if (response.success) {
                            console.log(`✅ DEBUG: ${sector} clearance period started successfully`);
                            
                            // Display form distribution results if available
                            let successMessage = `${sector} clearance period started successfully`;
                            if (response.form_distribution) {
                                const dist = response.form_distribution;
                                if (dist.success) {
                                    successMessage += `\n📋 Forms distributed: ${dist.forms_created} forms created for ${dist.eligible_users} eligible users`;
                                    if (dist.signatories_assigned > 0) {
                                        successMessage += `\n👥 Signatories assigned: ${dist.signatories_assigned} total assignments`;
                                    }
                                } else {
                                    console.warn(`⚠️ Form distribution failed: ${dist.message}`);
                                    successMessage += `\n⚠️ Form distribution: ${dist.message}`;
                                }
                            }
                            
                            showToast(successMessage, 'success');
                            
                            console.log(`🔄 DEBUG: Refreshing sector data...`);
                            await refreshSectorData();
                            console.log(`✅ DEBUG: Sector data refreshed`);
                        } else {
                            throw new Error(response.message || 'Failed to start clearance period');
                        }
                    },
                    buttonElement
                );
            } catch (error) {
                console.error(`❌ DEBUG: Error starting ${sector} sector period:`, error);
                showToast(error.message || 'Failed to start clearance period', 'error');
            }
        }

        // Skip clearance period function
        async function skipSectorPeriod(sector) {
            const sectorKey = sector === 'Senior High School' ? 'shs' : sector.toLowerCase();
            const buttonElement = document.getElementById(`${sectorKey}-close-btn`);
            
            console.log(`⏭️ DEBUG: Skipping ${sector} clearance period...`);
            console.log(`⏭️ DEBUG: Button element:`, buttonElement);
            
            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `skip-${sector}`,
                    async () => {
                        // Check if there's an active term first
                        const activeTerm = await getActiveTerm();
                        console.log(`⏭️ DEBUG: Active term data:`, activeTerm);
                        
                        if (!activeTerm) {
                            throw new Error('Cannot skip clearance period: No active term found');
                        }

                        const requestData = {
                            sector: sector,
                            academic_year_id: activeTerm.academic_year_id,
                            semester_id: activeTerm.semester_id,
                            start_date: new Date().toISOString().slice(0, 10),
                            action: 'skip'
                        };
                        
                        console.log(`⏭️ DEBUG: Sending request to API:`, requestData);

                        const response = await fetchJSON(`${API_BASE}/periods.php`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(requestData)
                        });

                        console.log(`⏭️ DEBUG: API response:`, response);

                        if (response.success) {
                            console.log(`✅ DEBUG: ${sector} clearance period skipped successfully`);
                            showToast(`${sector} clearance period skipped successfully`, 'success');
                            
                            console.log(`🔄 DEBUG: Refreshing sector data...`);
                            await refreshSectorData();
                            console.log(`✅ DEBUG: Sector data refreshed`);
                        } else {
                            throw new Error(response.message || 'Failed to skip clearance period');
                        }
                    },
                    buttonElement
                );
            } catch (error) {
                console.error(`❌ DEBUG: Error skipping ${sector} sector period:`, error);
                showToast(error.message || 'Failed to skip clearance period', 'error');
            }
        }

        async function pauseSectorPeriod(sector) {
            const sectorKey = sector === 'Senior High School' ? 'shs' : sector.toLowerCase();
            const buttonElement = document.getElementById(`${sectorKey}-pause-btn`);
            
            console.log(`⏸️ DEBUG: Pausing ${sector} clearance period...`);
            console.log(`⏸️ DEBUG: Button element:`, buttonElement);
            
            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `pause-${sector}`,
                    async () => {
                        // Get the current period data to extract period_id
                        const activeTerm = await getActiveTerm();
                        console.log(`⏸️ DEBUG: Active term data:`, activeTerm);

                        if (!activeTerm) {
                            throw new Error('Cannot pause clearance period: No active term found');
                        }

                        // Get the current period for this sector
                        const response = await fetch(`${API_BASE}/sector-periods.php`, {
                            credentials: 'include'
                        });
                        const data = await response.json();
                        
                        if (data.success && data.periods_by_sector && data.periods_by_sector[sector]) {
                            const currentPeriod = data.periods_by_sector[sector][0]; // Get latest period
                            console.log(`⏸️ DEBUG: Current period for ${sector}:`, currentPeriod);
                            
                            if (!currentPeriod || !currentPeriod.period_id) {
                                throw new Error(`No active period found for ${sector}`);
                            }

                            const requestData = {
                                period_id: currentPeriod.period_id,
                                action: 'pause'
                            };

                            console.log(`⏸️ DEBUG: Sending request to API:`, requestData);

                            const apiResponse = await fetchJSON(`${API_BASE}/periods.php`, {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(requestData)
                            });

                            console.log(`⏸️ DEBUG: API response:`, apiResponse);

                            if (apiResponse.success) {
                                console.log(`✅ DEBUG: ${sector} clearance period paused successfully`);
                                showToast(`${sector} clearance period paused`, 'success');
                                await refreshSectorData();
                            } else {
                                throw new Error(apiResponse.message || 'Failed to pause clearance period');
                            }
                        } else {
                            throw new Error(`No period data found for ${sector}`);
                        }
                    },
                    buttonElement
                );
            } catch (error) {
                console.error(`❌ DEBUG: Error pausing ${sector} sector period:`, error);
                showToast(error.message || 'Failed to pause clearance period', 'error');
            }
        }

        async function closeSectorPeriod(sector) {
            const sectorKey = sector === 'Senior High School' ? 'shs' : sector.toLowerCase();
            const buttonElement = document.getElementById(`${sectorKey}-close-btn`);
            
            console.log(`🛑 DEBUG: Closing ${sector} clearance period...`);
            console.log(`🛑 DEBUG: Button element:`, buttonElement);
            
            try {
                await gracePeriodManager.executeWithGracePeriod(
                    `close-${sector}`,
                    async () => {
                        // Get the current period data to extract period_id
                        const activeTerm = await getActiveTerm();
                        console.log(`🛑 DEBUG: Active term data:`, activeTerm);

                        if (!activeTerm) {
                            throw new Error('Cannot close clearance period: No active term found');
                        }

                        // Get the current period for this sector
                        const response = await fetch(`${API_BASE}/sector-periods.php`, {
                            credentials: 'include'
                        });
                        const data = await response.json();
                        
                        if (data.success && data.periods_by_sector && data.periods_by_sector[sector]) {
                            const currentPeriod = data.periods_by_sector[sector][0]; // Get latest period
                            console.log(`🛑 DEBUG: Current period for ${sector}:`, currentPeriod);
                            
                            if (!currentPeriod || !currentPeriod.period_id) {
                                throw new Error(`No active period found for ${sector}`);
                            }

                            const requestData = {
                                period_id: currentPeriod.period_id,
                                action: 'close'
                            };

                            console.log(`🛑 DEBUG: Sending request to API:`, requestData);

                            const apiResponse = await fetchJSON(`${API_BASE}/periods.php`, {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(requestData)
                            });

                            console.log(`🛑 DEBUG: API response:`, apiResponse);

                            if (apiResponse.success) {
                                console.log(`✅ DEBUG: ${sector} clearance period closed successfully`);
                                showToast(`${sector} clearance period closed`, 'success');
                                await refreshSectorData();
                            } else {
                                throw new Error(apiResponse.message || 'Failed to close clearance period');
                            }
                        } else {
                            throw new Error(`No period data found for ${sector}`);
                        }
                    },
                    buttonElement
                );
            } catch (error) {
                console.error(`❌ DEBUG: Error closing ${sector} sector period:`, error);
                showToast(error.message || 'Failed to close clearance period', 'error');
            }
        }

        // Helper function to get active term
        async function getActiveTerm() {
            try {
                const response = await fetchJSON(`${API_BASE}/context.php`);
                const activeSemester = response.terms?.find(term => term.is_active === 1);
                return activeSemester ? {
                    academic_year_id: response.academic_year.academic_year_id,
                    semester_id: activeSemester.semester_id
                } : null;
            } catch (error) {
                console.error('Error getting active term:', error);
                return null;
            }
        }

        // Helper function to refresh sector data
        async function refreshSectorData() {
            console.log(`🔄 DEBUG: refreshSectorData called`);
            try {
                console.log(`🔄 DEBUG: Loading sector periods...`);
                // Refresh sector period data
                await loadSectorPeriods();
                console.log(`✅ DEBUG: Sector periods loaded`);
                
                console.log(`🔄 DEBUG: Loading signatory lists...`);
                // Refresh signatory lists
                await loadScopeSignatories('College');
                await loadScopeSignatories('Senior High School');
                await loadScopeSignatories('Faculty');
                console.log(`✅ DEBUG: Signatory lists loaded`);
                
                console.log(`🔄 DEBUG: Skipping initializeSectorButtons() - buttons already updated by updateSectorCard()`);
                
            } catch (error) {
                console.error('❌ DEBUG: Error refreshing sector data:', error);
            }
        }

        // Load sector periods data
        async function loadSectorPeriods() {
            console.log(`📊 DEBUG: loadSectorPeriods called`);
            try {
                console.log(`📊 DEBUG: Fetching from ${API_BASE}/sector-periods.php`);
                // Check if the API endpoint exists first
                const response = await fetch(`${API_BASE}/sector-periods.php`, {
                    credentials: 'include'
                });
                
                console.log(`📊 DEBUG: Response status: ${response.status}`);
                
                if (!response.ok) {
                    console.warn('⚠️ DEBUG: Sector periods API not available, using fallback data');
                    // Use fallback data structure
                    const fallbackPeriods = {
                        'College': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }],
                        'Senior High School': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }],
                        'Faculty': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }]
                    };
                    console.log(`📊 DEBUG: Using fallback data:`, fallbackPeriods);
                    updateSectorPeriodsDisplay(fallbackPeriods);
                    return;
                }
                
                const data = await response.json();
                console.log(`📊 DEBUG: API response data:`, data);
                
                if (data.success && data.periods_by_sector) {
                    console.log(`📊 DEBUG: Updating display with API data:`, data.periods_by_sector);
                    updateSectorPeriodsDisplay(data.periods_by_sector);
                } else {
                    console.warn('⚠️ DEBUG: Sector periods API returned no data');
                    // Use fallback data
                    const fallbackPeriods = {
                        'College': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }],
                        'Senior High School': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }],
                        'Faculty': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }]
                    };
                    console.log(`📊 DEBUG: Using fallback data:`, fallbackPeriods);
                    updateSectorPeriodsDisplay(fallbackPeriods);
                }
            } catch (error) {
                console.error('❌ DEBUG: Error loading sector periods:', error);
                // Use fallback data on error
                const fallbackPeriods = {
                    'College': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }],
                    'Senior High School': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }],
                    'Faculty': [{ status: 'Not Started', start_date: null, end_date: null, total_forms: 0, completed_forms: 0 }]
                };
                console.log(`📊 DEBUG: Using fallback data due to error:`, fallbackPeriods);
                updateSectorPeriodsDisplay(fallbackPeriods);
            }
        }

        // Update sector periods display
        function updateSectorPeriodsDisplay(periodsBySector) {
            console.log(`🎨 DEBUG: updateSectorPeriodsDisplay called with:`, periodsBySector);
            const sectors = ['College', 'Senior High School', 'Faculty'];
            
            sectors.forEach(sector => {
                const sectorPeriods = periodsBySector[sector];
                console.log(`🎨 DEBUG: Processing ${sector}:`, sectorPeriods);
                
                if (sectorPeriods && sectorPeriods.length > 0) {
                    // Get the most recent period for this sector
                    const latestPeriod = sectorPeriods[0]; // API returns periods ordered by created_at DESC
                    console.log(`🎨 DEBUG: Latest period for ${sector}:`, latestPeriod);
                    updateSectorCard(sector, latestPeriod);
                } else {
                    console.warn(`⚠️ DEBUG: No period data for ${sector}`);
                    // Use fallback data
                    const fallbackPeriod = { 
                        status: 'Not Started', 
                        start_date: null, 
                        end_date: null, 
                        total_forms: 0, 
                        completed_forms: 0 
                    };
                    updateSectorCard(sector, fallbackPeriod);
                }
            });
        }

        // Update individual sector card
        function updateSectorCard(sector, period) {
            const sectorKey = sector === 'Senior High School' ? 'shs' : sector.toLowerCase();
            console.log(`🎨 DEBUG: updateSectorCard called for ${sector} (${sectorKey}) with period:`, period);
            
            // Update status badge
            const statusBadge = document.getElementById(`${sectorKey}-status-badge`);
            if (statusBadge) {
                statusBadge.textContent = period.status || 'Not Started';
                statusBadge.className = `status-badge ${(period.status || 'not-started').toLowerCase().replace(' ', '-')}`;
                console.log(`🎨 DEBUG: Updated status badge for ${sector}: ${period.status}`);
            } else {
                console.warn(`⚠️ DEBUG: Status badge not found for ${sector} (${sectorKey}-status-badge)`);
            }

            // Update dates
            const startDate = document.getElementById(`${sectorKey}-start-date`);
            const endDate = document.getElementById(`${sectorKey}-end-date`);
            if (startDate) startDate.textContent = period.start_date || '-';
            
            // Only show end date if the period is actually ended (status = 'Closed')
            if (endDate) {
                if (period.status === 'Closed' && period.end_date) {
                    endDate.textContent = period.end_date;
                    endDate.style.display = 'block';
                } else {
                    endDate.textContent = 'Not ended';
                    endDate.style.display = 'none'; // Hide end date for ongoing periods
                }
            }
            console.log(`🎨 DEBUG: Updated dates for ${sector}: start=${period.start_date}, end=${period.end_date}, status=${period.status}`);

            // Update statistics
            const applications = document.getElementById(`${sectorKey}-applications`);
            const completed = document.getElementById(`${sectorKey}-completed`);
            if (applications) applications.textContent = period.total_forms || 0;
            if (completed) completed.textContent = period.completed_forms || 0;
            console.log(`🎨 DEBUG: Updated stats for ${sector}: applications=${period.total_forms}, completed=${period.completed_forms}`);

            // Update buttons
            console.log(`🎨 DEBUG: Updating buttons for ${sector} with status: ${period.status}`);
            updateSectorButtons(sector, period.status || 'Not Started');
        }

        // Update sector buttons based on status and term state
        async function updateSectorButtons(sector, status) {
            const sectorKey = sector === 'Senior High School' ? 'shs' : sector.toLowerCase();
            console.log(`🔧 DEBUG: updateSectorButtons called for ${sector} (${sectorKey}) with status: ${status}`);
            
            const startBtn = document.getElementById(`${sectorKey}-start-btn`);
            const pauseBtn = document.getElementById(`${sectorKey}-pause-btn`);
            const closeBtn = document.getElementById(`${sectorKey}-close-btn`);

            console.log(`🔧 DEBUG: Button elements found:`, {
                startBtn: !!startBtn,
                pauseBtn: !!pauseBtn,
                closeBtn: !!closeBtn
            });

            // Check if buttons exist before proceeding
            if (!startBtn && !pauseBtn && !closeBtn) {
                console.warn(`⚠️ DEBUG: Sector buttons not found for ${sector}`);
                return;
            }

            // Hide all buttons first
            if (startBtn) startBtn.style.display = 'none';
            if (pauseBtn) pauseBtn.style.display = 'none';
            if (closeBtn) closeBtn.style.display = 'none';
            console.log(`🔧 DEBUG: Hidden all buttons for ${sector}`);

            // Check if there's an active term
            const hasActiveTerm = await checkActiveTerm();
            
            console.log(`🔧 DEBUG: Updating buttons for ${sector}: status=${status}, hasActiveTerm=${hasActiveTerm}`);
            
            // Show appropriate buttons based on status and term state
            switch (status) {
                case 'Not Started':
                    console.log(`🔧 DEBUG: Case 'Not Started' for ${sector}, hasActiveTerm: ${hasActiveTerm}`);
                    if (hasActiveTerm) {
                        // Term active but clearance not started → Show "Start Clearance Period" and "Skip Clearance Period"
                        console.log(`🔧 DEBUG: Showing Start and Skip buttons for ${sector}`);
                        if (startBtn) {
                            startBtn.innerHTML = '<i class="fas fa-play"></i> Start Clearance Period';
                            startBtn.style.display = 'inline-block';
                            startBtn.disabled = false;
                            startBtn.className = 'btn btn-sm btn-success';
                            startBtn.setAttribute('onclick', `startSectorPeriod('${sector}')`);
                            console.log(`🔧 DEBUG: Start button updated for ${sector}`);
                        }
                        if (closeBtn) {
                            closeBtn.innerHTML = '<i class="fas fa-forward"></i> Skip Clearance Period';
                            closeBtn.style.display = 'inline-block';
                            closeBtn.disabled = false;
                            closeBtn.className = 'btn btn-sm btn-outline-warning';
                            closeBtn.setAttribute('onclick', `skipSectorPeriod('${sector}')`);
                            console.log(`🔧 DEBUG: Skip button updated for ${sector}`);
                        }
                    } else {
                        // No active term → Disabled "Start Clearance Period" button
                        console.log(`🔧 DEBUG: No active term, showing disabled Start button for ${sector}`);
                        if (startBtn) {
                            startBtn.innerHTML = '<i class="fas fa-play"></i> Start Clearance Period';
                            startBtn.style.display = 'inline-block';
                            startBtn.disabled = true;
                            startBtn.className = 'btn btn-sm btn-secondary';
                            startBtn.title = 'No active term - cannot start clearance period';
                            console.log(`🔧 DEBUG: Disabled Start button updated for ${sector}`);
                        }
                    }
                    break;
                    
                case 'Ongoing':
                    console.log(`🔧 DEBUG: Case 'Ongoing' for ${sector}`);
                    // Clearance started (Ongoing) → Show "Pause Clearance Period" and "End Clearance Period"
                    if (pauseBtn) {
                        pauseBtn.innerHTML = '<i class="fas fa-pause"></i> Pause Clearance Period';
                        pauseBtn.style.display = 'inline-block';
                        pauseBtn.disabled = false;
                        pauseBtn.className = 'btn btn-sm btn-warning';
                        pauseBtn.setAttribute('onclick', `pauseSectorPeriod('${sector}')`);
                        console.log(`🔧 DEBUG: Pause button updated for ${sector}`);
                    }
                    if (closeBtn) {
                        closeBtn.innerHTML = '<i class="fas fa-stop"></i> End Clearance Period';
                        closeBtn.style.display = 'inline-block';
                        closeBtn.disabled = false;
                        closeBtn.className = 'btn btn-sm btn-danger';
                        closeBtn.setAttribute('onclick', `closeSectorPeriod('${sector}')`);
                        console.log(`🔧 DEBUG: End button updated for ${sector}`);
                    }
                    break;
                    
                case 'Paused':
                    console.log(`🔧 DEBUG: Case 'Paused' for ${sector}`);
                    // Clearance paused → Show "Resume Clearance Period" and "End Clearance Period"
                    if (startBtn) {
                        startBtn.innerHTML = '<i class="fas fa-play"></i> Resume Clearance Period';
                        startBtn.style.display = 'inline-block';
                        startBtn.disabled = false;
                        startBtn.className = 'btn btn-sm btn-success';
                        startBtn.setAttribute('onclick', `startSectorPeriod('${sector}')`);
                        console.log(`🔧 DEBUG: Resume button updated for ${sector}`);
                    }
                    if (closeBtn) {
                        closeBtn.innerHTML = '<i class="fas fa-stop"></i> End Clearance Period';
                        closeBtn.style.display = 'inline-block';
                        closeBtn.disabled = false;
                        closeBtn.className = 'btn btn-sm btn-danger';
                        closeBtn.setAttribute('onclick', `closeSectorPeriod('${sector}')`);
                        console.log(`🔧 DEBUG: End button updated for ${sector}`);
                    }
                    break;
                    
                case 'Closed':
                    console.log(`🔧 DEBUG: Case 'Closed' for ${sector}`);
                    // Clearance ended → Show disabled "Ended" button
                    if (closeBtn) {
                        closeBtn.innerHTML = '<i class="fas fa-check"></i> Ended';
                        closeBtn.style.display = 'inline-block';
                        closeBtn.disabled = true;
                        closeBtn.className = 'btn btn-sm btn-outline-secondary';
                        console.log(`🔧 DEBUG: Ended button updated for ${sector}`);
                    }
                    break;
            }
        }

        // Check if there's an active term
        async function checkActiveTerm() {
            try {
                const response = await fetchJSON(`${API_BASE}/context.php`);
                const activeSemester = response.terms?.find(term => term.is_active === 1);
                return !!activeSemester;
            } catch (error) {
                console.error('Error checking active term:', error);
                return false;
            }
        }

        // Reset Term function commented out as per requirements
        /*
        async function resetTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            if (!currentYear) { showToast('Data not loaded yet.', 'warning'); return; }
            const term = currentYear.terms.find(t => t.id === termId);
            if (!term) { showToast('Term not found.', 'error'); return; }
            if (term.status === 'active') { showToast('Cannot reset an active term. Deactivate it first.', 'warning'); return; }
            if (term.status === 'completed') { showToast('Cannot reset an ended term.', 'info'); return; }
            if (term.status === 'inactive') { showToast('Cannot reset. No period exists yet for this term.', 'info'); return; }
            if (term.status !== 'deactivated') { showToast('Reset is allowed only for deactivated terms.', 'warning'); return; }

            const dataSummary = 'This will revert all clearance progress to Unapplied for this paused term.';

            showConfirmation(
                'Reset Term',
                `Reset ${term.name}? ${dataSummary}`,
                'Reset Term',
                'Cancel',
                async () => {
                    try {
                        if (!term.periodId) { showToast('No period exists for this term.', 'warning'); return; }
                        await fetchJSON(`${API_BASE}/reset_by_period.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: term.periodId }) });
                        showToast(`${term.name} reset successfully!`, 'success');
                        await loadCurrentYearAndTerms();
                        updateSchoolYearDisplay();
                    } catch (e) { console.error(e); showToast(e.message || 'Failed to reset term', 'error'); }
                },
                'warning'
            );
        }
        */

        async function deleteTerm(termId) {
            const confirmed = await showConfirmationModal(
                'Delete Term',
                `Are you sure you want to delete ${termId}? This action cannot be undone.`,
                'Delete',
                'Cancel',
                'danger'
            );
            
            if (confirmed) {
                showToast(`${termId} deleted successfully!`, 'success');
                // Implementation for deleting term
            }
        }

        function viewTerm(termId) {
            showToast(`Viewing ${termId} data...`, 'info');
        }

        function exportTerm(termId) {
            showToast(`Exporting ${termId} data...`, 'info');
        }

        function viewSchoolYear(yearId) {
            showToast(`Viewing ${yearId} data...`, 'info');
        }

        function exportSchoolYear(yearId) {
            showToast(`Exporting ${yearId} data...`, 'info');
        }

        function addSignatory(type) {
            showAddSignatoryModal(type);
        }

        function openSignatorySettingsModal(type) {
            console.log('Opening signatory settings modal for:', type);
            
            // Check if the modal function exists
            if (typeof window.openSignatorySettingsModal === 'function') {
                console.log('Using window function from modal');
                window.openSignatorySettingsModal(type);
            } else {
                console.error('Modal function not found. Please ensure SignatorySettingsModal.php is properly loaded.');
            }
        }

        function openExportModal() {
            openClearanceExportModal();
        }

        // Initialize accordions as expanded by default
        document.addEventListener('DOMContentLoaded', async function() {
            // All accordions start expanded
            const accordions = document.querySelectorAll('.accordion-content');
            accordions.forEach(accordion => {
                accordion.style.display = 'block';
            });
            
            // Initialize Activity Tracker (only if not already initialized)
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized');
            }
            
            // Test if modal HTML is loaded
            const modal = document.querySelector('.signatory-settings-modal-overlay');
            if (modal) {
                console.log('✅ Modal HTML is loaded in DOM');
            } else {
                console.error('❌ Modal HTML is NOT loaded in DOM');
            }
            
            // Load data then render UI
            try {
                await loadCurrentYearAndTerms();
            } catch (e) {
                console.error('Failed to load academic context:', e);
            }
            updateSchoolYearDisplay();
            updateNavigationButtons();
            try { updateLockUI(); } catch (e) {}
            
            // Ensure sidebar backdrop functionality
            const backdrop = document.getElementById('sidebar-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('active');
                        this.style.display = 'none';
                    }
                });
            }
            
            // Close sidebar on window resize
            window.addEventListener('resize', function() {
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.getElementById('sidebar-backdrop');
                
                if (window.innerWidth > 768) {
                    if (sidebar) sidebar.classList.remove('active');
                    if (backdrop) backdrop.style.display = 'none';
                }
            });
            
            // Load scope signatories and sector periods
            loadScopeSignatories('College').catch(()=>{});
            loadScopeSignatories('Senior High School').catch(()=>{});
            loadScopeSignatories('Faculty').catch(()=>{});
            
            // Initialize sector buttons based on active term status
            await initializeSectorButtons();
            
                // Load grace period monitoring
                // TODO: Uncomment when grace period functionality is ready
                // loadGracePeriodMonitoring();
                
                // Refresh grace period monitoring every 30 seconds
                // TODO: Uncomment when grace period functionality is ready
                // setInterval(loadGracePeriodMonitoring, 30000);
        });

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar) {
                if (window.innerWidth <= 768) {
                    // Mobile: toggle sidebar overlay with backdrop
                    sidebar.classList.toggle('active');
                    
                    // Show/hide backdrop only on mobile
                    if (backdrop) {
                        if (sidebar.classList.contains('active')) {
                            backdrop.style.display = 'block';
                        } else {
                            backdrop.style.display = 'none';
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
                    }
                }
            }
        }

        // Grace Period Monitoring Functions
        // TODO: Uncomment when grace period functionality is ready
        /*
        async function loadGracePeriodMonitoring() {
            try {
                const sectors = ['College', 'Senior High School', 'Faculty'];
                const gracePeriodGrid = document.getElementById('grace-period-grid');
                
                if (!gracePeriodGrid) return;
                
                gracePeriodGrid.innerHTML = '';
                
                for (const sector of sectors) {
                    const gracePeriodData = await fetchGracePeriodData(sector);
                    const gracePeriodCard = createGracePeriodCard(sector, gracePeriodData);
                    gracePeriodGrid.appendChild(gracePeriodCard);
                }
            } catch (error) {
                console.error('Error loading grace period monitoring:', error);
            }
        }
        
        async function fetchGracePeriodData(sector) {
            try {
                const response = await fetch(`../../api/clearance/period_status.php?clearance_type=${encodeURIComponent(sector)}&include_grace_period=true`, {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                return data;
            } catch (error) {
                console.error(`Error fetching grace period data for ${sector}:`, error);
                return null;
            }
        }
        
        function createGracePeriodCard(sector, data) {
            const card = document.createElement('div');
            card.className = 'grace-period-card';
            card.id = `grace-period-${sector.replace(/\s+/g, '-').toLowerCase()}`;
            
            const isInGracePeriod = data && data.grace_period && data.grace_period.is_active;
            const status = data ? data.period_status : 'unknown';
            const period = data ? data.period : null;
            
            card.innerHTML = `
                <div class="grace-period-card-header">
                    <h4><i class="fas fa-${getSectorIcon(sector)}"></i> ${sector}</h4>
                    <span class="status-badge ${getStatusClass(status)}">${getStatusText(status)}</span>
                </div>
                <div class="grace-period-card-content">
                    ${period ? `
                        <div class="period-info">
                            <div class="info-item">
                                <span class="label">Period:</span>
                                <span class="value">${period.school_year} - ${period.semester_name}</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Status:</span>
                                <span class="value">${period.status}</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Updated:</span>
                                <span class="value">${formatDateTime(period.updated_at)}</span>
                            </div>
                        </div>
                    ` : '<div class="no-data">No active period</div>'}
                    
                    ${isInGracePeriod ? `
                        <div class="grace-period-active">
                            <div class="grace-period-timer">
                                <i class="fas fa-clock"></i>
                                <span class="countdown" id="countdown-${sector.replace(/\s+/g, '-').toLowerCase()}">
                                    ${window.gracePeriodUIManager.gracePeriodManager.formatTime(data.grace_period.remaining_seconds)}
                                </span>
                            </div>
                            <div class="grace-period-progress">
                                <div class="progress-bar" style="width: ${getGracePeriodProgress(data.grace_period)}%"></div>
                            </div>
                            <div class="grace-period-actions">
                                <button class="btn btn-sm btn-warning" onclick="overrideGracePeriod('${sector}')">
                                    <i class="fas fa-stop"></i> Override Grace Period
                                </button>
                            </div>
                        </div>
                    ` : `
                        <div class="grace-period-inactive">
                            <i class="fas fa-check-circle"></i>
                            <span>No grace period active</span>
                        </div>
                    `}
                </div>
            `;
            
            // Start countdown if in grace period
            if (isInGracePeriod) {
                startGracePeriodCountdown(sector, data.grace_period);
            }
            
            return card;
        }
        
        function getSectorIcon(sector) {
            switch (sector) {
                case 'College': return 'university';
                case 'Senior High School': return 'graduation-cap';
                case 'Faculty': return 'chalkboard-teacher';
                default: return 'users';
            }
        }
        
        function getStatusClass(status) {
            switch (status) {
                case 'ongoing': return 'status-active';
                case 'grace_period': return 'status-warning';
                case 'paused': return 'status-paused';
                case 'closed': return 'status-closed';
                case 'not_started': return 'status-inactive';
                default: return 'status-unknown';
            }
        }
        
        function getStatusText(status) {
            switch (status) {
                case 'ongoing': return 'Active';
                case 'grace_period': return 'Grace Period';
                case 'paused': return 'Paused';
                case 'closed': return 'Closed';
                case 'not_started': return 'Not Started';
                default: return 'Unknown';
            }
        }
        
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
        
        function getGracePeriodProgress(gracePeriod) {
            const totalSeconds = gracePeriod.duration_minutes * 60;
            const elapsed = totalSeconds - gracePeriod.remaining_seconds;
            return Math.min(100, (elapsed / totalSeconds) * 100);
        }
        
        function startGracePeriodCountdown(sector, gracePeriod) {
            const countdownElement = document.getElementById(`countdown-${sector.replace(/\s+/g, '-').toLowerCase()}`);
            if (!countdownElement) return;
            
            let remainingSeconds = gracePeriod.remaining_seconds;
            
            const timer = setInterval(() => {
                remainingSeconds--;
                countdownElement.textContent = window.gracePeriodUIManager.gracePeriodManager.formatTime(remainingSeconds);
                
                if (remainingSeconds <= 0) {
                    clearInterval(timer);
                    // Refresh the grace period monitoring
                    setTimeout(() => loadGracePeriodMonitoring(), 1000);
                }
            }, 1000);
        }
        
        async function overrideGracePeriod(sector) {
            if (!confirm(`Are you sure you want to override the grace period for ${sector}? This will immediately allow students to apply.`)) {
                return;
            }
            
            try {
                // This would need to be implemented in the backend
                const response = await fetch('../../api/clearance/override_grace_period.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ sector: sector }),
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    showToast(`Grace period overridden for ${sector}`, 'success');
                    loadGracePeriodMonitoring();
                } else {
                    throw new Error('Failed to override grace period');
                }
            } catch (error) {
                console.error('Error overriding grace period:', error);
                showToast('Failed to override grace period', 'error');
            }
        }
        */

        // Initial load of scope lists and sector periods
        // This is now handled in the main DOMContentLoaded event above

        // Initialize sector buttons based on active term status
        async function initializeSectorButtons() {
            try {
                const hasActiveTerm = await checkActiveTerm();
                console.log('🔍 Active term status:', hasActiveTerm);
                
                // Load current sector periods and update buttons based on actual status
                await loadSectorPeriods();
            } catch (error) {
                console.error('Error initializing sector buttons:', error);
            }
        }
    </script>

    <!-- Include Signatory Settings Modal -->
    <?php include '../../Modals/SignatorySettingsModal.php'; ?>
    
    <!-- View Past Clearances Modal -->
    <?php include '../../Modals/ViewPastClearancesModal.php'; ?>
    
    <!-- Include Alerts Component -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Alerts JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Override the alerts.js executeConfirmedAction to prevent conflicts -->
    <script>
        // Override the alerts.js executeConfirmedAction to prevent conflicts
        window.executeConfirmedAction = function() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
            
            if (window.confirmationResolve) {
                window.confirmationResolve(true);
                window.confirmationResolve = null;
            }
        }
    </script>
    
    <!-- Include Sector Clearance JavaScript -->
    <!-- Temporarily disabled to prevent JSON parsing errors -->
    <!-- <script src="../../assets/js/sector-clearance.js"></script> -->
</body>
</html> 