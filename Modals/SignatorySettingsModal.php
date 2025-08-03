<?php
// Signatory Settings Modal
?>

<div class="modal-overlay signatory-settings-modal-overlay">
    <div class="modal-window">
        <!-- Close Button -->
        <button class="modal-close" onclick="closeSignatorySettingsModal()">&times;</button>
        
        <!-- Modal Title -->
        <h2 class="modal-title" id="signatorySettingsTitle">Signatory Settings</h2>
        
        <!-- Supporting Text -->
        <div class="modal-supporting-text">Configure required signatory positions. Staff members will be automatically assigned based on their positions.</div>
        
        <!-- Content Area -->
        <div class="modal-content-area">
            <form id="signatorySettingsForm">
                <input type="hidden" id="settingsClearanceType" name="clearanceType" value="">
                
                <!-- Required First Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <h3><i class="fas fa-flag"></i> Required First Signatory</h3>
                        <div class="toggle-switch">
                            <input type="checkbox" id="requiredFirstEnabled" name="requiredFirstEnabled" checked>
                            <label for="requiredFirstEnabled" class="toggle-label"></label>
                        </div>
                    </div>
                    
                    <div class="settings-section-content" id="requiredFirstContent">
                        <p class="settings-description">The first signatory position that must approve before others can sign. The staff member holding this position will be automatically assigned.</p>
                        
                        <div class="form-group">
                            <label for="requiredFirstPosition">Required First Position</label>
                            <select id="requiredFirstPosition" name="requiredFirstPosition">
                                <option value="">Select Position</option>
                                <option value="Cashier" selected>Cashier</option>
                                <option value="Accountant">Accountant</option>
                                <option value="Program Head">Program Head</option>
                                <option value="Registrar">Registrar</option>
                                <option value="Academic Head">Academic Head</option>
                                <option value="School Administrator">School Administrator</option>
                            </select>
                        </div>
                        

                    </div>
                </div>
                
                <!-- Required Last Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <h3><i class="fas fa-flag-checkered"></i> Required Last Signatory</h3>
                        <div class="toggle-switch">
                            <input type="checkbox" id="requiredLastEnabled" name="requiredLastEnabled" checked>
                            <label for="requiredLastEnabled" class="toggle-label"></label>
                        </div>
                    </div>
                    
                    <div class="settings-section-content" id="requiredLastContent">
                        <p class="settings-description">The final signatory position that must approve to complete the clearance. The staff member holding this position will be automatically assigned.</p>
                        
                        <div class="form-group">
                            <label for="requiredLastPosition">Required Last Position</label>
                            <select id="requiredLastPosition" name="requiredLastPosition">
                                <option value="">Select Position</option>
                                <option value="Registrar" selected>Registrar</option>
                                <option value="Cashier">Cashier</option>
                                <option value="Accountant">Accountant</option>
                                <option value="Program Head">Program Head</option>
                                <option value="Academic Head">Academic Head</option>
                                <option value="School Administrator">School Administrator</option>
                            </select>
                        </div>
                        

                    </div>
                </div>
                
                <!-- Settings Notes -->
                <div class="settings-notes">
                    <h4><i class="fas fa-info-circle"></i> Important Notes</h4>
                    <ul>
                        <li>When "Required First" is enabled, other signatories cannot sign until the first signatory approves.</li>
                        <li>When "Required Last" is enabled, clearance cannot be completed until the last signatory approves.</li>
                        <li>Both features can be enabled simultaneously for strict clearance flow.</li>
                        <li>If both are disabled, signatories can sign in any order.</li>
                    </ul>
                </div>
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeSignatorySettingsModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveSignatorySettings()">Save Settings</button>
        </div>
    </div>
</div>

<script>
// Global variable to track current clearance type
if (typeof window.currentClearanceType === 'undefined') {
    window.currentClearanceType = '';
}

// Open signatory settings modal
window.openSignatorySettingsModal = function(clearanceType) {
    console.log('Window function called with:', clearanceType);
    
    window.currentClearanceType = clearanceType;
    
    // Update modal title based on clearance type
    const modalTitle = clearanceType === 'student' ? 'Student Signatory Settings' : 'Faculty Signatory Settings';
    const titleElement = document.getElementById('signatorySettingsTitle');
    if (titleElement) {
        titleElement.textContent = modalTitle;
    } else {
        console.error('signatorySettingsTitle element not found');
    }
    
    // Note: settingsType element was removed, so we skip that
    const clearanceTypeInput = document.getElementById('settingsClearanceType');
    if (clearanceTypeInput) {
        clearanceTypeInput.value = clearanceType;
    } else {
        console.error('settingsClearanceType element not found');
    }
    
    // Load current settings based on clearance type
    loadSignatorySettings(clearanceType);
    
    // Show modal
    const modal = document.querySelector('.signatory-settings-modal-overlay');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        console.log('Modal should now be visible');
    } else {
        console.error('Modal overlay not found');
    }
};

// Close signatory settings modal
window.closeSignatorySettingsModal = function() {
    document.querySelector('.signatory-settings-modal-overlay').style.display = 'none';
    document.body.style.overflow = 'auto';
};

// Load signatory settings
function loadSignatorySettings(clearanceType) {
    // This would typically load from database
    // For now, using default values based on clearance type
    
    if (clearanceType === 'student') {
        document.getElementById('requiredFirstPosition').value = 'Cashier';
        document.getElementById('requiredLastPosition').value = 'Registrar';
    } else {
        document.getElementById('requiredFirstPosition').value = 'Accountant';
        document.getElementById('requiredLastPosition').value = 'Registrar';
    }
    
    // Enable/disable content based on toggle states
    updateSettingsContent();
}

// Update settings content visibility based on toggle states
function updateSettingsContent() {
    const requiredFirstEnabled = document.getElementById('requiredFirstEnabled').checked;
    const requiredLastEnabled = document.getElementById('requiredLastEnabled').checked;
    
    const requiredFirstContent = document.getElementById('requiredFirstContent');
    const requiredLastContent = document.getElementById('requiredLastContent');
    
    requiredFirstContent.style.opacity = requiredFirstEnabled ? '1' : '0.5';
    requiredFirstContent.style.pointerEvents = requiredFirstEnabled ? 'auto' : 'none';
    
    requiredLastContent.style.opacity = requiredLastEnabled ? '1' : '0.5';
    requiredLastContent.style.pointerEvents = requiredLastEnabled ? 'auto' : 'none';
}

// Save signatory settings
window.saveSignatorySettings = function() {
    const form = document.getElementById('signatorySettingsForm');
    const formData = new FormData(form);
    
    // Add clearance type to form data
    formData.append('clearanceType', window.currentClearanceType);
    
    // Validate form
    if (!validateSignatorySettings(formData)) {
        return;
    }
    
    // Show loading state
    const saveBtn = document.querySelector('.modal-actions .btn-primary');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        // Here you would typically send the data to the server
        console.log('Saving signatory settings:', Object.fromEntries(formData));
        
        // Show success message
        showToast('Signatory settings saved successfully!', 'success');
        
        // Close modal
        closeSignatorySettingsModal();
        
        // Reset button
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    }, 1000);
};

// Validate signatory settings
function validateSignatorySettings(formData) {
    const requiredFirstEnabled = formData.get('requiredFirstEnabled') === 'on';
    const requiredLastEnabled = formData.get('requiredLastEnabled') === 'on';
    
    if (requiredFirstEnabled) {
        const requiredFirstPosition = formData.get('requiredFirstPosition');
        if (!requiredFirstPosition) {
            showToast('Please select a position for Required First signatory.', 'error');
            return false;
        }
    }
    
    if (requiredLastEnabled) {
        const requiredLastPosition = formData.get('requiredLastPosition');
        if (!requiredLastPosition) {
            showToast('Please select a position for Required Last signatory.', 'error');
            return false;
        }
    }
    
    // Check if same position is selected for both
    if (requiredFirstEnabled && requiredLastEnabled) {
        const firstPosition = formData.get('requiredFirstPosition');
        const lastPosition = formData.get('requiredLastPosition');
        
        if (firstPosition && lastPosition && firstPosition === lastPosition) {
            showToast('Required First and Required Last positions cannot be the same.', 'error');
            return false;
        }
    }
    
    return true;
}

// Add event listeners for toggle switches
document.addEventListener('DOMContentLoaded', function() {
    const requiredFirstToggle = document.getElementById('requiredFirstEnabled');
    const requiredLastToggle = document.getElementById('requiredLastEnabled');
    
    if (requiredFirstToggle) {
        requiredFirstToggle.addEventListener('change', updateSettingsContent);
    }
    
    if (requiredLastToggle) {
        requiredLastToggle.addEventListener('change', updateSettingsContent);
    }
});
</script> 