<?php
// -----------------------------------------------------------------------------
// CLI Smoke-test: Dashboard Global-Apply Logic
// -----------------------------------------------------------------------------
// Usage:  php test_dashboard_apply.php
// Requires: student1 / faculty1 demo accounts with passwords you define below.
// -----------------------------------------------------------------------------

$BASE = 'http://localhost/OnlineClearanceWebsite';
$USERS = [
    [ 'username'=>'student1',  'password'=>'student123',  'role'=>'Student' ],
    [ 'username'=>'faculty1',  'password'=>'faculty123',  'role'=>'Faculty' ],
];

foreach($USERS as $u){
    echo "\n==== Testing {$u['role']} ({$u['username']}) ====\n";
    $cookie = tempnam(sys_get_temp_dir(), 'ck');

    // 1) login
    $loginRes = curl_json("$BASE/api/auth/login.php", [
        'username'=>$u['username'],
        'password'=>$u['password']
    ], $cookie);
    if(!$loginRes['success']){ echo "Login failed: {$loginRes['message']}\n"; continue; }
    echo "Login OK\n";

    // 2) status before
    $statusBefore = curl_get("$BASE/api/clearance/status.php", $cookie);
    echo 'applied(before)=' . var_export($statusBefore['applied'],true) . "\n";

    // 3) apply_all
    $apply = curl_json("$BASE/api/clearance/apply_all.php", [], $cookie);
    echo 'apply_all => ' . json_encode($apply) . "\n";

    // 4) status after
    $statusAfter = curl_get("$BASE/api/clearance/status.php", $cookie);
    echo 'applied(after)=' . var_export($statusAfter['applied'],true) . "\n";
    $sigCt = $statusAfter['signatories'] ?? [];
    echo 'signatories rows=' . count($sigCt) . "\n";

    // quick verdict
    if($statusAfter['applied'] && count($sigCt)>0){
        echo "PASS: Global apply successful\n";
    }else{
        echo "FAIL: Signatories not populated\n";
    }

    unlink($cookie);
}

// ---------------- helpers -----------------
function curl_json($url, array $payload, $cookie){
    $ch = curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($payload),
        CURLOPT_COOKIEJAR=>$cookie,
        CURLOPT_COOKIEFILE=>$cookie,
        CURLOPT_FAILONERROR=>false,
    ]);
    $out = curl_exec($ch);
    if($out===false){ return ['success'=>false,'message'=>curl_error($ch)]; }
    return json_decode($out,true)??['success'=>false,'message'=>'Invalid JSON'];
}
function curl_get($url,$cookie){
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_COOKIEJAR=>$cookie,
        CURLOPT_COOKIEFILE=>$cookie,
    ]);
    $out=curl_exec($ch);
    return json_decode($out,true)??['success'=>false,'message'=>'Invalid JSON'];
}
