<!-- Add Course Modal -->
<div id="addCourseModal" class="modal-overlay" style="display: none;">
    <div class="modal-window" style="max-width: 600px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeAddCourseModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">📚 Add New Course</h2>
            <div class="modal-supporting-text">Create a new course for student registration</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="addCourseForm">
                <input type="hidden" id="addCourseDepartmentId" name="departmentId">
                
                <div class="form-group">
                    <label for="courseCode">Course Code *</label>
                    <input type="text" id="courseCode" name="courseCode" required 
                           placeholder="Enter course code (e.g., BSIT, BSCS)">
                </div>
                
                <div class="form-group">
                    <label for="courseName">Course Name *</label>
                    <input type="text" id="courseName" name="courseName" required 
                           placeholder="Enter course name">
                </div>
                
                <div class="form-group">
                    <label for="courseDepartment">Department *</label>
                    <select id="courseDepartment" name="courseDepartment" required>
                        <option value="">Select department</option>
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
                    <label for="courseStatus">Status *</label>
                    <select id="courseStatus" name="courseStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="courseDescription">Description</label>
                    <textarea id="courseDescription" name="courseDescription" 
                              placeholder="Enter course description (optional)"></textarea>
                </div>
            </form>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeAddCourseModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveCourse()">Add Course</button>
        </div>
    </div>
</div>

<style>
/* Enhanced form styling for course modals */
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: var(--deep-navy-blue);
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-group input[type="text"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-group input[type="text"]:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--darker-saturated-blue);
    box-shadow: 0 0 0 3px rgba(12, 85, 145, 0.1);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.form-group input[type="text"]::placeholder,
.form-group textarea::placeholder {
    color: #9ca3af;
}

/* Enhanced select styling */
.form-group select {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 40px;
}

/* Required field indicator */
.form-group label::after {
    content: " *";
    color: #ef4444;
    font-weight: 600;
}

.form-group label:not([for*="Description"])::after {
    content: " *";
    color: #ef4444;
    font-weight: 600;
}
</style>

<script>
function openAddCourseModalInternal(departmentId) {
    // Set the department ID
    document.getElementById('addCourseDepartmentId').value = departmentId;
    
    // Pre-select the department in dropdown
    document.getElementById('courseDepartment').value = departmentId;
    
    // Reset form
    document.getElementById('addCourseForm').reset();
    
    // Show modal
    document.getElementById('addCourseModal').style.display = 'flex';
}

// Global function for external access
window.openAddCourseModalInternal = openAddCourseModalInternal;

function closeAddCourseModal() {
    document.getElementById('addCourseModal').style.display = 'none';
}

function saveCourse() {
    const form = document.getElementById('addCourseForm');
    
    // Form validation with detailed error messages
    if (!form.checkValidity()) {
        // Check specific fields and show custom error messages
        const courseCode = form.querySelector('[name="courseCode"]');
        const courseName = form.querySelector('[name="courseName"]');
        const courseDepartment = form.querySelector('[name="courseDepartment"]');
        const courseStatus = form.querySelector('[name="courseStatus"]');
        
        if (!courseCode.value.trim()) {
            showToastNotification('Course code is required', 'error', 4000);
            courseCode.focus();
            return;
        }
        
        if (courseCode.value.trim().length < 2) {
            showToastNotification('Course code must be at least 2 characters long', 'error', 4000);
            courseCode.focus();
            return;
        }
        
        if (!courseName.value.trim()) {
            showToastNotification('Course name is required', 'error', 4000);
            courseName.focus();
            return;
        }
        
        if (courseName.value.trim().length < 5) {
            showToastNotification('Course name must be at least 5 characters long', 'error', 4000);
            courseName.focus();
            return;
        }
        
        if (!courseDepartment.value) {
            showToastNotification('Please select a department', 'error', 4000);
            courseDepartment.focus();
            return;
        }
        
        if (!courseStatus.value) {
            showToastNotification('Please select a course status', 'error', 4000);
            courseStatus.focus();
            return;
        }
        
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const courseData = {
        code: formData.get('courseCode'),
        name: formData.get('courseName'),
        departmentId: formData.get('departmentId'),
        department: formData.get('courseDepartment'),
        status: formData.get('courseStatus'),
        description: formData.get('courseDescription')
    };
    
    // Show loading notification
    showToastNotification('Adding course...', 'info', 2000);
    
    // Simulate API call
    console.log('Adding course:', courseData);
    
    setTimeout(() => {
        // Show success message
        showToastNotification('Course added successfully!', 'success', 3000);
        
        // Close modal
        closeAddCourseModal();
        
        // Refresh the page or update the UI
        setTimeout(() => {
            location.reload();
        }, 1000);
    }, 1500);
}
</script> 