<?php
// SHS Edit Student Modal - Edit Existing Senior High School Student
// This modal is specifically for Senior High School students only
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay edit-student-modal-overlay" id="shsEditStudentModal" style="display: none;">
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
        
        <!-- Department (SHS Only, Editable for Admin, View-only for Program Head) -->
        <div class="form-group" id="editDepartmentGroup">
          <label for="editDepartment">Department *</label>
          <select id="editDepartment" name="department" required onchange="handleDepartmentChange()"></select>
          <small id="editDepartmentHelp" class="form-help" style="display: none;">
            <i class="fas fa-lock"></i> Department assignments are managed by administrators
          </small>
        </div>
        
        <!-- Hidden field to store department_id for submission -->
        <input type="hidden" id="editDepartmentId" name="departmentId">
        
        <!-- Program (SHS Only, Editable) -->
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
        
        <!-- Section (Editable) - Simple text input like registry modal -->
        <div class="form-group">
          <label for="editSection">Section *</label>
          <input type="text" id="editSection" name="section" required 
                 placeholder="e.g., 11/1-1" maxlength="10">
        </div>
        
        <?php /* COMMENTED OUT: Term, Section Number, and Generated Section Format fields
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
        */ ?>
        
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
// populateSHSEditSelect function - Updated to use innerHTML instead of appendChild
// Renamed from populateSelect to avoid conflicts with ExportModal.php
async function populateSHSEditSelect(selectId, url, placeholder, valueField = 'value', textField = 'text') {
    console.log(`[SHSEditStudentModal] populateSHSEditSelect v2 called for: ${selectId}`);
    
    // Helper function to safely get the select element
    const getSelectElement = () => {
        const element = document.getElementById(selectId);
        if (!element) {
            console.error(`[SHSEditStudentModal] Select element not found: ${selectId}`);
            return null;
        }
        if (!(element instanceof HTMLSelectElement)) {
            console.error(`[SHSEditStudentModal] Element ${selectId} is not a select element:`, element);
            return null;
        }
        return element;
    };
    
    // Try to get element, with retry if needed
    let selectElement = getSelectElement();
    if (!selectElement) {
        // Wait a bit and try again
        await new Promise(resolve => setTimeout(resolve, 100));
        selectElement = getSelectElement();
        if (!selectElement) {
            console.error(`[SHSEditStudentModal] Select element ${selectId} not found after retry`);
            return;
        }
    }
    
    try {
        // Set loading state using innerHTML (NO appendChild used anywhere)
        selectElement.innerHTML = `<option value="">Loading...</option>`;
        
        const response = await fetch(url, { credentials: 'include' });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();

        // Re-get element to ensure it's still valid
        selectElement = getSelectElement();
        if (!selectElement) {
            throw new Error(`Select element ${selectId} is no longer available`);
        }

        // Build options HTML string - NO appendChild, only innerHTML
        let optionsHTML = `<option value="">${placeholder}</option>`;
        
        if (data.success && data.options && Array.isArray(data.options)) {
            data.options.forEach(option => {
                try {
                    const value = typeof option === 'object' ? option[valueField] : option;
                    const text = typeof option === 'object' ? option[textField] : option;
                    // Escape HTML to prevent XSS
                    const escapedText = String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    const escapedValue = String(value || '').replace(/"/g, '&quot;');
                    
                    // Handle department_id dataset if present
                    let datasetAttr = '';
                    if (typeof option === 'object' && option.department_id) {
                        datasetAttr = ` data-department-id="${String(option.department_id).replace(/"/g, '&quot;')}"`;
                    }
                    
                    optionsHTML += `<option value="${escapedValue}"${datasetAttr}>${escapedText}</option>`;
                } catch (optError) {
                    console.error(`[SHSEditStudentModal] Error processing option:`, optError, option);
                }
            });
        }
        
        // Set all options at once using innerHTML - NO appendChild
        selectElement.innerHTML = optionsHTML;
        
        console.log(`[SHSEditStudentModal] Successfully populated ${selectId} with ${data.options?.length || 0} options`);
        
    } catch (error) {
        console.error(`[SHSEditStudentModal] Error loading options for ${selectId}:`, error);
        const errorSelect = getSelectElement();
        if (errorSelect) {
            errorSelect.innerHTML = `<option value="">Error loading options</option>`;
        }
        throw error; // Re-throw to be caught by caller
    }
}

async function loadEditDepartments() {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'departments');
    url.searchParams.append('sector', 'Senior High School');
    await populateSHSEditSelect('editDepartment', url, 'Select Department', 'value', 'text');
}

async function loadEditPrograms(departmentId = '') {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'programs');
    url.searchParams.append('sector', 'Senior High School');
    
    // If departmentId is provided, filter programs by department
    if (departmentId) {
        url.searchParams.append('department_id', departmentId);
    }
    
    // Use 'value', 'text' to match API response structure (same as College modal)
    await populateSHSEditSelect('editProgram', url, 'Select Program', 'value', 'text');
}

async function loadEditYearLevels() {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'enum');
    url.searchParams.append('table', 'students');
    url.searchParams.append('column', 'year_level');
    url.searchParams.append('sector', 'Senior High School');
    await populateSHSEditSelect('editYearLevel', url, 'Select Year Level');
}

async function updateEditProgramsAndYearLevels() {
    const departmentId = document.getElementById('editDepartment').value;
    // Load programs based on the selected department
    await loadEditPrograms(departmentId);
    // Year levels are independent of the department for SHS
}

function handleDepartmentChange() {
    const departmentId = document.getElementById('editDepartment').value;
    loadEditPrograms(departmentId);
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

<?php /* COMMENTED OUT: Section generation function
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
*/ ?>

document.addEventListener('DOMContentLoaded', function() {
    // Department change handler
    const departmentSelect = document.getElementById('editDepartment');
    if (departmentSelect) {
        departmentSelect.addEventListener('change', handleDepartmentChange);
    }
    
    <?php /* COMMENTED OUT: Section generation event listeners
    // Add event listeners for section generation
    const yearLevelSelect = document.getElementById('editYearLevel');
    const termSelect = document.getElementById('editSectionTerm');
    const sectionSelect = document.getElementById('editSectionNumber');
    
    if (yearLevelSelect) yearLevelSelect.addEventListener('change', updateGeneratedSection);
    if (termSelect) termSelect.addEventListener('change', updateGeneratedSection);
    if (sectionSelect) sectionSelect.addEventListener('change', updateGeneratedSection);
    */ ?>
});

// Form validation
function validateEditStudentForm() {
  const form = document.getElementById('editStudentForm');
  
  // Check required fields
  const requiredFields = ['editDepartment', 'editProgram', 'editYearLevel', 'editSection', 'editAccountStatus'];
  
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
    const modal = document.getElementById('shsEditStudentModal');
    if (!modal) {
      console.warn('[SHSEditStudentModal] Modal not found');
      return;
    }
    console.log('[SHSEditStudentModal] Closing modal:', modal.id);

    // Use window.closeModal if available, otherwise fallback
    if (typeof window.closeModal === 'function') {
      window.closeModal('shsEditStudentModal');
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
  console.log('[SHSEditStudentModal] ===== openEditStudentModal called =====');
  console.log('[SHSEditStudentModal] studentId:', studentId);
  
  try {
    const modal = document.getElementById('shsEditStudentModal');
    console.log('[SHSEditStudentModal] Modal element search result:', modal);
    
    if (!modal) {
      console.error('[SHSEditStudentModal] ❌ Modal element not found!');
      console.error('[SHSEditStudentModal] Searching for alternative selectors...');
      const altModal = document.querySelector('.edit-student-modal-overlay');
      console.log('[SHSEditStudentModal] Found by class selector:', altModal);
      
      if (typeof showToastNotification === 'function') {
        showToastNotification('Edit student modal not found. Please refresh the page.', 'error');
      }
      return;
    }
    
    console.log('[SHSEditStudentModal] ✅ Modal element found');
    console.log('[SHSEditStudentModal] Modal current display:', window.getComputedStyle(modal).display);
    console.log('[SHSEditStudentModal] Modal current opacity:', window.getComputedStyle(modal).opacity);
    console.log('[SHSEditStudentModal] Modal has active class:', modal.classList.contains('active'));
    console.log('[SHSEditStudentModal] Modal inline style:', modal.style.display);
    
    // Use window.openModal if available, otherwise fallback
    if (typeof window.openModal === 'function') {
      console.log('[SHSEditStudentModal] Using window.openModal()');
      window.openModal('shsEditStudentModal');
      console.log('[SHSEditStudentModal] window.openModal() called');
      
      // Verify modal is now visible
      setTimeout(() => {
        const finalDisplay = window.getComputedStyle(modal).display;
        const finalOpacity = window.getComputedStyle(modal).opacity;
        console.log('[SHSEditStudentModal] After openModal - display:', finalDisplay);
        console.log('[SHSEditStudentModal] After openModal - opacity:', finalOpacity);
        console.log('[SHSEditStudentModal] After openModal - has active class:', modal.classList.contains('active'));
        
        if (finalDisplay === 'none' || finalDisplay === '') {
          console.error('[SHSEditStudentModal] ❌ Modal still hidden! Display:', finalDisplay);
        } else {
          console.log('[SHSEditStudentModal] ✅ Modal should be visible');
        }
      }, 100);
    } else {
      console.log('[SHSEditStudentModal] window.openModal not available, using fallback');
      // Fallback to direct manipulation
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
      requestAnimationFrame(() => {
        modal.classList.add('active');
        console.log('[SHSEditStudentModal] Fallback: Added active class');
        
        // Verify
        setTimeout(() => {
          const finalDisplay = window.getComputedStyle(modal).display;
          const finalOpacity = window.getComputedStyle(modal).opacity;
          console.log('[SHSEditStudentModal] Fallback - display:', finalDisplay);
          console.log('[SHSEditStudentModal] Fallback - opacity:', finalOpacity);
        }, 50);
      });
    }

    // Load student data
    if (studentId) {
      console.log('[SHSEditStudentModal] Loading student data for:', studentId);
      loadStudentData(studentId);
    }
  } catch (error) {
    console.error('[SHSEditStudentModal] ❌ Error opening modal:', error);
    console.error('[SHSEditStudentModal] Error stack:', error.stack);
    if (typeof showToastNotification === 'function') {
      showToastNotification('Unable to open edit student modal. Please try again.', 'error');
    }
  }
};

// Load student data for editing
async function loadStudentData(userId) {
    const form = document.getElementById('editStudentForm');
    const submitBtn = document.getElementById('editSubmitBtn');
    
    // Ensure form elements exist before proceeding
    if (!form) {
        console.error('[SHSEditStudentModal] Form element not found');
        if (typeof showToastNotification === 'function') {
            showToastNotification('Form elements not found. Please refresh the page.', 'error');
        }
        return;
    }
    
    // Wrap EVERYTHING in try-catch
    try {
        console.log('[SHSEditStudentModal] Loading data for userId:', userId);
        
        // Show loading state
        form.classList.add('loading');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Loading...';
        }
        
        // Wait a bit to ensure modal elements are fully rendered
        await new Promise(resolve => setTimeout(resolve, 50));
        
        // Verify select elements exist before trying to populate
        const deptSelect = document.getElementById('editDepartment');
        const yearSelect = document.getElementById('editYearLevel');
        
        if (!deptSelect || !yearSelect) {
            throw new Error('Form select elements not found. Modal may not be fully loaded.');
        }
        
        // Load departments and year levels first
        await Promise.all([loadEditDepartments(), loadEditYearLevels()]);
        
        // Fetch student data from the API
        const response = await fetch(`../../api/users/get_student.php?user_id=${userId}`, {
            credentials: 'include'
        });
        
        // Check response.ok before parsing
        if (!response.ok) {
            const errorText = await response.text();
            console.error('[SHSEditStudentModal] API error response:', errorText);
            throw new Error(`Failed to fetch student data. Status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[SHSEditStudentModal] API response:', data);

        if (data.success && data.student) {
            const student = data.student;

            // Store user_id in form dataset for password reset
            form.dataset.userId = student.user_id;
            
            // Populate form fields
            document.getElementById('editStudentId').value = student.user_id;
            document.getElementById('editStudentNumber').value = student.student_id || '';
            document.getElementById('editLastName').value = student.last_name || '';
            document.getElementById('editFirstName').value = student.first_name || '';
            document.getElementById('editMiddleName').value = student.middle_name || '';
            document.getElementById('editEmail').value = student.email || '';
            document.getElementById('editContactNumber').value = student.contact_number || '';
            document.getElementById('editAccountStatus').value = student.account_status || 'inactive';

            // Set department and load programs
            const departmentSelect = document.getElementById('editDepartment');
            if (departmentSelect && student.department_id) {
                departmentSelect.value = student.department_id;
            }
            // Always load programs (even if no department_id, it will load all SHS programs)
            await updateEditProgramsAndYearLevels();

            // Set program and year level after options are loaded
            const programSelect = document.getElementById('editProgram');
            const yearLevelSelect = document.getElementById('editYearLevel');
            
            if (programSelect && student.program_id) {
                programSelect.value = student.program_id;
            }
            if (yearLevelSelect && student.year_level) {
                yearLevelSelect.value = student.year_level;
            }

            // Set section directly (no parsing needed)
            if (student.section) {
                document.getElementById('editSection').value = student.section;
            }
            
            console.log('[SHSEditStudentModal] Form populated successfully');

        } else {
            throw new Error(data.message || 'Student not found or failed to load data.');
        }
        
    } catch (error) {
        console.error('[SHSEditStudentModal] Error loading student data:', error);
        
        // Show error toast
        if (typeof showToastNotification === 'function') {
            showToastNotification(error.message || 'Failed to load student data', 'error');
        }
        
        // Close modal on error
        closeEditStudentModal();
        
    } finally {
        // Always restore button state
        if (form) form.classList.remove('loading');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Update Student';
        }
    }
}
</script>
