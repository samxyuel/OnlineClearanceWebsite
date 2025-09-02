<?php // Shared modal for displaying generated credentials ?>
<div class="modal-overlay" id="credentialModal" style="display:none;">
  <div class="modal-window credential-modal">
    <h2 class="modal-title">Generated Credentials</h2>
    <p class="modal-supporting-text">Copy these credentials and provide them securely to the user.</p>
    <div class="credential-display">
      <label>Username:</label>
      <input type="text" id="generatedUsername" readonly>
    </div>
    <div class="credential-display">
      <label>Password:</label>
      <input type="text" id="generatedPassword" readonly>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeCredentialModal()">Back</button>
      <button class="btn btn-secondary" onclick="copyCredentials()">Copy Credentials</button>
      <button class="modal-action-primary" id="confirmFacultyCreateBtn" onclick="confirmFacultyCreation()">Confirm & Save</button>
    </div>
  </div>
</div>
