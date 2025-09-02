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
                            <input type="checkbox" id="requiredFirstEnabled" name="requiredFirstEnabled">
                            <label for="requiredFirstEnabled" class="toggle-label"></label>
                        </div>
                    </div>
                    
                    <div class="settings-section-content" id="requiredFirstContent">
                        <p class="settings-description">The first signatory position that must approve before others can sign. The staff member holding this position will be automatically assigned.</p>
                        
                        <div class="form-group">
                            <label for="requiredFirstPosition">Required First Position</label>
                            <select id="requiredFirstPosition" name="requiredFirstPosition">
                                <option value="">Select Position</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Required Last Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <h3><i class="fas fa-flag-checkered"></i> Required Last Signatory</h3>
                        <div class="toggle-switch">
                            <input type="checkbox" id="requiredLastEnabled" name="requiredLastEnabled">
                            <label for="requiredLastEnabled" class="toggle-label"></label>
                        </div>
                    </div>
                    
                    <div class="settings-section-content" id="requiredLastContent">
                        <p class="settings-description">The final signatory position that must approve to complete the clearance. The staff member holding this position will be automatically assigned.</p>
                        
                        <div class="form-group">
                            <label for="requiredLastPosition">Required Last Position</label>
                            <select id="requiredLastPosition" name="requiredLastPosition">
                                <option value="">Select Position</option>
                                <!-- Options will be populated dynamically -->
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
// Global variable to track current clearance type and designations
if (typeof window.currentClearanceType === 'undefined') {
    window.currentClearanceType = '';
}
if (typeof window.designationsData === 'undefined') {
    window.designationsData = [];
}

// Debug: Log that modal script is loading
console.log('🔧 SignatorySettingsModal script is loading...');

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
    
    const clearanceTypeInput = document.getElementById('settingsClearanceType');
    if (clearanceTypeInput) {
        clearanceTypeInput.value = clearanceType;
    } else {
        console.error('settingsClearanceType element not found');
    }
    
    // Load designations and current settings
    loadDesignations().then(() => {
        loadSignatorySettings(clearanceType);
    });
    
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

// Debug: Log that functions are being defined
console.log('🔧 Defining other functions...');

// Close signatory settings modal
window.closeSignatorySettingsModal = function() {
    const modal = document.querySelector('.signatory-settings-modal-overlay');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

// Load designations from database
async function loadDesignations() {
    try {
        console.log('🔧 loadDesignations called for:', window.currentClearanceType);
        
        // Get the current clearance type to load the right signatories
        const clearanceType = window.currentClearanceType;
        
        // Fetch assigned signatories for this specific scope
        const response = await fetch(`/OnlineClearanceWebsite/api/signatories/list.php?clearance_type=${clearanceType}`);
        if (!response.ok) throw new Error('Failed to fetch signatories');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load signatories');
        
        // Store designations data globally for ID mapping
        window.designationsData = data.signatories;
        
        // Extract unique designations from assigned signatories
        const designations = [...new Set(data.signatories.map(s => s.designation_name))].filter(d => d && d !== 'Program Head');
        
        // Populate both dropdowns
        const firstSelect = document.getElementById('requiredFirstPosition');
        const lastSelect = document.getElementById('requiredLastPosition');
        
        // Clear existing options except the first one
        firstSelect.innerHTML = '<option value="">Select Position</option>';
        lastSelect.innerHTML = '<option value="">Select Position</option>';
        
        // Add designation options
        designations.forEach(designation => {
            const firstOption = document.createElement('option');
            firstOption.value = designation;
            firstOption.textContent = designation;
            firstSelect.appendChild(firstOption);
            
            const lastOption = document.createElement('option');
            lastOption.value = designation;
            lastOption.textContent = designation;
            lastSelect.appendChild(lastOption);
        });
        
        // If no designations found, show a message
        if (designations.length === 0) {
            firstSelect.innerHTML = '<option value="">No signatories assigned to this scope</option>';
            lastSelect.innerHTML = '<option value="">No signatories assigned to this scope</option>';
            showToast('No signatories are currently assigned to this scope. Please add signatories first.', 'warning');
        }
        
    } catch (error) {
        console.error('Error loading designations:', error);
        showToast('Failed to load designations. Please try again.', 'error');
        
        // Set placeholder text
        const firstSelect = document.getElementById('requiredFirstPosition');
        const lastSelect = document.getElementById('requiredLastPosition');
        firstSelect.innerHTML = '<option value="">Error loading designations</option>';
        lastSelect.innerHTML = '<option value="">Error loading designations</option>';
    }
}

// Load signatory settings from API
async function loadSignatorySettings(clearanceType) {
    try {
        console.log('🔧 loadSignatorySettings called for:', clearanceType);
        
        const response = await fetch(`/OnlineClearanceWebsite/api/signatories/scope_settings.php?clearance_type=${clearanceType}`);
        if (!response.ok) throw new Error('Failed to fetch settings');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load settings');
        
        const settings = data.settings;
        
        // Set toggle states
        document.getElementById('requiredFirstEnabled').checked = settings.required_first_enabled == 1;
        document.getElementById('requiredLastEnabled').checked = settings.required_last_enabled == 1;
        
        // Set selected positions (need to find designation name by ID)
        if (settings.required_first_designation_id) {
            await setPositionByDesignationId('requiredFirstPosition', settings.required_first_designation_id);
        }
        if (settings.required_last_designation_id) {
            await setPositionByDesignationId('requiredLastPosition', settings.required_last_designation_id);
        }
        
        // Update UI
        updateSettingsContent();
        
    } catch (error) {
        console.error('Error loading settings:', error);
        showToast('Failed to load settings. Using defaults.', 'warning');
        
        // Set defaults
        document.getElementById('requiredFirstEnabled').checked = false;
        document.getElementById('requiredLastEnabled').checked = false;
        updateSettingsContent();
    }
}

// Helper function to set position by designation ID
async function setPositionByDesignationId(selectId, designationId) {
    try {
        // Find the designation name by ID from our cached data
        const designation = window.designationsData.find(d => d.designation_id === designationId);
        if (designation) {
            const select = document.getElementById(selectId);
            if (select) {
                select.value = designation.designation_name;
            }
        }
    } catch (error) {
        console.error('Error setting position by designation ID:', error);
    }
}

// Update content visibility based on toggle states
function updateSettingsContent() {
    const requiredFirstEnabled = document.getElementById('requiredFirstEnabled')?.checked;
    const requiredLastEnabled = document.getElementById('requiredLastEnabled')?.checked;
    
    const requiredFirstContent = document.getElementById('requiredFirstContent');
    const requiredLastContent = document.getElementById('requiredLastContent');
    
    if (requiredFirstContent) {
        requiredFirstContent.style.display = requiredFirstEnabled ? 'block' : 'none';
    }
    
    if (requiredLastContent) {
        requiredLastContent.style.display = requiredLastEnabled ? 'block' : 'none';
    }
}

// Save signatory settings
async function saveSignatorySettings() {
    const form = document.getElementById('signatorySettingsForm');
    if (!form) {
        showToast('Form not found', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Validate settings
    if (!validateSignatorySettings(formData)) {
        return;
    }
    
    // Get save button and disable it
    const saveBtn = document.querySelector('.modal-actions .btn-primary');
    if (saveBtn) {
        saveBtn.disabled = true;
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        
        try {
            const settingsData = {
                clearance_type: window.currentClearanceType,
                include_program_head: false, // Keep existing value, we're only updating required fields
                required_first_enabled: formData.get('requiredFirstEnabled') === 'on',
                required_first_designation_id: getDesignationIdByName(formData.get('requiredFirstPosition')),
                required_last_enabled: formData.get('requiredLastEnabled') === 'on',
                required_last_designation_id: getDesignationIdByName(formData.get('requiredLastPosition'))
            };
            
            // Send to API
            const response = await fetch('/OnlineClearanceWebsite/api/signatories/scope_settings.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(settingsData)
            });
            
            if (!response.ok) throw new Error('Failed to save settings');
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Failed to save settings');
            
            // Show success message
            showToast('Signatory settings saved successfully!', 'success');
            
            // Update the signatory list in real-time
            if (typeof loadScopeSignatories === 'function') {
                loadScopeSignatories(window.currentClearanceType);
            }
            
            // Close modal
            closeSignatorySettingsModal();
            
        } catch (error) {
            console.error('Error saving settings:', error);
            showToast('Failed to save settings: ' + error.message, 'error');
        } finally {
            // Reset button
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        }
    }
}

// Helper function to get designation ID by name
function getDesignationIdByName(designationName) {
    if (!designationName || !window.designationsData || window.designationsData.length === 0) {
        return null;
    }
    
    // Find the designation in our cached data
    const designation = window.designationsData.find(d => d.designation_name === designationName);
    return designation ? designation.designation_id : null;
}

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

// Debug: Log that all functions are defined
console.log('🔧 All SignatorySettingsModal functions defined successfully!');

// Debug: Log that all functions are defined
console.log('🔧 All SignatorySettingsModal functions defined successfully!');
</script>
