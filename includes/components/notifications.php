<?php
/**
 * Notifications Component
 * Reusable component for displaying notifications panel across all dashboards
 * 
 * Usage: <?php include '../../includes/components/notifications.php'; ?>
 * 
 * The component expects a container with id="notificationsList" to be populated
 * by JavaScript using the updateNotifications() function.
 */
?>
<!-- Notifications Panel -->
<div class="notifications-section">
    <div class="section-header">
        <h3><i class="fas fa-bell"></i> Notifications & Alerts</h3>
    </div>
    <div class="notifications-list" id="notificationsList">
        <!-- Dynamic notifications will be loaded here via JavaScript -->
        <div class="notification-item">
            <div class="notification-content">
                <p>Loading notifications...</p>
            </div>
        </div>
    </div>
</div>

