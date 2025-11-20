<?php
/**
 * API: Bulk Signatory Action
 *
 * Performs a bulk approval or rejection for multiple applicants in a single request.
 * This is more efficient than making N+1 requests from the client.
 *
 * Method: POST
 * Body: {
 *   "applicant_user_ids": [123, 124, 125],   // required - array of target applicant user_ids
 *   "action": "Approved" | "Rejected",       // required
 *   "designation_name": "Program Head",      // required - name of the acting designation
 *   "remarks": "optional text",              // optional
 *   "reason_id": 5                           // optional, for rejections
 * }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/functions/audit_functions.php';

// --- Authentication and Authorization ---
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$actingUserId = $auth->getUserId();
$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
if (
    !$input ||
    empty($input['applicant_user_ids']) || !is_array($input['applicant_user_ids']) ||
    empty($input['action']) ||
    empty($input['designation_name'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: applicant_user_ids (array), action, and designation_name are required.']);
    exit;
}

$applicantUserIds = array_filter(array_map('intval', $input['applicant_user_ids']));
$action = trim($input['action']);
$designationName = trim($input['designation_name']);
$remarks = isset($input['remarks']) ? trim($input['remarks']) : null;
$reasonId = isset($input['reason_id']) ? (int)$input['reason_id'] : null;

if (empty($applicantUserIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'applicant_user_ids cannot be empty.']);
    exit;
}

if ($action !== 'Approved' && $action !== 'Rejected') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action. Must be "Approved" or "Rejected".']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // --- Get Designation ID ---
    $desigStmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ?");
    $desigStmt->execute([$designationName]);
    $designationId = $desigStmt->fetchColumn();

    if (!$designationId) {
        throw new Exception("Designation '{$designationName}' not found.");
    }

    // --- Get Active Period ---
    // Check if school_term parameter is provided to use specific period
    $schoolTerm = isset($input['school_term']) ? trim($input['school_term']) : '';
    $activePeriod = null;
    
    if (!empty($schoolTerm)) {
        // Parse school_term format: "YEAR|semester_id" (e.g., "2024-2025|2")
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = isset($termParts[1]) ? (int)trim($termParts[1]) : null;
        
        if ($yearName && $semesterId) {
            // Find the period for the specified term (can be Ongoing or Closed)
            $periodStmt = $pdo->prepare("
                SELECT cp.academic_year_id, cp.semester_id 
                FROM clearance_periods cp
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                WHERE ay.year = ? AND cp.semester_id = ? AND cp.status IN ('Ongoing', 'Closed')
                ORDER BY 
                    CASE cp.status
                        WHEN 'Ongoing' THEN 1
                        WHEN 'Closed' THEN 2
                    END
                LIMIT 1
            ");
            $periodStmt->execute([$yearName, $semesterId]);
            $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Fallback to Ongoing period if no school_term provided or if lookup failed
    if (!$activePeriod) {
        $periodStmt = $pdo->query("SELECT academic_year_id, semester_id FROM clearance_periods WHERE status = 'Ongoing' LIMIT 1");
        $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$activePeriod) {
        throw new Exception("No active clearance period found.");
    }

    // --- Find Clearance Forms for the given users in the active period ---
    $placeholders = implode(',', array_fill(0, count($applicantUserIds), '?'));
    $formSql = "SELECT clearance_form_id FROM clearance_forms WHERE user_id IN ($placeholders) AND academic_year_id = ? AND semester_id = ?";
    
    $formParams = array_merge($applicantUserIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]);
    $formStmt = $pdo->prepare($formSql);
    $formStmt->execute($formParams);
    $formIds = $formStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($formIds)) {
        throw new Exception("No matching clearance forms found for the selected users in the active period.");
    }

    // --- Perform Bulk Update ---
    $formPlaceholders = implode(',', array_fill(0, count($formIds), '?'));
    $updateSql = "UPDATE clearance_signatories SET action = ?, updated_at = NOW()";
    $updateParams = [$action];

    if ($action === 'Approved') {
        $updateSql .= ", remarks = ?, reason_id = NULL, additional_remarks = NULL, actual_user_id = ?, date_signed = NOW()";
        array_push($updateParams, $remarks, $actingUserId);
    } else { // Rejected
        $updateSql .= ", remarks = NULL, reason_id = ?, additional_remarks = ?, actual_user_id = NULL, date_signed = NULL";
        array_push($updateParams, $reasonId, $remarks);
    }

    $updateSql .= " WHERE clearance_form_id IN ($formPlaceholders) AND designation_id = ?";
    $updateParams = array_merge($updateParams, $formIds);
    $updateParams[] = $designationId;

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);
    $affectedRows = $updateStmt->rowCount();

    // --- Logging and Committing ---
    logActivity($actingUserId, 'Bulk Signatory Action', [
        'action' => $action,
        'designation_id' => $designationId,
        'affected_user_count' => count($applicantUserIds),
        'affected_rows' => $affectedRows
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Successfully updated {$affectedRows} records.",
        'affected_rows' => $affectedRows
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}