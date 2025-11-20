<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $connection;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }
    
    // User authentication
    public function authenticate($username, $password) {
        try {
            // First, check if user exists (without checking account_status)
            $sql = "SELECT u.*, ur.role_id, r.role_name 
                    FROM users u 
                    JOIN user_roles ur ON u.user_id = ur.user_id 
                    JOIN roles r ON ur.role_id = r.role_id 
                    WHERE u.username = ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Check if user exists
            if (!$user) {
                return ['success' => false, 'message' => 'Account does not exist. Please contact the system administrator.'];
            }
            
            // Check if account is active
            if ($user['account_status'] !== 'active') {
                return ['success' => false, 'message' => 'Account is inactive. Please contact the system administrator.'];
            }
            
            // User exists and is active - verify password
            if (password_verify($password, $user['password'])) {
                // Start session and store user data
                $this->ensureSessionStarted();
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();

                // If user is any kind of staff, check if they also have a faculty role
                $staffRoles = ['Regular Staff', 'staff', 'Program Head', 'School Administrator'];
                if (in_array($user['role_name'], $staffRoles, true)) {
                    $facultyCheckSql = "SELECT COUNT(*) FROM faculty WHERE user_id = ?";
                    $facultyStmt = $this->connection->prepare($facultyCheckSql);
                    $facultyStmt->execute([$user['user_id']]);
                    $_SESSION['has_faculty_role'] = $facultyStmt->fetchColumn() > 0;
                } else {
                    $_SESSION['has_faculty_role'] = false;
                }
                
                // Log successful login
                $this->logActivity($user['user_id'], 'login', 'User logged in successfully');
                
                return [
                    'success' => true,
                    'user' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'role_name' => $user['role_name'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email']
                    ]
                ];
            }
            
            // Password is incorrect (user exists and is active)
            return ['success' => false, 'message' => 'Invalid password'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()];
        }
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        $this->ensureSessionStarted();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    // Ensure session is started without causing conflicts
    private function ensureSessionStarted() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role_id' => $_SESSION['role_id'],
            'role_name' => $_SESSION['role_name'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'email' => $_SESSION['email']
        ];
    }
    
    // User logout
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            @session_start();
        }
        
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Log logout activity
        if ($user_id) {
            $this->logActivity($user_id, 'logout', 'User logged out');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    // Check user permissions
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM role_permissions rp 
                    JOIN permissions p ON rp.permission_id = p.permission_id 
                    WHERE rp.role_id = ? AND p.permission_name = ? AND p.is_active = 1";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$_SESSION['role_id'], $permission]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Log user activity
    private function logActivity($user_id, $activity_type, $details) {
        try {
            $sql = "INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $user_id,
                $activity_type,
                json_encode(['details' => $details]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (PDOException $e) {
            // Log error silently to avoid breaking authentication
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }

    /** Get logged in user_id or null */
    public function getUserId() {
        if ($this->isLoggedIn()) {
            return $_SESSION['user_id'];
        }
        return null;
    }
    /** Get logged in role_name or null */
    public function getRoleName() {
        if ($this->isLoggedIn()) {
            return $_SESSION['role_name'];
        }
        return null;
    }
}
?>

