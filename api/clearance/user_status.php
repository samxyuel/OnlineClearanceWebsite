<?php
/**
 * User Clearance Status API
 * 
 * This API provides the current clearance status for the logged-in user,
 * integrating with the automatic form distribution system.
 */

// Start output buffering to prevent any HTML/warnings from breaking JSON
ob_start();

// Suppress warnings/notices that might output HTML (but log them)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, we'll handle them in JSON

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $connection = Database::getInstance()->getConnection();

    // Determine target user
    $targetUserIdParam = $_GET['user_id'] ?? null;
    $employeeNumberParam = $_GET['employee_number'] ?? null;
    $studentIdParam = $_GET['student_id'] ?? null; // NEW: Explicit student_id parameter
    $userRole = $auth->getRoleName();
    $userId = null;

    // Check if an admin/staff is looking up another user
    if (($targetUserIdParam || $employeeNumberParam || $studentIdParam) && in_array($userRole, ['Admin', 'School Administrator', 'Regular Staff', 'Program Head'])) {
        if ($targetUserIdParam) {
            // Direct user_id lookup
            $userId = (int)$targetUserIdParam;
        } elseif ($studentIdParam) {
            // Resolve student_id to user_id
            try {
                $stmt = $connection->prepare("SELECT user_id FROM students WHERE student_id = ? LIMIT 1");
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . implode(", ", $connection->errorInfo()));
                }
                $stmt->execute([$studentIdParam]);
                $userId = $stmt->fetchColumn();
                
                if (!$userId) {
                    // Clear any output buffer before sending JSON
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Student not found for the given student ID.']);
                    exit;
                }
            } catch (PDOException $e) {
                error_log("USER_STATUS_DEBUG: PDO Error resolving student_id: " . $e->getMessage());
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database error while looking up student.']);
                exit;
            } catch (Exception $e) {
                error_log("USER_STATUS_DEBUG: Error resolving student_id: " . $e->getMessage());
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error while looking up student.']);
                exit;
            }
        } elseif ($employeeNumberParam) {
            // Resolve employee_number to user_id (faculty/staff)
            try {
                $stmt = $connection->prepare("SELECT user_id FROM faculty WHERE employee_number = ? LIMIT 1");
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . implode(", ", $connection->errorInfo()));
                }
                $stmt->execute([$employeeNumberParam]);
                $userId = $stmt->fetchColumn();
                
                if (!$userId) {
                    // Clear any output buffer before sending JSON
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Faculty not found for the given employee number.']);
                    exit;
                }
            } catch (PDOException $e) {
                error_log("USER_STATUS_DEBUG: PDO Error resolving employee_number: " . $e->getMessage());
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database error while looking up faculty.']);
                exit;
            } catch (Exception $e) {
                error_log("USER_STATUS_DEBUG: Error resolving employee_number: " . $e->getMessage());
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error while looking up faculty.']);
                exit;
            }
        }
    } else {
        // Default to the logged-in user
        $userId = $auth->getUserId();
    }
    
    if (!$userId) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Could not determine the target user.']);
        exit;
    }

    // Check if specific form_id is requested
    $requestedFormId = $_GET['form_id'] ?? null;
    
    if ($requestedFormId) {
        // Fetch specific form by ID
        $sql = "
            SELECT 
                cf.clearance_form_id,
                cf.clearance_form_progress as form_status,
                cf.applied_at,
                cf.completed_at,
                cf.clearance_type,
                ay.year as academic_year,
                s.semester_name,
                s.semester_id,
                ay.academic_year_id
            FROM clearance_forms cf
            INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cf.semester_id = s.semester_id
            WHERE cf.clearance_form_id = ? AND cf.user_id = ?
            LIMIT 1
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$requestedFormId, $userId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form) {
            ob_clean();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance form not found']);
            exit;
        }
        
        $academicYearId = $form['academic_year_id'];
        $semesterId = $form['semester_id'];
        $clearanceType = $form['clearance_type'];
        
    } else {
        // If a specific school_term was passed from the frontend (format: "YEAR|semester_id")
        // try to resolve the requested academic_year_id and semester_id and return the
        // clearance form for that exact period. Otherwise, fall back to the most recent form.
        $schoolTermParam = $_GET['school_term'] ?? null;

        if ($schoolTermParam) {
            // Expected format: "<academic_year_string>|<semester_id>" (e.g. "2026-2027|99")
            $parts = explode('|', $schoolTermParam);
            $academicYearStr = isset($parts[0]) ? trim($parts[0]) : '';
            $requestedSemesterId = isset($parts[1]) ? (int)trim($parts[1]) : null;

            // DEBUG: Log school term parsing
            error_log("USER_STATUS_DEBUG: school_term parameter = " . $schoolTermParam);
            error_log("USER_STATUS_DEBUG: parsed academic_year = '" . $academicYearStr . "', semester_id = " . ($requestedSemesterId ?? 'NULL'));

            if ($academicYearStr !== '' && $requestedSemesterId) {
                // Resolve academic_year_id from the academic_years table using the year string
                $ayStmt = $connection->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? LIMIT 1");
                $ayStmt->execute([$academicYearStr]);
                $resolvedAcademicYearId = $ayStmt->fetchColumn();

                // If not found, try a more permissive lookup (LIKE) to tolerate small formatting differences
                if (!$resolvedAcademicYearId) {
                    $ayStmt2 = $connection->prepare("SELECT academic_year_id FROM academic_years WHERE year LIKE ? LIMIT 1");
                    $ayStmt2->execute(["%" . $academicYearStr . "%"]);
                    $resolvedAcademicYearId = $ayStmt2->fetchColumn();
                }

                // DEBUG: Log resolution result
                error_log("USER_STATUS_DEBUG: resolved academic_year_id = " . ($resolvedAcademicYearId ?: 'NULL'));

                if ($resolvedAcademicYearId) {
                    $sql = "
                        SELECT 
                            cf.clearance_form_id,
                            cf.clearance_form_progress as form_status,
                            cf.applied_at,
                            cf.completed_at,
                            cf.clearance_type,
                            ay.year as academic_year,
                            s.semester_name,
                            s.semester_id,
                            ay.academic_year_id
                        FROM clearance_forms cf
                        INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                        INNER JOIN semesters s ON cf.semester_id = s.semester_id
                        WHERE cf.user_id = ?
                          AND cf.academic_year_id = ?
                          AND cf.semester_id = ?
                        LIMIT 1
                    ";

                    $stmt = $connection->prepare($sql);
                    $stmt->execute([$userId, $resolvedAcademicYearId, $requestedSemesterId]);
                    $form = $stmt->fetch(PDO::FETCH_ASSOC);

                    // DEBUG: Log form lookup result
                    error_log("USER_STATUS_DEBUG: form lookup for user_id=$userId, academic_year_id=$resolvedAcademicYearId, semester_id=$requestedSemesterId: " . ($form ? 'FOUND' : 'NOT FOUND'));

                    if (!$form) {
                        // No form found for that specific term — return a successful empty response
                        ob_clean();
                        echo json_encode([
                            'success' => true,
                            'applied' => false,
                            'message' => 'No clearance forms found for user for the selected school term',
                            'signatories' => [],
                            'overall_status' => 'No Form'
                        ]);
                        exit;
                    }

                    $academicYearId = $form['academic_year_id'];
                    $semesterId = $form['semester_id'];
                    $clearanceType = $form['clearance_type'];
                } else {
                    // Could not resolve academic year string passed — fall back to most recent form
                    error_log("USER_STATUS_DEBUG: Could not resolve academic year, falling back to most recent form");
                    $form = null;
                }
            } else {
                error_log("USER_STATUS_DEBUG: Invalid school_term format - missing academic_year or semester_id");
            }
        }

        // If $form is not set by the school_term path, fetch the most recent clearance form
        if (!$form) {
            // Get the most recent clearance form for the user
            $sql = "
                SELECT 
                    cf.clearance_form_id,
                    cf.clearance_form_progress as form_status,
                    cf.applied_at,
                    cf.completed_at,
                    cf.clearance_type,
                    ay.year as academic_year,
                    s.semester_name,
                    s.semester_id,
                    ay.academic_year_id
                FROM clearance_forms cf
                INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                INNER JOIN semesters s ON cf.semester_id = s.semester_id
                WHERE cf.user_id = ?
                ORDER BY cf.created_at DESC
                LIMIT 1
            ";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userId]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$form) {
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'applied' => false,
                    'message' => 'No clearance forms found for user',
                    'signatories' => [],
                    'overall_status' => 'No Form'
                ]);
                exit;
            }
            
            $academicYearId = $form['academic_year_id'];
            $semesterId = $form['semester_id'];
            $clearanceType = $form['clearance_type'];
        }
    }
    
    // Get period status for this clearance form
    $periodStatusSql = "
        SELECT p.status as period_status
        FROM clearance_periods p
        WHERE p.sector = ? 
        AND p.semester_id = ? 
        AND p.academic_year_id = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ";
    $stmt = $connection->prepare($periodStatusSql);
    $stmt->execute([$clearanceType, $semesterId, $academicYearId]);
    $periodStatusRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the status to match frontend expectations (e.g., 'Not Started' -> 'not_started')
    $rawStatus = $periodStatusRow ? $periodStatusRow['period_status'] : 'Unknown';
    $periodStatusValue = strtolower(str_replace(' ', '_', $rawStatus));
    
    // Get sector clearance settings for required first/last logic
    $settingsSql = "
        SELECT * 
        FROM sector_clearance_settings 
        WHERE clearance_type = ? 
        LIMIT 1
    ";
    $settingsStmt = $connection->prepare($settingsSql);
    $settingsStmt->execute([$clearanceType]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Get signatories for this clearance form
    $signatoriesSql = "
        SELECT 
            cs.signatory_id,
            cs.action,
            cs.designation_id,
            cs.actual_user_id,
            cs.remarks,
            cs.additional_remarks,
            cs.date_signed,
            cs.created_at,
            cs.updated_at,
            d.designation_name,
            u_signatory.first_name as signatory_first_name,
            u_signatory.last_name as signatory_last_name,
            u_signatory.username as signatory_username
        FROM clearance_signatories cs
        INNER JOIN designations d ON cs.designation_id = d.designation_id
        LEFT JOIN users u_signatory ON cs.actual_user_id = u_signatory.user_id
        WHERE cs.clearance_form_id = ?
        ORDER BY cs.created_at ASC
    ";
    
    $stmt = $connection->prepare($signatoriesSql);
    $stmt->execute([$form['clearance_form_id']]);
    $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process signatories data
    $processedSignatories = [];
    $hasUnapplied = false;
    $hasPending = false;
    $hasRejected = false;
    $hasApproved = false;
    $allApproved = true;
    
    foreach ($signatories as $signatory) {
        $action = $signatory['action'] ?: 'Unapplied';
        
        if ($action === 'Unapplied') {
            $hasUnapplied = true;
            $allApproved = false;
        } elseif ($action === 'Pending') {
            $hasPending = true;
            $allApproved = false;
        } elseif ($action === 'Rejected') {
            $hasRejected = true;
            $allApproved = false;
        } elseif ($action === 'Approved') {
            $hasApproved = true;
        }
        
        $processedSignatories[] = [
            'signatory_id' => $signatory['signatory_id'],
            'designation_id' => $signatory['designation_id'],
            'designation_name' => $signatory['designation_name'],
            'actual_user_id' => $signatory['actual_user_id'] ?? null,
            'action' => $action,
            'remarks' => $signatory['remarks'],
            'additional_remarks' => $signatory['additional_remarks'],
            'date_signed' => $signatory['date_signed'],
            'created_at' => $signatory['created_at'],
            'updated_at' => $signatory['updated_at'],
            'signatory_name' => trim(($signatory['signatory_first_name'] ?? '') . ' ' . ($signatory['signatory_last_name'] ?? '')),
            'signatory_username' => $signatory['signatory_username']
        ];
    }
    
    // Determine clearance form progress (new 3-status system)
    $clearanceFormProgress = 'unapplied';
    
    // Check if user has applied to any signatory
    $hasApplied = $hasPending || $hasApproved || $hasRejected;
    
    if ($allApproved && !$hasUnapplied && count($signatories) > 0) {
        // All signatories approved
        $clearanceFormProgress = 'complete';
    } elseif ($hasApplied) {
        // User has applied to one or more signatories
        $clearanceFormProgress = 'in-progress';
    } else {
        // User hasn't applied to any signatory yet
        $clearanceFormProgress = 'unapplied';
    }
    
    // Determine overall status (for backward compatibility)
    $overallStatus = 'Unapplied';
    if ($clearanceFormProgress === 'complete') {
        $overallStatus = 'Complete';
    } elseif ($clearanceFormProgress === 'in-progress') {
        $overallStatus = 'In Progress';
    } else {
        $overallStatus = 'Unapplied';
    }
    
    // Get logged-in user's ID for signatory highlighting
    $loggedInUserId = $auth->getUserId();
    
    // Data inconsistency detection: Check if there's a mismatch between form progress and signatory actions
    // This is useful when viewing from a signatory perspective
    $dataInconsistency = null;
    if ($loggedInUserId && in_array($userRole, ['School Administrator', 'Regular Staff', 'Program Head'])) {
        // Find the logged-in user's signatory entry
        $loggedInUserSignatory = null;
        foreach ($processedSignatories as $sig) {
            if ($sig['actual_user_id'] == $loggedInUserId) {
                $loggedInUserSignatory = $sig;
                break;
            }
        }
        
        // If logged-in user is a signatory, check for inconsistencies
        if ($loggedInUserSignatory) {
            $signatoryAction = strtolower($loggedInUserSignatory['action'] ?? 'unapplied');
            $formProgress = strtolower($clearanceFormProgress);
            
            // Inconsistency: Form shows "unapplied" but signatory has "approved" or "rejected"
            if ($formProgress === 'unapplied' && in_array($signatoryAction, ['approved', 'rejected'])) {
                $dataInconsistency = [
                    'type' => 'form_unapplied_but_signatory_acted',
                    'message' => 'Form shows "Unapplied" but your status is "' . ucfirst($signatoryAction) . '". This may indicate a data sync issue.',
                    'form_progress' => $formProgress,
                    'signatory_action' => $signatoryAction
                ];
            }
            // Inconsistency: Form shows "complete" but signatory hasn't approved
            elseif ($formProgress === 'complete' && $signatoryAction !== 'approved') {
                $dataInconsistency = [
                    'type' => 'form_complete_but_signatory_not_approved',
                    'message' => 'Form shows "Completed" but your status is "' . ucfirst($signatoryAction) . '". This may indicate a data sync issue.',
                    'form_progress' => $formProgress,
                    'signatory_action' => $signatoryAction
                ];
            }
        }
    }
    
    // Clear any output buffer before sending JSON
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'applied' => $form['form_status'] !== 'Unapplied',
        'form_status' => $form['form_status'],
        'overall_status' => $overallStatus,
        'clearance_form_progress' => $clearanceFormProgress,
        'clearance_form_id' => $form['clearance_form_id'],
        'academic_year' => $form['academic_year'],
        'semester_name' => $form['semester_name'],
        'clearance_type' => $clearanceType,
        'applied_at' => $form['applied_at'],
        'completed_at' => $form['completed_at'],
        'period_status' => $periodStatusValue,
        'settings' => $settings,
        'can_apply' => $periodStatusValue !== 'Closed',
        'signatories' => $processedSignatories,
        'total_signatories' => count($processedSignatories),
        'total' => count($processedSignatories), // For backward compatibility
        'approved' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Approved')), // For backward compatibility
        'approved_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Approved')),
        'pending_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Pending')),
        'rejected_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Rejected')),
        'unapplied_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Unapplied')),
        'logged_in_user_id' => $loggedInUserId, // For signatory highlighting
        'data_inconsistency' => $dataInconsistency // Data inconsistency warning if applicable
    ]);
    
} catch (Exception $e) {
    error_log("User Status API Error: " . $e->getMessage());
    error_log("User Status API Stack Trace: " . $e->getTraceAsString());
    
    // Clear any output buffer before sending JSON
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("User Status API Fatal Error: " . $e->getMessage());
    error_log("User Status API Stack Trace: " . $e->getTraceAsString());
    
    // Clear any output buffer before sending JSON
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error occurred. Please check server logs.'
    ]);
} finally {
    // End output buffering and send output
    ob_end_flush();
}
?>
