<?php
/**
 * Logout Page
 *
 * This script handles the user logout process. It destroys the current
 * session and redirects the user to the login page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/classes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit();