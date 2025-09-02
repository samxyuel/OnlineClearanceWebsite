<?php
$BASE='http://localhost/OnlineClearanceWebsite';
$cookie=tempnam(sys_get_temp_dir(),'sess_');
function http($url,$payload,$cookie){$ch=curl_init($url);curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_HTTPHEADER,['Content-Type: application/json']);curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload));curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie);curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie);$out=curl_exec($ch);curl_close($ch);return $out;}
// login
echo http($BASE.'/api/auth/login.php',["username"=>"admin","password"=>"admin123"],$cookie)."\n\n";
// bulk assign
$payload=["assignments"=>[
    ["user_id"=>3,"designation"=>"Program Head","department_id"=>5,"staff_category"=>"Program Head"],
    ["user_id"=>4,"designation"=>"Cashier","staff_category"=>"Regular Staff"]
]];
echo http($BASE.'/api/signatories/bulk_assign.php',$payload,$cookie)."\n";
unlink($cookie);
