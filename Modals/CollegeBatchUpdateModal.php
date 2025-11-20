<?php
/**
 * College Batch Update Modal
 * Dynamic modal for updating multiple college students at once
 * Permissions vary based on user role (Admin vs Program Head)
 */

// Get current user role and permissions
$isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
$isProgramHead = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
$userRole = $isAdmin ? 'admin' : ($isProgramHead ? 'program_head' : 'unknown');
?>

<!-- Mobile viewport meta tag for proper rendering -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<div class="modal-overlay college-batch-update-modal-overlay" id="collegeBatchUpdateModal">
    <div class="modal-window college-batch-update-modal">
        <button class="modal-close" onclick="closeCollegeBatchUpdateModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-users-cog"></i> 
                Batch Update - College Students
            </h2>
            <p class="modal-supporting-text">
                Update multiple students' information efficiently. 
                <?php if ($isProgramHead): ?>
                    You can update Year Level and Section for students in your assigned departments.
                <?php else: ?>
                    You can update Year Level, Section, and Program for college students.
                <?php endif; ?>
            </p>
        </div>

        <!-- Modal Content -->
        <div class="modal-content-area">
            <!-- Step 1: Filter & Select Students -->
            <div class="batch-update-step" id="step1">
                <div class="step-header">
                    <div class="step-indicator active">
                        <span class="step-number">1</span>
                        <span class="step-title">Filter & Select Students</span>
                    </div>
                </div>

                <div class="step-content">
                    <!-- Search and Filters -->
                    <div class="search-filters-section">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="batchSearchInput" placeholder="Search by name, student ID, or program...">
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="batchYearLevelFilter">Year Level</label>
                                <select id="batchYearLevelFilter" class="filter-select">
                                    <option value="">All Year Levels</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="batchSectionFilter">Section</label>
                                <input type="text" id="batchSectionFilter" placeholder="Filter by section...">
                            </div>
                            
                            <?php if ($isAdmin): ?>
                            <div class="filter-group">
                                <label for="batchProgramFilter">Program</label>
                                <select id="batchProgramFilter" class="filter-select">
                                    <option value="">All Programs</option>
                                    <!-- Programs will be loaded dynamically -->
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Students List -->
                    <div class="students-selection-section">
                        <div class="selection-header">
                            <div class="selection-info">
                                <h4>Students Found (<span id="studentsFoundCount">0</span>)</h4>
                                <div class="bulk-selection-controls">
                                    <button class="btn btn-sm btn-outline-primary" onclick="selectAllFilteredStudents()">
                                        <i class="fas fa-check-square"></i> Select All Filtered
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="clearStudentSelection()">
                                        <i class="fas fa-square"></i> Clear Selection
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="students-list-container">
                            <div class="students-list" id="batchStudentsList">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading students...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="selected-students-summary">
                            <span id="selectedStudentsCount">0</span> students selected
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Configure Updates -->
            <div class="batch-update-step" id="step2" style="display: none;">
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
                                    <input type="checkbox" id="updateYearLevel" onchange="toggleUpdateField('yearLevel')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Year Level</span>
                                </label>
                                <div class="update-field" id="yearLevelField" style="display: none;">
                                    <select id="newYearLevel" class="form-control">
                                        <option value="">Select Year Level</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="update-option">
                                <label class="checkbox-option">
                                    <input type="checkbox" id="updateSection" onchange="toggleUpdateField('section')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Section</span>
                                </label>
                                <div class="update-field" id="sectionField" style="display: none;">
                                    <input type="text" id="newSection" class="form-control" placeholder="Enter new section...">
                                </div>
                            </div>
                            
                            <?php if ($isAdmin): ?>
                            <div class="update-option">
                                <label class="checkbox-option">
                                    <input type="checkbox" id="updateProgram" onchange="toggleUpdateField('program')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Program</span>
                                </label>
                                <div class="update-field" id="programField" style="display: none;">
                                    <div class="program-selection">
                                        <label for="newDepartment">Department</label>
                                        <select id="newDepartment" class="form-control" onchange="loadProgramsForDepartment()">
                                            <option value="">Select Department</option>
                                            <!-- Departments will be loaded dynamically -->
                                        </select>
                                        
                                        <label for="newProgram">Program</label>
                                        <select id="newProgram" class="form-control">
                                            <option value="">Select Program</option>
                                            <!-- Programs will be loaded dynamically -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Selected Students Preview -->
                        <div class="selected-students-preview">
                            <h4>Selected Students (<span id="previewStudentsCount">0</span>)</h4>
                            <div class="students-preview-list" id="selectedStudentsPreview">
                                <!-- Selected students will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Preview & Confirm -->
            <div class="batch-update-step" id="step3" style="display: none;">
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
                            The following changes will be applied to <strong id="finalStudentsCount">0</strong> students:
                        </p>
                        
                        <div class="preview-table-container">
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Current Values</th>
                                        <th>New Values</th>
                                    </tr>
                                </thead>
                                <tbody id="updatePreviewTable">
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
            <button class="modal-action-secondary" onclick="closeCollegeBatchUpdateModal()">
                Cancel
            </button>
            <button class="modal-action-primary" id="nextStepBtn" onclick="nextBatchUpdateStep()">
                Next Step
            </button>
            <button class="modal-action-primary" id="updateStudentsBtn" onclick="executeBatchUpdate()" style="display: none;">
                <i class="fas fa-save"></i> Update Students
            </button>
        </div>
    </div>
</div>

<!-- Batch Update Results Modal -->
<div class="modal-overlay batch-update-results-modal-overlay" id="batchUpdateResultsModal">
    <div class="modal-window batch-update-results-modal">
        <button class="modal-close" onclick="closeBatchUpdateResultsModal()">
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
                    <span class="result-count" id="successCount">0</span>
                    <span class="result-label">Successfully Updated</span>
                </div>
                <div class="result-stat error" id="errorStat" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="result-count" id="errorCount">0</span>
                    <span class="result-label">Failed to Update</span>
                </div>
            </div>
            
            <div class="results-details" id="resultsDetails">
                <!-- Results details will be populated here -->
            </div>
        </div>

        <div class="modal-actions">
            <button class="modal-action-primary" onclick="closeBatchUpdateResultsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Batch Update Modal JavaScript
let batchUpdateData = {
    selectedStudents: [],
    currentStep: 1,
    updateFields: {
        yearLevel: false,
        section: false,
        program: false
    },
    newValues: {
        yearLevel: '',
        section: '',
        program: '',
        department: ''
    },
    userRole: '<?php echo $userRole; ?>'
};

// Initialize modal
function openCollegeBatchUpdateModal() {
    const modal = document.getElementById('collegeBatchUpdateModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset modal state
    resetBatchUpdateModal();
    
    // Load initial data
    loadStudentsForBatchUpdate();
    if (batchUpdateData.userRole === 'admin') {
        loadDepartments();
    }
    
    // Add mobile-specific enhancements
    addMobileEnhancements();
}

function closeCollegeBatchUpdateModal() {
    const modal = document.getElementById('collegeBatchUpdateModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset modal state
    resetBatchUpdateModal();
}

function resetBatchUpdateModal() {
    batchUpdateData.selectedStudents = [];
    batchUpdateData.currentStep = 1;
    batchUpdateData.updateFields = { yearLevel: false, section: false, program: false };
    batchUpdateData.newValues = { yearLevel: '', section: '', program: '', department: '' };
    
    // Reset UI
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'none';
    
    document.getElementById('nextStepBtn').style.display = 'inline-block';
    document.getElementById('updateStudentsBtn').style.display = 'none';
    
    // Reset step indicators
    document.querySelectorAll('.step-indicator').forEach(step => {
        step.classList.remove('active', 'completed');
    });
    document.querySelector('#step1 .step-indicator').classList.add('active');
}

function nextBatchUpdateStep() {
    if (batchUpdateData.currentStep === 1) {
        if (batchUpdateData.selectedStudents.length === 0) {
            showToast('Please select at least one student to update.', 'warning');
            return;
        }
        
        // Move to step 2
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        
        document.querySelector('#step1 .step-indicator').classList.remove('active');
        document.querySelector('#step1 .step-indicator').classList.add('completed');
        document.querySelector('#step2 .step-indicator').classList.add('active');
        
        batchUpdateData.currentStep = 2;
        updateSelectedStudentsPreview();
        
    } else if (batchUpdateData.currentStep === 2) {
        // Validate that at least one field is selected for update
        const hasUpdates = batchUpdateData.updateFields.yearLevel || 
                          batchUpdateData.updateFields.section || 
                          batchUpdateData.updateFields.program;
        
        if (!hasUpdates) {
            showToast('Please select at least one field to update.', 'warning');
            return;
        }
        
        // Move to step 3
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';
        
        document.querySelector('#step2 .step-indicator').classList.remove('active');
        document.querySelector('#step2 .step-indicator').classList.add('completed');
        document.querySelector('#step3 .step-indicator').classList.add('active');
        
        batchUpdateData.currentStep = 3;
        generateUpdatePreview();
        
        document.getElementById('nextStepBtn').style.display = 'none';
        document.getElementById('updateStudentsBtn').style.display = 'inline-block';
    }
}

function toggleUpdateField(fieldName) {
    const checkbox = document.getElementById('update' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
    const field = document.getElementById(fieldName + 'Field');
    
    if (checkbox.checked) {
        field.style.display = 'block';
        batchUpdateData.updateFields[fieldName] = true;
    } else {
        field.style.display = 'none';
        batchUpdateData.updateFields[fieldName] = false;
    }
}

function loadStudentsForBatchUpdate() {
    const studentsList = document.getElementById('batchStudentsList');
    const studentsFoundCount = document.getElementById('studentsFoundCount');
    
    studentsList.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading students...</span>
        </div>
    `;
    
    // Load students based on user role
    const apiUrl = batchUpdateData.userRole === 'admin' 
        ? '../../api/users/students.php?type=college'
        : '../../api/program-head/college_students.php';
    
    fetch(apiUrl, { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const students = data.students || data.data || [];
                displayStudentsForSelection(students);
                studentsFoundCount.textContent = students.length;
            } else {
                throw new Error(data.message || 'Failed to load students');
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
            studentsList.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Error loading students: ${error.message}</span>
                </div>
            `;
        });
}

function displayStudentsForSelection(students) {
    const studentsList = document.getElementById('batchStudentsList');
    
    if (students.length === 0) {
        studentsList.innerHTML = `
            <div class="no-students">
                <i class="fas fa-users"></i>
                <span>No students found</span>
            </div>
        `;
        return;
    }
    
    const studentsHTML = students.map(student => `
        <div class="student-item" data-student-id="${student.user_id}">
            <input type="checkbox" class="student-selection-checkbox" 
                   data-student-id="${student.user_id}"
                   onchange="toggleStudentSelection(this)">
            <div class="student-info">
                <div class="student-name">${student.last_name}, ${student.first_name} ${student.middle_name || ''}</div>
                <div class="student-details">
                    <span class="student-id">${student.student_id || student.username}</span>
                    <span>•</span>
                    <span>${student.program || 'N/A'}</span>
                    <span>•</span>
                    <span>${student.year_level || 'N/A'}</span>
                    <span>•</span>
                    <span>${student.section || 'N/A'}</span>
                </div>
            </div>
        </div>
    `).join('');
    
    studentsList.innerHTML = studentsHTML;
}

function toggleStudentSelection(checkbox) {
    const studentId = checkbox.dataset.studentId;
    const studentItem = checkbox.closest('.student-item');
    const studentName = studentItem.querySelector('.student-name').textContent;
    const studentDetails = studentItem.querySelector('.student-details').textContent;
    
    if (checkbox.checked) {
        // Add to selected students
        const studentData = {
            user_id: studentId,
            name: studentName,
            student_id: studentDetails.split('•')[0].trim(),
            program: studentDetails.split('•')[1]?.trim() || 'N/A',
            year_level: studentDetails.split('•')[2]?.trim() || 'N/A',
            section: studentDetails.split('•')[3]?.trim() || 'N/A'
        };
        
        batchUpdateData.selectedStudents.push(studentData);
        studentItem.classList.add('selected');
    } else {
        // Remove from selected students
        batchUpdateData.selectedStudents = batchUpdateData.selectedStudents.filter(
            s => s.user_id !== studentId
        );
        studentItem.classList.remove('selected');
    }
    
    updateSelectedStudentsPreview();
    updateStudentsFoundCount();
}

function selectAllFilteredStudents() {
    const checkboxes = document.querySelectorAll('.student-selection-checkbox');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            toggleStudentSelection(checkbox);
        }
    });
}

function clearStudentSelection() {
    const checkboxes = document.querySelectorAll('.student-selection-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('.student-item').classList.remove('selected');
    });
    
    batchUpdateData.selectedStudents = [];
    updateSelectedStudentsPreview();
    updateStudentsFoundCount();
}

function updateStudentsFoundCount() {
    const selectedCount = document.getElementById('selectedStudentsCount');
    selectedCount.textContent = batchUpdateData.selectedStudents.length;
}

function loadDepartments() {
    if (batchUpdateData.userRole !== 'admin') return;
    
    fetch('../../api/departments/list.php?sector=College', { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const departmentSelect = document.getElementById('newDepartment');
                departmentSelect.innerHTML = '<option value="">Select Department</option>';
                
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.department_id;
                    option.textContent = dept.department_name;
                    departmentSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading departments:', error);
        });
}

function loadProgramsForDepartment() {
    const departmentId = document.getElementById('newDepartment').value;
    const programSelect = document.getElementById('newProgram');
    
    if (!departmentId) {
        programSelect.innerHTML = '<option value="">Select Program</option>';
        return;
    }
    
    fetch(`../../api/programs/list.php?department_id=${departmentId}`, { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                programSelect.innerHTML = '<option value="">Select Program</option>';
                data.programs.forEach(program => {
                    const option = document.createElement('option');
                    option.value = program.program_id;
                    option.textContent = program.program_name;
                    programSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading programs:', error);
        });
}

function updateSelectedStudentsPreview() {
    const previewContainer = document.getElementById('selectedStudentsPreview');
    const countElement = document.getElementById('previewStudentsCount');
    
    countElement.textContent = batchUpdateData.selectedStudents.length;
    
    if (batchUpdateData.selectedStudents.length === 0) {
        previewContainer.innerHTML = '<div class="no-selection">No students selected</div>';
        return;
    }
    
    const previewHTML = batchUpdateData.selectedStudents.map(student => `
        <div class="student-preview-item">
            <div class="student-info">
                <strong>${student.name}</strong>
                <span class="student-id">${student.student_id}</span>
            </div>
            <div class="student-details">
                ${student.program} • ${student.year_level} • ${student.section}
            </div>
        </div>
    `).join('');
    
    previewContainer.innerHTML = previewHTML;
}

function generateUpdatePreview() {
    const previewTable = document.getElementById('updatePreviewTable');
    const finalCount = document.getElementById('finalStudentsCount');
    
    finalCount.textContent = batchUpdateData.selectedStudents.length;
    
    const previewRows = batchUpdateData.selectedStudents.map(student => {
        const currentValues = [];
        const newValues = [];
        
        if (batchUpdateData.updateFields.yearLevel) {
            currentValues.push(`Year: ${student.year_level}`);
            newValues.push(`Year: ${batchUpdateData.newValues.yearLevel}`);
        }
        
        if (batchUpdateData.updateFields.section) {
            currentValues.push(`Section: ${student.section}`);
            newValues.push(`Section: ${batchUpdateData.newValues.section}`);
        }
        
        if (batchUpdateData.updateFields.program) {
            currentValues.push(`Program: ${student.program}`);
            newValues.push(`Program: ${batchUpdateData.newValues.program}`);
        }
        
        return `
            <tr>
                <td>
                    <strong>${student.name}</strong><br>
                    <small>${student.student_id}</small>
                </td>
                <td>${currentValues.join('<br>')}</td>
                <td>${newValues.join('<br>')}</td>
            </tr>
        `;
    }).join('');
    
    previewTable.innerHTML = previewRows;
}

function executeBatchUpdate() {
    // Validate that we have updates to make
    const hasUpdates = batchUpdateData.updateFields.yearLevel || 
                      batchUpdateData.updateFields.section || 
                      batchUpdateData.updateFields.program;
    
    if (!hasUpdates) {
        showToast('Please select at least one field to update.', 'warning');
        return;
    }
    
    // Prepare update data
    const updateData = {
        student_ids: batchUpdateData.selectedStudents.map(s => s.user_id),
        updates: {},
        sector: 'College'
    };
    
    if (batchUpdateData.updateFields.yearLevel) {
        updateData.updates.year_level = batchUpdateData.newValues.yearLevel;
    }
    
    if (batchUpdateData.updateFields.section) {
        updateData.updates.section = batchUpdateData.newValues.section;
    }
    
    if (batchUpdateData.updateFields.program) {
        updateData.updates.program_id = batchUpdateData.newValues.program;
    }
    
    // Show loading state
    const updateBtn = document.getElementById('updateStudentsBtn');
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
            showBatchUpdateResults(data);
            closeCollegeBatchUpdateModal();
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

function showBatchUpdateResults(data) {
    const resultsModal = document.getElementById('batchUpdateResultsModal');
    const successCount = document.getElementById('successCount');
    const errorCount = document.getElementById('errorCount');
    const errorStat = document.getElementById('errorStat');
    const resultsDetails = document.getElementById('resultsDetails');
    
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
        data.data.updated.forEach(student => {
            detailsHTML += `<li><strong>${student.student_name}</strong> (${student.student_id})</li>`;
        });
        detailsHTML += '</ul>';
    }
    
    if (data.data.failed.length > 0) {
        detailsHTML += '<h4>Failed to Update:</h4><ul>';
        data.data.failed.forEach(student => {
            detailsHTML += `<li><strong>${student.student_name || student.student_id}</strong>: ${student.error}</li>`;
        });
        detailsHTML += '</ul>';
    }
    
    resultsDetails.innerHTML = detailsHTML;
    resultsModal.style.display = 'flex';
    
    // Show success message
    showToast(data.message, 'success');
}

function closeBatchUpdateResultsModal() {
    const modal = document.getElementById('batchUpdateResultsModal');
    modal.style.display = 'none';
}

// Mobile-specific enhancements
function addMobileEnhancements() {
    // Add touch-friendly interactions
    addTouchInteractions();
    
    // Improve form inputs for mobile
    improveMobileFormInputs();
    
    // Add swipe gestures for step navigation
    addSwipeGestures();
    
    // Optimize scrolling for mobile
    optimizeMobileScrolling();
}

function addTouchInteractions() {
    // Make checkboxes more touch-friendly
    const checkboxes = document.querySelectorAll('.student-selection-checkbox, .checkbox-option input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.style.minWidth = '20px';
        checkbox.style.minHeight = '20px';
    });
    
    // Add touch feedback for buttons
    const buttons = document.querySelectorAll('.btn, .modal-action-primary, .modal-action-secondary');
    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        button.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

function improveMobileFormInputs() {
    // Prevent zoom on input focus for iOS
    const inputs = document.querySelectorAll('input[type="text"], input[type="search"], select');
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

function addSwipeGestures() {
    let startX = 0;
    let startY = 0;
    let endX = 0;
    let endY = 0;
    
    const modal = document.getElementById('collegeBatchUpdateModal');
    
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
            if (diffX > 0 && batchUpdateData.currentStep > 1) {
                // Swipe left - go to previous step
                goToPreviousStep();
            } else if (diffX < 0 && batchUpdateData.currentStep < 3) {
                // Swipe right - go to next step
                nextBatchUpdateStep();
            }
        }
    });
}

function goToPreviousStep() {
    if (batchUpdateData.currentStep === 2) {
        // Go back to step 1
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = 'block';
        
        document.querySelector('#step2 .step-indicator').classList.remove('active');
        document.querySelector('#step1 .step-indicator').classList.add('active');
        document.querySelector('#step1 .step-indicator').classList.remove('completed');
        
        batchUpdateData.currentStep = 1;
        document.getElementById('nextStepBtn').style.display = 'inline-block';
        document.getElementById('updateStudentsBtn').style.display = 'none';
        
    } else if (batchUpdateData.currentStep === 3) {
        // Go back to step 2
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        
        document.querySelector('#step3 .step-indicator').classList.remove('active');
        document.querySelector('#step2 .step-indicator').classList.add('active');
        document.querySelector('#step2 .step-indicator').classList.remove('completed');
        
        batchUpdateData.currentStep = 2;
        document.getElementById('nextStepBtn').style.display = 'inline-block';
        document.getElementById('updateStudentsBtn').style.display = 'none';
    }
}

function optimizeMobileScrolling() {
    // Improve scrolling performance on mobile
    const scrollableElements = document.querySelectorAll('.students-list-container, .students-preview-list, .modal-content-area');
    
    scrollableElements.forEach(element => {
        element.style.webkitOverflowScrolling = 'touch';
        element.style.overflowScrolling = 'touch';
    });
    
    // Add momentum scrolling for iOS
    const modalContent = document.querySelector('.college-batch-update-modal-overlay .modal-content-area');
    if (modalContent) {
        modalContent.style.webkitOverflowScrolling = 'touch';
    }
}

// Add keyboard navigation support
function addKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('collegeBatchUpdateModal').style.display === 'flex') {
            if (e.key === 'Escape') {
                closeCollegeBatchUpdateModal();
            } else if (e.key === 'Enter' && e.ctrlKey) {
                if (batchUpdateData.currentStep < 3) {
                    nextBatchUpdateStep();
                } else {
                    executeBatchUpdate();
                }
            }
        }
    });
}

// Initialize keyboard navigation when modal loads
document.addEventListener('DOMContentLoaded', function() {
    addKeyboardNavigation();
});
</script>
