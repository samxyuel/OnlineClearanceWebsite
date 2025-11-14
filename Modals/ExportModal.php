<?php
// Dynamic Export Modal - Adaptive by User Role
require_once __DIR__ . '/../includes/classes/Auth.php';
$auth = class_exists('Auth') ? new Auth() : null;
$currentUser = $auth && $auth->isLoggedIn() ? $auth->getCurrentUser() : null;
$currentRoleName = $currentUser['role_name'] ?? 'Guest';
$currentUserId = $currentUser['user_id'] ?? null;
?>
<!-- Include Modal Styles -->
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay export-modal-overlay" id="exportModal">
  <div class="modal-window" style="max-width: 600px;">
    <!-- Close Button -->
    <button class="modal-close" onclick="closeExportModal()">&times;</button>
    
    <!-- Modal Header -->
    <div class="modal-header">
      <h2 class="modal-title">📊 Export Reports</h2>
      <div class="modal-supporting-text">Select period, report, and scope based on your role. Reports are exported as PDF.</div>
    </div>
    
    <!-- Content Area -->
    <div class="modal-content-area">
      <form id="exportForm" class="modal-form" data-endpoint="../../api/reports/export.php">
        <input type="hidden" id="currentRole" value="<?php echo htmlspecialchars($currentRoleName); ?>">
        <input type="hidden" id="currentUserId" value="<?php echo htmlspecialchars((string)$currentUserId); ?>">
        
        <!-- File Format Section - PDF Only (Excel formats commented out for future implementation) -->
        <div class="export-section" id="sectionFormat">
          <h3 class="section-title">📄 Export Format</h3>
          <div class="format-indicator" style="padding: 12px; background-color: var(--very-light-off-white, #f8f9fa); border-radius: 6px; border: 1px solid var(--light-blue-gray, #e0e0e0);">
            <span style="display: flex; align-items: center; gap: 8px; color: var(--deep-navy-blue, #1a1a2e); font-weight: 500;">
              <span>📄</span>
              <span>Reports will be exported as <strong>PDF (.pdf)</strong> format</span>
            </span>
          </div>
        </div>
        
        <!-- File Format Selection (Commented out for future implementation)
        <div class="export-section" id="sectionFormat">
          <h3 class="section-title">📄 File Format</h3>
          <div class="radio-group">
            <label class="radio-option" title="Generate an Excel workbook (.xlsx)">
              <input type="radio" name="fileFormat" value="xlsx" checked>
              <span class="radio-custom"></span>
              <span class="radio-label">Excel (.xlsx)</span>
            </label>
            <label class="radio-option" title="Generate a legacy Excel file (.xls)">
              <input type="radio" name="fileFormat" value="xls">
              <span class="radio-custom"></span>
              <span class="radio-label">Excel (.xls)</span>
            </label>
            <label class="radio-option" title="Generate a PDF document (.pdf)">
              <input type="radio" name="fileFormat" value="pdf">
              <span class="radio-custom"></span>
              <span class="radio-label">PDF (.pdf)</span>
            </label>
          </div>
        </div>
        -->
        
        <!-- Clearance Period -->
        <div class="export-section" id="sectionPeriod">
          <h3 class="section-title">🗓️ Clearance Period</h3>
          <div class="form-group">
            <label for="periodSelect">School Year and Term</label>
            <select id="periodSelect" name="period_id">
              <option value="">Loading periods...</option>
            </select>
          </div>
        </div>

        <!-- Report Type -->
        <div class="export-section" id="sectionReport">
          <h3 class="section-title">📑 Report Type</h3>
          <div class="form-group">
            <label for="reportType">Select Report</label>
            <select id="reportType" name="report_type" disabled>
              <option value="">Select a report</option>
            </select>
          </div>
          </div>
          
        <!-- Sector Scope -->
        <div class="export-section" id="sectionSector">
          <h3 class="section-title">🏷️ Sector Scope</h3>
          <div class="form-group">
            <label for="sectorSelect">Sector</label>
            <select id="sectorSelect" name="sector" disabled>
              <option value="">Select a sector</option>
            </select>
          </div>
          </div>
          
        <!-- Department Scope -->
        <div class="export-section" id="sectionDepartment" style="display: none;">
          <h3 class="section-title">🏛️ Department Scope</h3>
          <div class="form-group">
            <label for="departmentSelect">Department</label>
            <select id="departmentSelect" name="department_id" disabled>
              <option value="">Select a department</option>
            </select>
          </div>
        </div>
        
        <!-- Program Scope (only for College/SHS) -->
        <div class="export-section" id="sectionProgram" style="display: none;">
          <h3 class="section-title">🎓 Program Scope</h3>
          <div class="form-group">
            <label for="programSelect">Program</label>
            <select id="programSelect" name="program_id" disabled>
              <option value="">All Programs</option>
            </select>
          </div>
        </div>
        
        <!-- Export Options -->
        <div class="export-section" id="sectionOptions">
          <h3 class="section-title">⚙️ Export Options</h3>
          <div class="form-group">
            <label for="exportFileName">File Name</label>
            <input type="text" id="exportFileName" name="fileName" placeholder="report_export" value="" disabled>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Actions -->
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeExportModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitExportForm()" id="exportSubmitBtn">Export</button>
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

/* Disabled section styling and smooth transitions */
.export-section { transition: opacity 0.2s ease; }
.section-disabled { opacity: 0.5; pointer-events: none; filter: grayscale(15%); }

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
// Role-based report options
const REPORT_OPTIONS = {
  'Admin': [
    { value: 'student_progress', label: 'Student Clearance Form Progress Report' },
    { value: 'faculty_progress', label: 'Faculty Clearance Form Progress Report' }
  ],
  'School Administrator': [
    { value: 'student_progress', label: 'Student Clearance Form Progress Report' },
    { value: 'faculty_progress', label: 'Faculty Clearance Form Progress Report' },
    { value: 'student_applicant_status', label: 'Student Clearance Applicant Status Report' },
    { value: 'faculty_applicant_status', label: 'Faculty Clearance Applicant Status Report' }
  ],
  'Program Head': [
    { value: 'student_applicant_status', label: 'Student Clearance Applicant Status Report' },
    { value: 'faculty_applicant_status', label: 'Faculty Clearance Applicant Status Report' }
  ],
  'Regular Staff': [
    { value: 'student_applicant_status', label: 'Student Clearance Applicant Status Report' },
    { value: 'faculty_applicant_status', label: 'Faculty Clearance Applicant Status Report' }
  ]
};

let USER_ASSIGNMENTS = null; // populated from API for scope filtering

async function fetchJSON(url) {
  console.log('[ExportModal] fetchJSON() calling:', url);
  try {
    const resp = await fetch(url, { credentials: 'include' });
    console.log('[ExportModal] fetchJSON() response status:', resp.status, resp.statusText);
    console.log('[ExportModal] fetchJSON() response headers:', {
      'content-type': resp.headers.get('content-type'),
      'content-length': resp.headers.get('content-length')
    });
    
    if (!resp.ok) {
      const errorText = await resp.text();
      console.error('[ExportModal] fetchJSON() error response:', errorText);
      throw new Error(`HTTP ${resp.status}: ${resp.statusText} - ${errorText.substring(0, 200)}`);
    }
    
    const contentType = resp.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      const text = await resp.text();
      console.error('[ExportModal] fetchJSON() non-JSON response:', text.substring(0, 500));
      throw new Error('Response is not JSON. Content-Type: ' + contentType);
    }
    
    const json = await resp.json();
    console.log('[ExportModal] fetchJSON() parsed JSON:', json);
    return json;
  } catch (e) {
    console.error('[ExportModal] fetchJSON() exception:', e);
    throw e;
  }
}

function setLoading(selectEl, text = 'Loading...') {
  selectEl.innerHTML = '';
  const opt = document.createElement('option');
  opt.value = '';
  opt.textContent = text;
  selectEl.appendChild(opt);
}

function populateSelect(selectEl, items, valueKey, labelKey, placeholder = 'Select...') {
  selectEl.innerHTML = '';
  const ph = document.createElement('option');
  ph.value = '';
  ph.textContent = placeholder;
  selectEl.appendChild(ph);
  items.forEach(it => {
    const opt = document.createElement('option');
    opt.value = it[valueKey];
    opt.textContent = it[labelKey];
    selectEl.appendChild(opt);
  });
}

function normalizeRole(role) {
  if (!role) return 'Guest';
  const r = role.trim().toLowerCase();
  if (r === 'admin') return 'Admin';
  if (r === 'school administrator') return 'School Administrator';
  if (r === 'program head') return 'Program Head';
  if (r === 'regular staff') return 'Regular Staff';
  if (r === 'student') return 'Student';
  if (r === 'faculty') return 'Faculty';
  return role;
}

function shouldShowProgramSection(selectedSectorName) {
  return selectedSectorName === 'College' || selectedSectorName === 'Senior High School';
}

// Hierarchical helpers and export button gating
// Note: sectionFormat is always enabled (PDF only for now)
const SECTION_ORDER = ['sectionFormat','sectionPeriod','sectionReport','sectionSector','sectionDepartment','sectionProgram','sectionOptions'];

function setSectionEnabled(sectionId, enabled) {
  const el = document.getElementById(sectionId);
  if (!el) {
    console.warn('[ExportModal] setSectionEnabled: Element not found:', sectionId);
    return;
  }
  
  console.log('[ExportModal] setSectionEnabled:', sectionId, '->', enabled);
  
  el.classList.toggle('section-disabled', !enabled);
  el.querySelectorAll('select, input, button').forEach(ctrl => {
    // sectionFormat is always enabled (PDF only indicator, no interactive controls)
    if (sectionId === 'sectionFormat') { 
      // No controls to disable in format indicator
      return; 
    }
    const wasDisabled = ctrl.disabled;
    ctrl.disabled = !enabled;
    console.log('[ExportModal] Control', ctrl.id || ctrl.name, 'disabled:', !enabled, '(was:', wasDisabled, ')');
  });
}

function updateExportButtonState() {
  const btn = document.getElementById('exportSubmitBtn');
  const period = document.getElementById('periodSelect').value;
  const report = document.getElementById('reportType').value;
  const sector = document.getElementById('sectorSelect').value;
  const dept = document.getElementById('departmentSelect').value;
  const fname = document.getElementById('exportFileName').value.trim();
  btn.disabled = !(period && report && sector && dept && fname);
}

function computeDefaultFilename() {
  const reportSel = document.getElementById('reportType');
  const selected = reportSel.options[reportSel.selectedIndex];
  const label = (selected && selected.textContent) ? selected.textContent : 'Report';
  const base = label.replace(/\s+/g,'_').replace(/[^A-Za-z0-9_\-]/g,'_');
  const dateStr = new Date().toISOString().slice(0,10);
  return `${base}_${dateStr}`;
}

async function loadExportPeriods() {
  console.log('[ExportModal] loadExportPeriods() ENTRY POINT - function started');
  
  try {
    const sel = document.getElementById('periodSelect');
    console.log('[ExportModal] loadExportPeriods() - Select element found:', sel);
    
    if (!sel) {
      throw new Error('periodSelect element not found in DOM');
    }
    
    console.log('[ExportModal] loadExportPeriods() - Setting loading state');
    setLoading(sel);
    
    const apiUrl = '../../api/clearance/periods_for_export.php';
    console.log('[ExportModal] loadExportPeriods() - Fetching periods from:', apiUrl);
    
    const startTime = performance.now();
    const data = await fetchJSON(apiUrl);
    const duration = Math.round(performance.now() - startTime);
    
    console.log('[ExportModal] API Response received in', duration + 'ms');
    console.log('[ExportModal] Response data:', data);
    console.log('[ExportModal] Response type:', typeof data);
    console.log('[ExportModal] Has success:', 'success' in (data || {}));
    console.log('[ExportModal] Has periods:', 'periods' in (data || {}));
    console.log('[ExportModal] Periods is array:', Array.isArray(data?.periods));
    
    if (!data) {
      throw new Error('No data returned from API');
    }
    
    if (!data.success) {
      console.error('[ExportModal] API returned success=false:', data.message);
      throw new Error(data.message || 'API returned unsuccessful response');
    }
    
    if (!Array.isArray(data.periods)) {
      console.error('[ExportModal] Periods is not an array:', typeof data.periods, data.periods);
      throw new Error('Invalid response format: periods is not an array');
    }
    
    console.log('[ExportModal] Periods count:', data.periods.length);
    
    if (data.periods.length === 0) {
      console.warn('[ExportModal] No periods found');
      populateSelect(sel, [], 'value', 'label', 'No clearance periods available');
    } else {
      console.log('[ExportModal] Processing', data.periods.length, 'periods');
      const items = data.periods.map(p => {
        const item = {
          value: p.value || '',
          label: p.label || ''
        };
        console.log('[ExportModal] Period item:', item);
        return item;
      });
      
      console.log('[ExportModal] Populating select with', items.length, 'items');
      populateSelect(sel, items, 'value', 'label', 'Select School Year and Term');
      console.log('[ExportModal] Select populated successfully');
    }
    console.log('[ExportModal] loadExportPeriods() - Function completed successfully');
  } catch (e) {
    console.error('[ExportModal] loadExportPeriods() - ERROR CAUGHT:', e);
    console.error('[ExportModal] Error name:', e.name);
    console.error('[ExportModal] Error message:', e.message);
    console.error('[ExportModal] Error stack:', e.stack);
    
    const sel = document.getElementById('periodSelect');
    if (sel) {
      populateSelect(sel, [], 'value', 'label', 'Unable to load periods: ' + (e.message || 'Unknown error'));
    } else {
      console.error('[ExportModal] Could not find periodSelect element for error display');
    }
    
    // Re-throw so outer catch can handle it
    throw e;
  }
}

async function loadSectors(filteredNames = null) {
  const sel = document.getElementById('sectorSelect');
  console.log('[ExportModal] loadSectors() called, filteredNames:', filteredNames);
  setLoading(sel);
  try {
    const data = await fetchJSON('../../api/sectors/list.php');
    let sectors = data.sectors || [];
    if (filteredNames && Array.isArray(filteredNames)) {
      sectors = sectors.filter(s => filteredNames.includes(s.sector_name));
      console.log('[ExportModal] Filtered sectors:', sectors.map(s => s.sector_name));
    }
    populateSelect(sel, sectors.map(s => ({ value: s.sector_name, label: s.sector_name })), 'value', 'label', 'Select a sector');
    console.log('[ExportModal] Sectors populated:', sectors.length, 'items');
  } catch (e) {
    console.error('[ExportModal] Error loading sectors:', e);
    populateSelect(sel, [], 'value', 'label', 'Unable to load sectors');
  }
}

// Export modal specific function - use unique name to avoid conflicts
async function loadExportDepartments(sectorName, allowedDepartmentIds = null) {
  console.log('[ExportModal] loadExportDepartments() ENTRY - sector:', sectorName, 'allowedIds:', allowedDepartmentIds);
  
  const sel = document.getElementById('departmentSelect');
  if (!sel) {
    console.error('[ExportModal] Department select element not found!');
    return;
  }
  
  console.log('[ExportModal] Setting loading state for department select');
  setLoading(sel);
  
  document.getElementById('sectionDepartment').style.display = sectorName ? 'block' : 'none';
  if (!sectorName) {
    console.log('[ExportModal] No sector name, skipping department load');
    return;
  }
  
  const apiUrl = `../../api/departments/list.php?sector=${encodeURIComponent(sectorName)}`;
  console.log('[ExportModal] Calling API:', apiUrl);
  
  try {
    const data = await fetchJSON(apiUrl);
    console.log('[ExportModal] API response received:', data);
    console.log('[ExportModal] Data success:', data.success);
    console.log('[ExportModal] Departments in response:', Array.isArray(data.departments) ? data.departments.length : 'not an array');
    
    let departments = data.departments || [];
    console.log('[ExportModal] Departments before filtering:', departments.length);
    
    if (Array.isArray(allowedDepartmentIds) && allowedDepartmentIds.length > 0) {
      const set = new Set(allowedDepartmentIds.map(Number));
      const beforeFilter = departments.length;
      departments = departments.filter(d => set.has(Number(d.department_id)));
      console.log('[ExportModal] Filtered departments:', departments.length, 'after filtering (was:', beforeFilter, ')');
    } else {
      console.log('[ExportModal] No department ID filter applied');
    }
    
    console.log('[ExportModal] Populating select with', departments.length, 'departments');
    const items = departments.map(d => ({ value: d.department_id, label: d.department_name }));
    console.log('[ExportModal] Items to populate:', items);
    
    populateSelect(sel, items, 'value', 'label', 'Select a department');
    console.log('[ExportModal] Departments populated successfully');
  } catch (e) {
    console.error('[ExportModal] Error loading departments:', e);
    console.error('[ExportModal] Error stack:', e.stack);
    populateSelect(sel, [], 'value', 'label', 'Unable to load departments: ' + (e.message || 'Unknown error'));
  }
}

async function loadPrograms(departmentId) {
  const programSection = document.getElementById('sectionProgram');
  const sel = document.getElementById('programSelect');
  if (!departmentId) {
    programSection.style.display = 'none';
    return;
  }
  setLoading(sel);
  try {
    const data = await fetchJSON(`../../api/programs/list.php?department_id=${encodeURIComponent(departmentId)}`);
    const programs = data.programs || [];
    populateSelect(sel, programs.map(p => ({ value: p.program_id, label: p.program_name })), 'value', 'label', 'All Programs');
    programSection.style.display = 'block';
  } catch (e) {
    programSection.style.display = 'none';
  }
}

async function loadUserAssignments() {
  try {
    const data = await fetchJSON('../../api/signatories/check_user_status.php');
    USER_ASSIGNMENTS = data.signatory_status || null;
  } catch (e) {
    USER_ASSIGNMENTS = null;
  }
}

function buildReportTypeOptions(roleNorm) {
  const sel = document.getElementById('reportType');
  const options = REPORT_OPTIONS[roleNorm] || [];
  populateSelect(sel, options, 'value', 'label', 'Select a report');
}

function deriveAllowedScope(roleNorm) {
  const result = { sectors: null, departmentIds: null };
  if (!USER_ASSIGNMENTS || !USER_ASSIGNMENTS.assignments) return result;
  const assignments = USER_ASSIGNMENTS.assignments;

  if (roleNorm === 'School Administrator' || roleNorm === 'Regular Staff') {
    const sectors = new Set();
    const deptIds = new Set();
    Object.keys(assignments).forEach(ct => {
      assignments[ct].forEach(a => {
        if (a.sector_name) sectors.add(a.sector_name);
        if (a.department_id) deptIds.add(Number(a.department_id));
      });
    });
    result.sectors = Array.from(sectors);
    result.departmentIds = Array.from(deptIds);
  }

  if (roleNorm === 'Program Head') {
    const deptIds = new Set();
    const sectors = new Set();
    Object.keys(assignments).forEach(ct => {
      assignments[ct].forEach(a => {
        if (a.department_id) deptIds.add(Number(a.department_id));
        if (a.sector_name && (a.sector_name === 'College' || a.sector_name === 'Senior High School')) {
          sectors.add(a.sector_name);
        }
      });
    });
    result.sectors = Array.from(sectors).filter(s => s === 'College' || s === 'Senior High School');
    result.departmentIds = Array.from(deptIds);
  }

  return result;
}

function validateExportForm() {
  // Helper function for notifications
  const notify = (message, type = 'info') => {
    if (typeof showNotification === 'function') {
      showNotification(message, type);
    } else if (typeof showToastNotification === 'function') {
      showToastNotification(message, type);
    } else if (typeof showToast === 'function') {
      showToast(message, type);
    } else {
      alert(message);
    }
  };
  
  // File format is always PDF (commented out for future implementation)
  // const fileFormat = document.querySelector('input[name="fileFormat"]:checked')?.value;
  const periodId = document.getElementById('periodSelect').value;
  const reportType = document.getElementById('reportType').value;
  const sector = document.getElementById('sectorSelect').value;
  const deptId = document.getElementById('departmentSelect').value;
  const fileName = document.getElementById('exportFileName').value.trim();

  // File format validation removed - always PDF
  // if (!fileFormat) { notify('Please select a file format', 'error'); return false; }
  if (!periodId) { notify('Please select a clearance period', 'error'); return false; }
  if (!reportType) { notify('Please select a report type', 'error'); return false; }
  if (!sector) { notify('Please select a sector', 'error'); return false; }
  if (!deptId) { notify('Please select a department', 'error'); return false; }
  if (!fileName) { notify('Please enter a file name', 'error'); return false; }
  return true;
}

window.openExportModal = async function() {
  console.log('[ExportModal] openExportModal() called');
  document.getElementById('exportModal').style.display = 'flex';
  document.body.classList.add('modal-open');
  
  const now = new Date();
  const timestamp = now.toISOString().slice(0, 10);
  document.getElementById('exportFileName').value = '';

  const roleRaw = document.getElementById('currentRole')?.value || 'Guest';
  const roleNorm = normalizeRole(roleRaw);
  console.log('[ExportModal] Current role:', roleRaw, '-> normalized:', roleNorm);
  
  buildReportTypeOptions(roleNorm);
  console.log('[ExportModal] Report type options built');

  console.log('[ExportModal] Loading user assignments...');
  await loadUserAssignments();
  console.log('[ExportModal] User assignments loaded');
  
  console.log('[ExportModal] Loading periods...');
  try {
    await loadExportPeriods();
    console.log('[ExportModal] Periods loaded successfully');
  } catch (e) {
    console.error('[ExportModal] loadExportPeriods() threw an exception:', e);
    console.error('[ExportModal] Exception details:', e.message, e.stack);
  }
  console.log('[ExportModal] Periods loading attempt completed');

  // Load sectors after periods are loaded (initial load - will be filtered when report type is selected)
  // Don't load sectors here - wait for report type selection to filter appropriately
  console.log('[ExportModal] Sectors will be loaded when report type is selected');

  // Initialize hierarchical states
  // sectionFormat is always enabled (PDF indicator only, no controls to enable/disable)
  setSectionEnabled('sectionFormat', true);
  setSectionEnabled('sectionPeriod', true);
  setSectionEnabled('sectionReport', false);
  setSectionEnabled('sectionSector', false);
  document.getElementById('sectionDepartment').style.display = 'none';
  setSectionEnabled('sectionDepartment', false);
  document.getElementById('sectionProgram').style.display = 'none';
  setSectionEnabled('sectionProgram', false);
  setSectionEnabled('sectionOptions', false);
  const submitBtn = document.getElementById('exportSubmitBtn');
  submitBtn.disabled = true;

  // Period change → enable Report
  const periodSelect = document.getElementById('periodSelect');
  periodSelect.onchange = () => {
    console.log('[ExportModal] Period changed:', periodSelect.value);
    if (periodSelect.value) {
      setSectionEnabled('sectionReport', true);
      console.log('[ExportModal] Report section enabled');
    } else {
      setSectionEnabled('sectionReport', false);
      setSectionEnabled('sectionSector', false);
      document.getElementById('sectionDepartment').style.display = 'none';
      setSectionEnabled('sectionDepartment', false);
      document.getElementById('sectionProgram').style.display = 'none';
      setSectionEnabled('sectionProgram', false);
      setSectionEnabled('sectionOptions', false);
      console.log('[ExportModal] Report section disabled');
    }
    updateExportButtonState();
  };
  
  // Trigger period change if already selected
  if (periodSelect.value) {
    console.log('[ExportModal] Period already selected on init, triggering change');
    periodSelect.dispatchEvent(new Event('change'));
  }

  // Report change → enable Sector and filter sectors based on report type
  const reportSelect = document.getElementById('reportType');
  reportSelect.onchange = async () => {
    console.log('[ExportModal] Report changed:', reportSelect.value);
    if (reportSelect.value) {
      // Determine which sectors to show based on report type
      let sectorsToShow = null;
      if (reportSelect.value === 'student_progress' || reportSelect.value === 'student_applicant_status') {
        // Student reports: Show College and Senior High School only
        sectorsToShow = ['College', 'Senior High School'];
        console.log('[ExportModal] Student report selected, filtering sectors to:', sectorsToShow);
      } else if (reportSelect.value === 'faculty_progress' || reportSelect.value === 'faculty_applicant_status') {
        // Faculty reports: Show Faculty only
        sectorsToShow = ['Faculty'];
        console.log('[ExportModal] Faculty report selected, filtering sectors to:', sectorsToShow);
      }
      
      // Reload sectors with the appropriate filter
      await loadSectors(sectorsToShow);
      
      // Clear department/program/options since sector changed
      document.getElementById('departmentSelect').value = '';
      document.getElementById('programSelect').value = '';
      document.getElementById('sectionDepartment').style.display = 'none';
      setSectionEnabled('sectionDepartment', false);
      document.getElementById('sectionProgram').style.display = 'none';
      setSectionEnabled('sectionProgram', false);
      setSectionEnabled('sectionOptions', false);
      
      // Enable sector section
      setSectionEnabled('sectionSector', true);
      console.log('[ExportModal] Sector section enabled');
      
      const fname = document.getElementById('exportFileName');
      if (fname && !fname.value) fname.value = computeDefaultFilename();
    } else {
      setSectionEnabled('sectionSector', false);
      document.getElementById('sectionDepartment').style.display = 'none';
      setSectionEnabled('sectionDepartment', false);
      document.getElementById('sectionProgram').style.display = 'none';
      setSectionEnabled('sectionProgram', false);
      setSectionEnabled('sectionOptions', false);
      console.log('[ExportModal] Sector section disabled');
    }
    updateExportButtonState();
  };
  
  // Trigger report change if already selected
  if (reportSelect.value) {
    console.log('[ExportModal] Report already selected on init, triggering change');
    reportSelect.dispatchEvent(new Event('change'));
  }

  // Sector change → enable Department and load
  const sectorSelect = document.getElementById('sectorSelect');
  sectorSelect.onchange = async () => {
    console.log('[ExportModal] Sector changed:', sectorSelect.value);
    const selectedSector = sectorSelect.value;
    
    if (!selectedSector) {
      console.log('[ExportModal] No sector selected, disabling department section');
      setSectionEnabled('sectionDepartment', false);
      document.getElementById('sectionDepartment').style.display = 'none';
      document.getElementById('sectionProgram').style.display = 'none';
      setSectionEnabled('sectionProgram', false);
      setSectionEnabled('sectionOptions', false);
      updateExportButtonState();
      return;
    }
    
    console.log('[ExportModal] Calling loadDepartments for sector:', selectedSector);
    const roleN = normalizeRole(document.getElementById('currentRole')?.value);
    const scopeN = deriveAllowedScope(roleN);
    console.log('[ExportModal] Scope derived - departmentIds:', scopeN.departmentIds);
    
    try {
      await loadExportDepartments(selectedSector, scopeN.departmentIds);
      console.log('[ExportModal] loadExportDepartments completed');
    } catch (e) {
      console.error('[ExportModal] Error in loadExportDepartments call:', e);
      console.error('[ExportModal] Error details:', e.message, e.stack);
    }
    
    setSectionEnabled('sectionDepartment', true);
    document.getElementById('sectionDepartment').style.display = 'block';
    console.log('[ExportModal] Department section enabled');
    
    document.getElementById('sectionProgram').style.display = 'none';
    setSectionEnabled('sectionProgram', false);
    setSectionEnabled('sectionOptions', false);
    updateExportButtonState();
  };
  
  // Trigger sector change if already selected
  if (sectorSelect.value) {
    console.log('[ExportModal] Sector already selected on init, triggering change');
    sectorSelect.dispatchEvent(new Event('change'));
  }

  // Department change → optional Program, then enable Options
  const departmentSelect = document.getElementById('departmentSelect');
  departmentSelect.onchange = async () => {
    console.log('[ExportModal] Department changed:', departmentSelect.value);
    const selectedSector = sectorSelect.value;
    const deptId = departmentSelect.value;
    if (shouldShowProgramSection(selectedSector)) {
      await loadPrograms(deptId);
      document.getElementById('sectionProgram').style.display = 'block';
      setSectionEnabled('sectionProgram', true);
    } else {
      document.getElementById('sectionProgram').style.display = 'none';
      setSectionEnabled('sectionProgram', false);
    }
    if (deptId) {
      setSectionEnabled('sectionOptions', true);
      console.log('[ExportModal] Options section enabled');
      const fname = document.getElementById('exportFileName');
      if (fname && !fname.value) fname.value = computeDefaultFilename();
    } else {
      setSectionEnabled('sectionOptions', false);
    }
    updateExportButtonState();
  };
  
  // Trigger department change if already selected
  if (departmentSelect.value) {
    console.log('[ExportModal] Department already selected on init, triggering change');
    departmentSelect.dispatchEvent(new Event('change'));
  }

  // Program change → keep options enabled if department chosen
  const programSelect = document.getElementById('programSelect');
  programSelect.onchange = () => {
    const deptId = document.getElementById('departmentSelect').value;
    if (deptId) setSectionEnabled('sectionOptions', true);
    updateExportButtonState();
  };
  
  // Final check: Evaluate current state and enable sections accordingly
  console.log('[ExportModal] Final state check');
  console.log('[ExportModal] Period value:', periodSelect.value);
  console.log('[ExportModal] Report value:', reportSelect.value);
  console.log('[ExportModal] Sector value:', sectorSelect.value);
  console.log('[ExportModal] Department value:', departmentSelect.value);
  
  // Re-evaluate all sections based on current values
  if (periodSelect.value) {
    console.log('[ExportModal] Period has value, enabling Report section');
    setSectionEnabled('sectionReport', true);
    if (reportSelect.value) {
      console.log('[ExportModal] Report has value, triggering report change to load filtered sectors');
      // Trigger report change to load appropriate sectors
      reportSelect.dispatchEvent(new Event('change'));
    }
  }
  
  updateExportButtonState();
};

window.closeExportModal = function() {
  document.getElementById('exportModal').style.display = 'none';
  document.body.classList.remove('modal-open');
  document.getElementById('exportForm').reset();
  document.getElementById('sectionDepartment').style.display = 'none';
  document.getElementById('sectionProgram').style.display = 'none';
};

window.submitExportForm = async function() {
  // Helper function for notifications
  const notify = (message, type = 'info') => {
    if (typeof showNotification === 'function') {
      showNotification(message, type);
    } else if (typeof showToastNotification === 'function') {
      showToastNotification(message, type);
    } else if (typeof showToast === 'function') {
      showToast(message, type);
    } else {
      // Fallback to alert if no notification system is available
      alert(message);
    }
  };
  
  if (!validateExportForm()) return;
  const submitBtn = document.getElementById('exportSubmitBtn');
  const form = document.getElementById('exportForm');
  submitBtn.disabled = true;
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Exporting...';
  form.classList.add('modal-loading');
  
  try {
    const fd = new FormData(form);
    fd.append('role', normalizeRole(document.getElementById('currentRole')?.value));
    // Always use PDF format (commented out for future implementation)
    fd.append('fileFormat', 'pdf');
    // Encode selected clearance period (school_year | semester_name)
    const selectedPeriod = document.getElementById('periodSelect').value;
    if (selectedPeriod && selectedPeriod.includes('|')) {
      const [schoolYear, semesterName] = selectedPeriod.split('|');
      fd.append('school_year', schoolYear);
      fd.append('semester_name', semesterName);
    }

    console.log('[ExportModal] Submitting export form...');
    const resp = await fetch(form.getAttribute('data-endpoint'), { method: 'POST', body: fd, credentials: 'include' });
    
    // Check response status and content type
    const contentType = resp.headers.get('Content-Type') || '';
    console.log('[ExportModal] Response Content-Type:', contentType);
    console.log('[ExportModal] Response status:', resp.status, resp.statusText);
    
    if (!resp.ok) {
      // HTTP error - try to get error message
      let errorText = '';
      try {
        const errorJson = await resp.json();
        errorText = errorJson.message || JSON.stringify(errorJson);
        console.error('[ExportModal] JSON error response:', errorJson);
        
        // If there's a trace, log it for debugging
        if (errorJson.trace) {
          console.error('[ExportModal] Error trace:', errorJson.trace);
        }
      } catch (e) {
        errorText = await resp.text();
        console.error('[ExportModal] Error response (text):', errorText);
      }
      throw new Error('Export failed: ' + (errorText || resp.statusText));
    }
    
    // Check if response is JSON (error) instead of PDF/Excel
    if (contentType.includes('application/json')) {
      const errorJson = await resp.json();
      const errorMsg = errorJson.message || JSON.stringify(errorJson);
      console.error('[ExportModal] Server returned JSON error:', errorJson);
      if (errorJson.trace) {
        console.error('[ExportModal] Error trace:', errorJson.trace);
      }
      throw new Error('Export failed: ' + errorMsg);
    }
    
    const disp = resp.headers.get('Content-Disposition') || '';
    const blob = await resp.blob();
    
    console.log('[ExportModal] Export successful, blob size:', blob.size, 'bytes');
    
    if (blob.size === 0) {
      throw new Error('Exported file is empty. Please check the server logs for details.');
    }
    
    if (blob.size < 500) {
      // Very small file - might be an error message (like the 114 bytes we're seeing)
      // Check if it's JSON or HTML error
      const textPreview = await blob.slice(0, 200).text();
      if (textPreview.trim().startsWith('{') || textPreview.trim().startsWith('<')) {
        console.error('[ExportModal] Small file appears to be error response:', textPreview);
        // Re-read full blob as text to show the error
        const fullText = await blob.text();
        console.error('[ExportModal] Full error response:', fullText);
        throw new Error('Export failed: Server returned an error response instead of a file. Check browser console for details.');
      }
    }
    
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const match = /filename\s*=\s*([^;]+)/i.exec(disp);
    // Always use PDF extension (file format selection commented out for future implementation)
    const suggested = match ? match[1].replace(/\"/g,'').trim() : `${document.getElementById('exportFileName').value || computeDefaultFilename()}.pdf`;
    a.href = url; 
    a.download = suggested; 
    document.body.appendChild(a); 
    a.click(); 
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    closeExportModal();
    notify('Export generated successfully', 'success');
  } catch (e) {
    console.error('[ExportModal] Export error:', e);
    notify(e.message || 'Export failed', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    form.classList.remove('modal-loading');
  }
};

  document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeExportModal();
});
</script> 