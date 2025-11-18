<?php
// Staff Registration Modal
?>

<div class="modal-overlay staff-registration-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeStaffRegistrationModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Register New Staff Member</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Add a new administrative personnel or signatory to the clearance system.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="staffRegistrationForm" data-endpoint="../../controllers/addStaff.php">
                <input type="hidden" name="type" value="staff">
                
                <div class="form-group">
                    <label for="employeeId">Employee ID</label>
                    <input type="text" id="employeeId" name="employeeId" placeholder="LCA1234P" required 
                           pattern="LCA[0-9]{4}[A-Z]" 
                           title="Format: LCA + 4 digits + 1 letter (e.g., LCA1234P)">
                    <small class="form-help">Format: LCA + 4 digits + 1 letter (e.g., LCA1234P)</small>
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Doe" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="John" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name (Optional)</label>
                    <input type="text" id="middleName" name="middleName" placeholder="A.">
                </div>
                
                <div class="form-group">
                    <label for="designationInput">Designation</label>
                    <input type="text" id="designationInput" name="designation" list="designationOptions" placeholder="e.g., Registrar, Program Head, MIS/IT" required>
                    <datalist id="designationOptions"></datalist>
                    <small class="form-help">Type to search existing designations or create a new one. Allowed: letters, numbers, space, - / & ' . (2–50 chars)</small>
                    <small id="designationCreateHint" class="form-help" style="display:none;color:var(--primary-color)"></small>
                </div>

                                <!-- Additional Designations Section -->
                                <div class="form-group">
                                    <label>Additional Designations (Optional)</label>
                                    <small class="form-help">Add secondary designations for this staff member</small>
                                    <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
                                        <select id="additionalDesignationSelect" style="flex: 1; padding:6px;">
                                            <option value="">Select a designation...</option>
                                        </select>
                                        <button type="button" id="addDesignationBtn" class="btn btn-sm btn-outline-primary" onclick="addAdditionalDesignation()">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <div id="designationsList" style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                                        <!-- Additional designations will appear as chips here -->
                                    </div>
                                </div>
                
                
                <!-- Program Head Assignment Section (Hidden by default) -->
                <div id="programHeadAssignmentSection" class="program-head-assignment-section" style="display: none;">
                    <div class="form-group">
                        <label for="programHeadCategory">Program Head Assignment <span class="required-asterisk">*</span></label>
                        <select id="programHeadCategory" name="programHeadCategory" onchange="updateDepartmentCheckboxes()">
                            <option value="">Select Category</option>
                            <option value="College">College</option>
                            <option value="Senior High School">Senior High School</option>
                            <option value="Faculty">Faculty</option>
                        </select>
                        <small class="form-help">Select the category this Program Head will manage</small>
                    </div>
                    
                    <div id="departmentCheckboxesContainer" class="department-checkboxes-container" style="display: none;">
                        <label class="checkbox-section-label">Available Departments <span class="required-asterisk">*</span></label>
                        <small class="form-help">Select at least one department for Program Head assignment</small>
                        <div id="departmentCheckboxesList" class="checkbox-group">
                            <!-- Checkboxes will be populated dynamically -->
                        </div>
                        <div class="form-group" style="margin-top:10px;">
                            <label class="checkbox-label" style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" id="phTransferToggle" checked>
                                <span>Transfer existing Program Head if a department is already assigned</span>
                            </label>
                            <small class="form-help">When checked, assigning will replace the current Program Head for occupied departments.</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="staffEmail">Email Address (Optional)</label>
                    <input type="email" id="staffEmail" name="staffEmail" 
                           placeholder="john.smith@gosti.edu.ph">
                </div>
                
                <div class="form-group">
                    <label for="staffContact">Contact Number (Optional)</label>
                    <input type="tel" id="staffContact" name="staffContact" 
                           placeholder="+63 912 345 6789">
                </div>
                
                
                
                <!-- Faculty Section Divider 
                <div class="form-section-divider">
                    <hr>
                    <span class="divider-text">Faculty Registration (Optional)</span>
                </div -->
                
                <!-- Is also a faculty checkbox -
                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" id="isAlsoFaculty" name="isAlsoFaculty" onchange="toggleFacultySection()">
                        <label for="isAlsoFaculty" class="checkbox-label">Is also a faculty</label>
                    </div>
                    <small class="form-help">Check this if the staff member should also have faculty access</small>
</div -->
                
                <!-- Faculty Fields Section (Hidden by default) --
                <div id="facultyFieldsSection" class="faculty-fields-section" style="display: none;">
                    <div class="form-group">
                        <label for="facultyEmploymentStatus">Faculty Employment Status <span class="required-asterisk">*</span></label>
                        <select id="facultyEmploymentStatus" name="facultyEmploymentStatus">
                            <option value="">Select Employment Status</option>
                            <option value="Part Time - Full Load">Part Time - Full Load</option>
                            <option value="Part Time">Part Time</option>
                            <option value="Full Time">Full Time</option>
                        </select>
                        <small class="form-help">Required when "Is also a faculty" is checked</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="facultyEmployeeNumber">Employee Number</label>
                        <input type="text" id="facultyEmployeeNumber" name="facultyEmployeeNumber" readonly 
                               style="background-color: #f8f9fa; color: #6c757d;">
                        <small class="form-help">Auto-filled from Employee ID (read-only)</small>
                    </div>
                </div -->
            </form>
        </div>
        
        <!-- Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeStaffRegistrationModal()">Cancel</button>
            <button class="modal-action-primary" onclick="submitStaffRegistrationForm()">Register Staff</button>
        </div>
    </div>
</div>

<script>
// Make functions globally accessible
window.closeStaffRegistrationModal = function() {
    const modal = document.querySelector('.staff-registration-modal-overlay');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        // Reset form
        document.getElementById('staffRegistrationForm').reset();
        // Reset additional designations
        window.additionalDesignations = [];
        const designationsList = document.getElementById('designationsList');
        if (designationsList) designationsList.innerHTML = '';
        // Reset additional designation select if present
        const addSel = document.getElementById('additionalDesignationSelect');
        if (addSel) addSel.value = '';
    }
};

// Open Staff Registration Modal with initialization
window.openStaffRegistrationModalWithInit = function() {
    const modal = document.querySelector('.staff-registration-modal-overlay');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        
        // Initialize designations on first open
        if (!document.getElementById('designationOptions').hasChildNodes()) {
            fetchDesignations('');
        }
        // Ensure the additional-designation select is populated with the full list
        populateAdditionalDesignationSelect();
    }
};

window.submitStaffRegistrationForm = function() {
    if (!validateStaffRegistrationForm()) {
        showToast('Please correct the errors in the form.', 'error');
        return;
    }

    // Generate credentials locally first
    const form = document.getElementById('staffRegistrationForm');
    const empId = form.employeeId.value.trim();
    const lastName = form.lastName.value.trim().replace(/\s+/g, '');
    const username = empId;
    const password = `${lastName}${empId}`; // Case-sensitive as per previous request

    // Prepare the data for the modal and the final submission
    const credentialData = { username, password };

    // The callback function that will be executed when "Confirm & Save" is clicked
    const confirmCallback = () => {
      confirmStaffCreation(credentialData);
    };

    // Open the unified credentials modal
    openGeneratedCredentialsModal('newAccount', credentialData, confirmCallback);
};

function validateStaffRegistrationForm() {
    const form = document.getElementById('staffRegistrationForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    /*
    // Temporarily disabled because the "Is also a faculty" UI is commented out.
    const isAlsoFaculty = document.getElementById('isAlsoFaculty').checked;
    const facultyEmploymentStatus = document.getElementById('facultyEmploymentStatus').value;
    
    if (isAlsoFaculty && !facultyEmploymentStatus) {
        showToast('Faculty Employment Status is required when "Is also a faculty" is checked.', 'error');
        document.getElementById('facultyEmploymentStatus').focus();
        return false;
    }
    */

    return true;
}

function confirmStaffCreation(credentialData) {
    const form = document.getElementById('staffRegistrationForm');
    const formData = new FormData(form);
    const designationInput = document.getElementById('designationInput');
    const rawDesignation = (designationInput.value || '').trim();
    const normalizedDesignation = normalizeDesignation(rawDesignation);
    if (!isValidDesignation(normalizedDesignation)) {
        showToast('Invalid designation. Use 2–50 allowed characters.', 'error');
        designationInput.focus();
        return;
    }
    
    // Handle Program Head validation
    if (normalizedDesignation.toLowerCase() === 'program head') {
        const programHeadCategory = document.getElementById('programHeadCategory').value;
        const assignedDepartments = document.querySelectorAll('input[name="assignedDepartments[]"]:checked');
        
        if (!programHeadCategory) {
            showToast('Please select a category for Program Head assignment.', 'error');
            document.getElementById('programHeadCategory').focus();
            return;
        }
        
        if (assignedDepartments.length === 0) {
            showToast('Please select at least one department for Program Head assignment.', 'error');
            return;
        }
    }
    
    // Convert to JSON
    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });

    // Manually collect department assignments for Program Heads, as FormData doesn't handle array-like names well.
    /*
    // Temporarily disabled because the "Is also a faculty" UI is commented out.
    const isAlsoFaculty = document.getElementById('isAlsoFaculty').checked;
    const facultyEmploymentStatus = document.getElementById('facultyEmploymentStatus').value;
    */
    const isAlsoFaculty = false;

    if (normalizedDesignation.toLowerCase() === 'program head') {
        const assignedDeptCheckboxes = document.querySelectorAll('#staffRegistrationForm input[name="assignedDepartments[]"]:checked');
        if (assignedDeptCheckboxes.length > 0) {
            jsonData['assignedDepartments'] = Array.from(assignedDeptCheckboxes).map(cb => cb.value);
        }
    }

    // Build name parts
    const lastName = (jsonData.lastName || '').trim();
    const firstName = (jsonData.firstName || '').trim();
    const middleName = (jsonData.middleName || '').trim();
    if (!lastName || !firstName) {
        showToast('First and Last name are required.', 'error');
        return;
    }
    jsonData['is_also_faculty'] = isAlsoFaculty;
    // Map to expected backend payload for user creation
    jsonData['first_name'] = firstName;
    jsonData['last_name'] = lastName;
    if (middleName) jsonData['middle_name'] = middleName;

    // Add generated credentials to the payload
    jsonData['username'] = credentialData.username;
    jsonData['password'] = credentialData.password;

    // Optional fields normalization
    if (!jsonData.staffEmail) delete jsonData.staffEmail;
    if (!jsonData.staffContact) delete jsonData.staffContact;
    
    // Use the correct key for the backend to recognize the designation
    jsonData['staffPosition'] = normalizedDesignation;
    delete jsonData['designation']; // Remove the incorrect key

    // Add additional designations if any (send as array of IDs)
    if (window.additionalDesignations && window.additionalDesignations.length > 0) {
        jsonData['assignedDesignations'] = window.additionalDesignations.map(d => d.designation_id);
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
        // Re-enable the primary button in the credentials modal
        const confirmBtn = document.getElementById('credentialModalConfirmBtn');
        if(confirmBtn) confirmBtn.disabled = false;

        if (data.status === 'success') {
            // Fire audit log (non-blocking)
            try{
                fetch('../../api/audit/log.php', {
                    method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                    body: JSON.stringify({ activity_type:'staff_registered', details: { employee_id: jsonData.employeeId || null, name: jsonData.staffName || null, designation: normalizedDesignation } })
                });
            }catch(e){}
            // If Program Head, perform assignments to selected departments (with transfer support)
            const newUserId = data.user_id || data.userId || data.created_user_id || null;
            if (newUserId && isAlsoFaculty) {
                // Trigger automatic clearance form creation for the new faculty member
                onUserCreated(newUserId, 'Faculty').catch(console.error);
            }

            const isPH = normalizedDesignation.toLowerCase() === 'program head';
            const selectedCategory = document.getElementById('programHeadCategory').value;
            const selectedDeptInputs = document.querySelectorAll('input[name="assignedDepartments[]"]:checked');
            const selectedDeptIds = Array.from(selectedDeptInputs).map(i => parseInt(i.value, 10)).filter(n => !isNaN(n));

            const proceedAfterAssign = () => {
                try{
                    // Collect selected department names (for Program Head chips)
                    const selectedDeptInputs = document.querySelectorAll('input[name="assignedDepartments[]"]:checked');
                    const selectedDeptNames = Array.from(selectedDeptInputs).map(cb => {
                        const container = cb.closest('.checkbox-option');
                        const label = container ? container.querySelector('label') : null;
                        return label ? label.textContent.replace(/\s*\(Assigned to.*\)$/,'').trim() : '';
                    }).filter(Boolean);
                    const payload = {
                        employee_id: data.employee_id || data.user_id || null,
                        employeeId: data.employee_id || null,
                        first_name: jsonData.first_name || null,
                        middle_name: jsonData.middle_name || null,
                        last_name: jsonData.last_name || null,
                        name: ((jsonData.first_name||'') + ' ' + (jsonData.middle_name? (jsonData.middle_name+' ') : '') + (jsonData.last_name||'')).trim(),
                        designation: normalizedDesignation,
                        departments: selectedDeptNames
                    };
                    document.dispatchEvent(new CustomEvent('staff-added', { detail: payload }));
                }catch(e){}
                showToast('Staff member registered successfully!', 'success');
                closeGeneratedCredentialsModal(); // Close the credentials modal
                closeStaffRegistrationModal();
                            // Reset additional designations
                            window.additionalDesignations = [];
                            const designationsList = document.getElementById('designationsList');
                            if (designationsList) designationsList.innerHTML = '';
            };

            if (isPH && selectedDeptIds.length > 0) {
                // Determine user_id of the newly created staff
                if (!newUserId) {
                    // Fallback: cannot assign without user_id
                    proceedAfterAssign();
                    return;
                }
                const transferFlag = !!(document.getElementById('phTransferToggle') && document.getElementById('phTransferToggle').checked);
                // Build bulk assignments payload
                const assignments = selectedDeptIds.map(depId => ({
                    user_id: newUserId,
                    designation: 'Program Head',
                    department_id: depId,
                    staff_category: 'Program Head',
                    transfer: transferFlag
                }));
                fetch('../../api/signatories/bulk_assign.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ assignments })
                })
                .then(r => r.json())
                .then(res => {
                    if (!res || res.success !== true) {
                        showToast('Registered, but PH assignment failed.', 'error');
                    }
                    // Audit PH assignments
                    try{
                        fetch('../../api/audit/log.php', {
                            method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                            body: JSON.stringify({ activity_type:'program_head_assigned', details: { user_id:newUserId, department_ids: selectedDeptIds, transfer: transferFlag } })
                        });
                    }catch(e){}
                })
                .catch(() => {
                    showToast('Registered, but PH assignment failed.', 'error');
                })
                .finally(() => {
                    proceedAfterAssign();
                });
            } else {
                proceedAfterAssign();
            }
        } else {
            showToast(data.message || 'Failed to register staff member.', 'error');
        }
    })
    .catch(error => {
        // Re-enable the primary button in the credentials modal on error
        const confirmBtn = document.getElementById('credentialModalConfirmBtn');
        if(confirmBtn) confirmBtn.disabled = false;

        console.error('Error:', error);
        showToast('An error occurred while registering staff member.', 'error');
    });
    // Disable the primary button in the credentials modal to prevent double-clicks
    const confirmBtn = document.getElementById('credentialModalConfirmBtn');
    if(confirmBtn) confirmBtn.disabled = true;
};

async function onUserCreated(newUserId, userSector) {
    // Only proceed if a valid sector is provided (e.g., 'Faculty')
    if (!userSector) return;

    try {
        // 1. Check for an active clearance period
        const context = await fetch('../../api/clearance/context.php', { credentials: 'include' }).then(r => r.json());
        const activeSemester = context.terms.find(t => t.is_active === 1);

        if (activeSemester) {
            console.log(`Active period found for ${activeSemester.semester_name}. Creating clearance form for new user...`);

            // 2. Call the distribution API for the single new user
            const response = await fetch('../../api/clearance/form_distribution.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    user_id: newUserId, // The new, crucial parameter
                    clearance_type: userSector,
                    academic_year_id: context.academic_year.academic_year_id,
                    semester_id: activeSemester.semester_id
                })
            }).then(r => r.json());

            console.log('Auto form generation response:', response);
        }
    } catch (error) {
        console.error('Failed to auto-generate clearance form for new user:', error);
    }
}

    // Toggle faculty section visibility
    window.toggleFacultySection = function() {
        const isAlsoFaculty = document.getElementById('isAlsoFaculty');
        const facultySection = document.getElementById('facultyFieldsSection');
        const facultyEmploymentStatus = document.getElementById('facultyEmploymentStatus');
        const employeeId = document.getElementById('employeeId');
        const facultyEmployeeNumber = document.getElementById('facultyEmployeeNumber');
        
        if (!isAlsoFaculty || !facultySection || !facultyEmploymentStatus || !facultyEmployeeNumber) {
            return;
        }
        
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

    // Toggle Program Head assignment section
    window.toggleProgramHeadAssignment = function() {
        const designationInput = document.getElementById('designationInput');
        const programHeadSection = document.getElementById('programHeadAssignmentSection');
        
        const title = (designationInput && designationInput.value) ? designationInput.value.trim().toLowerCase() : '';
        if (title === 'program head') {
            // Show Program Head assignment section
            programHeadSection.style.display = 'block';
            programHeadSection.style.opacity = '0';
            setTimeout(() => { programHeadSection.style.opacity = '1'; }, 10);
        } else {
            // Hide Program Head assignment section
            programHeadSection.style.opacity = '0';
            setTimeout(() => { programHeadSection.style.display = 'none'; }, 300);
            clearProgramHeadFields();
        }
    };

    // Update department checkboxes based on selected category
    window.updateDepartmentCheckboxes = function() {
        const categorySelect = document.getElementById('programHeadCategory');
        const checkboxesContainer = document.getElementById('departmentCheckboxesContainer');
        const checkboxesList = document.getElementById('departmentCheckboxesList');
        
        if (!categorySelect || !checkboxesContainer || !checkboxesList) return;
        
        const selectedCategory = categorySelect.value;
        
        if (!selectedCategory) {
            checkboxesContainer.style.display = 'none';
            checkboxesList.innerHTML = '';
            return;
        }
        
        // Load departments from API filtered by sector/category
        const url = `../../api/departments/list.php?sector=${encodeURIComponent(selectedCategory)}&include_ph=1&limit=500`;
        checkboxesList.innerHTML = '<div style="padding:8px;color:#6c757d;">Loading departments...</div>';
        
        fetch(url, { credentials: 'include' })
            .then(r => r.json())
            .then(resp => {
                checkboxesList.innerHTML = '';
                if (!resp || resp.success !== true) {
                    checkboxesList.innerHTML = '<div style="padding:8px;color:#dc3545;">Failed to load departments</div>';
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
                    
                    const disabled = !!phUserId; // lock if already has a PH
                    const option = document.createElement('div');
                    option.className = 'checkbox-option';
                    const inputId = `dept_${depId}`;
                    option.innerHTML = `
                        <input type="checkbox" id="${inputId}" name="assignedDepartments[]" value="${depId}" ${disabled ? 'disabled' : ''}>
                        <label for="${inputId}">${depName}${disabled ? ` <span style="color:#dc3545;font-size:12px;">(Assigned to ${phName})</span>` : ''}</label>
                    `;
                    checkboxesList.appendChild(option);
                });

                // Sync visual selection state with checked state (multi-select)
                const syncSelectedClass = () => {
                    const allOptions = checkboxesList.querySelectorAll('.checkbox-option');
                    allOptions.forEach(opt => {
                        const cb = opt.querySelector('input[type="checkbox"]');
                        if (cb && cb.checked) {
                            opt.classList.add('selected');
                        } else {
                            opt.classList.remove('selected');
                        }
                    });
                };
                checkboxesList.addEventListener('change', function(e){
                    if (e.target && e.target.matches('input[type="checkbox"]')) {
                        const container = e.target.closest('.checkbox-option');
                        if (container) {
                            if (e.target.checked) container.classList.add('selected');
                            else container.classList.remove('selected');
                        }
                    }
                });
                // initial sync
                syncSelectedClass();
            })
            .catch(() => {
                checkboxesList.innerHTML = '<div style="padding:8px;color:#dc3545;">Error loading departments</div>';
            })
            .finally(() => {
                checkboxesContainer.style.display = 'block';
                checkboxesContainer.style.opacity = '0';
                setTimeout(() => { checkboxesContainer.style.opacity = '1'; }, 10);
            });
    };

    // Clear Program Head fields
    function clearProgramHeadFields() {
        const categorySelect = document.getElementById('programHeadCategory');
        const checkboxesContainer = document.getElementById('departmentCheckboxesContainer');
        const checkboxesList = document.getElementById('departmentCheckboxesList');
        
        if (categorySelect) categorySelect.value = '';
        if (checkboxesContainer) checkboxesContainer.style.display = 'none';
        if (checkboxesList) checkboxesList.innerHTML = '';
    }

// Helpers: designation normalization/validation + suggestions
function normalizeDesignation(name){
    name = (name || '').trim();
    return name.replace(/\s+/g,' ');
}

function isValidDesignation(name){
    if (name.length < 2 || name.length > 50) return false;
    return /^[A-Za-z0-9 \-\/&'.\.]+$/.test(name);
}

function updateCreateHint(){
    const input = document.getElementById('designationInput');
    const list = document.getElementById('designationOptions');
    const hint = document.getElementById('designationCreateHint');
    if (!input || !list || !hint) return;
    const value = normalizeDesignation(input.value || '');
    if (!value || !isValidDesignation(value)) { hint.style.display='none'; hint.textContent=''; return; }
    const exists = Array.from(list.options).some(opt => (opt.value || '').toLowerCase() === value.toLowerCase());
    if (exists) { hint.style.display='none'; hint.textContent=''; return; }
    hint.style.display='block';
    hint.textContent = `Press Enter to create "${value}"`;
}

async function createDesignation(name){
    try{
        const res = await fetch('../../api/users/designations.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
            body: JSON.stringify({ designation_name: name })
        });
        const data = await res.json();
        if (!data || data.success !== true) {
            showToast('Failed to create designation. You might need admin permission.', 'error');
            return;
        }
        // Add to datalist immediately and select
        const list = document.getElementById('designationOptions');
        if (list) {
            const opt = document.createElement('option');
            opt.value = (data.designation && data.designation.designation_name) ? data.designation.designation_name : name;
            list.appendChild(opt);
        }
        // Also add to additional-designation select if present
        const addSel = document.getElementById('additionalDesignationSelect');
        if (addSel && data.designation) {
            // prevent duplicate
            const exists = Array.from(addSel.options).some(o => (o.value+'') === (data.designation.designation_id+''));
            if (!exists) {
                const newOpt = document.createElement('option');
                newOpt.value = data.designation.designation_id;
                newOpt.text = data.designation.designation_name;
                addSel.appendChild(newOpt);
            }
        }
        const designationInput = document.getElementById('designationInput');
        designationInput.value = (data.designation && data.designation.designation_name) ? data.designation.designation_name : name;
        updateCreateHint();
        toggleProgramHeadAssignment();
        showToast('Designation created', 'success');
    }catch(e){
        showToast('Error creating designation', 'error');
    }
}

function fetchDesignations(q){
    const list = document.getElementById('designationOptions');
    if (!list) return;
    const url = '../../api/users/designations.php' + (q ? ('?q=' + encodeURIComponent(q)) : '');
    fetch(url, { credentials: 'include' })
        .then(r=>r.json())
        .then(data=>{
            if (!data || data.success !== true) return;
            // Populate datalist for primary designation (names)
            list.innerHTML = '';
            (data.designations || []).forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.designation_name;
                list.appendChild(opt);
            });
            updateCreateHint();
        })
        .catch(()=>{});
}

// Populate the additional-designation select with a full (unfiltered) list
function populateAdditionalDesignationSelect() {
    const sel = document.getElementById('additionalDesignationSelect');
    if (!sel) return;
    const url = '../../api/users/designations.php';
    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data || data.success !== true) return;
            sel.innerHTML = '';
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.text = 'Select a designation...';
            sel.appendChild(placeholderOption);
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

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const employeeIdInput = document.getElementById('employeeId');
    if (employeeIdInput) {
        employeeIdInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            
            // Auto-fill faculty employee number if faculty section is visible
            const facultyEmployeeNumber = document.getElementById('facultyEmployeeNumber');
            const isAlsoFaculty = document.getElementById('isAlsoFaculty');
            if (isAlsoFaculty && isAlsoFaculty.checked && facultyEmployeeNumber) {
                facultyEmployeeNumber.value = this.value;
            }
        });
    }
    
    const contactInput = document.getElementById('staffContact');
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
    
    // Designation input: suggestions + PH toggle
    const designationInput = document.getElementById('designationInput');
    if (designationInput) {
        let suggestTimer = null;
        const run = () => {
            const q = designationInput.value.trim();
            fetchDesignations(q);
            toggleProgramHeadAssignment();
            updateCreateHint();
        };
        designationInput.addEventListener('input', function(){
            if (suggestTimer) clearTimeout(suggestTimer);
            suggestTimer = setTimeout(run, 200);
        });
        // initial load
        fetchDesignations('');
        toggleProgramHeadAssignment();
        updateCreateHint();

        // Handle Enter to create when valid and not existing
        designationInput.addEventListener('keydown', async function(e){
            if (e.key === 'Enter') {
                const value = normalizeDesignation(designationInput.value || '');
                if (!isValidDesignation(value)) { return; }

                // Check if current suggestions contain exact match
                const list = document.getElementById('designationOptions');
                let exists = false;
                if (list) {
                    exists = Array.from(list.options).some(opt => (opt.value || '').toLowerCase() === value.toLowerCase());
                }
                if (exists) { return; }

                e.preventDefault();
                await createDesignation(value);
            }
        });
    }
});

// --- Additional designation helpers (global) ---
// additionalDesignations holds objects { designation_id, designation_name }
window.additionalDesignations = [];

window.addAdditionalDesignation = function() {
    const sel = document.getElementById('additionalDesignationSelect');
    if (!sel) return;
    const val = sel.value;
    const text = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';

    if (!val) {
        showToast('Please select a designation', 'error');
        return;
    }

    const desigId = parseInt(val, 10);
    if (isNaN(desigId)) {
        showToast('Invalid designation selected', 'error');
        return;
    }

    if (window.additionalDesignations.some(d => d.designation_id === desigId)) {
        showToast('This designation is already added', 'warning');
        return;
    }

    window.additionalDesignations.push({ designation_id: desigId, designation_name: text });
    sel.value = '';
    renderAdditionalDesignations();
};

window.removeAdditionalDesignation = function(designationName) {
    window.additionalDesignations = window.additionalDesignations.filter(d => (d.designation_name || '') !== designationName);
    renderAdditionalDesignations();
};

function renderAdditionalDesignations() {
    const container = document.getElementById('designationsList');
    if (!container) return;

    container.innerHTML = window.additionalDesignations.map(d => `
        <span class="chip" style="padding: 6px 12px; background: #e9ecef; border-radius: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            ${d.designation_name}
            <button type="button" onclick="removeAdditionalDesignation('${d.designation_name.replace(/'/g, "\\'") }')" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 16px; padding: 0;">
                ×
            </button>
        </span>
    `).join('');
}
</script> 