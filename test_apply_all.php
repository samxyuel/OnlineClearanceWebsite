<?php
$BASE='http://localhost/OnlineClearanceWebsite';
$cookie=tempnam(sys_get_temp_dir(),'sess_');
function post($url,$payload,$cookie){$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie]);$out=curl_exec($ch);curl_close($ch);return $out;}
// login
echo "Login as student1...\n";
echo post($BASE.'/api/auth/login.php',["username"=>"student1","password"=>"student123"],$cookie)."\n";
// apply all
echo "Apply all...\n";
echo post($BASE.'/api/clearance/apply_all.php',[],$cookie)."\n";
unlink($cookie);
