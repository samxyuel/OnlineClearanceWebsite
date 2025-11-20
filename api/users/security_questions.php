<?php
/**
 * API: Security Questions Management
 * 
 * GET: Retrieve user's security questions (for profile page)
 * POST: Save/update security questions (from user profile)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/classes/SecurityQuestionsManager.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$securityQuestionsManager = new SecurityQuestionsManager();
$currentUser = $auth->getCurrentUser();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get user's security questions
        $result = $securityQuestionsManager->getSecurityQuestions($currentUser['user_id']);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;
        
    case 'POST':
        // Save/update security questions
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request data']);
            exit;
        }
        
        // Validate required fields - ALL 3 questions and answers are required
        if (empty($input['questions']) || !is_array($input['questions']) || count($input['questions']) !== 3) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exactly 3 questions are required. You must set up all security questions together.']);
            exit;
        }
        
        if (empty($input['answers']) || !is_array($input['answers']) || count($input['answers']) !== 3) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exactly 3 answers are required. You must set up all security questions together.']);
            exit;
        }
        
        // Validate that all questions are selected (not empty)
        foreach ($input['questions'] as $index => $question) {
            if (empty(trim($question))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'All 3 security questions must be selected.']);
                exit;
            }
        }
        
        // Validate that all answers are provided (not empty)
        foreach ($input['answers'] as $index => $answer) {
            if (empty(trim($answer))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'All 3 answers must be provided.']);
                exit;
            }
        }
        
        // Save security questions
        $result = $securityQuestionsManager->saveSecurityQuestions(
            $currentUser['user_id'],
            $input['questions'],
            $input['answers']
        );
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>

