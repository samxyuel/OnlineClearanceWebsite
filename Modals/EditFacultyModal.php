<?php // Edit Faculty Modal - Modify Existing Faculty ?>
<link rel="stylesheet" href="../../assets/css/modals.css">
<div class="modal-overlay edit-faculty-modal-overlay" id="editFacultyModal">
  <div class="modal-window">
    <button class="modal-close" onclick="closeEditFacultyModal()">&times;</button>
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
            <button type="button" class="modal-action-secondary" onclick="sendPasswordEmail()" style="flex: 1;">
              <i class="fas fa-envelope"></i> Send Email
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeEditFacultyModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitEditFacultyForm()" id="editSubmitBtn">Update Faculty</button>
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
  

  
  function populateEditForm(facultyId) {
    // In a real application, you would fetch faculty data from the server
    // For now, we'll use sample data based on the faculty ID
         const sampleData = {
       'EMP001': {
         employeeNumber: 'EMP001',
         employmentStatus: 'full-time',
        lastName: 'Santos',
        firstName: 'Maria',
        middleName: 'Garcia',
        email: 'maria.santos@example.com',
        contactNumber: '+63 912 345 6789',
        accountStatus: 'active'
      },
             'EMP002': {
         employeeNumber: 'EMP002',
         employmentStatus: 'part-time',
        lastName: 'Dela Cruz',
        firstName: 'Juan',
        middleName: 'Santos',
        email: 'juan.delacruz@example.com',
        contactNumber: '+63 923 456 7890',
        accountStatus: 'active'
      },
             'EMP003': {
         employeeNumber: 'EMP003',
         employmentStatus: 'contract',
        lastName: 'Rodriguez',
        firstName: 'Ana',
        middleName: 'Lopez',
        email: 'ana.rodriguez@example.com',
        contactNumber: '+63 934 567 8901',
        accountStatus: 'inactive'
      },
             'EMP004': {
         employeeNumber: 'EMP004',
         employmentStatus: 'full-time',
        lastName: 'Mendoza',
        firstName: 'Carlos',
        middleName: 'Reyes',
        email: 'carlos.mendoza@example.com',
        contactNumber: '+63 945 678 9012',
        accountStatus: 'resigned'
      }
    };
    
    const facultyData = sampleData[facultyId] || sampleData['EMP001'];
    
         // Populate form fields
     document.getElementById('editFacultyId').value = facultyId;
     document.getElementById('editEmployeeNumber').value = facultyData.employeeNumber;
     document.getElementById('editEmploymentStatus').value = facultyData.employmentStatus;
    document.getElementById('editLastName').value = facultyData.lastName;
    document.getElementById('editFirstName').value = facultyData.firstName;
    document.getElementById('editMiddleName').value = facultyData.middleName;
    document.getElementById('editEmail').value = facultyData.email;
    document.getElementById('editContactNumber').value = facultyData.contactNumber;
    document.getElementById('editAccountStatus').value = facultyData.accountStatus;
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
  window.openEditFacultyModal = function(facultyId) {
    const modal = document.getElementById('editFacultyModal');
    modal.style.display = 'flex';
    
    // Populate form with faculty data
    populateEditForm(facultyId);
  };
  
  window.closeEditFacultyModal = function() {
    const modal = document.getElementById('editFacultyModal');
    modal.style.display = 'none';
    
    // Reset form
    document.getElementById('editFacultyForm').reset();
    
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
      showToastNotification('Please correct the errors in the form', 'error');
      return;
    }
    
    // Simulate form submission
    showToastNotification('Faculty information updated successfully!', 'success');
    window.closeEditFacultyModal();
    
    // In a real application, you would submit the form data to the server
    // const form = document.getElementById('editFacultyForm');
    // form.submit();
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