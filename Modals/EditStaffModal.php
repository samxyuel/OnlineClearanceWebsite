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
                    </div>
                </div>
                
                <div class="form-group" id="editRegularDepartmentGroup">
                    <label for="editStaffDepartment">Department</label>
                    <select id="editStaffDepartment" name="staffDepartment" required>
                        <option value="">Select Department</option>
                        <option value="Administration">Administration</option>
                        <option value="Finance">Finance</option>
                        <option value="Student Services">Student Services</option>
                        <option value="Library">Library</option>
                        <option value="IT">IT</option>
                        <option value="Academic">Academic</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Facilities">Facilities</option>
                        <option value="Health Services">Health Services</option>
                        <option value="Alumni Relations">Alumni Relations</option>
                    </select>
                </div>
                
                <!-- Program Head Assignment Section (Hidden by default) -->
                <div id="editProgramHeadAssignmentSection" class="program-head-assignment-section" style="display: none;">
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
                           placeholder="+63 912 345 6789" required>
                </div>
                
                <div class="form-group">
                    <label for="editStaffStatus">Staff Status</label>
                    <select id="editStaffStatus" name="staffStatus" required>
                        <option value="">Select Status</option>
                        <option value="essential">Essential Staff</option>
                        <option value="optional">Optional Staff</option>
                    </select>
                    <small class="form-help">Essential staff cannot be deleted and are critical for clearance workflow.</small>
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
    const modal = document.querySelector('.edit-staff-modal-overlay');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        // Reset form
        document.getElementById('editStaffForm').reset();
    }
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
    document.getElementById('editStaffStatus').value = staffData.staff_status || '';
    
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
        document.getElementById('editFacultyEmploymentStatus').value = staffData.faculty_employment_status || '';
        document.getElementById('editFacultyEmployeeNumber').value = staffData.employee_number || staffData.employeeId || '';
    }
    toggleEditFacultySection();
    
    // Handle Program Head assignment
    toggleEditProgramHeadAssignment();
    
    // If it's a Program Head, populate department assignments
    if (designation.toLowerCase() === 'program head') {
        // This would need to be populated based on existing assignments
        // For now, just show the section
        setTimeout(() => {
            updateEditDepartmentCheckboxes();
        }, 100);
    }
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
        showToast('Please select a standard position or enter a custom position.', 'error');
        return;
    }
    
    if (standardPosition && customPosition) {
        showToast('Please select either a standard position OR enter a custom position, not both.', 'error');
        return;
    }
    
    // Set the final position value
    const finalPosition = standardPosition || customPosition;
    formData.set('staffPosition', finalPosition);
    
    // Handle faculty validation
    const isAlsoFaculty = document.getElementById('editIsAlsoFaculty').checked;
    const facultyEmploymentStatus = document.getElementById('editFacultyEmploymentStatus').value;
    
    if (isAlsoFaculty && !facultyEmploymentStatus) {
        showToast('Faculty Employment Status is required when "Is also a faculty" is checked.', 'error');
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
            showToast('Please select a category for Program Head assignment.', 'error');
            document.getElementById('editProgramHeadCategory').focus();
            return;
        }
        
        if (assignedDepartments.length === 0) {
            showToast('Please select at least one department for Program Head assignment.', 'error');
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
    
    // Build name parts
    const lastName = (jsonData.lastName || '').trim();
    const firstName = (jsonData.firstName || '').trim();
    const middleName = (jsonData.middleName || '').trim();
    if (!lastName || !firstName) {
        showToast('First and Last name are required.', 'error');
        return;
    }
    
    // Map to expected backend payload for user update
    jsonData['first_name'] = firstName;
    jsonData['last_name'] = lastName;
    if (middleName) jsonData['middle_name'] = middleName;
    
    jsonData['role_id'] = 4; // Staff role
    jsonData['is_also_faculty'] = isAlsoFaculty;
    
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
            showToast('Staff member updated successfully!', 'success');
            closeEditStaffModal();
            // Optionally reload the page or update the cards
            location.reload();
        } else {
            showToast(data.message || 'Failed to update staff member.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating staff member.', 'error');
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
        const regularDepartmentGroup = document.getElementById('editRegularDepartmentGroup');
        const programHeadSection = document.getElementById('editProgramHeadAssignmentSection');
        
        // Get the final position value
        const editProgramHeadStandardPosition = editProgramHeadPositionSelect ? editProgramHeadPositionSelect.value.trim() : '';
        const editProgramHeadCustomPosition = editProgramHeadCustomPositionInput ? editProgramHeadCustomPositionInput.value.trim() : '';
        const editProgramHeadFinalPosition = editProgramHeadStandardPosition || editProgramHeadCustomPosition;
        
        if (editProgramHeadFinalPosition === 'Program Head') {
            // Hide regular department field
            regularDepartmentGroup.style.display = 'none';
            
            // Show Program Head assignment section
            programHeadSection.style.display = 'block';
            programHeadSection.style.opacity = '0';
            
            // Animate in
            setTimeout(() => {
                programHeadSection.style.opacity = '1';
            }, 10);
        } else {
            // Show regular department field
            regularDepartmentGroup.style.display = 'block';
            
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
            return;
        }
        
        // Define department mappings
        const categoryDepartments = {
            "College": [
                "ICT Department",
                "THM Department",
                "BSA Department",
                "Computer Science Department"
            ],
            "Senior High School": [
                "Home Economics",
                "Academic Track",
                "Technological Vocational Livelihood"
            ],
            "Faculty": [
                "General Education"
            ]
        };
        
        const departments = categoryDepartments[selectedCategory] || [];
        
        // Clear existing checkboxes
        checkboxesList.innerHTML = '';
        
        // Create checkboxes for each department
        departments.forEach(department => {
            const checkboxOption = document.createElement('div');
            checkboxOption.className = 'checkbox-option';
            checkboxOption.innerHTML = `
                <input type="checkbox" id="edit_dept_${department.replace(/\s+/g, '_')}" 
                       name="assignedDepartments[]" value="${department}">
                <label for="edit_dept_${department.replace(/\s+/g, '_')}">${department}</label>
            `;
            checkboxesList.appendChild(checkboxOption);
        });
        
        // Show checkboxes container
        checkboxesContainer.style.display = 'block';
        checkboxesContainer.style.opacity = '0';
        setTimeout(() => {
            checkboxesContainer.style.opacity = '1';
        }, 10);
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
</script> 