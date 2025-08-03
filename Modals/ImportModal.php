<?php
// Import Modal - Import Student Data
// This modal is included in StudentManagement.php
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay import-modal-overlay" id="importModal">
  <div class="modal-window" style="max-width: 700px;">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeImportModal()">&times;</button>
    
    <!-- Modal Title -->
    <h2 class="modal-title">📥 Import Student Data</h2>
    
    <!-- Supporting Text -->
    <div class="modal-supporting-text">Upload a file to import student data. Supported formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)</div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="importForm" class="modal-form" data-endpoint="../../controllers/importData.php" enctype="multipart/form-data">
        <input type="hidden" name="type" value="student_import">
        
        <!-- File Upload Section -->
        <div class="import-section">
          <h3 class="section-title">📁 File Upload</h3>
          
          <!-- Drag & Drop Area -->
          <div class="file-upload-area" id="fileUploadArea">
            <div class="upload-content">
              <i class="fas fa-cloud-upload-alt upload-icon"></i>
              <h4>Drag & Drop your file here</h4>
              <p>or</p>
              <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
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
              <span class="radio-label">Skip existing students</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="importMode" value="update">
              <span class="radio-custom"></span>
              <span class="radio-label">Update existing students</span>
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
            <label class="checkbox-option">
              <input type="checkbox" name="createBackup">
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Create backup before import</span>
            </label>
            <label class="checkbox-option">
              <input type="checkbox" name="sendNotification">
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Send email notifications</span>
            </label>
          </div>
        </div>
        
        <!-- Data Mapping Section -->
        <div class="import-section">
          <h3 class="section-title">📋 Data Mapping</h3>
          <div class="mapping-grid">
            <div class="mapping-row">
              <label>Student Number</label>
              <select name="mapping[studentNumber]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="A" selected>A - Student Number</option>
                <option value="B">B - Student ID</option>
                <option value="C">C - ID Number</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>Last Name</label>
              <select name="mapping[lastName]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="B">B - Last Name</option>
                <option value="C">C - Surname</option>
                <option value="D">D - Family Name</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>First Name</label>
              <select name="mapping[firstName]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="C">C - First Name</option>
                <option value="D">D - Given Name</option>
                <option value="E">E - Name</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>Program</label>
              <select name="mapping[program]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="D">D - Program</option>
                <option value="E">E - Course</option>
                <option value="F">F - Degree</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>Year Level</label>
              <select name="mapping[yearLevel]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="E">E - Year Level</option>
                <option value="F">F - Year</option>
                <option value="G">G - Level</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>Section</label>
              <select name="mapping[section]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="F">F - Section</option>
                <option value="G">G - Class</option>
                <option value="H">H - Group</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>Email</label>
              <select name="mapping[email]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="G">G - Email</option>
                <option value="H">H - Email Address</option>
                <option value="I">I - Contact Email</option>
              </select>
            </div>
            <div class="mapping-row">
              <label>Contact Number</label>
              <select name="mapping[contactNumber]" class="mapping-select">
                <option value="">Select Column</option>
                <option value="H">H - Contact Number</option>
                <option value="I">I - Phone</option>
                <option value="J">J - Mobile</option>
              </select>
            </div>
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
                <thead>
                  <tr>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Status</th>
                  </tr>
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

<style>
/* Import Modal Specific Styles */
.import-section {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--light-blue-gray);
}

.import-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

/* File Upload Area */
.file-upload-area {
  border: 2px dashed var(--light-blue-gray);
  border-radius: 12px;
  padding: 40px 20px;
  text-align: center;
  transition: all 0.3s ease;
  background: var(--very-light-off-white);
  cursor: pointer;
}

.file-upload-area:hover {
  border-color: var(--medium-muted-blue);
  background: white;
}

.file-upload-area.dragover {
  border-color: var(--medium-muted-blue);
  background: rgba(81, 134, 177, 0.1);
  transform: scale(1.02);
}

.upload-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}

.upload-icon {
  font-size: 3rem;
  color: var(--medium-muted-blue);
  margin-bottom: 10px;
}

.upload-content h4 {
  margin: 0;
  color: var(--deep-navy-blue);
  font-size: 1.2rem;
}

.upload-content p {
  margin: 5px 0;
  color: var(--medium-muted-blue);
}

.file-info {
  font-size: 0.85rem;
  color: var(--medium-muted-blue);
  margin-top: 10px;
}

/* Selected File */
.selected-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background: var(--very-light-off-white);
  border-radius: 8px;
  margin-top: 15px;
}

.file-details {
  display: flex;
  align-items: center;
  gap: 10px;
}

.file-details i {
  color: var(--medium-muted-blue);
  font-size: 1.2rem;
}

/* Data Mapping */
.mapping-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.mapping-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mapping-row label {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--deep-navy-blue);
}

.mapping-select {
  padding: 8px 12px;
  border: 1px solid var(--light-blue-gray);
  border-radius: 6px;
  background: white;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.mapping-select:focus {
  border-color: var(--medium-muted-blue);
  outline: none;
}

/* Preview Section */
.preview-container {
  background: var(--very-light-off-white);
  border-radius: 8px;
  padding: 15px;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  font-size: 0.9rem;
  color: var(--medium-muted-blue);
}

.preview-table-container {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid var(--light-blue-gray);
  border-radius: 6px;
  background: white;
}

.preview-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.preview-table th {
  background: var(--light-blue-gray);
  padding: 10px 8px;
  text-align: left;
  font-weight: 600;
  color: var(--deep-navy-blue);
  position: sticky;
  top: 0;
}

.preview-table td {
  padding: 8px;
  border-bottom: 1px solid var(--light-blue-gray);
}

.preview-table tr:hover {
  background: var(--very-light-off-white);
}

.preview-stats {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
  font-size: 0.85rem;
  color: var(--medium-muted-blue);
}

.no-preview {
  text-align: center;
  padding: 40px 20px;
  color: var(--medium-muted-blue);
}

.no-preview i {
  font-size: 2rem;
  margin-bottom: 10px;
}

.no-preview p {
  margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .mapping-grid {
    grid-template-columns: 1fr;
  }
  
  .preview-stats {
    flex-direction: column;
    gap: 5px;
  }
  
  .modal-window {
    max-width: 95vw !important;
  }
}
</style>

<script>
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
  showNotification('Preview refreshed', 'success');
}

// Form validation
function validateImportForm() {
  const fileInput = document.getElementById('fileInput');
  const importMode = document.querySelector('input[name="importMode"]:checked').value;
  
  if (!fileInput.files[0]) {
    showNotification('Please select a file to import', 'error');
    return false;
  }
  
  // Validate mapping selections
  const requiredMappings = ['studentNumber', 'lastName', 'firstName'];
  for (const field of requiredMappings) {
    const mappingSelect = document.querySelector(`select[name="mapping[${field}]"]`);
    if (!mappingSelect.value) {
      showNotification(`Please map the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()} field`, 'error');
      return false;
    }
  }
  
  if (importMode === 'replace') {
    if (!confirm('This will replace all existing data. Are you sure you want to continue?')) {
      return false;
    }
  }
  
  return true;
}

// Modal functions
window.openImportModal = function() {
  document.getElementById('importModal').style.display = 'flex';
  document.body.classList.add('modal-open');
  
  // Reset form
  document.getElementById('importForm').reset();
  removeSelectedFile();
  
  // Reset to defaults
  document.querySelector('input[name="importMode"][value="skip"]').checked = true;
  document.querySelector('input[name="validateData"]').checked = true;
}

window.closeImportModal = function() {
  document.getElementById('importModal').style.display = 'none';
  document.body.classList.remove('modal-open');
  
  // Reset form
  document.getElementById('importForm').reset();
  removeSelectedFile();
}

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
  
  // Simulate import process
  setTimeout(() => {
    const fileName = document.getElementById('fileName').textContent;
    showNotification(`Successfully imported data from ${fileName}`, 'success');
    window.closeImportModal();
    
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.textContent = 'Import Data';
    form.classList.remove('modal-loading');
  }, 3000);
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
});
</script> 