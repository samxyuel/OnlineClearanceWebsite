<?php
// -----------------------------------------------------------------------------
// Reset Current Clearance Period (Admin only)
// POST /api/clearance/reset_period.php
// -----------------------------------------------------------------------------
// Behaviour:
//   • Finds the active academic year + semester (and active clearance period)
//   • Sets every clearance_signatories.action back to NULL and wipes remarks
//   • Sets every clearance_forms.status back to 'Unapplied'
//   • Leaves rows in place so history/audit keeps IDs intact
//   • Writes an audit log entry
// Safety:
//   • Refuses to run if no active period found
//   • Refuses if the period is already closed (is_active = 0)
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/functions/audit_functions.php';

$auth = new Auth();
if(!$auth->isLoggedIn()){
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit;
}

// Only admins allowed (role_name == 'Admin' or hasPermission)
if($auth->getRoleName() !== 'Admin'){
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin permission required']); exit;
}

// Deprecated: Reset is no longer allowed for active periods.
http_response_code(400);
echo json_encode([
    'success'=>false,
    'message'=>'This endpoint is deprecated. Reset is allowed only for deactivated terms. Please deactivate the term, then call /api/clearance/reset_by_period.php with its period_id.'
]);
// -----------------------------------------------------------------------------
