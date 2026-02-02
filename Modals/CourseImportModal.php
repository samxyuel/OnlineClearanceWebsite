<!-- Course Import Modal -->
<div id="courseImportModal" class="modal-overlay" style="display: none;">
    <div class="modal-window" style="max-width: 700px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeCourseImportModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">📥 Import Course Data</h2>
            <div class="modal-supporting-text">Upload a file to import course data. Supported formats: Excel (.xlsx, .xls), CSV (.csv)</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <div class="import-section">
                <h3 class="section-title">📁 Upload File</h3>
                <p class="import-description">
                    Upload a CSV or Excel file containing course data. 
                    <a href="#" onclick="downloadTemplate()">Download template</a>
                </p>
                
                <div class="file-upload-container">
                    <input type="file" id="importFile" accept=".csv,.xlsx,.xls" 
                           onchange="handleFileSelect(event)">
                    <label for="importFile" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choose file or drag and drop</span>
                        <small>CSV, XLSX, XLS (Max 5MB)</small>
                    </label>
                </div>
                
                <div id="filePreview" class="file-preview" style="display: none;">
                    <h5>File Preview</h5>
                    <div class="preview-table-container">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Department</th>
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
            
            <div class="import-section">
                <h3 class="section-title">⚙️ Import Options</h3>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="skipDuplicates" checked>
                        Skip duplicate courses
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="updateExisting">
                        Update existing courses
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="importType">Import Type</label>
                    <select id="importType">
                        <option value="all">All Courses</option>
                        <option value="college">College Courses Only</option>
                        <option value="senior-high">Senior High Courses Only</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeCourseImportModal()">Cancel</button>
            <button class="btn btn-primary" onclick="processImport()" id="importButton" disabled>
                <i class="fas fa-upload"></i>
                Import Courses
            </button>
        </div>
    </div>
</div>

<style>
/* Enhanced import description */
.import-description {
    color: var(--medium-muted-blue);
    margin-bottom: 20px;
    line-height: 1.5;
}

/* Enhanced file upload container */
.file-upload-container {
    position: relative;
    margin-bottom: 20px;
}

.file-upload-container input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 40px;
    border: 2px dashed #d1d5db;
    border-radius: 16px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 20px 0;
}

.file-upload-label:hover {
    border-color: var(--darker-saturated-blue);
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(12, 85, 145, 0.1);
}

.file-upload-label i {
    font-size: 3rem;
    color: var(--darker-saturated-blue);
    margin-bottom: 16px;
}

.file-upload-label span {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 1.1rem;
    color: var(--deep-navy-blue);
}

.file-upload-label small {
    color: var(--medium-muted-blue);
    font-size: 0.9rem;
}

/* Enhanced file preview */
.file-preview {
    margin-top: 20px;
    padding: 24px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.file-preview h5 {
    margin-bottom: 16px;
    color: var(--deep-navy-blue);
    font-weight: 600;
}

/* Enhanced preview table */
.preview-table-container {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
}

.preview-table th,
.preview-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.preview-table th {
    background: linear-gradient(135deg, var(--darker-saturated-blue) 0%, var(--bright-golden-yellow) 100%);
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.preview-table tr:nth-child(even) {
    background: #f8fafc;
}

.preview-table tr:hover {
    background: #f1f5f9;
}

/* Enhanced form styling */
.form-group {
    margin-bottom: 20px;
}

/* Regular form labels (for inputs, selects, etc.) */
#courseImportModal .form-group > label {
    display: block !important;
    font-weight: 600 !important;
    color: var(--deep-navy-blue) !important;
    margin-bottom: 8px !important;
    font-size: 0.95rem !important;
    cursor: default !important;
    padding: 0 !important;
    border: none !important;
    background: transparent !important;
    border-radius: 0 !important;
    transition: none !important;
    box-shadow: none !important;
    transform: none !important;
}

/* Override any hover effects on regular form labels */
#courseImportModal .form-group > label:hover {
    cursor: default !important;
    padding: 0 !important;
    border: none !important;
    background: transparent !important;
    border-radius: 0 !important;
    transition: none !important;
    box-shadow: none !important;
    transform: none !important;
}

/* Checkbox labels should have button-like styling */
#courseImportModal .form-group label:has(input[type="checkbox"]) {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    cursor: pointer !important;
    font-size: 0.95rem !important;
    padding: 12px 16px !important;
    border-radius: 8px !important;
    border: 1px solid #e5e7eb !important;
    background: white !important;
    transition: all 0.2s ease !important;
}

#courseImportModal .form-group label:has(input[type="checkbox"]):hover {
    border-color: var(--darker-saturated-blue) !important;
    background: #f8fafc !important;
}

.form-group input[type="checkbox"] {
    margin: 0;
    width: 16px;
    height: 16px;
}

.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    transition: all 0.2s ease;
}

.form-group select:focus {
    outline: none;
    border-color: var(--darker-saturated-blue);
    box-shadow: 0 0 0 3px rgba(12, 85, 145, 0.1);
}
</style>

<script>
function openImportModalInternal() {
    try {
        const modal = document.getElementById('courseImportModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('Course import modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('courseImportModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }

        resetImportModal();
    } catch (error) {
        if (typeof showToastNotification === 'function') {
            showToastNotification('Unable to open course import modal. Please try again.', 'error');
        }
    }
}

// Global function for external access
window.openImportModalInternal = openImportModalInternal;

window.closeCourseImportModal = function() {
    try {
        const modal = document.getElementById('courseImportModal');
        if (!modal) return;

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('courseImportModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            modal.classList.remove('active');
        }
    } catch (error) {
        // Silent error handling
    }
};

function resetImportModal() {
    document.getElementById('importFile').value = '';
    document.getElementById('filePreview').style.display = 'none';
    document.getElementById('importButton').disabled = true;
    document.getElementById('previewTableBody').innerHTML = '';
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        showToastNotification('File size exceeds 5MB limit', 'error');
        return;
    }
    
    // Validate file type
    const allowedTypes = ['.csv', '.xlsx', '.xls'];
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    if (!allowedTypes.includes(fileExtension)) {
        showToastNotification('Please select a valid CSV or Excel file', 'error');
        return;
    }
    
    // Simulate file processing and preview
    simulateFilePreview(file);
}

function simulateFilePreview(file) {
    // Read CSV file and show preview
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const text = e.target.result;
            const lines = text.split('\n').filter(line => line.trim());
            
            if (lines.length < 2) {
                showToastNotification('File is empty or has no data rows', 'error');
                return;
            }
            
            // Parse header
            const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
            
            // Parse data rows (show first 5 for preview)
            const previewRows = lines.slice(1, 6);
            const previewData = [];
            
            previewRows.forEach(line => {
                const values = line.split(',').map(v => v.trim().replace(/"/g, ''));
                const row = {};
                headers.forEach((header, index) => {
                    row[header.toLowerCase().replace(/ /g, '_')] = values[index] || '';
                });
                previewData.push(row);
            });
            
            // Render preview table
            const tbody = document.getElementById('previewTableBody');
            tbody.innerHTML = '';
            
            previewData.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.course_code || row.code || ''}</td>
                    <td>${row.course_name || row.name || ''}</td>
                    <td>${row.department || ''}</td>
                    <td>${row.status || 'Active'}</td>
                `;
                tbody.appendChild(tr);
            });
            
            // Update stats
            const totalRows = lines.length - 1; // Exclude header
            document.getElementById('totalRecords').textContent = totalRows;
            document.getElementById('toImport').textContent = totalRows;
            document.getElementById('toUpdate').textContent = '0';
            document.getElementById('toSkip').textContent = '0';
            
            document.getElementById('filePreview').style.display = 'block';
            document.getElementById('importButton').disabled = false;
            
            showToastNotification(`Preview loaded: ${totalRows} courses found`, 'success');
        } catch (error) {
            showToastNotification('Error reading file: ' + error.message, 'error');
            console.error('File preview error:', error);
        }
    };
    
    reader.onerror = function() {
        showToastNotification('Error reading file', 'error');
    };
    
    reader.readAsText(file);
}

// Debounce mechanism to prevent multiple calls
let isProcessingImport = false;

function processImport() {
    // Prevent multiple simultaneous calls
    if (isProcessingImport) {
        return;
    }
    
    if (!selectedFile) {
        showToastNotification('Please select a file first', 'error');
        return;
    }
    
    isProcessingImport = true;
    
    const skipDuplicates = document.getElementById('skipDuplicates').checked;
    const updateExisting = document.getElementById('updateExisting').checked;
    const importType = document.getElementById('importType').value;
    
    // Determine import mode based on checkboxes
    let importMode = 'skip'; // default
    if (updateExisting) {
        importMode = 'update';
    }
    
    // Build confirmation message based on user's choices
    let confirmationMessage = 'Are you sure you want to import courses with the following settings?\n\n';
    confirmationMessage += `• Import Type: ${importType === 'all' ? 'All Courses' : importType === 'college' ? 'College Courses Only' : 'Senior High Courses Only'}\n`;
    confirmationMessage += `• Skip Duplicates: ${skipDuplicates ? 'Yes' : 'No'}\n`;
    confirmationMessage += `• Update Existing: ${updateExisting ? 'Yes' : 'No'}\n\n`;
    confirmationMessage += 'This action will import the selected courses into the system.';
    
    // Show confirmation alert
    showConfirmationModal(
        'Confirm Import',
        confirmationMessage,
        'Import Courses',
        'Cancel',
        async () => {
            try {
                // User confirmed - proceed with import
                showToastNotification('Importing courses...', 'info', 2000);
                
                // Prepare form data
                const formData = new FormData();
                formData.append('importFile', selectedFile);
                formData.append('importType', 'course_import');
                formData.append('importMode', importMode);
                formData.append('validateData', 'off');
                formData.append('validateOnly', '0');
                formData.append('importPolicy', 'partial');
                
                // Make API call
                const response = await fetch('../../controllers/importData.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToastNotification(result.message || 'Courses imported successfully!', 'success', 3000);
                    
                    // Show detailed stats if available
                    if (result.stats) {
                        console.log('Import Stats:', result.stats);
                        if (result.stats.errors && result.stats.errors.length > 0) {
                            console.warn('Import Errors:', result.stats.errors);
                        }
                    }
                    
                    closeCourseImportModal();
                    isProcessingImport = false;
                    
                    // Refresh the page
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToastNotification(result.message || 'Import failed', 'error');
                    isProcessingImport = false;
                }
            } catch (error) {
                showToastNotification('Error importing courses: ' + error.message, 'error');
                console.error('Import error:', error);
                isProcessingImport = false;
            }
        },
        'info'
    );
    
    // If user cancels, reset the flag
    setTimeout(() => {
        if (isProcessingImport) {
            isProcessingImport = false;
        }
    }, 1000);
}

function downloadTemplate() {
    showToastNotification('Template download started', 'info');
    
    // Create CSV content with proper examples
    const csvContent = `Course Code,Course Name,Department,Status
BSIT,Bachelor of Science in Information Technology,ICT,Active
BSCS,Bachelor of Science in Computer Science,ICT,Active
BSCPE,Bachelor of Science in Computer Engineering,ICT,Active
BSBA,Bachelor of Science in Business Administration,Business Administration,Active
ABM,Accountancy Business and Management,Academic Track,Active
STEM,Science Technology Engineering and Mathematics,Academic Track,Active`;
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'course_import_template.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showToastNotification('Template downloaded successfully', 'success');
}
</script> 