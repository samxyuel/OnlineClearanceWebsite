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
    const modal = document.getElementById('credentialModal');
    const titleEl = document.getElementById('credentialModalTitle');
    const textEl = document.getElementById('credentialModalText');
    const usernameEl = document.getElementById('generatedUsername');
    const passwordEl = document.getElementById('generatedPassword');
    const passwordLabelEl = document.getElementById('passwordLabel');
    const confirmBtn = document.getElementById('credentialModalConfirmBtn');
    const closeBtn = document.getElementById('credentialModalCloseBtn');

    usernameEl.value = data.username || '';
    passwordEl.value = data.password || '';

    if (mode === 'passwordReset') {
        titleEl.textContent = 'New Password Generated';
        textEl.textContent = 'Copy the new password and provide it securely to the user. This is the only time it will be shown.';
        passwordLabelEl.textContent = 'New Password:';
        confirmBtn.style.display = 'none';
        closeBtn.textContent = 'Close';
    } else { // 'newAccount' or default
        titleEl.textContent = 'Generated Credentials';
        textEl.textContent = 'Copy these credentials and provide them securely to the user.';
        passwordLabelEl.textContent = 'Password:';
        closeBtn.textContent = 'Back';
        if (confirmCallback && typeof confirmCallback === 'function') {
            confirmBtn.style.display = 'inline-flex';
            confirmBtn.onclick = confirmCallback; // Assign the specific callback
        } else {
            confirmBtn.style.display = 'none';
        }
    }

    modal.style.display = 'flex';
}

function closeGeneratedCredentialsModal() {
    document.getElementById('credentialModal').style.display = 'none';
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