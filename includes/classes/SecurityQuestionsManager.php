<?php
require_once __DIR__ . '/../config/database.php';

class SecurityQuestionsManager {
    private $db;
    private $connection;
    
    // Encryption configuration
    private const ENCRYPTION_METHOD = 'AES-256-CBC';
    private const ENCRYPTION_KEY = 'your-secret-encryption-key-change-this-in-production'; // TODO: Move to config file
    
    // Question mapping: key => full text
    private static $questionMap = [
        // Question 1 options
        'mother_maiden_name' => "What is your mother's maiden name?",
        'birth_city' => 'What city were you born in?',
        'first_pet' => "What was the name of your first pet?",
        'elementary_school' => 'What was the name of your elementary school?',
        'childhood_nickname' => 'What was your childhood nickname?',
        'favorite_teacher' => "What was the name of your favorite teacher?",
        'first_car' => 'What was the make and model of your first car?',
        'childhood_friend' => "What was the name of your childhood best friend?",
        
        // Question 2 options
        'father_middle_name' => "What is your father's middle name?",
        'birth_hospital' => 'What hospital were you born in?',
        'favorite_food' => 'What was your favorite food as a child?',
        'high_school' => 'What was the name of your high school?',
        'street_grew_up' => 'What street did you grow up on?',
        'favorite_sport' => 'What was your favorite sport in high school?',
        'first_job' => 'What was your first job?',
        'wedding_anniversary' => 'What is your wedding anniversary date? (MM/DD/YYYY)',
        
        // Question 3 options
        'sibling_name' => "What is your oldest sibling's middle name?",
        'grandmother_name' => "What is your maternal grandmother's first name?",
        'favorite_movie' => 'What was your favorite movie as a child?',
        'college_name' => 'What college did you attend?',
        'first_concert' => 'What was the first concert you attended?',
        'favorite_place' => 'What is your favorite place to visit?',
        'childhood_hero' => 'Who was your childhood hero?',
        'favorite_book' => 'What was your favorite book as a child?'
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }
    
    /**
     * Get question text by key
     */
    public static function getQuestionText($key) {
        return self::$questionMap[$key] ?? null;
    }
    
    /**
     * Get all available questions
     */
    public static function getAllQuestions() {
        return self::$questionMap;
    }
    
    /**
     * Hash answer (normalize to lowercase, trim, then hash)
     */
    public function hashAnswer($answer) {
        $normalized = strtolower(trim($answer));
        return password_hash($normalized, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify answer against hash (case-insensitive)
     */
    public function verifyAnswer($answer, $hash) {
        $normalized = strtolower(trim($answer));
        return password_verify($normalized, $hash);
    }
    
    /**
     * Encrypt answer for display storage
     */
    private function encryptAnswer($answer) {
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($answer, self::ENCRYPTION_METHOD, self::ENCRYPTION_KEY, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt answer for display
     */
    private function decryptAnswer($encryptedAnswer) {
        if (empty($encryptedAnswer)) {
            return null;
        }
        try {
            $data = base64_decode($encryptedAnswer);
            $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            return openssl_decrypt($encrypted, self::ENCRYPTION_METHOD, self::ENCRYPTION_KEY, 0, $iv);
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user has partial security questions setup (1 or 2 questions)
     * Returns: ['has_partial' => bool, 'count' => int]
     */
    public function checkPartialSetup($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT 
                    CASE WHEN question_1 IS NOT NULL AND question_1 != '' THEN 1 ELSE 0 END +
                    CASE WHEN question_2 IS NOT NULL AND question_2 != '' THEN 1 ELSE 0 END +
                    CASE WHEN question_3 IS NOT NULL AND question_3 != '' THEN 1 ELSE 0 END as question_count
                FROM user_security_questions
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return ['has_partial' => false, 'count' => 0];
            }
            
            $count = (int)$result['question_count'];
            return [
                'has_partial' => ($count > 0 && $count < 3),
                'count' => $count
            ];
            
        } catch (PDOException $e) {
            error_log("Error checking partial setup: " . $e->getMessage());
            return ['has_partial' => false, 'count' => 0];
        }
    }
    
    /**
     * Save or update security questions for a user
     */
    public function saveSecurityQuestions($userId, $questions, $answers) {
        try {
            // Validate input
            if (empty($userId) || !is_numeric($userId)) {
                return ['success' => false, 'message' => 'Invalid user ID'];
            }
            
            if (!is_array($questions) || count($questions) !== 3) {
                return ['success' => false, 'message' => 'Exactly 3 questions are required'];
            }
            
            if (!is_array($answers) || count($answers) !== 3) {
                return ['success' => false, 'message' => 'Exactly 3 answers are required'];
            }
            
            // Validate all questions are different
            if (count(array_unique($questions)) !== 3) {
                return ['success' => false, 'message' => 'All security questions must be different'];
            }
            
            // Validate all question keys exist
            foreach ($questions as $question) {
                if (!isset(self::$questionMap[$question])) {
                    return ['success' => false, 'message' => "Invalid question key: $question"];
                }
            }
            
            // Validate all answers are provided and not empty
            foreach ($answers as $index => $answer) {
                if (empty(trim($answer))) {
                    return ['success' => false, 'message' => "Answer " . ($index + 1) . " cannot be empty"];
                }
            }
            
            // Hash all answers (for verification)
            $hashedAnswers = [
                $this->hashAnswer($answers[0]),
                $this->hashAnswer($answers[1]),
                $this->hashAnswer($answers[2])
            ];
            
            // Encrypt all answers (for display)
            $encryptedAnswers = [
                $this->encryptAnswer($answers[0]),
                $this->encryptAnswer($answers[1]),
                $this->encryptAnswer($answers[2])
            ];
            
            // Check if user already has security questions
            $stmt = $this->connection->prepare("SELECT security_question_id FROM user_security_questions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $sql = "UPDATE user_security_questions 
                        SET question_1 = ?, answer_1 = ?, answer_1_display = ?,
                            question_2 = ?, answer_2 = ?, answer_2_display = ?,
                            question_3 = ?, answer_3 = ?, answer_3_display = ?,
                            updated_at = NOW()
                        WHERE user_id = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([
                    $questions[0], $hashedAnswers[0], $encryptedAnswers[0],
                    $questions[1], $hashedAnswers[1], $encryptedAnswers[1],
                    $questions[2], $hashedAnswers[2], $encryptedAnswers[2],
                    $userId
                ]);
            } else {
                // Insert new
                $sql = "INSERT INTO user_security_questions 
                        (user_id, question_1, answer_1, answer_1_display, 
                         question_2, answer_2, answer_2_display, 
                         question_3, answer_3, answer_3_display, 
                         created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([
                    $userId,
                    $questions[0], $hashedAnswers[0], $encryptedAnswers[0],
                    $questions[1], $hashedAnswers[1], $encryptedAnswers[1],
                    $questions[2], $hashedAnswers[2], $encryptedAnswers[2]
                ]);
            }
            
            // Log activity
            $this->logActivity($userId, 'security_questions_updated', 'Security questions saved/updated');
            
            return [
                'success' => true,
                'message' => 'Security questions saved successfully'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get security questions for a user (returns question keys, full text, and decrypted answers)
     */
    public function getSecurityQuestions($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT question_1, question_2, question_3, 
                       answer_1_display, answer_2_display, answer_3_display,
                       created_at, updated_at
                FROM user_security_questions
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return [
                    'success' => true,
                    'has_questions' => false,
                    'has_partial' => false,
                    'data' => null
                ];
            }
            
            // Check for partial setup
            $partialCheck = $this->checkPartialSetup($userId);
            
            // Decrypt answers for display
            $decryptedAnswers = [
                $this->decryptAnswer($result['answer_1_display'] ?? null),
                $this->decryptAnswer($result['answer_2_display'] ?? null),
                $this->decryptAnswer($result['answer_3_display'] ?? null)
            ];
            
            // Convert question keys to full text
            $questions = [
                [
                    'key' => $result['question_1'],
                    'text' => self::getQuestionText($result['question_1']),
                    'answer' => $decryptedAnswers[0]
                ],
                [
                    'key' => $result['question_2'],
                    'text' => self::getQuestionText($result['question_2']),
                    'answer' => $decryptedAnswers[1]
                ],
                [
                    'key' => $result['question_3'],
                    'text' => self::getQuestionText($result['question_3']),
                    'answer' => $decryptedAnswers[2]
                ]
            ];
            
            return [
                'success' => true,
                'has_questions' => true,
                'has_partial' => $partialCheck['has_partial'],
                'question_count' => $partialCheck['count'],
                'data' => [
                    'questions' => $questions,
                    'created_at' => $result['created_at'],
                    'updated_at' => $result['updated_at']
                ]
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get security questions by username (for forgot password flow)
     */
    public function getSecurityQuestionsByUsername($username) {
        try {
            // First get user_id from username
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            return $this->getSecurityQuestions($user['user_id']);
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate security question answers
     * Returns: ['success' => bool, 'message' => string, 'all_correct' => bool]
     */
    public function validateAnswers($userId, $answers) {
        try {
            if (!is_array($answers) || count($answers) !== 3) {
                return [
                    'success' => false,
                    'message' => 'Exactly 3 answers are required',
                    'all_correct' => false
                ];
            }
            
            // Get stored questions and hashed answers
            $stmt = $this->connection->prepare("
                SELECT question_1, answer_1, question_2, answer_2, question_3, answer_3
                FROM user_security_questions
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stored) {
                return [
                    'success' => false,
                    'message' => 'Security questions not set for this user',
                    'all_correct' => false
                ];
            }
            
            // Verify each answer
            $correctCount = 0;
            $storedAnswers = [
                $stored['answer_1'],
                $stored['answer_2'],
                $stored['answer_3']
            ];
            
            foreach ($answers as $index => $answer) {
                if ($this->verifyAnswer($answer, $storedAnswers[$index])) {
                    $correctCount++;
                }
            }
            
            $allCorrect = ($correctCount === 3);
            
            return [
                'success' => true,
                'message' => $allCorrect ? 'All answers are correct' : 'One or more answers are incorrect',
                'all_correct' => $allCorrect,
                'correct_count' => $correctCount
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'all_correct' => false
            ];
        }
    }
    
    /**
     * Validate security question answers by username (for forgot password flow)
     */
    public function validateAnswersByUsername($username, $answers) {
        try {
            // First get user_id from username
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'all_correct' => false
                ];
            }
            
            return $this->validateAnswers($user['user_id'], $answers);
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'all_correct' => false
            ];
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
            // Log error silently to avoid breaking the main operation
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}
?>

