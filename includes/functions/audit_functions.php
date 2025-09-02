<?php
/**
 * Audit Functions
 * Functions for managing and retrieving audit trail data
 */

/**
 * Get filtered activities based on various criteria
 */
function getFilteredActivities($type = '', $priority = '', $user = '', $date_from = '', $date_to = '', $search = '', $limit = 20, $offset = 0) {
    // For now, return demo data. Replace with actual database queries later
    $activities = getDemoActivities();
    
    // Apply filters
    $filtered = array_filter($activities, function($activity) use ($type, $priority, $user, $date_from, $date_to, $search) {
        // Type filter
        if ($type && $activity['type'] !== $type) {
            return false;
        }
        
        // Priority filter
        if ($priority && $activity['priority'] !== $priority) {
            return false;
        }
        
        // User filter
        if ($user && $activity['user_id'] != $user) {
            return false;
        }
        
        // Date filters
        if ($date_from) {
            $activity_date = strtotime($activity['timestamp']);
            $from_date = strtotime($date_from);
            if ($activity_date < $from_date) {
                return false;
            }
        }
        
        if ($date_to) {
            $activity_date = strtotime($activity['timestamp']);
            $to_date = strtotime($date_to . ' 23:59:59');
            if ($activity_date > $to_date) {
                return false;
            }
        }
        
        // Search filter
        if ($search) {
            $search_lower = strtolower($search);
            $title_match = strpos(strtolower($activity['title']), $search_lower) !== false;
            $desc_match = strpos(strtolower($activity['description']), $search_lower) !== false;
            $user_match = strpos(strtolower($activity['user']), $search_lower) !== false;
            
            if (!$title_match && !$desc_match && !$user_match) {
                return false;
            }
        }
        
        return true;
    });
    
    // Apply pagination
    return array_slice($filtered, $offset, $limit);
}

/**
 * Get total count of activities matching filters
 */
function getTotalActivitiesCount($type = '', $priority = '', $user = '', $date_from = '', $date_to = '', $search = '') {
    $activities = getDemoActivities();
    
    $filtered = array_filter($activities, function($activity) use ($type, $priority, $user, $date_from, $date_to, $search) {
        // Type filter
        if ($type && $activity['type'] !== $type) {
            return false;
        }
        
        // Priority filter
        if ($priority && $activity['priority'] !== $priority) {
            return false;
        }
        
        // User filter
        if ($user && $activity['user_id'] != $user) {
            return false;
        }
        
        // Date filters
        if ($date_from) {
            $activity_date = strtotime($activity['timestamp']);
            $from_date = strtotime($date_from);
            if ($activity_date < $from_date) {
                return false;
            }
        }
        
        if ($date_to) {
            $activity_date = strtotime($activity['timestamp']);
            $to_date = strtotime($date_to . ' 23:59:59');
            if ($activity_date > $to_date) {
                return false;
            }
        }
        
        // Search filter
        if ($search) {
            $search_lower = strtolower($search);
            $title_match = strpos(strtolower($activity['title']), $search_lower) !== false;
            $desc_match = strpos(strtolower($activity['description']), $search_lower) !== false;
            $user_match = strpos(strtolower($activity['user']), $search_lower) !== false;
            
            if (!$title_match && !$desc_match && !$user_match) {
                return false;
            }
        }
        
        return true;
    });
    
    return count($filtered);
}

/**
 * Get activity statistics
 */
function getActivityStatistics() {
    $activities = getDemoActivities();
    
    $stats = [
        'total_activities' => count($activities),
        'high_priority' => 0,
        'medium_priority' => 0,
        'low_priority' => 0,
        'active_users' => 0,
        'last_24h' => 0
    ];
    
    $users = [];
    $last_24h = strtotime('-24 hours');
    
    foreach ($activities as $activity) {
        // Count by priority
        switch ($activity['priority']) {
            case 'high':
                $stats['high_priority']++;
                break;
            case 'medium':
                $stats['medium_priority']++;
                break;
            case 'low':
                $stats['low_priority']++;
                break;
        }
        
        // Count unique users
        if (!in_array($activity['user_id'], $users)) {
            $users[] = $activity['user_id'];
        }
        
        // Count last 24 hours
        if (strtotime($activity['timestamp']) >= $last_24h) {
            $stats['last_24h']++;
        }
    }
    
    $stats['active_users'] = count($users);
    
    return $stats;
}

/**
 * Get unique users for filter dropdown
 */
function getUniqueUsers() {
    // For now, return demo data. Replace with database query later
    return [
        ['id' => 1, 'name' => 'Admin User'],
        ['id' => 2, 'name' => 'Dr. Emily Brown'],
        ['id' => 3, 'name' => 'John Smith'],
        ['id' => 4, 'name' => 'Maria Garcia'],
        ['id' => 5, 'name' => 'System Monitor'],
        ['id' => 6, 'name' => 'Security System']
    ];
}

/**
 * Get activity types for filter dropdown
 */
function getActivityTypes() {
    return [
        ['value' => 'user_login', 'label' => 'User Login'],
        ['value' => 'user_logout', 'label' => 'User Logout'],
        ['value' => 'clearance_approved', 'label' => 'Clearance Approved'],
        ['value' => 'clearance_rejected', 'label' => 'Clearance Rejected'],
        ['value' => 'student_registered', 'label' => 'Student Registered'],
        ['value' => 'faculty_updated', 'label' => 'Faculty Updated'],
        ['value' => 'staff_updated', 'label' => 'Staff Updated'],
        ['value' => 'course_created', 'label' => 'Course Created'],
        ['value' => 'course_updated', 'label' => 'Course Updated'],
        ['value' => 'term_activated', 'label' => 'Term Activated'],
        ['value' => 'term_deactivated', 'label' => 'Term Deactivated'],
        ['value' => 'system_alert', 'label' => 'System Alert'],
        ['value' => 'security_event', 'label' => 'Security Event'],
        ['value' => 'data_export', 'label' => 'Data Export'],
        ['value' => 'data_import', 'label' => 'Data Import']
    ];
}

/**
 * Get demo activities data
 */
function getDemoActivities() {
    return [
        [
            'id' => 1,
            'type' => 'user_login',
            'type_label' => 'User Login',
            'icon' => 'fas fa-sign-in-alt',
            'title' => 'User Login',
            'description' => 'Admin User logged in successfully',
            'user' => 'Admin User',
            'user_id' => 1,
            'user_role' => 'Administrator',
            'timestamp' => date('Y-m-d H:i:s'),
            'relative_time' => 'Just now',
            'priority' => 'low',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 2,
            'type' => 'clearance_approved',
            'type_label' => 'Clearance Approved',
            'icon' => 'fas fa-check-circle',
            'title' => 'Clearance Approved',
            'description' => 'Maria Garcia\'s clearance completed and approved',
            'user' => 'Dr. Emily Brown',
            'user_id' => 2,
            'user_role' => 'Faculty',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            'relative_time' => '2 minutes ago',
            'priority' => 'medium',
            'ip_address' => '192.168.1.101',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 3,
            'type' => 'student_registered',
            'type_label' => 'Student Registered',
            'icon' => 'fas fa-user-plus',
            'title' => 'New Student Registration',
            'description' => 'John Smith registered for clearance system',
            'user' => 'System',
            'user_id' => 0,
            'user_role' => 'System',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
            'relative_time' => '5 minutes ago',
            'priority' => 'low',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'System Process',
            'session_id' => 'sess_system'
        ],
        [
            'id' => 4,
            'type' => 'term_deactivated',
            'type_label' => 'Term Deactivated',
            'icon' => 'fas fa-pause-circle',
            'title' => 'Academic Term Deactivated',
            'description' => '2024-2025 Term 1 was paused by administrator',
            'user' => 'Admin User',
            'user_id' => 1,
            'user_role' => 'Administrator',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'relative_time' => '1 hour ago',
            'priority' => 'high',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 5,
            'type' => 'staff_updated',
            'type_label' => 'Staff Updated',
            'icon' => 'fas fa-user-edit',
            'title' => 'Staff Information Modified',
            'description' => 'Dr. Emily Brown\'s profile information was updated',
            'user' => 'Admin User',
            'user_id' => 1,
            'user_role' => 'Administrator',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'relative_time' => '2 hours ago',
            'priority' => 'medium',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 6,
            'type' => 'course_created',
            'type_label' => 'Course Created',
            'icon' => 'fas fa-book',
            'title' => 'New Course Added',
            'description' => 'Advanced Mathematics course was created',
            'user' => 'Admin User',
            'user_id' => 1,
            'user_role' => 'Administrator',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            'relative_time' => '3 hours ago',
            'priority' => 'low',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 7,
            'type' => 'security_event',
            'type_label' => 'Security Event',
            'icon' => 'fas fa-shield-alt',
            'title' => 'Multiple Login Attempts',
            'description' => 'Multiple failed login attempts detected from IP 192.168.1.200',
            'user' => 'Security System',
            'user_id' => 0,
            'user_role' => 'System',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            'relative_time' => '4 hours ago',
            'priority' => 'high',
            'ip_address' => '192.168.1.200',
            'user_agent' => 'Unknown Browser',
            'session_id' => 'sess_security'
        ],
        [
            'id' => 8,
            'type' => 'data_export',
            'type_label' => 'Data Export',
            'icon' => 'fas fa-download',
            'title' => 'Student Data Exported',
            'description' => 'Complete student database exported to CSV format',
            'user' => 'Admin User',
            'user_id' => 1,
            'user_role' => 'Administrator',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-5 hours')),
            'relative_time' => '5 hours ago',
            'priority' => 'medium',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 9,
            'type' => 'faculty_updated',
            'type_label' => 'Faculty Updated',
            'icon' => 'fas fa-chalkboard-teacher',
            'title' => 'Faculty Profile Modified',
            'description' => 'Dr. Sarah Johnson\'s department assignment was changed',
            'user' => 'Admin User',
            'user_id' => 1,
            'user_role' => 'Administrator',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours')),
            'relative_time' => '6 hours ago',
            'priority' => 'medium',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'session_id' => 'sess_' . uniqid()
        ],
        [
            'id' => 10,
            'type' => 'system_alert',
            'type_label' => 'System Alert',
            'icon' => 'fas fa-exclamation-triangle',
            'title' => 'High Clearance Volume',
            'description' => 'System detected unusually high clearance submission volume',
            'user' => 'System Monitor',
            'user_id' => 0,
            'user_role' => 'System',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-7 hours')),
            'relative_time' => '7 hours ago',
            'priority' => 'high',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'System Monitor',
            'session_id' => 'sess_monitor'
        ]
    ];
}

/**
 * Insert a real audit log row
 */
function logActivity(int $userId, string $activityType, array $details = [])
{
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $userId,
            $activityType,
            json_encode($details, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Throwable $e) {
        // Fail silently – audit logging should not break primary action
    }
}

/**
 * Get activity by ID
 */
function getActivityById($id) {
    $activities = getDemoActivities();
    
    foreach ($activities as $activity) {
        if ($activity['id'] == $id) {
            return $activity;
        }
    }
    
    return null;
}

/**
 * Export activities to various formats
 */
function exportActivities($activities, $format = 'csv') {
    switch ($format) {
        case 'csv':
            return exportToCSV($activities);
        case 'excel':
            return exportToExcel($activities);
        case 'pdf':
            return exportToPDF($activities);
        default:
            return exportToCSV($activities);
    }
}

/**
 * Export to CSV format
 */
function exportToCSV($activities) {
    $filename = 'audit_trail_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['ID', 'Type', 'Title', 'Description', 'User', 'Priority', 'Timestamp', 'IP Address']);
    
    // CSV data
    foreach ($activities as $activity) {
        fputcsv($output, [
            $activity['id'],
            $activity['type_label'] ?? $activity['type'],
            $activity['title'],
            $activity['description'],
            $activity['user'],
            $activity['priority'],
            $activity['timestamp'],
            $activity['ip_address']
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Export to Excel format (placeholder)
 */
function exportToExcel($activities) {
    // TODO: Implement Excel export using PHPSpreadsheet or similar library
    return 'Excel export not yet implemented';
}

/**
 * Export to PDF format (placeholder)
 */
function exportToPDF($activities) {
    // Implementation for PDF export
    // This would typically use a library like TCPDF, mPDF, or similar
    return "PDF export functionality to be implemented";
}

/**
 * AJAX handler for getting activities
 */
function handleGetActivities($filters, $limit = 10) {
    $type = $filters['type'] ?? '';
    $priority = $filters['priority'] ?? '';
    $user = $filters['user'] ?? '';
    $dateRange = $filters['dateRange'] ?? '';
    
    // Parse date range if provided
    $date_from = '';
    $date_to = '';
    if ($dateRange) {
        $dates = explode(' - ', $dateRange);
        if (count($dates) == 2) {
            $date_from = trim($dates[0]);
            $date_to = trim($dates[1]);
        }
    }
    
    $activities = getFilteredActivities($type, $priority, $user, $date_from, $date_to, '', $limit, 0);
    $stats = getActivityStatistics();
    
    return [
        'success' => true,
        'activities' => $activities,
        'stats' => $stats
    ];
}

/**
 * AJAX handler for getting activity details
 */
function handleGetActivityDetails($activity_id) {
    $activity = getActivityById($activity_id);
    
    if ($activity) {
        return [
            'success' => true,
            'activity' => $activity
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Activity not found'
        ];
    }
}

/**
 * AJAX request handler
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        switch ($action) {
            case 'get_activities':
                $filters = json_decode($_POST['filters'] ?? '{}', true) ?: [];
                $limit = intval($_POST['limit'] ?? 10);
                $response = handleGetActivities($filters, $limit);
                break;
                
            case 'get_activity_details':
                $activity_id = intval($_POST['activity_id'] ?? 0);
                if ($activity_id > 0) {
                    $response = handleGetActivityDetails($activity_id);
                } else {
                    $response = ['success' => false, 'message' => 'Invalid activity ID'];
                }
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Unknown action'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}
?>
