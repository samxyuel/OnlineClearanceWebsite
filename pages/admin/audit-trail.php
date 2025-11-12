<?php
/**
 * Audit Trail Page - Admin
 * Comprehensive view of all system activities and user actions
 */

session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../pages/auth/login.php');
    exit();
}

// Include necessary files
require_once '../../includes/config/database.php';
require_once '../../includes/functions/audit_functions.php';

// Get current user info
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter parameters
$activity_type = $_GET['type'] ?? '';
$priority = $_GET['priority'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Get filtered activities (for now using demo data, replace with database queries)
$activities = getFilteredActivities($activity_type, $priority, $user_filter, $date_from, $date_to, $search, $per_page, $offset);
$total_activities = getTotalActivitiesCount($activity_type, $priority, $user_filter, $date_from, $date_to, $search);
$total_pages = ceil($total_activities / $per_page);

// Get activity statistics
$stats = getActivityStatistics();

// Get unique users for filter dropdown
$users = getUniqueUsers();

// Get activity types for filter dropdown
$activity_types = getActivityTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Admin Dashboard</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js for statistics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/components/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../../includes/components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content audit-trail-page">
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-content">
                    <h2>System Audit Trail</h2>
                    <p>Monitor and track all system activities, user actions, and security events</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="exportAuditTrail()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-secondary" onclick="refreshAuditTrail()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-activity"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_activities']); ?></h3>
                        <p>Total Activities</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['high_priority']); ?></h3>
                        <p>High Priority</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['active_users']); ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['last_24h']; ?></h3>
                        <p>Last 24 Hours</p>
                    </div>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Activity Trends</h3>
                    <div class="chart-controls">
                        <select id="chartPeriod" onchange="updateChart()">
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-header">
                    <h3><i class="fas fa-filter"></i> Advanced Filters</h3>
                    <button class="btn btn-sm btn-secondary" onclick="toggleFilters()">
                        <i class="fas fa-chevron-down"></i> Toggle
                    </button>
                </div>
                <div class="filters-content" id="filtersContent">
                    <form class="filters-form" method="GET" action="">
                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search activities, users, or descriptions...">
                            </div>
                            <div class="filter-group">
                                <label for="type">Activity Type</label>
                                <select id="type" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach ($activity_types as $type): ?>
                                        <option value="<?php echo $type['value']; ?>" 
                                                <?php echo $activity_type === $type['value'] ? 'selected' : ''; ?>>
                                            <?php echo $type['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="priority">Priority</label>
                                <select id="priority" name="priority">
                                    <option value="">All Priorities</option>
                                    <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                                </select>
                            </div>
                        </div>
                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="user">User</label>
                                <select id="user" name="user">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="date_from">Date From</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="filter-group">
                                <label for="date_to">Date To</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                        </div>
                        <div class="filters-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activities Table -->
            <div class="activities-section">
                <div class="section-header">
                    <h3>System Activities</h3>
                    <div class="table-controls">
                        <span class="results-count">
                            Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_activities); ?> 
                            of <?php echo $total_activities; ?> activities
                        </span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="activities-table">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Timestamp</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <div class="no-data-content">
                                            <i class="fas fa-inbox"></i>
                                            <p>No activities found matching your criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                    <tr class="activity-row priority-<?php echo $activity['priority']; ?>">
                                        <td>
                                            <div class="activity-info">
                                                <div class="activity-icon">
                                                    <i class="<?php echo $activity['icon']; ?>"></i>
                                                </div>
                                                <div class="activity-details">
                                                    <h4><?php echo htmlspecialchars($activity['title']); ?></h4>
                                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <span class="user-name"><?php echo htmlspecialchars($activity['user']); ?></span>
                                                <span class="user-role"><?php echo htmlspecialchars($activity['user_role'] ?? ''); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="activity-type-badge">
                                                <?php echo htmlspecialchars($activity['type_label'] ?? $activity['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="priority-badge priority-<?php echo $activity['priority']; ?>">
                                                <?php echo ucfirst($activity['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="timestamp-info">
                                                <span class="timestamp"><?php echo $activity['timestamp']; ?></span>
                                                <span class="relative-time"><?php echo $activity['relative_time']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="viewActivityDetails(<?php echo $activity['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline" onclick="exportActivity(<?php echo $activity['id']; ?>)" title="Export">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <?php if ($activity['priority'] === 'high'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="flagActivity(<?php echo $activity['id']; ?>)" title="Flag">
                                                        <i class="fas fa-flag"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <div class="page-numbers">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Activity Details Modal -->
    <div class="modal" id="activityDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Activity Details</h3>
                <button class="modal-close" onclick="closeModal('activityDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="activityDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('activityDetailsModal')">Close</button>
                <button class="btn btn-primary" onclick="exportActivityDetails()">Export Details</button>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal" id="exportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Export Audit Trail</h3>
                <button class="modal-close" onclick="closeModal('exportModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="export-options">
                    <div class="export-format">
                        <label>Export Format:</label>
                        <select id="exportFormat">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="export-range">
                        <label>Date Range:</label>
                        <select id="exportRange">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="export-filters">
                        <label>Include Filters:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" id="includeFilters" checked> Apply current filters</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('exportModal')">Cancel</button>
                <button class="btn btn-primary" onclick="executeExport()">Export</button>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="../../assets/js/alerts.js"></script>
    <script src="../../assets/js/modals.js"></script>
    
    <script>
        // Initialize activity chart
        let activityChart;
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeChart();
            initializeFilters();
        });

        function initializeChart() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Activities',
                        data: [12, 19, 15, 25, 22, 18, 24],
                        borderColor: '#0c5591',
                        backgroundColor: 'rgba(12, 85, 145, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        }

        function updateChart() {
            const period = document.getElementById('chartPeriod').value;
            // Update chart data based on selected period
            // This would typically involve an AJAX call to get new data
            showToast('Chart updated for ' + period + ' days', 'info');
        }

        function toggleFilters() {
            const content = document.getElementById('filtersContent');
            const toggleBtn = document.querySelector('.filters-header .btn i');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggleBtn.className = 'fas fa-chevron-up';
            } else {
                content.style.display = 'none';
                toggleBtn.className = 'fas fa-chevron-down';
            }
        }

        function clearFilters() {
            document.getElementById('search').value = '';
            document.getElementById('type').value = '';
            document.getElementById('priority').value = '';
            document.getElementById('user').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            
            // Submit the form to refresh results
            document.querySelector('.filters-form').submit();
        }

        function refreshAuditTrail() {
            location.reload();
        }

        function exportAuditTrail() {
            openModal('exportModal');
        }

        function executeExport() {
            const format = document.getElementById('exportFormat').value;
            const range = document.getElementById('exportRange').value;
            const includeFilters = document.getElementById('includeFilters').checked;
            
            // Simulate export process
            showToast('Exporting audit trail...', 'info');
            
            setTimeout(() => {
                showToast('Audit trail exported successfully!', 'success');
                closeModal('exportModal');
            }, 2000);
        }

        function viewActivityDetails(activityId) {
            // Load activity details via AJAX (simulated for now)
            const content = document.getElementById('activityDetailsContent');
            content.innerHTML = `
                <div class="activity-details-content">
                    <div class="detail-row">
                        <label>Activity ID:</label>
                        <span>${activityId}</span>
                    </div>
                    <div class="detail-row">
                        <label>Title:</label>
                        <span>Sample Activity Title</span>
                    </div>
                    <div class="detail-row">
                        <label>Description:</label>
                        <span>Detailed description of the activity...</span>
                    </div>
                    <div class="detail-row">
                        <label>User:</label>
                        <span>John Doe</span>
                    </div>
                    <div class="detail-row">
                        <label>IP Address:</label>
                        <span>192.168.1.100</span>
                    </div>
                    <div class="detail-row">
                        <label>User Agent:</label>
                        <span>Mozilla/5.0...</span>
                    </div>
                    <div class="detail-row">
                        <label>Session ID:</label>
                        <span>sess_123456789</span>
                    </div>
                </div>
            `;
            
            openModal('activityDetailsModal');
        }

        function exportActivity(activityId) {
            showToast(`Exporting activity ${activityId}...`, 'info');
        }

        function flagActivity(activityId) {
            showToast(`Activity ${activityId} flagged for review`, 'warning');
        }

        function exportActivityDetails() {
            showToast('Activity details exported successfully!', 'success');
        }

        // Auto-refresh every 5 minutes
        setInterval(() => {
            if (!document.hidden) {
                refreshAuditTrail();
            }
        }, 300000);
    </script>
</body>
</html>
