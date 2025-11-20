<!-- Edit Department Modal -->
<div id="editDepartmentModal" class="modal-overlay" style="display: none;">
    <div class="modal-window" style="max-width: 600px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeEditDepartmentModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">✏️ Edit Department</h2>
            <div class="modal-supporting-text">Update department information and settings</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="editDepartmentForm">
                <input type="hidden" id="editDepartmentId" name="departmentId">
                
                <div class="form-group">
                    <label for="editDepartmentName">Department Name *</label>
                    <input type="text" id="editDepartmentName" name="departmentName" required 
                           placeholder="Enter department name">
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentType">Department Type *</label>
                    <select id="editDepartmentType" name="departmentType" required>
                        <option value="">Select department type</option>
                        <option value="college">College</option>
                        <option value="senior-high">Senior High School</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentStatus">Status *</label>
                    <select id="editDepartmentStatus" name="departmentStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentDescription">Description</label>
                    <textarea id="editDepartmentDescription" name="departmentDescription" 
                              placeholder="Enter department description (optional)"></textarea>
                </div>
            </form>
            
            <!-- Course Management Section -->
            <div class="course-management-section">
                <h3 class="section-title">📚 Department Courses</h3>
                <p class="section-description">Manage courses within this department. You can remove courses that are no longer needed.</p>
                
                <div class="courses-list" id="departmentCoursesList">
                    <!-- Course items will be populated here -->
                </div>
                
                <div class="no-courses-message" id="noCoursesMessage" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <p>No courses found in this department.</p>
                </div>
            </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeEditDepartmentModal()">Cancel</button>
            <button class="btn btn-primary" onclick="updateDepartment()">Update Department</button>
        </div>
    </div>
</div>

<style>
/* Enhanced form styling for edit department modal */
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
.form-group label:not([for*="Description"])::after {
    content: " *";
    color: #ef4444;
    font-weight: 600;
}

/* Course Management Section Styles */
.course-management-section {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--deep-navy-blue);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-description {
    color: var(--medium-muted-blue);
    font-size: 0.9rem;
    margin-bottom: 20px;
    line-height: 1.5;
}

.courses-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 200px;
    overflow-y: auto;
}

.course-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.course-item:hover {
    border-color: var(--darker-saturated-blue);
    box-shadow: 0 2px 8px rgba(12, 85, 145, 0.1);
}

.course-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.course-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}



.course-icon {
    color: var(--darker-saturated-blue);
    font-size: 1.1rem;
}

.course-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.course-code {
    font-weight: 600;
    color: var(--deep-navy-blue);
    font-size: 0.95rem;
}

.course-name {
    color: var(--medium-muted-blue);
    font-size: 0.85rem;
}





/* Course Status Toggle Switch */
.course-status-toggle {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
}

.course-status-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.course-status-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 20px;
}

.course-status-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.course-status-toggle input:checked + .course-status-slider {
    background-color: #10b981;
}

.course-status-toggle input:checked + .course-status-slider:before {
    transform: translateX(20px);
}

.course-status-toggle:hover .course-status-slider {
    box-shadow: 0 0 4px rgba(16, 185, 129, 0.3);
}

.remove-course-btn {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.remove-course-btn:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    transform: translateY(-1px);
}

.no-courses-message {
    text-align: center;
    padding: 32px 16px;
    color: var(--medium-muted-blue);
}

.no-courses-message i {
    font-size: 2rem;
    margin-bottom: 12px;
    opacity: 0.5;
}

.no-courses-message p {
    margin: 0;
    font-size: 0.9rem;
}

/* Responsive design for course management */
@media (max-width: 768px) {
    .course-item {
        padding: 10px 12px;
    }
    
    .course-info {
        gap: 8px;
    }
    
    .course-code {
        font-size: 0.9rem;
    }
    
    .course-name {
        font-size: 0.8rem;
    }
    
    .course-actions {
        gap: 8px;
    }
    
    .course-status-toggle {
        width: 36px;
        height: 18px;
    }
    
    .course-status-slider:before {
        height: 14px;
        width: 14px;
    }
    
    .course-status-toggle input:checked + .course-status-slider:before {
        transform: translateX(18px);
    }
    
    .remove-course-btn {
        padding: 4px 8px;
        font-size: 0.75rem;
    }
}
</style>

<script>
function openEditDepartmentModalInternal(departmentId) {
    try {
        const modal = document.getElementById('editDepartmentModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('Edit department modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        // Simulate fetching department data
        const departmentData = getDepartmentData(departmentId);
        
        // Populate form fields
        const deptIdField = document.getElementById('editDepartmentId');
        const deptNameField = document.getElementById('editDepartmentName');
        const deptTypeField = document.getElementById('editDepartmentType');
        const deptStatusField = document.getElementById('editDepartmentStatus');
        const deptDescField = document.getElementById('editDepartmentDescription');
        
        if (deptIdField) deptIdField.value = departmentId;
        if (deptNameField) deptNameField.value = departmentData.name;
        if (deptTypeField) deptTypeField.value = departmentData.type;
        if (deptStatusField) deptStatusField.value = departmentData.status;
        if (deptDescField) deptDescField.value = departmentData.description || '';
        
        // Load department courses
        if (departmentId) {
            loadDepartmentCourses(departmentId);
        }
        
        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('editDepartmentModal');
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
            showToastNotification('Unable to open edit department modal. Please try again.', 'error');
        }
    }
}

// Global function for external access
window.openEditDepartmentModalInternal = openEditDepartmentModalInternal;

window.closeEditDepartmentModal = function() {
    console.log('[EditDepartmentModal] closeEditDepartmentModal() called');
    try {
        const modal = document.getElementById('editDepartmentModal');
        if (!modal) {
            console.warn('[EditDepartmentModal] Modal not found');
            return;
        }
        console.log('[EditDepartmentModal] Closing modal:', modal.id);

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('editDepartmentModal');
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

function updateDepartment() {
    const form = document.getElementById('editDepartmentForm');
    
    // Form validation with detailed error messages
    if (!form.checkValidity()) {
        // Check specific fields and show custom error messages
        const departmentName = form.querySelector('[name="departmentName"]');
        const departmentType = form.querySelector('[name="departmentType"]');
        const departmentStatus = form.querySelector('[name="departmentStatus"]');
        
        if (!departmentName.value.trim()) {
            showToastNotification('Department name is required', 'error', 4000);
            departmentName.focus();
            return;
        }
        
        if (departmentName.value.trim().length < 3) {
            showToastNotification('Department name must be at least 3 characters long', 'error', 4000);
            departmentName.focus();
            return;
        }
        
        if (!departmentType.value) {
            showToastNotification('Please select a department type', 'error', 4000);
            departmentType.focus();
            return;
        }
        
        if (!departmentStatus.value) {
            showToastNotification('Please select a department status', 'error', 4000);
            departmentStatus.focus();
            return;
        }
        
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const departmentData = {
        id: formData.get('departmentId'),
        name: formData.get('departmentName'),
        type: formData.get('departmentType'),
        status: formData.get('departmentStatus'),
        description: formData.get('departmentDescription')
    };
    
    // Show loading notification
    showToastNotification('Updating department...', 'info', 2000);
    
    // Simulate API call
    console.log('Updating department:', departmentData);
    
    setTimeout(() => {
        // Show success message
        showToastNotification('Department updated successfully!', 'success', 3000);
        
        // Close modal
        closeEditDepartmentModal();
        
        // Refresh the page or update the UI
        setTimeout(() => {
            location.reload();
        }, 1000);
    }, 1500);
}

function getDepartmentData(departmentId) {
    // Simulate API call to get department data
    const departments = {
        'ICT': {
            name: 'INFORMATION & COMMUNICATION TECHNOLOGY (ICT)',
            type: 'college',
            status: 'active',
            description: 'Information and Communication Technology Department'
        },
        'BSA': {
            name: 'BUSINESS & MANAGEMENT, Arts, and Sciences (BSA)',
            type: 'college',
            status: 'active',
            description: 'Business and Management Department'
        },
        'THM': {
            name: 'Tourism and Hospitality Management (THM)',
            type: 'college',
            status: 'active',
            description: 'Tourism and Hospitality Management Department'
        },
        'ACADEMIC': {
            name: 'ACADEMIC TRACK',
            type: 'senior-high',
            status: 'active',
            description: 'Academic Track Department'
        },
        'TVL': {
            name: 'TECHNICAL-VOCATIONAL LIVELIHOOD TRACK',
            type: 'senior-high',
            status: 'active',
            description: 'Technical-Vocational Livelihood Track Department'
        },
        'HOME_ECON': {
            name: 'HOME ECONOMICS',
            type: 'senior-high',
            status: 'active',
            description: 'Home Economics Department'
        }
    };
    
    return departments[departmentId] || {
        name: '',
        type: '',
        status: 'active',
        description: ''
    };
}

function loadDepartmentCourses(departmentId) {
    // Simulate API call to get department courses
    const departmentCourses = {
        'ICT': [
            { code: 'BSIT', name: 'BS in Information Technology', status: 'active' },
            { code: 'BSCS', name: 'BS in Computer Science', status: 'active' },
            { code: 'BSCpE', name: 'BS in Computer Engineering', status: 'inactive' }
        ],
        'BSA': [
            { code: 'BSBA', name: 'BS in Business Administration', status: 'active' },
            { code: 'BSA', name: 'BS in Accountancy', status: 'active' },
            { code: 'BSAIS', name: 'BS in Accounting Information System', status: 'inactive' },
            { code: 'BMMA', name: 'Bachelor of Multimedia Arts', status: 'active' },
            { code: 'BAC', name: 'BA in Communication', status: 'active' }
        ],
        'THM': [
            { code: 'BSHM', name: 'BS in Hospitality Management', status: 'active' },
            { code: 'BSCM', name: 'BS in Culinary Management', status: 'active' },
            { code: 'BSTM', name: 'BS in Tourism Management', status: 'inactive' }
        ],
        'ACADEMIC': [
            { code: 'ABM', name: 'Accountancy, Business, Management', status: 'active' },
            { code: 'STEM', name: 'Science, Technology, Engineering, and Mathematics', status: 'active' },
            { code: 'HUMSS', name: 'Humanities and Social Sciences', status: 'active' },
            { code: 'GA', name: 'General Academic', status: 'inactive' }
        ],
        'TVL': [
            { code: 'DIGITAL_ARTS', name: 'Digital Arts', status: 'active' },
            { code: 'MAWD', name: 'IT in Mobile app and Web development (MAWD)', status: 'inactive' }
        ],
        'HOME_ECON': [
            { code: 'TOURISM_OPS', name: 'Tourism Operations', status: 'active' },
            { code: 'RESTAURANT_OPS', name: 'Restaurant and Cafe Operations', status: 'active' },
            { code: 'CULINARY_ARTS', name: 'Culinary Arts', status: 'inactive' }
        ]
    };
    
    const courses = departmentCourses[departmentId] || [];
    displayDepartmentCourses(courses);
}

function displayDepartmentCourses(courses) {
    const coursesList = document.getElementById('departmentCoursesList');
    const noCoursesMessage = document.getElementById('noCoursesMessage');
    
    if (courses.length === 0) {
        coursesList.style.display = 'none';
        noCoursesMessage.style.display = 'block';
        return;
    }
    
    coursesList.style.display = 'flex';
    noCoursesMessage.style.display = 'none';
    
    coursesList.innerHTML = '';
    
    courses.forEach(course => {
        const courseItem = document.createElement('div');
        courseItem.className = 'course-item';
        courseItem.innerHTML = `
            <div class="course-info">
                <i class="fas fa-graduation-cap course-icon"></i>
                <div class="course-details">
                    <div class="course-code">${course.code}</div>
                    <div class="course-name">${course.name}</div>
                </div>
            </div>
            <div class="course-actions">
                <label class="course-status-toggle">
                    <input type="checkbox" ${course.status === 'active' ? 'checked' : ''} 
                           onchange="toggleCourseStatus('${course.code}', this.checked)">
                    <span class="course-status-slider"></span>
                </label>
                <button class="remove-course-btn" onclick="removeCourse('${course.code}')">
                    <i class="fas fa-trash"></i>
                    Remove
                </button>
            </div>
        `;
        coursesList.appendChild(courseItem);
    });
}

function removeCourse(courseCode) {
    showConfirmationModal(
        `Are you sure you want to remove the course "${courseCode}"?`,
        'This action cannot be undone. The course will be permanently removed from this department.',
        'Remove Course',
        'Cancel',
        () => {
            // Simulate API call to remove course
            console.log('Removing course:', courseCode);
            
            // Show success message
            showToastNotification(`Course ${courseCode} removed successfully`, 'success');
            
            // Refresh the courses list
            const departmentId = document.getElementById('editDepartmentId').value;
            loadDepartmentCourses(departmentId);
        },
        'danger'
    );
}

function toggleCourseStatus(courseCode, isActive) {
    const newStatus = isActive ? 'active' : 'inactive';
    
    // Simulate API call to update course status
    console.log('Updating course status:', courseCode, 'to', newStatus);
    
    // Show success message
    const statusText = isActive ? 'activated' : 'deactivated';
    showToastNotification(`Course ${courseCode} ${statusText} successfully`, 'success');
}
</script> 