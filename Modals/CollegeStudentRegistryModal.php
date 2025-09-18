<?php
// College Student Registry Modal - Add New College Student
// This modal is specifically for College students only
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay student-registration-modal-overlay" id="studentRegistrationModal">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeStudentRegistrationModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">🎓 Add New College Student</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Fill out the form below to register a new college student.</div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="studentRegistrationForm" class="modal-form" data-endpoint="../../controllers/addUsers.php">
        <input type="hidden" name="type" value="student">
        <input type="hidden" name="sector" value="college">
        
        <!-- Student Number -->
        <div class="form-group">
          <label for="studentNumber">Student Number *</label>
          <input type="text" id="studentNumber" name="studentNumber" required 
                 placeholder="e.g., 02000288327" maxlength="11">
        </div>
        
        <!-- Department (College Only) -->
        <div class="form-group">
          <label for="department">Department *</label>
          <select id="department" name="department" required onchange="updateProgramsAndYearLevels()">
            <option value="">Select Department</option>
            <option value="Tourism and Hospitality Management">Tourism and Hospitality Management</option>
            <option value="Information, Communication, and Technology">Information, Communication, and Technology</option>
            <option value="Business, Arts, and Science">Business, Arts, and Science</option>
          </select>
        </div>
        
        <!-- Program (College Only) -->
        <div class="form-group">
          <label for="program">Program *</label>
          <select id="program" name="program" required>
            <option value="">Select Program</option>
          </select>
        </div>
        
        <!-- Year Level -->
        <div class="form-group">
          <label for="yearLevel">Year Level *</label>
          <select id="yearLevel" name="yearLevel" required>
            <option value="">Select Year Level</option>
          </select>
        </div>
        
        <!-- Section -->
        <div class="form-group">
          <label for="section">Section *</label>
          <input type="text" id="section" name="section" required 
                 placeholder="e.g., 1/1-1" maxlength="10">
        </div>
        
        <!-- Student Names -->
        <div class="form-row">
          <div class="form-group">
            <label for="lastName">Last Name *</label>
            <input type="text" id="lastName" name="lastName" required 
                   placeholder="Enter last name" maxlength="50">
          </div>
          
          <div class="form-group">
            <label for="firstName">First Name *</label>
            <input type="text" id="firstName" name="firstName" required 
                   placeholder="Enter first name" maxlength="50">
          </div>
        </div>
        
        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" id="middleName" name="middleName" 
                 placeholder="Enter middle name (optional)" maxlength="50">
        </div>
        
        <!-- Contact Information -->
        <div class="form-group">
          <label for="email">Email Address *</label>
          <input type="email" id="email" name="email" required 
                 placeholder="student@email.com" maxlength="100">
        </div>
        
        <div class="form-group">
          <label for="phoneNumber">Phone Number</label>
          <input type="tel" id="phoneNumber" name="phoneNumber" 
                 placeholder="+63 9XX XXX XXXX" maxlength="15">
        </div>
        
        <!-- Address Information -->
        <div class="form-group">
          <label for="address">Address *</label>
          <textarea id="address" name="address" required 
                    placeholder="Enter complete address" rows="3" maxlength="200"></textarea>
        </div>
        
        <!-- Account Settings -->
        <div class="form-section">
          <h3 class="form-section-title">Account Settings</h3>
          
          <div class="form-group">
            <label for="password">Initial Password *</label>
            <input type="password" id="password" name="password" required 
                   placeholder="Enter initial password" minlength="8">
            <small class="form-help">Minimum 8 characters</small>
          </div>
          
          <div class="form-group">
            <label for="confirmPassword">Confirm Password *</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required 
                   placeholder="Confirm initial password" minlength="8">
          </div>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="sendWelcomeEmail" name="sendWelcomeEmail" checked>
              <span class="checkmark"></span>
              Send welcome email with login credentials
            </label>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeStudentRegistrationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitStudentRegistrationForm()" id="submitBtn">Add Student</button>
    </div>
  </div>
</div>

<script>
// College-specific Department → Program mapping
if (typeof window.collegeDepartmentPrograms === 'undefined') {
window.collegeDepartmentPrograms = {
  'Tourism and Hospitality Management': [
    'BS in Tourism Management (BSTM)',
    'BS in Culinary Management (BSCM)'
  ],
  'Information, Communication, and Technology': [
    'BS in Information Technology (BSIT)',
    'BS in Computer Science (BSCS)',
    'BS in Information Systems (BSIS)',
    'BS in Computer Engineering (BSCpE)'
  ],
  'Business, Arts, and Science': [
    'BS in Business Administration (BSBA)',
    'BS in Accountancy (BSA)',
    'BS in Accounting Information System (BSAIS)',
    'BA in Communication (BAComm)',
    'Bachelor of Multimedia Arts (BMMA)'
  ]
};

// College-specific Department → Year Level mapping
window.collegeDepartmentYearLevels = {
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
  ]
};
} // End of conditional block

// Update programs and year levels when department changes
function updateProgramsAndYearLevels() {
  const department = document.getElementById('department').value;
  const programSelect = document.getElementById('program');
  const yearLevelSelect = document.getElementById('yearLevel');
  
  // Clear current options
  programSelect.innerHTML = '<option value="">Select Program</option>';
  yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
  
  if (department) {
    // Update programs
    if (window.collegeDepartmentPrograms && window.collegeDepartmentPrograms[department]) {
      window.collegeDepartmentPrograms[department].forEach(program => {
        const option = document.createElement('option');
        option.value = program;
        option.textContent = program;
        programSelect.appendChild(option);
      });
    }
    
    // Update year levels
    if (window.collegeDepartmentYearLevels && window.collegeDepartmentYearLevels[department]) {
      window.collegeDepartmentYearLevels[department].forEach(yearLevel => {
        const option = document.createElement('option');
        option.value = yearLevel;
        option.textContent = yearLevel;
        yearLevelSelect.appendChild(option);
      });
    }
  }
}

// Form validation and submission
function validateStudentRegistrationForm() {
  const form = document.getElementById('studentRegistrationForm');
  const formData = new FormData(form);
  
  // Check required fields
  const requiredFields = ['studentNumber', 'department', 'program', 'yearLevel', 'section', 'lastName', 'firstName', 'email', 'password', 'confirmPassword'];
  
  for (const field of requiredFields) {
    const input = form.querySelector(`[name="${field}"]`);
    if (!input.value.trim()) {
      showToastNotification(`Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
      input.focus();
      return false;
    }
  }
  
  // Validate password match
  const password = formData.get('password');
  const confirmPassword = formData.get('confirmPassword');
  
  if (password !== confirmPassword) {
    showToastNotification('Passwords do not match', 'error');
    document.getElementById('confirmPassword').focus();
    return false;
  }
  
  // Validate email format
  const email = formData.get('email');
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showToastNotification('Please enter a valid email address', 'error');
    document.getElementById('email').focus();
    return false;
  }
  
  return true;
}

function submitStudentRegistrationForm() {
  if (!validateStudentRegistrationForm()) {
    return;
  }
  
  const form = document.getElementById('studentRegistrationForm');
  const formData = new FormData(form);
  const submitBtn = document.getElementById('submitBtn');
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.textContent = 'Adding Student...';
  
  // Submit form
  fetch(form.dataset.endpoint, {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToastNotification('Student added successfully!', 'success');
      closeStudentRegistrationModal();
      form.reset();
      // Refresh the student list
      if (typeof loadStudentsData === 'function') {
        loadStudentsData();
      }
    } else {
      showToastNotification(data.message || 'Failed to add student', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToastNotification('An error occurred while adding the student', 'error');
  })
  .finally(() => {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.textContent = 'Add Student';
  });
}

function closeStudentRegistrationModal() {
  const modal = document.getElementById('studentRegistrationModal');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
  
  // Reset form
  const form = document.getElementById('studentRegistrationForm');
  form.reset();
  
  // Clear dynamic dropdowns
  document.getElementById('program').innerHTML = '<option value="">Select Program</option>';
  document.getElementById('yearLevel').innerHTML = '<option value="">Select Year Level</option>';
}

// Open modal function (called from parent page)
function openStudentRegistrationModal() {
  console.log('🎓 openStudentRegistrationModal called - College Student Registry Modal');
  const modal = document.getElementById('studentRegistrationModal');
  if (!modal) {
    console.error('❌ Modal element not found: studentRegistrationModal');
    return;
  }
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  console.log('✅ College Student Registry Modal opened successfully');
  
  // Focus on first input
  setTimeout(() => {
    document.getElementById('studentNumber').focus();
  }, 100);
}

// Debug: Confirm modal script loaded
console.log('✅ CollegeStudentRegistryModal.php script loaded successfully');
console.log('Function openStudentRegistrationModal defined:', typeof openStudentRegistrationModal);
</script>
