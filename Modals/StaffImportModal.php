<?php
// Staff Import Modal
?>

<div class="modal-overlay staff-import-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeStaffImportModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Import Staff Data</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Import staff members from CSV or Excel file. Ensure your file has the correct column headers.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="staffImportForm" data-endpoint="../../controllers/importStaff.php">
                <input type="hidden" name="type" value="staff_import">
                
                <!-- File Upload Section -->
                <div class="import-section">
                    <h4><i class="fas fa-upload"></i> File Upload</h4>
                    <div class="form-group">
                        <label for="importFile">Select File</label>
                        <input type="file" id="importFile" name="importFile" accept=".csv,.xlsx,.xls" required>
                        <small class="form-help">Supported formats: CSV, Excel (.xlsx, .xls)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="importOptions">Import Options</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="skipDuplicates" name="skipDuplicates" checked>
                                Skip duplicate employee IDs
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="updateExisting" name="updateExisting">
                                Update existing records
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="validateData" name="validateData" checked>
                                Validate data before import
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Data Mapping Section -->
                <div class="import-section" id="mappingSection" style="display: none;">
                    <h4><i class="fas fa-columns"></i> Column Mapping</h4>
                    <div class="mapping-grid">
                        <div class="mapping-row">
                            <div class="mapping-field">Employee ID</div>
                            <div class="mapping-select">
                                <select id="mapEmployeeId" name="mapEmployeeId">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                        <div class="mapping-row">
                            <div class="mapping-field">Full Name</div>
                            <div class="mapping-select">
                                <select id="mapStaffName" name="mapStaffName">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                        <div class="mapping-row">
                            <div class="mapping-field">Position</div>
                            <div class="mapping-select">
                                <select id="mapPosition" name="mapPosition">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                        <div class="mapping-row">
                            <div class="mapping-field">Department</div>
                            <div class="mapping-select">
                                <select id="mapDepartment" name="mapDepartment">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                        <div class="mapping-row">
                            <div class="mapping-field">Email</div>
                            <div class="mapping-select">
                                <select id="mapEmail" name="mapEmail">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                        <div class="mapping-row">
                            <div class="mapping-field">Contact Number</div>
                            <div class="mapping-select">
                                <select id="mapContact" name="mapContact">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                        <div class="mapping-row">
                            <div class="mapping-field">Staff Status</div>
                            <div class="mapping-select">
                                <select id="mapStatus" name="mapStatus">
                                    <option value="">Select Column</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Section -->
                <div class="import-section" id="previewSection" style="display: none;">
                    <h4><i class="fas fa-eye"></i> Data Preview</h4>
                    <div class="preview-container">
                        <div class="preview-info">
                            <span id="previewCount">0</span> records found
                        </div>
                        <div class="preview-table-container">
                            <table class="preview-table" id="previewTable">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                    <!-- Preview data will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeStaffImportModal()">Cancel</button>
            <button class="modal-action-secondary" onclick="previewImportData()" id="previewBtn" style="display: none;">Preview Data</button>
            <button class="modal-action-primary" onclick="submitStaffImportForm()" id="importBtn" style="display: none;">Import Staff</button>
        </div>
    </div>
</div>

<script>
// Make functions globally accessible
window.closeStaffImportModal = function() {
    const modal = document.querySelector('.staff-import-modal-overlay');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        // Reset form
        document.getElementById('staffImportForm').reset();
        document.getElementById('mappingSection').style.display = 'none';
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('previewBtn').style.display = 'none';
        document.getElementById('importBtn').style.display = 'none';
    }
};

window.previewImportData = function() {
    const fileInput = document.getElementById('importFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showToast('Please select a file first.', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'preview');
    
    // Show loading state
    document.getElementById('previewBtn').textContent = 'Processing...';
    document.getElementById('previewBtn').disabled = true;
    
    fetch('../../controllers/importStaff.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Populate column mapping
            populateColumnMapping(data.columns);
            document.getElementById('mappingSection').style.display = 'block';
            document.getElementById('previewBtn').style.display = 'inline-block';
            document.getElementById('previewBtn').textContent = 'Preview Data';
            document.getElementById('previewBtn').disabled = false;
        } else {
            showToast(data.message || 'Failed to process file.', 'error');
            document.getElementById('previewBtn').textContent = 'Preview Data';
            document.getElementById('previewBtn').disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while processing the file.', 'error');
        document.getElementById('previewBtn').textContent = 'Preview Data';
        document.getElementById('previewBtn').disabled = false;
    });
};

window.submitStaffImportForm = function() {
    const form = document.getElementById('staffImportForm');
    const formData = new FormData(form);
    
    // Add mapping data
    const mapping = {
        employeeId: document.getElementById('mapEmployeeId').value,
        staffName: document.getElementById('mapStaffName').value,
        position: document.getElementById('mapPosition').value,
        department: document.getElementById('mapDepartment').value,
        email: document.getElementById('mapEmail').value,
        contact: document.getElementById('mapContact').value,
        status: document.getElementById('mapStatus').value
    };
    
    formData.append('mapping', JSON.stringify(mapping));
    
    // Show loading state
    document.getElementById('importBtn').textContent = 'Importing...';
    document.getElementById('importBtn').disabled = true;
    
    fetch(form.dataset.endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast(`Successfully imported ${data.imported} staff members!`, 'success');
            closeStaffImportModal();
            // Optionally reload the page
            location.reload();
        } else {
            showToast(data.message || 'Failed to import staff data.', 'error');
        }
        document.getElementById('importBtn').textContent = 'Import Staff';
        document.getElementById('importBtn').disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while importing staff data.', 'error');
        document.getElementById('importBtn').textContent = 'Import Staff';
        document.getElementById('importBtn').disabled = false;
    });
};

function populateColumnMapping(columns) {
    const selects = [
        'mapEmployeeId', 'mapStaffName', 'mapPosition', 
        'mapDepartment', 'mapEmail', 'mapContact', 'mapStatus'
    ];
    
    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        select.innerHTML = '<option value="">Select Column</option>';
        
        columns.forEach(column => {
            const option = document.createElement('option');
            option.value = column;
            option.textContent = column;
            select.appendChild(option);
        });
    });
}

// File input change handler
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('importFile');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('previewBtn').style.display = 'inline-block';
            } else {
                document.getElementById('previewBtn').style.display = 'none';
                document.getElementById('importBtn').style.display = 'none';
            }
        });
    }
});
</script> 