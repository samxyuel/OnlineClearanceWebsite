<?php
// Eligible for Graduation Modal - Manage 4th Year Student Graduation Status
// This modal allows administrators to select which 4th Year students are eligible to graduate
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay graduation-modal-overlay" id="eligibleForGraduationModal">
  <div class="modal-window graduation-modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeEligibleForGraduationModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">🎓 Eligible for Graduation</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">
      Select which 4th Year students are eligible to graduate. Students marked as "Graduated" will not be included in future clearance periods until their status is updated.
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <!-- Filter Controls -->
      <div class="graduation-filters">
        <div class="filter-group">
          <label for="graduationSector">Sector:</label>
          <select id="graduationSector" onchange="loadEligibleStudents()">
            <option value="">All Sectors</option>
            <option value="College">College</option>
            <option value="Senior High School">Senior High School</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="graduationDepartment">Department:</label>
          <select id="graduationDepartment" onchange="loadEligibleStudents()">
            <option value="">All Departments</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="graduationSearch">Search:</label>
          <input type="text" id="graduationSearch" placeholder="Search by name or student number..." 
                 onkeyup="debounceSearch()">
        </div>
      </div>
      
      <!-- Student List -->
      <div class="graduation-student-list">
        <div class="list-header">
          <div class="list-controls">
            <label class="checkbox-container">
              <input type="checkbox" id="selectAllStudents" onchange="toggleAllStudents()">
              <span class="checkmark"></span>
              Select All
            </label>
            <span class="selected-count" id="selectedCount">0 selected</span>
          </div>
          <div class="list-stats" id="listStats">
            Loading students...
          </div>
        </div>
        
        <div class="student-list-container" id="studentListContainer">
          <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p>Loading eligible students...</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeEligibleForGraduationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="processGraduationStatus()" id="processBtn" disabled>
        Update Graduation Status
      </button>
    </div>
  </div>
</div>


<script>
let eligibleStudents = [];
let selectedStudents = new Set();
let searchTimeout = null;

// Debounced search function
function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadEligibleStudents();
  }, 300);
}

// Load eligible students (4th Year students)
async function loadEligibleStudents() {
  const sector = document.getElementById('graduationSector').value;
  const department = document.getElementById('graduationDepartment').value;
  const search = document.getElementById('graduationSearch').value;
  
  const loadingSpinner = document.getElementById('loadingSpinner');
  const studentListContainer = document.getElementById('studentListContainer');
  const listStats = document.getElementById('listStats');
  
  // Show loading
  loadingSpinner.style.display = 'flex';
  studentListContainer.innerHTML = '<div class="loading-spinner" id="loadingSpinner"><div class="spinner"></div><p>Loading eligible students...</p></div>';
  
  try {
    // Build query parameters
    const params = new URLSearchParams();
    params.append('year_level', '4th Year');
    params.append('enrollment_status', 'Enrolled');
    
    if (sector) params.append('sector', sector);
    if (department) params.append('department_id', department);
    if (search) params.append('search', search);
    
    const response = await fetch(`../../api/users/get_eligible_students.php?${params.toString()}`, {
      credentials: 'include'
    });
    
    const data = await response.json();
    
    if (data.success) {
      eligibleStudents = data.students || [];
      renderStudentList();
      updateListStats();
    } else {
      throw new Error(data.message || 'Failed to load students');
    }
  } catch (error) {
    console.error('Error loading students:', error);
    studentListContainer.innerHTML = `
      <div class="no-students">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Error loading students: ${error.message}</p>
      </div>
    `;
    listStats.textContent = 'Error loading data';
  }
}

// Render the student list
function renderStudentList() {
  const container = document.getElementById('studentListContainer');
  
  if (eligibleStudents.length === 0) {
    container.innerHTML = `
      <div class="no-students">
        <i class="fas fa-graduation-cap"></i>
        <p>No 4th Year students found matching the current filters.</p>
      </div>
    `;
    return;
  }
  
  const studentItems = eligibleStudents.map(student => `
    <div class="student-item">
      <div class="student-checkbox">
        <input type="checkbox" 
               id="student_${student.user_id}" 
               value="${student.user_id}"
               onchange="toggleStudentSelection('${student.user_id}')"
               ${selectedStudents.has(student.user_id) ? 'checked' : ''}>
      </div>
      <div class="student-info">
        <div class="student-name">
          ${student.last_name}, ${student.first_name} ${student.middle_name || ''}
        </div>
        <div class="student-number">${student.student_id}</div>
        <div class="student-program">${student.program || 'N/A'}</div>
        <div class="student-section">${student.section || 'N/A'}</div>
      </div>
    </div>
  `).join('');
  
  container.innerHTML = studentItems;
}

// Update list statistics
function updateListStats() {
  const listStats = document.getElementById('listStats');
  const total = eligibleStudents.length;
  const selected = selectedStudents.size;
  
  listStats.textContent = `${total} students found, ${selected} selected`;
}

// Toggle individual student selection
function toggleStudentSelection(userId) {
  if (selectedStudents.has(userId)) {
    selectedStudents.delete(userId);
  } else {
    selectedStudents.add(userId);
  }
  
  updateSelectedCount();
  updateProcessButton();
  updateSelectAllCheckbox();
}

// Toggle all students selection
function toggleAllStudents() {
  const selectAllCheckbox = document.getElementById('selectAllStudents');
  
  if (selectAllCheckbox.checked) {
    // Select all visible students
    eligibleStudents.forEach(student => {
      selectedStudents.add(student.user_id);
    });
  } else {
    // Deselect all students
    selectedStudents.clear();
  }
  
  // Update checkboxes
  eligibleStudents.forEach(student => {
    const checkbox = document.getElementById(`student_${student.user_id}`);
    if (checkbox) {
      checkbox.checked = selectAllCheckbox.checked;
    }
  });
  
  updateSelectedCount();
  updateProcessButton();
}

// Update select all checkbox state
function updateSelectAllCheckbox() {
  const selectAllCheckbox = document.getElementById('selectAllStudents');
  const visibleStudentIds = eligibleStudents.map(s => s.user_id);
  const selectedVisibleCount = visibleStudentIds.filter(id => selectedStudents.has(id)).length;
  
  if (selectedVisibleCount === 0) {
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
  } else if (selectedVisibleCount === visibleStudentIds.length) {
    selectAllCheckbox.checked = true;
    selectAllCheckbox.indeterminate = false;
  } else {
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = true;
  }
}

// Update selected count display
function updateSelectedCount() {
  const selectedCount = document.getElementById('selectedCount');
  selectedCount.textContent = `${selectedStudents.size} selected`;
}

// Update process button state
function updateProcessButton() {
  const processBtn = document.getElementById('processBtn');
  processBtn.disabled = selectedStudents.size === 0;
}

// Process graduation status updates
async function processGraduationStatus() {
  if (selectedStudents.size === 0) {
    showToast('Please select at least one student to update.', 'warning');
    return;
  }
  
  const processBtn = document.getElementById('processBtn');
  const originalText = processBtn.textContent;
  
  // Disable button and show loading
  processBtn.disabled = true;
  processBtn.textContent = 'Processing...';
  
  try {
    const response = await fetch('../../api/users/update_graduation_status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        student_ids: Array.from(selectedStudents),
        action: 'graduate' // or 'retain' based on your business logic
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      showToast(`Successfully updated ${selectedStudents.size} student(s) graduation status.`, 'success');
      
      // Clear selections and reload
      selectedStudents.clear();
      await loadEligibleStudents();
      
      // Notify parent page if needed
      document.dispatchEvent(new CustomEvent('graduation-status-updated', { 
        detail: { updated_count: selectedStudents.size } 
      }));
      
    } else {
      throw new Error(data.message || 'Failed to update graduation status');
    }
  } catch (error) {
    console.error('Error updating graduation status:', error);
    showToast(`Error updating graduation status: ${error.message}`, 'error');
  } finally {
    // Re-enable button
    processBtn.disabled = false;
    processBtn.textContent = originalText;
  }
}

// Load departments based on selected sector
async function loadDepartments() {
  const sector = document.getElementById('graduationSector').value;
  const departmentSelect = document.getElementById('graduationDepartment');
  
  departmentSelect.innerHTML = '<option value="">All Departments</option>';
  
  if (!sector) {
    return;
  }
  
  try {
    const response = await fetch(`../../api/departments/list.php?sector=${encodeURIComponent(sector)}`);
    const data = await response.json();
    
    if (data.success && data.departments) {
      data.departments.forEach(dept => {
        const option = document.createElement('option');
        option.value = dept.department_id;
        option.textContent = dept.department_name;
        departmentSelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading departments:', error);
  }
}

// Close modal function
function closeEligibleForGraduationModal() {
  const modal = document.getElementById('eligibleForGraduationModal');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
  
  // Reset form
  selectedStudents.clear();
  eligibleStudents = [];
  
  // Reset filters
  document.getElementById('graduationSector').value = '';
  document.getElementById('graduationDepartment').innerHTML = '<option value="">All Departments</option>';
  document.getElementById('graduationSearch').value = '';
  
  // Reset UI
  document.getElementById('selectAllStudents').checked = false;
  document.getElementById('selectAllStudents').indeterminate = false;
  document.getElementById('selectedCount').textContent = '0 selected';
  document.getElementById('processBtn').disabled = true;
}

// Open modal function (called from parent page)
function openEligibleForGraduationModal() {
  const modal = document.getElementById('eligibleForGraduationModal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  
  // Load initial data
  loadEligibleStudents();
  
  // Set up department loading on sector change
  document.getElementById('graduationSector').addEventListener('change', loadDepartments);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Set up event listeners
  document.getElementById('graduationSector').addEventListener('change', loadDepartments);
});
</script>
