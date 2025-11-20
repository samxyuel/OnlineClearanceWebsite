<?php
// Edit Student Modal - Modify Existing Student
// This modal is included in StudentManagement.php
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay edit-student-modal-overlay" id="editStudentModal">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="window.closeEditStudentModal && window.closeEditStudentModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">✏️ Edit Student Information</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Update student information and account settings.</div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="editStudentForm" class="modal-form" data-endpoint="../../controllers/updateUsers.php">
        <input type="hidden" name="type" value="student">
        <input type="hidden" id="editStudentId" name="studentId">
        
        <!-- Student Number (Read-only) -->
        <div class="form-group">
          <label for="editStudentNumber">Student Number</label>
          <input type="text" id="editStudentNumber" name="studentNumber" readonly 
                 style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Department (Editable) -->
        <div class="form-group">
          <label for="editDepartment">Department *</label>
          <select id="editDepartment" name="department" required>
            <option value="">Select Department</option>
            <option value="Tourism and Hospitality Management">Tourism and Hospitality Management</option>
            <option value="Information, Communication, and Technology">Information, Communication, and Technology</option>
            <option value="Business, Arts, and Science">Business, Arts, and Science</option>
            <option value="Senior High School">Senior High School</option>
          </select>
        </div>
        
        <!-- Program (Editable) -->
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
        
        <!-- Year Level for Section (Editable) -->
        <div class="form-group">
          <label for="editSectionYearLevel">Year Level for Section *</label>
          <select id="editSectionYearLevel" name="sectionYearLevel" required>
            <option value="">Select Year Level</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
            <option value="11">Grade 11</option>
            <option value="12">Grade 12</option>
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
          <label for="editEmail">Email *</label>
          <input type="email" id="editEmail" name="email" required 
                 placeholder="Enter email address">
        </div>
        
        <!-- Contact Number (Editable) -->
        <div class="form-group">
          <label for="editContactNumber">Contact Number *</label>
          <input type="tel" id="editContactNumber" name="contactNumber" required 
                 placeholder="e.g., +63 912 345 6789">
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
        
        <!-- Reset Password Section -->
        <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--light-blue-gray);">
          <label style="color: var(--deep-navy-blue); font-weight: 600;">Password Management</label>
          <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="button" class="modal-action-secondary" onclick="resetStudentPassword()" style="flex: 1;">
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
    
    <!-- Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="window.closeEditStudentModal && window.closeEditStudentModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitEditStudentForm()" id="editSubmitBtn">Update Student</button>
    </div>
  </div>
</div>

<script>
// Department → Program mapping (same as StudentRegistryModal)
const editDepartmentPrograms = {
  'Tourism and Hospitality Management': [
    'BS in Tourism Management (BSTM)',
    'BS in Culinary Management (BSCM)'
  ],
  'Information, Communication, and Technology': [
    'BS in Information Technology (BSIT)',
    'BS in Computer Science (BSCS)',
    'BS in Computer Engineering (BSCpE)',
    'BS in Information Systems (BSIS)'
  ],
  'Business, Arts, and Science': [
    'BS in Business Administration (BSBA)',
    'BS in Accounting Information System (BSAIS)',
    'BS in Accountancy (BSA)',
    'BA in Communication (BAComm)',
    'Bachelor of Multimedia Arts (BMMA)'
  ],
  'Senior High School': [
    'Accountancy, Business, and Management (ABM)',
    'Science, Technology, Engineering and Mathematics (STEM)',
    'Humanities and Social Sciences (HUMSS)',
    'General Academic (GA)',
    'IT in Mobile App and Web Development (MAWD)',
    'Digital Arts (DA)',
    'Tourism Operations (TOp)',
    'Restaurant and Cafe Operations (RCO)',
    'Culinary Arts (CA)'
  ]
};

// Department → Year Level mapping (same as StudentRegistryModal)
const editDepartmentYearLevels = {
  'Tourism and Hospitality Management': [
    '1st Year',
    '2nd Year', 
    '3rd Year',
    '4th Year'
  ],
  'Information, Communication, and Technology': [
    '1st Year',
    '2nd Year',
    '3rd Year', 
    '4th Year'
  ],
  'Business, Arts, and Science': [
    '1st Year',
    '2nd Year',
    '3rd Year',
    '4th Year'
  ],
  'Senior High School': [
    'Grade 11',
    'Grade 12'
  ]
};

// Update programs and year levels when department changes
function updateEditProgramsAndYearLevels() {
  const department = document.getElementById('editDepartment').value;
  const programSelect = document.getElementById('editProgram');
  const yearLevelSelect = document.getElementById('editYearLevel');
  
  // Clear current options
  programSelect.innerHTML = '<option value="">Select Program</option>';
  yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
  
  if (department) {
    // Update programs
    if (editDepartmentPrograms[department]) {
      editDepartmentPrograms[department].forEach(program => {
        const option = document.createElement('option');
        option.value = program;
        option.textContent = program;
        programSelect.appendChild(option);
      });
    }
    
    // Update year levels
    if (editDepartmentYearLevels[department]) {
      editDepartmentYearLevels[department].forEach(yearLevel => {
        const option = document.createElement('option');
        option.value = yearLevel;
        option.textContent = yearLevel;
        yearLevelSelect.appendChild(option);
      });
    }
  }
}

// Form validation for edit form
function validateEditForm() {
  let isValid = true;
  const form = document.getElementById('editStudentForm');
  
  // Clear previous error states
  form.querySelectorAll('.form-group').forEach(group => {
    group.classList.remove('error', 'success');
    const errorMsg = group.querySelector('.error-message');
    if (errorMsg) errorMsg.remove();
  });
  
  // Validate Email
  const email = document.getElementById('editEmail').value;
  if (!email) {
    showEditFieldError('editEmail', 'Email is required');
    isValid = false;
  } else if (!isValidEmail(email)) {
    showEditFieldError('editEmail', 'Please enter a valid email address');
    isValid = false;
  } else {
    showEditFieldSuccess('editEmail');
  }
  
  // Validate Contact Number
  const contactNumber = document.getElementById('editContactNumber').value;
  if (!contactNumber) {
    showEditFieldError('editContactNumber', 'Contact number is required');
    isValid = false;
  } else {
    showEditFieldSuccess('editContactNumber');
  }
  
  // Validate required fields
  const requiredFields = ['editDepartment', 'editProgram', 'editYearLevel', 'editSectionYearLevel', 'editSectionTerm', 'editSectionNumber', 'editAccountStatus'];
  requiredFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (!field.value) {
      showEditFieldError(fieldId, `${field.previousElementSibling.textContent.replace(' *', '')} is required`);
      isValid = false;
    } else {
      showEditFieldSuccess(fieldId);
    }
  });
  
  return isValid;
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function showEditFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const formGroup = field.closest('.form-group');
  formGroup.classList.add('error');
  formGroup.classList.remove('success');
  
  let errorMsg = formGroup.querySelector('.error-message');
  if (!errorMsg) {
    errorMsg = document.createElement('div');
    errorMsg.className = 'error-message';
    formGroup.appendChild(errorMsg);
  }
  errorMsg.textContent = message;
}

function showEditFieldSuccess(fieldId) {
  const field = document.getElementById(fieldId);
  const formGroup = field.closest('.form-group');
  formGroup.classList.add('success');
  formGroup.classList.remove('error');
  
  const errorMsg = formGroup.querySelector('.error-message');
  if (errorMsg) errorMsg.remove();
}

// Modal functions - Make globally accessible
window.openEditStudentModal = function(studentId) {
  // Fetch student data and populate form
  populateEditForm(studentId);
  
  const modal = document.getElementById('editStudentModal');
  if (!modal) {
    console.error('EditStudentModal not found in DOM');
    return;
  }
  
  modal.style.display = 'flex';
  document.body.classList.add('modal-open');
  
  // Focus on first editable field
  setTimeout(() => {
    const deptField = document.getElementById('editDepartment');
    if (deptField) deptField.focus();
  }, 100);
};

window.closeEditStudentModal = function() {
  const modal = document.getElementById('editStudentModal');
  if (!modal) {
    return;
  }
  
  modal.style.display = 'none';
  document.body.classList.remove('modal-open');
  
  // Reset form
  const form = document.getElementById('editStudentForm');
  if (form) form.reset();
  
  // Clear error states
  document.querySelectorAll('.form-group').forEach(group => {
    group.classList.remove('error', 'success');
    const errorMsg = group.querySelector('.error-message');
    if (errorMsg) errorMsg.remove();
  });
};

// Load student data for editing
async function populateEditForm(userId) {
  const form = document.getElementById('editStudentForm');
  const submitBtn = document.getElementById('editSubmitBtn');
  
  if (form) form.classList.add('loading');
  if (submitBtn) submitBtn.disabled = true;
  if (submitBtn) submitBtn.textContent = 'Loading...';

  try {
    // Ensure dropdowns are populated before setting values
    await updateEditProgramsAndYearLevels();

    // Fetch student data from the API using the user_id
    const response = await fetch(`../../api/users/get_student.php?user_id=${userId}`, {
      credentials: 'include'
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();

    if (data.success && data.student) {
      const student = data.student;

      // Populate form fields
      document.getElementById('editStudentId').value = student.user_id;
      document.getElementById('editStudentNumber').value = student.student_id || '';
      document.getElementById('editLastName').value = student.last_name || '';
      document.getElementById('editFirstName').value = student.first_name || '';
      document.getElementById('editMiddleName').value = student.middle_name || '';
      document.getElementById('editEmail').value = student.email || '';
      document.getElementById('editContactNumber').value = student.contact_number || '';
      document.getElementById('editAccountStatus').value = student.account_status || 'active';

      // Populate and select department, then trigger program/year update
      // The dropdown uses department names as values, so match by name
      const departmentSelect = document.getElementById('editDepartment');
      if (departmentSelect && student.department_name) {
        // Try to find matching option by text content or value
        const options = Array.from(departmentSelect.options);
        const matchingOption = options.find(opt => 
          opt.value === student.department_name || 
          opt.textContent.trim() === student.department_name.trim()
        );
        if (matchingOption) {
          departmentSelect.value = matchingOption.value;
        } else {
          // If no exact match, try to set by value directly
          departmentSelect.value = student.department_name;
        }
        await updateEditProgramsAndYearLevels(); // Wait for programs to load based on the department
      }

      // Set program and year level after options are loaded
      setTimeout(() => {
        const programSelect = document.getElementById('editProgram');
        const yearLevelSelect = document.getElementById('editYearLevel');
        
        if (programSelect && student.program_name) {
          programSelect.value = student.program_name;
        }
        if (yearLevelSelect && student.year_level) {
          yearLevelSelect.value = student.year_level;
        }
      }, 100);

      // Parse section format and populate section fields
      const sectionValue = student.section || '';
      if (sectionValue && sectionValue.includes('/')) {
        const parts = sectionValue.split('/');
        const yearLevel = parts[0];
        const termSection = parts[1].split('-');
        const term = termSection[0];
        const sectionNumber = termSection[1];
        
        document.getElementById('editSectionYearLevel').value = yearLevel;
        document.getElementById('editSectionTerm').value = term;
        document.getElementById('editSectionNumber').value = sectionNumber;
        document.getElementById('editGeneratedSection').value = sectionValue;
      } else {
        document.getElementById('editSectionYearLevel').value = '';
        document.getElementById('editSectionTerm').value = '';
        document.getElementById('editSectionNumber').value = '';
        document.getElementById('editGeneratedSection').value = '';
      }
    } else {
      throw new Error(data.message || 'Student not found');
    }
  } catch (error) {
    console.error('Error loading student data:', error);
    if (typeof showToast === 'function') {
      showToast(`Error loading student data: ${error.message}`, 'error');
    } else if (typeof showNotification === 'function') {
      showNotification(`Error loading student data: ${error.message}`, 'error');
    } else {
      alert(`Error loading student data: ${error.message}`);
    }
    window.closeEditStudentModal();
  } finally {
    if (form) form.classList.remove('loading');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Update Student';
    }
  }
}

function submitEditStudentForm() {
  if (!validateEditForm()) {
    return;
  }
  
  const submitBtn = document.getElementById('editSubmitBtn');
  const form = document.getElementById('editStudentForm');
  
  // Show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = 'Updating...';
  form.classList.add('modal-loading');
  
  // Use FormData for proper form submission (the controller expects POST form data, not JSON)
  const formData = new FormData(form);
  
  fetch(form.dataset.endpoint, {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success' || data.success) {
      const message = data.message || 'Student updated successfully!';
      if (typeof showToast === 'function') {
        showToast(message, 'success');
      } else if (typeof showNotification === 'function') {
        showNotification(message, 'success');
      }
      window.closeEditStudentModal();
      // Refresh graduated students list if available
      if (typeof loadGraduatedStudentsData === 'function') {
        // Determine which sector to refresh based on current tab
        const collegeTab = document.querySelector('.graduated-sector-tab[data-sector="college"]');
        const shsTab = document.querySelector('.graduated-sector-tab[data-sector="shs"]');
        if (collegeTab && collegeTab.classList.contains('active')) {
          loadGraduatedStudentsData('college');
        } else if (shsTab && shsTab.classList.contains('active')) {
          loadGraduatedStudentsData('shs');
        } else {
          // Refresh both if we can't determine
          loadGraduatedStudentsData('college');
          loadGraduatedStudentsData('shs');
        }
      }
      // Optionally reload table or update UI dynamically here
      if (typeof fetchSortedData === 'function') {
        fetchSortedData();
      }
    } else {
      const errorMsg = data.message || 'Error updating student';
      if (typeof showToast === 'function') {
        showToast(errorMsg, 'error');
      } else if (typeof showNotification === 'function') {
        showNotification(errorMsg, 'error');
      }
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('Something went wrong!', 'error');
  })
  .finally(() => {
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.textContent = 'Update Student';
    form.classList.remove('modal-loading');
  });
}

function resetStudentPassword() {
  const studentId = document.getElementById('editStudentId').value;
  const studentName = document.getElementById('editFirstName').value + ' ' + document.getElementById('editLastName').value;
  
  if (confirm(`Are you sure you want to reset the password for ${studentName}?`)) {
    // Show loading state
    const resetBtn = event.target;
    const originalText = resetBtn.innerHTML;
    resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
    resetBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
      showNotification(`Password reset successfully for ${studentName}`, 'success');
      resetBtn.innerHTML = originalText;
      resetBtn.disabled = false;
    }, 1500);
  }
}

function sendPasswordEmail() {
  const studentId = document.getElementById('editStudentId').value;
  const studentName = document.getElementById('editFirstName').value + ' ' + document.getElementById('editLastName').value;
  const studentEmail = document.getElementById('editEmail').value;
  
  if (confirm(`Send password reset email to ${studentEmail}?`)) {
    // Show loading state
    const emailBtn = event.target;
    const originalText = emailBtn.innerHTML;
    emailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    emailBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
      showNotification(`Password reset email sent to ${studentEmail}`, 'success');
      emailBtn.innerHTML = originalText;
      emailBtn.disabled = false;
    }, 1500);
  }
}

// Generate section format automatically for edit modal
function updateEditGeneratedSection() {
  const yearLevel = document.getElementById('editSectionYearLevel').value;
  const term = document.getElementById('editSectionTerm').value;
  const sectionNumber = document.getElementById('editSectionNumber').value;
  const generatedSection = document.getElementById('editGeneratedSection');
  
  if (yearLevel && term && sectionNumber) {
    generatedSection.value = `${yearLevel}/${term}-${sectionNumber}`;
  } else {
    generatedSection.value = '';
  }
}

// Add event listeners when modal is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Department change event
  const departmentSelect = document.getElementById('editDepartment');
  if (departmentSelect) {
    departmentSelect.addEventListener('change', updateEditProgramsAndYearLevels);
  }
  
  // Section format generation events for edit modal
  const editSectionYearLevel = document.getElementById('editSectionYearLevel');
  const editSectionTerm = document.getElementById('editSectionTerm');
  const editSectionNumber = document.getElementById('editSectionNumber');
  
  if (editSectionYearLevel) {
    editSectionYearLevel.addEventListener('change', updateEditGeneratedSection);
  }
  if (editSectionTerm) {
    editSectionTerm.addEventListener('change', updateEditGeneratedSection);
  }
  if (editSectionNumber) {
    editSectionNumber.addEventListener('change', updateEditGeneratedSection);
  }
  
  // Form submission on Enter key
  const form = document.getElementById('editStudentForm');
  if (form) {
    form.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitEditStudentForm();
      }
    });
  }
  
  // Close modal on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeEditStudentModal();
    }
  });
});
</script> 