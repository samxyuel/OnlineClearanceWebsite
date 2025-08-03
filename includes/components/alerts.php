<?php
// Alert System Components
// This file contains the HTML templates for all alert types
?>

<!-- Confirmation Modal Template -->
<div class="alert-modal-overlay" id="confirmationModal" style="display: none;">
    <div class="alert-modal-window">
        <div class="alert-modal-header" id="alertHeader">
            <i class="fas fa-info-circle" id="alertIcon"></i>
            <h3 id="alertTitle">Confirm Action</h3>
        </div>
        <div class="alert-modal-body">
            <p id="alertMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="alert-modal-actions">
            <button class="btn btn-secondary" onclick="closeConfirmationModal()" id="cancelBtn">Cancel</button>
            <button class="btn btn-primary" onclick="executeConfirmedAction()" id="confirmBtn">Confirm</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div> 