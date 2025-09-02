<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if(!$auth->isLoggedIn()){
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit;
}

try{
    $pdo = Database::getInstance()->getConnection();

    // Current school year (active)
    $ay = $pdo->query("SELECT academic_year_id, year FROM academic_years WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if(!$ay){ echo json_encode(['success'=>true,'academic_year'=>null,'terms'=>[]]); exit; }

    // Find semester ids for '1st' and '2nd' for this academic year (may be NULL if not created)
    $stmt = $pdo->prepare("SELECT semester_id, semester_name FROM semesters WHERE academic_year_id=? AND semester_name IN ('1st','2nd')");
    $stmt->execute([$ay['academic_year_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = ['1st'=>null,'2nd'=>null];
    foreach($rows as $r){ $map[$r['semester_name']] = (int)$r['semester_id']; }

    echo json_encode([
        'success'=>true,
        'academic_year'=>[ 'academic_year_id'=>(int)$ay['academic_year_id'], 'year'=>$ay['year'] ],
        'terms'=>[
            ['label'=>'Term 1','semester_name'=>'1st','semester_id'=>$map['1st']],
            ['label'=>'Term 2','semester_name'=>'2nd','semester_id'=>$map['2nd']]
        ]
    ]);

}catch(Exception $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
?>


