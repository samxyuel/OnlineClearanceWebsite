<!-- Course Export Modal -->
<div id="courseExportModal" class="modal-overlay" style="display: none;">
    <div class="modal-window" style="max-width: 700px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeCourseExportModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">📊 Export Course Data</h2>
            <div class="modal-supporting-text">Select export format, scope, and data to include in your export.</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <div class="export-section">
                <h3 class="section-title">⚙️ Export Options</h3>
                
                <div class="form-group">
                    <label for="exportFormat">Export Format *</label>
                    <select id="exportFormat" onchange="updateExportPreview()">
                        <option value="csv">CSV</option>
                        <option value="xlsx">Excel (XLSX)</option>
                        <option value="pdf">PDF Report</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="exportType">Export Type</label>
                    <select id="exportType" onchange="updateExportPreview()">
                        <option value="all">All Courses</option>
                        <option value="college">College Courses Only</option>
                        <option value="senior-high">Senior High Courses Only</option>
                        <option value="active">Active Courses Only</option>
                        <option value="inactive">Inactive Courses Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="exportDepartment">Filter by Department</label>
                    <select id="exportDepartment" onchange="updateExportPreview()">
                        <option value="">All Departments</option>
                        <!-- College Departments -->
                        <optgroup label="College Departments">
                            <option value="ICT">INFORMATION & COMMUNICATION TECHNOLOGY (ICT)</option>
                            <option value="BSA">BUSINESS & MANAGEMENT, Arts, and Sciences (BSA)</option>
                            <option value="THM">Tourism and Hospitality Management (THM)</option>
                        </optgroup>
                        <!-- Senior High Departments -->
                        <optgroup label="Senior High School Departments">
                            <option value="ACADEMIC">ACADEMIC TRACK</option>
                            <option value="TVL">TECHNICAL-VOCATIONAL LIVELIHOOD TRACK</option>
                            <option value="HOME_ECON">HOME ECONOMICS</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Include Fields</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" id="includeCode" checked onchange="updateExportPreview()">
                            Course Code
                        </label>
                        <label>
                            <input type="checkbox" id="includeName" checked onchange="updateExportPreview()">
                            Course Name
                        </label>
                        <label>
                            <input type="checkbox" id="includeDepartment" checked onchange="updateExportPreview()">
                            Department
                        </label>
                        <label>
                            <input type="checkbox" id="includeStatus" checked onchange="updateExportPreview()">
                            Status
                        </label>
                        <label>
                            <input type="checkbox" id="includeDescription" onchange="updateExportPreview()">
                            Description
                        </label>
                        <label>
                            <input type="checkbox" id="includeStudents" onchange="updateExportPreview()">
                            Student Count
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="export-section">
                <h3 class="section-title">📋 Export Preview</h3>
                <div class="preview-info">
                    <span id="previewCount">0 courses will be exported</span>
                    <span id="previewSize">Estimated size: 0 KB</span>
                </div>
                
                <div class="preview-table-container">
                    <table class="preview-table">
                        <thead>
                            <tr id="previewHeader">
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <tr>
                                <td>BSIT</td>
                                <td>BS in Information Technology</td>
                                <td>ICT</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>BSCS</td>
                                <td>BS in Computer Science</td>
                                <td>ICT</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>BSBA</td>
                                <td>BS in Business Administration</td>
                                <td>BSA</td>
                                <td>Active</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeCourseExportModal()">Cancel</button>
            <button class="btn btn-primary" onclick="processExport()">
                <i class="fas fa-download"></i>
                Export Data
            </button>
        </div>
    </div>
</div>

<style>
/* Enhanced form styling */
.form-group {
    margin-bottom: 20px;
}

/* Regular form labels (for inputs, selects, etc.) */
#courseExportModal .form-group > label {
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
#courseExportModal .form-group > label:hover {
    cursor: default !important;
    padding: 0 !important;
    border: none !important;
    background: transparent !important;
    border-radius: 0 !important;
    transition: none !important;
    box-shadow: none !important;
    transform: none !important;
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

/* Enhanced checkbox group */
#courseExportModal .checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
    width: 100%;
}

#courseExportModal .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 0.95rem;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
}

#courseExportModal .checkbox-group label:hover {
    border-color: var(--darker-saturated-blue);
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(12, 85, 145, 0.1);
}

#courseExportModal .checkbox-group input[type="checkbox"] {
    margin: 0;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    accent-color: var(--darker-saturated-blue);
}

/* Enhanced preview info */
.preview-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    font-size: 0.95rem;
    color: var(--medium-muted-blue);
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
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
    position: sticky;
    top: 0;
    font-size: 0.9rem;
}

.preview-table tr:nth-child(even) {
    background: #f8fafc;
}

.preview-table tr:hover {
    background: #f1f5f9;
}

/* Responsive design for checkbox group */
@media (max-width: 768px) {
    .checkbox-group {
        gap: 6px;
        margin-top: 12px;
    }
    
    .checkbox-group label {
        padding: 10px 14px;
        font-size: 0.9rem;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 16px;
        height: 16px;
    }
}

@media (max-width: 480px) {
    .checkbox-group label {
        padding: 8px 12px;
        font-size: 0.85rem;
    }
}
</style>

<script>
function openExportModalInternal() {
    document.getElementById('courseExportModal').style.display = 'flex';
    updateExportPreview();
}

// Global function for external access
window.openExportModalInternal = openExportModalInternal;

function closeCourseExportModal() {
    document.getElementById('courseExportModal').style.display = 'none';
}

function updateExportPreview() {
    const format = document.getElementById('exportFormat').value;
    const type = document.getElementById('exportType').value;
    const department = document.getElementById('exportDepartment').value;
    
    // Update preview count based on filters
    let count = 15; // Total courses
    if (type === 'college') count = 11;
    else if (type === 'senior-high') count = 4;
    else if (type === 'active') count = 15;
    else if (type === 'inactive') count = 0;
    
    if (department) {
        // Filter by department
        const deptCounts = {
            'ICT': 3, 'BSA': 5, 'THM': 3,
            'ACADEMIC': 4, 'TVL': 2, 'HOME_ECON': 3
        };
        count = deptCounts[department] || 0;
    }
    
    document.getElementById('previewCount').textContent = `${count} courses will be exported`;
    
    // Update estimated size
    const sizePerRow = 0.1; // KB per row
    const estimatedSize = (count * sizePerRow).toFixed(1);
    document.getElementById('previewSize').textContent = `Estimated size: ${estimatedSize} KB`;
    
    // Update preview table headers based on selected fields
    updatePreviewHeaders();
    
    // Update preview table content
    updatePreviewContent();
}

function updatePreviewHeaders() {
    const includeCode = document.getElementById('includeCode').checked;
    const includeName = document.getElementById('includeName').checked;
    const includeDepartment = document.getElementById('includeDepartment').checked;
    const includeStatus = document.getElementById('includeStatus').checked;
    const includeDescription = document.getElementById('includeDescription').checked;
    const includeStudents = document.getElementById('includeStudents').checked;
    
    const header = document.getElementById('previewHeader');
    header.innerHTML = '';
    
    if (includeCode) {
        const th = document.createElement('th');
        th.textContent = 'Course Code';
        header.appendChild(th);
    }
    
    if (includeName) {
        const th = document.createElement('th');
        th.textContent = 'Course Name';
        header.appendChild(th);
    }
    
    if (includeDepartment) {
        const th = document.createElement('th');
        th.textContent = 'Department';
        header.appendChild(th);
    }
    
    if (includeStatus) {
        const th = document.createElement('th');
        th.textContent = 'Status';
        header.appendChild(th);
    }
    
    if (includeDescription) {
        const th = document.createElement('th');
        th.textContent = 'Description';
        header.appendChild(th);
    }
    
    if (includeStudents) {
        const th = document.createElement('th');
        th.textContent = 'Students';
        header.appendChild(th);
    }
}

function updatePreviewContent() {
    const includeCode = document.getElementById('includeCode').checked;
    const includeName = document.getElementById('includeName').checked;
    const includeDepartment = document.getElementById('includeDepartment').checked;
    const includeStatus = document.getElementById('includeStatus').checked;
    const includeDescription = document.getElementById('includeDescription').checked;
    const includeStudents = document.getElementById('includeStudents').checked;
    
    const tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = '';
    
    const sampleData = [
        { code: 'BSIT', name: 'BS in Information Technology', department: 'ICT', status: 'Active', description: 'Bachelor of Science in Information Technology', students: 0 },
        { code: 'BSCS', name: 'BS in Computer Science', department: 'ICT', status: 'Active', description: 'Bachelor of Science in Computer Science', students: 0 },
        { code: 'BSBA', name: 'BS in Business Administration', department: 'BSA', status: 'Active', description: 'Bachelor of Science in Business Administration', students: 0 }
    ];
    
    sampleData.forEach(row => {
        const tr = document.createElement('tr');
        
        if (includeCode) {
            const td = document.createElement('td');
            td.textContent = row.code;
            tr.appendChild(td);
        }
        
        if (includeName) {
            const td = document.createElement('td');
            td.textContent = row.name;
            tr.appendChild(td);
        }
        
        if (includeDepartment) {
            const td = document.createElement('td');
            td.textContent = row.department;
            tr.appendChild(td);
        }
        
        if (includeStatus) {
            const td = document.createElement('td');
            td.textContent = row.status;
            tr.appendChild(td);
        }
        
        if (includeDescription) {
            const td = document.createElement('td');
            td.textContent = row.description;
            tr.appendChild(td);
        }
        
        if (includeStudents) {
            const td = document.createElement('td');
            td.textContent = row.students;
            tr.appendChild(td);
        }
        
        tbody.appendChild(tr);
    });
}

// Debounce mechanism to prevent multiple calls
let isProcessingExport = false;

function processExport() {
    // Prevent multiple simultaneous calls
    if (isProcessingExport) {
        return;
    }
    isProcessingExport = true;
    
    const format = document.getElementById('exportFormat').value;
    const type = document.getElementById('exportType').value;
    const department = document.getElementById('exportDepartment').value;
    
    // Get selected fields
    const includeCode = document.getElementById('includeCode').checked;
    const includeName = document.getElementById('includeName').checked;
    const includeDepartment = document.getElementById('includeDepartment').checked;
    const includeStatus = document.getElementById('includeStatus').checked;
    const includeDescription = document.getElementById('includeDescription').checked;
    const includeStudents = document.getElementById('includeStudents').checked;
    
    // Build confirmation message based on user's choices
    let confirmationMessage = 'Are you sure you want to export courses with the following settings?\n\n';
    confirmationMessage += `• Export Format: ${format.toUpperCase()}\n`;
    confirmationMessage += `• Export Type: ${type === 'all' ? 'All Courses' : type === 'college' ? 'College Courses Only' : type === 'senior-high' ? 'Senior High Courses Only' : type === 'active' ? 'Active Courses Only' : 'Inactive Courses Only'}\n`;
    if (department) {
        confirmationMessage += `• Department Filter: ${department}\n`;
    }
    confirmationMessage += `• Include Fields: ${includeCode ? 'Code, ' : ''}${includeName ? 'Name, ' : ''}${includeDepartment ? 'Department, ' : ''}${includeStatus ? 'Status, ' : ''}${includeDescription ? 'Description, ' : ''}${includeStudents ? 'Students' : ''}\n\n`;
    confirmationMessage += 'This action will generate and download the export file.';
    
    // Show confirmation alert
    showConfirmationModal(
        'Confirm Export',
        confirmationMessage,
        'Export Courses',
        'Cancel',
        () => {
            // User confirmed - proceed with export
            showToastNotification('Preparing export...', 'info', 2000);
            
            setTimeout(() => {
                // Simulate file download
                const fileName = `courses_export_${new Date().toISOString().split('T')[0]}.${format}`;
                
                if (format === 'csv') {
                    downloadCSV(fileName);
                } else if (format === 'xlsx') {
                    downloadExcel(fileName);
                } else if (format === 'pdf') {
                    downloadPDF(fileName);
                }
                
                showToastNotification('Export completed successfully!', 'success', 3000);
                closeCourseExportModal();
                isProcessingExport = false; // Reset processing flag
            }, 1500);
        },
        'info'
    );
}

function downloadCSV(fileName) {
    const csvContent = 'Course Code,Course Name,Department,Status\nBSIT,BS in Information Technology,ICT,Active\nBSCS,BS in Computer Science,ICT,Active\nBSBA,BS in Business Administration,BSA,Active';
    const blob = new Blob([csvContent], { type: 'text/csv' });
    downloadFile(blob, fileName);
}

function downloadExcel(fileName) {
    // Simulate Excel file download
    const blob = new Blob(['Excel file content'], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    downloadFile(blob, fileName);
}

function downloadPDF(fileName) {
    // Simulate PDF file download
    const blob = new Blob(['PDF file content'], { type: 'application/pdf' });
    downloadFile(blob, fileName);
}

function downloadFile(blob, fileName) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.click();
    window.URL.revokeObjectURL(url);
}
</script> 