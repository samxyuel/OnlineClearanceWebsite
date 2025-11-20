<?php
// Edit Staff Modal
?>

<div class="modal-overlay edit-staff-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeEditStaffModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Edit Staff Member</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Update staff member information. Employee ID cannot be changed.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="editStaffForm" data-endpoint="../../controllers/updateStaff.php">
                <input type="hidden" name="type" value="staff">
                
                <div class="form-group">
                    <label for="editEmployeeId">Employee ID</label>
                    <input type="text" id="editEmployeeId" name="employeeId" readonly 
                           style="background-color: #f8f9fa; color: #6c757d;"
                           pattern="LCA[0-9]{4}[A-Z]"
                           title="Format: LCA + 4 digits + 1 letter (e.g., LCA1234P)">
                    <small class="form-help">Employee ID cannot be modified</small>
                </div>
                
                <div class="form-group">
                    <label for="editLastName">Last Name</label>
                    <input type="text" id="editLastName" name="lastName" placeholder="Doe" required>
                </div>
                <div class="form-group">
                    <label for="editFirstName">First Name</label>
                    <input type="text" id="editFirstName" name="firstName" placeholder="John" required>
                </div>
                <div class="form-group">
                    <label for="editMiddleName">Middle Name (Optional)</label>
                    <input type="text" id="editMiddleName" name="middleName" placeholder="A.">
                </div>
                
                <div class="form-group">
                    <label for="editStaffPosition">Position</label>
                    <select id="editStaffPosition" name="staffPosition">
                        <option value="">Select Standard Position (or leave blank for custom)</option>
                        <optgroup label="Standard Positions">
                            <option value="Guidance">Guidance</option>
                            <option value="Disciplinary Officer">Disciplinary Officer</option>
                            <option value="Clinic">Clinic</option>
                            <option value="Librarian">Librarian</option>
                            <option value="Alumni Placement Officer">Alumni Placement Officer</option>
                            <option value="Student's Affairs Officer">Student's Affairs Officer</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Program Head">Program Head</option>
                            <option value="PAMO">PAMO</option>
                            <option value="MIS/IT">MIS/IT</option>
                            <option value="Petty Cash Custodian">Petty Cash Custodian</option>
                            <option value="Building Administrator">Building Administrator</option>
                            <option value="Accountant">Accountant</option>
                            <option value="Academic Head">Academic Head</option>
                            <option value="School Administrator">School Administrator</option>
                            <option value="HR">HR</option>
                        </optgroup>
                    </select>
                    <div class="custom-position-container">
                        <label for="editCustomPosition">Custom Position</label>
                        <input type="text" id="editCustomPosition" name="editCustomPosition" 
                               placeholder="Type custom position if not in standard list above...">
                        <small class="form-help">Only fill this if you didn't select a standard position above</small>

                                    <!-- Additional Designations Section -->
                                    <div class="form-group">
                                        <label>Additional Designations (Optional)</label>
                                        <small class="form-help">Add secondary designations for this staff member</small>
                                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
                                                <select id="editAdditionalDesignationSelect" style="flex: 1; padding:6px;">
                                                    <option value="">Select a designation...</option>
                                                </select>
                                                <button type="button" id="editAddDesignationBtn" class="btn btn-sm btn-outline-primary" onclick="addEditAdditionalDesignation()">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                        <div id="editDesignationsList" style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                                            <!-- Additional designations will appear as chips here -->
                                        </div>
                                    </div>
                    </div>
                </div>
                
                <!-- Department field removed - only Program Heads get department assignments -->
                
                <!-- Program Head Assignment Section (Hidden by default) -->
                <div id="editProgramHeadAssignmentSection" class="program-head-assignment-section" style="display: none;">
                    <!-- Current Assignments Display -->
                    <div id="editCurrentAssignmentsContainer" class="current-assignments-container" style="display: none;">
                        <h4 class="current-assignments-title">
                            <i class="fas fa-list-check"></i> Current Department Assignments
                        </h4>
                        <div id="editCurrentAssignmentsList" class="current-assignments-list">
                            <!-- Current assignments will be populated here -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editProgramHeadCategory">Program Head Assignment <span class="required-asterisk">*</span></label>
                        <select id="editProgramHeadCategory" name="programHeadCategory" onchange="updateEditDepartmentCheckboxes()">
                            <option value="">Select Category</option>
                            <option value="College">College</option>
                            <option value="Senior High School">Senior High School</option>
                            <option value="Faculty">Faculty</option>
                        </select>
                        <small class="form-help">Select the category this Program Head will manage</small>
                    </div>
                    
                    <div id="editDepartmentCheckboxesContainer" class="department-checkboxes-container" style="display: none;">
                        <label class="checkbox-section-label">Available Departments <span class="required-asterisk">*</span></label>
                        <small class="form-help">Select at least one department for Program Head assignment</small>
                        <div id="editDepartmentCheckboxesList" class="checkbox-group">
                            <!-- Checkboxes will be populated dynamically -->
                        </div>
                        <div class="form-group" style="margin-top:10px;">
                            <label class="checkbox-label" style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" id="editPhTransferToggle" checked>
                                <span>Transfer existing Program Head if a department is already assigned</span>
                            </label>
                            <small class="form-help">When checked, assigning will replace the current Program Head for occupied departments.</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="editStaffEmail">Email Address</label>
                    <input type="email" id="editStaffEmail" name="staffEmail" 
                           placeholder="john.smith@gosti.edu.ph" required>
                </div>
                
                <div class="form-group">
                    <label for="editStaffContact">Contact Number</label>
                    <input type="tel" id="editStaffContact" name="staffContact" 
                           placeholder="+63 912 345 6789">
                </div>
                
                <!-- Faculty Section Divider -->
                <div class="form-section-divider">
                    <hr>
                    <span class="divider-text">Faculty Registration (Optional)</span>
                </div>
                
                <!-- Is also a faculty checkbox -->
                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" id="editIsAlsoFaculty" name="isAlsoFaculty" onchange="toggleEditFacultySection()">
                        <label for="editIsAlsoFaculty" class="checkbox-label">Is also a faculty</label>
                    </div>
                    <small class="form-help">Check this if the staff member should also have faculty access</small>
                </div>
                
                <!-- Faculty Fields Section (Hidden by default) -->
                <div id="editFacultyFieldsSection" class="faculty-fields-section" style="display: none;">
                    <div class="form-group">
                        <label for="editFacultyEmploymentStatus">Faculty Employment Status <span class="required-asterisk">*</span></label>
                        <select id="editFacultyEmploymentStatus" name="facultyEmploymentStatus">
                            <option value="">Select Employment Status</option>
                            <option value="Part Time - Full Load">Part Time - Full Load</option>
                            <option value="Part Time">Part Time</option>
                            <option value="Full Time">Full Time</option>
                        </select>
                        <small class="form-help">Required when "Is also a faculty" is checked</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFacultyEmployeeNumber">Employee Number</label>
                        <input type="text" id="editFacultyEmployeeNumber" name="facultyEmployeeNumber" readonly 
                               style="background-color: #f8f9fa; color: #6c757d;">
                        <small class="form-help">Auto-filled from Employee ID (read-only)</small>
                    </div>
                </div>

                <!-- Password Management Section -->
                <div class="form-section-divider">
                    <hr>
                    <span class="divider-text">Password Management</span>
                </div>
                <div class="form-group">
                    <label>Password Actions</label>
                    <button type="button" class="btn btn-outline-warning" onclick="handlePasswordReset()">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                    <small class="form-help">This will generate a new secure password for the user. The new password will be displayed for you to copy.</small>
                </div>
            </form>
        </div>
        
        <!-- Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeEditStaffModal()">Cancel</button>
            <button class="modal-action-primary" onclick="submitEditStaffForm()">Update Staff</button>
        </div>
    </div>
</div>

<script>
// Make functions globally accessible
window.closeEditStaffModal = function() {
    console.log('[EditStaffModal] closeEditStaffModal() called');
    try {
        const modal = document.querySelector('.edit-staff-modal-overlay');
        if (!modal) {
            console.warn('[EditStaffModal] Modal not found');
            return;
        }
        console.log('[EditStaffModal] Closing modal');

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal(modal);
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            modal.classList.remove('active');
        }
        // Reset form
        document.getElementById('editStaffForm').reset();
        // Clear current assignments data
        window.currentAssignments = null;
        // Clear department checkboxes
        const checkboxesContainer = document.getElementById('editDepartmentCheckboxesContainer');
        const checkboxesList = document.getElementById('editDepartmentCheckboxesList');
        if (checkboxesContainer) checkboxesContainer.style.display = 'none';
        if (checkboxesList) checkboxesList.innerHTML = '';
        
        // Remove any signatory warnings
        const existingWarning = document.querySelector('.signatory-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
    } catch (error) {
        console.error('[EditStaffModal] Error closing modal:', error);
    }
};

// Also reset additional designations when closing
const originalCloseFunc = window.closeEditStaffModal;
window.closeEditStaffModal = function() {
    window.editAdditionalDesignations = [];
    const editDesignationsList = document.getElementById('editDesignationsList');
    if (editDesignationsList) editDesignationsList.innerHTML = '';
    const sel = document.getElementById('editAdditionalDesignationSelect');
    if (sel) sel.value = '';
    return originalCloseFunc();
};
// Function to populate edit form with existing data
window.populateEditStaffForm = function(staffData) {
    // Populate basic fields
    document.getElementById('editEmployeeId').value = staffData.employee_number || staffData.employeeId || '';
    document.getElementById('editLastName').value = staffData.last_name || '';
    document.getElementById('editFirstName').value = staffData.first_name || '';
    document.getElementById('editMiddleName').value = staffData.middle_name || '';
    document.getElementById('editStaffEmail').value = staffData.email || '';
    document.getElementById('editStaffContact').value = staffData.contact_number || '';
    
    // Handle position/designation
    const positionSelect = document.getElementById('editStaffPosition');
    const customPositionInput = document.getElementById('editCustomPosition');
    const designation = staffData.designation || staffData.staff_position || '';
    
    // Check if it's a standard position
    const standardPositions = Array.from(positionSelect.options).map(opt => opt.value);
    if (standardPositions.includes(designation)) {
        positionSelect.value = designation;
        customPositionInput.value = '';
    } else {
        positionSelect.value = '';
        customPositionInput.value = designation;
    }
    
    // Handle faculty section
    const isAlsoFaculty = staffData.is_also_faculty || false;
    document.getElementById('editIsAlsoFaculty').checked = isAlsoFaculty;
    if (isAlsoFaculty) {
        document.getElementById('editFacultyEmploymentStatus').value = staffData.employment_status || staffData.faculty_employment_status || '';
        document.getElementById('editFacultyEmployeeNumber').value = staffData.employee_number || staffData.employeeId || '';
    }
    toggleEditFacultySection();
    
    // Handle Program Head assignment
    toggleEditProgramHeadAssignment();
    
    // If it's a Program Head, populate department assignments
    if (designation.toLowerCase() === 'program head') {
        // Load existing assignments and populate the form
        loadExistingAssignments(staffData);
        // Check for signatory assignments and show warnings
        checkSignatoryAssignments(staffData);
    }
    // Clear previous designations and reset chips
    window.editAdditionalDesignations = [];
    const editDesignationsList = document.getElementById('editDesignationsList');
    if (editDesignationsList) editDesignationsList.innerHTML = '';
    
    // Populate edit-designation select
    try { fetchEditDesignations(); } catch(e){}

    // Fetch existing designation assignments for this user and render them
    try {
        const userId = staffData.user_id || staffData.id;
        if (userId) {
            // Fire-and-forget async loader; it will populate window.editAdditionalDesignations and render chips
            fetchEditDesignationAssignments(userId);
        }
    } catch (e) {
        console.error('Failed to initiate fetchEditDesignationAssignments', e);
    }
};

// Load existing department assignments for Program Head
window.loadExistingAssignments = async function(staffData) {
    const userId = staffData.user_id || staffData.id;
    if (!userId) {
        console.error('No user ID found for loading assignments');
        return;
    }

    try {
        // Fetch existing assignments (API accepts both staff_id and user_id)
        const response = await fetch(`../../api/staff/assignments.php?user_id=${userId}`, { credentials: 'include' });
        const data = await response.json();

        if (data.success && data.assignments) {
            // Store existing assignments for later use
            window.currentAssignments = data.assignments;

            // Display current assignments
            displayCurrentAssignments(data.assignments);

            // Determine the sector from existing assignments
            const sectors = [...new Set(data.assignments.map(a => a.sector_name || a.department_type))];
            if (sectors.length > 0) {
                // Set the sector
                const sectorSelect = document.getElementById('editProgramHeadCategory');
                if (sectorSelect) {
                    sectorSelect.value = sectors[0];
                    // Wait for checkboxes to be loaded, then mark existing assignments
                    await updateEditDepartmentCheckboxes();
                    markExistingAssignments(data.assignments);
                }
            }
        } else {
            // No existing assignments, hide current assignments display
            hideCurrentAssignments();
            updateEditDepartmentCheckboxes();
        }
    } catch (error) {
        console.error('Error loading assignments:', error);
        hideCurrentAssignments();
        updateEditDepartmentCheckboxes();
    }
};

// Display current assignments
window.displayCurrentAssignments = function(assignments) {
    const container = document.getElementById('editCurrentAssignmentsContainer');
    const list = document.getElementById('editCurrentAssignmentsList');
    
    if (!container || !list) return;
    
    if (assignments.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    // Group assignments by sector
    const assignmentsBySector = {};
    assignments.forEach(assignment => {
        const sector = assignment.sector_name || 'Unknown';
        if (!assignmentsBySector[sector]) {
            assignmentsBySector[sector] = [];
        }
        assignmentsBySector[sector].push(assignment);
    });
    
    // Generate HTML for each sector
    const html = Object.entries(assignmentsBySector).map(([sector, sectorAssignments]) => {
        const sectorHtml = sectorAssignments.map(assignment => `
            <div class="assigned-dept-item" data-department-id="${assignment.department_id}">
                <div class="dept-info">
                    <span class="dept-name">${assignment.department_name}</span>
                    <span class="dept-sector">(${sector})</span>
                </div>
                <button class="remove-dept-btn" onclick="removeDepartmentAssignment(${assignment.department_id}, '${assignment.department_name}')" title="Remove this department assignment">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        
        return `
            <div class="sector-assignments">
                <div class="sector-header">${sector}</div>
                <div class="sector-dept-list">${sectorHtml}</div>
            </div>
        `;
    }).join('');
    
    list.innerHTML = html;
    container.style.display = 'block';
};

// Hide current assignments display
window.hideCurrentAssignments = function() {
    const container = document.getElementById('editCurrentAssignmentsContainer');
    if (container) {
        container.style.display = 'none';
    }
};

// Remove department assignment
window.removeDepartmentAssignment = function(departmentId, departmentName) {
    if (!confirm(`Are you sure you want to remove the assignment to ${departmentName}?`)) {
        return;
    }
    
    // Remove from current assignments
    if (window.currentAssignments) {
        window.currentAssignments = window.currentAssignments.filter(a => a.department_id !== departmentId);
    }
    
    // Remove from UI
    const item = document.querySelector(`.assigned-dept-item[data-department-id="${departmentId}"]`);
    if (item) {
        item.remove();
    }
    
    // Update the department checkboxes to show this department as available
    const checkbox = document.querySelector(`input[name="assignedDepartments[]"][value="${departmentId}"]`);
    if (checkbox) {
        checkbox.checked = false;
        checkbox.closest('.checkbox-option').classList.remove('selected');
    }
    
    // Check if there are any assignments left
    const remainingItems = document.querySelectorAll('.assigned-dept-item');
    if (remainingItems.length === 0) {
        hideCurrentAssignments();
    }
    
    showToast(`Removed assignment to ${departmentName}`, 'info');
};

// Check if Program Head is currently assigned as a signatory
window.checkSignatoryAssignments = function(staffData) {
    const userId = staffData.user_id || staffData.id;
    if (!userId) return;
    
    // Check if this Program Head is assigned as a signatory in any sector
    fetch(`../../api/signatories/sector_assignments.php?user_id=${userId}`, {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.signatories && data.signatories.length > 0) {
            // Show warning about signatory assignments
            showSignatoryWarning(data.signatories);
        }
    })
    .catch(error => {
        console.error('Error checking signatory assignments:', error);
    });
};

// Show warning about signatory assignments
window.showSignatoryWarning = function(signatoryAssignments) {
    const sectors = [...new Set(signatoryAssignments.map(s => s.clearance_type))];
    const sectorText = sectors.join(', ');
    
    const warningHtml = `
        <div class="signatory-warning" style="
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
            color: #856404;
        ">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>
                <strong>Signatory Assignment Warning</strong>
            </div>
            <p style="margin: 0; font-size: 13px;">
                This Program Head is currently assigned as a signatory for: <strong>${sectorText}</strong>
            </p>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #6c757d;">
                Please uncheck "Include Program Head" in the Add Scope Signatory Modal before making department changes.
            </p>
        </div>
    `;
    
    // Insert warning before the current assignments container
    const currentAssignmentsContainer = document.getElementById('editCurrentAssignmentsContainer');
    if (currentAssignmentsContainer) {
        currentAssignmentsContainer.insertAdjacentHTML('beforebegin', warningHtml);
    }
};

// Mark existing assignments as checked
window.markExistingAssignments = function(assignments) {
    assignments.forEach(assignment => {
        const checkbox = document.querySelector(`input[name="assignedDepartments[]"][value="${assignment.department_id}"]`);
        if (checkbox) {
            checkbox.checked = true;
            checkbox.closest('.checkbox-option').classList.add('selected');
            
            // Mark as primary if needed
            if (assignment.is_primary) {
                checkbox.closest('.checkbox-option').classList.add('primary');
            }
        }
    });
};

window.submitEditStaffForm = function() {
    const form = document.getElementById('editStaffForm');
    const formData = new FormData(form);
    
    // Handle position logic
    const positionSelect = document.getElementById('editStaffPosition');
    const customPositionInput = document.getElementById('editCustomPosition');
    
    const standardPosition = positionSelect.value.trim();
    const customPosition = customPositionInput.value.trim();
    
    // Validation: Must have either standard position OR custom position, not both
    if (!standardPosition && !customPosition) {
        showToastNotification('Please select a standard position or enter a custom position.', 'error');
        return;
    }
    
    if (standardPosition && customPosition) {
        showToastNotification('Please select either a standard position OR enter a custom position, not both.', 'error');
        return;
    }
    
    // Set the final position value
    const finalPosition = standardPosition || customPosition;
    formData.set('staffPosition', finalPosition);
    
    // Handle faculty validation
    const isAlsoFaculty = document.getElementById('editIsAlsoFaculty').checked;
    const facultyEmploymentStatus = document.getElementById('editFacultyEmploymentStatus').value;
    
    if (isAlsoFaculty && !facultyEmploymentStatus) {
        showToastNotification('Faculty Employment Status is required when "Is also a faculty" is checked.', 'error');
        document.getElementById('editFacultyEmploymentStatus').focus();
        return;
    }
    
    // Handle Program Head validation
    const editValidationPositionSelect = document.getElementById('editStaffPosition');
    const editValidationCustomPositionInput = document.getElementById('editCustomPosition');
    const editValidationStandardPosition = editValidationPositionSelect ? editValidationPositionSelect.value.trim() : '';
    const editValidationCustomPosition = editValidationCustomPositionInput ? editValidationCustomPositionInput.value.trim() : '';
    const editValidationFinalPosition = editValidationStandardPosition || editValidationCustomPosition;
    
    if (editValidationFinalPosition === 'Program Head') {
        const programHeadCategory = document.getElementById('editProgramHeadCategory').value;
        const assignedDepartments = document.querySelectorAll('input[name="assignedDepartments[]"]:checked');
        
        if (!programHeadCategory) {
            showToastNotification('Please select a category for Program Head assignment.', 'error');
            document.getElementById('editProgramHeadCategory').focus();
            return;
        }
        
        if (assignedDepartments.length === 0) {
            showToastNotification('Please select at least one department for Program Head assignment.', 'error');
            return;
        }
    }
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Convert to JSON
    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });
    
    // Manually collect department assignments for Program Heads
    if (editValidationFinalPosition === 'Program Head') {
        const assignedDeptCheckboxes = document.querySelectorAll('input[name="assignedDepartments[]"]:checked');
        if (assignedDeptCheckboxes.length > 0) {
            jsonData['assignedDepartments'] = Array.from(assignedDeptCheckboxes).map(cb => cb.value);
        }
    }

    // Build name parts
    const lastName = (jsonData.lastName || '').trim();
    const firstName = (jsonData.firstName || '').trim();
    const middleName = (jsonData.middleName || '').trim();
    if (!lastName || !firstName) {
        showToastNotification('First and Last name are required.', 'error');
        return;
    }
    
    // Map to expected backend payload for user update
    jsonData['first_name'] = firstName;
    jsonData['last_name'] = lastName;
    if (middleName) jsonData['middle_name'] = middleName;
    
    jsonData['role_id'] = 7; // Regular Staff role
    jsonData['is_also_faculty'] = isAlsoFaculty;

        // Add additional designations if any (send as array of IDs)
        if (window.editAdditionalDesignations && window.editAdditionalDesignations.length > 0) {
            jsonData['assignedDesignations'] = window.editAdditionalDesignations.map(d => d.designation_id);
        }
    
    // Submit form
    fetch(form.dataset.endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Handle department assignment updates based on role
            const isProgramHead = (standardPosition || customPosition).toLowerCase() === 'program head';
            const userId = data.user_id || data.userId || jsonData.user_id;
            
            if (isProgramHead) {
                updateProgramHeadAssignments(userId);
            } else {
                // If changing from Program Head to another role, remove all assignments
                const hadAssignments = window.currentAssignments && window.currentAssignments.length > 0;
                if (hadAssignments) {
                    removeAllAssignments(userId);
                } else {
                    showToastNotification('Staff member updated successfully!', 'success');
                    setTimeout(() => {
                        closeEditStaffModal();
                        location.reload();
                    }, 1500); // Wait 1.5 seconds before reloading
                }
            }
        } else {
            showToastNotification(data.message || 'Failed to update staff member.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToastNotification('An error occurred while updating staff member.', 'error');
    });
};

// Update Program Head department assignments
window.updateProgramHeadAssignments = function(userId) {
    if (!userId) {
        showToastNotification('Staff member updated, but assignment update failed - no user ID.', 'error');
        closeEditStaffModal();
        location.reload();
        return;
    }

    // Get current assignments from the form
    const selectedDepartments = Array.from(document.querySelectorAll('input[name="assignedDepartments[]"]:checked'))
        .map(cb => parseInt(cb.value));

    // Get previous assignments
    const previousAssignments = window.currentAssignments || [];
    const previousDepartmentIds = previousAssignments.map(a => a.department_id);

    // Determine which assignments to add and remove
    const toAdd = selectedDepartments.filter(id => !previousDepartmentIds.includes(id));
    const toRemove = previousDepartmentIds.filter(id => !selectedDepartments.includes(id));

    // Process all assignment changes
    const assignmentPromises = [];

    // Remove assignments
    toRemove.forEach(departmentId => {
        const assignment = previousAssignments.find(a => a.department_id === departmentId);
        if (assignment) {
            assignmentPromises.push(
                fetch('../../api/staff/assignments.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        user_id: userId,
                        department_id: departmentId
                    })
                })
            );
        }
    });

    // Add new assignments
    toAdd.forEach(departmentId => {
        assignmentPromises.push(
            fetch('../../api/staff/assignments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    user_id: userId,
                    department_id: departmentId,
                    is_primary: false // Default to non-primary
                })
            })
        );
    });

    // Execute all assignment changes
    Promise.all(assignmentPromises)
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            const hasErrors = results.some(r => !r.success);
            if (hasErrors) {
                showToastNotification('Staff member updated, but some assignment changes failed.', 'warning');
            } else {
                showToastNotification('Staff member and assignments updated successfully!', 'success');
            }
            setTimeout(() => {
                closeEditStaffModal();
                location.reload();
            }, 1500);
        })
        .catch(error => {
            console.error('Error updating assignments:', error);
            showToastNotification('Staff member updated, but assignment update failed.', 'warning');
            setTimeout(() => {
                closeEditStaffModal();
                location.reload();
            }, 1500);
        });
};

// Remove all department assignments (when changing from Program Head to another role)
window.removeAllAssignments = function(userId) {
    if (!userId || !window.currentAssignments) {
        showToastNotification('Staff member updated successfully!', 'success');
        setTimeout(() => {
            closeEditStaffModal();
            location.reload();
        }, 1500);
        return;
    }

    // Remove all existing assignments
    const removePromises = window.currentAssignments.map(assignment => 
        fetch('../../api/staff/assignments.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                user_id: userId,
                department_id: assignment.department_id
            })
        })
    );

    Promise.all(removePromises)
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            const hasErrors = results.some(r => !r.success);
            if (hasErrors) {
                showToastNotification('Staff member updated, but some assignment removals failed.', 'warning');
            } else {
                showToastNotification('Staff member updated and assignments removed successfully!', 'success');
            }
            setTimeout(() => {
                closeEditStaffModal();
                location.reload();
            }, 1500);
        })
        .catch(error => {
            console.error('Error removing assignments:', error);
            showToastNotification('Staff member updated, but assignment removal failed.', 'warning');
            setTimeout(() => {
                closeEditStaffModal();
                location.reload();
            }, 1500);
    });
};

    // Toggle edit faculty section visibility
    window.toggleEditFacultySection = function() {
        const isAlsoFaculty = document.getElementById('editIsAlsoFaculty');
        const facultySection = document.getElementById('editFacultyFieldsSection');
        const facultyEmploymentStatus = document.getElementById('editFacultyEmploymentStatus');
        const employeeId = document.getElementById('editEmployeeId');
        const facultyEmployeeNumber = document.getElementById('editFacultyEmployeeNumber');
        
        if (isAlsoFaculty.checked) {
            // Show faculty section
            facultySection.style.display = 'block';
            facultySection.style.opacity = '0';
            
            // Auto-fill employee number
            if (employeeId.value) {
                facultyEmployeeNumber.value = employeeId.value;
            }
            
            // Make employment status required
            facultyEmploymentStatus.required = true;
            
            // Animate in
            setTimeout(() => {
                facultySection.style.opacity = '1';
            }, 10);
        } else {
            // Hide faculty section
            facultySection.style.opacity = '0';
            setTimeout(() => {
                facultySection.style.display = 'none';
            }, 300);
            
            // Clear and unrequire employment status
            facultyEmploymentStatus.value = '';
            facultyEmploymentStatus.required = false;
            facultyEmployeeNumber.value = '';
        }
    };

    // Toggle edit Program Head assignment section
    window.toggleEditProgramHeadAssignment = function() {
        const editProgramHeadPositionSelect = document.getElementById('editStaffPosition');
        const editProgramHeadCustomPositionInput = document.getElementById('editCustomPosition');
        const programHeadSection = document.getElementById('editProgramHeadAssignmentSection');
        
        // Get the final position value
        const editProgramHeadStandardPosition = editProgramHeadPositionSelect ? editProgramHeadPositionSelect.value.trim() : '';
        const editProgramHeadCustomPosition = editProgramHeadCustomPositionInput ? editProgramHeadCustomPositionInput.value.trim() : '';
        const editProgramHeadFinalPosition = editProgramHeadStandardPosition || editProgramHeadCustomPosition;
        
        if (editProgramHeadFinalPosition === 'Program Head') {
            // Show Program Head assignment section
            programHeadSection.style.display = 'block';
            programHeadSection.style.opacity = '0';
            
            // Animate in
            setTimeout(() => {
                programHeadSection.style.opacity = '1';
            }, 10);
        } else {
            // Hide Program Head assignment section
            programHeadSection.style.opacity = '0';
            setTimeout(() => {
                programHeadSection.style.display = 'none';
            }, 300);
            
            // Clear Program Head fields
            clearEditProgramHeadFields();
        }
    };

    // Update edit department checkboxes based on selected category
    window.updateEditDepartmentCheckboxes = function() {
        const categorySelect = document.getElementById('editProgramHeadCategory');
        const checkboxesContainer = document.getElementById('editDepartmentCheckboxesContainer');
        const checkboxesList = document.getElementById('editDepartmentCheckboxesList');
        
        if (!categorySelect || !checkboxesContainer || !checkboxesList) return;
        
        const selectedCategory = categorySelect.value;
        
        if (!selectedCategory) {
            checkboxesContainer.style.display = 'none'; 
            return Promise.resolve(); // Return a resolved promise
        }
        
        return new Promise((resolve, reject) => {
            // Load departments from API filtered by sector/category
            const url = `../../api/departments/list.php?sector=${encodeURIComponent(selectedCategory)}&include_ph=1&limit=500`;
            checkboxesList.innerHTML = '<div style="padding:8px;color:#6c757d;">Loading departments...</div>';
            
            fetch(url, { credentials: 'include' })
                .then(r => r.json())
                .then(resp => {
                    checkboxesList.innerHTML = '';
                    if (!resp || resp.success !== true) {
                        checkboxesList.innerHTML = '<div style="padding:8px;color:#dc3545;">Failed to load departments</div>';
                        reject(new Error('Failed to load departments'));
                        return;
                    }
                    const departments = resp.departments || [];
                    if (departments.length === 0) {
                        checkboxesList.innerHTML = '<div style="padding:8px;color:#6c757d;">No departments found for this sector</div>';
                    }
                    
                    departments.forEach(dep => {
                        const depId = dep.department_id;
                        const depName = dep.department_name;
                        const phUserId = dep.current_program_head_user_id || null;
                        const phName = dep.current_program_head_name || '';
                        const phEmployeeNumber = dep.current_program_head_employee_number || '';
                        
                        const disabled = false; // Always allow selection, transfer toggle will handle logic
                        const option = document.createElement('div');
                        option.className = 'checkbox-option';
                        const inputId = `edit_dept_${depId}`;
                        
                        // Create enhanced label with Program Head info
                        let phInfo = '';
                        if (phUserId) { // Show info if a PH is assigned
                            phInfo = `
                                <div class="current-ph-info">
                                    <span class="ph-assigned-label">Currently assigned to:</span>
                                    <span class="ph-name">${phName}</span>
                                    <span class="ph-employee">(${phEmployeeNumber})</span>
                                </div>
                            `;
                        }
                        
                        option.innerHTML = `
                            <input type="checkbox" id="${inputId}" name="assignedDepartments[]" value="${depId}" ${disabled ? 'disabled' : ''}>
                            <label for="${inputId}">
                                <div class="dept-label-main">${depName}</div>
                                ${phInfo}
                            </label>
                        `;
                        checkboxesList.appendChild(option);
                    });

                    // Sync visual selection state with checked state (multi-select)
                    checkboxesList.addEventListener('change', function(e){
                        if (e.target && e.target.matches('input[type="checkbox"]')) {
                            const container = e.target.closest('.checkbox-option');
                            if (container) {
                                if (e.target.checked) container.classList.add('selected');
                                else container.classList.remove('selected');
                            }
                        }
                    });
                    resolve(); // Resolve the promise when done
                })
                .catch((err) => {
                    checkboxesList.innerHTML = '<div style="padding:8px;color:#dc3545;">Error loading departments</div>';
                    reject(err); // Reject the promise on error
                })
                .finally(() => {
                    checkboxesContainer.style.display = 'block';
                    checkboxesContainer.style.opacity = '0';
                    setTimeout(() => { checkboxesContainer.style.opacity = '1'; }, 10);
                });
        });
    };

    // Clear edit Program Head fields
    function clearEditProgramHeadFields() {
        const categorySelect = document.getElementById('editProgramHeadCategory');
        const checkboxesContainer = document.getElementById('editDepartmentCheckboxesContainer');
        const checkboxesList = document.getElementById('editDepartmentCheckboxesList');
        
        if (categorySelect) categorySelect.value = '';
        if (checkboxesContainer) checkboxesContainer.style.display = 'none';
        if (checkboxesList) checkboxesList.innerHTML = '';
    }

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const contactInput = document.getElementById('editStaffContact');
    if (contactInput) {
        contactInput.addEventListener('input', function() {
            // Format phone number
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '+63 ' + value.substring(2);
            } else if (value.startsWith('09')) {
                value = '+63 ' + value.substring(1);
            }
            this.value = value;
        });
    }
    
    // Handle position field validation
    const positionSelect = document.getElementById('editStaffPosition');
    const customPositionInput = document.getElementById('editCustomPosition');
    
    if (positionSelect && customPositionInput) {
        // Clear custom position when standard position is selected
        positionSelect.addEventListener('change', function() {
            if (this.value.trim()) {
                customPositionInput.value = '';
            }
            // Toggle Program Head assignment section
            toggleEditProgramHeadAssignment();
        });
        
        // Clear standard position when custom position is entered
        customPositionInput.addEventListener('input', function() {
            if (this.value.trim()) {
                positionSelect.value = '';
            }
            // Toggle Program Head assignment section
            toggleEditProgramHeadAssignment();
        });
    }
});

// --- Password Reset Logic ---

function handlePasswordReset() {
    const userId = document.getElementById('editStaffForm').dataset.userId;
    const username = document.getElementById('editEmployeeId').value;

    if (!userId) {
        showToastNotification('Cannot reset password. User ID is missing.', 'error');
        return;
    }

    showConfirmationModal(
        'Reset Password',
        `Are you sure you want to reset the password for ${username}? A new password will be generated.`,
        'Reset',
        'Cancel',
        async () => {
            try {
                // Generate a new secure password on the client-side for immediate display
                const newPassword = generateSecurePassword();

                const response = await fetch('../../api/users/password.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        user_id: userId,
                        new_password: newPassword
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'An unknown error occurred.' }));
                    throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                }


                const data = await response.json();

                if (data.success) {
                    // Use the new unified GeneratedCredentialsModal
                    openGeneratedCredentialsModal('passwordReset', { username: username, password: newPassword });
                } else {
                    throw new Error(data.message || 'Failed to reset password.');
                }
            } catch (error) {
                showToastNotification(error.message, 'error');
            }
        },
        'warning'
    );
}

function generateSecurePassword(length = 12) {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
    let password = "";
    for (let i = 0, n = charset.length; i < length; ++i) {
        password += charset.charAt(Math.floor(Math.random() * n));
    }
    return password;
}

// Store additional designations for edit form
// Each item: { designation_id, designation_name }
window.editAdditionalDesignations = [];

// Helper functions for designation normalization
function normalizeEditDesignation(name) {
    name = (name || '').trim();
    return name.replace(/\s+/g, ' ');
}

function isValidEditDesignation(name) {
    if (name.length < 2 || name.length > 50) return false;
    return /^[A-Za-z0-9 \-\/&'.\.]+$/.test(name);
}

// Add additional designation in edit form (resolve name -> id, create if needed)
// Add additional designation in edit form (select from dropdown)
window.addEditAdditionalDesignation = function() {
    const sel = document.getElementById('editAdditionalDesignationSelect');
    if (!sel) return;
    const val = sel.value;
    const text = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';

    if (!val) {
        showToastNotification('Please select a designation', 'error');
        return;
    }

    const desigId = parseInt(val, 10);
    if (isNaN(desigId)) {
        showToastNotification('Invalid designation selected', 'error');
        return;
    }

    // Prevent duplicates by id
    if (window.editAdditionalDesignations.some(d => d.designation_id === desigId)) {
        showToastNotification('This designation is already added', 'warning');
        return;
    }

    window.editAdditionalDesignations.push({ designation_id: desigId, designation_name: text });
    sel.value = '';
    renderEditAdditionalDesignations();
};

// Remove additional designation from edit form
window.removeEditAdditionalDesignation = function(designationName) {
    window.editAdditionalDesignations = window.editAdditionalDesignations.filter(d => (d.designation_name || '') !== designationName);
    renderEditAdditionalDesignations();
};

// Render additional designations as chips in edit form
function renderEditAdditionalDesignations() {
    const container = document.getElementById('editDesignationsList');
    if (!container) return;

    container.innerHTML = window.editAdditionalDesignations.map(d => `
        <span class="chip" style="padding: 6px 12px; background: #e9ecef; border-radius: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            ${d.designation_name}
            <button type="button" onclick="removeEditAdditionalDesignation('${d.designation_name.replace(/'/g, "\\'") }')" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 16px; padding: 0;">
                ×
            </button>
        </span>
    `).join('');
}

// Fetch designations and populate edit additional-designation select
function fetchEditDesignations(q) {
    const sel = document.getElementById('editAdditionalDesignationSelect');
    if (!sel) return;
    const url = '../../api/users/designations.php' + (q ? ('?q=' + encodeURIComponent(q)) : '');
    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data || data.success !== true) return;
            // clear and add placeholder
            sel.innerHTML = '';
            const ph = document.createElement('option');
            ph.value = '';
            ph.text = 'Select a designation...';
            sel.appendChild(ph);
            (data.designations || []).forEach(d => {
                const o = document.createElement('option');
                o.value = d.designation_id;
                o.text = d.designation_name;
                sel.appendChild(o);
            });
            sel.selectedIndex = 0;
        })
        .catch(() => {});
}

// Fetch existing designation assignments for a user and populate editAdditionalDesignations
async function fetchEditDesignationAssignments(userId) {
    try {
        const url = `../../api/staff/designation_assignments.php?user_id=${encodeURIComponent(userId)}`;
        console.log(`Fetching designations from: ${url}`);
        const res = await fetch(url, { credentials: 'include' });
        const data = await res.json();
        console.log('Designation assignments response:', data);
        
        if (data && data.success === true && Array.isArray(data.designations)) {
            console.log('Setting editAdditionalDesignations:', data.designations);
            window.editAdditionalDesignations = data.designations.map(d => ({
                designation_id: parseInt(d.designation_id, 10),
                designation_name: d.designation_name
            }));
            renderEditAdditionalDesignations();
        } else {
            console.warn('API response not successful or no designations array:', data);
            // Ensure array is initialized
            window.editAdditionalDesignations = window.editAdditionalDesignations || [];
            renderEditAdditionalDesignations();
        }
    } catch (err) {
        console.error('Error fetching designation assignments:', err);
        window.editAdditionalDesignations = window.editAdditionalDesignations || [];
        renderEditAdditionalDesignations();
    }
}
</script> 