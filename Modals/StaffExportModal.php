<?php
// Staff Export Modal
?>

<div class="modal-overlay staff-export-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeStaffExportModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Export Staff Data</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Export staff member data for the current school year or specific criteria.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="staffExportForm" data-endpoint="../../controllers/exportStaff.php">
                <input type="hidden" name="type" value="staff_export">
                
                <!-- Export Options Section -->
                <div class="export-section">
                    <h4><i class="fas fa-file-export"></i> Export Options</h4>
                    
                    <div class="form-group">
                        <label for="exportFormat">File Format</label>
                        <select id="exportFormat" name="exportFormat" required>
                            <option value="">Select Format</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="xlsx">Excel (.xlsx)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exportScope">Export Scope</label>
                        <select id="exportScope" name="exportScope" required>
                            <option value="">Select Scope</option>
                            <option value="all">All Staff Members</option>
                            <option value="essential">Essential Staff Only</option>
                            <option value="optional">Optional Staff Only</option>
                            <option value="active">Active Staff Only</option>
                            <option value="position">By Position</option>
                            <option value="department">By Department</option>
                        </select>
                    </div>
                    
                    <!-- Conditional fields based on scope -->
                    <div class="form-group" id="positionFilter" style="display: none;">
                        <label for="exportPosition">Position</label>
                        <select id="exportPosition" name="exportPosition">
                            <option value="">Select Position</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Program Head">Program Head</option>
                            <option value="School Administrator">School Administrator</option>
                            <option value="Guidance Counselor">Guidance Counselor</option>
                            <option value="Accountant">Accountant</option>
                            <option value="Librarian">Librarian</option>
                            <option value="IT Support">IT Support</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="departmentFilter" style="display: none;">
                        <label for="exportDepartment">Department</label>
                        <select id="exportDepartment" name="exportDepartment">
                            <option value="">Select Department</option>
                            <option value="Administration">Administration</option>
                            <option value="Finance">Finance</option>
                            <option value="Student Services">Student Services</option>
                            <option value="Library">Library</option>
                            <option value="IT">IT</option>
                            <option value="Academic">Academic</option>
                        </select>
                    </div>
                </div>
                
                <!-- Column Selection Section -->
                <div class="export-section">
                    <h4><i class="fas fa-columns"></i> Column Selection</h4>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="employee_number" checked>
                            Employee ID
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="staff_name" checked>
                            Full Name
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="position" checked>
                            Position
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="department" checked>
                            Department
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="email" checked>
                            Email Address
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="contact" checked>
                            Contact Number
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="status" checked>
                            Staff Status
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="created_date">
                            Registration Date
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="columns[]" value="last_updated">
                            Last Updated
                        </label>
                    </div>
                </div>
                
                <!-- Additional Options Section -->
                <div class="export-section">
                    <h4><i class="fas fa-cog"></i> Additional Options</h4>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="includeHeaders" checked>
                            Include column headers
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="includeTimestamp">
                            Include export timestamp
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="includeSummary">
                            Include summary statistics
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeStaffExportModal()">Cancel</button>
            <button class="modal-action-primary" onclick="submitStaffExportForm()">Export Data</button>
        </div>
    </div>
</div>

<script>
// Make functions globally accessible
window.closeStaffExportModal = function() {
    const modal = document.querySelector('.staff-export-modal-overlay');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        // Reset form
        document.getElementById('staffExportForm').reset();
        document.getElementById('positionFilter').style.display = 'none';
        document.getElementById('departmentFilter').style.display = 'none';
    }
};

window.submitStaffExportForm = function() {
    const form = document.getElementById('staffExportForm');
    const formData = new FormData(form);
    
    // Validate required fields
    const format = document.getElementById('exportFormat').value;
    const scope = document.getElementById('exportScope').value;
    
    if (!format || !scope) {
        showToast('Please select file format and export scope.', 'error');
        return;
    }
    
    // Check if at least one column is selected
    const selectedColumns = formData.getAll('columns[]');
    if (selectedColumns.length === 0) {
        showToast('Please select at least one column to export.', 'error');
        return;
    }
    
    // Show loading state
    const exportBtn = document.querySelector('.modal-action-primary');
    const originalText = exportBtn.textContent;
    exportBtn.textContent = 'Exporting...';
    exportBtn.disabled = true;
    
    fetch(form.dataset.endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        } else {
            throw new Error('Export failed');
        }
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `staff_export_${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showToast('Staff data exported successfully!', 'success');
        closeStaffExportModal();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while exporting staff data.', 'error');
    })
    .finally(() => {
        exportBtn.textContent = originalText;
        exportBtn.disabled = false;
    });
};

// Scope change handler
document.addEventListener('DOMContentLoaded', function() {
    const scopeSelect = document.getElementById('exportScope');
    const positionFilter = document.getElementById('positionFilter');
    const departmentFilter = document.getElementById('departmentFilter');
    
    if (scopeSelect) {
        scopeSelect.addEventListener('change', function() {
            const value = this.value;
            
            // Hide all conditional fields
            positionFilter.style.display = 'none';
            departmentFilter.style.display = 'none';
            
            // Show relevant conditional field
            if (value === 'position') {
                positionFilter.style.display = 'block';
            } else if (value === 'department') {
                departmentFilter.style.display = 'block';
            }
        });
    }
    
    // Column selection validation
    const columnCheckboxes = document.querySelectorAll('input[name="columns[]"]');
    columnCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedColumns = document.querySelectorAll('input[name="columns[]"]:checked');
            if (checkedColumns.length === 0) {
                this.checked = true; // Prevent unchecking all columns
                showToast('At least one column must be selected.', 'warning');
            }
        });
    });
});
</script> 