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
            <!-- Past Clearances Content -->
            <div class="past-clearances-content">
                <div class="past-clearances-list" id="all-past-clearances">
                    <div class="loading-text">Loading clearance history...</div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer" style="background: white; border-top: 1px solid #e9ecef; border-radius: 0 0 12px 12px;">
            <button class="btn btn-secondary" onclick="closeViewPastClearancesModal()">Close</button>
        </div>
    </div>
</div>

<style>
/* Past Clearances Modal - Content Styles */
#viewPastClearancesModal .past-clearances-content {
    position: relative;
}

#viewPastClearancesModal .past-clearances-list {
    max-height: 70vh;
    overflow-y: auto;
    padding: 0.5rem;
}

/* Academic Year Card Styles */
#viewPastClearancesModal .academic-year-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

#viewPastClearancesModal .academic-year-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#viewPastClearancesModal .academic-year-header {
    background: linear-gradient(135deg, var(--darker-saturated-blue, #0c5591) 0%, var(--deep-navy-blue, #1e3a5f) 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--bright-golden-yellow, #fbbf24);
}

#viewPastClearancesModal .academic-year-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#viewPastClearancesModal .academic-year-title i {
    color: var(--bright-golden-yellow, #fbbf24);
}

#viewPastClearancesModal .delete-year-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#viewPastClearancesModal .delete-year-btn:hover {
    background: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

#viewPastClearancesModal .delete-year-btn:active {
    transform: translateY(0);
}

#viewPastClearancesModal .academic-year-body {
    padding: 1.5rem;
}

#viewPastClearancesModal .semester-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

#viewPastClearancesModal .semester-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

#viewPastClearancesModal .semester-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--deep-navy-blue, #1e3a5f);
    margin: 0 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

#viewPastClearancesModal .periods-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

#viewPastClearancesModal .clearance-period-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 1rem;
    transition: all 0.2s ease;
}

#viewPastClearancesModal .clearance-period-item:hover {
    border-color: var(--darker-saturated-blue, #0c5591);
    box-shadow: 0 2px 4px rgba(12, 85, 145, 0.1);
}

#viewPastClearancesModal .period-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

#viewPastClearancesModal .period-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--deep-navy-blue, #1e3a5f);
}

#viewPastClearancesModal .period-dates {
    font-size: 0.875rem;
    color: #6c757d;
}

#viewPastClearancesModal .period-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

#viewPastClearancesModal .period-stats span {
    color: #495057;
}

#viewPastClearancesModal .period-stats strong {
    color: var(--darker-saturated-blue, #0c5591);
    font-weight: 600;
}

#viewPastClearancesModal .loading-text {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-style: italic;
}

/* Sectors List Layout (Simple Text) */
#viewPastClearancesModal .sectors-list {
    margin-top: 0.75rem;
    padding-left: 0.5rem;
}

#viewPastClearancesModal .sector-text-line {
    display: flex;
    align-items: baseline;
    padding: 0.4rem 0;
    font-size: 0.9rem;
    line-height: 1.5;
    color: #495057;
}

#viewPastClearancesModal .sector-bullet {
    color: var(--darker-saturated-blue, #0c5591);
    font-weight: bold;
    margin-right: 0.5rem;
    flex-shrink: 0;
}

#viewPastClearancesModal .sector-name {
    font-weight: 600;
    color: var(--deep-navy-blue, #1e3a5f);
    margin-right: 0.5rem;
    min-width: 140px;
    flex-shrink: 0;
}

#viewPastClearancesModal .sector-text {
    color: #6c757d;
    flex: 1;
}

#viewPastClearancesModal .sector-text.no-data {
    font-style: italic;
    color: #adb5bd;
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
        
        // Load all past clearances (all sectors)
        if (typeof loadAllPastClearances === 'function') {
            loadAllPastClearances();
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
 * Load all past clearances for all sectors
 */
async function loadAllPastClearances() {
    const container = document.getElementById('all-past-clearances');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-text">Loading clearance history...</div>';
    
    try {
        const sectors = ['College', 'Senior High School', 'Faculty'];
        
        // Fetch all sectors in parallel
        const responses = await Promise.all(
            sectors.map(sector => 
                fetch(`../../api/clearance/past_clearances.php?sector=${encodeURIComponent(sector)}`, {
                    credentials: 'include'
                })
            )
        );
        
        // Parse all responses
        const dataArray = await Promise.all(
            responses.map(res => res.json())
        );
        
        // Merge all periods from all sectors
        const allPeriods = [];
        dataArray.forEach((data, index) => {
            if (data.success && data.periods) {
                // Tag each period with its sector
                data.periods.forEach(period => {
                    allPeriods.push({
                        ...period,
                        sector: sectors[index]
                    });
                });
            }
        });
        
        // Render grouped by academic year
        renderPastClearances(container, allPeriods);
        
    } catch (error) {
        console.error('Error loading past clearances:', error);
        container.innerHTML = '<div class="loading-text">Error loading clearance history</div>';
    }
}

/**
 * Group periods by academic year, semester, and sector
 */
function groupPeriodsByAcademicYear(periods) {
    const grouped = {};

    periods.forEach((period) => {
        const yearKey = period.academic_year;
        const semesterKey = period.semester_name;
        const sectorKey = period.sector; // Include sector

        if (!grouped[yearKey]) {
            grouped[yearKey] = {
                academic_year: yearKey,
                academic_year_id: period.academic_year_id,
                semesters: {},
            };
        }

        if (!grouped[yearKey].semesters[semesterKey]) {
            grouped[yearKey].semesters[semesterKey] = {
                semester_name: semesterKey,
                semester_id: period.semester_id,
                sectors: {}, // Changed from periods[] to sectors{}
            };
        }

        // Store period by sector
        if (!grouped[yearKey].semesters[semesterKey].sectors[sectorKey]) {
            grouped[yearKey].semesters[semesterKey].sectors[sectorKey] = {
                period: period,
                stats: {
                    total_applications: period.total_applications || period.total_forms || 0,
                    completed_applications: period.completed_applications || period.completed_forms || 0,
                    status: period.status
                }
            };
        }
    });

    return grouped;
}

/**
 * Render past clearances grouped by academic year
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
    
    // Group by academic year
    const grouped = groupPeriodsByAcademicYear(pastPeriods);
    
    // Render academic year cards
    const html = Object.values(grouped).map(yearData => {
        return renderAcademicYearCard(yearData);
    }).join('');
    
    container.innerHTML = html;
}

/**
 * Render academic year card with delete button
 */
function renderAcademicYearCard(yearData) {
    const semesterHtml = Object.values(yearData.semesters)
        .sort((a, b) => {
            // Sort semesters: 1st before 2nd
            if (a.semester_name === '1st') return -1;
            if (b.semester_name === '1st') return 1;
            return a.semester_name.localeCompare(b.semester_name);
        })
        .map(semester => {
            return `
                <div class="semester-section">
                    <h5 class="semester-title">${semester.semester_name} Semester</h5>
                    <div class="sectors-list">
                        ${renderSectorBreakdown(semester.sectors)}
                    </div>
                </div>
            `;
        }).join('');
    
    return `
        <div class="academic-year-card" data-year-id="${yearData.academic_year_id}">
            <div class="academic-year-header">
                <h4 class="academic-year-title">
                    <i class="fas fa-calendar-alt"></i> Academic Year: ${yearData.academic_year}
                </h4>
                <button class="btn btn-sm btn-danger delete-year-btn" 
                        onclick="deleteAcademicYear(${yearData.academic_year_id}, '${yearData.academic_year.replace(/'/g, "\\'")}')"
                        title="Delete this academic year and all associated data">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
            <div class="academic-year-body">
                ${semesterHtml}
            </div>
        </div>
    `;
}

/**
 * Render sector breakdown for a semester (simple text format)
 */
function renderSectorBreakdown(sectors) {
    const sectorOrder = ['College', 'Senior High School', 'Faculty'];
    
    return sectorOrder.map(sectorName => {
        const sectorData = sectors[sectorName];
        
        if (!sectorData || !sectorData.period) {
            return `
                <div class="sector-text-line">
                    <span class="sector-bullet">•</span>
                    <span class="sector-name">${sectorName}:</span>
                    <span class="sector-text no-data">No clearance period for this sector</span>
                </div>
            `;
        }
        
        const period = sectorData.period;
        const status = period.status || 'N/A';
        const apps = period.total_applications || period.total_forms || 0;
        const done = period.completed_applications || period.completed_forms || 0;
        const dates = `${formatDate(period.start_date)} - ${formatDate(period.end_date || period.ended_at)}`;
        
        return `
            <div class="sector-text-line">
                <span class="sector-bullet">•</span>
                <span class="sector-name">${sectorName}:</span>
                <span class="sector-text">
                    ${status} | ${apps} apps | ${done} done | ${dates}
                </span>
            </div>
        `;
    }).join('');
}

/**
 * Delete academic year with confirmation
 */
async function deleteAcademicYear(academicYearId, academicYearLabel) {
    try {
        // First, check if deletion is allowed
        const checkResponse = await fetch(
            `../../api/clearance/check_year_deletable.php?id=${academicYearId}`,
            { credentials: 'include' }
        );
        const checkData = await checkResponse.json();

        if (!checkData.success) {
            showToast(checkData.message || 'Unable to check if academic year can be deleted', 'error');
            return;
        }

        if (!checkData.can_delete) {
            showToast(checkData.message || 'Cannot delete this academic year', 'error');
            return;
        }

        // Build impact summary
        const impact = checkData.impact || {};
        const impactSummary = buildDeletionImpactSummary(impact);

        // Show confirmation modal
        const confirmed = await showConfirmationModal(
            `Delete Academic Year "${academicYearLabel}"`,
            impactSummary,
            'Delete Academic Year',
            'Cancel',
            'danger'
        );

        if (!confirmed) {
            return;
        }

        // Perform deletion
        showToast('Deleting academic year...', 'info');

        const response = await fetch(
            `../../api/clearance/years.php?id=${academicYearId}`,
            {
                method: 'DELETE',
                credentials: 'include',
            }
        );

        const data = await response.json();

        if (data.success) {
            showToast(
                `Academic year "${academicYearLabel}" deleted successfully`,
                'success'
            );
            
            // Reload the modal data
            if (typeof loadAllPastClearances === 'function') {
                loadAllPastClearances();
            }
        } else {
            showToast(
                data.message || 'Failed to delete academic year',
                'error'
            );
        }
    } catch (error) {
        console.error('Error deleting academic year:', error);
        showToast(
            'An error occurred while deleting the academic year',
            'error'
        );
    }
}

/**
 * Build deletion impact summary
 */
function buildDeletionImpactSummary(impact) {
    let summary = 'This will permanently delete: ';
    summary += `The academic year record, ${impact.semester_count || 0} semester(s), `;
    summary += `${impact.period_count || 0} clearance period(s), and ${impact.form_count || 0} clearance form(s). `;
    summary += '⚠️ Warning: This action cannot be undone!';
    return summary;
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
window.loadAllPastClearances = loadAllPastClearances;
window.exportPastClearances = exportPastClearances;
window.exportPeriodReport = exportPeriodReport;
window.viewPeriodDetails = viewPeriodDetails;
window.deleteAcademicYear = deleteAcademicYear;
</script>
