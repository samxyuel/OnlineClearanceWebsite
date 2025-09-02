<?php // Faculty Export Modal - Export Faculty Data ?>
<link rel="stylesheet" href="../../assets/css/modals.css">
<div class="modal-overlay faculty-export-modal-overlay" id="facultyExportModal">
  <div class="modal-window" style="max-width: 600px;">
    <button class="modal-close" onclick="closeFacultyExportModal()">&times;</button>
    <h2 class="modal-title">📊 Export Faculty Data</h2>
    <div class="modal-supporting-text">Select export format, scope, and data to include in your export.</div>
    <div class="modal-content-area">
      <form id="facultyExportForm" class="modal-form" data-endpoint="../../controllers/exportData.php">
        <input type="hidden" name="type" value="faculty_export">
        
        <div class="export-section">
          <h3 class="section-title">📄 File Format</h3>
          <div class="radio-group">
            <label class="radio-label">
              <input type="radio" name="exportFormat" value="excel" checked>
              <span class="radio-custom"></span>
              <i class="fas fa-file-excel"></i> Excel (.xlsx)
            </label>
            <label class="radio-label">
              <input type="radio" name="exportFormat" value="csv">
              <span class="radio-custom"></span>
              <i class="fas fa-file-csv"></i> CSV (.csv)
            </label>
            <label class="radio-label">
              <input type="radio" name="exportFormat" value="pdf">
              <span class="radio-custom"></span>
              <i class="fas fa-file-pdf"></i> PDF (.pdf)
            </label>
            <label class="radio-label">
              <input type="radio" name="exportFormat" value="json">
              <span class="radio-custom"></span>
              <i class="fas fa-file-code"></i> JSON (.json)
            </label>
          </div>
        </div>
        
        <div class="export-section">
          <h3 class="section-title">🏫 Export Scope</h3>
          <div class="radio-group">
            <label class="radio-label">
              <input type="radio" name="exportScope" value="all" checked>
              <span class="radio-custom"></span>
              All Faculty
            </label>
            <label class="radio-label">
              <input type="radio" name="exportScope" value="filtered">
              <span class="radio-custom"></span>
              Filtered Faculty (based on current filters)
            </label>
            <label class="radio-label">
              <input type="radio" name="exportScope" value="selected">
              <span class="radio-custom"></span>
              Selected Faculty Only
            </label>
          </div>
        </div>
        
        <div class="export-section" id="facultyFilterSection" style="display: none;">
          <h3 class="section-title">🎓 Filter Selection</h3>
          <div class="form-group">
            <label for="exportEmploymentStatus">Employment Status</label>
            <select id="exportEmploymentStatus" name="employmentStatus">
              <option value="">All Employment Status</option>
              <option value="full-time">Full-time</option>
              <option value="part-time">Part-time</option>
              <option value="contract">Contract</option>
            </select>
          </div>
          <div class="form-group">
            <label for="exportAccountStatus">Account Status</label>
            <select id="exportAccountStatus" name="accountStatus">
              <option value="">All Account Status</option>
              <option value="active">Active Only</option>
              <option value="inactive">Inactive Only</option>
              <option value="resigned">Resigned Only</option>
            </select>
          </div>
          <div class="form-group">
            <label for="exportClearanceStatus">Clearance Status</label>
            <select id="exportClearanceStatus" name="clearanceStatus">
              <option value="">All Clearance Status</option>
              <option value="unapplied">Unapplied</option>
              <option value="pending">Pending</option>
              <option value="in-progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
        
        <div class="export-section">
          <h3 class="section-title">📋 Include Columns</h3>
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" name="includeEmployeeNumber" checked>
              <span class="checkmark"></span>
              Employee Number
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeEmploymentStatus" checked>
              <span class="checkmark"></span>
              Employment Status
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeName" checked>
              <span class="checkmark"></span>
              Full Name
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeEmail" checked>
              <span class="checkmark"></span>
              Email Address
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeContactNumber" checked>
              <span class="checkmark"></span>
              Contact Number
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeAccountStatus" checked>
              <span class="checkmark"></span>
              Account Status
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeClearanceStatus" checked>
              <span class="checkmark"></span>
              Clearance Status
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeSchoolTerm">
              <span class="checkmark"></span>
              School Term
            </label>
          </div>
        </div>
        
        <div class="export-section">
          <h3 class="section-title">⚙️ Export Options</h3>
          <div class="form-group">
            <label for="exportFileName">File Name</label>
            <input type="text" id="exportFileName" name="fileName" placeholder="faculty_data_export" value="faculty_data_export">
          </div>
          <div class="form-group" id="pdfLayoutSection" style="display: none;">
            <label for="pdfPageSize">Layout Size (PDF)</label>
            <select id="pdfPageSize" name="pdfPageSize">
              <option value="A4" selected>A4 (210 × 297 mm)</option>
              <option value="LETTER">Letter (8.5 × 11 in)</option>
              <option value="LEGAL">Legal (8.5 × 14 in)</option>
              <option value="A5">A5 (148 × 210 mm)</option>
              <option value="A3">A3 (297 × 420 mm)</option>
            </select>
          </div>
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" name="includeHeaders" checked>
              <span class="checkmark"></span>
              Include column headers
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeTimestamp">
              <span class="checkmark"></span>
              Include export timestamp
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="includeSummary">
              <span class="checkmark"></span>
              Include summary statistics
            </label>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeFacultyExportModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitFacultyExportForm()" id="facultyExportSubmitBtn">Export Faculty Data</button>
    </div>
  </div>
</div>

<style>
  .export-section {
    margin-bottom: 25px;
  }
  
  .section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--deep-navy-blue);
    margin-bottom: 12px;
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
  
  .radio-label i, .checkbox-label i {
    color: var(--medium-muted-blue);
    width: 16px;
    text-align: center;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
  
  .form-group label {
    display: block;
    font-weight: 600;
    color: var(--deep-navy-blue);
    margin-bottom: 5px;
    font-size: 0.9rem;
  }
  
  .form-group input, .form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--light-blue-gray);
    border-radius: 6px;
    background: white;
    font-size: 0.9rem;
  }
  
  .form-group input:focus, .form-group select:focus {
    outline: none;
    border-color: var(--medium-muted-blue);
    box-shadow: 0 0 0 2px rgba(81, 134, 177, 0.1);
  }
  
  @media (max-width: 768px) {
    .radio-group, .checkbox-group {
      gap: 8px;
    }
    
    .radio-label, .checkbox-label {
      padding: 6px;
      font-size: 0.9rem;
    }
  }
</style>

<script>
  function updateFacultyExportScope() {
    const exportScope = document.querySelector('input[name="exportScope"]:checked').value;
    const filterSection = document.getElementById('facultyFilterSection');
    
    if (exportScope === 'filtered') {
      filterSection.style.display = 'block';
    } else {
      filterSection.style.display = 'none';
    }
  }
  
  function validateFacultyExportForm() {
    const exportFormat = document.querySelector('input[name="exportFormat"]:checked');
    const exportScope = document.querySelector('input[name="exportScope"]:checked');
    const fileName = document.getElementById('exportFileName').value.trim();
    
    if (!exportFormat) {
      showToastNotification('Please select an export format', 'error');
      return false;
    }
    
    if (!exportScope) {
      showToastNotification('Please select an export scope', 'error');
      return false;
    }
    
    if (!fileName) {
      showToastNotification('Please enter a file name', 'error');
      return false;
    }
    
    // Check if at least one column is selected
    const selectedColumns = document.querySelectorAll('input[name^="include"]:checked');
    if (selectedColumns.length === 0) {
      showToastNotification('Please select at least one column to export', 'error');
      return false;
    }
    
    return true;
  }
  
  function updatePdfOptionsVisibility() {
    const format = document.querySelector('input[name="exportFormat"]:checked')?.value;
    const pdfSection = document.getElementById('pdfLayoutSection');
    if (pdfSection) {
      pdfSection.style.display = (format === 'pdf') ? 'block' : 'none';
    }
  }


  
  // Make functions globally accessible
  window.openFacultyExportModal = function() {
    const modal = document.getElementById('facultyExportModal');
    modal.style.display = 'flex';
    
    // Add event listeners for radio buttons
    const scopeRadios = document.querySelectorAll('input[name="exportScope"]');
    scopeRadios.forEach(radio => {
      radio.addEventListener('change', updateFacultyExportScope);
    });

    const formatRadios = document.querySelectorAll('input[name="exportFormat"]');
    formatRadios.forEach(r => r.addEventListener('change', updatePdfOptionsVisibility));
    updatePdfOptionsVisibility();
  };
  
  window.closeFacultyExportModal = function() {
    const modal = document.getElementById('facultyExportModal');
    modal.style.display = 'none';
    
    // Reset form
    document.getElementById('facultyExportForm').reset();
    document.getElementById('exportFileName').value = 'faculty_data_export';
    document.getElementById('facultyFilterSection').style.display = 'none';
  };
  
  window.submitFacultyExportForm = function() {
    if (!validateFacultyExportForm()) return;

    const exportFormat = document.querySelector('input[name="exportFormat"]:checked').value;
    const exportScope = document.querySelector('input[name="exportScope"]:checked').value;
    const fileName = document.getElementById('exportFileName').value.trim();

    // Build payload
    const form = document.getElementById('facultyExportForm');
    const fd = new FormData(form);

    // Column order: capture checked include checkboxes in DOM order
    const keyMap = {
      includeEmployeeNumber: 'employee_number',
      includeEmploymentStatus: 'employment_status',
      includeName: 'name',
      includeEmail: 'email',
      includeContactNumber: 'contact_number',
      includeAccountStatus: 'account_status',
      includeClearanceStatus: 'clearance_status',
      includeSchoolTerm: 'school_term'
    };
    const includeBoxes = document.querySelectorAll('input[type="checkbox"][name^="include"]');
    includeBoxes.forEach(cb => {
      if (cb.checked) {
        const key = keyMap[cb.name];
        if (key) fd.append('columns[]', key);
      }
    });

    // Include page context for filtered scope
    if (exportScope === 'filtered') {
      fd.append('employmentStatus', (document.getElementById('exportEmploymentStatus')?.value) || '');
      fd.append('accountStatus', (document.getElementById('exportAccountStatus')?.value) || '');
      fd.append('clearanceStatus', (document.getElementById('exportClearanceStatus')?.value) || '');
      // current tab status if any
      if (window.currentTabStatus) fd.append('tabStatus', window.currentTabStatus);
    }

    // Selected scope: collect selected employee_numbers from table
    if (exportScope === 'selected') {
      const selected = Array.from(document.querySelectorAll('.faculty-checkbox:checked')).map(cb=>cb.getAttribute('data-id'));
      if (selected.length === 0) {
        showToastNotification('No faculty selected', 'warning');
        return;
      }
      fd.append('selected', selected.join(','));
    }

    // Column includes
    // (already captured by form inputs)

    // Kick off download
    showToastNotification('Preparing faculty data export...', 'info');
    fetch('../../controllers/exportData.php', { method:'POST', body: fd, credentials:'include' })
      .then(async resp => {
        if (!resp.ok) throw new Error('Export failed');
        const disp = resp.headers.get('Content-Disposition') || '';
        const blob = await resp.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        // Try to parse filename from header; fallback
        const match = /filename\s*=\s*([^;]+)/i.exec(disp);
        const suggested = match ? match[1].replace(/\"/g,'').trim() : `${fileName}.${exportFormat}`;
        a.href = url; a.download = suggested; document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
        window.closeFacultyExportModal();
        showToastNotification('Faculty data exported successfully!', 'success');
      })
      .catch(err => {
        console.error(err);
        showToastNotification(err.message || 'Export failed', 'error');
      });
  };
  
  // Add event listeners
  document.addEventListener('DOMContentLoaded', function() {
    const scopeRadios = document.querySelectorAll('input[name="exportScope"]');
    scopeRadios.forEach(radio => {
      radio.addEventListener('change', updateFacultyExportScope);
    });

    const formatRadios = document.querySelectorAll('input[name="exportFormat"]');
    formatRadios.forEach(r => r.addEventListener('change', updatePdfOptionsVisibility));
    updatePdfOptionsVisibility();
  });
</script> 