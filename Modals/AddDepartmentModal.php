<!-- Add Department Modal -->
<div id="addDepartmentModal" class="modal-overlay" style="display: none;">
    <div class="modal-window" style="max-width: 600px;">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeAddDepartmentModal()">&times;</button>
        
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">🏢 Add New Department</h2>
            <div class="modal-supporting-text">Create a new department for course organization</div>
        </div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="addDepartmentForm">
                <div class="form-group">
                    <label for="departmentName">Department Name *</label>
                    <input type="text" id="departmentName" name="departmentName" required 
                           placeholder="Enter department name">
                </div>
                
                <div class="form-group">
                    <label for="departmentType">Department Type *</label>
                    <select id="departmentType" name="departmentType" required>
                        <option value="">Select department type</option>
                        <option value="college">College</option>
                        <option value="senior-high">Senior High School</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="departmentStatus">Status *</label>
                    <select id="departmentStatus" name="departmentStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="departmentDescription">Description</label>
                    <textarea id="departmentDescription" name="departmentDescription" 
                              placeholder="Enter department description (optional)"></textarea>
                </div>
            </form>
        </div>
        
        <!-- Modal Actions -->
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeAddDepartmentModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveDepartment()">Add Department</button>
        </div>
    </div>
</div>

<style>
/* Enhanced form styling for department modals */
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
function openAddDepartmentModalInternal() {
    document.getElementById('addDepartmentModal').style.display = 'flex';
    document.getElementById('addDepartmentForm').reset();
}

// Global function for external access
window.openAddDepartmentModalInternal = openAddDepartmentModalInternal;

function closeAddDepartmentModal() {
    document.getElementById('addDepartmentModal').style.display = 'none';
}

function saveDepartment() {
    const form = document.getElementById('addDepartmentForm');
    
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
        name: formData.get('departmentName'),
        type: formData.get('departmentType'),
        status: formData.get('departmentStatus'),
        description: formData.get('departmentDescription')
    };
    
    // Show loading notification
    showToastNotification('Adding department...', 'info', 2000);
    
    // Simulate API call
    console.log('Adding department:', departmentData);
    
    setTimeout(() => {
        // Show success message
        showToastNotification('Department added successfully!', 'success', 3000);
        
        // Close modal
        closeAddDepartmentModal();
        
        // Refresh the page or update the UI
        setTimeout(() => {
            location.reload();
        }, 1000);
    }, 1500);
}
</script> 