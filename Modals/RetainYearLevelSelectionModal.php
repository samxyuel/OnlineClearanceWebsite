<?php
// Retain Year Level Selection Modal - Manage Student Year Level Retention
// This modal allows administrators to select students who will retain their year level
// when a new school year is created
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay retention-modal-overlay" id="retainYearLevelModal">
  <div class="modal-window retention-modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="window.closeRetainYearLevelModal && window.closeRetainYearLevelModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">📚 Retain Year Level Selection</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">
      Select students who will retain their current year level when the next school year is created. 
      These selections apply only to the next school year being created.
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <!-- Tab Navigation -->
      <div class="retention-tabs">
        <button class="retention-tab active" onclick="window.switchRetentionTab && window.switchRetentionTab('shs')" id="retentionTabShs">
          <i class="fas fa-graduation-cap"></i> Senior High School
        </button>
        <button class="retention-tab" onclick="window.switchRetentionTab && window.switchRetentionTab('college')" id="retentionTabCollege">
          <i class="fas fa-university"></i> College
        </button>
      </div>
      
      <!-- SHS Tab Content -->
      <div class="retention-tab-content active" id="retentionContentShs">
        <!-- Filter Controls -->
        <div class="retention-filters">
          <div class="filter-group">
            <label for="retentionShsDepartment">Department:</label>
            <select id="retentionShsDepartment" onchange="window.handleRetentionDepartmentChange && window.handleRetentionDepartmentChange('shs')">
              <option value="">All Departments</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="retentionShsProgram">Program:</label>
            <select id="retentionShsProgram" onchange="window.loadRetentionStudents && window.loadRetentionStudents('shs')">
              <option value="">All Programs</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="retentionShsYearLevel">Year Level:</label>
            <select id="retentionShsYearLevel" onchange="window.loadRetentionStudents && window.loadRetentionStudents('shs')">
              <option value="">All Year Levels</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="retentionShsSearch">Search:</label>
            <input type="text" id="retentionShsSearch" placeholder="Search by name or student number..." 
                   onkeyup="window.debounceRetentionSearch && window.debounceRetentionSearch('shs')">
          </div>
        </div>
        
        <!-- Student List -->
        <div class="retention-student-list">
          <div class="list-header">
            <div class="list-controls">
              <label class="checkbox-container">
                <input type="checkbox" id="selectAllShs" onchange="window.toggleAllRetentionStudents && window.toggleAllRetentionStudents('shs')">
                <span class="checkmark"></span>
                Select All
              </label>
              <span class="selected-count" id="selectedCountShs">0 selected</span>
            </div>
            <div class="list-stats" id="listStatsShs">
              Loading students...
            </div>
          </div>
          
          <div class="student-list-container" id="studentListContainerShs">
            <div class="loading-spinner">
              <div class="spinner"></div>
              <p>Loading students...</p>
            </div>
          </div>
        </div>
        
        <!-- Selected Students Preview (SHS) -->
        <div class="selected-retention-preview" id="selectedRetentionPreview-shs">
          <h4>Selected Students for Retention (SHS):</h4>
          <div class="selected-list" id="selectedRetentionListShs">
            <p class="no-selection">No students selected</p>
          </div>
        </div>
      </div>
      
      <!-- College Tab Content -->
      <div class="retention-tab-content" id="retentionContentCollege">
        <!-- Filter Controls -->
        <div class="retention-filters">
          <div class="filter-group">
            <label for="retentionCollegeDepartment">Department:</label>
            <select id="retentionCollegeDepartment" onchange="window.handleRetentionDepartmentChange && window.handleRetentionDepartmentChange('college')">
              <option value="">All Departments</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="retentionCollegeProgram">Program:</label>
            <select id="retentionCollegeProgram" onchange="window.loadRetentionStudents && window.loadRetentionStudents('college')">
              <option value="">All Programs</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="retentionCollegeYearLevel">Year Level:</label>
            <select id="retentionCollegeYearLevel" onchange="window.loadRetentionStudents && window.loadRetentionStudents('college')">
              <option value="">All Year Levels</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="retentionCollegeSearch">Search:</label>
            <input type="text" id="retentionCollegeSearch" placeholder="Search by name or student number..." 
                   onkeyup="window.debounceRetentionSearch && window.debounceRetentionSearch('college')">
          </div>
        </div>
        
        <!-- Student List -->
        <div class="retention-student-list">
          <div class="list-header">
            <div class="list-controls">
              <label class="checkbox-container">
                <input type="checkbox" id="selectAllCollege" onchange="window.toggleAllRetentionStudents && window.toggleAllRetentionStudents('college')">
                <span class="checkmark"></span>
                Select All
              </label>
              <span class="selected-count" id="selectedCountCollege">0 selected</span>
            </div>
            <div class="list-stats" id="listStatsCollege">
              Loading students...
            </div>
          </div>
          
          <div class="student-list-container" id="studentListContainerCollege">
            <div class="loading-spinner">
              <div class="spinner"></div>
              <p>Loading students...</p>
            </div>
          </div>
        </div>
        
        <!-- Selected Students Preview (College) -->
        <div class="selected-retention-preview" id="selectedRetentionPreview-college">
          <h4>Selected Students for Retention (College):</h4>
          <div class="selected-list" id="selectedRetentionListCollege">
            <p class="no-selection">No students selected</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="window.closeRetainYearLevelModal && window.closeRetainYearLevelModal()">Cancel</button>
      <button class="modal-action-primary" onclick="window.saveRetentionSelections && window.saveRetentionSelections()" id="saveRetentionBtn">
        Save Retention Selection
      </button>
    </div>
  </div>
</div>

<script>
// Debug: Log that functions are being defined
console.log('🔧 Defining retention modal functions...');

let retentionStudents = {
  shs: [],
  college: []
};
let selectedRetentionStudents = new Set();
let retentionSearchTimeouts = {
  shs: null,
  college: null
};
let currentRetentionTab = 'shs';

// Switch between SHS and College tabs - Make it globally accessible
window.switchRetentionTab = function(tab) {
  currentRetentionTab = tab;
  
  // Update tab buttons
  document.querySelectorAll('.retention-tab').forEach(btn => btn.classList.remove('active'));
  document.getElementById(`retentionTab${tab === 'shs' ? 'Shs' : 'College'}`).classList.add('active');
  
  // Update tab content
  document.querySelectorAll('.retention-tab-content').forEach(content => content.classList.remove('active'));
  document.getElementById(`retentionContent${tab === 'shs' ? 'Shs' : 'College'}`).classList.add('active');
  
  // If we already have students loaded for this tab, just render them
  // Otherwise, load them
  if (retentionStudents[tab] && retentionStudents[tab].length > 0) {
    renderRetentionStudentList(tab);
    updateRetentionListStats(tab);
  } else {
    window.loadRetentionStudents(tab);
  }
};

// Debounced search function - Make it globally accessible
window.debounceRetentionSearch = function(tab) {
  clearTimeout(retentionSearchTimeouts[tab]);
  retentionSearchTimeouts[tab] = setTimeout(() => {
    window.loadRetentionStudents(tab);
  }, 300);
};

// Handle department change - load programs and reload students - Make it globally accessible
window.handleRetentionDepartmentChange = async function(tab) {
  const departmentId = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}Department`).value;
  const programSelect = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}Program`);
  
  // Reset program dropdown
  programSelect.innerHTML = '<option value="">All Programs</option>';
  
  // Load programs for the selected department
  if (departmentId) {
    try {
      const response = await fetch(`../../api/programs/list.php?department_id=${departmentId}`, {
        method: 'GET',
        credentials: 'include'
      });
      
      const data = await response.json();
      
      if (data.success && data.programs) {
        data.programs.forEach(program => {
          const option = document.createElement('option');
          option.value = program.program_id;
          option.textContent = program.program_name;
          programSelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Error loading programs:', error);
    }
  }
  
  // Reload students with new department filter
  window.loadRetentionStudents(tab);
};

// Load retention students - Make it globally accessible
window.loadRetentionStudents = async function(tab) {
  const sector = tab === 'shs' ? 'Senior High School' : 'College';
  const department = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}Department`).value;
  const program = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}Program`).value;
  const yearLevel = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}YearLevel`).value;
  const search = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}Search`).value;
  
  const container = document.getElementById(`studentListContainer${tab === 'shs' ? 'Shs' : 'College'}`);
  const listStats = document.getElementById(`listStats${tab === 'shs' ? 'Shs' : 'College'}`);
  
  // Show loading
  if (container) {
    container.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><p>Loading students...</p></div>';
  }
  
  try {
    // Build query parameters
    const params = new URLSearchParams();
    params.append('sector', sector);
    if (department) params.append('department_id', department);
    if (program) params.append('program_id', program);
    if (yearLevel) params.append('year_level', yearLevel);
    if (search) params.append('search', search);
    
    console.log(`Loading ${sector} students for retention:`, params.toString());
    
    const response = await fetch(`../../api/users/year_level_retention.php?${params.toString()}`, {
      method: 'GET',
      credentials: 'include'
    });
    
    // Read response text once
    const responseText = await response.text();
    console.log(`Raw API Response for ${sector}:`, responseText.substring(0, 500));
    
    // Check if response is ok
    if (!response.ok) {
      try {
        const errorData = JSON.parse(responseText);
        throw new Error(errorData.message || `HTTP ${response.status}: ${responseText}`);
      } catch (parseError) {
        throw new Error(`HTTP ${response.status}: ${responseText || 'Unknown error'}`);
      }
    }
    
    // Parse JSON response
    let data;
    try {
      data = JSON.parse(responseText);
    } catch (parseError) {
      console.error('JSON Parse Error:', parseError);
      console.error('Response text:', responseText);
      throw new Error('Invalid JSON response from server');
    }
    
    console.log(`API Response for ${sector}:`, data);
    
    if (data.success) {
      retentionStudents[tab] = data.data?.students || [];
      console.log(`Loaded ${retentionStudents[tab].length} ${sector} students`);
      
      renderRetentionStudentList(tab);
      updateRetentionListStats(tab);
    } else {
      throw new Error(data.message || 'Failed to load students');
    }
  } catch (error) {
    console.error('Error loading students:', error);
    if (container) {
      container.innerHTML = `
        <div class="no-students">
          <i class="fas fa-exclamation-triangle"></i>
          <p>Error loading students: ${error.message}</p>
        </div>
      `;
    }
    if (listStats) {
      listStats.textContent = 'Error loading data';
    }
  }
};

// Render the student list for retention
function renderRetentionStudentList(tab) {
  const container = document.getElementById(`studentListContainer${tab === 'shs' ? 'Shs' : 'College'}`);
  const students = retentionStudents[tab];
  
  if (students.length === 0) {
    container.innerHTML = `
      <div class="no-students">
        <i class="fas fa-user-graduate"></i>
        <p>No students found matching the current filters.</p>
      </div>
    `;
    return;
  }
  
  const studentItems = students.map(student => {
    const isSelected = selectedRetentionStudents.has(student.user_id);
    // Handle TINYINT(1) - can be 0, 1, or string "0"/"1"
    const isRetained = student.retain_year_level_for_next_year == 1 || student.retain_year_level_for_next_year === '1' || student.retain_year_level_for_next_year === true;
    
    return `
      <div class="student-item ${isRetained ? 'retained' : ''}">
        <div class="student-checkbox">
          <input type="checkbox" 
                 id="retention_${tab}_${student.user_id}" 
                 value="${student.user_id}"
                 onchange="if(window.toggleRetentionSelection){window.toggleRetentionSelection('${student.user_id}', '${tab}');}"
                 ${isSelected ? 'checked' : ''}
                 ${isRetained ? 'checked' : ''}>
        </div>
        <div class="student-info">
          <div class="student-name">
            ${student.last_name}, ${student.first_name} ${student.middle_name || ''}
            ${isRetained ? '<span class="retention-badge">Currently Retained</span>' : ''}
          </div>
          <div class="student-number">${student.student_id}</div>
          <div class="student-program">${student.program || 'N/A'}</div>
          <div class="student-section">${student.section || 'N/A'} • ${student.year_level}</div>
        </div>
      </div>
    `;
  }).join('');
  
  container.innerHTML = studentItems;
  
  // Update selected students set with currently retained students
  // Handle TINYINT(1) - can be 0, 1, or string "0"/"1"
  students.forEach(student => {
    const isRetained = student.retain_year_level_for_next_year == 1 || student.retain_year_level_for_next_year === '1' || student.retain_year_level_for_next_year === true;
    if (isRetained) {
      selectedRetentionStudents.add(student.user_id);
    }
  });
  
  updateRetentionSelectedCount();
  updateRetentionSelectAllCheckbox(tab);
  updateRetentionPreview(tab);
}

// Update list statistics
function updateRetentionListStats(tab) {
  const listStats = document.getElementById(`listStats${tab === 'shs' ? 'Shs' : 'College'}`);
  const total = retentionStudents[tab].length;
  const selected = Array.from(selectedRetentionStudents).filter(id => {
    const student = retentionStudents[tab].find(s => s.user_id == id);
    return student !== undefined;
  }).length;
  
  listStats.textContent = `${total} students found, ${selected} selected`;
}

// Toggle individual student selection - Make it globally accessible
window.toggleRetentionSelection = function(userId, tab) {
  if (selectedRetentionStudents.has(userId)) {
    selectedRetentionStudents.delete(userId);
  } else {
    selectedRetentionStudents.add(userId);
  }
  
  updateRetentionSelectedCount();
  updateRetentionSelectAllCheckbox(tab);
  updateRetentionPreview(tab);
};

// Toggle all students selection - Make it globally accessible
window.toggleAllRetentionStudents = function(tab) {
  const selectAllCheckbox = document.getElementById(`selectAll${tab === 'shs' ? 'Shs' : 'College'}`);
  const students = retentionStudents[tab];
  
  if (selectAllCheckbox.checked) {
    // Select all visible students
    students.forEach(student => {
      selectedRetentionStudents.add(student.user_id);
    });
  } else {
    // Deselect all students in this tab
    students.forEach(student => {
      selectedRetentionStudents.delete(student.user_id);
    });
  }
  
  // Update checkboxes
  students.forEach(student => {
    const checkbox = document.getElementById(`retention_${tab}_${student.user_id}`);
    if (checkbox) {
      checkbox.checked = selectAllCheckbox.checked;
    }
  });
  
  updateRetentionSelectedCount();
  updateRetentionPreview(tab);
};

// Update select all checkbox state
function updateRetentionSelectAllCheckbox(tab) {
  const selectAllCheckbox = document.getElementById(`selectAll${tab === 'shs' ? 'Shs' : 'College'}`);
  const students = retentionStudents[tab];
  const visibleStudentIds = students.map(s => s.user_id);
  const selectedVisibleCount = visibleStudentIds.filter(id => selectedRetentionStudents.has(id)).length;
  
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
function updateRetentionSelectedCount() {
  const totalSelected = selectedRetentionStudents.size;
  const shsSelected = Array.from(selectedRetentionStudents).filter(id => {
    return retentionStudents.shs.find(s => s.user_id == id) !== undefined;
  }).length;
  const collegeSelected = Array.from(selectedRetentionStudents).filter(id => {
    return retentionStudents.college.find(s => s.user_id == id) !== undefined;
  }).length;
  
  document.getElementById('selectedCountShs').textContent = `${shsSelected} selected`;
  document.getElementById('selectedCountCollege').textContent = `${collegeSelected} selected`;
}

// Update selected students preview for a specific tab
function updateRetentionPreview(tab) {
  const previewList = document.getElementById(`selectedRetentionList${tab === 'shs' ? 'Shs' : 'College'}`);
  const previewContainer = document.getElementById(`selectedRetentionPreview-${tab}`);
  
  if (!previewList || !previewContainer) {
    console.error(`Preview elements not found for ${tab}`);
    return;
  }
  
  // Get selected students for this specific tab only
  const students = retentionStudents[tab] || [];
  const selectedForTab = students.filter(s => selectedRetentionStudents.has(s.user_id));
  
  if (selectedForTab.length === 0) {
    previewList.innerHTML = '<p class="no-selection">No students selected</p>';
    return;
  }
  
  const previewItems = selectedForTab.map(student => `
    <div class="preview-item">
      <span class="preview-name">${student.last_name}, ${student.first_name}</span>
      <span class="preview-details">${student.student_id} • ${student.year_level} • ${student.program || 'N/A'}</span>
    </div>
  `).join('');
  
  previewList.innerHTML = previewItems;
}

// Save retention selections - Make it globally accessible
window.saveRetentionSelections = async function() {
  if (selectedRetentionStudents.size === 0) {
    showToast('Please select at least one student to retain their year level.', 'warning');
    return;
  }
  
  const saveBtn = document.getElementById('saveRetentionBtn');
  const originalText = saveBtn.textContent;
  
  // Disable button and show loading
  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving...';
  
  try {
    const response = await fetch('../../api/users/year_level_retention.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        student_ids: Array.from(selectedRetentionStudents)
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      const updatedCount = data.data.updated_count || selectedRetentionStudents.size;
      showToast(`Successfully set retention for ${updatedCount} student(s).`, 'success');
      
      // Reload both tabs to show updated retention status
      await window.loadRetentionStudents('shs');
      await window.loadRetentionStudents('college');
      
      // Notify parent page if needed
      document.dispatchEvent(new CustomEvent('retention-selections-updated', { 
        detail: { updated_count: updatedCount } 
      }));
      
    } else {
      throw new Error(data.message || 'Failed to save retention selections');
    }
  } catch (error) {
    console.error('Error saving retention selections:', error);
    showToast(`Error saving retention selections: ${error.message}`, 'error');
  } finally {
    // Re-enable button
    saveBtn.disabled = false;
    saveBtn.textContent = originalText;
  }
};

// Load departments based on selected sector
async function loadRetentionDepartments(tab) {
  const sector = tab === 'shs' ? 'Senior High School' : 'College';
  const departmentSelect = document.getElementById(`retention${tab === 'shs' ? 'Shs' : 'College'}Department`);
  
  departmentSelect.innerHTML = '<option value="">All Departments</option>';
  
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

// Close modal function - Make it globally accessible
window.closeRetainYearLevelModal = function() {
  console.log('[RetainYearLevelSelectionModal] closeRetainYearLevelModal() called');
  try {
    const modal = document.getElementById('retainYearLevelModal');
    if (!modal) {
      console.warn('[RetainYearLevelSelectionModal] Modal not found');
      return;
    }
    console.log('[RetainYearLevelSelectionModal] Closing modal:', modal.id);

    // Use window.closeModal if available, otherwise fallback
    if (typeof window.closeModal === 'function') {
      window.closeModal('retainYearLevelModal');
    } else {
      // Fallback to direct manipulation
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
      document.body.classList.remove('modal-open');
      modal.classList.remove('active');
    }
  
    // Reset form
    selectedRetentionStudents.clear();
  retentionStudents = { shs: [], college: [] };
  
  // Reset filters
  document.getElementById('retentionShsDepartment').innerHTML = '<option value="">All Departments</option>';
  document.getElementById('retentionCollegeDepartment').innerHTML = '<option value="">All Departments</option>';
  document.getElementById('retentionShsProgram').innerHTML = '<option value="">All Programs</option>';
  document.getElementById('retentionCollegeProgram').innerHTML = '<option value="">All Programs</option>';
  document.getElementById('retentionShsYearLevel').value = '';
  document.getElementById('retentionCollegeYearLevel').value = '';
  document.getElementById('retentionShsSearch').value = '';
  document.getElementById('retentionCollegeSearch').value = '';
  
  // Reset UI
  document.getElementById('selectAllShs').checked = false;
  document.getElementById('selectAllCollege').checked = false;
  document.getElementById('selectedCountShs').textContent = '0 selected';
  document.getElementById('selectedCountCollege').textContent = '0 selected';
  
  // Reset to SHS tab
  window.switchRetentionTab('shs');
  } catch (error) {
    console.error('[RetainYearLevelSelectionModal] Error closing modal:', error);
  }
};

// Open modal function (called from parent page) - Make it globally accessible
window.openRetainYearLevelModal = function() {
  try {
    const modal = document.getElementById('retainYearLevelModal');
    if (!modal) {
      if (typeof showToastNotification === 'function') {
        showToastNotification('Retain year level modal not found. Please refresh the page.', 'error');
      }
      return;
    }

    // Use window.openModal if available, otherwise fallback
    if (typeof window.openModal === 'function') {
      window.openModal('retainYearLevelModal');
    } else {
      // Fallback to direct manipulation
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
      requestAnimationFrame(() => {
        modal.classList.add('active');
      });
    }
  
  // Set SHS tab as active (without triggering load)
  currentRetentionTab = 'shs';
  document.querySelectorAll('.retention-tab').forEach(btn => btn.classList.remove('active'));
  const shsTab = document.getElementById('retentionTabShs');
  if (shsTab) shsTab.classList.add('active');
  document.querySelectorAll('.retention-tab-content').forEach(content => content.classList.remove('active'));
  const shsContent = document.getElementById('retentionContentShs');
  if (shsContent) shsContent.classList.add('active');
  
  // Load initial data for both tabs after a small delay to ensure DOM is ready
  setTimeout(() => {
    loadRetentionDepartments('shs');
    loadRetentionDepartments('college');
    // Load students for both tabs
    window.loadRetentionStudents('shs');
    window.loadRetentionStudents('college');
    // Initialize previews (will be updated after students load)
    updateRetentionPreview('shs');
    updateRetentionPreview('college');
  }, 100);
  } catch (error) {
    console.error('[RetainYearLevelSelectionModal] Error opening modal:', error);
    if (typeof showToastNotification === 'function') {
      showToastNotification('Unable to open retention modal. Please try again.', 'error');
    }
  }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Log all functions after they're all defined
  console.log('✅ Retention modal functions defined:', {
    openRetainYearLevelModal: typeof window.openRetainYearLevelModal,
    closeRetainYearLevelModal: typeof window.closeRetainYearLevelModal,
    switchRetentionTab: typeof window.switchRetentionTab,
    debounceRetentionSearch: typeof window.debounceRetentionSearch,
    handleRetentionDepartmentChange: typeof window.handleRetentionDepartmentChange,
    loadRetentionStudents: typeof window.loadRetentionStudents,
    toggleRetentionSelection: typeof window.toggleRetentionSelection,
    toggleAllRetentionStudents: typeof window.toggleAllRetentionStudents,
    saveRetentionSelections: typeof window.saveRetentionSelections
  });
});
</script>

