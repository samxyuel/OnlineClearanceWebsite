<?php
require_once __DIR__ . '/../config/database.php';

class UserManager {
    private $db;
    private $connection;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }
    
    // Create new user
    public function createUser($userData) {
        try {
            // Validate required fields
            $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name', 'role_id'];
            foreach ($requiredFields as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }
            
            // Check if username already exists
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$userData['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Check if email already exists
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $passwordHash,
                $userData['first_name'],
                $userData['last_name'],
                $userData['status'] ?? 'active'
            ]);
            
            $userId = $this->connection->lastInsertId();
            
            // Assign role
            $this->assignRole($userId, $userData['role_id']);
            
            // Log activity
            $this->logActivity($userId, 'user_created', 'User account created');
            
            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $userId
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Create faculty (user + faculty table) with employee specifics
    public function createFaculty($data) {
        // Expected keys: employee_number, employment_status, first_name, last_name, middle_name?, email?, contact_number?
        // username & password are auto-generated outside but also passed in for consistency
        $required = ['employee_number', 'employment_status', 'username', 'password', 'first_name', 'last_name'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                return ['success'=>false,'message'=>"Field '$f' is required"];
            }
        }

        // Ensure employment_status matches enum values
        $statusMap = [
            'full-time' => 'Full Time',
            'part-time' => 'Part Time',
            'part-time-full-load' => 'Part Time - Full Load',
            'Full Time' => 'Full Time',
            'Part Time' => 'Part Time',
            'Part Time - Full Load' => 'Part Time - Full Load'
        ];
        $employmentStatus = $statusMap[$data['employment_status']] ?? null;
        if (!$employmentStatus) {
            return ['success'=>false,'message'=>'Invalid employment status'];
        }

        // Auto-generate placeholder email if none or blank provided
        $emailInput = $data['email'] ?? '';
        if ($emailInput === '') {
            $email = $data['employee_number'] . '@placeholder.local';
        } else {
            $email = $emailInput;
        }

        $userPayload = [
            'username'   => $data['username'],
            'email'      => $email,
            'password'   => $data['password'],
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'role_id'    => 4, // Faculty
            'status'     => 'active'
        ];

        try {
            $this->connection->beginTransaction();

            $userRes = $this->createUser($userPayload);
            if (!$userRes['success']) {
                $this->connection->rollBack();
                return $userRes;
            }

            $userId = $userRes['user_id'];

            // Insert into faculty table
            $stmt = $this->connection->prepare("INSERT INTO faculty (employee_number, user_id, employment_status, created_at) VALUES (?,?,?,NOW())");
            $stmt->execute([$data['employee_number'], $userId, $employmentStatus]);

            $this->connection->commit();
            return ['success'=>true,'message'=>'Faculty registered','user_id'=>$userId];

        } catch (PDOException $e) {
            $this->connection->rollBack();
            return ['success'=>false,'message'=>'Database error: '.$e->getMessage()];
        }
    }
    
    // Get user by ID
    public function getUserById($userId) {
        try {
            $sql = "SELECT u.*, ur.role_id, r.role_name 
                    FROM users u 
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                    LEFT JOIN roles r ON ur.role_id = r.role_id 
                    WHERE u.user_id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Get user by username
    public function getUserByUsername($username) {
        try {
            $sql = "SELECT u.*, ur.role_id, r.role_name 
                    FROM users u 
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                    LEFT JOIN roles r ON ur.role_id = r.role_id 
                    WHERE u.username = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Get all users with pagination
    public function getAllUsers($page = 1, $limit = 20, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['search'])) {
                $whereConditions[] = "(u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($filters['role_id'])) {
                $whereConditions[] = "ur.role_id = ?";
                $params[] = $filters['role_id'];
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "u.status = ?";
                $params[] = $filters['status'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(DISTINCT u.user_id) as total 
                        FROM users u 
                        LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                        $whereClause";
            $countStmt = $this->connection->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetch()['total'];
            
            // Get users
            $sql = "SELECT u.*, ur.role_id, r.role_name 
                    FROM users u 
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                    LEFT JOIN roles r ON ur.role_id = r.role_id 
                    $whereClause 
                    ORDER BY u.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            return [
                'success' => true,
                'users' => $users,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update user
    public function updateUser($userId, $userData) {
        try {
            // Check if user exists
            $existingUser = $this->getUserById($userId);
            if (!$existingUser) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Check username uniqueness (if changed)
            if (isset($userData['username']) && $userData['username'] !== $existingUser['username']) {
                $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $stmt->execute([$userData['username'], $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Username already exists'];
                }
            }
            
            // Check email uniqueness (if changed)
            if (isset($userData['email']) && $userData['email'] !== $existingUser['email']) {
                $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$userData['email'], $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email already exists'];
                }
            }
            
            // Build update query
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['username', 'email', 'first_name', 'last_name', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($userData[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $userData[$field];
                }
            }
            
            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            // Update role if provided
            if (isset($userData['role_id'])) {
                $this->updateUserRole($userId, $userData['role_id']);
            }
            
            // Log activity
            $this->logActivity($userId, 'user_updated', 'User account updated');
            
            return ['success' => true, 'message' => 'User updated successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Update faculty information (user + faculty table)
    public function updateFaculty($employeeId,$data){
        try{
            // fetch faculty row
            $row=$this->connection->prepare("SELECT user_id FROM faculty WHERE employee_number=?");
            $row->execute([$employeeId]);
            $faculty=$row->fetch(PDO::FETCH_ASSOC);
            if(!$faculty){return ['success'=>false,'message'=>'Faculty not found'];}
            $userId=$faculty['user_id'];

            // build user update payload
            $userPayload=[];
            foreach(['email','first_name','last_name','middle_name','contact_number','status'] as $f){
                if(isset($data[$f])) $userPayload[$f]=$data[$f];
            }
            if(!empty($userPayload)){
                $this->updateUser($userId,$userPayload);
            }

            // faculty table updates
            $facFields=[];$params=[];
            if(isset($data['employment_status']) && trim($data['employment_status'])!==''){ $facFields[]='employment_status=?'; $params[]=$data['employment_status']; }
            if(isset($data['department_id'])){ $facFields[]='department_id=?'; $params[]=$data['department_id']; }
            if(!empty($facFields)){
                $params[]=$employeeId;
                $sql="UPDATE faculty SET ".implode(', ',$facFields).", updated_at=NOW() WHERE employee_number=?";
                $stmt=$this->connection->prepare($sql);
                $stmt->execute($params);
            }
            return ['success'=>true,'message'=>'Faculty updated'];
        }catch(PDOException $e){return ['success'=>false,'message'=>$e->getMessage()];}
    }
    
    // Delete user
    public function deleteUser($userId) {
        try {
            // Check if user exists
            $existingUser = $this->getUserById($userId);
            if (!$existingUser) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Prevent deletion of admin users
            if ($existingUser['username'] === 'admin') {
                return ['success' => false, 'message' => 'Cannot delete admin user'];
            }
            
            // Start transaction
            $this->connection->beginTransaction();
            
            // Delete user roles
            $stmt = $this->connection->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Delete user
            $stmt = $this->connection->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Commit transaction
            $this->connection->commit();
            
            // Log activity
            $this->logActivity($userId, 'user_deleted', 'User account deleted');
            
            return ['success' => true, 'message' => 'User deleted successfully'];
            
        } catch (PDOException $e) {
            // Rollback transaction
            $this->connection->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Change user password
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->connection->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$newPasswordHash, $userId]);
            
            // Log activity
            $this->logActivity($userId, 'password_changed', 'Password changed');
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Reset user password (admin function)
    public function resetPassword($userId, $newPassword) {
        try {
            // Check if user exists
            $existingUser = $this->getUserById($userId);
            if (!$existingUser) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->connection->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$newPasswordHash, $userId]);
            
            // Log activity
            $this->logActivity($userId, 'password_reset', 'Password reset by administrator');
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Assign role to user
    public function assignRole($userId, $roleId) {
        try {
            // Remove existing role
            $stmt = $this->connection->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Assign new role
            $stmt = $this->connection->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $roleId]);
            
            return ['success' => true, 'message' => 'Role assigned successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update user role
    public function updateUserRole($userId, $roleId) {
        return $this->assignRole($userId, $roleId);
    }
    
    // Get all roles
    public function getAllRoles() {
        try {
            $stmt = $this->connection->query("SELECT * FROM roles WHERE is_active = 1 ORDER BY role_name");
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get user permissions
    public function getUserPermissions($userId) {
        try {
            $sql = "SELECT p.permission_name, p.description 
                    FROM permissions p 
                    JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                    JOIN user_roles ur ON rp.role_id = ur.role_id 
                    WHERE ur.user_id = ? AND p.is_active = 1";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Log user activity
    private function logActivity($userId, $activityType, $details) {
        try {
            $sql = "INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $userId,
                $activityType,
                json_encode(['details' => $details]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (PDOException $e) {
            // Log error silently to avoid breaking main functionality
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }

    // Delete faculty (hard delete faculty row, mark user as deleted)
    public function deleteFaculty($employeeId){
        try{
            // Find faculty
            $stmt=$this->connection->prepare("SELECT user_id FROM faculty WHERE employee_number=?");
            $stmt->execute([$employeeId]);
            $row=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row){return ['success'=>false,'message'=>'Faculty not found'];}
            $userId=$row['user_id'];

            $this->connection->beginTransaction();
            // Delete faculty row
            $del=$this->connection->prepare("DELETE FROM faculty WHERE employee_number=?");
            $del->execute([$employeeId]);

            // Rather than deleting users row (may break FK), mark as deleted
            $upd=$this->connection->prepare("UPDATE users SET status='deleted', updated_at=NOW() WHERE user_id=?");
            $upd->execute([$userId]);

            $this->connection->commit();
            return ['success'=>true,'message'=>'Faculty deleted'];
        }catch(PDOException $e){
            $this->connection->rollBack();
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }
}
?>
