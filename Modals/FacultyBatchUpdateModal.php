<?php
/**
 * Faculty Batch Update Modal
 * Dynamic modal for updating multiple faculty members at once
 * Permissions vary based on user role (Admin vs Program Head)
 */

// Get current user role and permissions
$isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
$isProgramHead = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
$userRole = $isAdmin ? 'admin' : ($isProgramHead ? 'program_head' : 'unknown');
?>

<!-- Mobile viewport meta tag for proper rendering -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<div class="modal-overlay faculty-batch-update-modal-overlay" id="facultyBatchUpdateModal">
    <div class="modal-window faculty-batch-update-modal">
        <button class="modal-close" onclick="closeFacultyBatchUpdateModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-users-cog"></i> 
                Batch Update - Faculty Members
            </h2>
            <p class="modal-supporting-text">
                Update multiple faculty members' information efficiently. 
                <?php if ($isProgramHead): ?>
                    You can update Employment Status for faculty in your assigned departments.
                <?php else: ?>
                    You can update Employment Status and Department for faculty members.
                <?php endif; ?>
            </p>
        </div>

        <!-- Modal Content -->
        <div class="modal-content-area">
            <!-- Step 1: Filter & Select Faculty -->
            <div class="batch-update-step" id="facultyStep1">
                <div class="step-header">
                    <div class="step-indicator active">
                        <span class="step-number">1</span>
                        <span class="step-title">Filter & Select Faculty</span>
                    </div>
                </div>

                <div class="step-content">
                    <!-- Search and Filters -->
                    <div class="search-filters-section">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="facultyBatchSearchInput" placeholder="Search by name, employee number, or department...">
                        </div>
                        
                        <div class="filter-row">
                            <?php if ($isAdmin): ?>
                            <div class="filter-group">
                                <label for="facultyBatchDepartmentFilter">Department</label>
                                <select id="facultyBatchDepartmentFilter" class="filter-select">
                                    <option value="">All Departments</option>
                                    <!-- Departments will be loaded dynamically -->
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="filter-group">
                                <label for="facultyBatchEmploymentStatusFilter">Employment Status</label>
                                <select id="facultyBatchEmploymentStatusFilter" class="filter-select">
                                    <option value="">All Employment Status</option>
                                    <option value="Full time">Full time</option>
                                    <option value="Part time">Part time</option>
                                    <option value="Part time-Full load">Part time-Full load</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Faculty List -->
                    <div class="students-selection-section">
                        <div class="selection-header">
                            <div class="selection-info">
                                <h4>Faculty Found (<span id="facultyFoundCount">0</span>)</h4>
                                <div class="bulk-selection-controls">
                                    <button class="btn btn-sm btn-outline-primary" onclick="selectAllFilteredFaculty()">
                                        <i class="fas fa-check-square"></i> Select All Filtered
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="clearFacultySelection()">
                                        <i class="fas fa-square"></i> Clear Selection
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="students-list-container">
                            <div class="students-list" id="facultyBatchFacultyList">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading faculty...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="selected-students-summary">
                            <span id="facultySelectedCount">0</span> faculty selected
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Configure Updates -->
            <div class="batch-update-step" id="facultyStep2" style="display: none;">
                <div class="step-header">
                    <div class="step-indicator">
                        <span class="step-number">2</span>
                        <span class="step-title">Configure Updates</span>
                    </div>
                </div>

                <div class="step-content">
                    <div class="update-configuration">
                        <h4>What would you like to update?</h4>
                        
                        <div class="update-options">
                            <div class="update-option">
                                <label class="checkbox-option">
                                    <input type="checkbox" id="facultyUpdateEmploymentStatus" onchange="toggleFacultyUpdateField('employmentStatus')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Employment Status</span>
                                </label>
                                <div class="update-field" id="facultyEmploymentStatusField" style="display: none;">
                                    <select id="facultyNewEmploymentStatus" class="form-control">
                                        <option value="">Select Employment Status</option>
                                        <option value="Full time">Full time</option>
                                        <option value="Part time">Part time</option>
                                        <option value="Part time-Full load">Part time-Full load</option>
                                    </select>
                                </div>
                            </div>
                            
                            <?php if ($isAdmin): ?>
                            <div class="update-option">
                                <label class="checkbox-option">
                                    <input type="checkbox" id="facultyUpdateDepartment" onchange="toggleFacultyUpdateField('department')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Department</span>
                                </label>
                                <div class="update-field" id="facultyDepartmentField" style="display: none;">
                                    <select id="facultyNewDepartment" class="form-control">
                                        <option value="">Select Department</option>
                                        <!-- Departments will be loaded dynamically -->
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Selected Faculty Preview -->
                        <div class="selected-students-preview">
                            <h4>Selected Faculty (<span id="facultyPreviewCount">0</span>)</h4>
                            <div class="students-preview-list" id="facultySelectedPreview">
                                <!-- Selected faculty will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Preview & Confirm -->
            <div class="batch-update-step" id="facultyStep3" style="display: none;">
                <div class="step-header">
                    <div class="step-indicator">
                        <span class="step-number">3</span>
                        <span class="step-title">Preview & Confirm</span>
                    </div>
                </div>

                <div class="step-content">
                    <div class="update-preview">
                        <h4>Update Preview</h4>
                        <p class="preview-description">
                            The following changes will be applied to <strong id="facultyFinalCount">0</strong> faculty members:
                        </p>
                        
                        <div class="preview-table-container">
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th>Faculty Member</th>
                                        <th>Current Values</th>
                                        <th>New Values</th>
                                    </tr>
                                </thead>
                                <tbody id="facultyUpdatePreviewTable">
                                    <!-- Preview rows will be populated here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="update-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>This action cannot be undone. Please review the changes carefully before proceeding.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeFacultyBatchUpdateModal()">
                Cancel
            </button>
            <button class="modal-action-primary" id="facultyNextStepBtn" onclick="nextFacultyBatchUpdateStep()">
                Next Step
            </button>
            <button class="modal-action-primary" id="facultyUpdateFacultyBtn" onclick="executeFacultyBatchUpdate()" style="display: none;">
                <i class="fas fa-save"></i> Update Faculty
            </button>
        </div>
    </div>
</div>

<!-- Batch Update Results Modal -->
<div class="modal-overlay batch-update-results-modal-overlay" id="facultyBatchUpdateResultsModal">
    <div class="modal-window batch-update-results-modal">
        <button class="modal-close" onclick="closeFacultyBatchUpdateResultsModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-check-circle"></i> 
                Batch Update Results
            </h2>
        </div>

        <div class="modal-content-area">
            <div class="results-summary">
                <div class="result-stat success">
                    <i class="fas fa-check-circle"></i>
                    <span class="result-count" id="facultySuccessCount">0</span>
                    <span class="result-label">Successfully Updated</span>
                </div>
                <div class="result-stat error" id="facultyErrorStat" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="result-count" id="facultyErrorCount">0</span>
                    <span class="result-label">Failed to Update</span>
                </div>
            </div>
            
            <div class="results-details" id="facultyResultsDetails">
                <!-- Results details will be populated here -->
            </div>
        </div>

        <div class="modal-actions">
            <button class="modal-action-primary" onclick="closeFacultyBatchUpdateResultsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Faculty Batch Update Modal JavaScript
let facultyBatchUpdateData = {
    selectedFaculty: [],
    currentStep: 1,
    updateFields: {
        employmentStatus: false,
        department: false
    },
    newValues: {
        employmentStatus: '',
        department: ''
    },
    userRole: '<?php echo $userRole; ?>'
};

// Initialize modal
function openFacultyBatchUpdateModal() {
    const modal = document.getElementById('facultyBatchUpdateModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset modal state
    resetFacultyBatchUpdateModal();
    
    // Load initial data
    loadFacultyForBatchUpdate();
    if (facultyBatchUpdateData.userRole === 'admin') {
        loadFacultyDepartments();
    }
    
    // Add mobile-specific enhancements
    addFacultyMobileEnhancements();
}

function closeFacultyBatchUpdateModal() {
    const modal = document.getElementById('facultyBatchUpdateModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset modal state
    resetFacultyBatchUpdateModal();
}

function resetFacultyBatchUpdateModal() {
    facultyBatchUpdateData.selectedFaculty = [];
    facultyBatchUpdateData.currentStep = 1;
    facultyBatchUpdateData.updateFields = { employmentStatus: false, department: false };
    facultyBatchUpdateData.newValues = { employmentStatus: '', department: '' };
    
    // Reset UI
    document.getElementById('facultyStep1').style.display = 'block';
    document.getElementById('facultyStep2').style.display = 'none';
    document.getElementById('facultyStep3').style.display = 'none';
    
    document.getElementById('facultyNextStepBtn').style.display = 'inline-block';
    document.getElementById('facultyUpdateFacultyBtn').style.display = 'none';
    
    // Reset step indicators
    document.querySelectorAll('#facultyBatchUpdateModal .step-indicator').forEach(step => {
        step.classList.remove('active', 'completed');
    });
    document.querySelector('#facultyStep1 .step-indicator').classList.add('active');
}

function nextFacultyBatchUpdateStep() {
    if (facultyBatchUpdateData.currentStep === 1) {
        if (facultyBatchUpdateData.selectedFaculty.length === 0) {
            showToast('Please select at least one faculty member to update.', 'warning');
            return;
        }
        
        // Move to step 2
        document.getElementById('facultyStep1').style.display = 'none';
        document.getElementById('facultyStep2').style.display = 'block';
        
        document.querySelector('#facultyStep1 .step-indicator').classList.remove('active');
        document.querySelector('#facultyStep1 .step-indicator').classList.add('completed');
        document.querySelector('#facultyStep2 .step-indicator').classList.add('active');
        
        facultyBatchUpdateData.currentStep = 2;
        updateFacultySelectedPreview();
        
    } else if (facultyBatchUpdateData.currentStep === 2) {
        // Validate that at least one field is selected for update
        const hasUpdates = facultyBatchUpdateData.updateFields.employmentStatus || 
                          facultyBatchUpdateData.updateFields.department;
        
        if (!hasUpdates) {
            showToast('Please select at least one field to update.', 'warning');
            return;
        }
        
        // Move to step 3
        document.getElementById('facultyStep2').style.display = 'none';
        document.getElementById('facultyStep3').style.display = 'block';
        
        document.querySelector('#facultyStep2 .step-indicator').classList.remove('active');
        document.querySelector('#facultyStep2 .step-indicator').classList.add('completed');
        document.querySelector('#facultyStep3 .step-indicator').classList.add('active');
        
        facultyBatchUpdateData.currentStep = 3;
        generateFacultyUpdatePreview();
        
        document.getElementById('facultyNextStepBtn').style.display = 'none';
        document.getElementById('facultyUpdateFacultyBtn').style.display = 'inline-block';
    }
}

function toggleFacultyUpdateField(fieldName) {
    const checkbox = document.getElementById('facultyUpdate' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
    const field = document.getElementById('faculty' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Field');
    
    if (checkbox.checked) {
        field.style.display = 'block';
        facultyBatchUpdateData.updateFields[fieldName] = true;
    } else {
        field.style.display = 'none';
        facultyBatchUpdateData.updateFields[fieldName] = false;
    }
}

function loadFacultyForBatchUpdate() {
    const facultyList = document.getElementById('facultyBatchFacultyList');
    const facultyFoundCount = document.getElementById('facultyFoundCount');
    
    facultyList.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading faculty...</span>
        </div>
    `;
    
    // Load faculty using staff_faculty_list.php
    fetch('../../api/users/staff_faculty_list.php', { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const faculty = data.faculty || [];
                displayFacultyForSelection(faculty);
                facultyFoundCount.textContent = faculty.length;
            } else {
                throw new Error(data.message || 'Failed to load faculty');
            }
        })
        .catch(error => {
            console.error('Error loading faculty:', error);
            facultyList.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Error loading faculty: ${error.message}</span>
                </div>
            `;
        });
}

function displayFacultyForSelection(faculty) {
    const facultyList = document.getElementById('facultyBatchFacultyList');
    
    if (faculty.length === 0) {
        facultyList.innerHTML = `
            <div class="no-students">
                <i class="fas fa-users"></i>
                <span>No faculty found</span>
            </div>
        `;
        return;
    }
    
    const facultyHTML = faculty.map(member => `
        <div class="student-item" data-faculty-id="${member.user_id}">
            <input type="checkbox" class="student-selection-checkbox" 
                   data-faculty-id="${member.user_id}"
                   onchange="toggleFacultySelection(this)">
            <div class="student-info">
                <div class="student-name">${member.last_name}, ${member.first_name}</div>
                <div class="student-details">
                    <span class="student-id">${member.employee_number}</span>
                    <span>•</span>
                    <span>${member.employment_status || 'N/A'}</span>
                    <span>•</span>
                    <span>${member.department_name || 'N/A'}</span>
                </div>
            </div>
        </div>
    `).join('');
    
    facultyList.innerHTML = facultyHTML;
}

function toggleFacultySelection(checkbox) {
    const facultyId = checkbox.dataset.facultyId;
    const facultyItem = checkbox.closest('.student-item');
    const facultyName = facultyItem.querySelector('.student-name').textContent;
    const facultyDetails = facultyItem.querySelector('.student-details').textContent;
    
    if (checkbox.checked) {
        // Add to selected faculty
        const facultyData = {
            user_id: facultyId,
            name: facultyName,
            employee_number: facultyDetails.split('•')[0].trim(),
            employment_status: facultyDetails.split('•')[1]?.trim() || 'N/A',
            department: facultyDetails.split('•')[2]?.trim() || 'N/A'
        };
        
        facultyBatchUpdateData.selectedFaculty.push(facultyData);
        facultyItem.classList.add('selected');
    } else {
        // Remove from selected faculty
        facultyBatchUpdateData.selectedFaculty = facultyBatchUpdateData.selectedFaculty.filter(
            f => f.user_id !== facultyId
        );
        facultyItem.classList.remove('selected');
    }
    
    updateFacultySelectedPreview();
    updateFacultyFoundCount();
}

function selectAllFilteredFaculty() {
    const checkboxes = document.querySelectorAll('#facultyBatchFacultyList .student-selection-checkbox');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            toggleFacultySelection(checkbox);
        }
    });
}

function clearFacultySelection() {
    const checkboxes = document.querySelectorAll('#facultyBatchFacultyList .student-selection-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('.student-item').classList.remove('selected');
    });
    
    facultyBatchUpdateData.selectedFaculty = [];
    updateFacultySelectedPreview();
    updateFacultyFoundCount();
}

function updateFacultyFoundCount() {
    const selectedCount = document.getElementById('facultySelectedCount');
    selectedCount.textContent = facultyBatchUpdateData.selectedFaculty.length;
}

function loadFacultyDepartments() {
    if (facultyBatchUpdateData.userRole !== 'admin') return;
    
    fetch('../../api/departments/list.php?sector=Faculty', { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const departmentSelect = document.getElementById('facultyBatchDepartmentFilter');
                const newDepartmentSelect = document.getElementById('facultyNewDepartment');
                
                if (departmentSelect) {
                    departmentSelect.innerHTML = '<option value="">All Departments</option>';
                    data.departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.department_id;
                        option.textContent = dept.department_name;
                        departmentSelect.appendChild(option);
                    });
                }
                
                if (newDepartmentSelect) {
                    newDepartmentSelect.innerHTML = '<option value="">Select Department</option>';
                    data.departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.department_id;
                        option.textContent = dept.department_name;
                        newDepartmentSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading departments:', error);
        });
}

function updateFacultySelectedPreview() {
    const previewContainer = document.getElementById('facultySelectedPreview');
    const countElement = document.getElementById('facultyPreviewCount');
    
    countElement.textContent = facultyBatchUpdateData.selectedFaculty.length;
    
    if (facultyBatchUpdateData.selectedFaculty.length === 0) {
        previewContainer.innerHTML = '<div class="no-selection">No faculty selected</div>';
        return;
    }
    
    const previewHTML = facultyBatchUpdateData.selectedFaculty.map(faculty => `
        <div class="student-preview-item">
            <div class="student-info">
                <strong>${faculty.name}</strong>
                <span class="student-id">${faculty.employee_number}</span>
            </div>
            <div class="student-details">
                ${faculty.employment_status} • ${faculty.department}
            </div>
        </div>
    `).join('');
    
    previewContainer.innerHTML = previewHTML;
}

function generateFacultyUpdatePreview() {
    const previewTable = document.getElementById('facultyUpdatePreviewTable');
    const finalCount = document.getElementById('facultyFinalCount');
    
    finalCount.textContent = facultyBatchUpdateData.selectedFaculty.length;
    
    const previewRows = facultyBatchUpdateData.selectedFaculty.map(faculty => {
        const currentValues = [];
        const newValues = [];
        
        if (facultyBatchUpdateData.updateFields.employmentStatus) {
            currentValues.push(`Status: ${faculty.employment_status}`);
            newValues.push(`Status: ${facultyBatchUpdateData.newValues.employmentStatus}`);
        }
        
        if (facultyBatchUpdateData.updateFields.department) {
            currentValues.push(`Department: ${faculty.department}`);
            newValues.push(`Department: ${facultyBatchUpdateData.newValues.department}`);
        }
        
        return `
            <tr>
                <td>
                    <strong>${faculty.name}</strong><br>
                    <small>${faculty.employee_number}</small>
                </td>
                <td>${currentValues.join('<br>')}</td>
                <td>${newValues.join('<br>')}</td>
            </tr>
        `;
    }).join('');
    
    previewTable.innerHTML = previewRows;
}

function executeFacultyBatchUpdate() {
    // Validate that we have updates to make
    const hasUpdates = facultyBatchUpdateData.updateFields.employmentStatus || 
                      facultyBatchUpdateData.updateFields.department;
    
    if (!hasUpdates) {
        showToast('Please select at least one field to update.', 'warning');
        return;
    }
    
    // Prepare update data
    const updateData = {
        student_ids: facultyBatchUpdateData.selectedFaculty.map(f => f.user_id),
        updates: {},
        sector: 'Faculty'
    };
    
    if (facultyBatchUpdateData.updateFields.employmentStatus) {
        updateData.updates.employment_status = facultyBatchUpdateData.newValues.employmentStatus;
    }
    
    if (facultyBatchUpdateData.updateFields.department) {
        updateData.updates.department_id = facultyBatchUpdateData.newValues.department;
    }
    
    // Show loading state
    const updateBtn = document.getElementById('facultyUpdateFacultyBtn');
    const originalText = updateBtn.innerHTML;
    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    updateBtn.disabled = true;
    
    // Execute batch update
    fetch('../../api/users/batch_update_endusers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(updateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFacultyBatchUpdateResults(data);
            closeFacultyBatchUpdateModal();
        } else {
            throw new Error(data.message || 'Batch update failed');
        }
    })
    .catch(error => {
        console.error('Batch update error:', error);
        showToast('Batch update failed: ' + error.message, 'error');
    })
    .finally(() => {
        updateBtn.innerHTML = originalText;
        updateBtn.disabled = false;
    });
}

function showFacultyBatchUpdateResults(data) {
    const resultsModal = document.getElementById('facultyBatchUpdateResultsModal');
    const successCount = document.getElementById('facultySuccessCount');
    const errorCount = document.getElementById('facultyErrorCount');
    const errorStat = document.getElementById('facultyErrorStat');
    const resultsDetails = document.getElementById('facultyResultsDetails');
    
    successCount.textContent = data.data.success_count;
    errorCount.textContent = data.data.error_count;
    
    if (data.data.error_count > 0) {
        errorStat.style.display = 'flex';
    } else {
        errorStat.style.display = 'none';
    }
    
    // Show results details
    let detailsHTML = '';
    
    if (data.data.updated.length > 0) {
        detailsHTML += '<h4>Successfully Updated:</h4><ul>';
        data.data.updated.forEach(faculty => {
            detailsHTML += `<li><strong>${faculty.student_name}</strong> (${faculty.student_id})</li>`;
        });
        detailsHTML += '</ul>';
    }
    
    if (data.data.failed.length > 0) {
        detailsHTML += '<h4>Failed to Update:</h4><ul>';
        data.data.failed.forEach(faculty => {
            detailsHTML += `<li><strong>${faculty.student_name || faculty.student_id}</strong>: ${faculty.error}</li>`;
        });
        detailsHTML += '</ul>';
    }
    
    resultsDetails.innerHTML = detailsHTML;
    resultsModal.style.display = 'flex';
    
    // Show success message
    showToast(data.message, 'success');
}

function closeFacultyBatchUpdateResultsModal() {
    const modal = document.getElementById('facultyBatchUpdateResultsModal');
    modal.style.display = 'none';
}

// Mobile-specific enhancements for Faculty
function addFacultyMobileEnhancements() {
    // Add touch-friendly interactions
    addFacultyTouchInteractions();
    
    // Improve form inputs for mobile
    improveFacultyMobileFormInputs();
    
    // Add swipe gestures for step navigation
    addFacultySwipeGestures();
    
    // Optimize scrolling for mobile
    optimizeFacultyMobileScrolling();
}

function addFacultyTouchInteractions() {
    // Make checkboxes more touch-friendly
    const checkboxes = document.querySelectorAll('#facultyBatchUpdateModal .student-selection-checkbox, #facultyBatchUpdateModal .checkbox-option input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.style.minWidth = '20px';
        checkbox.style.minHeight = '20px';
    });
    
    // Add touch feedback for buttons
    const buttons = document.querySelectorAll('#facultyBatchUpdateModal .btn, #facultyBatchUpdateModal .modal-action-primary, #facultyBatchUpdateModal .modal-action-secondary');
    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        button.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

function improveFacultyMobileFormInputs() {
    // Prevent zoom on input focus for iOS
    const inputs = document.querySelectorAll('#facultyBatchUpdateModal input[type="text"], #facultyBatchUpdateModal input[type="search"], #facultyBatchUpdateModal select');
    inputs.forEach(input => {
        if (input.style.fontSize === '') {
            input.style.fontSize = '16px'; // Prevents zoom on iOS
        }
    });
    
    // Add better focus states for mobile
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = 'var(--medium-muted-blue)';
            this.style.boxShadow = '0 0 0 3px rgba(81, 134, 177, 0.2)';
        });
        
        input.addEventListener('blur', function() {
            this.style.borderColor = '#e1e5e9';
            this.style.boxShadow = 'none';
        });
    });
}

function addFacultySwipeGestures() {
    let startX = 0;
    let startY = 0;
    let endX = 0;
    let endY = 0;
    
    const modal = document.getElementById('facultyBatchUpdateModal');
    
    modal.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    modal.addEventListener('touchend', function(e) {
        endX = e.changedTouches[0].clientX;
        endY = e.changedTouches[0].clientY;
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        // Only handle horizontal swipes
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0 && facultyBatchUpdateData.currentStep > 1) {
                // Swipe left - go to previous step
                goToPreviousFacultyStep();
            } else if (diffX < 0 && facultyBatchUpdateData.currentStep < 3) {
                // Swipe right - go to next step
                nextFacultyBatchUpdateStep();
            }
        }
    });
}

function goToPreviousFacultyStep() {
    if (facultyBatchUpdateData.currentStep === 2) {
        // Go back to step 1
        document.getElementById('facultyStep2').style.display = 'none';
        document.getElementById('facultyStep1').style.display = 'block';
        
        document.querySelector('#facultyStep2 .step-indicator').classList.remove('active');
        document.querySelector('#facultyStep1 .step-indicator').classList.add('active');
        document.querySelector('#facultyStep1 .step-indicator').classList.remove('completed');
        
        facultyBatchUpdateData.currentStep = 1;
        document.getElementById('facultyNextStepBtn').style.display = 'inline-block';
        document.getElementById('facultyUpdateFacultyBtn').style.display = 'none';
        
    } else if (facultyBatchUpdateData.currentStep === 3) {
        // Go back to step 2
        document.getElementById('facultyStep3').style.display = 'none';
        document.getElementById('facultyStep2').style.display = 'block';
        
        document.querySelector('#facultyStep3 .step-indicator').classList.remove('active');
        document.querySelector('#facultyStep2 .step-indicator').classList.add('active');
        document.querySelector('#facultyStep2 .step-indicator').classList.remove('completed');
        
        facultyBatchUpdateData.currentStep = 2;
        document.getElementById('facultyNextStepBtn').style.display = 'inline-block';
        document.getElementById('facultyUpdateFacultyBtn').style.display = 'none';
    }
}

function optimizeFacultyMobileScrolling() {
    // Improve scrolling performance on mobile
    const scrollableElements = document.querySelectorAll('#facultyBatchUpdateModal .students-list-container, #facultyBatchUpdateModal .students-preview-list, #facultyBatchUpdateModal .modal-content-area');
    
    scrollableElements.forEach(element => {
        element.style.webkitOverflowScrolling = 'touch';
        element.style.overflowScrolling = 'touch';
    });
    
    // Add momentum scrolling for iOS
    const modalContent = document.querySelector('.faculty-batch-update-modal-overlay .modal-content-area');
    if (modalContent) {
        modalContent.style.webkitOverflowScrolling = 'touch';
    }
}

// Add keyboard navigation support for Faculty
function addFacultyKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('facultyBatchUpdateModal').style.display === 'flex') {
            if (e.key === 'Escape') {
                closeFacultyBatchUpdateModal();
            } else if (e.key === 'Enter' && e.ctrlKey) {
                if (facultyBatchUpdateData.currentStep < 3) {
                    nextFacultyBatchUpdateStep();
                } else {
                    executeFacultyBatchUpdate();
                }
            }
        }
    });
}

// Initialize keyboard navigation when modal loads
document.addEventListener('DOMContentLoaded', function() {
    addFacultyKeyboardNavigation();
});
</script>
