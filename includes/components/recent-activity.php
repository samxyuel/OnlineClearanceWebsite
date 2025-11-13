<?php
/**
 * Recent Activity Component
 * Reusable component for displaying recent activity timeline across all dashboards
 * 
 * Usage: <?php include '../../includes/components/recent-activity.php'; ?>
 * 
 * The component expects a container with id="activityTimeline" to be populated
 * by JavaScript using the updateRecentActivity() function.
 */
?>
<!-- Recent Activity Section -->
<div class="recent-activity-section">
    <div class="section-header">
        <h3><i class="fas fa-history"></i> Recent Activity</h3>
    </div>
    <div class="activity-timeline" id="activityTimeline">
        <!-- Dynamic activity items will be loaded here via JavaScript -->
        <div class="activity-item">
            <div class="activity-content">
                <p>Loading recent activity...</p>
            </div>
        </div>
    </div>
</div>

