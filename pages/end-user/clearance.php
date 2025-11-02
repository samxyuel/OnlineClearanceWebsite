<?php
// Online Clearance Website - Unified End-User Clearance Viewing Page
// Handles both Students (College & SHS) and Faculty dynamically
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Status - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/sector-clearance.css">
    <link rel="stylesheet" href="../../assets/css/clearance-status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h2><i class="fas fa-file-alt"></i> My Clearance</h2>
                    <p class="page-description">View and manage your <?php echo $user_type === 'faculty' ? 'faculty' : 'student'; ?> clearance applications.</p>
                </div>

                <!-- Clearance Snapshot Summary -->
                <div class="clearance-status-summary">
                    <div class="summary-header">
                        <div class="summary-title">
                            <i class="fas fa-file-alt"></i>
                            <span>Clearance Form</span>
                        </div>
                        <button class="btn btn-primary" onclick="exportClearance()">
                            <i class="fas fa-download"></i> Export Clearance Form
                        </button>
                    </div>

                    <div class="summary-top-row">
                        <div class="summary-selector">
                            <label for="schoolYearTerm">
                                <i class="fas fa-calendar-alt"></i> School Year & Term
                            </label>
                            <select id="schoolYearTerm" onchange="loadPeriodStatusAndData()">
                                <option value="">Loading periods...</option>
                            </select>
                        </div>

                        <div class="summary-card status-summary-card">
                            <div id="period-status-banner" class="period-status-banner" style="display: none;">
                                <div class="status-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="status-content">
                                    <h4 id="period-status-title">Period Status</h4>
                                    <p id="period-status-message">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-card form-summary-card">
                            <div class="summary-card-heading">
                                <h4>Form Details</h4>
                            </div>
                            <div id="clearanceFormBanner" class="form-summary" style="display: none;">
                                <div class="form-detail">
                                    <span class="detail-label">Form ID</span>
                                    <span class="detail-value" id="clearanceFormId">Loading...</span>
                                </div>
                                <div class="form-detail">
                                    <span class="detail-label">Period</span>
                                    <span class="detail-value" id="bannerPeriod">Loading...</span>
                                </div>
                                <div class="form-detail">
                                    <span class="detail-label">Clearance Progress</span>
                                    <span class="detail-value" id="overallStatusText">Loading...</span>
                                </div>
                            </div>
                            <div class="form-extra-meta">
                                <span class="meta-item"><strong>Sector:</strong> <?php echo $user_sector; ?></span>
                                <span class="meta-item"><strong>Account:</strong> <?php echo $first_name . ' ' . $last_name; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="clearance-tab-navigation">
                    <button class="tab-button active" onclick="switchTab('card')" data-tab="card">
                        <i class="fas fa-th-large"></i> Card View
                    </button>
                    <button class="tab-button" onclick="switchTab('table')" data-tab="table">
                        <i class="fas fa-table"></i> Table View
                    </button>
                    <button class="tab-button" onclick="switchTab('timeline')" data-tab="timeline">
                        <i class="fas fa-clock"></i> Timeline
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Card View Tab -->
                    <div id="card-tab" class="tab-pane active">
                        <div class="signatory-cards-grid" id="signatoryCardsGrid">
                            <!-- Dynamic signatory cards will be generated here -->
                            <div class="no-signatories" id="noSignatoriesMessage" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <p>No signatories assigned for this clearance period.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Table View Tab -->
                    <div id="table-tab" class="tab-pane">
                        <div class="table-container">
                            <table class="clearance-table">
                                <thead>
                                    <tr>
                                        <th>Signatory/Role</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Date Signed</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="signatoryTableBody">
                                    <!-- Dynamic table rows will be generated here -->
                                </tbody>
                            </table>
                            <div class="no-signatories" id="noSignatoriesTableMessage" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <p>No signatories assigned for this clearance period.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline View Tab -->
                    <div id="timeline-tab" class="tab-pane">
                        <div class="timeline-container">
                            <div class="timeline-item pending">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4><?php echo ucfirst($user_type); ?> Clearance Application Submitted</h4>
                                    <p>Application submitted for 2027-2028 1st Semester</p>
                                    <span class="timeline-date">Dec 15, 2024</span>
                                </div>
                            </div>
                            
                            <div class="timeline-item pending">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Awaiting Signatory Approvals</h4>
                                    <p><?php if ($user_type === 'faculty'): ?>
                                        All signatories pending: Department Head, Library, Finance, HR
                                    <?php else: ?>
                                        All signatories pending: Cashier, Librarian, Program Head, Registrar
                                    <?php endif; ?></p>
                                    <span class="timeline-date">In Progress</span>
                                </div>
                            </div>
                            
                            <div class="timeline-item future">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Clearance Completion</h4>
                                    <p>All signatories must approve for clearance completion</p>
                                    <span class="timeline-date">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </main>

    
    <script>
    // Global variables
    let currentPeriodData = null;
    let currentButtonStates = [];
    let refreshInterval = null;
    
    // User information from PHP
    const userInfo = {
        id: <?php echo $user_id; ?>,
        type: '<?php echo $user_type; ?>',
        sector: '<?php echo $user_sector; ?>',
        firstName: '<?php echo $first_name; ?>',
        lastName: '<?php echo $last_name; ?>'
    };

    // Tab switching function
    function switchTab(tabName) {
        // Hide all tab panes
        const tabPanes = document.querySelectorAll('.tab-pane');
        tabPanes.forEach(pane => pane.classList.remove('active'));
        
        // Remove active class from all tab buttons
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => button.classList.remove('active'));
        
        // Show selected tab pane
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
    }
    
    // View details function
    function viewDetails(signatory) {
        console.log('Viewing details for:', signatory);
        showToast(`Viewing details for ${signatory}`, 'info');
    }
    
    // Export clearance function
    function exportClearance() {
        const select = document.getElementById('schoolYearTerm');
        const formId = select.value;

        if (!formId) {
            showToast('Please select a clearance period to export.', 'warning');
            return;
        }

        const exportBtn = event.target.closest('button');
        const originalHTML = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing Report...';
        exportBtn.disabled = true;

        // The API endpoint will handle the file download directly.
        // We can use a simple window.location change to trigger it.
        window.location.href = `../../api/clearance/export_report.php?form_id=${formId}`;

        // Since the download is initiated, we can revert the button state after a short delay.
        // The browser will handle the download prompt.
        setTimeout(() => {
            exportBtn.innerHTML = originalHTML;
            exportBtn.disabled = false;
            showToast('Your report is being generated for download.', 'info');
        }, 2500);
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
    
    // Load user's clearance forms for period selector
    async function loadUserPeriods() {
        try {
            const response = await fetch('../../api/clearance/user_clearance_forms.php', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success && data.forms.length > 0) {
                const select = document.getElementById('schoolYearTerm');
                select.innerHTML = '<option value="">Select a period</option>';
                
                data.forms.forEach(form => {
                    const option = document.createElement('option');
                    option.value = form.clearance_form_id;
                    option.textContent = `${form.academic_year} - ${form.semester_name} (${form.clearance_type})`;
                    if (form.form_status !== 'Completed' && form.form_status !== 'Rejected') {
                        option.textContent += ' (Active)';
                    }
                    select.appendChild(option);
                });
                
                // Auto-select the current form if available
                if (data.current_form) {
                    select.value = data.current_form.clearance_form_id;
                    loadPeriodStatusAndData();
                } else if (data.forms.length > 0) {
                    select.value = data.forms[0].clearance_form_id;
                    loadPeriodStatusAndData();
                }
            } else {
                const select = document.getElementById('schoolYearTerm');
                select.innerHTML = '<option value="">No clearance forms found</option>';
                showToast('No clearance forms found. Clearance forms are created when a clearance period starts.', 'info');
            }
        } catch (error) {
            console.error('Error loading user forms:', error);
            const select = document.getElementById('schoolYearTerm');
            select.innerHTML = '<option value="">Error loading forms</option>';
            showToast('Error loading clearance forms', 'error');
        }
    }

    // Load period status and clearance data
    async function loadPeriodStatusAndData() {
        const select = document.getElementById('schoolYearTerm');
        const selectedFormId = select.value;
        
        if (!selectedFormId) {
            return;
        }

        try {
            // The period status is now part of the user_status.php response,
            // so a separate call to period_status.php is no longer needed.
            // This simplifies the logic and ensures the status matches the selected form.
            // The updatePeriodStatusUI function will be called from within the
            // updateClearanceUI function after the main data is loaded.

            // Load clearance data for the selected form
            const clearanceResponse = await fetch(`../../api/clearance/user_status.php?form_id=${selectedFormId}`, {
                credentials: 'same-origin'
            });
            
            if (!clearanceResponse.ok) {
                throw new Error(`User status API error: ${clearanceResponse.status}`);
            }
            
            const clearanceData = await clearanceResponse.json();
            
            if (clearanceData.success) {
                updateClearanceUI(clearanceData);
            } else {
                console.warn('Failed to load clearance data:', clearanceData.message);
                // Don't show toast for every error to avoid spam
                if (!clearanceData.message.includes('not found')) {
                    showToast('Failed to load clearance data: ' + clearanceData.message, 'error');
                }
            }
        } catch (error) {
            console.error('Error loading period status and data:', error);
            // Only show toast for critical errors, not for every refresh
            if (error.message.includes('API error')) {
                showToast('Error loading clearance data', 'error');
            }
        }
    }

    // Update period status UI
    function updatePeriodStatusUI(periodData) {
        const banner = document.getElementById('period-status-banner');
        const title = document.getElementById('period-status-title');
        const message = document.getElementById('period-status-message');
        
        // The periodData object is now passed from the main user_status API response
        const status = periodData.period_status;

        if (!banner || !title || !message) return;

        // Update banner content based on period status (grace period states simplified)
        switch (periodData.period_status) {
            case 'not_started':
                title.textContent = 'Clearance Period Not Started';
                message.textContent = 'The clearance period for this term has not been started yet.';
                banner.className = 'period-status-banner not-started';
                break;
                
            case 'ongoing':
            case 'grace_period': // Treat grace period as ongoing for students
                title.textContent = 'Clearance Period Active';
                message.textContent = 'You can apply to signatories for clearance.';
                banner.className = 'period-status-banner ongoing';
                break;
                
            case 'paused':
            case 'paused_grace_period': // Treat paused grace period as paused for students
                title.textContent = 'Clearance Period Paused';
                message.textContent = 'Applications are currently disabled. Signatories can still process existing applications.';
                banner.className = 'period-status-banner paused';
                break;
                
            case 'closed':
                title.textContent = 'Clearance Period Ended';
                message.textContent = 'The clearance period has ended. Applications are no longer accepted.';
                banner.className = 'period-status-banner closed';
                break;
                
            default:
                title.textContent = 'Period Status Unknown';
                message.textContent = 'Unable to determine the current period status.';
                banner.className = 'period-status-banner unknown';
        }

        // Show banner
        banner.style.display = 'flex';
    }

    // Update clearance data based on selected period
    async function updateClearanceData() {
        const select = document.getElementById('schoolYearTerm');
        const selectedFormId = select.value;
        
        if (!selectedFormId) {
            return;
        }
        
        try {
            // Load clearance data for the selected form using new API
            const response = await fetch(`../../api/clearance/user_status.php?form_id=${selectedFormId}`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                updateClearanceUI(data);
            } else {
                showToast('Failed to load clearance data: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating clearance data:', error);
            showToast('Error loading clearance data', 'error');
        }
    }

    // Update the clearance UI based on data
    function updateClearanceUI(data) {
        // Store button states for use in getActionButton
        currentButtonStates = data.button_states || [];
        
        // Store clearance data globally for button logic
        window.currentClearanceData = data;
        currentPeriodData = data;

        // statusPanelMeta removed (previous header timestamp)
        
        // Update the period status banner using the data from this response
        updatePeriodStatusUI(data);

        // Update clearance form banner
        updateClearanceFormBanner(data);
        
        // Update overall status
        const overallStatus = document.getElementById('overallStatusText');
        if (overallStatus) {
            overallStatus.textContent = data.overall_status ? data.overall_status : 'Pending';
        }
        
        // Update signatory cards and table rows based on data.signatories
        updateSignatoryCards(data.signatories);
        updateSignatoryTable(data.signatories);
        
        // Start real-time monitoring if period is active
        startRealTimeMonitoring();
    }

    // Start real-time monitoring for period status changes
    function startRealTimeMonitoring() {
        // Clear existing interval
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        
        // Only monitor if period is active (ongoing or grace period)
        if (currentPeriodData && 
            (currentPeriodData.period_status === 'ongoing' || 
             currentPeriodData.period_status === 'grace_period' ||
             currentPeriodData.period_status === 'paused' ||
             currentPeriodData.period_status === 'paused_grace_period')) {
            
            // Refresh every 30 seconds with error handling
            refreshInterval = setInterval(async () => {
                try {
                    await loadPeriodStatusAndData();
                } catch (error) {
                    console.warn('Real-time monitoring error:', error);
                    // Don't stop monitoring for individual errors
                }
            }, 30000);
        }
    }

    // Update clearance form banner
    function updateClearanceFormBanner(data) {
        const banner = document.getElementById('clearanceFormBanner');
        const formIdElement = document.getElementById('clearanceFormId');
        const periodElement = document.getElementById('bannerPeriod');
        
        if (banner && formIdElement && periodElement) {
            if (data.clearance_form_id) {
                formIdElement.textContent = data.clearance_form_id;
                periodElement.textContent = `${data.academic_year} - ${data.semester_name}`;
                banner.style.display = 'flex';
            } else {
                banner.style.display = 'none';
            }
        }
    }

    // Update signatory cards
    function updateSignatoryCards(signatories) {
        const cardsGrid = document.getElementById('signatoryCardsGrid');
        const noSignatoriesMessage = document.getElementById('noSignatoriesMessage');
        
        if (!cardsGrid) return;
        
        if (!signatories || signatories.length === 0) {
            if (noSignatoriesMessage) {
                noSignatoriesMessage.style.display = 'block';
            }
            return;
        }
        
        if (noSignatoriesMessage) {
            noSignatoriesMessage.style.display = 'none';
        }
        cardsGrid.innerHTML = '';
        
        signatories.forEach(signatory => {
            const card = createSignatoryCard(signatory);
            cardsGrid.appendChild(card);
        });
    }

    // Create a signatory card
    function createSignatoryCard(signatory) {
        const card = document.createElement('div');
        card.className = 'signatory-card';
        card.innerHTML = `
            <div class="card-header">
                <h3>${signatory.designation_name}</h3>
                <p>${getStatusDescription(signatory.action)}</p>
            </div>
            <div class="card-content">
                <div class="status-info">
                    <span class="status-label">Status:</span>
                    <span class="status-value ${getStatusClass(signatory.action)}">${signatory.action || 'Pending'}</span>
                </div>
                <div class="date-info">
                    <span class="date-label">Date Signed:</span>
                    <span class="date-value">
                        ${(signatory.action === 'Approved' || signatory.action === 'Rejected') && signatory.date_signed ? new Date(signatory.date_signed).toLocaleDateString() : 'N/A'}
                    </span>
                </div>
                <div class="remarks-info">
                    <span class="remarks-label">Remarks:</span>
                    <span class="remarks-value">${signatory.action === 'Rejected' ? (signatory.additional_remarks || 'None') : (signatory.remarks || 'None')}</span>
                </div>
                <div class="card-actions">
                    ${getActionButton(signatory)}
                </div>
                <div class="signatory-info">
                    <span class="signatory-label">Signatory:</span>
                    <span class="signatory-value">${signatory.signatory_name || 'N/A'}</span>
                </div>
            </div>

        `;
        return card;
    }

    // Update signatory table
    function updateSignatoryTable(signatories) {
        const tableBody = document.getElementById('signatoryTableBody');
        const noSignatoriesMessage = document.getElementById('noSignatoriesTableMessage');
        
        if (!tableBody) return;
        
        if (!signatories || signatories.length === 0) {
            if (noSignatoriesMessage) {
                noSignatoriesMessage.style.display = 'block';
            }
            return;
        }
        
        if (noSignatoriesMessage) {
            noSignatoriesMessage.style.display = 'none';
        }
        tableBody.innerHTML = '';
        
        signatories.forEach(signatory => {
            const row = createSignatoryTableRow(signatory);
            tableBody.appendChild(row);
        });
    }

    // Create a signatory table row
    function createSignatoryTableRow(signatory) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${signatory.designation_name}</td>
            <td class="signatory-name">${signatory.signatory_name || 'N/A'}</td>
            <td><span class="status-badge ${getStatusClass(signatory.action)}">${signatory.action || 'Pending'}</span></td>
            <td>
                ${(signatory.action === 'Approved' || signatory.action === 'Rejected') && signatory.date_signed ? new Date(signatory.date_signed).toLocaleDateString() : 'N/A'}
            </td>
            <td>${signatory.action === 'Rejected' ? (signatory.additional_remarks || 'None') : (signatory.remarks || 'None')}</td>
            <td>
                <div class="action-buttons">
                    ${getActionButton(signatory)}
                </div>
            </td>
        `;
        return row;
    }

    // Helper functions
    function getStatusDescription(action) {
        if (!action || action === '') return 'Awaiting Application';
        if (action === 'Pending') return 'Awaiting Signatory';
        if (action === 'Approved') return 'Approved';
        if (action === 'Rejected') return 'Rejected';
        if (action === 'Unapplied') return 'Awaiting Application';
        return 'Unknown Status';
    }

    function getStatusClass(action) {
        if (!action || action === '') return 'pending';
        if (action === 'Approved') return 'approved';
        if (action === 'Rejected') return 'rejected';
        if (action === 'Pending') return 'pending';
        if (action === 'Unapplied') return 'unapplied';
        return 'pending';
    }

    function getActionButton(signatory) {
        const slug = signatory.designation_name.toLowerCase().replace(/\s+/g, '-');
        
        const clearanceData = window.currentClearanceData;
        if (!clearanceData) return '';

        const periodStatus = clearanceData.period_status;
        const canApply = periodStatus === 'ongoing' || periodStatus === 'Ongoing';

        // --- Final Status Checks (Approved/Pending) ---

        if (signatory.action === 'Approved') {
            return `<button class="btn btn-sm btn-success" disabled title="Application has been approved"><i class="fas fa-${getButtonIcon('approved')}"></i> Approved</button>`;
        } else if (signatory.action === 'Pending') {
            return `<button class="btn btn-sm btn-warning" disabled title="Application is pending approval"><i class="fas fa-${getButtonIcon('pending_approval')}"></i> Pending</button>`;
        } else if (signatory.action === 'Rejected') {
            return `<button class="btn btn-sm btn-danger" disabled title="Application was rejected. Please see remarks and contact the signatory."><i class="fas fa-${getButtonIcon('rejected')}"></i> Rejected</button>`;
        }

        // --- Required Signatory Logic ---
        const settings = clearanceData.settings || {};
        const allSignatories = clearanceData.signatories || [];

        // 1. Check for "Required First"
        if (settings.required_first_enabled && settings.required_first_designation_id != signatory.designation_id) {
            const requiredFirstSignatory = allSignatories.find(s => s.designation_id == settings.required_first_designation_id);
            if (requiredFirstSignatory && requiredFirstSignatory.action !== 'Approved') {
                return `<button class="btn btn-sm btn-secondary" disabled title="You must be approved by ${requiredFirstSignatory.designation_name} first."><i class="fas fa-${getButtonIcon('locked')}"></i> Locked</button>`;
            }
        }

        // 2. Check for "Required Last"
        if (settings.required_last_enabled && settings.required_last_designation_id == signatory.designation_id) {
            const otherSignatories = allSignatories.filter(s => s.designation_id != settings.required_last_designation_id);
            const allOthersApproved = otherSignatories.every(s => s.action === 'Approved');
            if (!allOthersApproved) {
                return `<button class="btn btn-sm btn-secondary" disabled title="All other signatories must approve before you can apply to this one."><i class="fas fa-${getButtonIcon('locked')}"></i> Locked</button>`;
            }
        }
        // --- End Required Signatory Logic ---

        // If all checks pass, show the apply button if the period is ongoing.
        if (!canApply) {
            let tooltip = 'Applications are currently disabled.';
            let reason = 'disabled'; // Default reason

            if (periodStatus === 'closed' || periodStatus === 'Closed') {
                tooltip = 'Clearance period has ended.';
                reason = 'period_closed';
            } else if (periodStatus === 'paused' || periodStatus === 'Paused') {
                tooltip = 'Clearance period is paused.';
                reason = 'period_paused';
            } else if (periodStatus === 'not_started' || periodStatus === 'Not Started') {
                tooltip = 'Clearance period has not started yet.';
                reason = 'period_not_started';
            }
            return `<button class="btn btn-sm btn-secondary" disabled title="${tooltip}"><i class="fas fa-${getButtonIcon(reason)}"></i> Apply</button>`;
        }

        // This part will now only be reached if the status is 'Unapplied'
        return `<button class="btn btn-sm btn-primary apply-btn"
                    onclick="applyToSignatory('${slug}')"
                    data-signatory-id="${signatory.signatory_id}"
                    title="Click to apply to this signatory"><i class="fas fa-${getButtonIcon('can_apply')}"></i> Apply</button>`;
    }

    // Helper function to get button icon based on reason
    function getButtonIcon(reason) {
        switch (reason) {
            case 'period_not_started': return 'ban';
            case 'grace_period': return 'clock';
            case 'period_paused': return 'pause';
            case 'period_closed': return 'lock';
            case 'pending_approval': return 'clock';
            case 'approved': return 'check';
            case 'rejected': return 'times-circle';
            case 'can_apply': return 'paper-plane';
            case 'locked': return 'lock';
            default: return 'info-circle';
        }
    }

    // Apply / Re-apply to a specific signatory (enhanced API integration)
    function applyToSignatory(signatory) {
        const applyBtn = event.target.closest('.apply-btn');
        const originalHTML = applyBtn.innerHTML;
        
        // Check if period is closed, paused, or not started
        if (window.currentClearanceData) {
            const status = window.currentClearanceData.period_status;
            if (status === 'Closed' || status === 'closed') {
                showToast('Clearance period has ended. Applications are no longer accepted.', 'warning');
                return;
            } else if (status === 'Paused' || status === 'paused') {
                showToast('Clearance period is paused. Applications are temporarily disabled.', 'warning');
                return;
            } else if (status === 'Not Started' || status === 'not_started') {
                showToast('Clearance period has not started yet.', 'warning');
                return;
            }
        }
        
        // Get the signatory ID from the button's data attribute
        const signatoryId = applyBtn.getAttribute('data-signatory-id');
        
        if (!signatoryId) {
            showToast('Signatory ID not found. Please refresh the page.', 'error');
            return;
        }

        // Optimistic UI – disable button & show spinner
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';

        // Get current form ID
        const select = document.getElementById('schoolYearTerm');
        const formId = select.value;
        
        if (!formId) {
            showToast('No clearance form selected', 'error');
            applyBtn.innerHTML = originalHTML;
            applyBtn.disabled = false;
            return;
        }

        fetch('../../api/clearance/apply_signatory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ 
                signatory_id: signatoryId,
                clearance_form_id: formId,
                // Add operation type for clarity in the backend
                operation: 'apply'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                applyBtn.innerHTML = '<i class="fas fa-check"></i> Applied';
                applyBtn.classList.remove('btn-primary', 'btn-danger');
                applyBtn.classList.add('btn-warning'); // Change to pending/warning color
                
                showToast('Application submitted successfully!', 'success');
                
                // Refresh the clearance data to get updated status
                setTimeout(() => {
                    updateClearanceData();
                }, 1000);
            } else {
                throw new Error(data.message || 'Application failed');
            }
        })
        .catch(err => {
            console.error(err);
            showToast(err.message, 'error');
            applyBtn.innerHTML = originalHTML;
            applyBtn.disabled = false;
        });
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
        
        // Load user periods for period selector
        loadUserPeriods();
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    });
    </script>
</body>
</html>
