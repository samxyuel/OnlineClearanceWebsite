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
                
                <!-- Cross-Sector Warning Banner -->
                <div id="crossSectorWarning" class="cross-sector-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Cross-Sector Department</strong>
                        <p id="crossSectorSectors"></p>
                        <p class="warning-text">Updating name or code will affect all sectors where this department exists.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentName">Department Name *</label>
                    <input type="text" id="editDepartmentName" name="departmentName" required 
                           placeholder="Enter department name">
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentCode">Department Code *</label>
                    <input type="text" id="editDepartmentCode" name="departmentCode" required
                           placeholder="e.g., ICT" maxlength="10" pattern="[A-Z0-9]+"
                           title="Uppercase letters and numbers only"
                           style="text-transform: uppercase;">
                    <small class="form-help">Must be unique across all sectors</small>
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentType">Department Type *</label>
                    <select id="editDepartmentType" name="departmentType" required>
                        <option value="">Select department type</option>
                        <option value="college">College</option>
                        <option value="senior-high">Senior High School</option>
                        <option value="faculty">Faculty Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editDepartmentStatus">Status *</label>
                    <select id="editDepartmentStatus" name="departmentStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
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
async function openEditDepartmentModalInternal(departmentId) {
    try {
        const modal = document.getElementById('editDepartmentModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('Edit department modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        // Fetch department with cross-sector info
        const response = await fetch('../../api/departments/list.php?limit=500', {
            credentials: 'include'
        });
        const data = await response.json();
        
        // Find the department
        let department = null;
        data.departments.forEach(dept => {
            dept.sectors.forEach(sector => {
                if (sector.department_id == departmentId) {
                    department = { 
                        ...dept, 
                        current_department_id: sector.department_id,
                        current_sector_id: sector.sector_id
                    };
                }
            });
        });
        
        if (!department) {
            showToastNotification('Department not found', 'error');
            return;
        }
        
        // Populate form
        document.getElementById('editDepartmentId').value = department.current_department_id;
        document.getElementById('editDepartmentName').value = department.department_name;
        document.getElementById('editDepartmentCode').value = department.department_code || '';
        
        // Set department type based on sectors
        const hasCollege = department.sectors.some(s => s.sector_id === 1);
        const hasSHS = department.sectors.some(s => s.sector_id === 2);
        const hasFaculty = department.sectors.some(s => s.sector_id === 3);
        
        let deptType = '';
        if (hasCollege && hasFaculty && !hasSHS) {
            deptType = 'college';
        } else if (hasSHS && hasFaculty && !hasCollege) {
            deptType = 'senior-high';
        } else if (hasFaculty && !hasCollege && !hasSHS) {
            deptType = 'faculty';
        }
        
        document.getElementById('editDepartmentType').value = deptType;
        document.getElementById('editDepartmentStatus').value = department.is_active ? 'active' : 'inactive';
        
        // Show cross-sector warning if shared
        const warningDiv = document.getElementById('crossSectorWarning');
        const sectorsP = document.getElementById('crossSectorSectors');
        if (department.is_shared && department.sectors.length > 1) {
            const sectorsList = department.sectors.map(s => s.sector_name).join(', ');
            sectorsP.textContent = `This department exists in: ${sectorsList}`;
            warningDiv.style.display = 'flex';
        } else {
            warningDiv.style.display = 'none';
        }
        
        // Show/hide course management section based on eligibility
        const courseSection = document.querySelector('.course-management-section');
        const canAddCourses = department.sectors.some(s => s.sector_id === 1 || s.sector_id === 2);
        
        if (courseSection) {
            if (canAddCourses) {
                courseSection.style.display = 'block';
                loadDepartmentCourses(departmentId);
            } else {
                courseSection.style.display = 'none';
                // Show faculty-only message
                const noCoursesMsg = document.getElementById('noCoursesMessage');
                if (noCoursesMsg) {
                    noCoursesMsg.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        <p>This is a faculty-only department. Courses are not applicable.</p>
                    `;
                    noCoursesMsg.style.display = 'block';
                }
            }
        }
        
        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('editDepartmentModal');
        } else {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }
    } catch (error) {
        console.error('Error loading department data:', error);
        if (typeof showToastNotification === 'function') {
            showToastNotification('Unable to load department data. Please try again.', 'error');
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
        status: formData.get('departmentStatus')
    };
    
    // Show loading notification
    showToastNotification('Updating department...', 'info', 2000);
    
    // Call API to update department
    fetch('../../api/departments/update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(departmentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message || 'Department updated successfully!';
            if (data.is_cross_sector) {
                message += ` (Updated ${data.updated_records} sector records)`;
            }
            showToastNotification(message, 'success', 3000);
            
            // Close modal
            closeEditDepartmentModal();
            
            // Refresh the page
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToastNotification(
                data.message || 'Failed to update department', 
                'error', 
                4000
            );
        }
    })
    .catch(error => {
        console.error('Error updating department:', error);
        showToastNotification('An error occurred while updating the department', 'error', 4000);
    });
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

async function loadDepartmentCourses(departmentId) {
    const coursesList = document.getElementById('departmentCoursesList');
    const noCoursesMessage = document.getElementById('noCoursesMessage');
    
    // Show loading state
    if (coursesList) {
        coursesList.innerHTML = '<div class="loading-spinner" style="text-align: center; padding: 20px; color: var(--medium-muted-blue);"><i class="fas fa-spinner fa-spin" style="font-size: 1.5rem;"></i><p style="margin-top: 10px;">Loading courses...</p></div>';
        coursesList.style.display = 'flex';
    }
    if (noCoursesMessage) {
        noCoursesMessage.style.display = 'none';
    }
    
    try {
        const response = await fetch(`../../api/programs/list.php?department_id=${departmentId}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.programs) {
            // Map API response to expected format
            const courses = data.programs.map(program => ({
                code: program.program_code,
                name: program.program_name,
                status: program.is_active ? 'active' : 'inactive',
                program_id: program.program_id
            }));
            
            displayDepartmentCourses(courses);
        } else {
            throw new Error(data.message || 'Failed to load courses');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        if (coursesList) {
            coursesList.style.display = 'none';
        }
        if (noCoursesMessage) {
            noCoursesMessage.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error loading courses. Please try again.</p>
            `;
            noCoursesMessage.style.display = 'block';
        }
    }
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
                <button class="remove-course-btn" onclick="removeCourse(${course.program_id}, '${course.code}', '${course.name}')">
                    <i class="fas fa-trash"></i>
                    Remove
                </button>
            </div>
        `;
        coursesList.appendChild(courseItem);
    });
}

function removeCourse(programId, courseCode, courseName) {
    const label = courseName ? `${courseCode} - ${courseName}` : courseCode;
    showConfirmationModal(
        `Are you sure you want to remove the course "${label}"?`,
        'This action cannot be undone. The course will be permanently removed from this department.',
        'Remove Course',
        'Cancel',
        () => {
            showToastNotification('Removing course...', 'info', 1500);
            
            // Call API to delete course
            fetch('../../api/programs/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({ program_id: programId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToastNotification(
                        data.message || `Course ${courseCode} removed successfully`, 
                        'success', 
                        3000
                    );
                    
                    // Refresh the courses list
                    const departmentId = document.getElementById('editDepartmentId').value;
                    loadDepartmentCourses(departmentId);
                } else {
                    showToastNotification(
                        data.message || 'Failed to remove course', 
                        'error', 
                        4000
                    );
                }
            })
            .catch(error => {
                console.error('Error removing course:', error);
                showToastNotification('An error occurred while removing the course', 'error', 4000);
            });
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