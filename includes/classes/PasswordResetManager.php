<?php
require_once __DIR__ . '/../config/database.php';

class PasswordResetManager {
    private $db;
    private $connection;
    private const MAX_ATTEMPTS = 3;
    private const LOCKOUT_DURATION_MINUTES = 15;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }
    
    /**
     * Check if username is rate limited
     * Returns: ['is_locked' => bool, 'locked_until' => timestamp|null, 'message' => string]
     */
    public function checkRateLimit($username) {
        try {
            $stmt = $this->connection->prepare("
                SELECT failed_attempts, locked_until, last_failed_attempt
                FROM password_reset_attempts
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                // No record exists, not locked
                return [
                    'is_locked' => false,
                    'locked_until' => null,
                    'failed_attempts' => 0,
                    'message' => ''
                ];
            }
            
            // Check if currently locked
            if ($record['locked_until']) {
                $lockedUntil = new DateTime($record['locked_until']);
                $now = new DateTime();
                
                if ($now < $lockedUntil) {
                    // Still locked
                    $remainingMinutes = ceil(($lockedUntil->getTimestamp() - $now->getTimestamp()) / 60);
                    return [
                        'is_locked' => true,
                        'locked_until' => $record['locked_until'],
                        'failed_attempts' => $record['failed_attempts'],
                        'remaining_minutes' => $remainingMinutes,
                        'message' => "Too many failed attempts. Please try again in {$remainingMinutes} minute(s)."
                    ];
                } else {
                    // Lock expired, clear it
                    $this->clearAttempts($username);
                    return [
                        'is_locked' => false,
                        'locked_until' => null,
                        'failed_attempts' => 0,
                        'message' => ''
                    ];
                }
            }
            
            // Not locked, but has previous attempts
            return [
                'is_locked' => false,
                'locked_until' => null,
                'failed_attempts' => (int)$record['failed_attempts'],
                'message' => ''
            ];
            
        } catch (PDOException $e) {
            // On error, allow the attempt (fail open for availability)
            error_log("Rate limit check failed: " . $e->getMessage());
            return [
                'is_locked' => false,
                'locked_until' => null,
                'failed_attempts' => 0,
                'message' => ''
            ];
        }
    }
    
    /**
     * Record a failed attempt
     */
    public function recordFailedAttempt($username, $ipAddress = null, $userAgent = null) {
        try {
            $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgent = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            // Check if record exists
            $stmt = $this->connection->prepare("
                SELECT failed_attempts, locked_until
                FROM password_reset_attempts
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $newAttempts = 1;
            $lockedUntil = null;
            
            if ($existing) {
                // Increment attempts
                $newAttempts = (int)$existing['failed_attempts'] + 1;
                
                // If reached max attempts, set lockout
                if ($newAttempts >= self::MAX_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_DURATION_MINUTES . ' minutes'));
                }
                
                // Update existing record
                $stmt = $this->connection->prepare("
                    UPDATE password_reset_attempts
                    SET failed_attempts = ?,
                        last_failed_attempt = NOW(),
                        locked_until = ?,
                        ip_address = ?,
                        user_agent = ?,
                        updated_at = NOW()
                    WHERE username = ?
                ");
                $stmt->execute([
                    $newAttempts,
                    $lockedUntil,
                    $ipAddress,
                    $userAgent,
                    $username
                ]);
            } else {
                // Create new record
                if ($newAttempts >= self::MAX_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_DURATION_MINUTES . ' minutes'));
                }
                
                $stmt = $this->connection->prepare("
                    INSERT INTO password_reset_attempts
                    (username, failed_attempts, last_failed_attempt, locked_until, ip_address, user_agent, created_at, updated_at)
                    VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $username,
                    $newAttempts,
                    $lockedUntil,
                    $ipAddress,
                    $userAgent
                ]);
            }
            
            return [
                'success' => true,
                'failed_attempts' => $newAttempts,
                'is_locked' => ($lockedUntil !== null),
                'locked_until' => $lockedUntil
            ];
            
        } catch (PDOException $e) {
            error_log("Failed to record attempt: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to record attempt'];
        }
    }
    
    /**
     * Clear attempts (on successful password reset)
     */
    public function clearAttempts($username) {
        try {
            $stmt = $this->connection->prepare("
                DELETE FROM password_reset_attempts
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Failed to clear attempts: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to clear attempts'];
        }
    }
    
    /**
     * Generate a temporary reset token (for session-based reset)
     * In a production system, you might want to store this in a separate table
     * For now, we'll use a simple approach with session or return a token
     */
    public function generateResetToken($username) {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        
        // In a more secure implementation, you would:
        // 1. Store token in database with expiration
        // 2. Link token to username
        // 3. Validate token before allowing password reset
        
        // For this implementation, we'll return the token
        // The API will handle token validation through session or request validation
        return $token;
    }
    
    /**
     * Reset password for a user by username
     */
    public function resetPasswordByUsername($username, $newPassword) {
        try {
            // Validate password strength
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
            }
            
            // Get user_id from username
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->connection->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$passwordHash, $user['user_id']]);
            
            // Clear rate limiting attempts
            $this->clearAttempts($username);
            
            // Log activity (if we can get user_id)
            $this->logActivity($user['user_id'], 'password_reset_via_security_questions', 'Password reset via security questions');
            
            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Log activity
     */
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
            // Log error silently
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}
?>

