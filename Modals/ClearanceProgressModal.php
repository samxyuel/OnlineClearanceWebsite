<?php
// Clearance Progress Modal - View Detailed Clearance Progress
// This modal is included in StudentManagement.php and FacultyManagement.php
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay clearance-progress-modal-overlay" id="clearanceProgressModal">
    <div class="modal-window" style="max-width: 800px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeClearanceProgressModal()">&times;</button>
        
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-tasks"></i> Clearance Progress Details - <span id="progressPersonName">Student Name</span>
            </h2>
            <div class="modal-supporting-text">View detailed clearance progress and signatory status</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <!-- Overall Progress Section -->
            <div class="progress-overview-section">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i> Overall Progress
                </h3>
                
                <div class="progress-summary">
                    <div class="progress-stats">
                        <div class="stat-item">
                            <span class="stat-label">Completion</span>
                            <span class="stat-value" id="completionPercentage">75%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Status</span>
                            <span class="status-badge" id="overallStatusBadge">In Progress</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Signatories</span>
                            <span class="stat-value" id="signatoriesSummary">3 of 5 completed</span>
                        </div>
                    </div>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill" style="width: 75%;"></div>
                        </div>
                        <div class="progress-text">
                            <span id="progressText">3 of 5 signatories completed</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Signatories List Section -->
            <div class="signatories-section">
                <h3 class="section-title">
                    <i class="fas fa-users"></i> Signatories Status
                </h3>
                
                <div class="signatories-list" id="signatoriesList">
                    <!-- Signatories will be populated dynamically -->
                </div>
            </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeClearanceProgressModal()">
                <i class="fas fa-times"></i> Close
            </button>
            <button class="modal-action-primary" onclick="exportClearanceForm()">
                <i class="fas fa-download"></i> Export Clearance Form
            </button>
        </div>
    </div>
</div>

<style>
/* Clearance Progress Modal Specific Styles */
.clearance-progress-modal-overlay .modal-window {
    max-width: 800px;
    max-height: 90vh;
}

.progress-overview-section,
.signatories-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--very-light-off-white);
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--deep-navy-blue);
}

.section-title i {
    color: var(--medium-muted-blue);
}

.progress-summary {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    text-align: center;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--medium-muted-blue);
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--deep-navy-blue);
}

.progress-bar-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.progress-bar {
    width: 100%;
    height: 12px;
    background: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    border-radius: 6px;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-size: 0.9rem;
    color: var(--medium-muted-blue);
}

.signatories-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.signatory-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.signatory-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.signatory-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.signatory-position {
    font-size: 0.85rem;
    color: var(--medium-muted-blue);
    font-weight: 500;
}

.signatory-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--deep-navy-blue);
}

.signatory-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pending {
    background: #fef3c7;
    color: #d97706;
}

.status-badge.approved {
    background: #d1fae5;
    color: #059669;
}

.status-badge.rejected {
    background: #fee2e2;
    color: #dc2626;
}

.status-badge.in-progress {
    background: #dbeafe;
    color: #2563eb;
}

.status-badge.complete {
    background: #d1fae5;
    color: #059669;
}

.status-badge.unapplied {
    background: #f3f4f6;
    color: #6b7280;
}

.status-badge.applied {
    background: #e0e7ff;
    color: #4338ca;
}

/* Responsive Design */
@media (max-width: 768px) {
    .clearance-progress-modal-overlay .modal-window {
        max-width: 95vw;
        margin: 1rem;
    }
    
    .progress-stats {
        grid-template-columns: 1fr;
    }
    
    .signatory-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .signatory-status {
        align-self: flex-end;
    }
}
</style>

<script>
// Clearance Progress Modal Functions
window.openClearanceProgressModal = function(personId, personType, personName, schoolTerm = '') {
    try {
        const modal = document.getElementById('clearanceProgressModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('Clearance progress modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        const personNameElement = document.getElementById('progressPersonName');
        if (personNameElement) {
            personNameElement.textContent = personName;
        }
        
        // Load clearance progress data for the optional school term (if provided)
        loadClearanceProgressData(personId, personType, schoolTerm);
        
        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('clearanceProgressModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }
    } catch (error) {
        if (typeof showToastNotification === 'function') {
            showToastNotification('Unable to open clearance progress modal. Please try again.', 'error');
        }
    }
};

window.closeClearanceProgressModal = function() {
    console.log('[ClearanceProgressModal] closeClearanceProgressModal() called');
    try {
        const modal = document.getElementById('clearanceProgressModal');
        if (!modal) {
            console.warn('[ClearanceProgressModal] Modal not found');
            return;
        }
        console.log('[ClearanceProgressModal] Closing modal:', modal.id);

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('clearanceProgressModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            modal.classList.remove('active');
        }
    } catch (error) {
        // Silent error handling
    }
};

function loadClearanceProgressData(personId, personType, schoolTerm = '') {
    // The user_status.php API can handle both students and faculty by user_id.
    // The personId from the management pages is the user_id.

    
    const url = new URL('../../api/clearance/user_status.php', window.location.href);

    if (personType === 'student') {
        url.searchParams.append('user_id', personId);
    } else if (personType === 'faculty') {
        url.searchParams.append('employee_number', personId);
    } else {
        console.error('Invalid person type for clearance progress:', personType);
        return; // Stop if the type is unknown
    }

    // If a specific school term was provided (from the filters), include it so
    // the backend can scope the progress to that term.
    if (schoolTerm) {
        url.searchParams.append('school_term', schoolTerm);
    }
    
    
    fetch(url.toString(), {credentials:'include'})
        .then(r => {
            if (!r.ok) throw new Error(`Network response was not ok, status: ${r.status}`);
            return r.json();
        })
        .then(res=>{
            if(!res.success){throw new Error(res.message||'Failed to load progress');}

            const approved = res.approved_count || 0;
            const total    = res.total_signatories   || 0;
            const completionPercentage = total > 0 ? Math.round((approved / total) * 100) : 0;

            const signatories = (res.signatories || []).map(s => ({
                position: s.designation_name,
                name: s.signatory_name||'-',
                status: (s.action || 'Unapplied').toLowerCase().replace(' ', '-'),
                statusText: s.action || 'Unapplied'
            }));

            const payload={
                signatories,
                completionPercentage,
                completedCount: approved,
                totalCount: total,
                overallStatus: (res.overall_status||'Unapplied').toLowerCase().replace(' ','-')
            };
            updateProgressDisplay(payload);
        })
        .catch(err=>{
            console.error('Failed to load clearance progress:', err);
            const signatoriesList = document.getElementById('signatoriesList');
            signatoriesList.innerHTML = `<div class="error-state" style="padding: 2rem; text-align: center; color: var(--danger-red);">
                                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                            <p>Could not load clearance progress.</p>
                                            <small>${err.message}</small>
                                         </div>`;
            // Also reset the progress overview
            updateProgressDisplay({
                completionPercentage: 0,
                completedCount: 0,
                totalCount: 0,
                overallStatus: 'error'
            });
        });
}

function updateProgressDisplay(data) {
    // Update overall progress
    document.getElementById('completionPercentage').textContent = `${data.completionPercentage}%`;
    document.getElementById('signatoriesSummary').textContent = `${data.completedCount} of ${data.totalCount} completed`;
    document.getElementById('progressText').textContent = `${data.completedCount} of ${data.totalCount} signatories completed`;
    document.getElementById('progressFill').style.width = `${data.completionPercentage}%`;
    
    // Update overall status badge
    const overallStatusBadge = document.getElementById('overallStatusBadge');
    overallStatusBadge.textContent = data.overallStatus.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
    overallStatusBadge.className = `status-badge ${data.overallStatus}`;
    
    // Update signatories list
    const signatoriesList = document.getElementById('signatoriesList');
    signatoriesList.innerHTML = '';
    
    data.signatories.forEach(signatory => {
        const signatoryItem = document.createElement('div');
        signatoryItem.className = 'signatory-item';
        signatoryItem.innerHTML = `
            <div class="signatory-info">
                <div class="signatory-position">${signatory.position}</div>
                <div class="signatory-name">${signatory.name}</div>
            </div>
            <div class="signatory-status">
                <span class="status-badge ${signatory.status}">${signatory.statusText}</span>
            </div>
        `;
        signatoriesList.appendChild(signatoryItem);
    });
}

function exportClearanceForm() {
    const personName = document.getElementById('progressPersonName').textContent;
    showToastNotification(`Exporting clearance form for ${personName}...`, 'info');
    // In a real application, this would generate and download the clearance form PDF
    // This will be developed in the near future
}

// Make export function globally available
window.exportClearanceForm = exportClearanceForm;
</script>
