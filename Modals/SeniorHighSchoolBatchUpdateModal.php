<?php
/**
 * Senior High School Batch Update Modal
 * Dynamic modal for updating multiple senior high school students at once
 * Permissions vary based on user role (Admin vs Program Head)
 */

// Get current user role and permissions
$isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
$isProgramHead = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
$userRole = $isAdmin ? 'admin' : ($isProgramHead ? 'program_head' : 'unknown');
?>

<!-- Mobile viewport meta tag for proper rendering -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<div class="modal-overlay senior-high-batch-update-modal-overlay" id="seniorHighBatchUpdateModal">
    <div class="modal-window senior-high-batch-update-modal">
        <button class="modal-close" onclick="closeSeniorHighBatchUpdateModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-users-cog"></i> 
                Batch Update - Senior High School Students
            </h2>
            <p class="modal-supporting-text">
                Update multiple students' information efficiently. 
                <?php if ($isProgramHead): ?>
                    You can update Year Level and Section for students in your assigned departments.
                <?php else: ?>
                    You can update Year Level, Section, and Program for senior high school students.
                <?php endif; ?>
            </p>
        </div>

        <!-- Modal Content -->
        <div class="modal-content-area">
            <!-- Step 1: Filter & Select Students -->
            <div class="batch-update-step" id="shsStep1">
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
                            <input type="text" id="shsBatchSearchInput" placeholder="Search by name, student ID, or program...">
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="shsBatchYearLevelFilter">Year Level</label>
                                <select id="shsBatchYearLevelFilter" class="filter-select">
                                    <option value="">All Year Levels</option>
                                    <option value="Grade 11">Grade 11</option>
                                    <option value="Grade 12">Grade 12</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="shsBatchSectionFilter">Section</label>
                                <input type="text" id="shsBatchSectionFilter" placeholder="Filter by section...">
                            </div>
                            
                            <?php if ($isAdmin): ?>
                            <div class="filter-group">
                                <label for="shsBatchProgramFilter">Program</label>
                                <select id="shsBatchProgramFilter" class="filter-select">
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
                                <h4>Students Found (<span id="shsStudentsFoundCount">0</span>)</h4>
                                <div class="bulk-selection-controls">
                                    <button class="btn btn-sm btn-outline-primary" onclick="selectAllFilteredSHSStudents()">
                                        <i class="fas fa-check-square"></i> Select All Filtered
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="clearSHSStudentSelection()">
                                        <i class="fas fa-square"></i> Clear Selection
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="students-list-container">
                            <div class="students-list" id="shsBatchStudentsList">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading students...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="selected-students-summary">
                            <span id="shsSelectedStudentsCount">0</span> students selected
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Configure Updates -->
            <div class="batch-update-step" id="shsStep2" style="display: none;">
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
                                    <input type="checkbox" id="shsUpdateYearLevel" onchange="toggleSHSUpdateField('yearLevel')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Year Level</span>
                                </label>
                                <div class="update-field" id="shsYearLevelField" style="display: none;">
                                    <select id="shsNewYearLevel" class="form-control">
                                        <option value="">Select Year Level</option>
                                        <option value="Grade 11">Grade 11</option>
                                        <option value="Grade 12">Grade 12</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="update-option">
                                <label class="checkbox-option">
                                    <input type="checkbox" id="shsUpdateSection" onchange="toggleSHSUpdateField('section')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Section</span>
                                </label>
                                <div class="update-field" id="shsSectionField" style="display: none;">
                                    <input type="text" id="shsNewSection" class="form-control" placeholder="Enter new section...">
                                </div>
                            </div>
                            
                            <?php if ($isAdmin): ?>
                            <div class="update-option">
                                <label class="checkbox-option">
                                    <input type="checkbox" id="shsUpdateProgram" onchange="toggleSHSUpdateField('program')">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">Program</span>
                                </label>
                                <div class="update-field" id="shsProgramField" style="display: none;">
                                    <div class="program-selection">
                                        <label for="shsNewDepartment">Department</label>
                                        <select id="shsNewDepartment" class="form-control" onchange="loadSHSProgramsForDepartment()">
                                            <option value="">Select Department</option>
                                            <!-- Departments will be loaded dynamically -->
                                        </select>
                                        
                                        <label for="shsNewProgram">Program</label>
                                        <select id="shsNewProgram" class="form-control">
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
                            <h4>Selected Students (<span id="shsPreviewStudentsCount">0</span>)</h4>
                            <div class="students-preview-list" id="shsSelectedStudentsPreview">
                                <!-- Selected students will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Preview & Confirm -->
            <div class="batch-update-step" id="shsStep3" style="display: none;">
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
                            The following changes will be applied to <strong id="shsFinalStudentsCount">0</strong> students:
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
                                <tbody id="shsUpdatePreviewTable">
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
            <button class="modal-action-secondary" onclick="closeSeniorHighBatchUpdateModal()">
                Cancel
            </button>
            <button class="modal-action-primary" id="shsNextStepBtn" onclick="nextSHSBatchUpdateStep()">
                Next Step
            </button>
            <button class="modal-action-primary" id="shsUpdateStudentsBtn" onclick="executeSHSBatchUpdate()" style="display: none;">
                <i class="fas fa-save"></i> Update Students
            </button>
        </div>
    </div>
</div>

<!-- Batch Update Results Modal -->
<div class="modal-overlay batch-update-results-modal-overlay" id="shsBatchUpdateResultsModal">
    <div class="modal-window batch-update-results-modal">
        <button class="modal-close" onclick="closeSHSBatchUpdateResultsModal()">
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
                    <span class="result-count" id="shsSuccessCount">0</span>
                    <span class="result-label">Successfully Updated</span>
                </div>
                <div class="result-stat error" id="shsErrorStat" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="result-count" id="shsErrorCount">0</span>
                    <span class="result-label">Failed to Update</span>
                </div>
            </div>
            
            <div class="results-details" id="shsResultsDetails">
                <!-- Results details will be populated here -->
            </div>
        </div>

        <div class="modal-actions">
            <button class="modal-action-primary" onclick="closeSHSBatchUpdateResultsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Senior High School Batch Update Modal JavaScript
let shsBatchUpdateData = {
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
function openSeniorHighBatchUpdateModal() {
    const modal = document.getElementById('seniorHighBatchUpdateModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset modal state
    resetSHSBatchUpdateModal();
    
    // Load initial data
    loadSHSStudentsForBatchUpdate();
    if (shsBatchUpdateData.userRole === 'admin') {
        loadSHSDepartments();
    }
    
    // Add mobile-specific enhancements
    addSHSMobileEnhancements();
}

function closeSeniorHighBatchUpdateModal() {
    const modal = document.getElementById('seniorHighBatchUpdateModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset modal state
    resetSHSBatchUpdateModal();
}

function resetSHSBatchUpdateModal() {
    shsBatchUpdateData.selectedStudents = [];
    shsBatchUpdateData.currentStep = 1;
    shsBatchUpdateData.updateFields = { yearLevel: false, section: false, program: false };
    shsBatchUpdateData.newValues = { yearLevel: '', section: '', program: '', department: '' };
    
    // Reset UI
    document.getElementById('shsStep1').style.display = 'block';
    document.getElementById('shsStep2').style.display = 'none';
    document.getElementById('shsStep3').style.display = 'none';
    
    document.getElementById('shsNextStepBtn').style.display = 'inline-block';
    document.getElementById('shsUpdateStudentsBtn').style.display = 'none';
    
    // Reset step indicators
    document.querySelectorAll('#seniorHighBatchUpdateModal .step-indicator').forEach(step => {
        step.classList.remove('active', 'completed');
    });
    document.querySelector('#shsStep1 .step-indicator').classList.add('active');
}

function nextSHSBatchUpdateStep() {
    if (shsBatchUpdateData.currentStep === 1) {
        if (shsBatchUpdateData.selectedStudents.length === 0) {
            showToast('Please select at least one student to update.', 'warning');
            return;
        }
        
        // Move to step 2
        document.getElementById('shsStep1').style.display = 'none';
        document.getElementById('shsStep2').style.display = 'block';
        
        document.querySelector('#shsStep1 .step-indicator').classList.remove('active');
        document.querySelector('#shsStep1 .step-indicator').classList.add('completed');
        document.querySelector('#shsStep2 .step-indicator').classList.add('active');
        
        shsBatchUpdateData.currentStep = 2;
        updateSHSSelectedStudentsPreview();
        
    } else if (shsBatchUpdateData.currentStep === 2) {
        // Validate that at least one field is selected for update
        const hasUpdates = shsBatchUpdateData.updateFields.yearLevel || 
                          shsBatchUpdateData.updateFields.section || 
                          shsBatchUpdateData.updateFields.program;
        
        if (!hasUpdates) {
            showToast('Please select at least one field to update.', 'warning');
            return;
        }
        
        // Move to step 3
        document.getElementById('shsStep2').style.display = 'none';
        document.getElementById('shsStep3').style.display = 'block';
        
        document.querySelector('#shsStep2 .step-indicator').classList.remove('active');
        document.querySelector('#shsStep2 .step-indicator').classList.add('completed');
        document.querySelector('#shsStep3 .step-indicator').classList.add('active');
        
        shsBatchUpdateData.currentStep = 3;
        generateSHSUpdatePreview();
        
        document.getElementById('shsNextStepBtn').style.display = 'none';
        document.getElementById('shsUpdateStudentsBtn').style.display = 'inline-block';
    }
}

function toggleSHSUpdateField(fieldName) {
    const checkbox = document.getElementById('shsUpdate' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
    const field = document.getElementById('shs' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Field');
    
    if (checkbox.checked) {
        field.style.display = 'block';
        shsBatchUpdateData.updateFields[fieldName] = true;
    } else {
        field.style.display = 'none';
        shsBatchUpdateData.updateFields[fieldName] = false;
    }
}

function loadSHSStudentsForBatchUpdate() {
    const studentsList = document.getElementById('shsBatchStudentsList');
    const studentsFoundCount = document.getElementById('shsStudentsFoundCount');
    
    studentsList.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading students...</span>
        </div>
    `;
    
    // Load students based on user role
    const apiUrl = shsBatchUpdateData.userRole === 'admin' 
        ? '../../api/users/students.php?type=senior_high'
        : '../../api/program-head/senior_high_students.php';
    
    fetch(apiUrl, { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const students = data.students || data.data || [];
                displaySHSStudentsForSelection(students);
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

function displaySHSStudentsForSelection(students) {
    const studentsList = document.getElementById('shsBatchStudentsList');
    
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
                   onchange="toggleSHSStudentSelection(this)">
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

function toggleSHSStudentSelection(checkbox) {
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
        
        shsBatchUpdateData.selectedStudents.push(studentData);
        studentItem.classList.add('selected');
    } else {
        // Remove from selected students
        shsBatchUpdateData.selectedStudents = shsBatchUpdateData.selectedStudents.filter(
            s => s.user_id !== studentId
        );
        studentItem.classList.remove('selected');
    }
    
    updateSHSSelectedStudentsPreview();
    updateSHSStudentsFoundCount();
}

function selectAllFilteredSHSStudents() {
    const checkboxes = document.querySelectorAll('#shsBatchStudentsList .student-selection-checkbox');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            toggleSHSStudentSelection(checkbox);
        }
    });
}

function clearSHSStudentSelection() {
    const checkboxes = document.querySelectorAll('#shsBatchStudentsList .student-selection-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('.student-item').classList.remove('selected');
    });
    
    shsBatchUpdateData.selectedStudents = [];
    updateSHSSelectedStudentsPreview();
    updateSHSStudentsFoundCount();
}

function updateSHSStudentsFoundCount() {
    const selectedCount = document.getElementById('shsSelectedStudentsCount');
    selectedCount.textContent = shsBatchUpdateData.selectedStudents.length;
}

function loadSHSDepartments() {
    if (shsBatchUpdateData.userRole !== 'admin') return;
    
    fetch('../../api/departments/list.php?sector=Senior High School', { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const departmentSelect = document.getElementById('shsNewDepartment');
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

function loadSHSProgramsForDepartment() {
    const departmentId = document.getElementById('shsNewDepartment').value;
    const programSelect = document.getElementById('shsNewProgram');
    
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

function updateSHSSelectedStudentsPreview() {
    const previewContainer = document.getElementById('shsSelectedStudentsPreview');
    const countElement = document.getElementById('shsPreviewStudentsCount');
    
    countElement.textContent = shsBatchUpdateData.selectedStudents.length;
    
    if (shsBatchUpdateData.selectedStudents.length === 0) {
        previewContainer.innerHTML = '<div class="no-selection">No students selected</div>';
        return;
    }
    
    const previewHTML = shsBatchUpdateData.selectedStudents.map(student => `
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

function generateSHSUpdatePreview() {
    const previewTable = document.getElementById('shsUpdatePreviewTable');
    const finalCount = document.getElementById('shsFinalStudentsCount');
    
    finalCount.textContent = shsBatchUpdateData.selectedStudents.length;
    
    const previewRows = shsBatchUpdateData.selectedStudents.map(student => {
        const currentValues = [];
        const newValues = [];
        
        if (shsBatchUpdateData.updateFields.yearLevel) {
            currentValues.push(`Year: ${student.year_level}`);
            newValues.push(`Year: ${shsBatchUpdateData.newValues.yearLevel}`);
        }
        
        if (shsBatchUpdateData.updateFields.section) {
            currentValues.push(`Section: ${student.section}`);
            newValues.push(`Section: ${shsBatchUpdateData.newValues.section}`);
        }
        
        if (shsBatchUpdateData.updateFields.program) {
            currentValues.push(`Program: ${student.program}`);
            newValues.push(`Program: ${shsBatchUpdateData.newValues.program}`);
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

function executeSHSBatchUpdate() {
    // Validate that we have updates to make
    const hasUpdates = shsBatchUpdateData.updateFields.yearLevel || 
                      shsBatchUpdateData.updateFields.section || 
                      shsBatchUpdateData.updateFields.program;
    
    if (!hasUpdates) {
        showToast('Please select at least one field to update.', 'warning');
        return;
    }
    
    // Prepare update data
    const updateData = {
        student_ids: shsBatchUpdateData.selectedStudents.map(s => s.user_id),
        updates: {},
        sector: 'Senior High School'
    };
    
    if (shsBatchUpdateData.updateFields.yearLevel) {
        updateData.updates.year_level = shsBatchUpdateData.newValues.yearLevel;
    }
    
    if (shsBatchUpdateData.updateFields.section) {
        updateData.updates.section = shsBatchUpdateData.newValues.section;
    }
    
    if (shsBatchUpdateData.updateFields.program) {
        updateData.updates.program_id = shsBatchUpdateData.newValues.program;
    }
    
    // Show loading state
    const updateBtn = document.getElementById('shsUpdateStudentsBtn');
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
            showSHSBatchUpdateResults(data);
            closeSeniorHighBatchUpdateModal();
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

function showSHSBatchUpdateResults(data) {
    const resultsModal = document.getElementById('shsBatchUpdateResultsModal');
    const successCount = document.getElementById('shsSuccessCount');
    const errorCount = document.getElementById('shsErrorCount');
    const errorStat = document.getElementById('shsErrorStat');
    const resultsDetails = document.getElementById('shsResultsDetails');
    
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

function closeSHSBatchUpdateResultsModal() {
    const modal = document.getElementById('shsBatchUpdateResultsModal');
    modal.style.display = 'none';
}

// Mobile-specific enhancements for Senior High School
function addSHSMobileEnhancements() {
    // Add touch-friendly interactions
    addSHSTouchInteractions();
    
    // Improve form inputs for mobile
    improveSHSMobileFormInputs();
    
    // Add swipe gestures for step navigation
    addSHSSwipeGestures();
    
    // Optimize scrolling for mobile
    optimizeSHSMobileScrolling();
}

function addSHSTouchInteractions() {
    // Make checkboxes more touch-friendly
    const checkboxes = document.querySelectorAll('#seniorHighBatchUpdateModal .student-selection-checkbox, #seniorHighBatchUpdateModal .checkbox-option input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.style.minWidth = '20px';
        checkbox.style.minHeight = '20px';
    });
    
    // Add touch feedback for buttons
    const buttons = document.querySelectorAll('#seniorHighBatchUpdateModal .btn, #seniorHighBatchUpdateModal .modal-action-primary, #seniorHighBatchUpdateModal .modal-action-secondary');
    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        button.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

function improveSHSMobileFormInputs() {
    // Prevent zoom on input focus for iOS
    const inputs = document.querySelectorAll('#seniorHighBatchUpdateModal input[type="text"], #seniorHighBatchUpdateModal input[type="search"], #seniorHighBatchUpdateModal select');
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

function addSHSSwipeGestures() {
    let startX = 0;
    let startY = 0;
    let endX = 0;
    let endY = 0;
    
    const modal = document.getElementById('seniorHighBatchUpdateModal');
    
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
            if (diffX > 0 && shsBatchUpdateData.currentStep > 1) {
                // Swipe left - go to previous step
                goToPreviousSHSStep();
            } else if (diffX < 0 && shsBatchUpdateData.currentStep < 3) {
                // Swipe right - go to next step
                nextSHSBatchUpdateStep();
            }
        }
    });
}

function goToPreviousSHSStep() {
    if (shsBatchUpdateData.currentStep === 2) {
        // Go back to step 1
        document.getElementById('shsStep2').style.display = 'none';
        document.getElementById('shsStep1').style.display = 'block';
        
        document.querySelector('#shsStep2 .step-indicator').classList.remove('active');
        document.querySelector('#shsStep1 .step-indicator').classList.add('active');
        document.querySelector('#shsStep1 .step-indicator').classList.remove('completed');
        
        shsBatchUpdateData.currentStep = 1;
        document.getElementById('shsNextStepBtn').style.display = 'inline-block';
        document.getElementById('shsUpdateStudentsBtn').style.display = 'none';
        
    } else if (shsBatchUpdateData.currentStep === 3) {
        // Go back to step 2
        document.getElementById('shsStep3').style.display = 'none';
        document.getElementById('shsStep2').style.display = 'block';
        
        document.querySelector('#shsStep3 .step-indicator').classList.remove('active');
        document.querySelector('#shsStep2 .step-indicator').classList.add('active');
        document.querySelector('#shsStep2 .step-indicator').classList.remove('completed');
        
        shsBatchUpdateData.currentStep = 2;
        document.getElementById('shsNextStepBtn').style.display = 'inline-block';
        document.getElementById('shsUpdateStudentsBtn').style.display = 'none';
    }
}

function optimizeSHSMobileScrolling() {
    // Improve scrolling performance on mobile
    const scrollableElements = document.querySelectorAll('#seniorHighBatchUpdateModal .students-list-container, #seniorHighBatchUpdateModal .students-preview-list, #seniorHighBatchUpdateModal .modal-content-area');
    
    scrollableElements.forEach(element => {
        element.style.webkitOverflowScrolling = 'touch';
        element.style.overflowScrolling = 'touch';
    });
    
    // Add momentum scrolling for iOS
    const modalContent = document.querySelector('.senior-high-batch-update-modal-overlay .modal-content-area');
    if (modalContent) {
        modalContent.style.webkitOverflowScrolling = 'touch';
    }
}

// Add keyboard navigation support for Senior High School
function addSHSKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('seniorHighBatchUpdateModal').style.display === 'flex') {
            if (e.key === 'Escape') {
                closeSeniorHighBatchUpdateModal();
            } else if (e.key === 'Enter' && e.ctrlKey) {
                if (shsBatchUpdateData.currentStep < 3) {
                    nextSHSBatchUpdateStep();
                } else {
                    executeSHSBatchUpdate();
                }
            }
        }
    });
}

// Initialize keyboard navigation when modal loads
document.addEventListener('DOMContentLoaded', function() {
    addSHSKeyboardNavigation();
});
</script>
