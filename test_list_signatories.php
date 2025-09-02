<?php
$BASE = 'http://localhost/OnlineClearanceWebsite';
$cookie = tempnam(sys_get_temp_dir(),'sess_');
function call($url,$payload=null,$cookie){
 $ch=curl_init($url);
 curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 if($payload){
  curl_setopt($ch,CURLOPT_POST,true);
  curl_setopt($ch,CURLOPT_HTTPHEADER,['Content-Type: application/json']);
  curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload));
 }
 curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie);
 curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie);
 $resp=curl_exec($ch);
 curl_close($ch);
 return $resp;
}
// login
echo "Login...\n";
print(call($BASE.'/api/auth/login.php',["username"=>"admin","password"=>"admin123"],$cookie));
// list signatories
echo "\nList signatories (first 5)...\n";
print(call($BASE.'/api/signatories/list.php?limit=5',null,$cookie));
unlink($cookie);
