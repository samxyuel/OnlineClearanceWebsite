<?php
// Clearance Export Modal
?>

<div class="modal-overlay clearance-export-modal-overlay" style="display: none;">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeClearanceExportModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Export Clearance Data</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Configure export parameters for clearance data reporting, analysis, and record keeping.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="clearanceExportForm">
                
                <!-- Export Period Selection -->
                <div class="export-section">
                    <h3><i class="fas fa-calendar-alt"></i> Export Period Selection</h3>
                    
                    <div class="form-group">
                        <label for="exportSchoolYear">School Year</label>
                        <select id="exportSchoolYear" name="schoolYear" required>
                            <option value="">Select School Year</option>
                            <option value="2024-2025" selected>2024-2025</option>
                            <option value="2023-2024">2023-2024</option>
                            <option value="2022-2023">2022-2023</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exportTerm">Term</label>
                        <select id="exportTerm" name="term" required>
                            <option value="">Select Term</option>
                            <option value="all" selected>All Terms</option>
                            <option value="term1">Term 1</option>
                            <option value="term2">Term 2</option>
                            <option value="term3">Term 3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exportDateRange">Date Range (Optional)</label>
                        <div class="date-range-inputs">
                            <input type="date" id="exportStartDate" name="startDate" placeholder="Start Date">
                            <span class="date-separator">to</span>
                            <input type="date" id="exportEndDate" name="endDate" placeholder="End Date">
                        </div>
                        <small class="form-help">Leave empty to export all data for the selected period</small>
                    </div>
                </div>
                
                <!-- Data Selection -->
                <div class="export-section">
                    <h3><i class="fas fa-database"></i> Data Selection</h3>
                    
                    <div class="checkbox-group">
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportStudents" name="dataTypes[]" value="students" checked>
                            <label for="exportStudents">Students</label>
                        </div>
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportFaculty" name="dataTypes[]" value="faculty" checked>
                            <label for="exportFaculty">Faculty</label>
                        </div>
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportStaff" name="dataTypes[]" value="staff">
                            <label for="exportStaff">Staff</label>
                        </div>
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportClearanceStatus" name="dataTypes[]" value="clearanceStatus" checked>
                            <label for="exportClearanceStatus">Clearance Status</label>
                        </div>
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportSignatoryAssignments" name="dataTypes[]" value="signatoryAssignments">
                            <label for="exportSignatoryAssignments">Signatory Assignments</label>
                        </div>
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportDepartmentStats" name="dataTypes[]" value="departmentStats">
                            <label for="exportDepartmentStats">Department Statistics</label>
                        </div>
                        <div class="checkbox-option">
                            <input type="checkbox" id="exportCompletionRates" name="dataTypes[]" value="completionRates" checked>
                            <label for="exportCompletionRates">Completion Rates</label>
                        </div>
                    </div>
                </div>
                
                <!-- Export Format -->
                <div class="export-section">
                    <h3><i class="fas fa-file-export"></i> Export Format</h3>
                    
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="formatPdf" name="exportFormat" value="pdf" checked>
                            <label for="formatPdf">
                                <div class="format-info">
                                    <i class="fas fa-file-pdf"></i>
                                    <div>
                                        <strong>PDF Report</strong>
                                        <small>Formatted for printing and presentation</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="formatExcel" name="exportFormat" value="excel">
                            <label for="formatExcel">
                                <div class="format-info">
                                    <i class="fas fa-file-excel"></i>
                                    <div>
                                        <strong>Excel Spreadsheet</strong>
                                        <small>Data analysis and manipulation</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="formatCsv" name="exportFormat" value="csv">
                            <label for="formatCsv">
                                <div class="format-info">
                                    <i class="fas fa-file-csv"></i>
                                    <div>
                                        <strong>CSV File</strong>
                                        <small>Data migration and system integration</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Report Type -->
                <div class="export-section">
                    <h3><i class="fas fa-chart-bar"></i> Report Type</h3>
                    
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="reportSummary" name="reportType" value="summary" checked>
                            <label for="reportSummary">
                                <div class="report-info">
                                    <i class="fas fa-chart-pie"></i>
                                    <div>
                                        <strong>Summary Report</strong>
                                        <small>High-level statistics and overview</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="reportDetailed" name="reportType" value="detailed">
                            <label for="reportDetailed">
                                <div class="report-info">
                                    <i class="fas fa-list-alt"></i>
                                    <div>
                                        <strong>Detailed Report</strong>
                                        <small>Complete clearance records</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="reportCustom" name="reportType" value="custom">
                            <label for="reportCustom">
                                <div class="report-info">
                                    <i class="fas fa-cogs"></i>
                                    <div>
                                        <strong>Custom Report</strong>
                                        <small>Selected fields only</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Export Preview -->
                <div class="export-section">
                    <h3><i class="fas fa-eye"></i> Export Preview</h3>
                    <div class="export-preview">
                        <div class="preview-item">
                            <span class="preview-label">School Year:</span>
                            <span class="preview-value" id="previewSchoolYear">2024-2025</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Term:</span>
                            <span class="preview-value" id="previewTerm">All Terms</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Data Types:</span>
                            <span class="preview-value" id="previewDataTypes">Students, Faculty, Clearance Status, Completion Rates</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Format:</span>
                            <span class="preview-value" id="previewFormat">PDF Report</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Report Type:</span>
                            <span class="preview-value" id="previewReportType">Summary Report</span>
                        </div>
                    </div>
                </div>
                
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeClearanceExportModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="executeClearanceExport()">
                <i class="fas fa-download"></i> Export Data
            </button>
        </div>
    </div>
</div>

<script>
// Open clearance export modal
window.openClearanceExportModal = function() {
    // Show modal
    document.querySelector('.clearance-export-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Initialize preview
    updateExportPreview();
    
    // Add event listeners for real-time preview updates
    addExportModalEventListeners();
};

// Close clearance export modal
window.closeClearanceExportModal = function() {
    document.querySelector('.clearance-export-modal-overlay').style.display = 'none';
    document.body.style.overflow = 'auto';
};

// Update export preview based on form selections
function updateExportPreview() {
    const schoolYear = document.getElementById('exportSchoolYear').value || '2024-2025';
    const term = document.getElementById('exportTerm').value || 'All Terms';
    const format = document.querySelector('input[name="exportFormat"]:checked').value;
    const reportType = document.querySelector('input[name="reportType"]:checked').value;
    
    // Get selected data types
    const selectedDataTypes = [];
    const dataTypeCheckboxes = document.querySelectorAll('input[name="dataTypes[]"]:checked');
    dataTypeCheckboxes.forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`).textContent;
        selectedDataTypes.push(label);
    });
    
    // Update preview values with fixed content to prevent layout shifts
    document.getElementById('previewSchoolYear').textContent = schoolYear;
    document.getElementById('previewTerm').textContent = term;
    document.getElementById('previewDataTypes').textContent = selectedDataTypes.join(', ') || 'None selected';
    document.getElementById('previewFormat').textContent = format === 'pdf' ? 'PDF Report' : 
                                                       format === 'excel' ? 'Excel Spreadsheet' : 'CSV File';
    document.getElementById('previewReportType').textContent = reportType === 'summary' ? 'Summary Report' :
                                                             reportType === 'detailed' ? 'Detailed Report' : 'Custom Report';
    
    // Force layout recalculation to prevent width changes
    const previewContainer = document.querySelector('.export-preview');
    if (previewContainer) {
        previewContainer.style.width = previewContainer.offsetWidth + 'px';
    }
}

// Add event listeners for real-time preview updates
function addExportModalEventListeners() {
    // School year and term changes
    document.getElementById('exportSchoolYear').addEventListener('change', updateExportPreview);
    document.getElementById('exportTerm').addEventListener('change', updateExportPreview);
    
    // Data type checkboxes
    const dataTypeCheckboxes = document.querySelectorAll('input[name="dataTypes[]"]');
    dataTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateExportPreview();
            updateCheckboxVisualState(this);
        });
        
        // Initialize visual state
        updateCheckboxVisualState(checkbox);
    });
    
    // Format radio buttons
    const formatRadios = document.querySelectorAll('input[name="exportFormat"]');
    formatRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateExportPreview();
            updateRadioVisualState(this);
        });
        
        // Initialize visual state
        updateRadioVisualState(radio);
    });
    
    // Report type radio buttons
    const reportTypeRadios = document.querySelectorAll('input[name="reportType"]');
    reportTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateExportPreview();
            updateRadioVisualState(this);
        });
        
        // Initialize visual state
        updateRadioVisualState(radio);
    });
}

// Update checkbox visual state
function updateCheckboxVisualState(checkbox) {
    const checkboxOption = checkbox.closest('.checkbox-option');
    if (checkbox.checked) {
        checkboxOption.classList.add('selected');
    } else {
        checkboxOption.classList.remove('selected');
    }
}

// Update radio button visual state
function updateRadioVisualState(radio) {
    // Remove selected class from all radio options in the same group
    const radioGroup = radio.closest('.radio-group');
    const radioOptions = radioGroup.querySelectorAll('.radio-option');
    radioOptions.forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to the checked radio option
    const radioOption = radio.closest('.radio-option');
    if (radio.checked) {
        radioOption.classList.add('selected');
    }
}

// Execute the export
window.executeClearanceExport = function() {
    const form = document.getElementById('clearanceExportForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!validateExportForm(formData)) {
        return;
    }
    
    // Show loading state
    const exportBtn = document.querySelector('.modal-actions .btn-primary');
    const originalText = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    exportBtn.disabled = true;
    
    // Simulate export process
    setTimeout(() => {
        // Here you would typically send the data to the server
        console.log('Exporting clearance data:', Object.fromEntries(formData));
        
        // Show success message
        showToast('Export completed successfully! Your file is ready for download.', 'success');
        
        // Close modal
        closeClearanceExportModal();
        
        // Reset button
        exportBtn.innerHTML = originalText;
        exportBtn.disabled = false;
    }, 2000);
};

// Validate export form
function validateExportForm(formData) {
    const schoolYear = formData.get('schoolYear');
    const term = formData.get('term');
    const dataTypes = formData.getAll('dataTypes[]');
    
    if (!schoolYear) {
        showToast('Please select a School Year.', 'error');
        return false;
    }
    
    if (!term) {
        showToast('Please select a Term.', 'error');
        return false;
    }
    
    if (dataTypes.length === 0) {
        showToast('Please select at least one data type to export.', 'error');
        return false;
    }
    
    return true;
}
</script> 