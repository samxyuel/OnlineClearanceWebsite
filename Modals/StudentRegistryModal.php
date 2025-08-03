<?php
// Student Registry Modal - Add New Student
// This modal is included in StudentManagement.php
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay student-registration-modal-overlay" id="studentRegistrationModal">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeStudentRegistrationModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">🎓 Add New Student</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Fill out the form below to register a new student.</div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="studentRegistrationForm" class="modal-form" data-endpoint="../../controllers/addUsers.php">
        <input type="hidden" name="type" value="student">
        
        <!-- Student Number -->
        <div class="form-group">
          <label for="studentNumber">Student Number *</label>
          <input type="text" id="studentNumber" name="studentNumber" required 
                 placeholder="e.g., 02000288327" maxlength="11">
        </div>
        
        <!-- Department -->
        <div class="form-group">
          <label for="department">Department *</label>
          <select id="department" name="department" required>
            <option value="">Select Department</option>
            <option value="Tourism and Hospitality Management">Tourism and Hospitality Management</option>
            <option value="Information, Communication, and Technology">Information, Communication, and Technology</option>
            <option value="Business, Arts, and Science">Business, Arts, and Science</option>
            <option value="Senior High School">Senior High School</option>
          </select>
        </div>
        
        <!-- Program -->
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
        
        <!-- Year Level for Section -->
        <div class="form-group">
          <label for="sectionYearLevel">Year Level for Section *</label>
          <select id="sectionYearLevel" name="sectionYearLevel" required>
            <option value="">Select Year Level</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
            <option value="11">Grade 11</option>
            <option value="12">Grade 12</option>
          </select>
        </div>
        
        <!-- Term for Section -->
        <div class="form-group">
          <label for="sectionTerm">Term *</label>
          <select id="sectionTerm" name="sectionTerm" required>
            <option value="">Select Term</option>
            <option value="1">1st Term</option>
            <option value="2">2nd Term</option>
          </select>
        </div>
        
        <!-- Section Number -->
        <div class="form-group">
          <label for="sectionNumber">Section Number *</label>
          <select id="sectionNumber" name="sectionNumber" required>
            <option value="">Select Section</option>
            <option value="1">Section 1</option>
            <option value="2">Section 2</option>
            <option value="3">Section 3</option>
            <option value="4">Section 4</option>
            <option value="5">Section 5</option>
            <option value="6">Section 6</option>
          </select>
        </div>
        
        <!-- Generated Section Display -->
        <div class="form-group">
          <label>Generated Section Format</label>
          <input type="text" id="generatedSection" name="generatedSection" readonly 
                 placeholder="e.g., 3/2-1" style="background-color: var(--very-light-off-white); color: var(--medium-muted-blue);">
        </div>
        
        <!-- Last Name -->
        <div class="form-group">
          <label for="lastName">Last Name *</label>
          <input type="text" id="lastName" name="lastName" required 
                 placeholder="Enter last name">
        </div>
        
        <!-- First Name -->
        <div class="form-group">
          <label for="firstName">First Name *</label>
          <input type="text" id="firstName" name="firstName" required 
                 placeholder="Enter first name">
        </div>
        
        <!-- Middle Name -->
        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" id="middleName" name="middleName" 
                 placeholder="Enter middle name (optional)">
        </div>
        
        <!-- Email -->
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required 
                 placeholder="Enter email address">
        </div>
        
        <!-- Contact Number -->
        <div class="form-group">
          <label for="contactNumber">Contact Number *</label>
          <input type="tel" id="contactNumber" name="contactNumber" required 
                 placeholder="e.g., +63 912 345 6789">
        </div>
      </form>
    </div>
    
    <!-- Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeStudentRegistrationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitStudentRegistrationForm()" id="submitBtn">Add Student</button>
    </div>
  </div>
</div>

<script>
// Department → Program mapping
const departmentPrograms = {
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

// Department → Year Level mapping
const departmentYearLevels = {
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
function updateProgramsAndYearLevels() {
  const department = document.getElementById('department').value;
  const programSelect = document.getElementById('program');
  const yearLevelSelect = document.getElementById('yearLevel');
  
  // Clear current options
  programSelect.innerHTML = '<option value="">Select Program</option>';
  yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
  
  if (department) {
    // Update programs
    if (departmentPrograms[department]) {
      departmentPrograms[department].forEach(program => {
        const option = document.createElement('option');
        option.value = program;
        option.textContent = program;
        programSelect.appendChild(option);
      });
    }
    
    // Update year levels
    if (departmentYearLevels[department]) {
      departmentYearLevels[department].forEach(yearLevel => {
        const option = document.createElement('option');
        option.value = yearLevel;
        option.textContent = yearLevel;
        yearLevelSelect.appendChild(option);
      });
    }
  }
}

// Form validation
function validateForm() {
  let isValid = true;
  const form = document.getElementById('studentRegistrationForm');
  
  // Clear previous error states
  form.querySelectorAll('.form-group').forEach(group => {
    group.classList.remove('error', 'success');
    const errorMsg = group.querySelector('.error-message');
    if (errorMsg) errorMsg.remove();
  });
  
  // Validate Student Number
  const studentNumber = document.getElementById('studentNumber').value;
  if (!studentNumber) {
    showFieldError('studentNumber', 'Student number is required');
    isValid = false;
  } else if (!/^\d{11}$/.test(studentNumber)) {
    showFieldError('studentNumber', 'Student number must be 11 digits');
    isValid = false;
  } else {
    showFieldSuccess('studentNumber');
  }
  
  // Validate Email
  const email = document.getElementById('email').value;
  if (!email) {
    showFieldError('email', 'Email is required');
    isValid = false;
  } else if (!isValidEmail(email)) {
    showFieldError('email', 'Please enter a valid email address');
    isValid = false;
  } else {
    showFieldSuccess('email');
  }
  
  // Validate required fields
  const requiredFields = ['department', 'program', 'yearLevel', 'sectionYearLevel', 'sectionTerm', 'sectionNumber', 'lastName', 'firstName', 'contactNumber'];
  requiredFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (!field.value) {
      showFieldError(fieldId, `${field.previousElementSibling.textContent.replace(' *', '')} is required`);
      isValid = false;
    } else {
      showFieldSuccess(fieldId);
    }
  });
  
  return isValid;
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function showFieldError(fieldId, message) {
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

function showFieldSuccess(fieldId) {
  const field = document.getElementById(fieldId);
  const formGroup = field.closest('.form-group');
  formGroup.classList.add('success');
  formGroup.classList.remove('error');
  
  const errorMsg = formGroup.querySelector('.error-message');
  if (errorMsg) errorMsg.remove();
}

// Modal functions
function openStudentRegistrationModal() {
  document.getElementById('studentRegistrationModal').style.display = 'flex';
  document.body.classList.add('modal-open');
  
  // Focus on first field
  setTimeout(() => {
    document.getElementById('studentNumber').focus();
  }, 100);
}

function closeStudentRegistrationModal() {
  document.getElementById('studentRegistrationModal').style.display = 'none';
  document.body.classList.remove('modal-open');
  
  // Reset form
  document.getElementById('studentRegistrationForm').reset();
  
  // Clear error states
  document.querySelectorAll('.form-group').forEach(group => {
    group.classList.remove('error', 'success');
    const errorMsg = group.querySelector('.error-message');
    if (errorMsg) errorMsg.remove();
  });
}

function submitStudentRegistrationForm() {
  if (!validateForm()) {
    return;
  }
  
  const submitBtn = document.getElementById('submitBtn');
  const form = document.getElementById('studentRegistrationForm');
  
  // Show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = 'Adding...';
  form.classList.add('modal-loading');
  
  const formData = new FormData(form);
  const jsonData = {};
  formData.forEach((value, key) => { 
    jsonData[key] = value; 
  });
  jsonData['role_id'] = 5; // Student role
  
  fetch(form.dataset.endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(jsonData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification(data.message || 'Student added successfully!', 'success');
      closeStudentRegistrationModal();
      // Optionally reload table or update UI dynamically here
      if (typeof fetchSortedData === 'function') {
        fetchSortedData();
      }
    } else {
      showNotification(data.message || 'Error adding student', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('Something went wrong!', 'error');
  })
  .finally(() => {
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.textContent = 'Add Student';
    form.classList.remove('modal-loading');
  });
}

// Generate section format automatically
function updateGeneratedSection() {
  const yearLevel = document.getElementById('sectionYearLevel').value;
  const term = document.getElementById('sectionTerm').value;
  const sectionNumber = document.getElementById('sectionNumber').value;
  const generatedSection = document.getElementById('generatedSection');
  
  if (yearLevel && term && sectionNumber) {
    generatedSection.value = `${yearLevel}/${term}-${sectionNumber}`;
  } else {
    generatedSection.value = '';
  }
}

// Add event listeners when modal is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Department change event
  const departmentSelect = document.getElementById('department');
  if (departmentSelect) {
    departmentSelect.addEventListener('change', updateProgramsAndYearLevels);
  }
  
  // Section format generation events
  const sectionYearLevel = document.getElementById('sectionYearLevel');
  const sectionTerm = document.getElementById('sectionTerm');
  const sectionNumber = document.getElementById('sectionNumber');
  
  if (sectionYearLevel) {
    sectionYearLevel.addEventListener('change', updateGeneratedSection);
  }
  if (sectionTerm) {
    sectionTerm.addEventListener('change', updateGeneratedSection);
  }
  if (sectionNumber) {
    sectionNumber.addEventListener('change', updateGeneratedSection);
  }
  
  // Form submission on Enter key
  const form = document.getElementById('studentRegistrationForm');
  if (form) {
    form.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitStudentRegistrationForm();
      }
    });
  }
  
  // Close modal on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeStudentRegistrationModal();
    }
  });
});
</script> 