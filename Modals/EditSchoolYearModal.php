<?php
// Edit School Year Modal
?>
<script>
console.log('🔍 MODAL: EditSchoolYearModal.php loaded successfully!');
</script>

<div class="modal-overlay edit-school-year-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeEditSchoolYearModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title">Edit School Year</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Modify the term count for this school year.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="editSchoolYearForm">
                
                <!-- School Year Name (Read-only) -->
                <div class="form-group">
                    <label for="editSchoolYearName">School Year Name</label>
                    <input type="text" id="editSchoolYearName" name="editSchoolYearName" readonly>
                    <small class="form-help">School year name cannot be changed</small>
                </div>
                
                <!-- Current Term Count -->
                <div class="form-group">
                    <label>Current Term Count</label>
                    <div class="current-term-display">
                        <span id="currentTermCount">2 Terms</span>
                        <small class="form-help">Currently configured terms</small>
                    </div>
                </div>
                
                <!-- New Term Count -->
                <div class="form-group">
                    <label>New Term Count</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="editTwoTerms" name="editTermCount" value="2">
                            <label for="editTwoTerms">
                                <div class="term-option-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <strong>2 Terms</strong>
                                        <small>Standard academic year structure</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="editThreeTerms" name="editTermCount" value="3">
                            <label for="editThreeTerms">
                                <div class="term-option-info">
                                    <i class="fas fa-calendar-check"></i>
                                    <div>
                                        <strong>3 Terms</strong>
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
                    <div class="term-preview" id="editTermPreview">
                        <div class="preview-item">
                            <span class="preview-label">Term 1:</span>
                            <span class="preview-value">[Current Status]</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Term 2:</span>
                            <span class="preview-value">[Current Status]</span>
                        </div>
                        <div class="preview-item" id="editTerm3Preview" style="display: none;">
                            <span class="preview-label">Term 3:</span>
                            <span class="preview-value">[Inactive] - Will be created</span>
                        </div>
                    </div>
                </div>
                
                <!-- Validation Messages -->
                <div class="validation-messages" id="editValidationMessages">
                    <!-- Dynamic validation messages will appear here -->
                </div>
                
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeEditSchoolYearModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveSchoolYearChanges()">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>
</div>

<script>
// Open edit school year modal
window.showEditSchoolYearModal = function(yearId) {
    // Get current school year data
    const currentYear = schoolYears[currentSchoolYearIndex];
    
    // Check if school year can be edited
    if (hasActiveTerms(currentYear)) {
        showToast('Cannot edit school year with active terms.', 'warning');
        return;
    }
    
    // Show modal
    document.querySelector('.edit-school-year-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Populate form with current data
    document.getElementById('editSchoolYearName').value = currentYear.name;
    document.getElementById('currentTermCount').textContent = `${currentYear.terms.length} Terms`;
    
    // Set current term count as selected
    const currentTermCount = currentYear.terms.length;
    document.querySelector(`input[name="editTermCount"][value="${currentTermCount}"]`).checked = true;
    
    // Update preview
    updateEditTermPreview(currentYear);
    
    // Add event listeners
    addEditSchoolYearModalEventListeners();
};

// Close edit school year modal
window.closeEditSchoolYearModal = function() {
    document.querySelector('.edit-school-year-modal-overlay').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('editSchoolYearForm').reset();
    document.getElementById('editValidationMessages').innerHTML = '';
};

// Update term preview based on selection
function updateEditTermPreview(schoolYear) {
    const termCount = document.querySelector('input[name="editTermCount"]:checked').value;
    const editTerm3Preview = document.getElementById('editTerm3Preview');
    
    // Update existing terms
    const previewItems = document.querySelectorAll('#editTermPreview .preview-item');
    previewItems.forEach((item, index) => {
        if (index < schoolYear.terms.length) {
            const term = schoolYear.terms[index];
            const statusText = term.status === 'active' ? 'Active' : 
                             term.status === 'completed' ? 'Completed' : 'Inactive';
            item.querySelector('.preview-value').textContent = `[${statusText}] - ${term.students}`;
        }
    });
    
    if (termCount === '3') {
        editTerm3Preview.style.display = 'flex';
    } else {
        editTerm3Preview.style.display = 'none';
    }
}

// Add event listeners for the modal
function addEditSchoolYearModalEventListeners() {
    // Term count radio buttons
    const termCountRadios = document.querySelectorAll('input[name="editTermCount"]');
    termCountRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const currentYear = schoolYears[currentSchoolYearIndex];
            updateEditTermPreview(currentYear);
            updateRadioVisualState(this);
            validateEditSchoolYearChanges();
        });
        
        // Initialize visual state
        updateRadioVisualState(radio);
    });
}

// Validate edit school year changes
function validateEditSchoolYearChanges() {
    console.log('🔍 MODAL: validateEditSchoolYearChanges called');
    const currentYear = schoolYears[currentSchoolYearIndex];
    const newTermCount = parseInt(document.querySelector('input[name="editTermCount"]:checked').value);
    const currentTermCount = currentYear.terms.length;
    const validationMessages = document.getElementById('editValidationMessages');
    
    console.log('🔍 MODAL: Validation params - currentYear:', currentYear, 'newTermCount:', newTermCount, 'currentTermCount:', currentTermCount);
    
    validationMessages.innerHTML = '';
    
    // Check if reducing terms and Term 3 has data
    if (newTermCount === 2 && currentTermCount === 3) {
        const term3 = currentYear.terms[2];
        if (term3 && term3.students !== '0/0') {
            const message = document.createElement('div');
            message.className = 'validation-message warning';
            message.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Reducing to 2 terms will clear Term 3 data.';
            validationMessages.appendChild(message);
        }
    }
    
    console.log('🔍 MODAL: Validation function returning true');
    return true; // Always return true for now
}

// Save school year changes
console.log('🔍 MODAL: Defining saveSchoolYearChanges function...');
window.saveSchoolYearChanges = function() {
    console.log('🔍 MODAL: saveSchoolYearChanges function called!');
    console.log('🔍 MODAL: Function stack trace:', new Error().stack);
    console.log('🔍 MODAL: This is the MODAL version of the function!');
    
    const currentYear = schoolYears[currentSchoolYearIndex];
    const newTermCount = parseInt(document.querySelector('input[name="editTermCount"]:checked').value);
    const currentTermCount = currentYear.terms.length;
    
    // Validate changes
    console.log('🔍 MODAL: About to validate changes...');
    if (!validateEditSchoolYearChanges()) {
        console.log('🔍 MODAL: Validation failed, returning early');
        return;
    }
    console.log('🔍 MODAL: Validation passed, continuing...');
    
    // Prepare confirmation message based on changes
    console.log('🔍 MODAL: Preparing confirmation message...');
    let confirmationMessage = '';
    let confirmationType = 'info';
    
    if (newTermCount > currentTermCount) {
        // Adding terms (2 -> 3)
        console.log('🔍 MODAL: Adding terms condition met');
        confirmationMessage = `Are you sure you want to add Term ${newTermCount} to the school year? This will create a new inactive term.`;
        confirmationType = 'success';
    } else if (newTermCount < currentTermCount) {
        // Removing terms (3 -> 2)
        console.log('🔍 MODAL: Removing terms condition met');
        const termToRemove = currentYear.terms[currentTermCount - 1];
        const hasData = termToRemove && termToRemove.students !== '0/0';
        
        if (hasData) {
            confirmationMessage = `⚠️ WARNING: You are about to remove Term ${currentTermCount} which contains data (${termToRemove.students}). This action will permanently delete all data for this term. Are you sure you want to proceed?`;
            confirmationType = 'warning';
        } else {
            confirmationMessage = `Are you sure you want to remove Term ${currentTermCount} from the school year?`;
            confirmationType = 'warning';
        }
    } else {
        // No change in term count
        console.log('🔍 MODAL: No change condition met');
        confirmationMessage = 'No changes detected. Are you sure you want to proceed?';
        confirmationType = 'info';
    }
    
    // Show confirmation dialog
    console.log('About to show confirmation dialog:', confirmationMessage);
    console.log('showConfirmation function exists:', typeof showConfirmation);
    console.log('Global showConfirmation:', typeof window.showConfirmation);
    console.log('alertSystem.showConfirmation:', typeof window.alertSystem?.showConfirmation);
    
    if (typeof showConfirmation === 'function') {
        showConfirmation(
            'Confirm School Year Changes',
            confirmationMessage,
            'Save Changes',
            'Cancel',
            () => {
                // User confirmed - proceed with save
                console.log('User confirmed, executing save...');
                executeSaveSchoolYearChanges(currentYear, newTermCount, currentTermCount);
            },
            confirmationType
        );
    } else if (typeof window.showConfirmation === 'function') {
        console.log('Using global showConfirmation function');
        window.showConfirmation(
            'Confirm School Year Changes',
            confirmationMessage,
            'Save Changes',
            'Cancel',
            () => {
                // User confirmed - proceed with save
                console.log('User confirmed, executing save...');
                executeSaveSchoolYearChanges(currentYear, newTermCount, currentTermCount);
            },
            confirmationType
        );
    } else if (typeof window.alertSystem?.showConfirmation === 'function') {
        console.log('Using alertSystem.showConfirmation function');
        window.alertSystem.showConfirmation(
            'Confirm School Year Changes',
            confirmationMessage,
            'Save Changes',
            'Cancel',
            () => {
                // User confirmed - proceed with save
                console.log('User confirmed, executing save...');
                executeSaveSchoolYearChanges(currentYear, newTermCount, currentTermCount);
            },
            confirmationType
        );
    } else {
        console.error('showConfirmation function not found!');
        // Fallback: proceed without confirmation
        executeSaveSchoolYearChanges(currentYear, newTermCount, currentTermCount);
    }
};

// Execute the actual save operation
function executeSaveSchoolYearChanges(currentYear, newTermCount, currentTermCount) {
    // Show loading state
    const saveBtn = document.querySelector('.modal-actions .btn-primary');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    // Simulate save process
    setTimeout(() => {
        // Update school year terms
        if (newTermCount > currentTermCount) {
            // Adding terms (2 -> 3)
            for (let i = currentTermCount + 1; i <= newTermCount; i++) {
                currentYear.terms.push({
                    id: `term${i}`,
                    name: `Term ${i}`,
                    status: 'inactive',
                    students: '0/0'
                });
            }
        } else if (newTermCount < currentTermCount) {
            // Removing terms (3 -> 2)
            currentYear.terms = currentYear.terms.slice(0, newTermCount);
        }
        
        // Update display
        updateTermsList(currentYear);
        showToast(`School year updated to ${newTermCount} terms successfully!`, 'success');
        
        // Close modal
        closeEditSchoolYearModal();
        
        // Reset button
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }, 1000);
}

// Check if school year has active terms
function hasActiveTerms(schoolYear) {
    return schoolYear.terms.some(term => term.status === 'active');
}
</script> 