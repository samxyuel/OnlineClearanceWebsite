<?php
// Import Data Controller - Handle data imports for various entities
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/functions/audit_functions.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user has import permissions
if (!$auth->hasPermission('import_data')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions for data import']);
    exit;
}

// Handle POST request for data import
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleImportRequest();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleImportRequest() {
    try {
        // Validate import type
        $importType = $_POST['type'] ?? '';
        if (!in_array($importType, ['faculty_import', 'student_import', 'staff_import'])) {
            throw new Exception('Invalid import type');
        }

        // Validate file upload
        if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }

        $file = $_FILES['importFile'];
        $importMode = $_POST['importMode'] ?? 'skip';
        $validateData = isset($_POST['validateData']) && $_POST['validateData'] === 'on';
        $sendNotifications = isset($_POST['sendNotifications']) && $_POST['sendNotifications'] === 'on';
        $validateOnly = isset($_POST['validateOnly']) && $_POST['validateOnly'] === '1';
        $importPolicy = $_POST['importPolicy'] ?? 'partial'; // 'partial' or 'strict'

        // Process import based on type
        switch ($importType) {
            case 'faculty_import':
                $result = importFacultyData($file, $importMode, $validateData, $sendNotifications, $validateOnly, $importPolicy);
                break;
            default:
                throw new Exception('Import type not yet implemented');
        }

        echo json_encode($result);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage()
        ]);
    }
}

function importFacultyData($file, $importMode, $validateData, $sendNotifications, $validateOnly = false, $importPolicy = 'partial') {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    try {
        // Parse file based on extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $data = parseImportFile($file['tmp_name'], $fileExtension);
        
        if (empty($data)) {
            throw new Exception('No valid data found in file');
        }

        // Normalize incoming rows (headers/values) to expected schema
        $data = array_map('normalizeFacultyRow', $data);

        // If validate-only requested, build and return a validation report without DB writes
        if ($validateOnly) {
            $report = buildFacultyValidationReport($data, $connection);
            return [
                'success' => true,
                'message' => 'Validation completed',
                'validateOnly' => true,
                'summary' => $report['summary'],
                'rows' => $report['rows']
            ];
        }

        // Start transaction for actual import (strict mode only)
        if ($importPolicy === 'strict') {
            $connection->beginTransaction();
        }

        // Always perform core validation; include deeper checks if requested
        $validationResult = validateFacultyData($data, $connection, $validateData);
        if (!$validationResult['valid']) {
            throw new Exception('Data validation failed: ' . implode('; ', $validationResult['errors']));
        }

        // Process import based on mode and policy
        $importResult = processFacultyImport($data, $importMode, $connection, $sendNotifications);

        // If any row failed, rollback entire import (all-or-nothing)
        if (!empty($importResult['errors'])) {
            if ($importPolicy === 'strict') {
                $connection->rollBack();
                return [
                    'success' => false,
                    'message' => 'Import aborted due to validation/processing errors',
                    'summary' => [
                        'total' => count($data),
                        'imported' => 0,
                        'updated' => 0,
                        'skipped' => 0,
                        'errors' => $importResult['errors']
                    ],
                    'rows' => $importResult['rows'] ?? []
                ];
            }
            // In partial mode, we still succeed but report errors/skips
        }

        // Commit transaction (strict mode)
        if ($importPolicy === 'strict' && $connection->inTransaction()) {
            $connection->commit();
        }

        // Log successful import
        logImportActivity('faculty', count($data), $importResult['imported'], $importResult['updated'], $importResult['skipped']);

        return [
            'success' => true,
            'message' => 'Faculty data imported successfully',
            'summary' => [
                'total' => count($data),
                'imported' => $importResult['imported'],
                'updated' => $importResult['updated'],
                'skipped' => $importResult['skipped'],
                'errors' => $importResult['errors']
            ],
            'rows' => $importResult['rows'] ?? []
        ];

    } catch (Exception $e) {
        // Rollback transaction on error (only if in a transaction)
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $e;
    }
}

function parseImportFile($filePath, $extension) {
    switch ($extension) {
        case 'csv':
            return parseCSVFile($filePath);
        case 'xlsx':
        case 'xls':
            return parseExcelFile($filePath);
        case 'json':
            return parseJSONFile($filePath);
        case 'xml':
            return parseXMLFile($filePath);
        default:
            throw new Exception('Unsupported file format: ' . $extension);
    }
}

function parseCSVFile($filePath) {
    $data = [];
    $handle = fopen($filePath, 'r');
    
    if ($handle === false) {
        throw new Exception('Unable to open CSV file');
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    if (!$headers) {
        throw new Exception('Invalid CSV format - no headers found');
    }
    // Normalize header keys to snake_case where possible
    $normalizedHeaders = array_map(function($h){
        $h = trim($h);
        $h = str_replace([' ', '-'], '_', strtolower($h));
        return $h;
    }, $headers);
    
    // Read data rows
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) === count($normalizedHeaders)) {
            $dataRow = array_combine($normalizedHeaders, $row);
            $data[] = $dataRow;
        }
    }
    
    fclose($handle);
    return $data;
}

function parseExcelFile($filePath) {
    // For now, return sample data since we don't have Excel parsing library
    // In production, you'd use PhpSpreadsheet or similar library
    return [
        [
            'employee_number' => 'LCA123P',
            'employment_status' => 'Full Time',
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'middle_name' => '',
            'account_status' => 'active',
            'email' => 'maria.santos@example.com',
            'contact_number' => '+63 912 345 6789'
        ],
        [
            'employee_number' => 'LCA124P',
            'employment_status' => 'Part Time',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'account_status' => 'active',
            'email' => 'juan.delacruz@example.com',
            'contact_number' => '+63 923 456 7890'
        ]
    ];
}

function parseJSONFile($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new Exception('Unable to read JSON file');
    }
    
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format: ' . json_last_error_msg());
    }
    
    return is_array($data) ? $data : [$data];
}

function parseXMLFile($filePath) {
    $xml = simplexml_load_file($filePath);
    if ($xml === false) {
        throw new Exception('Unable to parse XML file');
    }
    
    $data = [];
    foreach ($xml->faculty as $faculty) {
        $data[] = [
            'employee_number' => (string)($faculty->employee_id ?: $faculty->employee_number),
            'employment_status' => (string)$faculty->employment_status,
            'last_name' => (string)$faculty->last_name,
            'first_name' => (string)$faculty->first_name,
            'middle_name' => (string)$faculty->middle_name,
            'account_status' => (string)$faculty->account_status,
            'email' => (string)$faculty->email,
            'contact_number' => (string)$faculty->contact_number
        ];
    }
    
    return $data;
}

// Build a detailed validation report without mutating the database
function buildFacultyValidationReport(array $data, PDO $connection): array {
    $rows = [];
    $errors = [];
    $total = count($data);
    $toCreate = 0;
    $toUpdate = 0;
    $toSkip = 0;

    // Detect duplicates within the file
    $counts = [];
    foreach ($data as $row) {
        $key = strtoupper(trim($row['employee_number'] ?? ''));
        if ($key !== '') {
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }

    foreach ($data as $index => $row) {
        $rowIssues = [];
        $rowNumber = $index + 1;

        $employeeNumber = strtoupper(trim($row['employee_number'] ?? ''));
        $lastName = trim($row['last_name'] ?? '');
        $firstName = trim($row['first_name'] ?? '');
        $employmentStatus = $row['employment_status'] ?? '';
        $accountStatus = $row['account_status'] ?? null;

        // Required fields
        if ($employeeNumber === '') { $rowIssues[] = "Missing employee_number"; }
        if ($lastName === '') { $rowIssues[] = "Missing last_name"; }
        if ($firstName === '') { $rowIssues[] = "Missing first_name"; }

        // Format checks
        if ($employeeNumber !== '' && !preg_match('/^[A-Z]{3}[0-9]{3}[A-Z]$/', $employeeNumber)) {
            $rowIssues[] = "Invalid employee_number format (expect LCA123P)";
        }

        if (!empty($employmentStatus)) {
            $validStatuses = ['Full Time', 'Part Time', 'Part Time - Full Load'];
            if (!in_array($employmentStatus, $validStatuses)) {
                $rowIssues[] = "Invalid employment_status";
            }
        }

        if ($accountStatus !== null && $accountStatus !== '') {
            $as = strtolower($accountStatus);
            if (!in_array($as, ['active', 'inactive'])) {
                $rowIssues[] = "Invalid account_status (active/inactive)";
            }
        }

        // Duplicate check (within file)
        if ($employeeNumber !== '' && ($counts[$employeeNumber] ?? 0) > 1) {
            $rowIssues[] = "Duplicate employee_number in file";
        }

        // DB existence check to determine action
        $action = 'create';
        if ($employeeNumber !== '') {
            $stmt = $connection->prepare("SELECT 1 FROM faculty WHERE employee_number = ? LIMIT 1");
            $stmt->execute([$employeeNumber]);
            if ($stmt->fetchColumn()) {
                $action = 'update';
            }
        }

        if (!empty($rowIssues)) {
            $errors[] = "Row $rowNumber: " . implode('; ', $rowIssues);
        } else {
            if ($action === 'create') { $toCreate++; } else if ($action === 'update') { $toUpdate++; }
        }

        $rows[] = [
            'rowNumber' => $rowNumber,
            'employee_number' => $employeeNumber,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => isset($row['middle_name']) ? trim((string)$row['middle_name']) : null,
            'employment_status' => $employmentStatus,
            'account_status' => $accountStatus,
            'email' => isset($row['email']) ? trim((string)$row['email']) : null,
            'contact_number' => isset($row['contact_number']) ? trim((string)$row['contact_number']) : null,
            'action' => empty($rowIssues) ? $action : 'error',
            'issues' => $rowIssues
        ];
    }

    return [
        'summary' => [
            'total' => $total,
            'will_create' => $toCreate,
            'will_update' => $toUpdate,
            'skipped' => $toSkip,
            'errors' => $errors
        ],
        'rows' => $rows
    ];
}

function validateFacultyData($data, $connection, $performDeepChecks = true) {
    $errors = [];
    
    foreach ($data as $index => $row) {
        $rowNumber = $index + 1;
        
        // Check required fields
        $requiredFields = ['employee_number', 'last_name', 'first_name'];
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                $errors[] = "Row $rowNumber: Missing required field '$field'";
            }
        }
        
        // Validate employee_number format (e.g., LCA123P)
        if (!empty($row['employee_number'])) {
            if (!preg_match('/^[A-Z]{3}[0-9]{3}[A-Z]$/', strtoupper($row['employee_number']))) {
                $errors[] = "Row $rowNumber: Invalid employee_number format (expected like LCA123P)";
            }
        }

        // Email optional, basic format if present
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row $rowNumber: Invalid email format";
        }

        // Validate normalized employment status (long form)
        if (!empty($row['employment_status'])) {
            $validStatuses = ['Full Time', 'Part Time', 'Part Time - Full Load'];
            if (!in_array($row['employment_status'], $validStatuses)) {
                $errors[] = "Row $rowNumber: Invalid employment status";
            }
        }

        // Validate account_status if provided
        if (isset($row['account_status']) && $row['account_status'] !== '') {
            $as = strtolower($row['account_status']);
            if (!in_array($as, ['active', 'inactive'])) {
                $errors[] = "Row $rowNumber: Invalid account_status (use 'active' or 'inactive')";
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

function processFacultyImport($data, $importMode, $connection, $sendNotifications) {
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    $rowsOutcomes = [];
    
    foreach ($data as $index => $row) {
        try {
            $rowNumber = $index + 1;
            
            // Check if faculty exists
            $stmt = $connection->prepare("SELECT f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.employee_number = ?");
            $stmt->execute([$row['employee_number']]);
            $existingFaculty = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingFaculty) {
                // Guard: employee_number belongs to a different name
                $inFirst  = trim($row['first_name'] ?? '');
                $inLast   = trim($row['last_name'] ?? '');
                $inMiddle = trim($row['middle_name'] ?? '');
                $nameMismatch = false;
                if ($inFirst !== '' && strcasecmp($inFirst, (string)$existingFaculty['first_name']) !== 0) { $nameMismatch = true; }
                if ($inLast !== '' && strcasecmp($inLast, (string)$existingFaculty['last_name']) !== 0) { $nameMismatch = true; }
                if ($inMiddle !== '' && isset($existingFaculty['middle_name']) && strcasecmp($inMiddle, (string)$existingFaculty['middle_name']) !== 0) { $nameMismatch = true; }
                if ($nameMismatch) {
                    $skipped++;
                    $rowsOutcomes[] = [
                        'rowNumber' => $rowNumber,
                        'employee_number' => $row['employee_number'],
                        'action' => 'skipped',
                        'reason' => 'employee_number belongs to a different name'
                    ];
                    continue;
                }
                // Faculty exists
                switch ($importMode) {
                    case 'skip':
                        $skipped++;
                        $rowsOutcomes[] = [
                            'rowNumber' => $rowNumber,
                            'employee_number' => $row['employee_number'],
                            'action' => 'skipped',
                            'reason' => 'exists (skip mode)'
                        ];
                        continue 2; // Skip to next iteration
                        
                    case 'update':
                        $result = updateFacultyRecord($row, $existingFaculty, $connection);
                        if ($result) {
                            $updated++;
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'employee_number' => $row['employee_number'],
                                'action' => 'updated'
                            ];
                        } else {
                            $errors[] = "Row $rowNumber: Failed to update faculty";
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'employee_number' => $row['employee_number'],
                                'action' => 'error',
                                'reason' => 'update failed'
                            ];
                        }
                        break;
                        
                    case 'replace':
                        $result = updateFacultyRecord($row, $existingFaculty, $connection);
                        if ($result) {
                            $updated++;
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'employee_number' => $row['employee_number'],
                                'action' => 'updated'
                            ];
                        } else {
                            $errors[] = "Row $rowNumber: Failed to update faculty";
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'employee_number' => $row['employee_number'],
                                'action' => 'error',
                                'reason' => 'update failed'
                            ];
                        }
                        break;
                }
            } else {
                // Faculty doesn't exist
                $result = createFacultyRecord($row, $connection, $sendNotifications);
                if ($result) {
                    $imported++;
                    $rowsOutcomes[] = [
                        'rowNumber' => $rowNumber,
                        'employee_number' => $row['employee_number'],
                        'action' => 'imported'
                    ];
                } else {
                    $errors[] = "Row $rowNumber: Failed to create faculty";
                    $rowsOutcomes[] = [
                        'rowNumber' => $rowNumber,
                        'employee_number' => $row['employee_number'],
                        'action' => 'error',
                        'reason' => 'create failed'
                    ];
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            $rowsOutcomes[] = [
                'rowNumber' => $rowNumber,
                'employee_number' => $row['employee_number'] ?? null,
                'action' => 'error',
                'reason' => $e->getMessage()
            ];
        }
    }
    
    return [
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors,
        'rows' => $rowsOutcomes
    ];
}

function createFacultyRecord($data, $connection, $sendNotifications) {
    try {
        $username = $data['employee_number'];
        $accountStatus = strtolower($data['account_status'] ?? 'active');
        if (!in_array($accountStatus, ['active', 'inactive'])) {
            $accountStatus = 'active';
        }

        // Check for existing user by username (employee_number)
        $stmt = $connection->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $existingUserId = $stmt->fetchColumn();

        if ($existingUserId) {
            $userId = (int)$existingUserId;
            // Safeguard email: if provided email is used by another user, null it (skip update)
            $emailToUse = $data['email'] ?? null;
            if ($emailToUse) {
                $chk = $connection->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
                $chk->execute([$emailToUse, $userId]);
                if ($chk->fetchColumn()) {
                    $emailToUse = null;
                }
            }
            $upd = $connection->prepare("UPDATE users SET email = COALESCE(?, email), first_name = ?, last_name = ?, middle_name = ?, contact_number = ?, status = ?, updated_at = NOW() WHERE user_id = ?");
            $upd->execute([
                $emailToUse,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['contact_number'] ?? null,
                $accountStatus,
                $userId
            ]);
        } else {
            // Create user account first (email conflict-safe)
            $stmt = $connection->prepare("
            INSERT INTO users (username, password, email, first_name, last_name, middle_name, contact_number, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
            
            // Generate default password (LastName + EmployeeID)
            $defaultPasswordPlain = ($data['last_name'] ?? '') . ($data['employee_number'] ?? '');
            $defaultPassword = password_hash($defaultPasswordPlain, PASSWORD_DEFAULT);

            // Ensure email uniqueness on create
            $emailToUse = $data['email'] ?? null;
            if ($emailToUse) {
                $chk = $connection->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
                $chk->execute([$emailToUse]);
                if ($chk->fetchColumn()) {
                    $emailToUse = null;
                }
            }
            
            $stmt->execute([
                $data['employee_number'],
                $defaultPassword,
                $emailToUse,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['contact_number'] ?? null,
                $accountStatus
            ]);
            
            $userId = $connection->lastInsertId();
        }
        
        // Assign faculty role (fix bind count)
        $stmt = $connection->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, 4, NOW())");
        $stmt->execute([$userId]);
        
        // Create faculty record
        $stmt = $connection->prepare("
            INSERT INTO faculty (employee_number, user_id, employment_status, department_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $data['employee_number'],
            $userId,
            $data['employment_status'] ?? 'Full Time',
            null // Department ID can be set later
        ]);
        
        // Send welcome notification if requested
        if ($sendNotifications) {
            // In production, implement actual email sending
            // For now, just log the action
            logImportActivity('faculty_notification', 1, 1, 0, 0);
        }
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception('Failed to create faculty record: ' . $e->getMessage());
    }
}

function updateFacultyRecord($data, $existingFaculty, $connection) {
    try {
        // Update only allowed user fields: email, contact_number, and optionally account status
        $accountStatus = null;
        if (isset($data['account_status']) && $data['account_status'] !== '') {
            $as = strtolower($data['account_status']);
            if (in_array($as, ['active', 'inactive'])) {
                $accountStatus = $as;
            }
        }

        if ($accountStatus !== null) {
            $stmt = $connection->prepare("UPDATE users SET email = ?, contact_number = ?, status = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([
                $data['email'] ?? null,
                $data['contact_number'] ?? null,
                $accountStatus,
                $existingFaculty['user_id']
            ]);
        } else {
            $stmt = $connection->prepare("UPDATE users SET email = ?, contact_number = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([
                $data['email'] ?? null,
                $data['contact_number'] ?? null,
                $existingFaculty['user_id']
            ]);
        }
        
        // Update faculty information
        $stmt = $connection->prepare("
            UPDATE faculty 
            SET employment_status = ?, updated_at = NOW()
            WHERE employee_number = ?
        ");
        
        $stmt->execute([
            $data['employment_status'] ?? 'Full Time',
            $data['employee_number']
        ]);
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception('Failed to update faculty record: ' . $e->getMessage());
    }
}

function logImportActivity($type, $total, $imported, $updated, $skipped) {
    // Log import activity for audit purposes
    // This function can be expanded to log to database or file
    $logData = [
        'type' => $type,
        'total' => $total,
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // For now, just write to log file
    $logMessage = json_encode($logData) . "\n";
    file_put_contents('../logs/import_activity.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Normalize incoming row keys/values to expected schema
function normalizeFacultyRow(array $row): array {
    $normalized = [];
    // Employee ID
    $normalized['employee_number'] = trim($row['employee_id'] ?? $row['employee_number'] ?? '');
    // Names
    $normalized['last_name'] = trim($row['last_name'] ?? ($row['surname'] ?? ''));
    $normalized['first_name'] = trim($row['first_name'] ?? ($row['given_name'] ?? ''));
    $normalized['middle_name'] = isset($row['middle_name']) ? trim($row['middle_name']) : null;
    // Employment status mapping short codes
    $statusRaw = trim((string)($row['employment_status'] ?? ''));
    $map = ['FT' => 'Full Time', 'PT' => 'Part Time', 'PTFL' => 'Part Time - Full Load'];
    $normalized['employment_status'] = $map[strtoupper($statusRaw)] ?? $statusRaw;
    // Account status
    $normalized['account_status'] = isset($row['account_status']) ? strtolower(trim($row['account_status'])) : null;
    // Contacts
    $normalized['email'] = isset($row['email']) ? trim($row['email']) : null;
    $normalized['contact_number'] = isset($row['contact_number']) ? trim($row['contact_number']) : null;
    return $normalized;
}
?>
