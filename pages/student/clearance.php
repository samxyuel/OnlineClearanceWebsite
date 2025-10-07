<?php
// Online Clearance Website - Student Clearance Viewing Page
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
    /* Demo session for testing - using SHS student Alex Garcia */
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = 118; // Alex Garcia (SHS student)
    $_SESSION['user_type'] = 'student';
    $_SESSION['first_name'] = 'Alex';
    $_SESSION['last_name'] = 'Garcia';
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
                    <p class="page-description">View and manage your clearance applications.</p>
                </div>



                <!-- Period Status Banner -->
                <div id="period-status-banner" class="period-status-banner" style="display: none;">
                    <div class="banner-content">
                        <div class="banner-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="banner-text">
                            <h3 id="period-status-title">Period Status</h3>
                            <p id="period-status-message">Loading...</p>
                        </div>
                    </div>
                </div>

                <!-- Period Selector -->
                <div class="period-selector-section">
                    <div class="period-selector">
                        <label for="schoolYearTerm">
                            <i class="fas fa-calendar-alt"></i> School Year & Term
                        </label>
                        <select id="schoolYearTerm" onchange="loadPeriodStatusAndData()">
                            <option value="">Loading periods...</option>
                        </select>
                    </div>
                </div>

                <!-- Clearance Form ID Banner -->
                <div class="clearance-form-banner" id="clearanceFormBanner" style="display: none;">
                    <div class="banner-content">
                        <div class="banner-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="banner-text">
                            <h3>Clearance Form ID</h3>
                            <p id="clearanceFormId">Loading...</p>
                        </div>
                        <div class="banner-period">
                            <span id="bannerPeriod">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Overall Clearance Form Progress Status Badge -->
                <div class="overall-status-section">
                    <div class="overall-status-badge">
                        <i class="fas fa-info-circle"></i>
                        Clearance Form Progress: 
                        <span class="status-badge clearance-unapplied" id="clearanceFormProgressBadge">Unapplied</span>
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
                                    <h4>Clearance Application Submitted</h4>
                                    <p>Application submitted for 2027-2028 1st Semester</p>
                                    <span class="timeline-date">Dec 15, 2024</span>
                                </div>
                            </div>
                            
                            <div class="timeline-item pending">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Awaiting Signatory Approvals</h4>
                                    <p>All signatories pending: Cashier, Librarian, Program Head, Registrar</p>
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

                <!-- Export Section -->
                <div class="export-section">
                    <button class="btn btn-primary" onclick="exportClearance()">
                        <i class="fas fa-download"></i> Export Clearance Report
                    </button>
                </div>
            </div>
        </div>
    </main>

    
    <script>
    // Global variables
    let currentPeriodData = null;
    let currentButtonStates = [];
    let refreshInterval = null;

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
        const exportBtn = event.target.closest('button');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        exportBtn.disabled = true;
        
        // Simulate export process
        setTimeout(() => {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
            showToast('Clearance report exported successfully!', 'success');
        }, 2000);
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
            // Load period status first
            const periodResponse = await fetch('../../api/clearance/period_status.php', {
                credentials: 'same-origin'
            });
            
            if (!periodResponse.ok) {
                throw new Error(`Period status API error: ${periodResponse.status}`);
            }
            
            const periodData = await periodResponse.json();
            
            if (periodData.success) {
                currentPeriodData = periodData;
                updatePeriodStatusUI(periodData);
                
                // Grace period handling removed - no longer shown to students
            }

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
        banner.style.display = 'block';
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
        
        // Update clearance form banner
        updateClearanceFormBanner(data);
        
        // Update clearance form progress status badge
        const clearanceFormProgressBadge = document.getElementById('clearanceFormProgressBadge');
        if (clearanceFormProgressBadge && data.clearance_form_progress) {
            // Remove existing status classes
            clearanceFormProgressBadge.classList.remove('clearance-unapplied', 'clearance-in-progress', 'clearance-complete');
            
            // Add new status class and update text
            const statusClass = `clearance-${data.clearance_form_progress}`;
            const statusText = data.clearance_form_progress.charAt(0).toUpperCase() + data.clearance_form_progress.slice(1).replace('-', ' ');
            clearanceFormProgressBadge.classList.add(statusClass);
            clearanceFormProgressBadge.textContent = statusText;
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
            
            // Show real-time indicator
            showRealTimeIndicator();
            
            // Refresh every 30 seconds with error handling
            refreshInterval = setInterval(async () => {
                try {
                    showRealTimeIndicator(true); // Show updating state
                    await loadPeriodStatusAndData();
                } catch (error) {
                    console.warn('Real-time monitoring error:', error);
                    // Don't stop monitoring for individual errors
                }
            }, 30000);
        } else {
            // Hide real-time indicator if not monitoring
            hideRealTimeIndicator();
        }
    }

    // Show real-time update indicator
    function showRealTimeIndicator(isUpdating = false) {
        const indicator = document.getElementById('real-time-indicator');
        if (indicator) {
            if (isUpdating) {
                indicator.classList.add('updating');
                indicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Updating...';
            } else {
                indicator.classList.remove('updating');
                indicator.innerHTML = '<i class="fas fa-sync-alt"></i> Live Updates Active';
            }
            indicator.classList.add('show');
        }
    }

    // Hide real-time update indicator
    function hideRealTimeIndicator() {
        const indicator = document.getElementById('real-time-indicator');
        if (indicator) {
            indicator.classList.remove('show', 'updating');
        }
    }

    // Stop real-time monitoring
    function stopRealTimeMonitoring() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
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
                banner.style.display = 'block';
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
                    <span class="date-value">${signatory.updated_at ? new Date(signatory.updated_at).toLocaleDateString() : 'N/A'}</span>
                </div>
                <div class="remarks-info">
                    <span class="remarks-label">Remarks:</span>
                    <span class="remarks-value">${signatory.remarks || 'None'}</span>
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
            noSignatoriesMessage.style.display = 'block';
            return;
        }
        
        noSignatoriesMessage.style.display = 'none';
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
            <td>${signatory.updated_at ? new Date(signatory.updated_at).toLocaleDateString() : 'N/A'}</td>
            <td>${signatory.remarks || 'None'}</td>
            <td>
                <div class="action-buttons">
                    ${getActionButton(signatory)}
                    <button class="btn btn-sm btn-outline" onclick="viewDetails('${signatory.designation_name.toLowerCase().replace(/\s+/g, '-')}')">
                        <i class="fas fa-eye"></i>
                    </button>
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
        
        // Check if the period is closed (from the data returned by user_status.php)
        const isPeriodClosed = window.currentClearanceData && window.currentClearanceData.period_status === 'Closed';
        
        // Find button state for this signatory
        const buttonState = currentButtonStates.find(state => 
            state.signatory_id === signatory.signatory_id || 
            state.designation_id === signatory.designation_id
        );
        
        if (buttonState) {
            // Use the button state from the API
            const buttonClass = buttonState.button_state.class;
            const buttonText = buttonState.button_state.text;
            const buttonTooltip = buttonState.button_state.tooltip;
            const isEnabled = buttonState.button_state.enabled && !isPeriodClosed;
            
            if (isEnabled) {
                return `<button class="btn btn-sm ${buttonClass} apply-btn" 
                            onclick="applyToSignatory('${slug}')" 
                            data-signatory="${slug}" 
                            data-signatory-id="${signatory.signatory_id}"
                            title="${buttonTooltip}">
                            <i class="fas fa-paper-plane"></i> ${buttonText}
                        </button>`;
            } else {
                const tooltip = isPeriodClosed ? 'Clearance period has ended. Applications are no longer accepted.' : buttonTooltip;
                return `<button class="btn btn-sm ${buttonClass}" 
                            disabled 
                            title="${tooltip}">
                            <i class="fas fa-${getButtonIcon(buttonState.button_state.reason)}"></i> ${buttonText}
                        </button>`;
            }
        }
        
        // Fallback to old logic if no button state found
        if (signatory.action === 'Approved') {
            return '<button class="btn btn-sm btn-success" disabled title="Application has been approved"><i class="fas fa-check"></i> Approved</button>';
        } else if (signatory.action === 'Rejected') {
            if (isPeriodClosed) {
                return '<button class="btn btn-sm btn-danger" disabled title="Clearance period has ended. Applications are no longer accepted."><i class="fas fa-ban"></i> Reapply</button>';
            }
            return `<button class="btn btn-sm btn-danger apply-btn" onclick="applyToSignatory('${slug}')" data-signatory="${slug}" data-signatory-id="${signatory.signatory_id}" title="Click to reapply after rejection">
                        <i class="fas fa-paper-plane"></i> Reapply
                    </button>`;
        } else if (signatory.action === 'Pending') {
            return '<button class="btn btn-sm btn-warning" disabled title="Application is pending approval"><i class="fas fa-clock"></i> Pending</button>';
        } else {
            if (isPeriodClosed) {
                return '<button class="btn btn-sm btn-secondary" disabled title="Clearance period has ended. Applications are no longer accepted."><i class="fas fa-ban"></i> Apply</button>';
            }
            return `<button class="btn btn-sm btn-primary apply-btn" onclick="applyToSignatory('${slug}')" data-signatory="${slug}" data-signatory-id="${signatory.signatory_id}" title="Click to apply to this signatory">
                        <i class="fas fa-paper-plane"></i> Apply
                    </button>`;
        }
    }

    // Helper function to get button icon based on reason
    function getButtonIcon(reason) {
        switch (reason) {
            case 'period_not_started':
                return 'ban';
            case 'grace_period':
                return 'clock';
            case 'period_paused':
                return 'pause';
            case 'period_closed':
                return 'lock';
            case 'pending_approval':
                return 'clock';
            case 'approved':
                return 'check';
            case 'can_apply':
            case 'can_reapply':
                return 'paper-plane';
            default:
                return 'info-circle';
        }
    }

    // Apply / Re-apply to a specific signatory (enhanced API integration)
    function applyToSignatory(signatory) {
        const applyBtn = event.target.closest('.apply-btn');
        const originalHTML = applyBtn.innerHTML;
        
        // Check if period is closed
        if (window.currentClearanceData && window.currentClearanceData.period_status === 'Closed') {
            showToast('Clearance period has ended. Applications are no longer accepted.', 'warning');
            return;
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
                clearance_form_id: formId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                applyBtn.innerHTML = '<i class="fas fa-check"></i> Applied';
                applyBtn.classList.remove('btn-primary');
                applyBtn.classList.add('btn-success');
                
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
            stopRealTimeMonitoring();
            // Only call cleanup if the grace period manager exists
            if (window.gracePeriodUIManager && typeof window.gracePeriodUIManager.cleanup === 'function') {
                window.gracePeriodUIManager.cleanup();
            }
        });
    });
    </script>
</body>
</html>
