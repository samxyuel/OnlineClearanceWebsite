<?php
// College Edit Student Modal - Edit Existing College Student
// This modal is specifically for College students only
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay edit-student-modal-overlay" id="editStudentModal">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeEditStudentModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">✏️ Edit College Student Information</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Update college student information and account settings.</div>

    <!-- Close Button -->
    <button class="modal-close" onclick="closeEditStudentModal()">&times;</button>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="editStudentForm" class="modal-form" data-endpoint="../../controllers/updateUsers.php">
        <input type="hidden" name="type" value="student">
        <input type="hidden" name="sector" value="college">
        <input type="hidden" id="editStudentId" name="studentId">
        
        <!-- Student Number (Read-only) -->
        <div class="form-group">
          <label for="editStudentNumber">Student Number</label>
          <input type="text" id="editStudentNumber" name="studentNumber" readonly 
                 style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Department (College Only, Editable) -->
        <div class="form-group">
          <label for="editDepartment">Department *</label>
          <select id="editDepartment" name="department" required onchange="handleDepartmentChange()"></select>
        </div>
        
        <!-- Program (College Only, Editable) -->
        <div class="form-group">
          <label for="editProgram">Program *</label>
          <select id="editProgram" name="program" required>
            <option value="">Select Program</option>
          </select>
        </div>
        
        <!-- Year Level (Editable) -->
        <div class="form-group">
          <label for="editYearLevel">Year Level *</label>
          <select id="editYearLevel" name="yearLevel" required>
            <option value="">Select Year Level</option>
          </select>
        </div>
        
        <!-- Term for Section (Editable) -->
        <div class="form-group">
          <label for="editSectionTerm">Term *</label>
          <select id="editSectionTerm" name="sectionTerm" required>
            <option value="">Select Term</option>
            <option value="1">1st Term</option>
            <option value="2">2nd Term</option>
          </select>
        </div>
        
        <!-- Section Number (Editable) -->
        <div class="form-group">
          <label for="editSectionNumber">Section Number *</label>
          <select id="editSectionNumber" name="sectionNumber" required>
            <option value="">Select Section</option>
            <option value="1">Section 1</option>
            <option value="2">Section 2</option>
            <option value="3">Section 3</option>
            <option value="4">Section 4</option>
            <option value="5">Section 5</option>
            <option value="6">Section 6</option>
          </select>
        </div>
        
        <!-- Generated Section Display (Read-only) -->
        <div class="form-group">
          <label>Generated Section Format</label>
          <input type="text" id="editGeneratedSection" name="generatedSection" readonly 
                 placeholder="e.g., 3/2-1" style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Last Name (Read-only) -->
        <div class="form-group">
          <label for="editLastName">Last Name</label>
          <input type="text" id="editLastName" name="lastName" readonly 
                 style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- First Name (Read-only) -->
        <div class="form-group">
          <label for="editFirstName">First Name</label>
          <input type="text" id="editFirstName" name="firstName" readonly 
                 style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Middle Name (Read-only) -->
        <div class="form-group">
          <label for="editMiddleName">Middle Name</label>
          <input type="text" id="editMiddleName" name="middleName" readonly 
                 style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Email (Editable) -->
        <div class="form-group">
          <label for="editEmail">Email</label>
          <input type="email" id="editEmail" name="email" placeholder="Enter email address">
        </div>
        
        <!-- Contact Number (Editable) -->
        <div class="form-group">
          <label for="editContactNumber">Contact Number</label>
          <input type="tel" id="editContactNumber" name="contactNumber" placeholder="e.g., +63 912 345 6789">
        </div>
        
        <!-- Account Status (Editable) -->
        <div class="form-group">
          <label for="editAccountStatus">Account Status *</label>
          <select id="editAccountStatus" name="accountStatus" required>
            <option value="">Select Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="graduated">Graduated</option>
          </select>
        </div>
        
        <!-- Password Section -->
        <div class="form-section">
          <h3 class="form-section-title">Password Management</h3>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="editChangePassword" onchange="togglePasswordFields()">
              <span class="checkmark"></span>
              Change password
            </label>
          </div>
          
          <div id="passwordFields" style="display: none;">
            <div class="form-group">
              <label for="editNewPassword">New Password *</label>
              <input type="password" id="editNewPassword" name="newPassword" 
                     placeholder="Enter new password" minlength="8">
              <small class="form-help">Minimum 8 characters</small>
            </div>
            
            <div class="form-group">
              <label for="editConfirmNewPassword">Confirm New Password *</label>
              <input type="password" id="editConfirmNewPassword" name="confirmNewPassword" 
                     placeholder="Confirm new password" minlength="8">
            </div>
          </div>
        </div>
        
        <!-- Account Actions -->
        <div class="form-section">
          <h3 class="form-section-title">Account Actions</h3>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="editSendNotification" name="sendNotification">
              <span class="checkmark"></span>
              Send notification email about changes
            </label>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeEditStudentModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitEditStudentForm()" id="editSubmitBtn">Update Student</button>
    </div>
  </div>
</div>

<script>
// --- Dynamic Filter Population ---
async function populateSelect(selectId, url, placeholder, valueField = 'value', textField = 'text') {
    const select = document.getElementById(selectId);
    try {
        select.innerHTML = `<option value="">Loading...</option>`;
        const response = await fetch(url, { credentials: 'include' });
        const data = await response.json();

        select.innerHTML = `<option value="">${placeholder}</option>`;
        if (data.success && data.options) {
            data.options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = typeof option === 'object' ? option[valueField] : option;
                optionElement.textContent = typeof option === 'object' ? option[textField] : option;
                select.appendChild(optionElement);
            });
        }
    } catch (error) {
        console.error(`Error loading options for ${selectId}:`, error);
        select.innerHTML = `<option value="">Error loading</option>`;
    }
}

async function loadEditDepartments() {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'departments');
    url.searchParams.append('sector', 'College');
    await populateSelect('editDepartment', url, 'Select Department', 'value', 'text');
}

async function loadEditPrograms(departmentId = '') {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'programs');
    url.searchParams.append('sector', 'College');
    if (departmentId) {
        url.searchParams.append('department_id', departmentId);
    }
    await populateSelect('editProgram', url, 'Select Program', 'value', 'text');
}

async function loadEditYearLevels() {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'enum');
    url.searchParams.append('table', 'students');
    url.searchParams.append('column', 'year_level');
    await populateSelect('editYearLevel', url, 'Select Year Level');
}

async function updateEditProgramsAndYearLevels() {
    const departmentId = document.getElementById('editDepartment').value;
    // Load programs based on the selected department.
    await loadEditPrograms(departmentId);
    // Year levels are independent of the department for College.
}

function handleDepartmentChange() {
    const departmentId = document.getElementById('editDepartment').value;
    loadEditPrograms(departmentId);
}

document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('editDepartment');
    if (departmentSelect) {
        departmentSelect.addEventListener('change', handleDepartmentChange);
    }
});

// Toggle password fields
function togglePasswordFields() {
  const changePassword = document.getElementById('editChangePassword');
  const passwordFields = document.getElementById('passwordFields');
  const newPassword = document.getElementById('editNewPassword');
  const confirmNewPassword = document.getElementById('editConfirmNewPassword');
  
  if (changePassword.checked) {
    passwordFields.style.display = 'block';
    newPassword.required = true;
    confirmNewPassword.required = true;
  } else {
    passwordFields.style.display = 'none';
    newPassword.required = false;
    confirmNewPassword.required = false;
    newPassword.value = '';
    confirmNewPassword.value = '';
  }
}

// Update generated section display
function updateGeneratedSection() {
  const yearLevelSelect = document.getElementById('editYearLevel');
  const yearLevelText = yearLevelSelect.value; // e.g., "1st Year"
  const term = document.getElementById('editSectionTerm').value;
  const sectionNumber = document.getElementById('editSectionNumber').value;
  const generatedSection = document.getElementById('editGeneratedSection');
  
  // Extract the number from the year level text (e.g., "1st Year" -> "1")
  const yearLevelNum = yearLevelText ? yearLevelText.match(/\d+/)?.[0] : null;

  if (yearLevelNum && term && sectionNumber) {
    generatedSection.value = `${yearLevelNum}/${term}-${sectionNumber}`;
  } else {
    generatedSection.value = '';
  }
}

// Add event listeners for section generation
document.addEventListener('DOMContentLoaded', function() {
  const termSelect = document.getElementById('editSectionTerm');
  const sectionSelect = document.getElementById('editSectionNumber');
  
  if (termSelect) termSelect.addEventListener('change', updateGeneratedSection);
  if (sectionSelect) sectionSelect.addEventListener('change', updateGeneratedSection);
});

// Form validation
function validateEditStudentForm() {
  const form = document.getElementById('editStudentForm');
  
  // Check required fields
  const requiredFields = ['editDepartment', 'editProgram', 'editYearLevel', 'editAccountStatus'];
  
  for (const field of requiredFields) {
    const input = form.querySelector(`#${field}`);
    if (!input.value.trim()) {
      showToastNotification(`Please fill in the ${field.replace('edit', '').replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
      input.focus();
      return false;
    }
  }
  
  // Validate password if changing
  const changePassword = document.getElementById('editChangePassword');
  if (changePassword.checked) {
    const newPassword = document.getElementById('editNewPassword').value;
    const confirmNewPassword = document.getElementById('editConfirmNewPassword').value;
    
    if (newPassword !== confirmNewPassword) {
      showToastNotification('New passwords do not match', 'error');
      document.getElementById('editConfirmNewPassword').focus();
      return false;
    }
  }
  
  // Validate email format
  const email = document.getElementById('editEmail').value;
  if (email.trim() !== '') { // Only validate if an email is entered
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showToastNotification('Please enter a valid email address', 'error');
      document.getElementById('editEmail').focus();
      return false;
    }
  }
  
  return true;
}

// Submit form
function submitEditStudentForm() {
  if (!validateEditStudentForm()) {
    return;
  }
  
  const form = document.getElementById('editStudentForm');
  const formData = new FormData(form);
  const submitBtn = document.getElementById('editSubmitBtn');
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.textContent = 'Updating...';
  
  // Submit form
  fetch(form.dataset.endpoint, {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToastNotification('Student updated successfully!', 'success');
      closeEditStudentModal();
      // Refresh the student list
      if (typeof loadStudentsData === 'function') {
        loadStudentsData();
      }
    } else {
      showToastNotification(data.message || 'Failed to update student', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToastNotification('An error occurred while updating the student', 'error');
  })
  .finally(() => {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.textContent = 'Update Student';
  });
}

// Close modal
function closeEditStudentModal() {
  const modal = document.getElementById('editStudentModal');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
  
  // Reset form
  const form = document.getElementById('editStudentForm');
  form.reset();
  
  // Reset password fields
  document.getElementById('editChangePassword').checked = false;
  togglePasswordFields();
}

// Open modal function (called from parent page)
function openEditStudentModal(studentId) {
  const modal = document.getElementById('editStudentModal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  // Load student data
  loadStudentData(studentId);
}

// Load student data for editing
async function loadStudentData(userId) {
    const form = document.getElementById('editStudentForm');
    const submitBtn = document.getElementById('editSubmitBtn');
    form.classList.add('loading');

    // Ensure dropdowns are populated before setting values
    await Promise.all([loadEditDepartments(), loadEditYearLevels()]);

    submitBtn.disabled = true;

    try {
        // Fetch student data from the API using the user_id
        const response = await fetch(`../../api/users/get_student.php?user_id=${userId}`, {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success && data.student) {
            const student = data.student;

            // Populate form fields
            document.getElementById('editStudentId').value = student.user_id;
            document.getElementById('editStudentNumber').value = student.student_id;
            document.getElementById('editLastName').value = student.last_name;
            document.getElementById('editFirstName').value = student.first_name;
            document.getElementById('editMiddleName').value = student.middle_name || '';
            document.getElementById('editEmail').value = student.email || '';
            document.getElementById('editContactNumber').value = student.contact_number || '';
            document.getElementById('editAccountStatus').value = student.account_status || 'inactive';

            // Populate and select department, then trigger program/year update
            const departmentSelect = document.getElementById('editDepartment');
            departmentSelect.value = student.department_id; // Set the department
            await updateEditProgramsAndYearLevels(); // Wait for programs to load based on the department

            // Set program and year level after options are loaded
            // Now this will work correctly because the options exist.
            document.getElementById('editProgram').value = student.program_id;
            document.getElementById('editYearLevel').value = student.year_level;

            // Populate section fields
            const sectionParts = (student.section || '').split('/');
            if (sectionParts.length === 2 && sectionParts[1].includes('-')) {
                const termAndSection = sectionParts[1].split('-');
                document.getElementById('editSectionTerm').value = termAndSection[0];
                document.getElementById('editSectionNumber').value = termAndSection[1];
            }

            updateGeneratedSection(); // Update the generated section display

        } else {
            throw new Error(data.message || 'Failed to load student data.');
        }
    } catch (error) {
        console.error('Error loading student data:', error);
        showToastNotification(error.message, 'error');
        closeEditStudentModal(); // Close modal on error
    } finally {
        form.classList.remove('loading');
        submitBtn.disabled = false;
    }
}
</script>
