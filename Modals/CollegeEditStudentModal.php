<?php
// College Edit Student Modal - Edit Existing College Student
// This modal is specifically for College students only
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay edit-student-modal-overlay" id="collegeEditStudentModal" style="display: none;">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeEditStudentModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">✏️ Edit College Student Information</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Update college student information and account settings.</div>
    
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
        
        <!-- Department (College Only, Editable for Admin, View-only for Program Head) -->
        <div class="form-group" id="editDepartmentGroup">
          <label for="editDepartment">Department *</label>
          <select id="editDepartment" name="department" required onchange="handleDepartmentChange()"></select>
          <small id="editDepartmentHelp" class="form-help" style="display: none;">
            <i class="fas fa-lock"></i> Department assignments are managed by administrators
          </small>
        </div>
        
        <!-- Program (College Only, Editable) -->
        <div class="form-group" id="editProgramGroup">
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
                 placeholder="e.g., 1/1-1" maxlength="10">
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
          <input type="text" id="editGeneratedSection" name="generatedSection" readonly 
                 placeholder="e.g., 3/2-1" style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
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
// ============================================
// GLOBAL STATE VARIABLES (Fix 8)
// ============================================
window.editStudentCurrentUserId = null;
window.editStudentDepartmentsData = [];

// ============================================
// ROLE-BASED ACCESS CONTROL (Fix 4)
// ============================================
/**
 * Detects if the current user is a Program Head (restricted mode)
 * Program Heads cannot change department assignments
 */
function isEditStudentRestrictedMode() {
    return (
        typeof DEPARTMENT_IDS !== 'undefined' &&
        Array.isArray(DEPARTMENT_IDS) &&
        DEPARTMENT_IDS.length > 0
    );
}

/**
 * Apply role-based UI restrictions
 */
function applyEditStudentRoleRestrictions() {
    const isRestricted = isEditStudentRestrictedMode();
    
    const departmentSelect = document.getElementById('editDepartment');
    const departmentHelp = document.getElementById('editDepartmentHelp');
    
    if (isRestricted) {
        // Program Head: Disable department only
        if (departmentSelect) {
            departmentSelect.disabled = true;
            departmentSelect.style.cursor = 'not-allowed';
            departmentSelect.style.opacity = '0.7';
        }
        if (departmentHelp) departmentHelp.style.display = 'block';
        
        // Program dropdown stays ENABLED but filtered to assigned departments
    } else {
        // Admin: Enable all
        if (departmentSelect) {
            departmentSelect.disabled = false;
            departmentSelect.style.cursor = '';
            departmentSelect.style.opacity = '';
        }
        if (departmentHelp) departmentHelp.style.display = 'none';
    }
}

// ============================================
// DYNAMIC DROPDOWN POPULATION
// ============================================
// populateCollegeEditSelect function - Updated 2024-12-26 to use innerHTML instead of appendChild
// Renamed from populateSelect to avoid conflicts with ExportModal.php
async function populateCollegeEditSelect(selectId, url, placeholder, valueField = 'value', textField = 'text') {
    console.log(`[CollegeEditStudentModal] populateCollegeEditSelect v2 called for: ${selectId}`);
    
    // Helper function to safely get the select element
    const getSelectElement = () => {
        const element = document.getElementById(selectId);
        if (!element) {
            console.error(`[CollegeEditStudentModal] Select element not found: ${selectId}`);
            return null;
        }
        if (!(element instanceof HTMLSelectElement)) {
            console.error(`[CollegeEditStudentModal] Element ${selectId} is not a select element:`, element);
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
            console.error(`[CollegeEditStudentModal] Select element ${selectId} not found after retry`);
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
                    optionsHTML += `<option value="${escapedValue}">${escapedText}</option>`;
                } catch (optError) {
                    console.error(`[CollegeEditStudentModal] Error processing option:`, optError, option);
                }
            });
        }
        
        // Set all options at once using innerHTML - NO appendChild
        selectElement.innerHTML = optionsHTML;
        
        console.log(`[CollegeEditStudentModal] Successfully populated ${selectId} with ${data.options?.length || 0} options`);
        
    } catch (error) {
        console.error(`[CollegeEditStudentModal] Error loading options for ${selectId}:`, error);
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
    url.searchParams.append('sector', 'College');
    await populateCollegeEditSelect('editDepartment', url, 'Select Department', 'value', 'text');
}

async function loadEditPrograms(departmentId = '') {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'programs');
    url.searchParams.append('sector', 'College');
    
    // Fix 5: Program dropdown filtering for Program Heads
    if (isEditStudentRestrictedMode()) {
        // Program Head: API will automatically filter by logged-in user's assigned departments
        // No need to pass department_id - the API handles this based on auth
    } else if (departmentId) {
        // Admin: Use selected department
        url.searchParams.append('department_id', departmentId);
    }
    
    await populateCollegeEditSelect('editProgram', url, 'Select Program', 'value', 'text');
}

async function loadEditYearLevels() {
    const url = new URL(`../../api/clearance/get_filter_options.php`, window.location.href);
    url.searchParams.append('type', 'enum');
    url.searchParams.append('table', 'students');
    url.searchParams.append('column', 'year_level');
    await populateCollegeEditSelect('editYearLevel', url, 'Select Year Level');
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
  const yearLevelText = yearLevelSelect ? yearLevelSelect.value : '';
  const term = document.getElementById('editSectionTerm')?.value || '';
  const sectionNumber = document.getElementById('editSectionNumber')?.value || '';
  const generatedSection = document.getElementById('editGeneratedSection');
  
  // Extract the number from the year level text (e.g., "1st Year" -> "1")
  const yearLevelNum = yearLevelText ? yearLevelText.match(/\d+/)?.[0] : null;

  if (yearLevelNum && term && sectionNumber && generatedSection) {
    generatedSection.value = `${yearLevelNum}/${term}-${sectionNumber}`;
  } else if (generatedSection) {
    generatedSection.value = '';
  }
}
*/ ?>

// Form validation (Fix 4: Role-based validation)
function validateEditStudentForm() {
  const form = document.getElementById('editStudentForm');
  const isRestricted = isEditStudentRestrictedMode();
  
  // Required fields - Program Heads don't need department/program validation (they're disabled)
  const requiredFields = isRestricted 
    ? ['editProgram', 'editYearLevel', 'editSection', 'editAccountStatus']
    : ['editDepartment', 'editProgram', 'editYearLevel', 'editSection', 'editAccountStatus'];
  
  for (const field of requiredFields) {
    const input = form.querySelector(`#${field}`);
    if (!input || !input.value.trim()) {
      const fieldName = field.replace('edit', '').replace(/([A-Z])/g, ' $1').toLowerCase().trim();
      showToastNotification(`Please fill in the ${fieldName}`, 'error');
      if (input) input.focus();
      return false;
    }
  }
  
  // Validate email format if provided
  const email = document.getElementById('editEmail')?.value || '';
  if (email.trim() !== '') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showToastNotification('Please enter a valid email address', 'error');
      document.getElementById('editEmail')?.focus();
      return false;
    }
  }
  
  return true;
}

// Submit form (Fix 6: Event dispatching)
function submitEditStudentForm() {
  if (!validateEditStudentForm()) {
    return;
  }
  
  const form = document.getElementById('editStudentForm');
  const formData = new FormData(form);
  const submitBtn = document.getElementById('editSubmitBtn');
  const studentNumber = document.getElementById('editStudentNumber')?.value || '';
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Updating...';
  
  fetch(form.dataset.endpoint, {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      showToastNotification('Student updated successfully!', 'success');
      closeEditStudentModal();
      
      // Fix 6: Dispatch custom event for parent page refresh
      document.dispatchEvent(new CustomEvent('student-updated', {
        detail: { 
          student_number: studentNumber, 
          user_id: window.editStudentCurrentUserId 
        }
      }));
      
      // Also call loadStudentsData if available (legacy support)
      if (typeof loadStudentsData === 'function') {
        loadStudentsData();
      }
    } else {
      showToastNotification(data.message || 'Failed to update student', 'error');
    }
  })
  .catch(error => {
    console.error('[CollegeEditStudentModal] Error:', error);
    showToastNotification('An error occurred while updating the student', 'error');
  })
  .finally(() => {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Update Student';
  });
}

// Close modal (Fix 8: State cleanup)
window.closeEditStudentModal = function() {
  console.log('[CollegeEditStudentModal] closeEditStudentModal() called');
  try {
    const modal = document.getElementById('collegeEditStudentModal');
    if (!modal) {
      console.warn('[CollegeEditStudentModal] Modal not found');
      return;
    }

    if (typeof window.closeModal === 'function') {
      window.closeModal('collegeEditStudentModal');
    } else {
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
      document.body.classList.remove('modal-open');
      modal.classList.remove('active');
    }
    
    // Reset form and state
    const form = document.getElementById('editStudentForm');
    if (form) {
      form.reset();
      form.dataset.userId = '';
    }
    
    // Clear global state
    window.editStudentCurrentUserId = null;
    
  } catch (error) {
    console.error('[CollegeEditStudentModal] Error closing modal:', error);
  }
};

// Open modal function (Fix 3: userId validation)
window.openEditStudentModal = function(userId) {
  console.log('[CollegeEditStudentModal] openEditStudentModal called with userId:', userId);
  
  // Fix 3: Validate userId before proceeding
  if (!userId || userId === 'undefined' || userId === 'null') {
    console.error('[CollegeEditStudentModal] Invalid userId:', userId);
    if (typeof showToastNotification === 'function') {
      showToastNotification('Cannot edit student: Invalid user ID.', 'error');
    }
    return;
  }
  
  try {
    const modal = document.getElementById('collegeEditStudentModal');
    
    if (!modal) {
      console.error('[CollegeEditStudentModal] Modal element not found');
      if (typeof showToastNotification === 'function') {
        showToastNotification('Edit student modal not found. Please refresh the page.', 'error');
      }
      return;
    }
    
    // Store userId in global state
    window.editStudentCurrentUserId = userId;
    
    // Open the modal
    if (typeof window.openModal === 'function') {
      window.openModal('collegeEditStudentModal');
    } else {
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
      requestAnimationFrame(() => {
        modal.classList.add('active');
      });
    }

    // Wait for modal to be fully rendered before loading data
    // Use requestAnimationFrame to ensure DOM is ready
    requestAnimationFrame(() => {
      setTimeout(() => {
        loadStudentData(userId);
      }, 100);
    });
    
  } catch (error) {
    console.error('[CollegeEditStudentModal] Error opening modal:', error);
    if (typeof showToastNotification === 'function') {
      showToastNotification('Unable to open edit student modal. Please try again.', 'error');
    }
  }
};

// Load student data (Fix 1 & 2: Error handling and HTTP response validation)
async function loadStudentData(userId) {
    const form = document.getElementById('editStudentForm');
    const submitBtn = document.getElementById('editSubmitBtn');
    
    // Ensure form elements exist before proceeding
    if (!form) {
        console.error('[CollegeEditStudentModal] Form element not found');
        if (typeof showToastNotification === 'function') {
            showToastNotification('Form elements not found. Please refresh the page.', 'error');
        }
        return;
    }
    
    // Fix 1: Wrap EVERYTHING in try-catch
    try {
        console.log('[CollegeEditStudentModal] Loading data for userId:', userId);
        
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
        
        // Fix 1: Move dropdown loading INSIDE try-catch
        await Promise.all([loadEditDepartments(), loadEditYearLevels()]);
        
        // Apply role restrictions after dropdowns are loaded
        applyEditStudentRoleRestrictions();
        
        // Fetch student data from the API
        const response = await fetch(`../../api/users/get_student.php?user_id=${userId}`, {
            credentials: 'include'
        });
        
        // Fix 2: Check response.ok before parsing
        if (!response.ok) {
            const errorText = await response.text();
            console.error('[CollegeEditStudentModal] API error response:', errorText);
            throw new Error(`Failed to fetch student data. Status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[CollegeEditStudentModal] API response:', data);

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
                await updateEditProgramsAndYearLevels();
            }

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

            <?php /* COMMENTED OUT: Section parsing logic
            // Populate section fields from section string (e.g., "3/2-1")
            if (student.section) {
                const sectionParts = student.section.split('/');
                if (sectionParts.length === 2 && sectionParts[1].includes('-')) {
                    const termAndSection = sectionParts[1].split('-');
                    document.getElementById('editSectionTerm').value = termAndSection[0] || '';
                    document.getElementById('editSectionNumber').value = termAndSection[1] || '';
                }
            }

            updateGeneratedSection();
            */ ?>
            
            console.log('[CollegeEditStudentModal] Form populated successfully');

        } else {
            throw new Error(data.message || 'Student not found or failed to load data.');
        }
        
    } catch (error) {
        console.error('[CollegeEditStudentModal] Error loading student data:', error);
        
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

// ============================================
// EVENT LISTENERS (Fix 9: Year Level handler)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Department change handler
    const departmentSelect = document.getElementById('editDepartment');
    if (departmentSelect) {
        departmentSelect.addEventListener('change', handleDepartmentChange);
    }
    
    <?php /* COMMENTED OUT: Section generation event listeners
    // Section generation handlers (Fix 9: Added year level)
    const termSelect = document.getElementById('editSectionTerm');
    const sectionSelect = document.getElementById('editSectionNumber');
    const yearLevelSelect = document.getElementById('editYearLevel');
    
    if (termSelect) termSelect.addEventListener('change', updateGeneratedSection);
    if (sectionSelect) sectionSelect.addEventListener('change', updateGeneratedSection);
    if (yearLevelSelect) yearLevelSelect.addEventListener('change', updateGeneratedSection);
    */ ?>
});
</script>
