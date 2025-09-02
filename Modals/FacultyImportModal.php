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
            <label class="checkbox-label policy-label">
              <span>Import policy:</span>
              <select name="importPolicy" style="margin-left: 8px;">
                <option value="partial" selected>Partial (skip conflicts)</option>
                <option value="strict">Strict (all-or-nothing)</option>
              </select>
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
                    <th>Action</th>
                    <th>Issues</th>
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
  
  /* Import Policy styling */
  .policy-label {
    justify-content: space-between;
    gap: 12px;
  }
  .policy-label span {
    font-weight: 600;
    color: var(--deep-navy-blue);
  }
  .policy-label select {
    padding: 8px 12px;
    border: 1px solid var(--light-blue-gray);
    border-radius: 6px;
    background: #fff;
    font-size: 0.9rem;
    color: var(--deep-navy-blue);
  }
  @media (max-width: 768px) {
    .policy-label {
      flex-direction: column;
      align-items: flex-start;
    }
    .policy-label select {
      width: 100%;
      margin-left: 0 !important;
    }
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

  /* Results modal styles */
  .results-summary {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin: 10px 0 16px 0;
  }
  .results-pill {
    background: var(--very-light-off-white);
    border: 1px solid var(--light-blue-gray);
    border-radius: 20px;
    padding: 6px 12px;
    font-size: 0.85rem;
    color: var(--deep-navy-blue);
  }
  .results-table-wrapper {
    max-height: 260px;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid var(--light-blue-gray);
    border-radius: 8px;
    background: #fff;
  }
  .results-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
  }
  .results-table th, .results-table td {
    padding: 8px 12px;
    border-bottom: 1px solid var(--light-blue-gray);
    text-align: left;
    font-size: 0.86rem;
  }
  .results-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
  }
  .download-btn {
    background: var(--medium-muted-blue);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
  }
  .download-btn:hover { background: var(--darker-saturated-blue); }

  /* Responsive tweaks for results modal */
  @media (max-width: 768px) {
    #importResultsModal .modal-window {
      max-width: 95% !important;
      width: 95%;
      margin: 12px;
    }
    #importResultsModal .modal-content-area {
      padding: 12px;
    }
    .results-summary { gap: 8px; }
    .results-table-wrapper { max-height: 50vh; }
    .results-table { min-width: 600px; }
    .results-table th, .results-table td { padding: 8px 10px; }
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
    
    const form = document.getElementById('facultyImportForm');
    const formData = new FormData();
    formData.append('type', 'faculty_import');
    formData.append('importMode', (form.querySelector('input[name="importMode"]:checked')?.value) || 'skip');
    formData.append('validateData', 'on');
    formData.append('validateOnly', '1');
    formData.append('importFile', file);
    const policySel = document.querySelector('select[name="importPolicy"]');
    if (policySel) formData.append('importPolicy', policySel.value || 'partial');
    
    const submitBtn = document.getElementById('facultyImportSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validating...';
    submitBtn.disabled = true;
    
    fetch('../../controllers/importData.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(res => {
        if (!res.success) { throw new Error(res.message || 'Validation failed'); }
        window.facultyValidationSummary = res.summary || {};
        window.facultyValidationRows = res.rows || [];
        renderFacultyPreview(window.facultyValidationRows, window.facultyValidationSummary);
        const hasErrors = Array.isArray(res.summary?.errors) && res.summary.errors.length > 0;
        if (hasErrors) {
          showToastNotification(`Validation found ${res.summary.errors.length} issue(s). Fix or remove invalid rows to proceed.`, 'warning');
        } else {
          showToastNotification('Validation passed. Ready to import.', 'success');
        }
      })
      .catch(err => {
        console.error(err);
        showToastNotification(err.message || 'Validation error', 'error');
      })
      .finally(() => {
        submitBtn.innerHTML = originalText;
        const hasErrors = Array.isArray(window.facultyValidationSummary?.errors) && window.facultyValidationSummary.errors.length > 0;
        submitBtn.disabled = !!hasErrors;
      });
  }

  function renderFacultyPreview(rows, summary){
    const previewContainer = document.getElementById('facultyPreviewContainer');
    const noPreview = document.getElementById('facultyNoPreview');
    const previewBody = document.getElementById('facultyPreviewBody');
    const maxRows = 100;
    
    previewBody.innerHTML = '';
    const toRender = rows.slice(0, maxRows);
    toRender.forEach(r => {
      const tr = document.createElement('tr');
      const issues = (r.issues && r.issues.length) ? r.issues.join('; ') : '';
      const actionLabel = (r.action === 'create') ? 'Create' : (r.action === 'update') ? 'Update' : 'Error';
      tr.innerHTML = `
        <td>${r.employee_number || ''}</td>
        <td>${r.employment_status || ''}</td>
        <td>${r.last_name || ''}</td>
        <td>${r.first_name || ''}</td>
        <td>${r.email || ''}</td>
        <td>${r.contact_number || ''}</td>
        <td>${actionLabel}</td>
        <td>${issues}</td>
      `;
      previewBody.appendChild(tr);
    });
    
    previewContainer.style.display = 'block';
    noPreview.style.display = 'none';
    
    // Optional: display a small note if truncated
    if (rows.length > maxRows) {
      showToastNotification(`Showing first ${maxRows} of ${rows.length} rows (+${rows.length - maxRows} more)`, 'info');
    }
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
    // Block import if validation exists and has errors
    if (window.facultyValidationSummary && Array.isArray(window.facultyValidationSummary.errors) && window.facultyValidationSummary.errors.length > 0) {
      showToastNotification('Cannot import while there are validation errors. Fix them or re-upload a corrected file.', 'error');
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
        // Perform actual import
        performFacultyImport();
      },
      'info'
    );
  };
  
  function performFacultyImport() {
    const form = document.getElementById('facultyImportForm');
    const formData = new FormData(form);
    // Include policy explicitly
    const policySel = document.querySelector('select[name="importPolicy"]');
    if (policySel && !formData.get('importPolicy')) {
      formData.append('importPolicy', policySel.value || 'partial');
    }
    
    // Show loading state
    const submitBtn = document.getElementById('facultyImportSubmitBtn');
    let originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
    submitBtn.disabled = true;
    
    // Show progress notification
    showToastNotification('Importing faculty data...', 'info');
    
    fetch('../../controllers/importData.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message with summary
        const summary = data.summary;
        // Optional: show per-row outcomes if returned
        if (Array.isArray(data.rows)) { console.table(data.rows); renderImportResults(data.rows, summary); }
        const message = `Import completed successfully!\n\n` +
                       `Total records: ${summary.total}\n` +
                       `Imported: ${summary.imported}\n` +
                       `Updated: ${summary.updated}\n` +
                       `Skipped: ${summary.skipped}`;
        
        if (summary.errors && summary.errors.length > 0) {
          message += `\n\nErrors: ${summary.errors.length}`;
        }
        
        showToastNotification(message, 'success');
        // Add view details link
        try { openImportResultsModal(); } catch(e) { console.warn('results modal open failed', e); }
        
        // Close modal and refresh faculty list
        setTimeout(() => {
          window.closeFacultyImportModal();
          // Refresh the faculty table if refresh function exists
          if (typeof refreshFacultyTable === 'function') {
            refreshFacultyTable();
          } else {
            // Fallback: reload the page
            location.reload();
          }
        }, 2000);
        
      } else {
        // If backend returns detailed summary with errors
        if (data.summary && Array.isArray(data.summary.errors) && data.summary.errors.length) {
          console.table(data.summary.errors);
        }
        if (Array.isArray(data.rows)) { console.table(data.rows); renderImportResults(data.rows, data.summary || {}); try { openImportResultsModal(); } catch(e){} }
        showToastNotification('Import failed: ' + data.message, 'error');
      }
    })
    .catch(error => {
      console.error('Import error:', error);
      showToastNotification('Import failed: Network error', 'error');
    })
    .finally(() => {
      // Restore button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
  }
  
  document.addEventListener('DOMContentLoaded', setupFacultyDragAndDrop);
</script> 

<!-- Import Results Modal -->
<div class="modal-overlay" id="importResultsModal" style="display: none;">
  <div class="modal-window" style="max-width: 850px;">
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-list-alt"></i> Import Results</h3>
      <button class="modal-close" onclick="closeImportResultsModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-content-area">
      <div class="results-summary" id="importResultsSummary"></div>
      <div class="results-table-wrapper">
        <table class="results-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Employee Number</th>
              <th>Name</th>
              <th>Action</th>
              <th>Reason</th>
            </tr>
          </thead>
          <tbody id="importResultsBody"></tbody>
        </table>
      </div>
      <div class="results-actions">
        <button class="download-btn" onclick="downloadImportReportCSV()"><i class="fas fa-download"></i> Download CSV report</button>
        <div></div>
      </div>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeImportResultsModal()">Close</button>
    </div>
  </div>
  
  <script>
    window._lastImportReport = { rows: [], summary: {} };

    function openImportResultsModal() {
      const m = document.getElementById('importResultsModal');
      if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }
    function closeImportResultsModal() {
      const m = document.getElementById('importResultsModal');
      if (m) { m.style.display = 'none'; document.body.style.overflow = 'auto'; }
      // Clear report UI (no persistence)
      const body = document.getElementById('importResultsBody');
      const summary = document.getElementById('importResultsSummary');
      if (body) body.innerHTML = '';
      if (summary) summary.innerHTML = '';
      window._lastImportReport = { rows: [], summary: {} };
    }
    function renderImportResults(rows, summary) {
      window._lastImportReport = { rows: rows || [], summary: summary || {} };
      const body = document.getElementById('importResultsBody');
      const summaryDiv = document.getElementById('importResultsSummary');
      if (!body || !summaryDiv) return;
      body.innerHTML = '';
      summaryDiv.innerHTML = '';
      // Summary pills
      const pills = [
        { label: 'Total', value: summary.total },
        { label: 'Imported', value: summary.imported },
        { label: 'Updated', value: summary.updated },
        { label: 'Skipped', value: summary.skipped },
        { label: 'Errors', value: (summary.errors || []).length }
      ];
      pills.forEach(p => {
        const span = document.createElement('span');
        span.className = 'results-pill';
        span.textContent = `${p.label}: ${p.value ?? 0}`;
        summaryDiv.appendChild(span);
      });
      // Rows
      (rows || []).forEach(r => {
        const tr = document.createElement('tr');
        const name = buildName(r);
        tr.innerHTML = `
          <td>${r.rowNumber ?? ''}</td>
          <td>${r.employee_number ?? ''}</td>
          <td>${name}</td>
          <td>${(r.action || '').toUpperCase()}</td>
          <td>${r.reason || (Array.isArray(r.issues) ? r.issues.join('; ') : '') || ''}</td>
        `;
        body.appendChild(tr);
      });
    }
    function buildName(r) {
      const ln = (r.last_name || '').trim();
      const fn = (r.first_name || '').trim();
      const mn = (r.middle_name || '').trim();
      return ln && fn ? `${ln}, ${fn}${mn ? ' ' + mn : ''}` : (ln || fn || '');
    }
    function downloadImportReportCSV() {
      const { rows, summary } = window._lastImportReport || { rows: [], summary: {} };
      const headers = ['rowNumber','employee_number','last_name','first_name','middle_name','action','reason'];
      const lines = [headers.join(',')];
      (rows || []).forEach(r => {
        const reason = r.reason || (Array.isArray(r.issues) ? r.issues.join('; ') : '') || '';
        const vals = [
          r.rowNumber ?? '',
          r.employee_number ?? '',
          (r.last_name ?? '').replaceAll(',', ' '),
          (r.first_name ?? '').replaceAll(',', ' '),
          (r.middle_name ?? '').replaceAll(',', ' '),
          (r.action ?? '').toUpperCase(),
          (reason || '').replaceAll('\n',' ').replaceAll(',', ' ')
        ];
        lines.push(vals.join(','));
      });
      const csv = lines.join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      const pad = n => String(n).padStart(2,'0');
      const d = new Date();
      const fname = `faculty_import_report_${d.getFullYear()}${pad(d.getMonth()+1)}${pad(d.getDate())}_${pad(d.getHours())}${pad(d.getMinutes())}.csv`;
      a.href = url; a.download = fname; document.body.appendChild(a); a.click(); document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }
  </script>
</div>