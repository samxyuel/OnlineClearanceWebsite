<?php
/**
 * Department Distribution Modal
 * 
 * Shows departments for a sector and allows starting form distribution per department
 */
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay" id="departmentDistributionModal" style="display: none;">
    <div class="modal-window" style="max-width: 800px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeDepartmentDistributionModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title" id="departmentModalTitle">📋 Start Form Distribution</h2>
            <div class="modal-supporting-text" id="departmentModalDescription">
                Select departments to start clearance form distribution. Forms will be distributed automatically via cronjob.
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <!-- Loading State -->
            <div id="departmentModalLoading" class="text-center" style="padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-3x" style="color: #007bff;"></i>
                <p style="margin-top: 20px;">Loading departments...</p>
            </div>
            
            <!-- Departments List -->
            <div id="departmentModalContent" style="display: none;">
                <div class="departments-list" id="departmentsList">
                    <!-- Departments will be populated here -->
                </div>
                
                <!-- Summary -->
                <div class="department-summary" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Total Departments:</strong> <span id="totalDepartments">0</span> | 
                    <strong>Total Users:</strong> <span id="totalUsers">0</span>
                </div>
            </div>
            
            <!-- Error State -->
            <div id="departmentModalError" style="display: none; padding: 40px; text-align: center; color: #dc3545;">
                <i class="fas fa-exclamation-triangle fa-3x"></i>
                <p style="margin-top: 20px;" id="departmentModalErrorMessage">Failed to load departments</p>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDepartmentDistributionModal()">Close</button>
            <button type="button" class="btn btn-warning" id="departmentModalPauseBtn" onclick="pauseSectorPeriodFromModal()" style="display: none;">
                <i class="fas fa-pause"></i> Pause Clearance Period
            </button>
        </div>
    </div>
</div>

<style>
.departments-list {
    max-height: 500px;
    overflow-y: auto;
}

.department-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin-bottom: 10px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.department-item:hover {
    background: #f8f9fa;
    border-color: #007bff;
}

.department-item.processing {
    background: #fff3cd;
    border-color: #ffc107;
}

.department-item.completed {
    background: #d4edda;
    border-color: #28a745;
}

.department-item.failed {
    background: #f8d7da;
    border-color: #dc3545;
}

.department-info {
    flex: 1;
}

.department-name {
    font-weight: 600;
    font-size: 16px;
    color: #212529;
    margin-bottom: 5px;
}

.department-meta {
    font-size: 14px;
    color: #6c757d;
}

.department-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.department-status {
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.department-status.pending {
    background: #e9ecef;
    color: #495057;
}

.department-status.processing {
    background: #fff3cd;
    color: #856404;
}

.department-status.completed {
    background: #d4edda;
    color: #155724;
}

.department-status.failed {
    background: #f8d7da;
    color: #721c24;
}

.department-progress {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.btn-start-dept {
    padding: 8px 16px;
    font-size: 14px;
}

.btn-start-dept:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
let currentSector = null;
let currentAcademicYearId = null;
let currentSemesterId = null;
let departmentJobs = {}; // Track job IDs for each department: { departmentId: { job_id, status, progress, ... } }
let globalPollingInterval = null; // Global polling interval that continues even when modal is closed

/**
 * Open department distribution modal
 */
async function openDepartmentDistributionModal(sector, academicYearId, semesterId) {
    currentSector = sector;
    currentAcademicYearId = academicYearId;
    currentSemesterId = semesterId;
    
    const modal = document.getElementById('departmentDistributionModal');
    const title = document.getElementById('departmentModalTitle');
    const description = document.getElementById('departmentModalDescription');
    
    title.textContent = `📋 Form Distribution - ${sector}`;
    description.textContent = `Select departments to start clearance form distribution for ${sector}. Forms will be distributed automatically via cronjob.`;
    
    // Show pause button if period is ongoing
    const pauseBtn = document.getElementById('departmentModalPauseBtn');
    pauseBtn.style.display = 'inline-block';
    
    modal.style.display = 'flex';
    
    // Load departments and check for existing active jobs
    await loadDepartmentsForDistribution(sector, academicYearId, semesterId);
    
    // Start global polling if not already running
    startGlobalPolling();
}

/**
 * Close department distribution modal
 */
function closeDepartmentDistributionModal() {
    const modal = document.getElementById('departmentDistributionModal');
    modal.style.display = 'none';
    // Note: We don't clear currentSector, currentAcademicYearId, currentSemesterId, or departmentJobs
    // This allows polling to continue in the background
    // Global polling will continue running even when modal is closed
}

/**
 * Load departments for the modal
 */
async function loadDepartmentsForDistribution(sector, academicYearId, semesterId) {
    const loading = document.getElementById('departmentModalLoading');
    const content = document.getElementById('departmentModalContent');
    const error = document.getElementById('departmentModalError');
    
    loading.style.display = 'block';
    content.style.display = 'none';
    error.style.display = 'none';
    
    try {
        // Load departments
        const deptResponse = await fetchJSON(
            `${API_BASE}/get_departments_for_distribution.php?clearance_type=${encodeURIComponent(sector)}&academic_year_id=${academicYearId}&semester_id=${semesterId}`
        );
        
        if (!deptResponse.success) {
            throw new Error(deptResponse.message || 'Failed to load departments');
        }
        
        // Check for existing active jobs
        const jobsResponse = await fetchJSON(
            `${API_BASE}/distribution_status.php?get_all_active=true&clearance_type=${encodeURIComponent(sector)}&academic_year_id=${academicYearId}&semester_id=${semesterId}`
        );
        
        // Update departmentJobs with existing active jobs
        if (jobsResponse.success && jobsResponse.active_jobs) {
            jobsResponse.active_jobs.forEach(job => {
                const deptKey = job.department_id !== null ? job.department_id : 'unassigned';
                departmentJobs[deptKey] = {
                    job_id: job.job_id,
                    status: job.status,
                    progress: job.progress,
                    results: job.results,
                    department_id: job.department_id,
                    department_name: job.department_name
                };
            });
        }
        
        // Display departments with their current job status
        displayDepartments(deptResponse.departments, deptResponse.total_departments, deptResponse.total_users);
        loading.style.display = 'none';
        content.style.display = 'block';
    } catch (error) {
        console.error('Error loading departments:', error);
        loading.style.display = 'none';
        error.style.display = 'block';
        document.getElementById('departmentModalErrorMessage').textContent = error.message || 'Failed to load departments';
    }
}

/**
 * Display departments in the modal
 */
function displayDepartments(departments, totalDepartments, totalUsers) {
    const list = document.getElementById('departmentsList');
    const totalDeptSpan = document.getElementById('totalDepartments');
    const totalUsersSpan = document.getElementById('totalUsers');
    
    totalDeptSpan.textContent = totalDepartments;
    totalUsersSpan.textContent = totalUsers;
    
    list.innerHTML = '';
    
    if (departments.length === 0) {
        list.innerHTML = '<p style="text-align: center; padding: 40px; color: #6c757d;">No departments found with eligible users.</p>';
        return;
    }
    
    departments.forEach(dept => {
        const deptItem = document.createElement('div');
        deptItem.className = 'department-item';
        deptItem.id = `dept-item-${dept.department_id ?? 'unassigned'}`;
        
        const deptName = dept.department_name || 'Unassigned Faculty';
        const deptId = dept.department_id;
        const userCount = dept.user_count || 0;
        const deptKey = deptId !== null ? deptId : 'unassigned';
        
        // Check if there's an existing job for this department
        const existingJob = departmentJobs[deptKey];
        const hasActiveJob = existingJob && (existingJob.status === 'pending' || existingJob.status === 'processing');
        
        // Determine status and button state
        let statusClass = 'pending';
        let statusText = 'Pending';
        let buttonText = '<i class="fas fa-play"></i> Start';
        let buttonClass = 'btn btn-success btn-start-dept';
        let buttonDisabled = '';
        let showProgress = false;
        let progressText = '';
        
        if (existingJob) {
            statusText = existingJob.status.charAt(0).toUpperCase() + existingJob.status.slice(1);
            if (existingJob.status === 'processing') {
                statusClass = 'processing';
                buttonText = '<i class="fas fa-check"></i> Processing';
                buttonClass = 'btn btn-secondary btn-start-dept';
                buttonDisabled = 'disabled';
                showProgress = true;
                if (existingJob.progress) {
                    progressText = `${existingJob.progress.processed}/${existingJob.progress.total} users (${existingJob.progress.percentage}%)`;
                }
            } else if (existingJob.status === 'completed') {
                statusClass = 'completed';
                buttonText = '<i class="fas fa-check"></i> Completed';
                buttonClass = 'btn btn-secondary btn-start-dept';
                buttonDisabled = 'disabled';
                showProgress = true;
                if (existingJob.progress) {
                    progressText = `${existingJob.progress.processed}/${existingJob.progress.total} users (100%)`;
                }
            } else if (existingJob.status === 'failed') {
                statusClass = 'failed';
                buttonText = '<i class="fas fa-exclamation-triangle"></i> Failed';
                buttonClass = 'btn btn-danger btn-start-dept';
                buttonDisabled = '';
            } else if (existingJob.status === 'pending') {
                statusClass = 'processing';
                buttonText = '<i class="fas fa-clock"></i> Queued';
                buttonClass = 'btn btn-secondary btn-start-dept';
                buttonDisabled = 'disabled';
            }
        }
        
        deptItem.innerHTML = `
            <div class="department-info">
                <div class="department-name">${deptName}</div>
                <div class="department-meta">
                    <i class="fas fa-users"></i> ${userCount} ${userCount === 1 ? 'user' : 'users'}
                </div>
                <div class="department-progress" id="dept-progress-${deptKey}" style="display: ${showProgress ? 'block' : 'none'};">
                    <span id="dept-progress-text-${deptKey}">${progressText}</span>
                </div>
            </div>
            <div class="department-actions">
                <span class="department-status ${statusClass}" id="dept-status-${deptKey}">${statusText}</span>
                <button class="${buttonClass}" 
                        onclick="startDepartmentDistribution(${deptId !== null ? deptId : 'null'}, '${deptName.replace(/'/g, "\\'")}')"
                        id="dept-btn-${deptKey}"
                        ${buttonDisabled}>
                    ${buttonText}
                </button>
            </div>
        `;
        
        // Update item class based on status
        if (existingJob) {
            deptItem.className = `department-item ${existingJob.status}`;
        }
        
        list.appendChild(deptItem);
    });
}

/**
 * Start form distribution for a specific department
 */
async function startDepartmentDistribution(departmentId, departmentName) {
    const btnId = `dept-btn-${departmentId ?? 'unassigned'}`;
    const btn = document.getElementById(btnId);
    const statusId = `dept-status-${departmentId ?? 'unassigned'}`;
    const status = document.getElementById(statusId);
    const progressId = `dept-progress-${departmentId ?? 'unassigned'}`;
    const progress = document.getElementById(progressId);
    const progressTextId = `dept-progress-text-${departmentId ?? 'unassigned'}`;
    const progressText = document.getElementById(progressTextId);
    const itemId = `dept-item-${departmentId ?? 'unassigned'}`;
    const item = document.getElementById(itemId);
    
    if (!btn || btn.disabled) return;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
    status.className = 'department-status processing';
    status.textContent = 'Starting...';
    item.className = 'department-item processing';
    
    try {
        const response = await fetchJSON(`${API_BASE}/create_department_job.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                clearance_type: currentSector,
                academic_year_id: currentAcademicYearId,
                semester_id: currentSemesterId,
                department_id: departmentId
            })
        });
        
        if (response.success && response.job) {
            const job = response.job;
            const deptKey = departmentId !== null ? departmentId : 'unassigned';
            
            // Store job info for global polling
            departmentJobs[deptKey] = {
                job_id: job.job_id,
                status: 'pending',
                progress: {
                    processed: 0,
                    total: job.total_users,
                    percentage: 0,
                    remaining: job.total_users
                },
                results: {
                    forms_created: 0,
                    forms_skipped: 0,
                    signatories_assigned: 0
                },
                department_id: departmentId,
                department_name: departmentName
            };
            
            status.className = 'department-status processing';
            status.textContent = 'Pending';
            btn.innerHTML = '<i class="fas fa-clock"></i> Queued';
            btn.className = 'btn btn-secondary btn-start-dept';
            btn.disabled = true;
            
            progress.style.display = 'block';
            progressText.textContent = `0/${job.total_users} users (0%)`;
            
            showToast(`Form distribution started for ${departmentName}. Processing ${job.total_users} users...`, 'success');
            
            // Ensure global polling is running
            startGlobalPolling();
        } else {
            throw new Error(response.message || 'Failed to start form distribution');
        }
    } catch (error) {
        console.error('Error starting department distribution:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play"></i> Start';
        status.className = 'department-status failed';
        status.textContent = 'Failed';
        item.className = 'department-item failed';
        showToast(`Failed to start form distribution for ${departmentName}: ${error.message}`, 'error');
    }
}

/**
 * Poll job status for a department
 */
async function pollDepartmentJobStatus(jobId, departmentId, departmentName) {
    const statusId = `dept-status-${departmentId ?? 'unassigned'}`;
    const status = document.getElementById(statusId);
    const progressTextId = `dept-progress-text-${departmentId ?? 'unassigned'}`;
    const progressText = document.getElementById(progressTextId);
    const itemId = `dept-item-${departmentId ?? 'unassigned'}`;
    const item = document.getElementById(itemId);
    
    try {
        const response = await fetchJSON(`${API_BASE}/distribution_status.php?job_id=${jobId}`);
        
        if (response.success && response.job) {
            const job = response.job;
            const progress = job.progress;
            
            // Update progress
            if (progressText) {
                progressText.textContent = `${progress.processed}/${progress.total} users (${progress.percentage}%)`;
            }
            
            // Update status
            if (status) {
                if (job.status === 'completed') {
                    status.className = 'department-status completed';
                    status.textContent = 'Completed';
                    if (item) item.className = 'department-item completed';
                    
                    showToast(`Form distribution completed for ${departmentName}! ${job.results.forms_created} forms created.`, 'success');
                    return; // Stop polling
                } else if (job.status === 'failed') {
                    status.className = 'department-status failed';
                    status.textContent = 'Failed';
                    if (item) item.className = 'department-item failed';
                    showToast(`Form distribution failed for ${departmentName}: ${job.error_message}`, 'error');
                    return; // Stop polling
                } else if (job.status === 'processing') {
                    status.className = 'department-status processing';
                    status.textContent = 'Processing';
                    if (item) item.className = 'department-item processing';
                    
                    // Continue polling after 5 seconds
                    setTimeout(() => pollDepartmentJobStatus(jobId, departmentId, departmentName), 5000);
                }
            }
        }
    } catch (error) {
        console.error('Error polling job status:', error);
        // Retry after 5 seconds
        setTimeout(() => pollDepartmentJobStatus(jobId, departmentId, departmentName), 5000);
    }
}

/**
 * Start global polling for all active jobs
 * This continues even when the modal is closed
 */
function startGlobalPolling() {
    // Clear existing interval if any
    if (globalPollingInterval) {
        clearInterval(globalPollingInterval);
    }
    
    // Poll every 5 seconds
    globalPollingInterval = setInterval(async () => {
        // Only poll if we have active jobs and valid sector info
        if (!currentSector || !currentAcademicYearId || !currentSemesterId) {
            return;
        }
        
        const activeJobKeys = Object.keys(departmentJobs).filter(key => {
            const job = departmentJobs[key];
            return job && (job.status === 'pending' || job.status === 'processing');
        });
        
        if (activeJobKeys.length === 0) {
            // No active jobs, stop polling
            clearInterval(globalPollingInterval);
            globalPollingInterval = null;
            return;
        }
        
        // Poll each active job
        for (const deptKey of activeJobKeys) {
            const job = departmentJobs[deptKey];
            if (!job || !job.job_id) continue;
            
            try {
                const response = await fetchJSON(`${API_BASE}/distribution_status.php?job_id=${job.job_id}`);
                
                if (response.success && response.job) {
                    // Update job status
                    departmentJobs[deptKey] = {
                        job_id: response.job.job_id,
                        status: response.job.status,
                        progress: response.job.progress,
                        results: response.job.results,
                        department_id: response.job.department_id,
                        department_name: response.job.department_name
                    };
                    
                    // Update UI if modal is open
                    updateDepartmentStatusUI(deptKey, response.job);
                    
                    // If job is completed or failed, remove from active tracking
                    if (response.job.status === 'completed' || response.job.status === 'failed') {
                        // Keep in departmentJobs for display, but it won't be polled anymore
                    }
                }
            } catch (error) {
                console.error(`Error polling job ${job.job_id} for department ${deptKey}:`, error);
            }
        }
    }, 5000); // Poll every 5 seconds
}

/**
 * Update department status UI
 */
function updateDepartmentStatusUI(deptKey, job) {
    const statusEl = document.getElementById(`dept-status-${deptKey}`);
    const progressEl = document.getElementById(`dept-progress-${deptKey}`);
    const progressTextEl = document.getElementById(`dept-progress-text-${deptKey}`);
    const btnEl = document.getElementById(`dept-btn-${deptKey}`);
    const itemEl = document.getElementById(`dept-item-${deptKey}`);
    
    if (!statusEl) return; // Element doesn't exist (modal might be closed)
    
    // Update status
    statusEl.className = `department-status ${job.status}`;
    statusEl.textContent = job.status.charAt(0).toUpperCase() + job.status.slice(1);
    
    // Update progress
    if (progressEl && progressTextEl && job.progress) {
        progressEl.style.display = 'block';
        progressTextEl.textContent = `${job.progress.processed}/${job.progress.total} users (${job.progress.percentage}%)`;
    }
    
    // Update button
    if (btnEl) {
        if (job.status === 'processing' || job.status === 'pending') {
            btnEl.disabled = true;
            btnEl.className = 'btn btn-secondary btn-start-dept';
            if (job.status === 'processing') {
                btnEl.innerHTML = '<i class="fas fa-check"></i> Processing';
            } else {
                btnEl.innerHTML = '<i class="fas fa-clock"></i> Queued';
            }
        } else if (job.status === 'completed') {
            btnEl.disabled = true;
            btnEl.className = 'btn btn-secondary btn-start-dept';
            btnEl.innerHTML = '<i class="fas fa-check"></i> Completed';
        } else if (job.status === 'failed') {
            btnEl.disabled = false;
            btnEl.className = 'btn btn-danger btn-start-dept';
            btnEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed';
        }
    }
    
    // Update item class
    if (itemEl) {
        itemEl.className = `department-item ${job.status}`;
    }
}

/**
 * Pause sector period from modal
 */
function pauseSectorPeriodFromModal() {
    if (currentSector) {
        closeDepartmentDistributionModal();
        pauseSectorPeriod(currentSector);
    }
}
</script>

