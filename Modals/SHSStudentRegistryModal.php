<?php
// SHS Student Registry Modal - Add New Senior High School Student
// This modal is specifically for Senior High School students only
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay student-registration-modal-overlay" id="studentRegistrationModal">
  <div class="modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeStudentRegistrationModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">🎓 Add New Senior High School Student</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Fill out the form below to register a new senior high school student.</div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="studentRegistrationForm" class="modal-form" data-endpoint="../../controllers/addUsers.php">
        <input type="hidden" name="type" value="student">
        <input type="hidden" name="sector" value="senior_high">
        
        <!-- Student Number -->
        <div class="form-group">
          <label for="studentNumber">Student Number *</label>
          <input type="text" id="studentNumber" name="studentNumber" required 
                 placeholder="e.g., 02000288327" maxlength="11">
        </div>
        
        <!-- Department (SHS Only) -->
        <div class="form-group">
          <label for="department">Department *</label>
          <select id="department" name="department" required readonly style="background-color: #e9ecef; pointer-events: none;">
            <option value="Senior High School" selected>Senior High School</option>
          </select>
        </div>
        
        <!-- Program (SHS Only) -->
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
            <option value="Grade 11">Grade 11</option>
            <option value="Grade 12">Grade 12</option>
          </select>
        </div>
        
        <!-- Section -->
        <div class="form-group">
          <label for="section">Section *</label>
          <input type="text" id="section" name="section" placeholder="e.g., 11/1-1" maxlength="10">
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
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="student@email.com" maxlength="100">
        </div>
        
        <div class="form-group">
          <label for="phoneNumber">Phone Number</label>
          <input type="tel" id="phoneNumber" name="phoneNumber" 
                 placeholder="+63 9XX XXX XXXX" maxlength="15">
        </div>
      </form>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeStudentRegistrationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitStudentRegistrationForm()" id="submitBtn">Generate Credentials</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/GeneratedCredentialsModal.php'; ?>

<script>
// Fetch programs and year levels when the modal is opened
async function updateProgramsAndYearLevels() {
  const department = document.getElementById('department').value;
  const programSelect = document.getElementById('program');
  const yearLevelSelect = document.getElementById('yearLevel'); // Year level is now static
  
  // Clear current options
  programSelect.innerHTML = '<option value="">Select Program</option>';
  yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
  programSelect.disabled = true;
  yearLevelSelect.disabled = true;
  
  if (department) {
    try {
      // The department name in the dropdown is "Senior High School"
      const response = await fetch(`../../controllers/programs.php?department_type=${encodeURIComponent(department)}`);
      const data = await response.json();

      if (data.success) {
        // Populate programs
        data.programs.forEach(program => {
          const option = document.createElement('option');
          option.value = program.value;
          option.textContent = program.display;
          programSelect.appendChild(option);
        });

        // Populate year levels
        data.year_levels.forEach(year => {
          const option = document.createElement('option');
          option.value = year;
          option.textContent = year;
          yearLevelSelect.appendChild(option);
        });

        programSelect.disabled = false;
        yearLevelSelect.disabled = false;
      } else {
        showToast(data.message || 'Could not load programs.', 'error');
      }
    } catch (error) {
      console.error('Failed to fetch academic data:', error);
      showToast('An error occurred while loading programs.', 'error');
    }
  }
}

async function onUserCreated(newUserId, userSector) {
    // Only proceed if a valid sector is provided
    if (!userSector) return;

    try {
        // 1. Check for an active clearance period
        const context = await fetch('../../api/clearance/context.php', { credentials: 'include' }).then(r => r.json());
        const activeSemester = context.terms.find(t => t.is_active === 1);

        if (activeSemester) {
            console.log(`Active period found for ${activeSemester.semester_name}. Creating clearance form for new user...`);

            // 2. Call the distribution API for the single new user
            const response = await fetch('../../api/clearance/form_distribution.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    user_id: newUserId,
                    clearance_type: userSector,
                    academic_year_id: context.academic_year.academic_year_id,
                    semester_id: activeSemester.semester_id
                })
            }).then(r => r.json());
            console.log('Auto form generation response:', response);
        }
    } catch (error) {
        console.error('Failed to auto-generate clearance form for new user:', error);
    }
}

// Form validation and submission
function validateStudentRegistrationForm() {
  const form = document.getElementById('studentRegistrationForm');
  const formData = new FormData(form);
  
  // Check required fields
  const requiredFields = ['studentNumber', 'department', 'program', 'yearLevel', 'lastName', 'firstName'];
  
  for (const field of requiredFields) {
    const input = form.querySelector(`[name="${field}"]`);
    if (!input.value.trim()) {
      showToast(`Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()}`, 'error');
      input.focus();
      return false;
    }
  }
  
  // Validate email format
  const email = formData.get('email');
  if (email) { // Only validate if an email is provided
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showToast('Please enter a valid email address', 'error');
      document.getElementById('email').focus();
      return false;
    }
  }
  
  return true;
}

function submitStudentRegistrationForm() {
  if (!validateStudentRegistrationForm()) {
    return;
  }

  // Generate credentials locally first
  const form = document.getElementById('studentRegistrationForm');
  const studentId = form.studentNumber.value.trim();
  const lastName = form.lastName.value.trim().replace(/\s+/g, '');
  const username = studentId; // Use student number as username
  const password = `${lastName}${studentId}`; // e.g., Doe02000288327

  // Prepare the data for the modal and the final submission
  const credentialData = { username, password };

  // The callback function that will be executed when "Confirm & Save" is clicked
  const confirmCallback = () => {
    // Pass the generated credentials along with the form data
    confirmStudentCreation(credentialData);
  };

  // Open the unified credentials modal
  openGeneratedCredentialsModal('newAccount', credentialData, confirmCallback);
}

function confirmStudentCreation(credentialData) {
  const form = document.getElementById('studentRegistrationForm');
  const formData = {
    studentNumber: form.studentNumber.value.trim(),
    department: form.department.value,
    program: form.program.value,
    yearLevel: form.yearLevel.value,
    section: form.section.value.trim(),
    firstName: form.firstName.value.trim(),
    lastName: form.lastName.value.trim(),
    middleName: form.middleName.value.trim() || null,
    email: form.email.value.trim() || null,
    phoneNumber: form.phoneNumber.value.trim() || null,
    username: credentialData.username,
    password: credentialData.password,
    confirmPassword: credentialData.password, // Add confirmPassword for validation
    sector: 'senior_high'
  };

  const confirmBtn = document.getElementById('credentialModalConfirmBtn');
  if(confirmBtn) confirmBtn.disabled = true;
  const submitBtn = document.getElementById('submitBtn');
  if(submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding Student...';
  }

  fetch('../../controllers/addUsers.php', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(Object.entries(formData))
  })
  .then(r => r.json())
  .then(res => {
    if(res.success){
      showToastNotification('Student registered successfully!', 'success');
      closeGeneratedCredentialsModal();
      closeStudentRegistrationModal();
      // Refresh the student list
      if (typeof loadStudentsData === 'function') {
        loadStudentsData();
      }
      // Trigger automatic clearance form creation
      const newUserId = res.user_id || null;
      if (newUserId) {
        onUserCreated(newUserId, 'Senior High School').catch(console.error);
      }
      // notify parent page
      document.dispatchEvent(new CustomEvent('student-added', { detail: { student_number: formData.student_number } }));
    } else {
      showToastNotification(res.message || 'Error registering student', 'error');
      if(confirmBtn) confirmBtn.disabled = false;
    }
  })
  .catch(err => {
    console.error(err);
    showToastNotification('Network error', 'error');
    if(confirmBtn) confirmBtn.disabled = false;
  })
  .finally(() => {
    if(submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Generate Credentials';
    }
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
}

// Open modal function (called from parent page)
function openStudentRegistrationModal() {
  const modal = document.getElementById('studentRegistrationModal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  // Fetch programs as soon as the modal opens
  updateProgramsAndYearLevels();
  
  // Focus on first input
  setTimeout(() => {
    document.getElementById('studentNumber').focus();
  }, 100);
}
</script>
