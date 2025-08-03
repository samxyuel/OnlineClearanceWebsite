<?php // Faculty Import Modal - Import Faculty Data ?>
<link rel="stylesheet" href="../../assets/css/modals.css">
<div class="modal-overlay faculty-import-modal-overlay" id="facultyImportModal">
  <div class="modal-window" style="max-width: 700px;">
    <button class="modal-close" onclick="closeFacultyImportModal()">&times;</button>
    <h2 class="modal-title">📥 Import Faculty Data</h2>
    <div class="modal-supporting-text">Upload a file to import faculty data. Supported formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), XML (.xml)</div>
    <div class="modal-content-area">
      <form id="facultyImportForm" class="modal-form" data-endpoint="../../controllers/importData.php" enctype="multipart/form-data">
        <input type="hidden" name="type" value="faculty_import">
        <div class="import-section">
          <h3 class="section-title">📁 File Upload</h3>
          <div class="file-upload-area" id="facultyFileUploadArea">
            <div class="upload-content">
              <i class="fas fa-cloud-upload-alt"></i>
              <h4>Drag & Drop Files Here</h4>
              <p>or</p>
              <button type="button" class="upload-btn" onclick="document.getElementById('facultyFileInput').click()">
                Choose File
              </button>
              <p class="supported-formats">Supported: .xlsx, .xls, .csv, .json, .xml</p>
            </div>
          </div>
          <input type="file" id="facultyFileInput" name="importFile" accept=".xlsx,.xls,.csv,.json,.xml" style="display: none;" onchange="handleFacultyFileSelect(this)">
        </div>
        
        <div class="selected-file" id="facultySelectedFile" style="display: none;">
          <div class="file-info">
            <i class="fas fa-file"></i>
            <div class="file-details">
              <span class="file-name" id="facultyFileName"></span>
              <span class="file-size" id="facultyFileSize"></span>
            </div>
            <button type="button" class="remove-file-btn" onclick="removeFacultySelectedFile()">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="import-section">
          <h3 class="section-title">⚙️ Import Options</h3>
          <div class="radio-group">
            <label class="radio-label">
              <input type="radio" name="importMode" value="skip" checked>
              <span class="radio-custom"></span>
              Skip existing faculty (keep current data)
            </label>
            <label class="radio-label">
              <input type="radio" name="importMode" value="update">
              <span class="radio-custom"></span>
              Update existing faculty (overwrite data)
            </label>
            <label class="radio-label">
              <input type="radio" name="importMode" value="replace">
              <span class="radio-custom"></span>
              Replace all faculty data
            </label>
          </div>
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" name="validateData" checked>
              <span class="checkmark"></span>
              Validate data before import
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="sendNotifications" checked>
              <span class="checkmark"></span>
              Send welcome emails to new faculty
            </label>
          </div>
        </div>
        
        <div class="import-section">
          <h3 class="section-title">📋 Data Mapping</h3>
          <div class="mapping-grid">
            <div class="mapping-item">
              <label>Employee Number</label>
              <select name="mapping_employeeNumber">
                <option value="employee_number">Employee Number</option>
                <option value="emp_id">Employee ID</option>
                <option value="id">ID</option>
              </select>
            </div>
            <div class="mapping-item">
              <label>Employment Status</label>
              <select name="mapping_employmentStatus">
                <option value="employment_status">Employment Status</option>
                <option value="status">Status</option>
                <option value="type">Type</option>
              </select>
            </div>
            <div class="mapping-item">
              <label>Last Name</label>
              <select name="mapping_lastName">
                <option value="last_name">Last Name</option>
                <option value="surname">Surname</option>
                <option value="family_name">Family Name</option>
              </select>
            </div>
            <div class="mapping-item">
              <label>First Name</label>
              <select name="mapping_firstName">
                <option value="first_name">First Name</option>
                <option value="given_name">Given Name</option>
                <option value="name">Name</option>
              </select>
            </div>
            <div class="mapping-item">
              <label>Email</label>
              <select name="mapping_email">
                <option value="email">Email</option>
                <option value="email_address">Email Address</option>
                <option value="contact_email">Contact Email</option>
              </select>
            </div>
            <div class="mapping-item">
              <label>Contact Number</label>
              <select name="mapping_contactNumber">
                <option value="contact_number">Contact Number</option>
                <option value="phone">Phone</option>
                <option value="mobile">Mobile</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="import-section">
          <h3 class="section-title">📊 Data Preview</h3>
          <div class="preview-container" id="facultyPreviewContainer" style="display: none;">
            <div class="preview-table-wrapper">
              <table class="preview-table">
                <thead>
                  <tr>
                    <th>Employee Number</th>
                    <th>Employment Status</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                  </tr>
                </thead>
                <tbody id="facultyPreviewBody">
                  <!-- Preview data will be populated here -->
                </tbody>
              </table>
            </div>
          </div>
          <div class="no-preview" id="facultyNoPreview">
            <i class="fas fa-file-alt"></i>
            <p>Upload a file to see a preview of the faculty data</p>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeFacultyImportModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitFacultyImportForm()" id="facultyImportSubmitBtn">Import Faculty Data</button>
    </div>
  </div>
</div>

<style>
  .import-section {
    margin-bottom: 25px;
  }
  
  .section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--deep-navy-blue);
    margin-bottom: 12px;
  }
  
  .file-upload-area {
    border: 2px dashed var(--light-blue-gray);
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
  }
  
  .file-upload-area:hover {
    border-color: var(--medium-muted-blue);
    background-color: rgba(81, 134, 177, 0.05);
  }
  
  .upload-content i {
    font-size: 3rem;
    color: var(--medium-muted-blue);
    margin-bottom: 15px;
  }
  
  .upload-content h4 {
    margin: 0 0 8px 0;
    color: var(--deep-navy-blue);
  }
  
  .upload-content p {
    margin: 5px 0;
    color: var(--medium-muted-blue);
  }
  
  .upload-btn {
    background: var(--medium-muted-blue);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    margin: 10px 0;
    transition: all 0.3s ease;
  }
  
  .upload-btn:hover {
    background: var(--darker-saturated-blue);
  }
  
  .supported-formats {
    font-size: 0.85rem;
    color: var(--medium-muted-blue);
  }
  
  .selected-file {
    background: var(--very-light-off-white);
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
  }
  
  .file-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .file-info i {
    color: var(--medium-muted-blue);
    font-size: 1.2rem;
  }
  
  .file-details {
    flex: 1;
  }
  
  .file-name {
    display: block;
    font-weight: 600;
    color: var(--deep-navy-blue);
  }
  
  .file-size {
    display: block;
    font-size: 0.85rem;
    color: var(--medium-muted-blue);
  }
  
  .remove-file-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s ease;
  }
  
  .remove-file-btn:hover {
    background: rgba(220, 53, 69, 0.1);
  }
  
  .radio-group, .checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 15px 0;
  }
  
  .radio-label, .checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background-color 0.3s ease;
  }
  
  .radio-label:hover, .checkbox-label:hover {
    background-color: rgba(81, 134, 177, 0.05);
  }
  
  .mapping-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 15px 0;
  }
  
  .mapping-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  
  .mapping-item label {
    font-weight: 600;
    color: var(--deep-navy-blue);
    font-size: 0.9rem;
  }
  
  .mapping-item select {
    padding: 8px 12px;
    border: 1px solid var(--light-blue-gray);
    border-radius: 6px;
    background: white;
    font-size: 0.9rem;
  }
  
  .preview-container {
    background: white;
    border: 1px solid var(--light-blue-gray);
    border-radius: 8px;
    overflow: hidden;
  }
  
  .preview-table-wrapper {
    max-height: 200px;
    overflow-y: auto;
  }
  
  .preview-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .preview-table th,
  .preview-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid var(--light-blue-gray);
    font-size: 0.85rem;
  }
  
  .preview-table th {
    background: var(--very-light-off-white);
    font-weight: 600;
    color: var(--deep-navy-blue);
    position: sticky;
    top: 0;
  }
  
  .no-preview {
    text-align: center;
    padding: 40px 20px;
    color: var(--medium-muted-blue);
  }
  
  .no-preview i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
  }
  
  @media (max-width: 768px) {
    .mapping-grid {
      grid-template-columns: 1fr;
    }
    
    .file-upload-area {
      padding: 20px;
    }
    
    .upload-content i {
      font-size: 2rem;
    }
  }
</style>

<script>
  function handleFacultyFileSelect(input) {
    const file = input.files[0];
    if (file) {
      displayFacultySelectedFile(file);
      validateAndPreviewFacultyFile(file);
    }
  }
  
  function displayFacultySelectedFile(file) {
    const selectedFileDiv = document.getElementById('facultySelectedFile');
    const fileNameSpan = document.getElementById('facultyFileName');
    const fileSizeSpan = document.getElementById('facultyFileSize');
    
    fileNameSpan.textContent = file.name;
    fileSizeSpan.textContent = formatFileSize(file.size);
    selectedFileDiv.style.display = 'block';
  }
  
  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
  
  function removeFacultySelectedFile() {
    document.getElementById('facultyFileInput').value = '';
    document.getElementById('facultySelectedFile').style.display = 'none';
    document.getElementById('facultyPreviewContainer').style.display = 'none';
    document.getElementById('facultyNoPreview').style.display = 'block';
  }
  
  function validateAndPreviewFacultyFile(file) {
    const validExtensions = ['.xlsx', '.xls', '.csv', '.json', '.xml'];
    const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
    
    if (!validExtensions.includes(fileExtension)) {
      showToastNotification('Please select a valid file format', 'error');
      return;
    }
    
    // Simulate file validation and preview
    generateFacultySamplePreview(file);
  }
  
  function generateFacultySamplePreview(file) {
    const previewContainer = document.getElementById('facultyPreviewContainer');
    const noPreview = document.getElementById('facultyNoPreview');
    const previewBody = document.getElementById('facultyPreviewBody');
    
    // Sample faculty data for preview
    const sampleData = [
      { employeeNumber: 'EMP001', employmentStatus: 'Full-time', lastName: 'Santos', firstName: 'Maria', email: 'maria.santos@example.com', contactNumber: '+63 912 345 6789' },
      { employeeNumber: 'EMP002', employmentStatus: 'Part-time', lastName: 'Dela Cruz', firstName: 'Juan', email: 'juan.delacruz@example.com', contactNumber: '+63 923 456 7890' },
      { employeeNumber: 'EMP003', employmentStatus: 'Contract', lastName: 'Rodriguez', firstName: 'Ana', email: 'ana.rodriguez@example.com', contactNumber: '+63 934 567 8901' }
    ];
    
    // Generate preview table
    previewBody.innerHTML = '';
    sampleData.forEach(row => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${row.employeeNumber}</td>
        <td>${row.employmentStatus}</td>
        <td>${row.lastName}</td>
        <td>${row.firstName}</td>
        <td>${row.email}</td>
        <td>${row.contactNumber}</td>
      `;
      previewBody.appendChild(tr);
    });
    
    previewContainer.style.display = 'block';
    noPreview.style.display = 'none';
  }
  
  function validateFacultyImportForm() {
    const fileInput = document.getElementById('facultyFileInput');
    const importMode = document.querySelector('input[name="importMode"]:checked');
    
    if (!fileInput.files[0]) {
      showToastNotification('Please select a file to import', 'error');
      return false;
    }
    
    if (!importMode) {
      showToastNotification('Please select an import mode', 'error');
      return false;
    }
    
    return true;
  }
  

  
  function setupFacultyDragAndDrop() {
    const uploadArea = document.getElementById('facultyFileUploadArea');
    const fileInput = document.getElementById('facultyFileInput');
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = '#5186b1';
      uploadArea.style.backgroundColor = 'rgba(81, 134, 177, 0.05)';
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = '';
      uploadArea.style.backgroundColor = '';
    });
    
    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = '';
      uploadArea.style.backgroundColor = '';
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        fileInput.files = files;
        handleFacultyFileSelect(fileInput);
      }
    });
  }
  
  // Make functions globally accessible
  window.openFacultyImportModal = function() {
    const modal = document.getElementById('facultyImportModal');
    modal.style.display = 'flex';
  };
  
  window.closeFacultyImportModal = function() {
    const modal = document.getElementById('facultyImportModal');
    modal.style.display = 'none';
    
    // Reset form
    document.getElementById('facultyImportForm').reset();
    removeFacultySelectedFile();
  };
  
  window.submitFacultyImportForm = function() {
    if (!validateFacultyImportForm()) {
      return;
    }
    
    const importMode = document.querySelector('input[name="importMode"]:checked').value;
    const fileName = document.getElementById('facultyFileName').textContent;
    
    let scopeText = '';
    switch (importMode) {
      case 'skip':
        scopeText = 'skip existing faculty';
        break;
      case 'update':
        scopeText = 'update existing faculty';
        break;
      case 'replace':
        scopeText = 'replace all faculty data';
        break;
    }
    
    showConfirmationModal(
      'Import Faculty Data',
      `Are you sure you want to import faculty data from "${fileName}" using ${scopeText} mode?`,
      'Import Data',
      'Cancel',
      () => {
        // Simulate import process
        showToastNotification('Importing faculty data...', 'info');
        
        setTimeout(() => {
          showToastNotification('Faculty data imported successfully!', 'success');
          window.closeFacultyImportModal();
        }, 2000);
      },
      'info'
    );
  };
  
  document.addEventListener('DOMContentLoaded', setupFacultyDragAndDrop);
</script> 