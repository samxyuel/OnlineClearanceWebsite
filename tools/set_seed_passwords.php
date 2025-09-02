<?php
// Seed password fixer: sets password = Last_Name + Employee_Number for seeded users
// Usage:
//  - Place this file at tools/set_seed_passwords.php
//  - Visit http://localhost/OnlineClearanceWebsite/tools/set_seed_passwords.php
//  - It will hash and set passwords only for the listed usernames where password is empty

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config/database.php';

function respond(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'DB connection failed', 'error' => $e->getMessage()], 500);
}

// Target seeded usernames
$usernames = [
    'LCA101P','LCA102P','LCA103P','LCA104P','LCA105P','LCA106P','LCA107P','LCA108P',
    'LCA109P','LCA110P','LCA111P','LCA112P','LCA113P','LCA114P','LCA115P','LCA116P',
    'PHC101P','PHS101P','PHF101P'
];

// Build placeholders for prepared statement
$placeholders = implode(',', array_fill(0, count($usernames), '?'));

try {
    // Select only accounts with empty or NULL password
    $sel = $pdo->prepare(
        "SELECT user_id, username, last_name FROM users 
         WHERE username IN ($placeholders) 
           AND (password IS NULL OR password = '')"
    );
    $sel->execute($usernames);
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        respond(['success' => true, 'updated' => 0, 'message' => 'No matching users with empty passwords']);
    }

    $upd = $pdo->prepare(
        'UPDATE users SET password = ?, must_change_password = 1, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?'
    );

    $updated = 0;
    $updatedUsernames = [];

    foreach ($rows as $r) {
        $plain = (string)$r['last_name'] . (string)$r['username']; // e.g., CashierLCA116P
        $hash  = password_hash($plain, PASSWORD_BCRYPT);
        $upd->execute([$hash, (int)$r['user_id']]);
        $updated++;
        $updatedUsernames[] = $r['username'];
    }

    respond([
        'success' => true,
        'updated' => $updated,
        'usernames' => $updatedUsernames
    ]);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Update failed', 'error' => $e->getMessage()], 500);
}

?>


