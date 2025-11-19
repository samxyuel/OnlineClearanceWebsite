<?php
// SHS Edit Student Modal - Edit Existing Senior High School Student
// This modal is specifically for Senior High School students only
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">
<?php include __DIR__ . '/GeneratedCredentialsModal.php'; ?>

<div class="modal-overlay edit-student-modal-overlay" id="editStudentModal">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeEditStudentModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">✏️ Edit Senior High School Student Information</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Update senior high school student information and account settings.</div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="editStudentForm" class="modal-form" data-endpoint="../../controllers/updateUsers.php">
        <input type="hidden" name="type" value="student">
        <input type="hidden" name="sector" value="senior_high">
        <input type="hidden" id="editStudentId" name="studentId">
        
        <!-- Student Number (Read-only) -->
        <div class="form-group">
          <label for="editStudentNumber">Student Number</label>
          <input type="text" id="editStudentNumber" name="studentNumber" readonly 
                 style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Department is fixed for SHS, so it's a hidden field -->
        <input type="hidden" id="editDepartment" name="department" value="Senior High School"> <!-- This might be for display/legacy, we'll use departmentId for submission -->
        <input type="hidden" id="editDepartmentId" name="departmentId"> <!-- This will hold the actual department ID -->
        <!-- Program (SHS Only, Editable) -->
        <div class="form-group">
          <label for="editProgram">Program *</label>
          <select id="editProgram" name="program" required onchange="updateDepartmentFromProgram()">
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
          <input type="text" id="editGeneratedSection" name="generatedSection" readonly placeholder="e.g., 11/2-1" style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
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
    
    // Check if select element exists before proceeding
    if (!select) {
        console.error(`[SHSEditStudentModal] Element with id "${selectId}" not found`);
        return;
    }
    
    try {
        select.innerHTML = `<option value="">Loading...</option>`;
        const response = await fetch(url, { credentials: 'include' });
        const data = await response.json();

        select.innerHTML = `<option value="">${placeholder}</option>`;
        if (data.success && data.options) {
            data.options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = typeof option === 'object' ? option[valueField] : option;
                if (typeof option === 'object' && option.department_id) optionElement.dataset.departmentId = option.department_id;
                optionElement.textContent = typeof option === 'object' ? option[textField] : option;
                select.appendChild(optionElement);
            });
        }
    } catch (error) {
        console.error(`[SHSEditStudentModal] Error loading options for ${selectId}:`, error);
        if (select) {
            select.innerHTML = `<option value="">Error loading</option>`;
        }
    }
}

async function loadEditSHSPrograms() {
    try {
        const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
        url.searchParams.append('type', 'programs');
        url.searchParams.append('sector', 'Senior High School');
        await populateSelect('editProgram', url, 'Select Program', 'program_id', 'program_name');
    } catch (error) {
        console.error('[SHSEditStudentModal] Error loading SHS programs:', error);
    }
}

async function loadEditSHSYearLevels() {
    try {
        const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
        url.searchParams.append('type', 'enum');
        url.searchParams.append('table', 'students');
        url.searchParams.append('column', 'year_level');
        url.searchParams.append('sector', 'Senior High School');
        await populateSelect('editYearLevel', url, 'Select Year Level');
    } catch (error) {
        console.error('[SHSEditStudentModal] Error loading SHS year levels:', error);
    }
}

async function updateEditProgramsAndYearLevels() {
    // For SHS, programs and year levels are independent of the department (which is fixed)
    try {
        await Promise.all([
            loadEditSHSPrograms(),
            loadEditSHSYearLevels()
        ]);
    } catch (error) {
        console.error('[SHSEditStudentModal] Error updating programs and year levels:', error);
        // Don't throw - allow the form to continue loading even if dropdowns fail
    }
}

function updateDepartmentFromProgram() {
    const programSelect = document.getElementById('editProgram');
    const selectedOption = programSelect.options[programSelect.selectedIndex];
    const departmentIdField = document.getElementById('editDepartmentId');
    if (selectedOption && selectedOption.dataset.departmentId) {
        departmentIdField.value = selectedOption.dataset.departmentId;
    }
}
// --- Password Reset Logic ---

function handlePasswordReset() {
    const userId = document.getElementById('editStudentForm').dataset.userId;
    const username = document.getElementById('editStudentNumber').value;

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

// Update generated section display
function updateGeneratedSection() {
  const yearLevelSelect = document.getElementById('editYearLevel');
  const yearLevelText = yearLevelSelect.value; // e.g., "Grade 11"
  const term = document.getElementById('editSectionTerm').value;
  const sectionNumber = document.getElementById('editSectionNumber').value;
  const generatedSection = document.getElementById('editGeneratedSection');
  
  const yearLevelNum = yearLevelText ? yearLevelText.match(/\d+/)?.[0] : null;

  if (yearLevelNum && term && sectionNumber) {
    generatedSection.value = `${yearLevelNum}/${term}-${sectionNumber}`;
  } else {
    generatedSection.value = '';
  }
}

// Add event listeners for section generation
document.addEventListener('DOMContentLoaded', function() {
  const yearLevelSelect = document.getElementById('editYearLevel');
  const termSelect = document.getElementById('editSectionTerm');
  const sectionSelect = document.getElementById('editSectionNumber');
  
  if (yearLevelSelect) yearLevelSelect.addEventListener('change', updateGeneratedSection);
  if (termSelect) termSelect.addEventListener('change', updateGeneratedSection);
  if (sectionSelect) sectionSelect.addEventListener('change', updateGeneratedSection);
});

// Form validation
function validateEditStudentForm() {
  const form = document.getElementById('editStudentForm');
  
  // Check required fields
  const requiredFields = ['editProgram', 'editYearLevel', 'editSectionTerm', 'editSectionNumber', 'editAccountStatus'];
  
  for (const field of requiredFields) {
    const input = form.querySelector(`#${field}`);
    if (!input.value.trim()) {
      showToastNotification(`Please fill in the ${field.replace('edit', '').replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
      input.focus();
      return false;
    }
  }
  
  // Password reset is handled separately via handlePasswordReset() function
  
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
window.closeEditStudentModal = function() {
  console.log('[SHSEditStudentModal] closeEditStudentModal() called');
  try {
    const modal = document.getElementById('editStudentModal');
    if (!modal) {
      console.warn('[SHSEditStudentModal] Modal not found');
      return;
    }
    console.log('[SHSEditStudentModal] Closing modal:', modal.id);

    // Use window.closeModal if available, otherwise fallback
    if (typeof window.closeModal === 'function') {
      window.closeModal('editStudentModal');
    } else {
      // Fallback to direct manipulation
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
      document.body.classList.remove('modal-open');
      modal.classList.remove('active');
    }
    
    // Reset form
    const form = document.getElementById('editStudentForm');
    if (form) form.reset();
    
    // Form reset is handled by form.reset() above
  } catch (error) {
    // Silent error handling
  }
};

// Open modal function (called from parent page) - Make globally available
window.openEditStudentModal = function(studentId) {
  try {
    const modal = document.getElementById('editStudentModal');
    if (!modal) {
      if (typeof showToastNotification === 'function') {
        showToastNotification('Edit student modal not found. Please refresh the page.', 'error');
      }
      return;
    }

    // Use window.openModal if available, otherwise fallback
    if (typeof window.openModal === 'function') {
      window.openModal('editStudentModal');
    } else {
      // Fallback to direct manipulation
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
      requestAnimationFrame(() => {
        modal.classList.add('active');
      });
    }

    // Load student data
    if (studentId) {
      loadStudentData(studentId);
    }
  } catch (error) {
    if (typeof showToastNotification === 'function') {
      showToastNotification('Unable to open edit student modal. Please try again.', 'error');
    }
  }
};

// Load student data for editing
async function loadStudentData(userId) {
    const form = document.getElementById('editStudentForm');
    if (!form) {
        console.error('[SHSEditStudentModal] Edit student form not found');
        return;
    }
    
    const submitBtn = document.getElementById('editSubmitBtn');
    form.classList.add('loading');

    // Wait a bit for modal to be fully rendered before accessing elements
    await new Promise(resolve => setTimeout(resolve, 100));

    // Ensure dropdowns are populated before setting values
    await updateEditProgramsAndYearLevels();

    submitBtn.disabled = true;

    try {
        // Fetch student data from the API using the user_id
        const response = await fetch(`../../api/users/get_student.php?user_id=${userId}`, {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success && data.student) {
            const student = data.student;

            // Store user_id in form dataset for password reset
            if (form) form.dataset.userId = student.user_id;

            // Populate form fields
            document.getElementById('editStudentId').value = student.user_id;
            document.getElementById('editStudentNumber').value = student.student_id;
            document.getElementById('editLastName').value = student.last_name;
            document.getElementById('editFirstName').value = student.first_name;
            document.getElementById('editMiddleName').value = student.middle_name || '';
            document.getElementById('editEmail').value = student.email || '';
            document.getElementById('editContactNumber').value = student.contact_number || '';
            document.getElementById('editAccountStatus').value = student.account_status || 'inactive';

            // Set department, program, and year level after options are loaded
            document.getElementById('editProgram').value = student.program_id;
            document.getElementById('editYearLevel').value = student.year_level;

            // Trigger department update based on the selected program
            updateDepartmentFromProgram();

            // Populate section fields by parsing the section string
            const sectionParts = (student.section || '').split('/');
            if (sectionParts.length === 2 && sectionParts[1].includes('-')) {
                const yearLevelNum = sectionParts[0];
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
