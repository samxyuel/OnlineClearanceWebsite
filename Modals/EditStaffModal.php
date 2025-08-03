<?php
// Edit Staff Modal
?>

<div class="modal-overlay edit-staff-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeEditStaffModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Edit Staff Member</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Update staff member information. Employee ID cannot be changed.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="editStaffForm" data-endpoint="../../controllers/updateStaff.php">
                <input type="hidden" name="type" value="staff">
                
                <div class="form-group">
                    <label for="editEmployeeId">Employee ID</label>
                    <input type="text" id="editEmployeeId" name="employeeId" readonly 
                           style="background-color: #f8f9fa; color: #6c757d;">
                    <small class="form-help">Employee ID cannot be modified</small>
                </div>
                
                <div class="form-group">
                    <label for="editStaffName">Full Name</label>
                    <input type="text" id="editStaffName" name="staffName" placeholder="John Smith" required>
                </div>
                
                <div class="form-group">
                    <label for="editStaffPosition">Position</label>
                    <select id="editStaffPosition" name="staffPosition">
                        <option value="">Select Standard Position (or leave blank for custom)</option>
                        <optgroup label="Standard Positions">
                            <option value="Guidance">Guidance</option>
                            <option value="Disciplinary Officer">Disciplinary Officer</option>
                            <option value="Clinic">Clinic</option>
                            <option value="Librarian">Librarian</option>
                            <option value="Alumni Placement Officer">Alumni Placement Officer</option>
                            <option value="Student's Affairs Officer">Student's Affairs Officer</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Program Head">Program Head</option>
                            <option value="PAMO">PAMO</option>
                            <option value="MIS/IT">MIS/IT</option>
                            <option value="Petty Cash Custodian">Petty Cash Custodian</option>
                            <option value="Building Administrator">Building Administrator</option>
                            <option value="Accountant">Accountant</option>
                            <option value="Academic Head">Academic Head</option>
                            <option value="School Administrator">School Administrator</option>
                            <option value="HR">HR</option>
                        </optgroup>
                    </select>
                    <div class="custom-position-container">
                        <label for="editCustomPosition">Custom Position</label>
                        <input type="text" id="editCustomPosition" name="editCustomPosition" 
                               placeholder="Type custom position if not in standard list above...">
                        <small class="form-help">Only fill this if you didn't select a standard position above</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="editStaffDepartment">Department</label>
                    <select id="editStaffDepartment" name="staffDepartment" required>
                        <option value="">Select Department</option>
                        <option value="Administration">Administration</option>
                        <option value="Finance">Finance</option>
                        <option value="Student Services">Student Services</option>
                        <option value="Library">Library</option>
                        <option value="IT">IT</option>
                        <option value="Academic">Academic</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Facilities">Facilities</option>
                        <option value="Health Services">Health Services</option>
                        <option value="Alumni Relations">Alumni Relations</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editStaffEmail">Email Address</label>
                    <input type="email" id="editStaffEmail" name="staffEmail" 
                           placeholder="john.smith@gosti.edu.ph" required>
                </div>
                
                <div class="form-group">
                    <label for="editStaffContact">Contact Number</label>
                    <input type="tel" id="editStaffContact" name="staffContact" 
                           placeholder="+63 912 345 6789" required>
                </div>
                
                <div class="form-group">
                    <label for="editStaffStatus">Staff Status</label>
                    <select id="editStaffStatus" name="staffStatus" required>
                        <option value="">Select Status</option>
                        <option value="essential">Essential Staff</option>
                        <option value="optional">Optional Staff</option>
                    </select>
                    <small class="form-help">Essential staff cannot be deleted and are critical for clearance workflow.</small>
                </div>
            </form>
        </div>
        
        <!-- Actions -->
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeEditStaffModal()">Cancel</button>
            <button class="modal-action-primary" onclick="submitEditStaffForm()">Update Staff</button>
        </div>
    </div>
</div>

<script>
// Make functions globally accessible
window.closeEditStaffModal = function() {
    const modal = document.querySelector('.edit-staff-modal-overlay');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        // Reset form
        document.getElementById('editStaffForm').reset();
    }
};

window.submitEditStaffForm = function() {
    const form = document.getElementById('editStaffForm');
    const formData = new FormData(form);
    
    // Handle position logic
    const positionSelect = document.getElementById('editStaffPosition');
    const customPositionInput = document.getElementById('editCustomPosition');
    
    const standardPosition = positionSelect.value.trim();
    const customPosition = customPositionInput.value.trim();
    
    // Validation: Must have either standard position OR custom position, not both
    if (!standardPosition && !customPosition) {
        showToast('Please select a standard position or enter a custom position.', 'error');
        return;
    }
    
    if (standardPosition && customPosition) {
        showToast('Please select either a standard position OR enter a custom position, not both.', 'error');
        return;
    }
    
    // Set the final position value
    const finalPosition = standardPosition || customPosition;
    formData.set('staffPosition', finalPosition);
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Convert to JSON
    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });
    jsonData['role_id'] = 4; // Staff role
    
    // Submit form
    fetch(form.dataset.endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Staff member updated successfully!', 'success');
            closeEditStaffModal();
            // Optionally reload the page or update the cards
            location.reload();
        } else {
            showToast(data.message || 'Failed to update staff member.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating staff member.', 'error');
    });
};

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const contactInput = document.getElementById('editStaffContact');
    if (contactInput) {
        contactInput.addEventListener('input', function() {
            // Format phone number
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '+63 ' + value.substring(2);
            } else if (value.startsWith('09')) {
                value = '+63 ' + value.substring(1);
            }
            this.value = value;
        });
    }
    
    // Handle position field validation
    const positionSelect = document.getElementById('editStaffPosition');
    const customPositionInput = document.getElementById('editCustomPosition');
    
    if (positionSelect && customPositionInput) {
        // Clear custom position when standard position is selected
        positionSelect.addEventListener('change', function() {
            if (this.value.trim()) {
                customPositionInput.value = '';
            }
        });
        
        // Clear standard position when custom position is entered
        customPositionInput.addEventListener('input', function() {
            if (this.value.trim()) {
                positionSelect.value = '';
            }
        });
    }
});
</script> 