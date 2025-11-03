<?php
// Import Modal - Dynamic Import for Students and Faculty
// This modal adapts based on the page type (college/shs/faculty)
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay import-modal-overlay" id="importModal">
  <div class="modal-window" style="max-width: 700px;">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeImportModal()">&times;</button>
    
    <!-- Modal Header -->
    <div class="modal-header">
      <h2 class="modal-title" id="importModalTitle">📥 Import Data</h2>
      <div class="modal-supporting-text" id="importModalDescription">Upload a file to import data. Supported formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)</div>
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="importForm" class="modal-form" data-endpoint="../../controllers/importData.php" enctype="multipart/form-data">
        <!-- Hidden Fields -->
        <input type="hidden" name="type" id="importType" value="">
        <input type="hidden" name="pageType" id="pageType" value="">
        <input type="hidden" name="selectedDepartment" id="selectedDepartmentId" value="">
        <input type="hidden" name="selectedProgram" id="selectedProgramId" value="">
        
        <!-- Selection Requirements Section -->
        <div class="import-section">
          <h3 class="section-title">📋 Selection Requirements</h3>
          <p class="selection-note">Before uploading a file, please select the appropriate scope:</p>
          
          <!-- Department Selection -->
          <div class="form-group">
            <label for="importDepartmentSelect">Department <span class="required-asterisk">*</span></label>
            <select id="importDepartmentSelect" class="form-control" required>
              <option value="">Loading Departments...</option>
            </select>
            <div id="importDepartmentNote" class="selection-info-note" style="display: none;"></div>
          </div>
          
          <!-- Course/Program Selection (Students Only) -->
          <div class="form-group" id="importProgramGroup" style="display: none;">
            <label for="importProgramSelect">Course/Program <span class="required-asterisk">*</span></label>
            <select id="importProgramSelect" class="form-control" disabled>
              <option value="">Select Department first</option>
            </select>
          </div>
          
          <div class="selection-note" id="uploadEnabledNote" style="display: none;">
            <i class="fas fa-info-circle"></i> File upload will be enabled once all selections are made.
          </div>
        </div>
        
        <!-- File Upload Section -->
        <div class="import-section">
          <h3 class="section-title">📁 File Upload</h3>
          
          <!-- Disabled State Overlay -->
          <div class="file-upload-disabled-overlay" id="fileUploadDisabledOverlay" style="display: none;">
            <div class="disabled-message">
              <i class="fas fa-exclamation-triangle"></i>
              <p id="disabledMessageText">Please select Department and Course/Program first</p>
            </div>
          </div>
          
          <!-- Drag & Drop Area -->
          <div class="file-upload-area" id="fileUploadArea">
            <div class="upload-content">
              <i class="fas fa-cloud-upload-alt upload-icon"></i>
              <h4>Drag & Drop your file here</h4>
              <p>or</p>
              <button type="button" class="btn btn-secondary" id="browseFilesBtn" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-folder-open"></i> Browse Files
              </button>
              <p class="file-info">Supported: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)</p>
            </div>
            <input type="file" id="fileInput" name="importFile" accept=".xlsx,.xls,.csv,.json,.xml" style="display: none;" onchange="handleFileSelect(this)">
          </div>
          
          <!-- Selected File Info -->
          <div class="selected-file" id="selectedFile" style="display: none;">
            <div class="file-details">
              <i class="fas fa-file-alt"></i>
              <span id="fileName"></span>
              <span id="fileSize"></span>
            </div>
            <button type="button" class="btn-icon" onclick="removeSelectedFile()" title="Remove file">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <!-- Import Options Section -->
        <div class="import-section">
          <h3 class="section-title">⚙️ Import Options</h3>
          <div class="radio-group">
            <label class="radio-option">
              <input type="radio" name="importMode" value="skip" checked>
              <span class="radio-custom"></span>
              <span class="radio-label" id="skipLabel">Skip existing records</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="importMode" value="update">
              <span class="radio-custom"></span>
              <span class="radio-label" id="updateLabel">Update existing records</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="importMode" value="replace">
              <span class="radio-custom"></span>
              <span class="radio-label">Replace all data</span>
            </label>
          </div>
          
          <div class="checkbox-group">
            <label class="checkbox-option">
              <input type="checkbox" name="validateData" checked>
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Validate data before import</span>
            </label>
          </div>
        </div>
        
        <!-- Preview Section -->
        <div class="import-section">
          <h3 class="section-title">📊 Data Preview</h3>
          <div class="preview-container" id="previewContainer" style="display: none;">
            <div class="preview-header">
              <span>Showing first 5 rows of data</span>
              <button type="button" class="btn-icon" onclick="refreshPreview()" title="Refresh preview">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="preview-table-container">
              <table class="preview-table" id="previewTable">
                <thead id="previewTableHead">
                  <!-- Table headers will be populated dynamically -->
                </thead>
                <tbody id="previewTableBody">
                  <!-- Preview data will be populated here -->
                </tbody>
              </table>
            </div>
            <div class="preview-stats">
              <span id="totalRows">Total Rows: 0</span>
              <span id="validRows">Valid Rows: 0</span>
              <span id="errorRows">Errors: 0</span>
            </div>
          </div>
          <div class="no-preview" id="noPreview">
            <i class="fas fa-table"></i>
            <p>Upload a file to see data preview</p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeImportModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitImportForm()" id="importSubmitBtn" disabled>Import Data</button>
    </div>
  </div>
</div>

<script>
// CRITICAL: This log must appear if script is executing
console.log('[ImportModal] ==========================================');
console.log('[ImportModal] SCRIPT START - ImportModal.php loaded');
console.log('[ImportModal] ==========================================');

// Import Modal Global Variables
let currentPageType = ''; // 'college', 'shs', 'faculty'
let currentImportType = ''; // 'student_import', 'faculty_import'
let userRole = ''; // 'Admin', 'Program Head'

// Ensure functions are defined immediately when script loads (before DOMContentLoaded)
// This prevents "function not found" errors if called before DOM is ready
console.log('[ImportModal] Script loading - defining functions...');

// Load departments from API - Define EARLY to ensure it's available
// Using unique name to avoid collision with page-level loadDepartments() functions
async function loadImportModalDepartments() {
  console.log('[ImportModal] >>> loadImportModalDepartments() STARTED');
  console.log('[ImportModal] Function execution began - this log should appear');
  
  // Use the global variables or closure variables
  const pageType = window.currentImportPageType || currentPageType;
  console.log('[ImportModal] Function context - currentPageType (from window):', window.currentImportPageType);
  console.log('[ImportModal] Function context - currentPageType (from closure):', currentPageType);
  console.log('[ImportModal] Using pageType:', pageType);
  
  const departmentSelect = document.getElementById('importDepartmentSelect');
  if (!departmentSelect) {
    console.error('[ImportModal] Department select element not found!');
    return Promise.reject(new Error('Department select element not found'));
  }
  console.log('[ImportModal] Department select element found');
  
  departmentSelect.innerHTML = '<option value="">Loading Departments...</option>';
  departmentSelect.disabled = true;
  console.log('[ImportModal] Dropdown set to loading state');
  
  console.log('[ImportModal] Loading departments for pageType:', pageType);
  console.log('[ImportModal] Type check - pageType:', typeof pageType, pageType);
  
  if (!pageType || pageType === '') {
    console.error('[ImportModal] ERROR: pageType is empty or undefined!', pageType);
    departmentSelect.innerHTML = '<option value="">Error: Page type not set</option>';
    showToastNotification('Error: Page type not configured. Please refresh and try again.', 'error');
    return Promise.reject(new Error('Page type not set'));
  }
  
  try {
    const apiUrl = `../../api/import/options.php?resource=departments&pageType=${pageType}`;
    console.log('[ImportModal] Fetching from:', apiUrl);
    console.log('[ImportModal] Full URL would be:', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/') + apiUrl);
    
    const response = await fetch(apiUrl, {
      credentials: 'include',
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });
    
    console.log('[ImportModal] Response status:', response.status, response.statusText);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('[ImportModal] Response error text:', errorText);
      throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
    }
    
    const contentType = response.headers.get('content-type');
    console.log('[ImportModal] Response content-type:', contentType);
    
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text();
      console.error('[ImportModal] Non-JSON response:', text);
      throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
    }
    
    const data = await response.json();
    console.log('[ImportModal] API response:', data);
    
    if (data.success && data.departments && data.departments.length > 0) {
      departmentSelect.innerHTML = '<option value="">Select Department</option>';
      
      data.departments.forEach(dept => {
        const option = document.createElement('option');
        option.value = dept.department_id;
        option.textContent = dept.department_name;
        departmentSelect.appendChild(option);
      });
      
      // Program Head: Auto-select if single department
      const finalUserRole = window.currentImportUserRole || userRole;
      if (finalUserRole === 'Program Head' && data.departments.length === 1) {
        const singleDept = data.departments[0];
        departmentSelect.value = singleDept.department_id;
        departmentSelect.disabled = true;
        
        // Show note
        const noteEl = document.getElementById('importDepartmentNote');
        noteEl.textContent = 'ℹ️ Auto-selected: Your assigned department';
        noteEl.style.display = 'block';
        
        // Set hidden field and trigger program load
        document.getElementById('selectedDepartmentId').value = singleDept.department_id;
        const finalImportType = window.currentImportType || currentImportType;
        if (finalImportType === 'student_import') {
          console.log('[ImportModal] Auto-selected department - loading programs:', singleDept.department_id);
          if (window.loadImportModalPrograms) {
            window.loadImportModalPrograms(singleDept.department_id);
          }
        } else {
          enableFileUpload();
        }
      }
      
      departmentSelect.disabled = false;
    } else {
      departmentSelect.innerHTML = '<option value="">No departments available</option>';
      console.warn('[ImportModal] No departments found:', data.message);
      showToastNotification(data.message || 'No departments found', 'warning');
    }
  } catch (error) {
    console.error('[ImportModal] Failed to load departments:', error);
    console.error('[ImportModal] Error stack:', error.stack);
    departmentSelect.innerHTML = '<option value="">Error loading departments</option>';
    showToastNotification('An error occurred while loading departments: ' + error.message, 'error');
  }
}

// Make it globally accessible with unique name
console.log('[ImportModal] Assigning loadImportModalDepartments to window...');
window.loadImportModalDepartments = loadImportModalDepartments;
// Keep legacy alias for compatibility
window.loadImportDepartments = loadImportModalDepartments;

// Initialize modal with page context - Define immediately
console.log('[ImportModal] Defining window.initializeImportModal...');
window.initializeImportModal = function(pageType, importType, role = 'Admin') {
  console.log('[ImportModal] initializeImportModal called');
  console.log('[ImportModal] initializeImportModal called with:', { pageType, importType, role });
  
  currentPageType = pageType;
  currentImportType = importType;
  userRole = role;
  
  // Also set as window properties for debugging and global access
  window.currentImportPageType = pageType;
  window.currentImportType = importType;
  window.currentImportUserRole = role;
  
  // Set hidden form fields
  const pageTypeInput = document.getElementById('pageType');
  const importTypeInput = document.getElementById('importType');
  
  if (pageTypeInput) {
    pageTypeInput.value = pageType;
  } else {
    console.error('[ImportModal] pageType input not found');
  }
  
  if (importTypeInput) {
    importTypeInput.value = importType;
  } else {
    console.error('[ImportModal] importType input not found');
  }
  
  // Update modal title and description
  updateModalTitle();
  
  // Load departments immediately - no need for setTimeout delay
  // The modal is already visible and DOM is ready
  // Use unique function name to avoid collision with page-level functions
  console.log('[ImportModal] About to call loadImportModalDepartments immediately...');
  console.log('[ImportModal] Checking window.loadImportModalDepartments:', typeof window.loadImportModalDepartments);
  
  // Use the unique function name from window scope
  const loadFunc = window.loadImportModalDepartments || window.loadImportDepartments;
  
  if (!loadFunc || typeof loadFunc !== 'function') {
    console.error('[ImportModal] loadImportModalDepartments function not available!');
    console.error('[ImportModal] Available:', {
      loadImportModalDepartments: typeof window.loadImportModalDepartments,
      loadImportDepartments: typeof window.loadImportDepartments
    });
    showToastNotification('Error: loadImportModalDepartments function not available', 'error');
    return;
  }
  
  console.log('[ImportModal] Calling loadImportModalDepartments function...');
  
  // Call the function and safely handle the result
  try {
    console.log('[ImportModal] About to invoke loadFunc()...');
    const result = loadFunc();
    console.log('[ImportModal] loadFunc() returned:', result);
    console.log('[ImportModal] Result type:', typeof result);
    console.log('[ImportModal] Is result a Promise?', result instanceof Promise);
    console.log('[ImportModal] Has result.then?', result && typeof result.then === 'function');
    
    // Check if result is a Promise before calling .catch()
    if (result && typeof result === 'object' && typeof result.then === 'function') {
      console.log('[ImportModal] Result is a Promise, attaching handlers...');
      result.then(() => {
        console.log('[ImportModal] ✅ loadImportModalDepartments completed successfully');
      }).catch(err => {
        console.error('[ImportModal] ❌ Error in loadImportModalDepartments():', err);
        console.error('[ImportModal] Error stack:', err.stack);
        showToastNotification('Error loading departments: ' + (err.message || 'Unknown error'), 'error');
      });
    } else {
      console.warn('[ImportModal] ⚠️ loadImportModalDepartments() did not return a Promise!');
      console.warn('[ImportModal] Returned value:', result);
      console.warn('[ImportModal] This should not happen with async functions. Checking function definition...');
      
      // Check if function is actually async
      const funcString = loadFunc.toString();
      const isAsync = funcString.trim().startsWith('async');
      console.warn('[ImportModal] Function is async?', isAsync);
      console.warn('[ImportModal] Function signature:', funcString.substring(0, 100));
    }
  } catch (error) {
    // Synchronous error during function call
    console.error('[ImportModal] ❌ SYNCHRONOUS ERROR calling loadImportModalDepartments():', error);
    console.error('[ImportModal] Error message:', error.message);
    console.error('[ImportModal] Error stack:', error.stack);
    showToastNotification('Error loading departments: ' + (error.message || 'Unknown error'), 'error');
  }
}

function updateModalTitle() {
  const titleEl = document.getElementById('importModalTitle');
  const descEl = document.getElementById('importModalDescription');
  
  if (currentImportType === 'faculty_import') {
    titleEl.textContent = '📥 Import Faculty Data';
    descEl.textContent = 'Upload a file to import faculty data. Supported formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)';
  } else if (currentPageType === 'shs') {
    titleEl.textContent = '📥 Import SHS Data';
    descEl.textContent = 'Upload a file to import senior high school student data. Supported formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)';
  } else {
    titleEl.textContent = '📥 Import College Data';
    descEl.textContent = 'Upload a file to import college student data. Supported formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)';
  }
  
  // Update radio labels
  if (currentImportType === 'student_import') {
    document.getElementById('skipLabel').textContent = 'Skip existing students';
    document.getElementById('updateLabel').textContent = 'Update existing students';
  } else {
    document.getElementById('skipLabel').textContent = 'Skip existing faculty';
    document.getElementById('updateLabel').textContent = 'Update existing faculty';
  }
  
  // Show/hide program group
  const programGroup = document.getElementById('importProgramGroup');
  if (currentImportType === 'student_import') {
    programGroup.style.display = 'block';
  } else {
    programGroup.style.display = 'none';
  }
}

// Load programs when department is selected - Make globally accessible
window.loadImportModalPrograms = async function(departmentId) {
  console.log('[ImportModal] >>> loadImportModalPrograms() STARTED');
  console.log('[ImportModal] departmentId:', departmentId);
  
  if (!departmentId) {
    console.log('[ImportModal] No departmentId provided, resetting program select');
    const programSelect = document.getElementById('importProgramSelect');
    programSelect.innerHTML = '<option value="">Select Department first</option>';
    programSelect.disabled = true;
    document.getElementById('selectedProgramId').value = '';
    checkSelectionsComplete();
    return;
  }
  
  const programSelect = document.getElementById('importProgramSelect');
  if (!programSelect) {
    console.error('[ImportModal] Program select element not found!');
    return;
  }
  
  console.log('[ImportModal] Program select element found');
  programSelect.innerHTML = '<option value="">Loading Programs...</option>';
  programSelect.disabled = true;
  
  try {
    const apiUrl = `../../api/import/options.php?resource=programs&department_id=${departmentId}`;
    console.log('[ImportModal] Fetching programs from:', apiUrl);
    
    const response = await fetch(apiUrl, {
      credentials: 'include',
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });
    
    console.log('[ImportModal] Programs response status:', response.status, response.statusText);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('[ImportModal] Programs response error text:', errorText);
      throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
    }
    
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text();
      console.error('[ImportModal] Non-JSON response for programs:', text);
      throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
    }
    
    const data = await response.json();
    console.log('[ImportModal] Programs API response:', data);
    
    if (data.success && data.programs && data.programs.length > 0) {
      programSelect.innerHTML = '<option value="">Select Course/Program</option>';
      
      data.programs.forEach(program => {
        const option = document.createElement('option');
        option.value = program.program_id;
        option.textContent = program.program_name;
        programSelect.appendChild(option);
      });
      
      programSelect.disabled = false;
      console.log('[ImportModal] ✅ Loaded', data.programs.length, 'programs');
    } else {
      programSelect.innerHTML = '<option value="">No programs available</option>';
      console.warn('[ImportModal] No programs found:', data.message);
      showToastNotification(data.message || 'No programs found for this department', 'warning');
    }
  } catch (error) {
    console.error('[ImportModal] Failed to load programs:', error);
    console.error('[ImportModal] Error stack:', error.stack);
    programSelect.innerHTML = '<option value="">Error loading programs</option>';
    showToastNotification('An error occurred while loading programs: ' + error.message, 'error');
  }
};

// Keep legacy alias for compatibility
const loadPrograms = window.loadImportModalPrograms;

// Check if all required selections are made
function checkSelectionsComplete() {
  const departmentId = document.getElementById('importDepartmentSelect').value;
  const programId = currentImportType === 'student_import' 
    ? document.getElementById('importProgramSelect').value 
    : true; // Faculty doesn't need program
  
  if (departmentId && programId) {
    enableFileUpload();
  } else {
    disableFileUpload();
  }
}

function enableFileUpload() {
  const fileUploadArea = document.getElementById('fileUploadArea');
  const fileUploadDisabledOverlay = document.getElementById('fileUploadDisabledOverlay');
  const browseBtn = document.getElementById('browseFilesBtn');
  const fileInput = document.getElementById('fileInput');
  const uploadNote = document.getElementById('uploadEnabledNote');
  
  fileUploadArea.style.pointerEvents = 'auto';
  fileUploadArea.style.opacity = '1';
  fileUploadDisabledOverlay.style.display = 'none';
  browseBtn.disabled = false;
  fileInput.disabled = false;
  uploadNote.style.display = 'none';
}

function disableFileUpload() {
  const fileUploadArea = document.getElementById('fileUploadArea');
  const fileUploadDisabledOverlay = document.getElementById('fileUploadDisabledOverlay');
  const browseBtn = document.getElementById('browseFilesBtn');
  const fileInput = document.getElementById('fileInput');
  const disabledMessageText = document.getElementById('disabledMessageText');
  
  fileUploadArea.style.pointerEvents = 'none';
  fileUploadArea.style.opacity = '0.5';
  fileUploadDisabledOverlay.style.display = 'flex';
  
  if (currentImportType === 'student_import') {
    disabledMessageText.textContent = 'Please select Department and Course/Program first';
  } else {
    disabledMessageText.textContent = 'Please select Department first';
  }
  
  browseBtn.disabled = true;
  fileInput.disabled = true;
}

// File handling
function handleFileSelect(input) {
  const file = input.files[0];
  if (file) {
    displaySelectedFile(file);
    validateAndPreviewFile(file);
  }
}

function displaySelectedFile(file) {
  const fileName = document.getElementById('fileName');
  const fileSize = document.getElementById('fileSize');
  const selectedFile = document.getElementById('selectedFile');
  const fileUploadArea = document.getElementById('fileUploadArea');
  
  fileName.textContent = file.name;
  fileSize.textContent = `(${formatFileSize(file.size)})`;
  
  selectedFile.style.display = 'flex';
  fileUploadArea.style.display = 'none';
  
  // Enable import button
  document.getElementById('importSubmitBtn').disabled = false;
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function removeSelectedFile() {
  const selectedFile = document.getElementById('selectedFile');
  const fileUploadArea = document.getElementById('fileUploadArea');
  const fileInput = document.getElementById('fileInput');
  const previewContainer = document.getElementById('previewContainer');
  const noPreview = document.getElementById('noPreview');
  
  selectedFile.style.display = 'none';
  fileUploadArea.style.display = 'block';
  fileInput.value = '';
  
  // Hide preview
  previewContainer.style.display = 'none';
  noPreview.style.display = 'block';
  
  // Disable import button
  document.getElementById('importSubmitBtn').disabled = true;
}

function validateAndPreviewFile(file) {
  const allowedTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
    'application/vnd.ms-excel', // .xls
    'text/csv', // .csv
    'application/json', // .json
    'application/xml', // .xml
    'text/xml' // .xml
  ];
  
  const allowedExtensions = ['.xlsx', '.xls', '.csv', '.json', '.xml'];
  const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
  
  if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
    showNotification('Invalid file type. Please upload Excel, CSV, JSON, or XML files.', 'error');
    removeSelectedFile();
    return;
  }
  
  if (file.size > 10 * 1024 * 1024) { // 10MB limit
    showNotification('File size too large. Please upload files smaller than 10MB.', 'error');
    removeSelectedFile();
    return;
  }
  
  // Simulate file preview
  generatePreviewData();
}

function generatePreviewData() {
  const previewContainer = document.getElementById('previewContainer');
  const noPreview = document.getElementById('noPreview');
  const previewTableBody = document.getElementById('previewTableBody');
  
  // Sample preview data
  const sampleData = [
    {
      studentNumber: '02000288322',
      name: 'Zinzu Chan Lee',
      program: 'BS Information Technology',
      yearLevel: '3rd Year',
      section: 'A',
      status: 'Valid'
    },
    {
      studentNumber: '02000288323',
      name: 'Maria Santos Garcia',
      program: 'BS Business Administration',
      yearLevel: '2nd Year',
      section: 'B',
      status: 'Valid'
    },
    {
      studentNumber: '02000288324',
      name: 'Juan Carlos Santos',
      program: 'BS Tourism Management',
      yearLevel: '4th Year',
      section: 'C',
      status: 'Valid'
    },
    {
      studentNumber: '02000288325',
      name: 'Ana Sofia Martinez',
      program: 'STEM',
      yearLevel: 'Grade 12',
      section: 'A',
      status: 'Valid'
    },
    {
      studentNumber: '02000288326',
      name: 'Carlos Miguel Reyes',
      program: 'BS Computer Science',
      yearLevel: '1st Year',
      section: 'D',
      status: 'Valid'
    }
  ];
  
  // Populate preview table
  previewTableBody.innerHTML = '';
  sampleData.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.studentNumber}</td>
      <td>${row.name}</td>
      <td>${row.program}</td>
      <td>${row.yearLevel}</td>
      <td>${row.section}</td>
      <td><span class="status-badge account-active">${row.status}</span></td>
    `;
    previewTableBody.appendChild(tr);
  });
  
  // Update stats
  document.getElementById('totalRows').textContent = 'Total Rows: 5';
  document.getElementById('validRows').textContent = 'Valid Rows: 5';
  document.getElementById('errorRows').textContent = 'Errors: 0';
  
  // Show preview
  previewContainer.style.display = 'block';
  noPreview.style.display = 'none';
}

function refreshPreview() {
  // Simulate refreshing preview data
  generatePreviewData();
  showToastNotification('Preview refreshed', 'success');
}

// Form validation
function validateImportForm() {
  const departmentId = document.getElementById('selectedDepartmentId').value;
  const programId = document.getElementById('selectedProgramId').value;
  const fileInput = document.getElementById('fileInput');
  const importMode = document.querySelector('input[name="importMode"]:checked').value;
  
  if (!departmentId) {
    showToastNotification('Please select a department', 'error');
    return false;
  }
  
  if (currentImportType === 'student_import' && !programId) {
    showToastNotification('Please select a course/program', 'error');
      return false;
    }
  
  if (!fileInput.files[0]) {
    showToastNotification('Please select a file to import', 'error');
    return false;
  }
  
  if (importMode === 'replace') {
    if (!confirm('This will replace all existing data. Are you sure you want to continue?')) {
      return false;
    }
  }
  
  return true;
}

// Modal functions - Define immediately (not in DOMContentLoaded)
// This ensures the function is available as soon as the script loads
console.log('[ImportModal] Defining window.openImportModal...');
window.openImportModal = function(pageType = null, importType = null, role = 'Admin') {
  console.log('[ImportModal] ===== openImportModal called =====');
  console.log('[ImportModal] Parameters:', { pageType, importType, role });
  console.log('[ImportModal] Current state:', { currentPageType, currentImportType, userRole });
  
  // Show modal first
  const modal = document.getElementById('importModal');
  if (!modal) {
    console.error('[ImportModal] Modal element not found!');
    return;
  }
  
  console.log('[ImportModal] Showing modal...');
  modal.style.display = 'flex';
  document.body.classList.add('modal-open');
  
  // Always initialize with provided or default parameters
  let finalPageType, finalImportType, finalRole;
  
  if (pageType && importType) {
    console.log('[ImportModal] Using provided parameters');
    finalPageType = pageType;
    finalImportType = importType;
    finalRole = role;
  } else if (currentPageType && currentImportType) {
    // Use existing context if already initialized
    console.log('[ImportModal] Using existing context');
    finalPageType = currentPageType;
    finalImportType = currentImportType;
    finalRole = userRole || role;
  } else {
    // Default to college student import if not specified
    console.warn('[ImportModal] No context provided. Using defaults.');
    finalPageType = 'college';
    finalImportType = 'student_import';
    finalRole = role;
  }
  
  console.log('[ImportModal] Final initialization values:', { finalPageType, finalImportType, finalRole });
  
  // Initialize immediately - use window.initializeImportModal to ensure it's available
  if (typeof window.initializeImportModal === 'function') {
    window.initializeImportModal(finalPageType, finalImportType, finalRole);
  } else {
    console.error('[ImportModal] initializeImportModal function not found!');
    showToastNotification('Error: Import modal initialization failed', 'error');
  }
  
  // Reset form
  const form = document.getElementById('importForm');
  if (form) {
    form.reset();
  }
  removeSelectedFile();
  
  // Reset to defaults
  const skipRadio = document.querySelector('input[name="importMode"][value="skip"]');
  const validateCheckbox = document.querySelector('input[name="validateData"]');
  if (skipRadio) skipRadio.checked = true;
  if (validateCheckbox) validateCheckbox.checked = true;
  
  // Reset selections
  const selectedDept = document.getElementById('selectedDepartmentId');
  const selectedProg = document.getElementById('selectedProgramId');
  if (selectedDept) selectedDept.value = '';
  if (selectedProg) selectedProg.value = '';
  
  // Reset dropdowns
  const deptSelect = document.getElementById('importDepartmentSelect');
  if (deptSelect) {
    deptSelect.value = '';
  }
  const progSelect = document.getElementById('importProgramSelect');
  if (progSelect) {
    progSelect.value = '';
    progSelect.disabled = true;
    progSelect.innerHTML = '<option value="">Select Department first</option>';
  }
  
  disableFileUpload();
};

// Define other modal functions to ensure they're available
console.log('[ImportModal] Defining window.closeImportModal...');
window.closeImportModal = function() {
  const modal = document.getElementById('importModal');
  if (!modal) return;
  
  modal.style.display = 'none';
  document.body.classList.remove('modal-open');
  
  // Reset form
  const form = document.getElementById('importForm');
  if (form) {
    form.reset();
  }
  removeSelectedFile();
}

// Ensure functions are available even if DOMContentLoaded hasn't fired
console.log('[ImportModal] Defining window.submitImportForm...');
window.submitImportForm = function() {
  if (!validateImportForm()) {
    return;
  }
  
  const submitBtn = document.getElementById('importSubmitBtn');
  const form = document.getElementById('importForm');
  
  // Show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = 'Importing...';
  form.classList.add('modal-loading');
  
  // Create FormData for file upload
  const formData = new FormData(form);
  
  // Submit to backend
  fetch(form.dataset.endpoint, {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToastNotification(data.message || 'Data imported successfully', 'success');
      
      // Trigger page refresh if callback exists
      if (typeof window.onImportSuccess === 'function') {
        window.onImportSuccess(data);
      }
      
    window.closeImportModal();
    } else {
      showToastNotification(data.message || 'Import failed', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Import Data';
    form.classList.remove('modal-loading');
    }
  })
  .catch(error => {
    console.error('Import error:', error);
    showToastNotification('An error occurred during import', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Import Data';
    form.classList.remove('modal-loading');
  });
}

// Drag and drop functionality
function setupDragAndDrop() {
  const uploadArea = document.getElementById('fileUploadArea');
  const fileInput = document.getElementById('fileInput');
  
  uploadArea.addEventListener('click', () => fileInput.click());
  
  uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
  });
  
  uploadArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
  });
  
  uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      fileInput.files = files;
      handleFileSelect(fileInput);
    }
  });
}

// Add event listeners when modal is loaded
document.addEventListener('DOMContentLoaded', function() {
  setupDragAndDrop();
  
  // Department change handler
  const departmentSelect = document.getElementById('importDepartmentSelect');
  if (departmentSelect) {
    departmentSelect.addEventListener('change', function() {
      console.log('[ImportModal] Department dropdown changed, value:', this.value);
      const departmentId = this.value;
      document.getElementById('selectedDepartmentId').value = departmentId;
      
      // Get current import type from window or closure
      const importType = window.currentImportType || currentImportType;
      console.log('[ImportModal] Current importType:', importType);
      
      if (departmentId) {
        if (importType === 'student_import') {
          console.log('[ImportModal] Student import - loading programs for department:', departmentId);
          // Use window function to ensure correct function is called
          if (window.loadImportModalPrograms) {
            window.loadImportModalPrograms(departmentId);
          } else {
            console.error('[ImportModal] loadImportModalPrograms function not found!');
            showToastNotification('Error: Programs loading function not available', 'error');
          }
        } else {
          console.log('[ImportModal] Faculty import - enabling file upload');
          enableFileUpload();
        }
      } else {
        if (importType === 'student_import') {
          const programSelect = document.getElementById('importProgramSelect');
          programSelect.innerHTML = '<option value="">Select Department first</option>';
          programSelect.disabled = true;
          document.getElementById('selectedProgramId').value = '';
        }
        disableFileUpload();
      }
      
      checkSelectionsComplete();
    });
  }
  
  // Program change handler
  const programSelect = document.getElementById('importProgramSelect');
  if (programSelect) {
    programSelect.addEventListener('change', function() {
      const programId = this.value;
      document.getElementById('selectedProgramId').value = programId;
      checkSelectionsComplete();
    });
  }
  
  // Form submission on Enter key
  const form = document.getElementById('importForm');
  if (form) {
    form.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitImportForm();
      }
    });
  }
  
  // Close modal on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeImportModal();
    }
  });
  
  // Initially disable file upload
  disableFileUpload();
});

// Verify functions are defined after script loads
console.log('[ImportModal] ==========================================');
console.log('[ImportModal] SCRIPT END - Verifying function definitions');
console.log('[ImportModal] ==========================================');
console.log('[ImportModal] Script loaded. Functions defined:', {
  openImportModal: typeof window.openImportModal,
  initializeImportModal: typeof window.initializeImportModal,
  loadImportModalDepartments: typeof window.loadImportModalDepartments,
  loadImportModalPrograms: typeof window.loadImportModalPrograms,
  closeImportModal: typeof window.closeImportModal,
  submitImportForm: typeof window.submitImportForm
});

// CRITICAL DEBUG: Try to call a function to see if script executed
if (typeof window.openImportModal === 'function') {
  console.log('[ImportModal] ✅ SUCCESS: window.openImportModal is defined');
} else {
  console.error('[ImportModal] ❌ ERROR: window.openImportModal is NOT defined!');
  console.error('[ImportModal] This means the script failed to execute properly.');
}
</script> 