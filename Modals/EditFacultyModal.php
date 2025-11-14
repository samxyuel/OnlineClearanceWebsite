<?php // Edit Faculty Modal - Modify Existing Faculty ?>
<link rel="stylesheet" href="../../assets/css/modals.css">
<div class="modal-overlay edit-faculty-modal-overlay" id="editFacultyModal">
  <div class="modal-window">
    <button class="modal-close" onclick="window.closeEditFacultyModal && window.closeEditFacultyModal()">&times;</button>
    <h2 class="modal-title">✏️ Edit Faculty Information</h2>
    <div class="modal-supporting-text">Update faculty information and account settings.</div>
    <div class="modal-content-area">
      <form id="editFacultyForm" class="modal-form" data-endpoint="../../controllers/updateUsers.php">
        <input type="hidden" name="type" value="faculty">
        <input type="hidden" id="editFacultyId" name="facultyId">
        <div class="form-group">
          <label for="editEmployeeNumber">Employee Number</label>
          <input type="text" id="editEmployeeNumber" name="employeeNumber" readonly style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>

        <div class="form-group">
          <label for="editEmploymentStatus">Employment Status *</label>
          <select id="editEmploymentStatus" name="employmentStatus" required>
            <option value="">Select Employment Status</option>
            <option value="full-time">Full Time</option>
            <option value="part-time">Part Time</option>
            <option value="part-time-full-load">Part Time - Full Load</option>
          </select>
        </div>
        <div class="form-group">
          <label for="editLastName">Last Name</label>
          <input type="text" id="editLastName" name="lastName" readonly style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        <div class="form-group">
          <label for="editFirstName">First Name</label>
          <input type="text" id="editFirstName" name="firstName" readonly style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        <div class="form-group">
          <label for="editMiddleName">Middle Name</label>
          <input type="text" id="editMiddleName" name="middleName" readonly style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        <div class="form-group">
          <label for="editEmail">Email *</label>
          <input type="email" id="editEmail" name="email" required placeholder="Enter email address">
        </div>
        <div class="form-group">
          <label for="editContactNumber">Contact Number *</label>
          <input type="tel" id="editContactNumber" name="contactNumber" required placeholder="e.g., +63 912 345 6789">
        </div>
        <div class="form-group">
          <label for="editAccountStatus">Account Status *</label>
          <select id="editAccountStatus" name="accountStatus" required>
            <option value="">Select Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="resigned">Resigned</option>
          </select>
        </div>
        <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--light-blue-gray);">
          <label style="color: var(--deep-navy-blue); font-weight: 600;">Password Management</label>
          <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="button" class="modal-action-secondary" onclick="resetFacultyPassword()" style="flex: 1;">
              <i class="fas fa-key"></i> Reset Password
            </button>
            <!--
            <button type="button" class="modal-action-secondary" onclick="sendPasswordEmail()" style="flex: 1;">
              <i class="fas fa-envelope"></i> Send Email
            </button>
            -->
          </div>
        </div>
      </form>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="window.closeEditFacultyModal && window.closeEditFacultyModal()">Cancel</button>
      <button class="modal-action-primary" onclick="window.submitEditFacultyForm && window.submitEditFacultyForm()" id="editSubmitBtn">Update Faculty</button>
    </div>
  </div>
</div>

<script>
  // Form validation
  function validateEditFacultyForm() {
    const requiredFields = ['editEmploymentStatus', 'editEmail', 'editContactNumber', 'editAccountStatus'];
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      const value = field.value.trim();
      
      if (!value) {
        showEditFieldError(fieldId, 'This field is required');
        isValid = false;
      } else {
        showEditFieldSuccess(fieldId);
      }
    });
    
    // Email validation
    const email = document.getElementById('editEmail').value.trim();
    if (email && !isValidEmail(email)) {
      showEditFieldError('editEmail', 'Please enter a valid email address');
      isValid = false;
    }
    
    return isValid;
  }
  
  function showEditFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = field.parentNode.querySelector('.field-error') || document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '4px';
    
    if (!field.parentNode.querySelector('.field-error')) {
      field.parentNode.appendChild(errorDiv);
    }
    
    field.style.borderColor = '#dc3545';
  }
  
  function showEditFieldSuccess(fieldId) {
    const field = document.getElementById(fieldId);
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
      errorDiv.remove();
    }
    field.style.borderColor = '#28a745';
  }
  
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }
  

  
  // Load faculty data for editing
  async function populateEditFacultyForm(employeeNumber) {
    const form = document.getElementById('editFacultyForm');
    const submitBtn = document.getElementById('editSubmitBtn');
    
    if (form) form.classList.add('loading');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Loading...';
    }

    try {
      // Fetch faculty data from the API using search parameter (employee_number is searched in the API)
      // The API searches in employee_number field, so we can use search parameter
      const response = await fetch(`../../api/users/facultyList.php?search=${encodeURIComponent(employeeNumber)}&limit=1`, {
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();

      if (data.success && data.faculty && data.faculty.length > 0) {
        const faculty = data.faculty[0]; // Get first result
    
         // Populate form fields
        const formEl = document.getElementById('editFacultyForm');
        if (formEl) formEl.dataset.userId = faculty.user_id; // Store user_id for password reset
        
        document.getElementById('editFacultyId').value = employeeNumber;
        document.getElementById('editEmployeeNumber').value = faculty.employee_number || employeeNumber;
        
        // Normalize employment status (API might return different format)
        let employmentStatus = (faculty.employment_status || '').toLowerCase().replace(/\s+/g, '-');
        // Map common variations
        if (employmentStatus === 'part-time-full-load') {
          employmentStatus = 'part-time-full-load';
        } else if (employmentStatus.includes('part-time')) {
          employmentStatus = 'part-time';
        } else if (employmentStatus.includes('full-time') || employmentStatus === 'fulltime') {
          employmentStatus = 'full-time';
        }
        document.getElementById('editEmploymentStatus').value = employmentStatus;
        
        document.getElementById('editLastName').value = faculty.last_name || '';
        document.getElementById('editFirstName').value = faculty.first_name || '';
        document.getElementById('editMiddleName').value = faculty.middle_name || '';
        document.getElementById('editEmail').value = faculty.email || '';
        document.getElementById('editContactNumber').value = faculty.contact_number || '';
        document.getElementById('editAccountStatus').value = faculty.account_status || 'active';
      } else {
        throw new Error(data.message || 'Faculty not found');
      }
    } catch (error) {
      console.error('Error loading faculty data:', error);
      if (typeof showToast === 'function') {
        showToast(`Error loading faculty data: ${error.message}`, 'error');
      } else if (typeof showToastNotification === 'function') {
        showToastNotification(`Error loading faculty data: ${error.message}`, 'error');
      } else {
        alert(`Error loading faculty data: ${error.message}`);
      }
      window.closeEditFacultyModal();
    } finally {
      if (form) form.classList.remove('loading');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update Faculty';
      }
    }
  }
  

  
  function resetFacultyPassword() { // Renamed from resetFacultyPassword to avoid conflict
    const userId = document.getElementById('editFacultyForm').dataset.userId;
    const username = document.getElementById('editEmployeeNumber').value;

    if (!userId) {
        showToastNotification('Cannot reset password. User ID is missing.', 'error');
        return;
    }

    showConfirmationModal(
      'Reset Password',
      `Are you sure you want to reset the password for ${username}? A new, secure password will be generated.`,
      'Reset Password',
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
                // Use the unified GeneratedCredentialsModal to show the new password
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
  
  function sendPasswordEmail() {
    const facultyId = document.getElementById('editFacultyId').value;
    const email = document.getElementById('editEmail').value;
    
    if (!email) {
      showToastNotification('Please enter a valid email address first', 'error');
      return;
    }
    
    showConfirmationModal(
      'Send Password Email',
      `Are you sure you want to send a password reset email to ${email}?`,
      'Send Email',
      'Cancel',
      () => {
        showToastNotification('Password reset email sent successfully!', 'success');
      },
      'info'
    );
  }
  
  // Make functions globally accessible
  window.openEditFacultyModal = function(employeeNumber) {
    const modal = document.getElementById('editFacultyModal');
    if (!modal) {
      console.error('EditFacultyModal not found in DOM');
      return;
    }
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Populate form with faculty data (will set user_id in the form dataset)
    populateEditFacultyForm(employeeNumber);
  };
  
  window.closeEditFacultyModal = function() {
    const modal = document.getElementById('editFacultyModal');
    if (!modal) {
      return;
    }
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset form
    const form = document.getElementById('editFacultyForm');
    if (form) form.reset();
    
    // Clear error messages
    const errorDivs = modal.querySelectorAll('.field-error');
    errorDivs.forEach(div => div.remove());
    
    // Reset field borders
    const fields = modal.querySelectorAll('input, select');
    fields.forEach(field => {
      field.style.borderColor = '';
    });
  };
  
  window.submitEditFacultyForm = function() {
    if (!validateEditFacultyForm()) {
      const errorMsg = 'Please correct the errors in the form';
      if (typeof showToast === 'function') {
        showToast(errorMsg, 'error');
      } else if (typeof showToastNotification === 'function') {
        showToastNotification(errorMsg, 'error');
      }
      return;
    }
    
    const submitBtn = document.getElementById('editSubmitBtn');
    const form = document.getElementById('editFacultyForm');
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';
    form.classList.add('modal-loading');
    
    // Use FormData for proper form submission
    const formData = new FormData(form);
    
    fetch(form.dataset.endpoint, {
      method: 'POST',
      credentials: 'include',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' || data.success) {
        const message = data.message || 'Faculty updated successfully!';
        if (typeof showToast === 'function') {
          showToast(message, 'success');
        } else if (typeof showToastNotification === 'function') {
          showToastNotification(message, 'success');
        }
        window.closeEditFacultyModal();
        // Refresh resigned faculty list if available
        if (typeof loadResignedFacultyList === 'function') {
          loadResignedFacultyList();
        }
      } else {
        const errorMsg = data.message || 'Error updating faculty';
        if (typeof showToast === 'function') {
          showToast(errorMsg, 'error');
        } else if (typeof showToastNotification === 'function') {
          showToastNotification(errorMsg, 'error');
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      const errorMsg = 'Something went wrong!';
      if (typeof showToast === 'function') {
        showToast(errorMsg, 'error');
      } else if (typeof showToastNotification === 'function') {
        showToastNotification(errorMsg, 'error');
      }
    })
    .finally(() => {
      // Reset button state
      submitBtn.disabled = false;
      submitBtn.textContent = 'Update Faculty';
      form.classList.remove('modal-loading');
    });
  };
  
  window.sendPasswordEmail = function() {
    sendPasswordEmail();
  };
  
  function generateSecurePassword(length = 12) {
      const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
      let password = "";
      for (let i = 0, n = charset.length; i < length; ++i) {
          password += charset.charAt(Math.floor(Math.random() * n));
      }
      return password;
  }

  // Add event listeners
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editFacultyForm');
    const fields = form.querySelectorAll('input, select');
    
    fields.forEach(field => {
      field.addEventListener('blur', function() {
        const fieldId = this.id;
        const value = this.value.trim();
        
        if (value && !fieldId.includes('editEmployeeNumber') && !fieldId.includes('editLastName') && !fieldId.includes('editFirstName') && !fieldId.includes('editMiddleName')) {
          showEditFieldSuccess(fieldId);
        }
      });
    });
  });
</script> 