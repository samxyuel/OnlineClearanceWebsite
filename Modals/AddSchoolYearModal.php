<?php
// Add School Year Modal
?>

<div class="modal-overlay add-school-year-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeAddSchoolYearModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Add New School Year</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Configure the new school year with flexible term management.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="addSchoolYearForm">
                
                <!-- School Year Name -->
                <div class="form-group">
                    <label for="schoolYearName">School Year Name</label>
                    <input type="text" id="schoolYearName" name="schoolYearName" required 
                           placeholder="e.g., 2025-2026" pattern="\d{4}-\d{4}">
                    <small class="form-help">Format: YYYY-YYYY (e.g., 2025-2026)</small>
                </div>
                
                <!-- Number of Terms -->
                <div class="form-group">
                    <label>Number of Terms</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="twoTerms" name="termCount" value="2" checked>
                            <label for="twoTerms">
                                <div class="term-option-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <strong>2 Terms (Recommended)</strong>
                                        <small>Standard academic year structure</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="threeTerms" name="termCount" value="3">
                            <label for="threeTerms">
                                <div class="term-option-info">
                                    <i class="fas fa-calendar-check"></i>
                                    <div>
                                        <strong>3 Terms (Full Academic Year)</strong>
                                        <small>Complete academic year with summer term</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Term Preview -->
                <div class="form-group">
                    <label>Term Configuration Preview</label>
                    <div class="term-preview">
                        <div class="preview-item">
                            <span class="preview-label">Term 1:</span>
                            <span class="preview-value">[Inactive] - Will be activated manually</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Term 2:</span>
                            <span class="preview-value">[Inactive] - Will be activated manually</span>
                        </div>
                        <div class="preview-item" id="term3Preview" style="display: none;">
                            <span class="preview-label">Term 3:</span>
                            <span class="preview-value">[Inactive] - Will be activated manually</span>
                        </div>
                    </div>
                    <small class="form-help">Terms will be created but remain inactive until you manually activate them when ready.</small>
                </div>
                
                <!-- Validation Messages -->
                <div class="validation-messages" id="addSchoolYearValidationMessages">
                    <!-- Dynamic validation messages will appear here -->
                </div>
                
                <!-- Important Note -->
                <div class="modal-notice">
                    <div class="notice-content">
                        <i class="fas fa-info-circle"></i>
                        <div class="notice-text">
                            <strong>Note:</strong> The school year will automatically end when the calendar year changes (e.g., 2025-2026 ends when 2027 starts). You can also manually end it anytime during the year.
                        </div>
                    </div>
                </div>
                
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeAddSchoolYearModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="createSchoolYear()">
                <i class="fas fa-plus"></i> Create School Year
            </button>
        </div>
    </div>
</div>

<script>
// Open add school year modal
window.showAddSchoolYearModal = function() {
    // Show modal
    document.querySelector('.add-school-year-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Auto-generate school year name
    const currentYear = new Date().getFullYear();
    const nextYear = currentYear + 1;
    const schoolYearName = `${nextYear}-${nextYear + 1}`;
    
    document.getElementById('schoolYearName').value = schoolYearName;
    
    // Initialize term preview
    updateTermPreview();
    
    // Add event listeners
    addSchoolYearModalEventListeners();
};

// Close add school year modal
window.closeAddSchoolYearModal = function() {
    document.querySelector('.add-school-year-modal-overlay').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('addSchoolYearForm').reset();
};

// Update term preview based on selection
function updateTermPreview() {
    const termCount = document.querySelector('input[name="termCount"]:checked').value;
    const term3Preview = document.getElementById('term3Preview');
    
    if (termCount === '3') {
        term3Preview.style.display = 'flex';
    } else {
        term3Preview.style.display = 'none';
    }
}

// Add event listeners for the modal
function addSchoolYearModalEventListeners() {
    // Term count radio buttons
    const termCountRadios = document.querySelectorAll('input[name="termCount"]');
    termCountRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateTermPreview();
            updateRadioVisualState(this);
        });
        
        // Initialize visual state
        updateRadioVisualState(radio);
    });
    
    // School year name validation
    const schoolYearInput = document.getElementById('schoolYearName');
    schoolYearInput.addEventListener('input', function() {
        validateSchoolYearName(this.value);
    });
}

// Validate school year name
function validateSchoolYearName(value) {
    const pattern = /^\d{4}-\d{4}$/;
    const isValid = pattern.test(value);
    
    const input = document.getElementById('schoolYearName');
    const validationMessages = document.getElementById('addSchoolYearValidationMessages');
    
    // Clear previous messages
    validationMessages.innerHTML = '';
    
    if (!value) {
        input.classList.remove('error', 'success');
        return false;
    }
    
    if (!isValid) {
        input.classList.remove('success');
        input.classList.add('error');
        validationMessages.innerHTML = '<div class="validation-error">Please enter a valid school year name (YYYY-YYYY format).</div>';
        return false;
    }
    
    // Check if school year already exists
    const existingYear = schoolYears.find(year => year.name === value);
    if (existingYear) {
        input.classList.remove('success');
        input.classList.add('error');
        validationMessages.innerHTML = '<div class="validation-error">This school year already exists. You must delete the existing one first.</div>';
        return false;
    }
    
    input.classList.remove('error');
    input.classList.add('success');
    return true;
}

// Update radio button visual state
function updateRadioVisualState(radio) {
    // Remove selected class from all radio options in the same group
    const radioGroup = radio.closest('.radio-group');
    const radioOptions = radioGroup.querySelectorAll('.radio-option');
    radioOptions.forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to the checked radio option
    const radioOption = radio.closest('.radio-option');
    if (radio.checked) {
        radioOption.classList.add('selected');
    }
}

// Create school year
window.createSchoolYear = function() {
    const form = document.getElementById('addSchoolYearForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!validateSchoolYearForm(formData)) {
        return;
    }
    
    // Show loading state
    const createBtn = document.querySelector('.modal-actions .btn-primary');
    const originalText = createBtn.innerHTML;
    createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    createBtn.disabled = true;
    
    // Simulate creation process
    setTimeout(() => {
        const schoolYearName = formData.get('schoolYearName');
        const termCount = formData.get('termCount');
        
        // Here you would typically send the data to the server
        console.log('Creating school year:', { schoolYearName, termCount });
        
        // Add to local data structure (for demo purposes)
        addSchoolYearToData(schoolYearName, parseInt(termCount));
        
        // Show success message
        showToast(`School Year ${schoolYearName} created successfully!`, 'success');
        
        // Close modal
        closeAddSchoolYearModal();
        
        // Reset button
        createBtn.innerHTML = originalText;
        createBtn.disabled = false;
    }, 1500);
};

// Validate school year form
function validateSchoolYearForm(formData) {
    const schoolYearName = formData.get('schoolYearName');
    const termCount = formData.get('termCount');
    
    if (!schoolYearName) {
        showToast('Please enter a school year name.', 'error');
        return false;
    }
    
    if (!validateSchoolYearName(schoolYearName)) {
        showToast('Please enter a valid school year name (YYYY-YYYY format).', 'error');
        return false;
    }
    
    if (!termCount) {
        showToast('Please select the number of terms.', 'error');
        return false;
    }
    
    // Check if school year already exists
    const existingYear = schoolYears.find(year => year.name === schoolYearName);
    if (existingYear) {
        showConfirmation(
            'School Year Already Exists',
            `School Year "${schoolYearName}" already exists in the system. You must delete the existing school year first before creating a new one with the same name.`,
            'Delete Existing',
            'Cancel',
            () => {
                // User chose to delete existing - show confirmation
                showConfirmation(
                    'Delete Existing School Year',
                    `Are you sure you want to delete the existing "${schoolYearName}" school year? This will permanently remove all data associated with this school year.`,
                    'Delete',
                    'Cancel',
                    () => {
                        // Delete the existing school year
                        const yearIndex = schoolYears.findIndex(year => year.name === schoolYearName);
                        if (yearIndex !== -1) {
                            schoolYears.splice(yearIndex, 1);
                            updateSchoolYearDisplay();
                            updateNavigationButtons();
                            showToast(`Deleted existing school year "${schoolYearName}".`, 'success');
                            
                            // Now proceed with creating the new one
                            setTimeout(() => {
                                createSchoolYear();
                            }, 1000);
                        }
                    },
                    'warning'
                );
            },
            'warning'
        );
        return false;
    }
    
    return true;
}

// Add school year to data structure (for demo)
function addSchoolYearToData(schoolYearName, termCount) {
    // Create terms array
    const terms = [];
    for (let i = 1; i <= termCount; i++) {
        terms.push({
            id: `term${i}`,
            name: `Term ${i}`,
            status: 'inactive',
            students: '0/0'
        });
    }
    
    // Add new school year to data
    const newSchoolYear = {
        id: schoolYearName,
        name: schoolYearName,
        status: 'current',
        terms: terms,
        canAddTerm: termCount < 3,
        canAddSchoolYear: false
    };
    
    // Update existing current year to completed
    const currentYearIndex = schoolYears.findIndex(year => year.status === 'current');
    if (currentYearIndex !== -1) {
        schoolYears[currentYearIndex].status = 'completed';
    }
    
    // Add new year to beginning of array (newest first)
    schoolYears.unshift(newSchoolYear);
    
    // Update display
    currentSchoolYearIndex = 0;
    updateSchoolYearDisplay();
    updateNavigationButtons();
}
</script> 