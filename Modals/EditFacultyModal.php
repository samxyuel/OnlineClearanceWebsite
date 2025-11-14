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

        <!-- Multi-Department Assignment Section -->
        <div class="form-section-divider">
          <hr>
          <span class="divider-text">Department Assignments (Optional)</span>
        </div>
        <div class="form-group">
          <label>Additional Departments</label>
          <small class="form-help">Select additional departments for this faculty member</small>
          <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
            <select id="editAdditionalDepartmentSelect" style="flex: 1; padding: 6px;">
              <option value="">Select a department...</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEditAdditionalDepartment()">
              <i class="fas fa-plus"></i> Add
            </button>
          </div>
          <div id="editDepartmentsList" style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
            <!-- Additional departments will appear as chips here -->
          </div>
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

    // Remember which faculty is being edited for follow-up actions
    const form = document.getElementById('editFacultyForm');
    form.dataset.userId = facultyId;

    // Populate form with faculty data
    populateEditForm(facultyId);
    
    // Initialize department select and fetch existing assignments
    window.editAdditionalDepartments = [];
    populateEditDepartmentSelect();
    fetchEditDepartmentAssignments(facultyId);
  };  window.closeEditFacultyModal = function() {
    const modal = document.getElementById('editFacultyModal');
    modal.style.display = 'none';
    
    // Reset form
    document.getElementById('editFacultyForm').reset();
    
    // Clear additional departments
    window.editAdditionalDepartments = [];
    const deptList = document.getElementById('editDepartmentsList');
    if (deptList) deptList.innerHTML = '';
    
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
    
    const form = document.getElementById('editFacultyForm');
    const data = {
      employee_number: document.getElementById('editEmployeeNumber').value.trim(),
      employment_status: document.getElementById('editEmploymentStatus').value,
      email: document.getElementById('editEmail').value.trim(),
      contact_number: document.getElementById('editContactNumber').value.trim(),
      account_status: document.getElementById('editAccountStatus').value
    };

    // Add additional departments if any
    if (window.editAdditionalDepartments && window.editAdditionalDepartments.length > 0) {
      data['assignedDepartments'] = window.editAdditionalDepartments.map(d => d.department_id);
    }

    const submitBtn = document.getElementById('editSubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    fetch('../../api/users/update_faculty.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToastNotification('Faculty information updated successfully!', 'success');
        window.closeEditFacultyModal();
        // Notify parent page of update
        document.dispatchEvent(new CustomEvent('faculty-updated', { detail: { employee_number: data.employee_number } }));
      } else {
        showToastNotification(res.message || 'Error updating faculty', 'error');
        if (submitBtn) submitBtn.disabled = false;
      }
    })
    .catch(err => {
      console.error(err);
      showToastNotification('Network error', 'error');
      if (submitBtn) submitBtn.disabled = false;
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

  // Store additional departments for edit form
  window.editAdditionalDepartments = [];

  // Add additional department in edit form
  window.addEditAdditionalDepartment = function() {
    const sel = document.getElementById('editAdditionalDepartmentSelect');
    if (!sel) return;
    const val = sel.value;
    const text = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';

    if (!val) {
      showToastNotification('Please select a department', 'error');
      return;
    }

    const deptId = parseInt(val, 10);
    if (isNaN(deptId)) {
      showToastNotification('Invalid department selected', 'error');
      return;
    }

    // Prevent duplicates
    if (window.editAdditionalDepartments.some(d => d.department_id === deptId)) {
      showToastNotification('This department is already added', 'warning');
      return;
    }

    window.editAdditionalDepartments.push({ department_id: deptId, department_name: text });
    sel.value = '';
    renderEditAdditionalDepartments();
  };

  // Remove additional department from edit form
  window.removeEditAdditionalDepartment = function(departmentName) {
    window.editAdditionalDepartments = window.editAdditionalDepartments.filter(d => (d.department_name || '') !== departmentName);
    renderEditAdditionalDepartments();
  };

  // Render additional departments as chips in edit form
  function renderEditAdditionalDepartments() {
    const container = document.getElementById('editDepartmentsList');
    if (!container) return;

    container.innerHTML = window.editAdditionalDepartments.map(d => `
      <span class="chip" style="padding: 6px 12px; background: #e9ecef; border-radius: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
        ${d.department_name}
        <button type="button" onclick="removeEditAdditionalDepartment('${d.department_name.replace(/'/g, "\\'")}')" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 16px; padding: 0;">
          ×
        </button>
      </span>
    `).join('');
  }

  // Populate edit department select on modal open
  function populateEditDepartmentSelect() {
    const sel = document.getElementById('editAdditionalDepartmentSelect');
    if (!sel) return;
    const url = '../../api/departments/list.php?limit=500';
    fetch(url, { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        if (!data || data.success !== true) return;
        sel.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.text = 'Select a department...';
        sel.appendChild(ph);
        (data.departments || []).forEach(d => {
          const o = document.createElement('option');
          o.value = d.department_id;
          o.text = d.department_name;
          sel.appendChild(o);
        });
        sel.selectedIndex = 0;
      })
      .catch(() => {});
  }

  // Fetch existing department assignments for a faculty and populate editAdditionalDepartments
  async function fetchEditDepartmentAssignments(userId) {
    try {
      const url = `../../api/faculty/department_assignments.php?user_id=${encodeURIComponent(userId)}`;
      console.log(`Fetching department assignments from: ${url}`);
      const res = await fetch(url, { credentials: 'include' });
      const data = await res.json();
      console.log('Department assignments response:', data);
      
      if (data && data.success === true && Array.isArray(data.departments)) {
        console.log('Setting editAdditionalDepartments:', data.departments);
        window.editAdditionalDepartments = data.departments.map(d => ({
          department_id: parseInt(d.department_id, 10),
          department_name: d.department_name
        }));
        renderEditAdditionalDepartments();
      } else {
        console.warn('API response not successful or no departments array:', data);
        window.editAdditionalDepartments = window.editAdditionalDepartments || [];
        renderEditAdditionalDepartments();
      }
    } catch (err) {
      console.error('Error fetching department assignments:', err);
      window.editAdditionalDepartments = window.editAdditionalDepartments || [];
      renderEditAdditionalDepartments();
    }
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