<?php
// Eligible for Graduation Modal - Manage Student Graduation Status
// This modal allows administrators to select which students are eligible to graduate
// SHS: 2nd Year students | College: 4th Year students
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay graduation-modal-overlay" id="eligibleForGraduationModal">
  <div class="modal-window graduation-modal-window">
    <!-- Close Button -->
    <button class="modal-close" onclick="window.closeEligibleForGraduationModal && window.closeEligibleForGraduationModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">🎓 Eligible for Graduation</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">
      Select students who are eligible to graduate. Students marked as "Graduated" will be excluded from future clearance periods until their status is updated.
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <!-- Tab Navigation -->
      <div class="graduation-tabs">
        <button class="graduation-tab active" onclick="window.switchGraduationTab && window.switchGraduationTab('shs')" id="graduationTabShs">
          <i class="fas fa-graduation-cap"></i> Senior High School
        </button>
        <button class="graduation-tab" onclick="window.switchGraduationTab && window.switchGraduationTab('college')" id="graduationTabCollege">
          <i class="fas fa-university"></i> College
        </button>
      </div>
      
      <!-- SHS Tab Content -->
      <div class="graduation-tab-content active" id="graduationContentShs">
        <!-- Filter Controls -->
        <div class="graduation-filters">
          <div class="filter-group">
            <label for="graduationShsDepartment">Department:</label>
            <select id="graduationShsDepartment" onchange="window.handleDepartmentChange && window.handleDepartmentChange('shs')">
              <option value="">All Departments</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="graduationShsProgram">Program:</label>
            <select id="graduationShsProgram" onchange="window.loadEligibleStudents && window.loadEligibleStudents('shs')">
              <option value="">All Programs</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="graduationShsSearch">Search:</label>
            <input type="text" id="graduationShsSearch" placeholder="Search by name or student number..." 
                   onkeyup="window.debounceGraduationSearch && window.debounceGraduationSearch('shs')">
          </div>
        </div>
        
        <!-- Student List -->
        <div class="graduation-student-list">
          <div class="list-header">
            <div class="list-controls">
              <label class="checkbox-container" onclick="event.preventDefault(); const cb = document.getElementById('selectAllShs'); if(cb && window.toggleAllGraduationStudents){const newState = !cb.checked; cb.checked = newState; window.toggleAllGraduationStudents('shs', newState);}">
                <input type="checkbox" id="selectAllShs" onclick="event.stopPropagation(); if(window.toggleAllGraduationStudents){const newState = this.checked; window.toggleAllGraduationStudents('shs', newState);}">
                <span class="checkmark"></span>
                Select All
              </label>
              <span class="selected-count" id="selectedCountShs">0 selected</span>
            </div>
            <div class="list-stats" id="listStatsShs">
              Loading students...
            </div>
          </div>
          
          <div class="student-list-container" id="studentListContainer-shs">
            <div class="loading-spinner">
              <div class="spinner"></div>
              <p>Loading eligible students...</p>
            </div>
          </div>
        </div>
        
        <!-- Selected Students Summary (SHS) -->
        <div class="selected-graduation-summary" id="selectedGraduationSummary-shs">
          <div class="summary-header">
            <h4><i class="fas fa-check-circle"></i> Selected Students for Graduation (SHS)</h4>
            <span class="summary-count" id="summaryCountShs">0 selected</span>
          </div>
          <div class="selected-list" id="selectedListShs">
            <p class="no-selection">No students selected</p>
          </div>
        </div>
      </div>
      
      <!-- College Tab Content -->
      <div class="graduation-tab-content" id="graduationContentCollege">
        <!-- Filter Controls -->
        <div class="graduation-filters">
          <div class="filter-group">
            <label for="graduationCollegeDepartment">Department:</label>
            <select id="graduationCollegeDepartment" onchange="window.handleDepartmentChange && window.handleDepartmentChange('college')">
              <option value="">All Departments</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="graduationCollegeProgram">Program:</label>
            <select id="graduationCollegeProgram" onchange="window.loadEligibleStudents && window.loadEligibleStudents('college')">
              <option value="">All Programs</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="graduationCollegeSearch">Search:</label>
            <input type="text" id="graduationCollegeSearch" placeholder="Search by name or student number..." 
                   onkeyup="window.debounceGraduationSearch && window.debounceGraduationSearch('college')">
          </div>
        </div>
        
        <!-- Student List -->
        <div class="graduation-student-list">
          <div class="list-header">
            <div class="list-controls">
              <label class="checkbox-container" onclick="event.preventDefault(); const cb = document.getElementById('selectAllCollege'); if(cb && window.toggleAllGraduationStudents){const newState = !cb.checked; cb.checked = newState; window.toggleAllGraduationStudents('college', newState);}">
                <input type="checkbox" id="selectAllCollege" onclick="event.stopPropagation(); if(window.toggleAllGraduationStudents){const newState = this.checked; window.toggleAllGraduationStudents('college', newState);}">
                <span class="checkmark"></span>
                Select All
              </label>
              <span class="selected-count" id="selectedCountCollege">0 selected</span>
            </div>
            <div class="list-stats" id="listStatsCollege">
              Loading students...
            </div>
          </div>
          
          <div class="student-list-container" id="studentListContainer-college">
            <div class="loading-spinner">
              <div class="spinner"></div>
              <p>Loading eligible students...</p>
            </div>
          </div>
        </div>
        
        <!-- Selected Students Summary (College) -->
        <div class="selected-graduation-summary" id="selectedGraduationSummary-college">
          <div class="summary-header">
            <h4><i class="fas fa-check-circle"></i> Selected Students for Graduation (College)</h4>
            <span class="summary-count" id="summaryCountCollege">0 selected</span>
          </div>
          <div class="selected-list" id="selectedListCollege">
            <p class="no-selection">No students selected</p>
          </div>
        </div>
      </div>
      
      <!-- Confirmation Status Indicator -->
      <div class="graduation-confirmation-status" id="graduationConfirmationStatus">
        <i class="fas fa-info-circle"></i>
        <span id="confirmationStatusText">Graduation selection not yet confirmed.</span>
      </div>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="window.closeEligibleForGraduationModal && window.closeEligibleForGraduationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="window.confirmGraduationSelection && window.confirmGraduationSelection()" id="confirmGraduationBtn">
        Confirm Graduation Selection
      </button>
    </div>
  </div>
</div>

<script>
// Debug: Log that functions are being defined
console.log('🔧 Defining graduation modal functions...');

let graduationStudents = {
  shs: [],
  college: []
};
let selectedGraduationStudents = new Set();
let graduationSearchTimeouts = {
  shs: null,
  college: null
};
let currentGraduationTab = 'shs';
let graduationConfirmed = false;

// Attach event listeners to "Select All" checkboxes
// NOTE: This function is kept for backward compatibility but inline onclick handlers are now primary
function attachSelectAllListeners() {
  console.log('🔧 attachSelectAllListeners called (inline handlers are primary)');
  
  // The inline onclick handlers on the label and checkbox are now the primary handlers
  // This function is kept for any cleanup or additional setup if needed
  // No need to attach additional listeners as inline handlers handle everything
}

// Define modal functions immediately (before DOMContentLoaded)
// Open modal function (called from parent page) - Make it globally accessible
window.openEligibleForGraduationModal = function() {
  const modal = document.getElementById('eligibleForGraduationModal');
  if (!modal) {
    console.error('EligibleForGraduationModal not found in DOM');
    return;
  }
  
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  
  // Set SHS tab as active (without triggering load)
  currentGraduationTab = 'shs';
  document.querySelectorAll('.graduation-tab').forEach(btn => btn.classList.remove('active'));
  const shsTab = document.getElementById('graduationTabShs');
  if (shsTab) shsTab.classList.add('active');
  document.querySelectorAll('.graduation-tab-content').forEach(content => content.classList.remove('active'));
  const shsContent = document.getElementById('graduationContentShs');
  if (shsContent) shsContent.classList.add('active');
  
  // Load initial data for both tabs after a small delay to ensure DOM is ready
  setTimeout(() => {
    loadGraduationDepartments('shs');
    loadGraduationDepartments('college');
    // Load students for both tabs
    loadEligibleStudents('shs');
    loadEligibleStudents('college');
    // Initialize summaries (will be updated after students load)
    updateGraduationSummary('shs');
    updateGraduationSummary('college');
    
    // Attach event listeners to "Select All" checkboxes after DOM is ready
    setTimeout(() => {
      attachSelectAllListeners();
      console.log('Select All listeners attached');
    }, 50);
  }, 100);
  
  // Check if already confirmed
  updateGraduationConfirmationStatus(graduationConfirmed);
};

// Close modal function - Make it globally accessible
window.closeEligibleForGraduationModal = function() {
  const modal = document.getElementById('eligibleForGraduationModal');
  if (!modal) {
    return;
  }
  
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
  
  // Reset form (but keep confirmation status)
  // selectedGraduationStudents.clear(); // Don't clear - keep selections
  // graduationStudents = { shs: [], college: [] }; // Don't clear - keep data
  
  // Reset filters
  const shsDept = document.getElementById('graduationShsDepartment');
  const collegeDept = document.getElementById('graduationCollegeDepartment');
  const shsProg = document.getElementById('graduationShsProgram');
  const collegeProg = document.getElementById('graduationCollegeProgram');
  const shsSearch = document.getElementById('graduationShsSearch');
  const collegeSearch = document.getElementById('graduationCollegeSearch');
  
  if (shsDept) shsDept.innerHTML = '<option value="">All Departments</option>';
  if (collegeDept) collegeDept.innerHTML = '<option value="">All Departments</option>';
  if (shsProg) shsProg.innerHTML = '<option value="">All Programs</option>';
  if (collegeProg) collegeProg.innerHTML = '<option value="">All Programs</option>';
  if (shsSearch) shsSearch.value = '';
  if (collegeSearch) collegeSearch.value = '';
  
  // Reset UI
  const selectAllShs = document.getElementById('selectAllShs');
  const selectAllCollege = document.getElementById('selectAllCollege');
  const selectedCountShs = document.getElementById('selectedCountShs');
  const selectedCountCollege = document.getElementById('selectedCountCollege');
  
  if (selectAllShs) selectAllShs.checked = false;
  if (selectAllCollege) selectAllCollege.checked = false;
  if (selectedCountShs) selectedCountShs.textContent = '0 selected';
  if (selectedCountCollege) selectedCountCollege.textContent = '0 selected';
  
  // Reset to SHS tab
  switchGraduationTab('shs');
};

// Switch between SHS and College tabs - Make it globally accessible
window.switchGraduationTab = function(tab) {
  currentGraduationTab = tab;
  
  // Update tab buttons
  document.querySelectorAll('.graduation-tab').forEach(btn => btn.classList.remove('active'));
  document.getElementById(`graduationTab${tab === 'shs' ? 'Shs' : 'College'}`).classList.add('active');
  
  // Update tab content
  document.querySelectorAll('.graduation-tab-content').forEach(content => content.classList.remove('active'));
  document.getElementById(`graduationContent${tab === 'shs' ? 'Shs' : 'College'}`).classList.add('active');
  
  // If we already have students loaded for this tab, just render them
  // Otherwise, load them
  if (graduationStudents[tab] && graduationStudents[tab].length > 0) {
    renderGraduationStudentList(tab);
    updateGraduationListStats(tab);
  } else {
    loadEligibleStudents(tab);
  }
};

// Debounced search function - Make it globally accessible
window.debounceGraduationSearch = function(tab) {
  clearTimeout(graduationSearchTimeouts[tab]);
  graduationSearchTimeouts[tab] = setTimeout(() => {
    loadEligibleStudents(tab);
  }, 300);
};

// Handle department change - load programs and reload students - Make it globally accessible
window.handleDepartmentChange = async function(tab) {
  const departmentId = document.getElementById(`graduation${tab === 'shs' ? 'Shs' : 'College'}Department`).value;
  const programSelect = document.getElementById(`graduation${tab === 'shs' ? 'Shs' : 'College'}Program`);
  
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
  loadEligibleStudents(tab);
};

// Load eligible students - Make it globally accessible
window.loadEligibleStudents = async function(tab) {
  const sector = tab === 'shs' ? 'Senior High School' : 'College';
  const yearLevel = tab === 'shs' ? '2nd Year' : '4th Year';
  const department = document.getElementById(`graduation${tab === 'shs' ? 'Shs' : 'College'}Department`).value;
  const program = document.getElementById(`graduation${tab === 'shs' ? 'Shs' : 'College'}Program`).value;
  const search = document.getElementById(`graduation${tab === 'shs' ? 'Shs' : 'College'}Search`).value;
  
  const container = document.getElementById(`studentListContainer-${tab}`);
  const listStats = document.getElementById(`listStats${tab === 'shs' ? 'Shs' : 'College'}`);
  
  // Show loading
  container.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><p>Loading eligible students...</p></div>';
  
  try {
    // Build query parameters
    const params = new URLSearchParams();
    params.append('sector', sector);
    params.append('year_level', yearLevel);
    // Note: enrollment_status removed - we filter by account_status='active' in the API
    if (department) params.append('department_id', department);
    if (program) params.append('program_id', program);
    if (search) params.append('search', search);
    
    console.log(`Loading ${sector} students with year_level=${yearLevel}`, params.toString());
    
    const response = await fetch(`../../api/users/graduation_management.php?${params.toString()}`, {
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
      graduationStudents[tab] = data.data?.students || [];
      console.log(`Loaded ${graduationStudents[tab].length} ${sector} students`);
      
      if (graduationStudents[tab].length === 0) {
        console.warn(`No students found for ${sector} - ${yearLevel}`);
      }
      
      renderGraduationStudentList(tab);
      updateGraduationListStats(tab);
    } else {
      throw new Error(data.message || 'Failed to load students');
    }
  } catch (error) {
    console.error('Error loading students:', error);
    container.innerHTML = `
      <div class="no-students">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Error loading students: ${error.message}</p>
        <p style="font-size: 0.85em; color: #6c757d; margin-top: 10px;">
          Searching for: ${sector} - ${yearLevel} students
        </p>
      </div>
    `;
    listStats.textContent = 'Error loading data';
  }
};

// Render the student list for graduation
function renderGraduationStudentList(tab) {
  const containerId = `studentListContainer-${tab}`;
  const container = document.getElementById(containerId);
  const students = graduationStudents[tab];
  const tabContentId = `graduationContent${tab === 'shs' ? 'Shs' : 'College'}`;
  const tabContent = document.getElementById(tabContentId);
  
  console.log(`Rendering ${tab} students:`, {
    containerId,
    containerExists: !!container,
    studentsCount: students.length,
    tabContentId,
    tabContentExists: !!tabContent,
    tabContentHasActive: tabContent?.classList.contains('active')
  });
  
  if (!container) {
    console.error(`Container not found: ${containerId}`);
    return;
  }
  
  // Ensure tab content is visible (in case we're rendering for a hidden tab)
  if (tabContent && !tabContent.classList.contains('active')) {
    console.warn(`Tab content for ${tab} is not active, but rendering anyway`);
  }
  
  if (students.length === 0) {
    container.innerHTML = `
      <div class="no-students">
        <i class="fas fa-graduation-cap"></i>
        <p>No ${tab === 'shs' ? '2nd Year' : '4th Year'} students found matching the current filters.</p>
      </div>
    `;
    return;
  }
  
  const studentItems = students.map(student => {
    const isSelected = selectedGraduationStudents.has(student.user_id);
    const isGraduated = student.account_status === 'graduated';
    const checkboxId = `graduation_${tab}_${student.user_id}`;
    
    return `
      <div class="student-item ${isGraduated ? 'graduated' : ''}">
        <div class="student-checkbox">
          <input type="checkbox" 
                 id="${checkboxId}" 
                 value="${student.user_id}"
                 data-user-id="${student.user_id}"
                 data-tab="${tab}"
                 ${isSelected || isGraduated ? 'checked' : ''}
                 ${isGraduated ? 'disabled' : ''}>
        </div>
        <div class="student-info">
          <div class="student-name">
            ${student.last_name}, ${student.first_name} ${student.middle_name || ''}
            ${isGraduated ? '<span class="graduated-badge">Graduated</span>' : ''}
          </div>
          <div class="student-number">${student.student_id}</div>
          <div class="student-program">${student.program || 'N/A'}</div>
          <div class="student-section">${student.section || 'N/A'} • ${student.year_level}</div>
        </div>
      </div>
    `;
  }).join('');
  
  console.log(`Setting innerHTML for ${containerId}, items length: ${studentItems.length}`);
  
  // Set innerHTML first
  container.innerHTML = studentItems;
  console.log(`Container innerHTML set, container.children.length: ${container.children.length}`);
  
  // Attach event listeners to checkboxes after rendering
  students.forEach(student => {
    const checkboxId = `graduation_${tab}_${student.user_id}`;
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
      // Remove any existing listeners and add new one
      checkbox.onchange = function() {
        if (window.toggleGraduationSelection) {
          window.toggleGraduationSelection(student.user_id, tab);
        }
      };
    }
  });
  
  // Re-attach event listener to "Select All" checkbox after rendering
  attachSelectAllListeners();
  
  // Force a reflow to ensure rendering
  void container.offsetHeight;
  
  // Verify visibility and dimensions
  const computedStyle = window.getComputedStyle(container);
  const rect = container.getBoundingClientRect();
  console.log(`Container computed styles:`, {
    display: computedStyle.display,
    visibility: computedStyle.visibility,
    opacity: computedStyle.opacity,
    height: computedStyle.height,
    maxHeight: computedStyle.maxHeight,
    overflow: computedStyle.overflow,
    width: computedStyle.width,
    boundingRect: {
      top: rect.top,
      left: rect.left,
      width: rect.width,
      height: rect.height
    },
    hasChildren: container.children.length,
    firstChild: container.firstElementChild ? {
      tagName: container.firstElementChild.tagName,
      className: container.firstElementChild.className,
      innerText: container.firstElementChild.innerText?.substring(0, 50)
    } : null
  });
  
  // Check parent visibility
  const parent = container.parentElement;
  if (parent) {
    const parentStyle = window.getComputedStyle(parent);
    console.log(`Parent (${parent.className}) computed styles:`, {
      display: parentStyle.display,
      visibility: parentStyle.visibility
    });
  }
  
  // Check tab content visibility (using closest to find parent tab content)
  const parentTabContent = container.closest('.graduation-tab-content');
  if (parentTabContent) {
    const tabStyle = window.getComputedStyle(parentTabContent);
    console.log(`Tab content computed styles:`, {
      display: tabStyle.display,
      visibility: tabStyle.visibility,
      hasActiveClass: parentTabContent.classList.contains('active')
    });
  }
  
  // Update selected students set with currently graduated students
  students.forEach(student => {
    if (student.account_status === 'graduated') {
      selectedGraduationStudents.add(student.user_id);
    }
  });
  
  updateGraduationSelectedCount();
  updateGraduationSelectAllCheckbox(tab);
  
  // Update summary after a brief delay to ensure DOM is ready
  setTimeout(() => {
    updateGraduationSummary(tab);
  }, 50);
}

// Update list statistics
function updateGraduationListStats(tab) {
  const listStats = document.getElementById(`listStats${tab === 'shs' ? 'Shs' : 'College'}`);
  const total = graduationStudents[tab].length;
  const selected = Array.from(selectedGraduationStudents).filter(id => {
    const student = graduationStudents[tab].find(s => s.user_id == id);
    return student !== undefined;
  }).length;
  
  listStats.textContent = `${total} students found, ${selected} selected`;
}

// Toggle individual student selection - Make it globally accessible
window.toggleGraduationSelection = function(userId, tab) {
  console.log(`toggleGraduationSelection called: userId=${userId}, tab=${tab}`);
  
  // Check if student is already selected
  const wasSelected = selectedGraduationStudents.has(userId);
  
  if (wasSelected) {
    selectedGraduationStudents.delete(userId);
    console.log(`Deselected student ${userId}`);
  } else {
    selectedGraduationStudents.add(userId);
    console.log(`Selected student ${userId}`);
  }
  
  // Update the checkbox state
  const checkbox = document.getElementById(`graduation_${tab}_${userId}`);
  if (checkbox) {
    checkbox.checked = !wasSelected;
  }
  
  updateGraduationSelectedCount();
  updateGraduationSelectAllCheckbox(tab);
  updateGraduationSummary(tab);
};

// Toggle all students selection - Make it globally accessible
window.toggleAllGraduationStudents = function(tab, checkboxState) {
  console.log(`🎯 toggleAllGraduationStudents called for ${tab}`, {checkboxState, checkboxStateType: typeof checkboxState});
  
  const selectAllCheckbox = document.getElementById(`selectAll${tab === 'shs' ? 'Shs' : 'College'}`);
  const students = graduationStudents[tab] || [];
  
  if (!selectAllCheckbox) {
    console.error(`❌ Select All checkbox not found for ${tab}`);
    return;
  }
  
  // Read the current checkbox state from DOM (most reliable)
  const currentChecked = selectAllCheckbox.checked;
  console.log(`📋 Current checkbox state from DOM: ${currentChecked}`);
  
  // Use the provided checkboxState if it's explicitly a boolean, otherwise use DOM state
  const isChecked = (typeof checkboxState === 'boolean') ? checkboxState : currentChecked;
  console.log(`📋 Final isChecked: ${isChecked}, students count: ${students.length}`);
  
  if (isChecked) {
    // Select all visible students (excluding already graduated ones)
    students.forEach(student => {
      if (student.account_status !== 'graduated') {
        selectedGraduationStudents.add(student.user_id);
      }
    });
    console.log(`✅ Selected all ${students.length} students for ${tab}`);
  } else {
    // Deselect all students in this tab (but keep graduated ones selected)
    students.forEach(student => {
      if (student.account_status !== 'graduated') {
        selectedGraduationStudents.delete(student.user_id);
      }
    });
    console.log(`❌ Deselected all students for ${tab}`);
  }
  
  // Update checkboxes immediately
  let updatedCount = 0;
  students.forEach(student => {
    const checkbox = document.getElementById(`graduation_${tab}_${student.user_id}`);
    if (checkbox) {
      // Don't uncheck graduated students
      if (student.account_status === 'graduated') {
        checkbox.checked = true;
      } else {
        checkbox.checked = isChecked;
        updatedCount++;
      }
    }
  });
  
  updateGraduationSelectedCount();
  updateGraduationSelectAllCheckbox(tab);
  updateGraduationSummary(tab);
  
  console.log(`📊 After toggle: ${selectedGraduationStudents.size} total selected`);
  console.log(`✅ Updated ${updatedCount} checkboxes for ${tab}`);
};

// Update select all checkbox state
function updateGraduationSelectAllCheckbox(tab) {
  const selectAllCheckbox = document.getElementById(`selectAll${tab === 'shs' ? 'Shs' : 'College'}`);
  const students = graduationStudents[tab];
  const visibleStudentIds = students.map(s => s.user_id);
  const selectedVisibleCount = visibleStudentIds.filter(id => selectedGraduationStudents.has(id)).length;
  
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
function updateGraduationSelectedCount() {
  const totalSelected = selectedGraduationStudents.size;
  const shsSelected = Array.from(selectedGraduationStudents).filter(id => {
    return graduationStudents.shs.find(s => s.user_id == id) !== undefined;
  }).length;
  const collegeSelected = Array.from(selectedGraduationStudents).filter(id => {
    return graduationStudents.college.find(s => s.user_id == id) !== undefined;
  }).length;
  
  document.getElementById('selectedCountShs').textContent = `${shsSelected} selected`;
  document.getElementById('selectedCountCollege').textContent = `${collegeSelected} selected`;
}

// Update selected students summary for a specific tab
function updateGraduationSummary(tab) {
  const summaryList = document.getElementById(`selectedList${tab === 'shs' ? 'Shs' : 'College'}`);
  const summaryCount = document.getElementById(`summaryCount${tab === 'shs' ? 'Shs' : 'College'}`);
  const summaryContainer = document.getElementById(`selectedGraduationSummary-${tab}`);
  
  console.log(`updateGraduationSummary called for ${tab}:`, {
    summaryList: !!summaryList,
    summaryCount: !!summaryCount,
    summaryContainer: !!summaryContainer
  });
  
  if (!summaryList || !summaryCount || !summaryContainer) {
    console.error(`Missing elements for ${tab} summary:`, {
      summaryList: !summaryList,
      summaryCount: !summaryCount,
      summaryContainer: !summaryContainer
    });
    return;
  }
  
  // Get selected students for this specific tab only
  const students = graduationStudents[tab] || [];
  const selectedForTab = students.filter(s => selectedGraduationStudents.has(s.user_id));
  
  console.log(`Selected students for ${tab}:`, selectedForTab.length);
  
  // Update count
  summaryCount.textContent = `${selectedForTab.length} selected`;
  
  // Show/hide summary container based on whether there are selections
  if (selectedForTab.length === 0) {
    summaryContainer.style.display = 'none';
    summaryList.innerHTML = '<p class="no-selection">No students selected</p>';
    console.log(`Summary hidden for ${tab} (no selections)`);
    return;
  }
  
  // Show summary container - use important to override any inline styles
  summaryContainer.style.display = 'block';
  summaryContainer.style.setProperty('display', 'block', 'important');
  console.log(`Summary shown for ${tab} with ${selectedForTab.length} students`);
  
  // Build summary items
  const summaryItems = selectedForTab.map(student => {
    const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
    const studentNumber = student.student_id || 'N/A';
    const program = student.program || 'N/A';
    const yearLevel = student.year_level || 'N/A';
    
    return `
      <div class="summary-item">
        <div class="summary-student-info">
          <div class="summary-student-name">${fullName}</div>
          <div class="summary-student-details">
            <span class="summary-detail-item"><i class="fas fa-id-card"></i> ${studentNumber}</span>
            <span class="summary-detail-item"><i class="fas fa-graduation-cap"></i> ${program}</span>
            <span class="summary-detail-item"><i class="fas fa-calendar-alt"></i> ${yearLevel}</span>
          </div>
        </div>
      </div>
    `;
  }).join('');
  
  summaryList.innerHTML = summaryItems;
}

// Confirm graduation selection - Make it globally accessible
window.confirmGraduationSelection = async function() {
  const confirmBtn = document.getElementById('confirmGraduationBtn');
  const originalText = confirmBtn.textContent;
  
  // Disable button and show loading
  confirmBtn.disabled = true;
  confirmBtn.textContent = 'Confirming...';
  
  try {
    // Even if 0 students selected, we still confirm (as per user requirement)
    const studentIds = Array.from(selectedGraduationStudents);
    
    const response = await fetch('../../api/users/graduation_management.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        student_ids: studentIds,
        action: 'graduate'
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      const updatedCount = data.data.updated_count || 0;
      graduationConfirmed = true;
      
      // Update confirmation status
      updateGraduationConfirmationStatus(true);
      
      if (updatedCount > 0) {
        showToast(`Successfully marked ${updatedCount} student(s) as graduated.`, 'success');
      } else {
        showToast('Graduation selection confirmed (0 students selected).', 'success');
      }
      
      // Reload both tabs to show updated graduation status
      await loadEligibleStudents('shs');
      await loadEligibleStudents('college');
      
      // Notify parent page that graduation is confirmed
      document.dispatchEvent(new CustomEvent('graduation-confirmed', { 
        detail: { 
          confirmed: true,
          updated_count: updatedCount 
        } 
      }));
      
    } else {
      throw new Error(data.message || 'Failed to confirm graduation selection');
    }
  } catch (error) {
    console.error('Error confirming graduation:', error);
      showToast(`Error confirming graduation: ${error.message}`, 'error');
    } finally {
      // Re-enable button
      confirmBtn.disabled = false;
      confirmBtn.textContent = originalText;
    }
  };

// Update graduation confirmation status display
function updateGraduationConfirmationStatus(confirmed) {
  const statusDiv = document.getElementById('graduationConfirmationStatus');
  const statusText = document.getElementById('confirmationStatusText');
  
  if (confirmed) {
    statusDiv.className = 'graduation-confirmation-status confirmed';
    statusText.textContent = 'Graduation selection confirmed. You can now create a new school year.';
  } else {
    statusDiv.className = 'graduation-confirmation-status';
    statusText.textContent = 'Graduation selection not yet confirmed.';
  }
}

// Load departments based on selected sector
async function loadGraduationDepartments(tab) {
  const sector = tab === 'shs' ? 'Senior High School' : 'College';
  const departmentSelect = document.getElementById(`graduation${tab === 'shs' ? 'Shs' : 'College'}Department`);
  
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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Set up event listeners for department changes (handled by handleDepartmentChange)
  // Set up event listeners for program changes
  const shsProg = document.getElementById('graduationShsProgram');
  const collegeProg = document.getElementById('graduationCollegeProgram');
  if (shsProg) {
    shsProg.addEventListener('change', () => loadEligibleStudents('shs'));
  }
  if (collegeProg) {
    collegeProg.addEventListener('change', () => loadEligibleStudents('college'));
  }
  
  // Log all functions after they're all defined
  console.log('✅ Graduation modal functions defined:', {
    openEligibleForGraduationModal: typeof window.openEligibleForGraduationModal,
    closeEligibleForGraduationModal: typeof window.closeEligibleForGraduationModal,
    switchGraduationTab: typeof window.switchGraduationTab,
    debounceGraduationSearch: typeof window.debounceGraduationSearch,
    handleDepartmentChange: typeof window.handleDepartmentChange,
    loadEligibleStudents: typeof window.loadEligibleStudents,
    toggleGraduationSelection: typeof window.toggleGraduationSelection,
    toggleAllGraduationStudents: typeof window.toggleAllGraduationStudents,
    confirmGraduationSelection: typeof window.confirmGraduationSelection
  });
});
</script>
