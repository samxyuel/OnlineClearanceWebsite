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
let departmentJobs = {}; // Track job IDs for each department

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
    
    title.textContent = `📋 Start Form Distribution - ${sector}`;
    description.textContent = `Select departments to start clearance form distribution for ${sector}. Forms will be distributed automatically via cronjob.`;
    
    // Show pause button if period is ongoing
    const pauseBtn = document.getElementById('departmentModalPauseBtn');
    pauseBtn.style.display = 'inline-block';
    
    modal.style.display = 'flex';
    
    // Load departments
    await loadDepartmentsForDistribution(sector, academicYearId, semesterId);
}

/**
 * Close department distribution modal
 */
function closeDepartmentDistributionModal() {
    const modal = document.getElementById('departmentDistributionModal');
    modal.style.display = 'none';
    currentSector = null;
    currentAcademicYearId = null;
    currentSemesterId = null;
    departmentJobs = {};
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
        const response = await fetchJSON(
            `${API_BASE}/get_departments_for_distribution.php?clearance_type=${encodeURIComponent(sector)}&academic_year_id=${academicYearId}&semester_id=${semesterId}`
        );
        
        if (response.success) {
            displayDepartments(response.departments, response.total_departments, response.total_users);
            loading.style.display = 'none';
            content.style.display = 'block';
        } else {
            throw new Error(response.message || 'Failed to load departments');
        }
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
        
        deptItem.innerHTML = `
            <div class="department-info">
                <div class="department-name">${deptName}</div>
                <div class="department-meta">
                    <i class="fas fa-users"></i> ${userCount} ${userCount === 1 ? 'user' : 'users'}
                </div>
                <div class="department-progress" id="dept-progress-${deptId ?? 'unassigned'}" style="display: none;">
                    <span id="dept-progress-text-${deptId ?? 'unassigned'}"></span>
                </div>
            </div>
            <div class="department-actions">
                <span class="department-status pending" id="dept-status-${deptId ?? 'unassigned'}">Pending</span>
                <button class="btn btn-success btn-start-dept" 
                        onclick="startDepartmentDistribution(${deptId !== null ? deptId : 'null'}, '${deptName.replace(/'/g, "\\'")}')"
                        id="dept-btn-${deptId ?? 'unassigned'}">
                    <i class="fas fa-play"></i> Start
                </button>
            </div>
        `;
        
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
            departmentJobs[departmentId ?? 'unassigned'] = job.job_id;
            
            status.className = 'department-status processing';
            status.textContent = 'Processing';
            btn.innerHTML = '<i class="fas fa-check"></i> Started';
            btn.className = 'btn btn-secondary btn-start-dept';
            btn.disabled = true;
            
            progress.style.display = 'block';
            progressText.textContent = `0/${job.total_users} users (0%)`;
            
            showToast(`Form distribution started for ${departmentName}. Processing ${job.total_users} users...`, 'success');
            
            // Start polling for progress
            pollDepartmentJobStatus(job.job_id, departmentId, departmentName);
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
 * Pause sector period from modal
 */
function pauseSectorPeriodFromModal() {
    if (currentSector) {
        closeDepartmentDistributionModal();
        pauseSectorPeriod(currentSector);
    }
}
</script>

