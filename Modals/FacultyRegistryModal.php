<?php // Faculty Registry Modal - Add New Faculty ?>
<link rel="stylesheet" href="../../assets/css/modals.css">
<div class="modal-overlay faculty-registration-modal-overlay" id="facultyRegistrationModal">
  <div class="modal-window">
    <button class="modal-close" onclick="closeFacultyRegistrationModal()">&times;</button>
    <h2 class="modal-title">👨‍🏫 Add New Faculty</h2>
    <div class="modal-supporting-text">Fill out the form below to register a new faculty member.</div>
    <div class="modal-content-area">
      <form id="facultyRegistrationForm" class="modal-form" data-endpoint="../../controllers/addUsers.php">
        <input type="hidden" name="type" value="faculty">
        <div class="form-group">
          <label for="employeeNumber">Employee Number *</label>
          <input type="text" id="employeeNumber" name="employeeNumber" required placeholder="e.g., LCA123P" maxlength="7">
        </div>

        <div class="form-group">
          <label for="employmentStatus">Employment Status *</label>
          <select id="employmentStatus" name="employmentStatus" required>
            <option value="">Select Employment Status</option>
            <option value="full-time">Full Time</option>
            <option value="part-time">Part Time</option>
            <option value="part-time-full-load">Part Time - Full Load</option>
          </select>
        </div>
        <div class="form-group">
          <label for="lastName">Last Name *</label>
          <input type="text" id="lastName" name="lastName" required placeholder="Enter last name">
        </div>
        <div class="form-group">
          <label for="firstName">First Name *</label>
          <input type="text" id="firstName" name="firstName" required placeholder="Enter first name">
        </div>
        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" id="middleName" name="middleName" placeholder="Enter middle name (optional)">
        </div>
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required placeholder="Enter email address">
        </div>
        <div class="form-group">
          <label for="contactNumber">Contact Number *</label>
          <input type="text" id="contactNumber" name="contactNumber" required placeholder="e.g., +63 912 345 6789">
        </div>
      </form>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeFacultyRegistrationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitFacultyRegistrationForm()">Add Faculty</button>
    </div>
  </div>
</div>

<script>
  // Form validation and submission
  function validateFacultyForm() {
    const requiredFields = ['employeeNumber', 'employmentStatus', 'lastName', 'firstName', 'email', 'contactNumber'];
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      const value = field.value.trim();
      
      if (!value) {
        showFieldError(fieldId, 'This field is required');
        isValid = false;
      } else {
        showFieldSuccess(fieldId);
      }
    });
    
    // Email validation
    const email = document.getElementById('email').value.trim();
    if (email && !isValidEmail(email)) {
      showFieldError('email', 'Please enter a valid email address');
      isValid = false;
    }
    
    // Employee number validation
    const employeeNumber = document.getElementById('employeeNumber').value.trim();
    if (employeeNumber && !/^[A-Z]{3}\d{3}[A-Z]$/.test(employeeNumber)) {
      showFieldError('employeeNumber', 'Employee number should be in format: 3 letters + 3 digits + 1 letter (e.g., LCA123P)');
      isValid = false;
    }
    
    return isValid;
  }
  
  function showFieldError(fieldId, message) {
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
  
  function showFieldSuccess(fieldId) {
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
  

  
  // Make functions globally accessible
  window.openFacultyRegistrationModal = function() {
    const modal = document.getElementById('facultyRegistrationModal');
    modal.style.display = 'flex';
  };
  
  window.closeFacultyRegistrationModal = function() {
    const modal = document.getElementById('facultyRegistrationModal');
    modal.style.display = 'none';
    
    // Reset form
    document.getElementById('facultyRegistrationForm').reset();
    
    // Clear error messages
    const errorDivs = modal.querySelectorAll('.field-error');
    errorDivs.forEach(div => div.remove());
    
    // Reset field borders
    const fields = modal.querySelectorAll('input, select');
    fields.forEach(field => {
      field.style.borderColor = '';
    });
  };
  
  window.submitFacultyRegistrationForm = function() {
    if (!validateFacultyForm()) {
      showToastNotification('Please correct the errors in the form', 'error');
      return;
    }
    
    // Simulate form submission
    showToastNotification('Faculty registration submitted successfully!', 'success');
    window.closeFacultyRegistrationModal();
    
    // In a real application, you would submit the form data to the server
    // const form = document.getElementById('facultyRegistrationForm');
    // form.submit();
  };
  
  // Add event listeners
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('facultyRegistrationForm');
    const fields = form.querySelectorAll('input, select');
    
    fields.forEach(field => {
      field.addEventListener('blur', function() {
        const fieldId = this.id;
        const value = this.value.trim();
        
        if (value) {
          showFieldSuccess(fieldId);
        }
      });
    });
  });
</script> 