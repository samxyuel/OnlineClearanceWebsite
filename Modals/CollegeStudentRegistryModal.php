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
            <option value="">Loading Departments...</option>
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
document.addEventListener('DOMContentLoaded', function() {
    // It's better to populate departments when the modal is opened.
});

async function populateCollegeDepartments() {
    const departmentSelect = document.getElementById('department');
    if (!departmentSelect) return;
    departmentSelect.innerHTML = '<option value="">Loading Departments...</option>';
    departmentSelect.disabled = true;

    try {
        const response = await fetch(`../../api/departments/list.php?sector=College`);
        const data = await response.json();

        if (data.success && data.departments) {
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            data.departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.department_id; // Send the ID
                option.textContent = dept.department_name;
                departmentSelect.appendChild(option);
            });
            departmentSelect.disabled = false;
        } else {
            showToast(data.message || 'Could not load departments.', 'error');
            departmentSelect.innerHTML = '<option value="">Error loading</option>';
        }
    } catch (error) {
        console.error('Failed to fetch departments:', error);
        showToast('An error occurred while loading departments.', 'error');
        departmentSelect.innerHTML = '<option value="">Error loading</option>';
    }
}

// Update programs and year levels when department changes
async function updateProgramsAndYearLevels() {
  const departmentId = document.getElementById('department').value;
  const programSelect = document.getElementById('program');
  const yearLevelSelect = document.getElementById('yearLevel');
  
  // Clear current options
  programSelect.innerHTML = '<option value="">Select Program</option>';
  yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
  programSelect.disabled = true;
  yearLevelSelect.disabled = true;
  
  if (departmentId) {
    try {
      // Use the modern, centralized API for programs, filtering by department.
      const response = await fetch(`../../api/programs/list.php?department_id=${departmentId}`);
      const data = await response.json();

      if (data.success && data.programs) {
        // Populate programs
        data.programs.forEach(program => {
          const option = document.createElement('option');
          option.value = program.program_id;
          option.textContent = program.program_name;
          programSelect.appendChild(option);
        });

        // Populate year levels from the same API response
        data.year_levels.forEach(yearLevel => {
          const option = document.createElement('option');
          option.value = yearLevel;
          option.textContent = yearLevel;
          yearLevelSelect.appendChild(option);
        });

        programSelect.disabled = false;
        yearLevelSelect.disabled = false;
      }
    } catch (error) {
      console.error('Failed to fetch academic data:', error);
      showToast('An error occurred while loading programs.', 'error');
    }
  }
}

// Form validation and submission
function validateStudentRegistrationForm() {
  const form = document.getElementById('studentRegistrationForm');
  const formData = new FormData(form);
  
  // Check required fields
  const requiredFields = ['studentNumber', 'department', 'program', 'yearLevel', 'section', 'lastName', 'firstName', 'email', 'address', 'password', 'confirmPassword'];
  
  for (const field of requiredFields) {
    const input = form.querySelector(`[name="${field}"]`);
    if (!input.value.trim()) {
      showToast(`Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
      input.focus();
      return false;
    }
  }
  
  // Validate password match
  const password = formData.get('password');
  const confirmPassword = formData.get('confirmPassword');
  
  if (password !== confirmPassword) {
    showToast('Passwords do not match', 'error');
    document.getElementById('confirmPassword').focus();
    return false;
  }
  
  // Validate email format
  const email = formData.get('email');
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showToast('Please enter a valid email address', 'error');
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
      showToast('Student added successfully!', 'success');
      closeStudentRegistrationModal();
      form.reset();
      // Refresh the student list
      if (typeof loadStudentsData === 'function') {
        loadStudentsData();
      }
    } else {
      showToast(data.message || 'Failed to add student', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('An error occurred while adding the student', 'error');
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
  const modal = document.getElementById('studentRegistrationModal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  // Populate departments when the modal is opened
  populateCollegeDepartments();
  
  // Focus on first input
  setTimeout(() => {
    document.getElementById('studentNumber').focus();
  }, 100);
}
</script>
