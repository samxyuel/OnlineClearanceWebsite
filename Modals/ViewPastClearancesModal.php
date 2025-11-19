<?php
/**
 * View Past Clearances Modal
 * Displays historical clearance data organized by sector
 */
?>

<!-- View Past Clearances Modal -->
<div class="modal-overlay" id="viewPastClearancesModal" style="display: none;">
    <div class="modal-content large-modal" style="background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);">
        <div class="modal-header" style="background: white; border-bottom: 1px solid #e9ecef; border-radius: 12px 12px 0 0;">
            <h3><i class="fas fa-history"></i> View Past Clearances</h3>
            <button class="modal-close" onclick="closeViewPastClearancesModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body" style="background: white;">
            <!-- Sector Tabs -->
            <div class="sector-tabs past-clearances-tabs">
                <button class="past-clearances-tab-button active" onclick="switchPastClearancesTab('college')">
                    <i class="fas fa-university"></i> College
                </button>
                <button class="past-clearances-tab-button" onclick="switchPastClearancesTab('shs')">
                    <i class="fas fa-graduation-cap"></i> Senior High School
                </button>
                <button class="past-clearances-tab-button" onclick="switchPastClearancesTab('faculty')">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content past-clearances-content">
                <!-- College Tab -->
                <div class="past-clearances-panel active" id="past-clearances-college">
                    <div class="past-clearances-header">
                        <h4><i class="fas fa-university"></i> College Clearance History</h4>
                        <!-- <div class="export-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportPastClearances('College')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </button>
                        </div> -->
                    </div>
                    <div class="past-clearances-list" id="college-past-clearances">
                        <div class="loading-text">Loading College clearance history...</div>
                    </div>
                </div>
                
                <!-- Senior High School Tab -->
                <div class="past-clearances-panel" id="past-clearances-shs">
                    <div class="past-clearances-header">
                        <h4><i class="fas fa-graduation-cap"></i> Senior High School Clearance History</h4>
                        <!-- <div class="export-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportPastClearances('Senior High School')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </button>
                        </div> -->
                    </div>
                    <div class="past-clearances-list" id="shs-past-clearances">
                        <div class="loading-text">Loading SHS clearance history...</div>
                    </div>
                </div>
                
                <!-- Faculty Tab -->
                <div class="past-clearances-panel" id="past-clearances-faculty">
                    <div class="past-clearances-header">
                        <h4><i class="fas fa-chalkboard-teacher"></i> Faculty Clearance History</h4>
                        <!-- <div class="export-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportPastClearances('Faculty')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </button>
                        </div> -->
                    </div>
                    <div class="past-clearances-list" id="faculty-past-clearances">
                        <div class="loading-text">Loading Faculty clearance history...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer" style="background: white; border-top: 1px solid #e9ecef; border-radius: 0 0 12px 12px;">
            <button class="btn btn-secondary" onclick="closeViewPastClearancesModal()">Close</button>
        </div>
    </div>
</div>

<style>
/* Past Clearances Modal - Specific Tab Styles to Avoid Conflicts */
#viewPastClearancesModal .past-clearances-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

#viewPastClearancesModal .past-clearances-tab-button {
    padding: 0.75rem 1.5rem;
    border: none;
    background: transparent;
    color: var(--medium-muted-blue, #6b7280);
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#viewPastClearancesModal .past-clearances-tab-button:hover {
    background: #f8f9fa;
    color: var(--deep-navy-blue, #1e3a5f);
}

#viewPastClearancesModal .past-clearances-tab-button.active {
    background: var(--darker-saturated-blue, #0c5591);
    color: white;
    border-bottom: 3px solid var(--bright-golden-yellow, #fbbf24);
}

#viewPastClearancesModal .past-clearances-tab-button.active i {
    color: white;
}

#viewPastClearancesModal .past-clearances-content {
    position: relative;
}

#viewPastClearancesModal .past-clearances-panel {
    display: none;
    animation: fadeInTab 0.35s ease;
}

#viewPastClearancesModal .past-clearances-panel.active {
    display: block;
}

@keyframes fadeInTab {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
/**
 * View Past Clearances Modal JavaScript Functions
 */

/**
 * Show View Past Clearances Modal - Make globally available
 */
window.showViewPastClearancesModal = function() {
    try {
        const modal = document.getElementById('viewPastClearancesModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('View past clearances modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('viewPastClearancesModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }
        
        // Load default tab (College)
        if (typeof loadPastClearances === 'function') {
            loadPastClearances('college');
        }
    } catch (error) {
        if (typeof showToastNotification === 'function') {
            showToastNotification('Unable to open view past clearances modal. Please try again.', 'error');
        }
    }
};

/**
 * Close View Past Clearances Modal - Make globally available
 */
window.closeViewPastClearancesModal = function() {
    try {
        const modal = document.getElementById('viewPastClearancesModal');
        if (!modal) return;

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('viewPastClearancesModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            modal.classList.remove('active');
        }
    } catch (error) {
        // Silent error handling
    }
};

/**
 * Switch past clearances tab
 */
function switchPastClearancesTab(tab) {
    // Get the modal container to scope selectors
    const modal = document.getElementById('viewPastClearancesModal');
    if (!modal) return;
    
    // Update tab buttons - use specific class to avoid conflicts
    modal.querySelectorAll('.past-clearances-tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const activeButton = modal.querySelector(`.past-clearances-tab-button[onclick="switchPastClearancesTab('${tab}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Update tab panels - use specific class to avoid conflicts
    modal.querySelectorAll('.past-clearances-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    const activePanel = document.getElementById(`past-clearances-${tab}`);
    if (activePanel) {
        activePanel.classList.add('active');
    }
    
    // Load data for the selected tab
    loadPastClearances(tab);
}

/**
 * Load past clearances for a sector
 */
async function loadPastClearances(tab) {
    const sectorMap = {
        'college': 'College',
        'shs': 'Senior High School',
        'faculty': 'Faculty'
    };
    
    const sector = sectorMap[tab];
    const container = document.getElementById(`${tab}-past-clearances`);
    
    if (!container) return;
    
    container.innerHTML = '<div class="loading-text">Loading clearance history...</div>';
    
    try {
        // Use the new past clearances API to get historical data with statistics
        const response = await fetch(`../../api/clearance/past_clearances.php?sector=${encodeURIComponent(sector)}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.periods) {
            renderPastClearances(container, data.periods);
        } else {
            container.innerHTML = '<div class="loading-text">No clearance history found</div>';
        }
    } catch (error) {
        console.error('Error loading past clearances:', error);
        container.innerHTML = '<div class="loading-text">Error loading clearance history</div>';
    }
}

/**
 * Render past clearances
 */
function renderPastClearances(container, periods) {
    if (!periods || periods.length === 0) {
        container.innerHTML = '<div class="loading-text">No clearance history found</div>';
        return;
    }
    
    // Filter to show only closed/completed periods
    const pastPeriods = periods.filter(period => 
        period.status === 'Closed' || period.status === 'Completed' || period.ended_at
    );
    
    if (pastPeriods.length === 0) {
        container.innerHTML = '<div class="loading-text">No completed clearance periods found</div>';
        return;
    }
    
    const html = pastPeriods.map(period => `
        <div class="clearance-period-item">
            <div class="period-info">
                <div class="period-title">${period.academic_year || 'N/A'} - ${period.semester_name || 'N/A'}</div>
                <div class="period-dates">
                    ${formatDate(period.start_date)} - ${formatDate(period.end_date || period.ended_at)}
                </div>
                <div class="period-stats">
                    <span>Status: <strong>${period.status || 'N/A'}</strong></span>
                    <span>Applications: <strong>${period.total_applications || period.total_forms || 0}</strong></span>
                    <span>Completed: <strong>${period.completed_applications || period.completed_forms || 0}</strong></span>
                </div>
            </div>
            <!-- <div class="period-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="exportPeriodReport('${period.period_id}')">
                    <i class="fas fa-file-pdf"></i> Export Report
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="viewPeriodDetails('${period.period_id}')">
                    <i class="fas fa-eye"></i> View Details
                </button>
            </div> -->
        </div>
    `).join('');
    
    container.innerHTML = html;
}

/**
 * Export past clearances for a sector
 */
async function exportPastClearances(sector) {
    try {
        showToast(`Exporting ${sector} clearance data...`, 'info');
        
        // This would typically call an export API
        // For now, simulate the export process
        setTimeout(() => {
            showToast(`${sector} clearance data exported successfully`, 'success');
        }, 2000);
    } catch (error) {
        console.error('Error exporting clearances:', error);
        showToast('Failed to export clearance data', 'error');
    }
}

/**
 * Export period report
 */
async function exportPeriodReport(periodId) {
    try {
        showToast('Exporting period report...', 'info');
        
        // This would typically call an export API
        setTimeout(() => {
            showToast('Period report exported successfully', 'success');
        }, 2000);
    } catch (error) {
        console.error('Error exporting period report:', error);
        showToast('Failed to export period report', 'error');
    }
}

/**
 * View period details
 */
function viewPeriodDetails(periodId) {
    showToast(`Viewing details for period ${periodId}...`, 'info');
    // This would typically open a detailed view modal or navigate to a details page
}

/**
 * Utility function to format dates
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Export functions for global access
window.showViewPastClearancesModal = showViewPastClearancesModal;
window.closeViewPastClearancesModal = closeViewPastClearancesModal;
window.switchPastClearancesTab = switchPastClearancesTab;
window.exportPastClearances = exportPastClearances;
window.exportPeriodReport = exportPeriodReport;
window.viewPeriodDetails = viewPeriodDetails;
</script>
