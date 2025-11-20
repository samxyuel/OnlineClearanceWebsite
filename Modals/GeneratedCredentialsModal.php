<?php // Shared modal for displaying generated credentials ?>
<div class="modal-overlay" id="credentialModal" style="display:none;">
  <div class="modal-window credential-modal">
    <h2 class="modal-title" id="credentialModalTitle">Generated Credentials</h2>
    <p class="modal-supporting-text" id="credentialModalText">Copy these credentials and provide them securely to the user.</p>
    <div class="credential-display">
      <label>Username:</label>
      <input type="text" id="generatedUsername" readonly>
    </div>
    <div class="credential-display">
      <label id="passwordLabel">Password:</label>
      <input type="text" id="generatedPassword" readonly>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" id="credentialModalCloseBtn" onclick="closeGeneratedCredentialsModal()">Close</button>
      <button class="btn btn-secondary" onclick="copyGeneratedCredentials()">
        <i class="fas fa-copy"></i> Copy Credentials
      </button>
      <button class="modal-action-primary" id="credentialModalConfirmBtn" style="display:none;">Confirm & Save</button>
    </div>
  </div>
</div>

<script>
/**
 * Opens the unified generated credentials modal with different configurations.
 * @param {string} mode - 'newAccount' or 'passwordReset'.
 * @param {object} data - Contains username and password.
 * @param {function} [confirmCallback] - Optional callback for the confirm button.
 */
function openGeneratedCredentialsModal(mode, data, confirmCallback) {
    console.log('[GeneratedCredentialsModal] openGeneratedCredentialsModal() called', { mode, data });
    try {
        const modal = document.getElementById('credentialModal');
        if (!modal) {
            console.error('[GeneratedCredentialsModal] Modal element not found');
            if (typeof showToastNotification === 'function') {
                showToastNotification('Credentials modal not found. Please refresh the page.', 'error');
            }
            return;
        }
        
        const titleEl = document.getElementById('credentialModalTitle');
        const textEl = document.getElementById('credentialModalText');
        const usernameEl = document.getElementById('generatedUsername');
        const passwordEl = document.getElementById('generatedPassword');
        const passwordLabelEl = document.getElementById('passwordLabel');
        const confirmBtn = document.getElementById('credentialModalConfirmBtn');
        const closeBtn = document.getElementById('credentialModalCloseBtn');

        if (usernameEl) usernameEl.value = data.username || '';
        if (passwordEl) passwordEl.value = data.password || '';

        if (mode === 'passwordReset') {
            if (titleEl) titleEl.textContent = 'New Password Generated';
            if (textEl) textEl.textContent = 'Copy the new password and provide it securely to the user. This is the only time it will be shown.';
            if (passwordLabelEl) passwordLabelEl.textContent = 'New Password:';
            if (confirmBtn) confirmBtn.style.display = 'none';
            if (closeBtn) closeBtn.textContent = 'Close';
        } else { // 'newAccount' or default
            if (titleEl) titleEl.textContent = 'Generated Credentials';
            if (textEl) textEl.textContent = 'Copy these credentials and provide them securely to the user.';
            if (passwordLabelEl) passwordLabelEl.textContent = 'Password:';
            if (closeBtn) closeBtn.textContent = 'Back';
            if (confirmCallback && typeof confirmCallback === 'function') {
                if (confirmBtn) {
                    confirmBtn.style.display = 'inline-flex';
                    confirmBtn.onclick = confirmCallback; // Assign the specific callback
                }
            } else {
                if (confirmBtn) confirmBtn.style.display = 'none';
            }
        }

        // Use window.openModal if available, otherwise fallback
        if (typeof window.openModal === 'function') {
            console.log('[GeneratedCredentialsModal] Using window.openModal()');
            window.openModal('credentialModal');
        } else {
            console.log('[GeneratedCredentialsModal] Using fallback method');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }
    } catch (error) {
        console.error('[GeneratedCredentialsModal] Error opening modal:', error);
        if (typeof showToastNotification === 'function') {
            showToastNotification('Unable to open credentials modal. Please try again.', 'error');
        }
    }
}

function closeGeneratedCredentialsModal() {
    console.log('[GeneratedCredentialsModal] closeGeneratedCredentialsModal() called');
    try {
        const modal = document.getElementById('credentialModal');
        if (!modal) {
            console.warn('[GeneratedCredentialsModal] Modal not found');
            return;
        }
        
        // Use window.closeModal if available, otherwise fallback
        if (typeof window.closeModal === 'function') {
            window.closeModal('credentialModal');
        } else {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
            modal.classList.remove('active');
        }
    } catch (error) {
        console.error('[GeneratedCredentialsModal] Error closing modal:', error);
    }
}

function copyGeneratedCredentials() {
    const username = document.getElementById('generatedUsername').value;
    const password = document.getElementById('generatedPassword').value;
    const textToCopy = `Username: ${username}\nPassword: ${password}`;
    navigator.clipboard.writeText(textToCopy).then(() => {
        showToastNotification('Credentials copied to clipboard!', 'success');
    }).catch(err => {
        showToastNotification('Failed to copy credentials.', 'error');
    });
}
</script>