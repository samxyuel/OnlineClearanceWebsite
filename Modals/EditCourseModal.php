<!-- Edit Course Modal -->
<div id="editCourseModal" class="modal-overlay" style="display: none;">
    <div class="modal-window" style="max-width: 600px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeEditCourseModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">✏️ Edit Course</h2>
            <div class="modal-supporting-text">Update course information and settings</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="editCourseForm">
                <input type="hidden" id="editCourseId" name="courseId">
                
                <div class="form-group">
                    <label for="editCourseCode">Course Code *</label>
                    <input type="text" id="editCourseCode" name="courseCode" required 
                           placeholder="Enter course code">
                </div>
                
                <div class="form-group">
                    <label for="editCourseName">Course Name *</label>
                    <input type="text" id="editCourseName" name="courseName" required 
                           placeholder="Enter course name">
                </div>
                
                <div class="form-group">
                    <label for="editCourseDepartment">Department *</label>
                    <select id="editCourseDepartment" name="courseDepartment" required>
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
                    <label for="editCourseStatus">Status *</label>
                    <select id="editCourseStatus" name="courseStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editCourseDescription">Description</label>
                    <textarea id="editCourseDescription" name="courseDescription" 
                              placeholder="Enter course description (optional)"></textarea>
                </div>
            </form>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeEditCourseModal()">Cancel</button>
            <button class="btn btn-primary" onclick="updateCourse()">Update Course</button>
        </div>
    </div>
</div>

<style>
/* Enhanced form styling for edit course modal */
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
</style>

<script>
function openEditCourseModalInternal(courseCode) {
    try {
        const modal = document.getElementById('editCourseModal');
        if (!modal) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('Edit course modal not found. Please refresh the page.', 'error');
            }
            return;
        }

        // Simulate fetching course data
        const courseData = getCourseData(courseCode);
        
        // Populate form fields
        const courseIdField = document.getElementById('editCourseId');
        const courseCodeField = document.getElementById('editCourseCode');
        const courseNameField = document.getElementById('editCourseName');
        const courseDeptField = document.getElementById('editCourseDepartment');
        const courseStatusField = document.getElementById('editCourseStatus');
        const courseDescField = document.getElementById('editCourseDescription');
        
        if (courseIdField) courseIdField.value = courseCode;
        if (courseCodeField) courseCodeField.value = courseData.code;
        if (courseNameField) courseNameField.value = courseData.name;
        if (courseDeptField) courseDeptField.value = courseData.department;
        if (courseStatusField) courseStatusField.value = courseData.status;
        if (courseDescField) courseDescField.value = courseData.description || '';
        
        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            window.openModal('editCourseModal');
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
            showToastNotification('Unable to open edit course modal. Please try again.', 'error');
        }
    }
}

// Global function for external access
window.openEditCourseModalInternal = openEditCourseModalInternal;

window.closeEditCourseModal = function() {
    console.log('[EditCourseModal] closeEditCourseModal() called');
    try {
        const modal = document.getElementById('editCourseModal');
        if (!modal) {
            console.warn('[EditCourseModal] Modal not found');
            return;
        }
        console.log('[EditCourseModal] Closing modal:', modal.id);

        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('editCourseModal');
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

function updateCourse() {
    const form = document.getElementById('editCourseForm');
    
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
        id: formData.get('courseId'),
        code: formData.get('courseCode'),
        name: formData.get('courseName'),
        department: formData.get('courseDepartment'),
        status: formData.get('courseStatus'),
        description: formData.get('courseDescription')
    };
    
    // Show loading notification
    showToastNotification('Updating course...', 'info', 2000);
    
    // Simulate API call
    console.log('Updating course:', courseData);
    
    setTimeout(() => {
        // Show success message
        showToastNotification('Course updated successfully!', 'success', 3000);
        
        // Close modal
        closeEditCourseModal();
        
        // Refresh the page or update the UI
        setTimeout(() => {
            location.reload();
        }, 1000);
    }, 1500);
}

function getCourseData(courseCode) {
    // Simulate API call to get course data
    const courses = {
        'BSIT': {
            code: 'BSIT',
            name: 'BS in Information Technology',
            department: 'ICT',
            status: 'active',
            description: 'Bachelor of Science in Information Technology'
        },
        'BSCS': {
            code: 'BSCS',
            name: 'BS in Computer Science',
            department: 'ICT',
            status: 'active',
            description: 'Bachelor of Science in Computer Science'
        },
        'BSCpE': {
            code: 'BSCpE',
            name: 'BS in Computer Engineering',
            department: 'ICT',
            status: 'active',
            description: 'Bachelor of Science in Computer Engineering'
        },
        'BSBA': {
            code: 'BSBA',
            name: 'BS in Business Administration',
            department: 'BSA',
            status: 'active',
            description: 'Bachelor of Science in Business Administration'
        },
        'BSA': {
            code: 'BSA',
            name: 'BS in Accountancy',
            department: 'BSA',
            status: 'active',
            description: 'Bachelor of Science in Accountancy'
        },
        'BSAIS': {
            code: 'BSAIS',
            name: 'BS in Accounting Information System',
            department: 'BSA',
            status: 'active',
            description: 'Bachelor of Science in Accounting Information System'
        },
        'BMMA': {
            code: 'BMMA',
            name: 'Bachelor of Multimedia Arts',
            department: 'BSA',
            status: 'active',
            description: 'Bachelor of Multimedia Arts'
        },
        'BAC': {
            code: 'BAC',
            name: 'BA in Communication',
            department: 'BSA',
            status: 'active',
            description: 'Bachelor of Arts in Communication'
        },
        'BSHM': {
            code: 'BSHM',
            name: 'BS in Hospitality Management',
            department: 'THM',
            status: 'active',
            description: 'Bachelor of Science in Hospitality Management'
        },
        'BSCM': {
            code: 'BSCM',
            name: 'BS in Culinary Management',
            department: 'THM',
            status: 'active',
            description: 'Bachelor of Science in Culinary Management'
        },
        'BSTM': {
            code: 'BSTM',
            name: 'BS in Tourism Management',
            department: 'THM',
            status: 'active',
            description: 'Bachelor of Science in Tourism Management'
        },
        'ABM': {
            code: 'ABM',
            name: 'Accountancy, Business, Management',
            department: 'ACADEMIC',
            status: 'active',
            description: 'Accountancy, Business, Management Track'
        },
        'STEM': {
            code: 'STEM',
            name: 'Science, Technology, Engineering, and Mathematics',
            department: 'ACADEMIC',
            status: 'active',
            description: 'Science, Technology, Engineering, and Mathematics Track'
        },
        'HUMSS': {
            code: 'HUMSS',
            name: 'Humanities and Social Sciences',
            department: 'ACADEMIC',
            status: 'active',
            description: 'Humanities and Social Sciences Track'
        },
        'GA': {
            code: 'GA',
            name: 'General Academic',
            department: 'ACADEMIC',
            status: 'active',
            description: 'General Academic Track'
        },
        'DIGITAL_ARTS': {
            code: 'DIGITAL ARTS',
            name: 'Digital Arts',
            department: 'TVL',
            status: 'active',
            description: 'Digital Arts Track'
        },
        'MAWD': {
            code: 'MAWD',
            name: 'IT in Mobile app and Web development (MAWD)',
            department: 'TVL',
            status: 'active',
            description: 'IT in Mobile app and Web development Track'
        },
        'TOURISM_OPS': {
            code: 'TOURISM OPS',
            name: 'Tourism Operations',
            department: 'HOME_ECON',
            status: 'active',
            description: 'Tourism Operations Track'
        },
        'RESTAURANT_OPS': {
            code: 'RESTAURANT OPS',
            name: 'Restaurant and Cafe Operations',
            department: 'HOME_ECON',
            status: 'active',
            description: 'Restaurant and Cafe Operations Track'
        },
        'CULINARY_ARTS': {
            code: 'CULINARY ARTS',
            name: 'Culinary Arts',
            department: 'HOME_ECON',
            status: 'active',
            description: 'Culinary Arts Track'
        }
    };
    
    return courses[courseCode] || {
        code: '',
        name: '',
        department: '',
        status: 'active',
        description: ''
    };
}
</script> 