<?php
// controllers/StudentManagementController.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function handleStudentManagementPageRequest() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../../pages/auth/login.php');
        exit;
    }

    $userId = (int)$auth->getUserId();

    try {
        $pdo = Database::getInstance()->getConnection();
        
        // 1. Check if there is an active clearance period
        $hasActivePeriod = (int)$pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE status = 'Ongoing'")->fetchColumn() > 0;

        // 2. Check if the staff user is assigned as a student signatory
        $staffStmt = $pdo->prepare("SELECT designation_id FROM staff WHERE user_id = ? AND is_active = 1");
        $staffStmt->execute([$userId]);
        $designationId = $staffStmt->fetchColumn();

        $hasStudentSignatoryAccess = false;
        if ($designationId) {
            $scopeStmt = $pdo->prepare("SELECT COUNT(*) FROM sector_signatory_assignments WHERE designation_id = ? AND clearance_type IN ('College', 'Senior High School') AND is_active = 1");
            $scopeStmt->execute([$designationId]);
            $hasStudentSignatoryAccess = (int)$scopeStmt->fetchColumn() > 0;
        }

        if (!$designationId) {
            http_response_code(403);
            die('Access Denied: You are not an active staff member.');
        }

        // Store permission flags for use in the page
        $GLOBALS['hasActivePeriod'] = $hasActivePeriod;
        $GLOBALS['hasStudentSignatoryAccess'] = $hasStudentSignatoryAccess;
        $GLOBALS['canPerformSignatoryActions'] = $hasActivePeriod && $hasStudentSignatoryAccess;

    } catch (Throwable $e) {
        http_response_code(500);
        // In a real app, you would log the error: error_log($e->getMessage());
        die('A server error occurred. Please try again later.');
    }
}