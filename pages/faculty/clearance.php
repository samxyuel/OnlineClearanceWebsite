<?php
// Online Clearance Website - Faculty Clearance Viewing Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Clearance Status - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php
    /*
     * Demo session block disabled – real session data comes from Auth after login.
     */
    // session_start();
    // $_SESSION['user_id'] = 2;
    // $_SESSION['role_id'] = 2; // Faculty role
    // $_SESSION['first_name'] = 'Jane';
    // $_SESSION['last_name'] = 'Smith';
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
                    <h2><i class="fas fa-file-alt"></i> Faculty Clearance Status</h2>
                    <p class="page-description">Select a period to view your clearance details.</p>
                </div>

                <!-- Period Selector -->
                <div class="period-selector-section">
                    <div class="period-selector">
                        <label for="schoolYearTerm">
                            <i class="fas fa-calendar-alt"></i> School Year & Term
                        </label>
                        <select id="schoolYearTerm" onchange="updateClearanceData()">
                            <option value="">Select a period</option>
                        </select>
                    </div>
                </div>

                <!-- Overall Clearance Progress Status Badge -->
                <div class="overall-status-section">
                    <div class="overall-status-badge">
                        <i class="fas fa-info-circle"></i>
                        <!-- Overall Clearance Status: Pending -->
                         Clearance Progress Status: Pending
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
                        <div class="signatory-cards-grid">
                            <!-- Dynamic signatory cards will be generated here -->
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading signatories...</p>
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
                                <tbody>
                                    <!-- Dynamic table rows will be generated here -->
                                    <tr class="loading-placeholder">
                                        <td colspan="6" style="text-align: center; padding: 2rem;">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            <p>Loading signatories...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Timeline View Tab -->
                    <div id="timeline-tab" class="tab-pane">
                        <div class="timeline-container">
                            <div class="timeline-item pending">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Faculty Clearance Application Submitted</h4>
                                    <p>Application submitted for 2027-2028 1st Semester</p>
                                    <span class="timeline-date">Dec 15, 2024</span>
                                </div>
                            </div>
                            
                            <div class="timeline-item pending">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Awaiting Signatory Approvals</h4>
                                    <p>All signatories pending: Program Head, Librarian, Cashier, Registrar</p>
                                    <span class="timeline-date">In Progress</span>
                                </div>
                            </div>
                            
                            <div class="timeline-item future">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Faculty Clearance Completion</h4>
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
                        <i class="fas fa-download"></i> Export Faculty Clearance Report
                    </button>
                    
                    <!-- Debug Section -->
                    <div class="debug-section" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <h4>Debug Information</h4>
                        <button class="btn btn-sm btn-outline" onclick="debugClearancePage()">Debug Clearance Page</button>
                        <button class="btn btn-sm btn-outline" onclick="testUserPeriods()">Test User Periods</button>
                        <div id="debugOutput" style="margin-top: 1rem; font-family: monospace; font-size: 12px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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
    
    // Update clearance data based on period selection
    function updateClearanceData() {
        const selectedPeriod = document.getElementById('schoolYearTerm').value;
        // Here you would typically fetch data for the selected period
        console.log('Updating faculty clearance data for period:', selectedPeriod);
        
        // Show loading state
        showToast('Loading faculty clearance data...', 'info');
        
        // Simulate data loading
        setTimeout(() => {
            showToast('Faculty clearance data updated', 'success');
        }, 1000);
    }
    
    // View details function
    function viewDetails(signatory) {
        console.log('Viewing details for:', signatory);
        showToast(`Viewing details for ${signatory}`, 'info');
    }

    // Apply / Re-apply to a specific signatory (real API integration)
    function applyToSignatory(signatory) {
        const designationCodeMap = {
            'program-head': 'PROGRAM_HEAD',
            'librarian': 'LIBRARIAN',
            'registrar': 'REGISTRAR',
            'cashier': 'CASHIER'
        };

        const designationCode = designationCodeMap[signatory];
        if (!designationCode) { showToast('Unknown signatory: '+signatory, 'error'); return; }

        const applyBtn = event.target.closest('.apply-btn');
        const originalHTML = applyBtn.innerHTML;
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';

        fetch('../../api/clearance/apply.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ designation_code: designationCode })
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                applyBtn.innerHTML = '<i class="fas fa-check"></i> Applied';
                applyBtn.classList.remove('btn-primary');
                applyBtn.classList.add('btn-success');
                updateSignatoryStatus(signatory,'pending');
                showToast('Application submitted!', 'success');
                checkAndUpdateDashboardButton(); // Call the new function
            }else{ throw new Error(data.message||'Failed'); }
        })
        .catch(err=>{ console.error(err); showToast(err.message,'error'); applyBtn.innerHTML = originalHTML; })
        .finally(()=>{ applyBtn.disabled = false; });
    }

    // Update signatory status across all views
    function updateSignatoryStatus(signatory, status, latestSigName='') {
        // Update card view
        const cardStatus = document.querySelector(`.signatory-card .card-actions .apply-btn[data-signatory="${signatory}"]`)?.closest('.signatory-card')?.querySelector('.status-value');
        if (cardStatus) {
            cardStatus.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            cardStatus.className = `status-value ${status}`;
        }
        
        // Update table view
        const tableStatus = document.querySelector(`.clearance-table .status-badge[data-signatory="${signatory}"]`);
        if (tableStatus) {
            tableStatus.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            tableStatus.className = `status-badge ${status}`;
        }
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
            showToast('Faculty clearance report exported successfully!', 'success');
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
    });

    // ---------------------- DYNAMIC CLEARANCE FUNCTIONALITY --------------------------
    document.addEventListener('DOMContentLoaded', function() {
        loadUserPeriods();
    });

    // Load user's clearance periods for period selector
    async function loadUserPeriods() {
        try {
            const response = await fetch('../../api/clearance/user_periods.php', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success && data.periods.length > 0) {
                const select = document.getElementById('schoolYearTerm');
                select.innerHTML = '<option value="">Select a period</option>';
                
                data.periods.forEach(period => {
                    const option = document.createElement('option');
                    option.value = period.clearance_form_id;
                    option.textContent = period.period_text;
                    if (period.is_active) {
                        option.textContent += ' (Active)';
                    }
                    select.appendChild(option);
                });
                
                // Auto-select the first period and load its data
                if (data.periods.length > 0) {
                    select.value = data.periods[0].clearance_form_id;
                    updateClearanceData();
                }
            } else {
                const select = document.getElementById('schoolYearTerm');
                select.innerHTML = '<option value="">No clearance periods found</option>';
            }
        } catch (error) {
            console.error('Error loading user periods:', error);
            const select = document.getElementById('schoolYearTerm');
            select.innerHTML = '<option value="">Error loading periods</option>';
        }
    }

    // Update clearance data based on selected period
    async function updateClearanceData() {
        const select = document.getElementById('schoolYearTerm');
        const selectedFormId = select.value;
        
        if (!selectedFormId) {
            return;
        }
        
        try {
            // Load clearance data for the selected form
            const response = await fetch(`../../api/clearance/status.php?form_id=${selectedFormId}`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                updateClearanceUI(data);
            } else {
                showToast('Failed to load clearance data', 'error');
            }
        } catch (error) {
            console.error('Error updating clearance data:', error);
            showToast('Error loading clearance data', 'error');
        }
    }

    // Update the clearance UI based on data
    function updateClearanceUI(data) {
        // Update overall status
        const overallStatus = document.querySelector('.overall-status-badge');
        if (overallStatus) {
            overallStatus.innerHTML = `<i class="fas fa-info-circle"></i> Clearance Progress Status: ${data.overall_status}`;
        }
        
        // Update signatory cards and table rows based on data.signatories
        updateSignatoryCards(data.signatories);
        updateSignatoryTable(data.signatories);
    }

    // Update signatory cards
    function updateSignatoryCards(signatories) {
        const cardsGrid = document.querySelector('.signatory-cards-grid');
        if (!cardsGrid || !signatories) return;
        
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
        const tableBody = document.querySelector('.clearance-table tbody');
        if (!tableBody || !signatories) return;
        
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
        return 'Unknown Status';
    }

    function getStatusClass(action) {
        if (!action || action === '') return 'pending';
        if (action === 'Approved') return 'approved';
        if (action === 'Rejected') return 'rejected';
        return 'pending';
    }

    function getActionButton(signatory) {
        if (signatory.action === 'Approved') {
            return '<button class="btn btn-sm btn-success" disabled><i class="fas fa-check"></i> Approved</button>';
        } else if (signatory.action === 'Rejected') {
            return `<button class="btn btn-sm btn-primary apply-btn" onclick="applyToSignatory('${signatory.designation_name.toLowerCase().replace(/\s+/g, '-')}')" data-signatory="${signatory.designation_name.toLowerCase().replace(/\s+/g, '-')}">
                        <i class="fas fa-paper-plane"></i> Re-apply
                    </button>`;
        } else {
            return `<button class="btn btn-sm btn-primary apply-btn" onclick="applyToSignatory('${signatory.designation_name.toLowerCase().replace(/\s+/g, '-')}')" data-signatory="${signatory.designation_name.toLowerCase().replace(/\s+/g, '-')}">
                        <i class="fas fa-paper-plane"></i> Apply
                    </button>`;
        }
    }

    // Update signatory status across all views
    function updateSignatoryStatus(signatory, status, latestSigName='') {
        // Update card view
        const cardStatus = document.querySelector(`.signatory-card .card-actions .apply-btn[data-signatory="${signatory}"]`)?.closest('.signatory-card')?.querySelector('.status-value');
        if (cardStatus) {
            cardStatus.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            cardStatus.className = `status-value ${status}`;
        }
        
        // Update table view
        const tableStatus = document.querySelector(`.clearance-table .status-badge[data-signatory="${signatory}"]`);
        if (tableStatus) {
            tableStatus.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            tableStatus.className = `status-badge ${status}`;
        }
    }

    // Check and update dashboard button if needed
    function checkAndUpdateDashboardButton() {
        // This function can be called after applying to update the dashboard button state
        // For now, we'll just show a success message
        showToast('Application submitted! Check your dashboard for updates.', 'success');
    }

    // Load user's clearance periods for period selector
    async function loadUserPeriods() {
        try {
            const response = await fetch('../../api/clearance/user_periods.php', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success && data.periods.length > 0) {
                const select = document.getElementById('schoolYearTerm');
                select.innerHTML = '<option value="">Select a period</option>';
                
                data.periods.forEach(period => {
                    const option = document.createElement('option');
                    option.value = period.clearance_form_id;
                    option.textContent = period.period_text;
                    if (period.is_active) {
                        option.textContent += ' (Active)';
                    }
                    select.appendChild(option);
                });
                
                // Auto-select the first period and load its data
                if (data.periods.length > 0) {
                    select.value = data.periods[0].clearance_form_id;
                    updateClearanceData();
                }
            } else {
                const select = document.getElementById('schoolYearTerm');
                select.innerHTML = '<option value="">No clearance periods found</option>';
            }
        } catch (error) {
            console.error('Error loading user periods:', error);
            const select = document.getElementById('schoolYearTerm');
            select.innerHTML = '<option value="">Error loading periods</option>';
        }
    }

    // Update clearance data based on selected period
    async function updateClearanceData() {
        const select = document.getElementById('schoolYearTerm');
        const selectedFormId = select.value;
        
        if (!selectedFormId) {
            return;
        }
        
        try {
            // Load clearance data for the selected form
            const response = await fetch(`../../api/clearance/status.php?form_id=${selectedFormId}`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                updateClearanceUI(data);
            } else {
                showToast('Failed to load clearance data', 'error');
            }
        } catch (error) {
            console.error('Error updating clearance data:', error);
            showToast('Error loading clearance data', 'error');
        }
    }

    // Update the clearance UI based on data
    function updateClearanceUI(data) {
        // Update overall status
        const overallStatus = document.querySelector('.overall-status-badge');
        if (overallStatus) {
            overallStatus.innerHTML = `<i class="fas fa-info-circle"></i> Clearance Progress Status: ${data.overall_status}`;
        }
        
        // Update signatory cards and table rows based on data.signatories
        updateSignatoryCards(data.signatories);
        updateSignatoryTable(data.signatories);
    }

    // Update signatory cards
    function updateSignatoryCards(signatories) {
        const cardsGrid = document.querySelector('.signatory-cards-grid');
        if (!cardsGrid || !signatories) return;
        
        cardsGrid.innerHTML = '';
        
        signatories.forEach(signatory => {
            const card = createSignatoryCard(signatory);
            cardsGrid.appendChild(card);
        });
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        loadUserPeriods();
    });

    // Debug functions
async function debugClearancePage() {
    const debugOutput = document.getElementById('debugOutput');
    debugOutput.innerHTML = '<p>Debugging clearance page...</p>';
    
    // Check if elements exist
    const cardsGrid = document.querySelector('.signatory-cards-grid');
    const tableBody = document.querySelector('.clearance-table tbody');
    const periodSelect = document.getElementById('schoolYearTerm');
    
    debugOutput.innerHTML += `
        <p><strong>Cards Grid:</strong> ${cardsGrid ? 'Found' : 'Not found'}</p>
        <p><strong>Table Body:</strong> ${tableBody ? 'Found' : 'Not found'}</p>
        <p><strong>Period Select:</strong> ${periodSelect ? 'Found' : 'Not found'}</p>
        <p><strong>Period Select Value:</strong> ${periodSelect ? periodSelect.value : 'N/A'}</p>
    `;
}

async function testUserPeriods() {
    const debugOutput = document.getElementById('debugOutput');
    debugOutput.innerHTML = '<p>Testing user periods API...</p>';
    
    try {
        const response = await fetch('../../api/clearance/user_periods.php', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        debugOutput.innerHTML += `<p><strong>User Periods Response:</strong> ${JSON.stringify(data, null, 2)}</p>`;
    } catch (error) {
        debugOutput.innerHTML += `<p><strong>Error:</strong> ${error.message}</p>`;
    }
}

    </script>
</body>
</html> 