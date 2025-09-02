<?php
// -----------------------------------------------------------------------------
// CLEARANCE PER-SIGNATORY APPLY SMOKE TEST (CLI)
// -----------------------------------------------------------------------------
// Usage:
//   php test_apply_signatory.php
//
// What it does:
//   1) Logs in as a regular end-user (Student or Faculty) via /api/auth/login.php
//      – edit the credentials below before running.
//   2) Calls /api/clearance/apply.php for several designation_id values.
//   3) Attempts an invalid designation_id to confirm 400/failed response.
//   4) Prints all JSON responses so you can eyeball success / error.
// -----------------------------------------------------------------------------

$BASE_URL        = 'http://localhost/OnlineClearanceWebsite';
$LOGIN_ENDPOINT  = $BASE_URL . '/api/auth/login.php';
$APPLY_ENDPOINT  = $BASE_URL . '/api/clearance/apply.php';

// -----------------------  CONFIG  -------------------------------------------
$userUsername = 'faculty1';    // <-- CHANGE to a valid Student / Faculty username
$userPassword = 'faculty123';  // <-- CHANGE to that user\'s password

// designation IDs to test (must exist in `designations` table)
$designationIds = [2, 3, 1];   // Cashier, Librarian, Registrar for example
// -----------------------------------------------------------------------------

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

// LOGIN ----------------------------------------------------------------------
echo "Logging in as {$userUsername}...\n";
$loginResponse = curl_json($LOGIN_ENDPOINT, ['username'=>$userUsername,'password'=>$userPassword], $cookieFile);
echo "Login response:\n$loginResponse\n\n";
$loginJson = json_decode($loginResponse, true);
if (!($loginJson['success'] ?? false)) {
    exit("Login failed – aborting.\n");
}

// APPLY ----------------------------------------------------------------------
foreach ($designationIds as $id) {
    echo "Applying to designation_id {$id}...\n";
    $applyResponse = curl_json($APPLY_ENDPOINT, ['designation_id'=>$id], $cookieFile);
    echo "Response: $applyResponse\n\n";
}

// INVALID INPUT --------------------------------------------------------------
echo "Testing invalid designation_id 9999...\n";
$invalidResp = curl_json($APPLY_ENDPOINT, ['designation_id'=>9999], $cookieFile);
echo "Invalid-input response: $invalidResp\n\n";

@unlink($cookieFile);

echo "Smoke test complete.\n";
?>
