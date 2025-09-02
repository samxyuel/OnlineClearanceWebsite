<?php
// Export Modal - Export Student Data
// This modal is included in StudentManagement.php
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay export-modal-overlay" id="exportModal">
  <div class="modal-window" style="max-width: 600px;">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeExportModal()">&times;</button>
    
    <!-- Modal Header -->
    <div class="modal-header">
      <h2 class="modal-title">📊 Export Student Data</h2>
      <div class="modal-supporting-text">Select export format, scope, and data to include in your export.</div>
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="exportForm" class="modal-form" data-endpoint="../../controllers/exportData.php">
        <input type="hidden" name="type" value="student_export">
        
        <!-- File Format Section -->
        <div class="export-section">
          <h3 class="section-title">📄 File Format</h3>
          <div class="radio-group">
            <label class="radio-option">
              <input type="radio" name="fileFormat" value="xlsx" checked>
              <span class="radio-custom"></span>
              <span class="radio-label">Excel (.xlsx)</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="fileFormat" value="csv">
              <span class="radio-custom"></span>
              <span class="radio-label">CSV (.csv)</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="fileFormat" value="pdf">
              <span class="radio-custom"></span>
              <span class="radio-label">PDF Report (.pdf)</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="fileFormat" value="json">
              <span class="radio-custom"></span>
              <span class="radio-label">JSON (.json)</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="fileFormat" value="xml">
              <span class="radio-custom"></span>
              <span class="radio-label">XML (.xml)</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="fileFormat" value="txt">
              <span class="radio-custom"></span>
              <span class="radio-label">Text (.txt)</span>
            </label>
          </div>
        </div>
        
        <!-- Export Scope Section -->
        <div class="export-section">
          <h3 class="section-title">🏫 Export Scope</h3>
          <div class="radio-group">
            <label class="radio-option">
              <input type="radio" name="exportScope" value="all" checked>
              <span class="radio-custom"></span>
              <span class="radio-label">All Students</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="exportScope" value="department">
              <span class="radio-custom"></span>
              <span class="radio-label">Department Only</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="exportScope" value="course">
              <span class="radio-custom"></span>
              <span class="radio-label">Specific Course</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="exportScope" value="yearLevel">
              <span class="radio-custom"></span>
              <span class="radio-label">Specific Year Level</span>
            </label>
          </div>
        </div>
        
        <!-- Department/Course/Year Selection -->
        <div class="export-section" id="filterSection" style="display: none;">
          <h3 class="section-title">🎓 Filter Selection</h3>
          
          <!-- Department Selection -->
          <div class="form-group">
            <label for="exportDepartment">Department</label>
            <select id="exportDepartment" name="department" onchange="updateExportCourseAndYear()">
              <option value="">Select Department</option>
              <option value="Tourism and Hospitality Management">Tourism and Hospitality Management</option>
              <option value="Information, Communication, and Technology">Information, Communication, and Technology</option>
              <option value="Business, Arts, and Science">Business, Arts, and Science</option>
              <option value="Senior High School">Senior High School</option>
            </select>
          </div>
          
          <!-- Course Selection -->
          <div class="form-group" id="courseGroup" style="display: none;">
            <label for="exportCourse">Course</label>
            <select id="exportCourse" name="course" onchange="updateExportYearLevel()">
              <option value="">All Courses</option>
            </select>
          </div>
          
          <!-- Year Level Selection -->
          <div class="form-group" id="yearGroup" style="display: none;">
            <label for="exportYearLevel">Year Level</label>
            <select id="exportYearLevel" name="yearLevel">
              <option value="">All Year Levels</option>
            </select>
          </div>
        </div>
        
        <!-- Column Selection Section -->
        <div class="export-section">
          <h3 class="section-title">📋 Include Columns</h3>
          <div class="checkbox-group">
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="studentNumber" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Student Number</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="name" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Name</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="program" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Program</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="yearLevel" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Year Level</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="section" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Section</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="accountStatus" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Account Status</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="clearanceStatus" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Clearance Status</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="email">
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Email</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="columns[]" value="contactNumber">
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Contact Number</span>
            </label>
          </div>
        </div>
        
        <!-- Export Options -->
        <div class="export-section">
          <h3 class="section-title">⚙️ Export Options</h3>
          <div class="form-group">
            <label for="exportFileName">File Name</label>
            <input type="text" id="exportFileName" name="fileName" 
                   placeholder="student_data_export" value="student_data_export">
          </div>
          <div class="checkbox-group">
            <label class="checkbox-option">
              <input type="checkbox" name="includeHeaders" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Include Column Headers</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="includeTimestamp">
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Include Export Timestamp</span>
            </label>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeExportModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitExportForm()" id="exportSubmitBtn">Export Data</button>
    </div>
  </div>
</div>

<style>
/* Export Modal Specific Styles */
.export-section {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--light-blue-gray);
}

.export-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--deep-navy-blue);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.radio-group, .checkbox-group {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.radio-option, .checkbox-option {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.radio-option:hover, .checkbox-option:hover {
  background-color: var(--very-light-off-white);
}

.radio-option input, .checkbox-option input {
  display: none;
}

.radio-custom, .checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid var(--light-blue-gray);
  border-radius: 50%;
  position: relative;
  transition: all 0.2s;
}

.checkbox-custom {
  border-radius: 4px;
}

.radio-option input:checked + .radio-custom {
  border-color: var(--medium-muted-blue);
  background-color: var(--medium-muted-blue);
}

.radio-option input:checked + .radio-custom::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 6px;
  height: 6px;
  background-color: white;
  border-radius: 50%;
}

.checkbox-option input:checked + .checkbox-custom {
  border-color: var(--medium-muted-blue);
  background-color: var(--medium-muted-blue);
}

.checkbox-option input:checked + .checkbox-custom::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.radio-label, .checkbox-label {
  font-size: 0.9rem;
  color: var(--deep-navy-blue);
  font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .radio-group, .checkbox-group {
    grid-template-columns: 1fr;
  }
  
  .modal-window {
    max-width: 95vw !important;
  }
}
</style>

<script>
// Department → Course mapping (same as other modals)
const exportDepartmentCourses = {
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
const exportDepartmentYearLevels = {
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

// Update export scope visibility
function updateExportScope() {
  const scope = document.querySelector('input[name="exportScope"]:checked').value;
  const filterSection = document.getElementById('filterSection');
  const departmentSelect = document.getElementById('exportDepartment');
  const courseGroup = document.getElementById('courseGroup');
  const yearGroup = document.getElementById('yearGroup');
  
  if (scope === 'all') {
    filterSection.style.display = 'none';
  } else {
    filterSection.style.display = 'block';
    
    if (scope === 'department') {
      courseGroup.style.display = 'none';
      yearGroup.style.display = 'none';
    } else if (scope === 'course') {
      courseGroup.style.display = 'block';
      yearGroup.style.display = 'none';
    } else if (scope === 'yearLevel') {
      courseGroup.style.display = 'block';
      yearGroup.style.display = 'block';
    }
  }
}

// Update course and year level dropdowns
function updateExportCourseAndYear() {
  const department = document.getElementById('exportDepartment').value;
  const courseSelect = document.getElementById('exportCourse');
  const yearSelect = document.getElementById('exportYearLevel');
  
  // Clear current options
  courseSelect.innerHTML = '<option value="">All Courses</option>';
  yearSelect.innerHTML = '<option value="">All Year Levels</option>';
  
  if (department) {
    // Update courses
    if (exportDepartmentCourses[department]) {
      exportDepartmentCourses[department].forEach(course => {
        const option = document.createElement('option');
        option.value = course;
        option.textContent = course;
        courseSelect.appendChild(option);
      });
    }
    
    // Update year levels
    if (exportDepartmentYearLevels[department]) {
      exportDepartmentYearLevels[department].forEach(yearLevel => {
        const option = document.createElement('option');
        option.value = yearLevel;
        option.textContent = yearLevel;
        yearSelect.appendChild(option);
      });
    }
  }
}

function updateExportYearLevel() {
  const department = document.getElementById('exportDepartment').value;
  const yearSelect = document.getElementById('exportYearLevel');
  
  // Clear current options
  yearSelect.innerHTML = '<option value="">All Year Levels</option>';
  
  if (department && exportDepartmentYearLevels[department]) {
    exportDepartmentYearLevels[department].forEach(yearLevel => {
      const option = document.createElement('option');
      option.value = yearLevel;
      option.textContent = yearLevel;
      yearSelect.appendChild(option);
    });
  }
}

// Form validation
function validateExportForm() {
  const scope = document.querySelector('input[name="exportScope"]:checked').value;
  const department = document.getElementById('exportDepartment').value;
  const course = document.getElementById('exportCourse').value;
  const yearLevel = document.getElementById('exportYearLevel').value;
  
  if (scope !== 'all') {
    if (!department) {
      showNotification('Please select a department', 'error');
      return false;
    }
    
    if (scope === 'course' && !course) {
      showNotification('Please select a course', 'error');
      return false;
    }
    
    if (scope === 'yearLevel' && !yearLevel) {
      showNotification('Please select a year level', 'error');
      return false;
    }
  }
  
  // Check if at least one column is selected
  const selectedColumns = document.querySelectorAll('input[name="columns[]"]:checked');
  if (selectedColumns.length === 0) {
    showNotification('Please select at least one column to export', 'error');
    return false;
  }
  
  return true;
}

// Modal functions
window.openExportModal = function() {
  document.getElementById('exportModal').style.display = 'flex';
  document.body.classList.add('modal-open');
  
  // Set default file name with timestamp
  const now = new Date();
  const timestamp = now.toISOString().slice(0, 10); // YYYY-MM-DD
  document.getElementById('exportFileName').value = `student_data_${timestamp}`;
}

window.closeExportModal = function() {
  document.getElementById('exportModal').style.display = 'none';
  document.body.classList.remove('modal-open');
  
  // Reset form
  document.getElementById('exportForm').reset();
  
  // Reset to defaults
  document.querySelector('input[name="fileFormat"][value="xlsx"]').checked = true;
  document.querySelector('input[name="exportScope"][value="all"]').checked = true;
  updateExportScope();
}

window.submitExportForm = function() {
  if (!validateExportForm()) {
    return;
  }
  
  const submitBtn = document.getElementById('exportSubmitBtn');
  const form = document.getElementById('exportForm');
  
  // Show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = 'Exporting...';
  form.classList.add('modal-loading');
  
  const formData = new FormData(form);
  const jsonData = {};
  formData.forEach((value, key) => { 
    if (key === 'columns[]') {
      if (!jsonData[key]) jsonData[key] = [];
      jsonData[key].push(value);
    } else {
      jsonData[key] = value; 
    }
  });
  
  // Simulate export process
  setTimeout(() => {
    const fileFormat = jsonData.fileFormat;
    const fileName = jsonData.fileName || 'student_data_export';
    
    showNotification(`Export completed! Downloading ${fileName}.${fileFormat}`, 'success');
    window.closeExportModal();
    
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.textContent = 'Export Data';
    form.classList.remove('modal-loading');
  }, 2000);
}

// Add event listeners when modal is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Export scope change event
  const scopeRadios = document.querySelectorAll('input[name="exportScope"]');
  scopeRadios.forEach(radio => {
    radio.addEventListener('change', updateExportScope);
  });
  
  // Form submission on Enter key
  const form = document.getElementById('exportForm');
  if (form) {
    form.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitExportForm();
      }
    });
  }
  
  // Close modal on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeExportModal();
    }
  });
});
</script> 