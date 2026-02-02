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
                        <!-- Departments will be populated dynamically -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="courseStatus">Status *</label>
                    <select id="courseStatus" name="courseStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
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
async function populateCourseDepartments() {
    const deptSelect = document.getElementById('courseDepartment');
    if (!deptSelect) return;
    
    try {
        const response = await fetch('../../api/departments/list.php?limit=500', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.departments) {
            deptSelect.innerHTML = '<option value="">Select department</option>';
            
            // FILTER: Only include departments that have College or SHS sectors (course-eligible)
            const courseEligibleDepts = data.departments.filter(dept => {
                return dept.sectors.some(sector => 
                    sector.sector_id === 1 || sector.sector_id === 2
                );
            });
            
            // Group by sector (only College and SHS)
            const bySector = {};
            courseEligibleDepts.forEach(dept => {
                dept.sectors.forEach(sector => {
                    // Only include College and SHS sectors
                    if (sector.sector_id === 1 || sector.sector_id === 2) {
                        const sectorName = sector.sector_name || 'Unknown';
                        if (!bySector[sectorName]) bySector[sectorName] = [];
                        bySector[sectorName].push({
                            department_id: sector.department_id, // Use student sector's ID
                            department_name: dept.department_name,
                            department_code: dept.department_code,
                            is_shared: dept.is_shared
                        });
                    }
                });
            });
            
            // Show departments grouped by sector (College, SHS only)
            const sectorOrder = ['College', 'Senior High School'];
            sectorOrder.forEach(sector => {
                if (bySector[sector] && bySector[sector].length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = sector;
                    
                    bySector[sector].forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.department_id;
                        let text = dept.department_name;
                        if (dept.department_code) {
                            text += ` (${dept.department_code})`;
                        }
                        // Show indicator if shared
                        if (dept.is_shared) {
                            text += ' [Shared]';
                        }
                        option.textContent = text;
                        optgroup.appendChild(option);
                    });
                    
                    deptSelect.appendChild(optgroup);
                }
            });
        }
    } catch (error) {
        console.error('Failed to load departments:', error);
        deptSelect.innerHTML = '<option value="">Error loading departments</option>';
    }
}

async function openAddCourseModalInternal(departmentId) {
    try {
        const modal = document.getElementById('addCourseModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('Add course modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        // Set the department ID
        const deptIdField = document.getElementById('addCourseDepartmentId');
        if (deptIdField && departmentId) {
            deptIdField.value = departmentId;
        }
        
        // Populate departments dropdown
        await populateCourseDepartments();
        
        // Pre-select the department in dropdown
        const deptSelect = document.getElementById('courseDepartment');
        if (deptSelect && departmentId) {
            deptSelect.value = departmentId;
        }
        
        // Reset form
        const form = document.getElementById('addCourseForm');
        if (form) form.reset();
        
        // Re-set department if provided
        if (deptSelect && departmentId) {
            deptSelect.value = departmentId;
        }
        
        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('addCourseModal');
        } else {
            // Fallback to direct manipulation
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }
    } catch (error) {
        if (typeof showToastNotification === 'function') {
            showToastNotification('Unable to open add course modal. Please try again.', 'error');
        }
    }
}

// Global function for external access
window.openAddCourseModalInternal = openAddCourseModalInternal;

window.closeAddCourseModal = function() {
    console.log('[AddCourseModal] closeAddCourseModal() called');
    try {
        const modal = document.getElementById('addCourseModal');
        if (!modal) {
            console.warn('[AddCourseModal] Modal not found');
            return;
        }
        console.log('[AddCourseModal] Closing modal:', modal.id);

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('addCourseModal');
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
        status: formData.get('courseStatus')
    };
    
    // Show loading notification
    showToastNotification('Adding course...', 'info', 2000);
    
    // Call API to create course
    fetch('../../api/programs/create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(courseData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToastNotification(
                data.message || 'Course added successfully!', 
                'success', 
                3000
            );
            
            // Close modal
            closeAddCourseModal();
            
            // Refresh the page
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToastNotification(
                data.message || 'Failed to add course', 
                'error', 
                4000
            );
        }
    })
    .catch(error => {
        console.error('Error adding course:', error);
        showToastNotification('An error occurred while adding the course', 'error', 4000);
    });
}
</script> 