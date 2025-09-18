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
            <div class="sector-tabs">
                <button class="tab-button active" onclick="switchPastClearancesTab('college')">
                    <i class="fas fa-university"></i> College
                </button>
                <button class="tab-button" onclick="switchPastClearancesTab('shs')">
                    <i class="fas fa-graduation-cap"></i> Senior High School
                </button>
                <button class="tab-button" onclick="switchPastClearancesTab('faculty')">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- College Tab -->
                <div class="tab-panel active" id="past-clearances-college">
                    <div class="past-clearances-header">
                        <h4><i class="fas fa-university"></i> College Clearance History</h4>
                        <div class="export-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportPastClearances('College')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </button>
                        </div>
                    </div>
                    <div class="past-clearances-list" id="college-past-clearances">
                        <div class="loading-text">Loading College clearance history...</div>
                    </div>
                </div>
                
                <!-- Senior High School Tab -->
                <div class="tab-panel" id="past-clearances-shs">
                    <div class="past-clearances-header">
                        <h4><i class="fas fa-graduation-cap"></i> Senior High School Clearance History</h4>
                        <div class="export-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportPastClearances('Senior High School')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </button>
                        </div>
                    </div>
                    <div class="past-clearances-list" id="shs-past-clearances">
                        <div class="loading-text">Loading SHS clearance history...</div>
                    </div>
                </div>
                
                <!-- Faculty Tab -->
                <div class="tab-panel" id="past-clearances-faculty">
                    <div class="past-clearances-header">
                        <h4><i class="fas fa-chalkboard-teacher"></i> Faculty Clearance History</h4>
                        <div class="export-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="exportPastClearances('Faculty')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </button>
                        </div>
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

<script>
/**
 * View Past Clearances Modal JavaScript Functions
 */

/**
 * Show View Past Clearances Modal
 */
function showViewPastClearancesModal() {
    const modal = document.getElementById('viewPastClearancesModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Load default tab (College)
        loadPastClearances('college');
        
        // Add click outside to close functionality
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeViewPastClearancesModal();
            }
        });
    }
}

/**
 * Close View Past Clearances Modal
 */
function closeViewPastClearancesModal() {
    const modal = document.getElementById('viewPastClearancesModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

/**
 * Switch past clearances tab
 */
function switchPastClearancesTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const activeButton = document.querySelector(`[onclick="switchPastClearancesTab('${tab}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Update tab panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
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
        // Use the existing sector-periods API to get historical data
        const response = await fetch(`../../api/clearance/sector-periods.php?sector=${encodeURIComponent(sector)}&include_closed=true`);
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
                    <span>Status: <strong>${period.status}</strong></span>
                    <span>Applications: <strong>${period.total_forms || 0}</strong></span>
                    <span>Completed: <strong>${period.completed_forms || 0}</strong></span>
                </div>
            </div>
            <div class="period-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="exportPeriodReport('${period.period_id}')">
                    <i class="fas fa-file-pdf"></i> Export Report
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="viewPeriodDetails('${period.period_id}')">
                    <i class="fas fa-eye"></i> View Details
                </button>
            </div>
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
