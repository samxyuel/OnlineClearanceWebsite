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
      <form id="importForm" class="modal-form" data-endpoint="../../controllers/importData.php" enctype="multipart/form-data" onsubmit="event.preventDefault(); window.submitImportForm(); return false;">
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
      <button type="button" class="modal-action-primary" onclick="window.submitImportForm()" id="importSubmitBtn" disabled>Import Data</button>
    </div>
  </div>
</div>

<!-- Confirmation Dialog for Duplicate Records -->
<div class="modal-overlay import-confirmation-modal-overlay" id="importConfirmationModal" style="display: none;">
  <div class="modal-window" style="max-width: 800px;">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeImportConfirmationModal()">&times;</button>
    
    <!-- Modal Header -->
    <div class="modal-header">
      <h2 class="modal-title">⚠️ Confirm Update</h2>
      <div class="modal-supporting-text" id="confirmationMessage">
        The following records already exist in the database and will be updated.
      </div>
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <div class="confirmation-summary" id="confirmationSummary" style="margin-bottom: 20px; padding: 15px; background-color: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
        <strong id="summaryText">Loading...</strong>
      </div>
      
      <div class="preview-container" style="max-height: 400px; overflow-y: auto;">
        <div class="preview-header" style="margin-bottom: 10px;">
          <span><strong>Records to be Updated:</strong></span>
        </div>
        <div class="preview-table-container">
          <table class="preview-table" id="confirmationTable">
            <thead id="confirmationTableHead">
              <!-- Table headers will be populated dynamically -->
            </thead>
            <tbody id="confirmationTableBody">
              <!-- Duplicate records will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
      
      <div class="confirmation-note" style="margin-top: 15px; padding: 10px; background-color: #e7f3ff; border-radius: 8px; font-size: 0.9rem;">
        <i class="fas fa-info-circle"></i> New records (not shown here) will be imported automatically after confirmation.
      </div>
    </div>
    
    <!-- Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeImportConfirmationModal()">Cancel</button>
      <button type="button" class="modal-action-primary" onclick="confirmImportUpdate()" id="confirmUpdateBtn">Confirm & Update</button>
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
  console.log('[ImportModal] validateAndPreviewFile called for file:', file.name, file.type, file.size);
  
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
    console.error('[ImportModal] Invalid file type:', file.type, fileExtension);
    showToastNotification('Invalid file type. Please upload Excel, CSV, JSON, or XML files.', 'error');
    removeSelectedFile();
    return;
  }
  
  if (file.size > 10 * 1024 * 1024) { // 10MB limit
    console.error('[ImportModal] File too large:', file.size);
    showToastNotification('File size too large. Please upload files smaller than 10MB.', 'error');
    removeSelectedFile();
    return;
  }
  
  // Parse and preview actual file content
  console.log('[ImportModal] File validated, parsing for preview...');
  parseAndPreviewFile(file);
}

// Parse CSV file and generate preview
function parseAndPreviewFile(file) {
  console.log('[ImportModal] ==========================================');
  console.log('[ImportModal] parseAndPreviewFile() called');
  console.log('[ImportModal] File:', file.name, file.type, file.size, 'bytes');
  console.log('[ImportModal] ==========================================');
  
  const reader = new FileReader();
  
  reader.onload = function(e) {
    try {
      const text = e.target.result;
      console.log('[ImportModal] ✓ File read successfully');
      console.log('[ImportModal] File length:', text.length, 'characters');
      console.log('[ImportModal] First 500 chars:', text.substring(0, 500));
      
      const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
      console.log('[ImportModal] File extension:', fileExtension);
      
      if (fileExtension === '.csv') {
        console.log('[ImportModal] Parsing as CSV...');
        parseCSVAndPreview(text);
      } else if (fileExtension === '.json') {
        console.log('[ImportModal] Parsing as JSON...');
        parseJSONAndPreview(text);
      } else {
        console.warn('[ImportModal] Preview not available for', fileExtension, 'files');
        // For Excel/XML, show a message that preview is limited
        showPreviewMessage('Preview available for CSV and JSON files only. File will be processed on import.');
      }
    } catch (error) {
      console.error('[ImportModal] ✗ Error parsing file:', error);
      console.error('[ImportModal] Error stack:', error.stack);
      showPreviewMessage('Error parsing file: ' + error.message);
    }
  };
  
  reader.onerror = function(error) {
    console.error('[ImportModal] ✗ FileReader error:', error);
    console.error('[ImportModal] Error details:', error.target?.error);
    showPreviewMessage('Error reading file: ' + (error.target?.error?.message || 'Unknown error'));
  };
  
  reader.onprogress = function(e) {
    if (e.lengthComputable) {
      const percentLoaded = Math.round((e.loaded / e.total) * 100);
      console.log('[ImportModal] File reading progress:', percentLoaded + '%');
    }
  };
  
  console.log('[ImportModal] Starting file read...');
  reader.readAsText(file);
}

// Parse CSV content
function parseCSVAndPreview(csvText) {
  console.log('[ImportModal] Parsing CSV content...');
  
  const lines = csvText.split(/\r?\n/).filter(line => line.trim() !== '');
  if (lines.length === 0) {
    showPreviewMessage('File is empty or contains no data.');
    return;
  }
  
  // Parse header row
  const headers = parseCSVLine(lines[0]);
  console.log('[ImportModal] CSV Headers:', headers);
  
  // Parse data rows
  const dataRows = [];
  const errors = [];
  
  for (let i = 1; i < lines.length; i++) {
    try {
      const values = parseCSVLine(lines[i]);
      if (values.length === 0) continue; // Skip empty rows
      
      // Create row object
      const row = {};
      headers.forEach((header, index) => {
        row[header.trim()] = values[index] ? values[index].trim() : '';
      });
      
      dataRows.push(row);
    } catch (error) {
      errors.push({ row: i + 1, error: error.message });
    }
  }
  
  console.log('[ImportModal] Parsed', dataRows.length, 'rows,', errors.length, 'errors');
  console.log('[ImportModal] First row:', dataRows[0]);
  
  // Display preview
  displayPreview(dataRows, headers, errors, lines.length - 1);
}

// Parse a single CSV line (handles quoted fields)
function parseCSVLine(line) {
  const result = [];
  let current = '';
  let inQuotes = false;
  
  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    
    if (char === '"') {
      if (inQuotes && line[i + 1] === '"') {
        // Escaped quote
        current += '"';
        i++; // Skip next quote
      } else {
        // Toggle quote state
        inQuotes = !inQuotes;
      }
    } else if (char === ',' && !inQuotes) {
      // End of field
      result.push(current);
      current = '';
    } else {
      current += char;
    }
  }
  
  // Add last field
  result.push(current);
  
  return result;
}

// Parse JSON content
function parseJSONAndPreview(jsonText) {
  try {
    const data = JSON.parse(jsonText);
    const rows = Array.isArray(data) ? data : [data];
    
    if (rows.length === 0) {
      showPreviewMessage('JSON file contains no data.');
      return;
    }
    
    const headers = Object.keys(rows[0]);
    displayPreview(rows, headers, [], rows.length);
  } catch (error) {
    console.error('[ImportModal] JSON parse error:', error);
    showPreviewMessage('Error parsing JSON: ' + error.message);
  }
}

// Display preview in table
function displayPreview(dataRows, headers, errors, totalRows) {
  const previewContainer = document.getElementById('previewContainer');
  const noPreview = document.getElementById('noPreview');
  const previewTableHead = document.getElementById('previewTableHead');
  const previewTableBody = document.getElementById('previewTableBody');
  
  if (!previewContainer || !previewTableHead || !previewTableBody) {
    console.error('[ImportModal] Preview elements not found');
    return;
  }
  
  // Clear previous content
  previewTableHead.innerHTML = '';
  previewTableBody.innerHTML = '';
  
  // Create header row
  const headerRow = document.createElement('tr');
  headers.forEach(header => {
    const th = document.createElement('th');
    th.textContent = header;
    headerRow.appendChild(th);
  });
  previewTableHead.appendChild(headerRow);
  
  // Display first 5 rows
  const previewRows = dataRows.slice(0, 5);
  previewRows.forEach(row => {
    const tr = document.createElement('tr');
    headers.forEach(header => {
      const td = document.createElement('td');
      const value = row[header.trim()] || '';
      td.textContent = value;
      tr.appendChild(td);
    });
    previewTableBody.appendChild(tr);
  });
  
  // Update stats
  document.getElementById('totalRows').textContent = `Total Rows: ${totalRows}`;
  document.getElementById('validRows').textContent = `Valid Rows: ${dataRows.length}`;
  document.getElementById('errorRows').textContent = `Errors: ${errors.length}`;
  
  // Show preview
  previewContainer.style.display = 'block';
  noPreview.style.display = 'none';
  
  console.log('[ImportModal] Preview displayed:', previewRows.length, 'rows shown');
}

// Show preview message instead of table
function showPreviewMessage(message) {
  const previewContainer = document.getElementById('previewContainer');
  const noPreview = document.getElementById('noPreview');
  const previewTableBody = document.getElementById('previewTableBody');
  
  if (previewTableBody) {
    previewTableBody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 20px; color: #666;">${message}</td></tr>`;
  }
  
  if (previewContainer) previewContainer.style.display = 'block';
  if (noPreview) noPreview.style.display = 'none';
}

function refreshPreview() {
  const fileInput = document.getElementById('fileInput');
  if (fileInput && fileInput.files[0]) {
    console.log('[ImportModal] Refreshing preview...');
    parseAndPreviewFile(fileInput.files[0]);
    showToastNotification('Preview refreshed', 'success');
  } else {
    showToastNotification('No file selected to preview', 'warning');
  }
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
    if (typeof showToastNotification === 'function') {
      showToastNotification('Import modal not found. Please refresh the page.', 'error');
    }
    return;
  }
  
  console.log('[ImportModal] Showing modal...');
  
  // Use window.openModal if available, otherwise fallback
  if (typeof window.openModal === 'function') {
    window.openModal('importModal');
  } else {
    // Fallback to direct manipulation
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
    requestAnimationFrame(() => {
      modal.classList.add('active');
    });
  }
  
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
  console.log('[ImportModal] closeImportModal() called');
  try {
    const modal = document.getElementById('importModal');
    if (!modal) {
      console.warn('[ImportModal] Modal not found');
      return;
    }
    console.log('[ImportModal] Closing modal:', modal.id);
    
    // Use window.closeModal if available, otherwise fallback
    if (typeof window.closeModal === 'function') {
      window.closeModal('importModal');
    } else {
      // Fallback to direct manipulation
      modal.style.display = 'none';
      document.body.classList.remove('modal-open');
      modal.classList.remove('active');
    }
    
    // Reset form
    const form = document.getElementById('importForm');
    if (form) {
      form.reset();
    }
    removeSelectedFile();
  } catch (error) {
    // Silent error handling
  }
}

// Store pending form data for confirmation resubmission
let pendingImportData = null;

// Download credentials file
function downloadCredentialsFile(fileContent, fileName) {
  console.log('[ImportModal] Downloading credentials file:', fileName);
  
  try {
    // Create a blob from the file content
    const blob = new Blob([fileContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    
    // Create a temporary anchor element and trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    
    // Clean up
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
    console.log('[ImportModal] ✓ Credentials file downloaded successfully');
    showToastNotification('Credentials file downloaded', 'success');
  } catch (error) {
    console.error('[ImportModal] Error downloading credentials file:', error);
    showToastNotification('Error downloading credentials file', 'error');
  }
}

// Show confirmation modal with duplicates
function showImportConfirmationModal(data) {
  console.log('[ImportModal] showImportConfirmationModal called with:', data);
  
  const modal = document.getElementById('importConfirmationModal');
  if (!modal) {
    console.error('[ImportModal] Confirmation modal not found!');
    return;
  }
  
  const duplicates = data.duplicates || [];
  const duplicateCount = data.duplicateCount || duplicates.length;
  const newCount = data.newCount || 0;
  const total = data.total || 0;
  
  // Update summary text
  const summaryText = document.getElementById('summaryText');
  if (summaryText) {
    summaryText.textContent = `${duplicateCount} existing record(s) will be updated. ${newCount > 0 ? newCount + ' new record(s) will be imported automatically.' : ''}`;
  }
  
  // Determine if student or faculty import
  const importType = document.getElementById('importType').value;
  const isStudent = importType === 'student_import';
  
  // Populate table
  const tableHead = document.getElementById('confirmationTableHead');
  const tableBody = document.getElementById('confirmationTableBody');
  
  if (tableHead && tableBody) {
    // Clear existing content
    tableHead.innerHTML = '';
    tableBody.innerHTML = '';
    
    // Create header row
    const headerRow = document.createElement('tr');
    if (isStudent) {
      headerRow.innerHTML = '<th>Student Number</th><th>Name</th><th>Department</th><th>Program</th><th>Year Level</th><th>Section</th>';
    } else {
      headerRow.innerHTML = '<th>Employee Number</th><th>Name</th><th>Department</th><th>Employment Status</th>';
    }
    tableHead.appendChild(headerRow);
    
    // Populate data rows
    duplicates.forEach(duplicate => {
      const row = document.createElement('tr');
      
      if (isStudent) {
        const name = [duplicate.first_name, duplicate.middle_name, duplicate.last_name].filter(Boolean).join(' ');
        row.innerHTML = `
          <td>${duplicate.student_number || ''}</td>
          <td>${name || ''}</td>
          <td>${duplicate.department || ''}</td>
          <td>${duplicate.program || ''}</td>
          <td>${duplicate.year_level || ''}</td>
          <td>${duplicate.section || ''}</td>
        `;
      } else {
        const name = [duplicate.first_name, duplicate.middle_name, duplicate.last_name].filter(Boolean).join(' ');
        row.innerHTML = `
          <td>${duplicate.employee_number || ''}</td>
          <td>${name || ''}</td>
          <td>${duplicate.department || ''}</td>
          <td>${duplicate.employment_status || ''}</td>
        `;
      }
      
      tableBody.appendChild(row);
    });
  }
  
  // Show modal
  modal.style.display = 'flex';
  document.body.classList.add('modal-open');
}

// Close confirmation modal
function closeImportConfirmationModal() {
  const modal = document.getElementById('importConfirmationModal');
  if (!modal) return;
  
  modal.style.display = 'none';
  document.body.classList.remove('modal-open');
  
  // Clear pending data
  pendingImportData = null;
}

// Confirm and proceed with update
function confirmImportUpdate() {
  console.log('[ImportModal] confirmImportUpdate called');
  
  const confirmBtn = document.getElementById('confirmUpdateBtn');
  const form = document.getElementById('importForm');
  const fileInput = document.getElementById('fileInput');
  const submitBtn = document.getElementById('importSubmitBtn');
  
  if (!form || !fileInput) {
    console.error('[ImportModal] Form or fileInput not found!');
    showToastNotification('Error: Form elements not found', 'error');
    return;
  }
  
  // Disable confirm button
  if (confirmBtn) {
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Processing...';
  }
  
  // Get form values
  const departmentId = document.getElementById('selectedDepartmentId').value;
  const programId = document.getElementById('selectedProgramId').value;
  const pageType = document.getElementById('pageType').value;
  const importType = document.getElementById('importType').value;
  const importMode = document.querySelector('input[name="importMode"]:checked')?.value || 'update';
  const validateData = document.querySelector('input[name="validateData"]')?.checked || false;
  const file = fileInput.files[0];
  
  // Create FormData
  const formData = new FormData();
  formData.append('type', importType);
  formData.append('pageType', pageType);
  formData.append('selectedDepartment', departmentId);
  if (programId) {
    formData.append('selectedProgram', programId);
  }
  formData.append('importMode', importMode);
  formData.append('validateData', validateData ? 'on' : '');
  formData.append('confirmed', '1'); // Flag to skip duplicate check on backend
  formData.append('importFile', file);
  
  // Get endpoint URL
  const endpoint = form.dataset.endpoint || '../../controllers/importData.php';
  
  console.log('[ImportModal] Proceeding with confirmed import...');
  
  // Submit to backend
  fetch(endpoint, {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(response => {
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      return response.text().then(text => {
        console.error('[ImportModal] Non-JSON response:', text.substring(0, 500));
        throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
      });
    }
    return response.json();
  })
  .then(data => {
    console.log('[ImportModal] Confirmed import response:', data);
    
    // Close confirmation modal
    closeImportConfirmationModal();
    
    if (data.success) {
      showToastNotification(data.message || 'Data imported successfully', 'success');
      
      // Handle credentials file download if available
      if (data.credentialsFile && data.credentialsFileName) {
        downloadCredentialsFile(data.credentialsFile, data.credentialsFileName);
      }
      
      // Trigger table refresh (same logic as in submitImportForm)
      const importType = document.getElementById('importType').value;
      const pageType = document.getElementById('pageType').value;
      
      if (importType === 'student_import' && typeof window.loadStudentsData === 'function') {
        window.loadStudentsData();
      } else if (importType === 'faculty_import') {
        if (typeof window.fetchFaculty === 'function') {
          window.fetchFaculty();
        } else if (typeof window.refreshFacultyTable === 'function') {
          window.refreshFacultyTable();
        }
      }
      
      // Close import modal after a delay
      setTimeout(() => {
        window.closeImportModal();
      }, 1000);
    } else {
      showToastNotification(data.message || 'Import failed', 'error');
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm & Update';
      }
    }
  })
  .catch(error => {
    console.error('[ImportModal] Confirmed import error:', error);
    showToastNotification('An error occurred during import: ' + error.message, 'error');
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm & Update';
    }
  });
}

// Ensure functions are available even if DOMContentLoaded hasn't fired
console.log('[ImportModal] Defining window.submitImportForm...');
window.submitImportForm = function() {
  console.log('[ImportModal] ==========================================');
  console.log('[ImportModal] submitImportForm() CALLED');
  console.log('[ImportModal] ==========================================');
  
  // Validate form first
  console.log('[ImportModal] Validating form...');
  if (!validateImportForm()) {
    console.error('[ImportModal] Form validation failed');
    return;
  }
  console.log('[ImportModal] ✓ Form validation passed');
  
  const submitBtn = document.getElementById('importSubmitBtn');
  const form = document.getElementById('importForm');
  const fileInput = document.getElementById('fileInput');
  
  if (!form || !fileInput) {
    console.error('[ImportModal] Form or fileInput not found!');
    showToastNotification('Error: Form elements not found', 'error');
    return;
  }
  
  // Show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = 'Importing...';
  form.classList.add('modal-loading');
  
  // Get form values for logging
  const departmentId = document.getElementById('selectedDepartmentId').value;
  const programId = document.getElementById('selectedProgramId').value;
  const pageType = document.getElementById('pageType').value;
  const importType = document.getElementById('importType').value;
  const importMode = document.querySelector('input[name="importMode"]:checked')?.value || 'skip';
  const validateData = document.querySelector('input[name="validateData"]')?.checked || false;
  const file = fileInput.files[0];
  
  // Detect current page context for better debugging
  const currentPagePath = window.location.pathname;
  const isCollegePage = currentPagePath.includes('CollegeStudentManagement');
  const isShsPage = currentPagePath.includes('SeniorHighStudentManagement') || currentPagePath.includes('SHSStudentManagement');
  const isFacultyPage = currentPagePath.includes('FacultyManagement');
  const isAdminPage = currentPagePath.includes('/admin/');
  const isProgramHeadPage = currentPagePath.includes('/program-head/');
  
  console.log('[ImportModal] Form values:', {
    departmentId,
    programId,
    pageType,
    importType,
    importMode,
    validateData,
    fileName: file ? file.name : 'NO FILE',
    fileSize: file ? file.size : 0
  });
  
  console.log('[ImportModal] Page Context Detection:', {
    currentPagePath,
    isCollegePage,
    isShsPage,
    isFacultyPage,
    isAdminPage,
    isProgramHeadPage,
    detectedPageType: isCollegePage ? 'College' : (isShsPage ? 'SHS' : (isFacultyPage ? 'Faculty' : 'Unknown'))
  });
  
  // Create FormData for file upload
  const formData = new FormData(form);
  
  // Explicitly add all required fields to FormData
  formData.set('type', importType);
  formData.set('pageType', pageType);
  formData.set('selectedDepartment', departmentId);
  if (programId) {
    formData.set('selectedProgram', programId);
  }
  formData.set('importMode', importMode);
  formData.set('validateData', validateData ? 'on' : '');
  
  // Verify file is in FormData
  if (!file) {
    console.error('[ImportModal] ERROR: No file selected!');
    showToastNotification('Please select a file to import', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Import Data';
    form.classList.remove('modal-loading');
    return;
  }
  
  // Log FormData contents (for debugging)
  console.log('[ImportModal] FormData entries:');
  for (const [key, value] of formData.entries()) {
    if (value instanceof File) {
      console.log(`  ${key}: [File] ${value.name} (${value.size} bytes, type: ${value.type})`);
    } else {
      console.log(`  ${key}: ${value}`);
    }
  }
  
  // Get endpoint URL
  const endpoint = form.dataset.endpoint || '../../controllers/importData.php';
  console.log('[ImportModal] Submitting to endpoint:', endpoint);
  console.log('[ImportModal] Full URL will be:', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/') + endpoint);
  
  // Submit to backend
  console.log('[ImportModal] Initiating fetch request...');
  fetch(endpoint, {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(response => {
    console.log('[ImportModal] Response received:', {
      status: response.status,
      statusText: response.statusText,
      ok: response.ok,
      headers: Object.fromEntries(response.headers.entries())
    });
    
    // Check content type
    const contentType = response.headers.get('content-type');
    console.log('[ImportModal] Response content-type:', contentType);
    
    if (!contentType || !contentType.includes('application/json')) {
      // Try to get text response for debugging
      return response.text().then(text => {
        console.error('[ImportModal] Non-JSON response:', text.substring(0, 500));
        throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
      });
    }
    
    return response.json();
  })
  .then(data => {
    console.log('[ImportModal] Response data:', data);
    
    // Check if confirmation is needed (duplicates found in update mode)
    if (data.needsConfirmation && data.duplicates) {
      console.log('[ImportModal] Duplicates found - showing confirmation dialog');
      showImportConfirmationModal(data);
      // Reset button state
      submitBtn.disabled = false;
      submitBtn.textContent = 'Import Data';
      form.classList.remove('modal-loading');
      return;
    }
    
    if (data.success) {
      console.log('[ImportModal] ✓ Import successful!');
      showToastNotification(data.message || 'Data imported successfully', 'success');
      
      // Handle credentials file download if available
      if (data.credentialsFile && data.credentialsFileName) {
        downloadCredentialsFile(data.credentialsFile, data.credentialsFileName);
      }
      
      // Trigger table refresh
      console.log('[ImportModal] ==========================================');
      console.log('[ImportModal] Attempting to refresh table...');
      console.log('[ImportModal] Import type:', importType);
      console.log('[ImportModal] Page type:', pageType);
      
      // Check which refresh functions are available
      const availableFunctions = {
        loadStudentsData: typeof window.loadStudentsData === 'function',
        fetchFaculty: typeof window.fetchFaculty === 'function',
        refreshFacultyTable: typeof window.refreshFacultyTable === 'function',
        onImportSuccess: typeof window.onImportSuccess === 'function'
      };
      
      console.log('[ImportModal] Available refresh functions:', availableFunctions);
      
      // Try multiple refresh methods based on import type and page
      let refreshCalled = false;
      
      if (importType === 'student_import') {
        // Student import - try loadStudentsData first
        if (availableFunctions.loadStudentsData) {
          console.log('[ImportModal] ✓ Found loadStudentsData() - calling for', pageType, 'students...');
          try {
            window.loadStudentsData();
            refreshCalled = true;
            console.log('[ImportModal] ✓ loadStudentsData() called successfully');
          } catch (error) {
            console.error('[ImportModal] ✗ Error calling loadStudentsData():', error);
          }
        } else {
          console.warn('[ImportModal] ⚠️ loadStudentsData() not found for student import');
        }
      } else if (importType === 'faculty_import') {
        // Faculty import - try both faculty refresh functions
        if (availableFunctions.fetchFaculty) {
          console.log('[ImportModal] ✓ Found fetchFaculty() - calling...');
          try {
            window.fetchFaculty();
            refreshCalled = true;
            console.log('[ImportModal] ✓ fetchFaculty() called successfully');
          } catch (error) {
            console.error('[ImportModal] ✗ Error calling fetchFaculty():', error);
          }
        } else if (availableFunctions.refreshFacultyTable) {
          console.log('[ImportModal] ✓ Found refreshFacultyTable() - calling...');
          try {
            window.refreshFacultyTable();
            refreshCalled = true;
            console.log('[ImportModal] ✓ refreshFacultyTable() called successfully');
          } catch (error) {
            console.error('[ImportModal] ✗ Error calling refreshFacultyTable():', error);
          }
        } else {
          console.warn('[ImportModal] ⚠️ No faculty refresh function found (fetchFaculty or refreshFacultyTable)');
        }
      }
      
      // Fallback to callback if available
      if (!refreshCalled && availableFunctions.onImportSuccess) {
        console.log('[ImportModal] Using fallback: onImportSuccess() callback...');
        try {
          window.onImportSuccess(data);
          refreshCalled = true;
          console.log('[ImportModal] ✓ onImportSuccess() called successfully');
        } catch (error) {
          console.error('[ImportModal] ✗ Error calling onImportSuccess():', error);
        }
      }
      
      if (!refreshCalled) {
        console.warn('[ImportModal] ⚠️ No refresh function was called!');
        console.warn('[ImportModal] Available window functions:', Object.keys(window).filter(k => 
          typeof window[k] === 'function' && 
          (k.toLowerCase().includes('student') || 
           k.toLowerCase().includes('faculty') || 
           k.toLowerCase().includes('refresh') || 
           k.toLowerCase().includes('load') ||
           k.toLowerCase().includes('fetch'))
        ).slice(0, 20));
        console.warn('[ImportModal] Reloading page as fallback...');
        // Fallback: reload page after a short delay
  setTimeout(() => {
          window.location.reload();
        }, 1500);
      }
      
      console.log('[ImportModal] ==========================================');
      
      // Close modal after a short delay to show success message
      setTimeout(() => {
    window.closeImportModal();
      }, 1000);
    } else {
      console.error('[ImportModal] ✗ Import failed:', data.message);
      showToastNotification(data.message || 'Import failed', 'error');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Import Data';
      form.classList.remove('modal-loading');
    }
  })
  .catch(error => {
    console.error('[ImportModal] ==========================================');
    console.error('[ImportModal] ✗ IMPORT ERROR:', error);
    console.error('[ImportModal] Error message:', error.message);
    console.error('[ImportModal] Error stack:', error.stack);
    console.error('[ImportModal] ==========================================');
    
    showToastNotification('An error occurred during import: ' + error.message, 'error');
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