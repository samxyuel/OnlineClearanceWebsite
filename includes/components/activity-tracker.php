<?php
/**
 * Activity Tracker Component
 * Shows real-time activity updates for admin pages
 * 
 * TEMPORARILY DISABLED: Database connection for interface configuration
 * TODO: Re-enable database connection when ready for production
 */

// TEMPORARILY DISABLED: Include audit functions
// require_once __DIR__ . '/../functions/audit_functions.php';

// TEMPORARILY DISABLED: Get current user role and ID for filtering activities
// $current_user_id = $_SESSION['user_id'] ?? 0;
// $current_user_role = $_SESSION['role_id'] ?? 0;
// $current_user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// TEMPORARILY DISABLED: Get activities using audit functions
// $activities = getFilteredActivities('', '', '', '', '', '', 10, 0); // Get latest 10 activities

// TEMPORARILY DISABLED: Get activity statistics
// $activity_stats = getActivityStatistics();

// TEMPORARILY DISABLED: Get filter options
// $activity_types = getActivityTypes();
// $unique_users = getUniqueUsers();

// TEMPORARY: Static demo data for interface configuration
$activities = [
    [
        'id' => 1,
        'type' => 'login',
        'title' => 'User Login',
        'description' => 'Admin user logged into the system',
        'user' => 'John Admin',
        'user_id' => 1,
        'priority' => 'low',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
    ],
    [
        'id' => 2,
        'type' => 'create',
        'title' => 'New Student Added',
        'description' => 'Student account created for Maria Santos',
        'user' => 'Admin User',
        'user_id' => 1,
        'priority' => 'medium',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
    ],
    [
        'id' => 3,
        'type' => 'update',
        'title' => 'Faculty Record Updated',
        'description' => 'Faculty information updated for Dr. Smith',
        'user' => 'System Admin',
        'user_id' => 2,
        'priority' => 'medium',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ],
    [
        'id' => 4,
        'type' => 'export',
        'title' => 'Data Export',
        'description' => 'Student records exported to CSV format',
        'user' => 'Admin User',
        'user_id' => 1,
        'priority' => 'low',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
    ],
    [
        'id' => 5,
        'type' => 'clearance',
        'title' => 'Clearance Approved',
        'description' => 'Student clearance request approved',
        'user' => 'Faculty Head',
        'user_id' => 3,
        'priority' => 'high',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ]
];

$activity_stats = [
    'total_activities' => 5,
    'high_priority' => 1,
    'medium_priority' => 2,
    'low_priority' => 2
];

$activity_types = [
    ['value' => 'login', 'label' => 'User Login'],
    ['value' => 'logout', 'label' => 'User Logout'],
    ['value' => 'create', 'label' => 'Create Record'],
    ['value' => 'update', 'label' => 'Update Record'],
    ['value' => 'delete', 'label' => 'Delete Record'],
    ['value' => 'export', 'label' => 'Data Export'],
    ['value' => 'import', 'label' => 'Data Import'],
    ['value' => 'clearance', 'label' => 'Clearance Action']
];

$unique_users = [
    ['id' => 1, 'name' => 'Admin User'],
    ['id' => 2, 'name' => 'System Admin'],
    ['id' => 3, 'name' => 'Faculty Head']
];
?>

<!-- Mobile Toggle Button - Fixed Position -->
<div class="activity-tracker-toggle" id="activity-tracker-toggle" title="Toggle Activity Tracker">
    <i class="fas fa-chart-line" id="toggle-icon"></i>
</div>

<!-- Activity Tracker Backdrop for Mobile -->
<div class="activity-tracker-backdrop" id="activity-tracker-backdrop"></div>

<!-- Activity Tracker Container -->
<div class="activity-tracker" id="activityTracker">
    <!-- Header -->
    <div class="activity-tracker-header">
        <h3 style="font-size: clamp(16px, 18px, 22px);">
            <i class="fas fa-chart-line" style="font-size: clamp(14px, 16px, 20px);"></i>
            Activity Tracker
        </h3>
    </div>

    <!-- Quick Stats Row -->
    <div class="quick-stats-row">
        <div class="quick-stat-item">
            <div class="quick-stat-number"><?php echo $activity_stats['total_activities']; ?></div>
            <div class="quick-stat-label">Total</div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-number"><?php echo $activity_stats['high_priority']; ?></div>
            <div class="quick-stat-label">High</div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-number"><?php echo $activity_stats['medium_priority']; ?></div>
            <div class="quick-stat-label">Medium</div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-number"><?php echo $activity_stats['low_priority']; ?></div>
            <div class="quick-stat-label">Low</div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="search-filter-section">
        <div class="search-filter-chips">
            <div class="filter-chip active" data-filter="all">All</div>
            <div class="filter-chip" data-filter="login">Login</div>
            <div class="filter-chip" data-filter="update">Update</div>
            <div class="filter-chip" data-filter="delete">Delete</div>
            <div class="filter-chip" data-filter="create">Create</div>
        </div>
    </div>




    <!-- Activity List Container -->
    <div class="activity-list-container">
        <ul class="activity-list">
            <?php if (empty($activities)): ?>
                <div class="no-activities">
                    <i class="fas fa-inbox"></i>
                    <p>No activities to display</p>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <li class="activity-item" data-type="<?php echo $activity['type']; ?>" data-activity-id="<?php echo $activity['id']; ?>">
                        <div class="activity-icon">
                            <i class="<?php echo getActivityIcon($activity['type']); ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                            <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                            <div class="activity-meta">
                                <span class="activity-user"><?php echo htmlspecialchars($activity['user']); ?></span>
                                <span class="activity-time"><?php echo getRelativeTime($activity['timestamp']); ?></span>
                            </div>
                        </div>
                        <div class="activity-priority priority-<?php echo $activity['priority']; ?>">
                            <?php echo ucfirst($activity['priority']); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Footer with Quick Actions -->
    <div class="activity-tracker-footer">
        <div class="quick-actions">
            <button class="quick-action-btn" title="Refresh Activities" style="font-size: clamp(11px, 12px, 15px);">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="quick-action-btn" title="Export Activities" style="font-size: clamp(11px, 12px, 15px);">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div class="activity-status">
            <small class="text-muted" style="font-size: clamp(10px, 11px, 14px);">Last updated: <?php echo date('M j, g:i A'); ?></small>
        </div>
    </div>
</div>

<?php
/**
 * Helper function to get activity icon based on type
 */
function getActivityIcon($type) {
    $iconMap = [
        'login' => 'fas fa-sign-in-alt',
        'logout' => 'fas fa-sign-out-alt',
        'create' => 'fas fa-plus',
        'update' => 'fas fa-edit',
        'delete' => 'fas fa-trash',
        'export' => 'fas fa-download',
        'import' => 'fas fa-upload',
        'approve' => 'fas fa-check',
        'reject' => 'fas fa-times',
        'clearance' => 'fas fa-clipboard-check',
        'default' => 'fas fa-info-circle'
    ];
    
    return $iconMap[strtolower($type)] ?? $iconMap['default'];
}

/**
 * Helper function to get relative time
 */
function getRelativeTime($timestamp) {
    $now = new DateTime();
    $activityTime = new DateTime($timestamp);
    $diff = $now->diff($activityTime);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    } elseif ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}
?>
