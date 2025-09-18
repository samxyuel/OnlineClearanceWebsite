<?php
// Add New Signatory Modal
?>

<div class="modal-overlay add-signatory-modal-overlay" style="display: none;">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeAddSignatoryModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title" id="addSignatoryModalTitle">Add New Signatory</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Select a position to add to the signatory list.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="addSignatoryForm">
                
                <!-- Signatory Type -->
                <div class="form-group">
                    <label for="signatoryType">Signatory Type</label>
                    <select id="signatoryType" name="signatoryType" class="form-control" onchange="updatePositionOptions()">
                        <option value="">Select type...</option>
                        <option value="student">Student Clearance</option>
                        <option value="faculty">Faculty Clearance</option>
                    </select>
                </div>
                
                <!-- Position Selection -->
                <div class="form-group">
                    <label for="signatoryPosition">Position</label>
                    <select id="signatoryPosition" name="signatoryPosition" class="form-control">
                        <option value="">Select a position...</option>
                    </select>
                    <small class="form-help">Choose the staff position to add as a signatory</small>
                </div>
                
                <!-- Validation Messages -->
                <div class="validation-messages" id="addSignatoryValidationMessages">
                    <!-- Dynamic validation messages will appear here -->
                </div>
                
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeAddSignatoryModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveNewSignatory()">
                <i class="fas fa-plus"></i> Add Signatory
            </button>
        </div>
    </div>
</div>

<script>
// Add New Signatory Modal Functions
window.showAddSignatoryModal = function(type) {
    const modal = document.querySelector('.add-signatory-modal-overlay');
    const signatoryType = document.getElementById('signatoryType');
    const modalTitle = document.getElementById('addSignatoryModalTitle');
    
    // Set the signatory type based on context
    if (type) {
        signatoryType.value = type;
        
        // Update modal title based on context
        if (type === 'student') {
            modalTitle.textContent = 'Add Student Signatory';
        } else if (type === 'faculty') {
            modalTitle.textContent = 'Add Faculty Signatory';
        }
        
        // Update position options immediately
        updatePositionOptions();
    }
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Focus on the first input
    setTimeout(() => {
        const firstInput = modal.querySelector('input, select');
        if (firstInput) firstInput.focus();
    }, 100);
};

window.closeAddSignatoryModal = function() {
    const modal = document.querySelector('.add-signatory-modal-overlay');
    const modalTitle = document.getElementById('addSignatoryModalTitle');
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset form and title
    document.getElementById('addSignatoryForm').reset();
    document.getElementById('addSignatoryValidationMessages').innerHTML = '';
    modalTitle.textContent = 'Add New Signatory';
};

window.updatePositionOptions = function() {
    const signatoryType = document.getElementById('signatoryType').value;
    const positionSelect = document.getElementById('signatoryPosition');
    
    // Clear current options
    positionSelect.innerHTML = '<option value="">Select a position...</option>';
    
    if (signatoryType === 'student') {
        // Student clearance positions
        const studentPositions = [
            'Security Officer',
            'IT Support',
            'Academic Advisor',
            'Student Affairs Coordinator',
            'Library Assistant',
            'Computer Laboratory (MIS/IT)',
            'Disciplinary Officer',
            'Guidance Counselor',
            'Student Affairs Officer',
            'Academic Head',
            'Department Head',
            'Research Coordinator',
            'Quality Assurance Officer',
            'Training Coordinator',
            'Assessment Officer'
        ];
        
        studentPositions.forEach(position => {
            const option = document.createElement('option');
            option.value = position;
            option.textContent = position;
            positionSelect.appendChild(option);
        });
        
    } else if (signatoryType === 'faculty') {
        // Faculty clearance positions
        const facultyPositions = [
            'PAMO',
            'Guidance Counselor',
            'MIS/IT',
            'Petty Cash Custodian',
            'Building Administrator',
            'Club Supervisor',
            'Homeroom Supervisor',
            'Discipline Officer',
            'Student Affairs Officer',
            'Academic Head',
            'SHS Asst. Principal',
            'HR Officer',
            'School Administrator',
            'Department Head',
            'Research Coordinator',
            'Quality Assurance Officer',
            'Training Coordinator',
            'Assessment Officer'
        ];
        
        facultyPositions.forEach(position => {
            const option = document.createElement('option');
            option.value = position;
            option.textContent = position;
            positionSelect.appendChild(option);
        });
    }
};

window.saveNewSignatory = function() {
    const signatoryType = document.getElementById('signatoryType').value;
    const signatoryPosition = document.getElementById('signatoryPosition').value;
    const validationMessages = document.getElementById('addSignatoryValidationMessages');
    
    // Clear previous validation messages
    validationMessages.innerHTML = '';
    
    // Validation
    if (!signatoryPosition) {
        validationMessages.innerHTML = '<div class="validation-error">Please select a position from the dropdown.</div>';
        return;
    }
    
    // Add the signatory to the appropriate list
    addSignatoryToList(signatoryType, signatoryPosition);
    
    // Close modal and show success message
    closeAddSignatoryModal();
    showToast(`Added ${signatoryPosition} to ${signatoryType} clearance signatories.`, 'success');
};

window.addSignatoryToList = function(type, position) {
    // Find the appropriate signatory list
    const signatoryList = document.querySelector(`#${type}-signatories .signatory-list`);
    
    if (signatoryList) {
        // Create new signatory item
        const newSignatoryItem = document.createElement('div');
        newSignatoryItem.className = 'signatory-item optional';
        newSignatoryItem.setAttribute('data-position', position);
        
        newSignatoryItem.innerHTML = `
            <span class="signatory-name">${position}</span>
            <button class="remove-signatory" onclick="removeSignatory('${type}', '${position}')">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Insert before the last required item (if any)
        const requiredLast = signatoryList.querySelector('.required-last');
        if (requiredLast) {
            signatoryList.insertBefore(newSignatoryItem, requiredLast);
        } else {
            signatoryList.appendChild(newSignatoryItem);
        }
    }
};

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.querySelector('.add-signatory-modal-overlay');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAddSignatoryModal();
            }
        });
    }
});
</script> 