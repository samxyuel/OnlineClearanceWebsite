<?php
// -----------------------------------------------------------------------------
// QUICK SIGNATORY ENDPOINT SMOKE-TEST (CLI)
// -----------------------------------------------------------------------------
// Usage (from project root):
//   php test_signatory_endpoints.php
//
// What it does:
//   1) Logs in as admin via /api/auth/login.php and stores the session cookie.
//   2) Calls /api/signatories/assign.php to assign a designation to a user.
//   3) Calls /api/signatories/unassign.php to deactivate the same signatory.
//   4) Prints JSON responses so you can verify success / error.
// -----------------------------------------------------------------------------

$BASE_URL        = 'http://localhost/OnlineClearanceWebsite';
$LOGIN_ENDPOINT  = $BASE_URL . '/api/auth/login.php';
$ASSIGN_ENDPOINT = $BASE_URL . '/api/signatories/assign.php';
$UNSIGN_ENDPOINT = $BASE_URL . '/api/signatories/unassign.php';

// ------------- CONFIG -------------
$adminUsername   = 'admin';      // change if needed
$adminPassword   = 'admin123';   // change if needed

// user/designation to test
$testUserId      = 2;            // any active Staff user_id
$designation     = 'Registrar';  // must exist in designations
$staffCategory   = 'Regular Staff';
//-----------------------------------

$cookieFile = tempnam(sys_get_temp_dir(), 'sess_');

function curl_json(string $url, array $payload, string $cookieFile = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        fwrite(STDERR, "cURL error: " . curl_error($ch) . PHP_EOL);
    }
    curl_close($ch);
    return $response;
}

// -----------------------------------------------------------------------------
// 1) LOGIN
// -----------------------------------------------------------------------------
echo "Logging in as admin...\n";
$loginResponse = curl_json(
    $LOGIN_ENDPOINT,
    ['username' => $adminUsername, 'password' => $adminPassword],
    $cookieFile
);

echo "Login response:\n$loginResponse\n\n";
$loginJson = json_decode($loginResponse, true);
if (!$loginJson['success'] ?? false) {
    exit("Login failed – aborting.\n");
}

// -----------------------------------------------------------------------------
// 2) ASSIGN
// -----------------------------------------------------------------------------
echo "Assigning user #{$testUserId} as {$designation}...\n";
$assignResponse = curl_json(
    $ASSIGN_ENDPOINT,
    [
        'user_id'       => $testUserId,
        'designation'   => $designation,
        'staff_category'=> $staffCategory
    ],
    $cookieFile
);

echo "Assign response:\n$assignResponse\n\n";

// -----------------------------------------------------------------------------
// 3) UNASSIGN
// -----------------------------------------------------------------------------
$assignJson = json_decode($assignResponse, true);
if (($assignJson['success'] ?? false) === true) {
    echo "Un-assigning user #{$testUserId}...\n";
    $unsignResponse = curl_json(
        $UNSIGN_ENDPOINT,
        [
            'user_id'     => $testUserId,
            'designation' => $designation
        ],
        $cookieFile
    );
    echo "Un-assign response:\n$unsignResponse\n\n";
} else {
    echo "Skipping un-assign because assignment failed.\n";
}

@unlink($cookieFile);

echo "Test complete.\n";
// -----------------------------------------------------------------------------
