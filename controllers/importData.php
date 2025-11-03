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

// Validate user role - Only Admin and Program Head can import
$userRoleName = $auth->getRoleName();
$allowedRoles = ['Admin', 'Program Head'];
if (!in_array($userRoleName, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only Administrators and Program Heads can perform bulk imports']);
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
    global $auth;
    
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

        // Get page context (determines sector and validation rules)
        $pageType = $_POST['pageType'] ?? ''; // 'college', 'shs', 'faculty'
        $selectedDepartmentId = $_POST['selectedDepartment'] ?? null;
        $selectedProgramId = $_POST['selectedProgram'] ?? null; // For students
        
        // Validate required context parameters based on import type
        if ($importType === 'student_import') {
            if (!in_array($pageType, ['college', 'shs'])) {
                throw new Exception('Invalid page type for student import. Must be "college" or "shs"');
            }
            if (empty($selectedDepartmentId)) {
                throw new Exception('Department must be selected before importing students');
            }
            if (empty($selectedProgramId)) {
                throw new Exception('Program/Course must be selected before importing students');
            }
        } elseif ($importType === 'faculty_import') {
            if ($pageType !== 'faculty') {
                throw new Exception('Invalid page type for faculty import. Must be "faculty"');
            }
            if (empty($selectedDepartmentId)) {
                throw new Exception('Department must be selected before importing faculty');
            }
        }

        $file = $_FILES['importFile'];
        $importMode = $_POST['importMode'] ?? 'skip';
        $validateData = isset($_POST['validateData']) && $_POST['validateData'] === 'on';
        $sendNotifications = false; // Email notifications disabled
        $validateOnly = isset($_POST['validateOnly']) && $_POST['validateOnly'] === '1';
        $importPolicy = $_POST['importPolicy'] ?? 'partial'; // 'partial' or 'strict'

        // Process import based on type
        switch ($importType) {
            case 'student_import':
                $result = importStudentData(
                    $file, 
                    $pageType, 
                    $selectedDepartmentId, 
                    $selectedProgramId, 
                    $importMode, 
                    $validateData, 
                    $sendNotifications, 
                    $validateOnly, 
                    $importPolicy,
                    $auth
                );
                break;
            case 'faculty_import':
                $result = importFacultyData(
                    $file, 
                    $selectedDepartmentId,
                    $importMode, 
                    $validateData, 
                    $sendNotifications, 
                    $validateOnly, 
                    $importPolicy,
                    $auth
                );
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

/**
 * Get Program Head's assigned departments and sector
 */
function getProgramHeadAssignments($connection, $auth) {
    $userId = $auth->getUserId();
    
    // Get staff employee_number for this user
    $stmt = $connection->prepare("
        SELECT s.employee_number 
        FROM staff s 
        WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        return null; // Not a Program Head
    }
    
    // Get assigned departments from staff_department_assignments
    $stmt = $connection->prepare("
        SELECT sda.department_id, sda.sector_id, d.department_name, s.sector_name
        FROM staff_department_assignments sda
        JOIN departments d ON sda.department_id = d.department_id
        JOIN sectors s ON sda.sector_id = s.sector_id
        WHERE sda.staff_id = ? AND sda.is_active = 1
    ");
    $stmt->execute([$staff['employee_number']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $assignments;
}

/**
 * Validate that a department belongs to the expected sector and is accessible to the user
 */
function validateDepartmentAccess($connection, $departmentId, $expectedSectorName, $auth) {
    // Get department and its sector
    $stmt = $connection->prepare("
        SELECT d.department_id, d.department_name, s.sector_id, s.sector_name
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE d.department_id = ? AND d.is_active = 1
    ");
    $stmt->execute([$departmentId]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dept) {
        throw new Exception("Department ID $departmentId not found or inactive");
    }
    
    // Check sector matches
    if (strcasecmp($dept['sector_name'], $expectedSectorName) !== 0) {
        throw new Exception("Department '{$dept['department_name']}' belongs to '{$dept['sector_name']}' sector, but expected '{$expectedSectorName}'");
    }
    
    // If Program Head, check they have access to this department
    $userRoleName = $auth->getRoleName();
    if ($userRoleName === 'Program Head') {
        $assignments = getProgramHeadAssignments($connection, $auth);
        if (!$assignments) {
            throw new Exception('Program Head has no assigned departments');
        }
        
        $hasAccess = false;
        foreach ($assignments as $assignment) {
            if ($assignment['department_id'] == $departmentId) {
                $hasAccess = true;
                // Also verify sector matches
                if (strcasecmp($assignment['sector_name'], $expectedSectorName) !== 0) {
                    throw new Exception("Program Head's assigned department '{$assignment['department_name']}' belongs to '{$assignment['sector_name']}' sector, which doesn't match expected '{$expectedSectorName}'");
                }
                break;
            }
        }
        
        if (!$hasAccess) {
            throw new Exception("Program Head does not have access to department '{$dept['department_name']}'");
        }
    }
    
    return $dept;
}

/**
 * Validate that a program belongs to the expected department
 */
function validateProgramAccess($connection, $programId, $expectedDepartmentId) {
    $stmt = $connection->prepare("
        SELECT program_id, program_name, program_code, department_id
        FROM programs
        WHERE program_id = ? AND is_active = 1
    ");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        throw new Exception("Program ID $programId not found or inactive");
    }
    
    if ($program['department_id'] != $expectedDepartmentId) {
        throw new Exception("Program '{$program['program_name']}' does not belong to the selected department");
    }
    
    return $program;
}

function importFacultyData($file, $selectedDepartmentId, $importMode, $validateData, $sendNotifications, $validateOnly = false, $importPolicy = 'partial', $auth) {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    try {
        // Validate department access and get sector
        $expectedSectorName = 'Faculty';
        $department = validateDepartmentAccess($connection, $selectedDepartmentId, $expectedSectorName, $auth);
        
        // Parse file based on extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $data = parseImportFile($file['tmp_name'], $fileExtension);
        
        if (empty($data)) {
            throw new Exception('No valid data found in file');
        }

        // Normalize incoming rows (headers/values) to expected schema
        $data = array_map('normalizeFacultyRow', $data);
        
        // Validate each row's department matches selected department
        foreach ($data as $index => $row) {
            $rowNumber = $index + 1;
            // If row has department info, validate it matches
            if (isset($row['department_id']) && !empty($row['department_id'])) {
                if ($row['department_id'] != $selectedDepartmentId) {
                    throw new Exception("Row $rowNumber: Department mismatch. Row has department ID {$row['department_id']} but expected $selectedDepartmentId");
                }
            }
            // Add selected department to each row
            $data[$index]['department_id'] = $selectedDepartmentId;
        }

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
        $validationResult = validateFacultyData($data, $connection, $validateData, $selectedDepartmentId);
        if (!$validationResult['valid']) {
            throw new Exception('Data validation failed: ' . implode('; ', $validationResult['errors']));
        }

        // Process import based on mode and policy
        $importResult = processFacultyImport($data, $importMode, $connection, $sendNotifications, $selectedDepartmentId);

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

/**
 * Import student data with sector, department, and program validation
 */
function importStudentData($file, $pageType, $selectedDepartmentId, $selectedProgramId, $importMode, $validateData, $sendNotifications, $validateOnly = false, $importPolicy = 'partial', $auth) {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    try {
        // Determine expected sector from page type
        $expectedSectorName = ($pageType === 'college') ? 'College' : 'Senior High School';
        
        // Validate department access
        $department = validateDepartmentAccess($connection, $selectedDepartmentId, $expectedSectorName, $auth);
        
        // Validate program access
        $program = validateProgramAccess($connection, $selectedProgramId, $selectedDepartmentId);
        
        // Parse file based on extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $data = parseImportFile($file['tmp_name'], $fileExtension);
        
        if (empty($data)) {
            throw new Exception('No valid data found in file');
        }

        // Normalize incoming rows
        $data = array_map('normalizeStudentRow', $data);
        
        // Validate each row matches expected sector, department, and program
        $validationErrors = [];
        foreach ($data as $index => $row) {
            $rowNumber = $index + 1;
            $rowErrors = [];
            
            // Check if row has program info (by name or code) - we need to resolve it to program_id
            if (isset($row['program']) && !empty($row['program'])) {
                $programNameOrCode = trim($row['program']);
                
                // Try to find program by code or name
                $stmt = $connection->prepare("
                    SELECT program_id, program_name, program_code, department_id
                    FROM programs
                    WHERE (program_code = ? OR program_name = ?) AND is_active = 1
                    LIMIT 1
                ");
                $stmt->execute([$programNameOrCode, $programNameOrCode]);
                $rowProgram = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$rowProgram) {
                    $rowErrors[] = "Program '$programNameOrCode' not found";
                } elseif ($rowProgram['department_id'] != $selectedDepartmentId) {
                    $rowErrors[] = "Program '{$rowProgram['program_name']}' does not belong to the selected department";
                } elseif ($rowProgram['program_id'] != $selectedProgramId) {
                    $rowErrors[] = "Program '{$rowProgram['program_name']}' does not match the selected program";
                } else {
                    // Valid program, use it
                    $data[$index]['program_id'] = $rowProgram['program_id'];
                }
            }
            
            // If row has department info, validate it
            if (isset($row['department']) && !empty($row['department'])) {
                // Could be name or ID - try both
                $deptIdentifier = trim($row['department']);
                $stmt = $connection->prepare("
                    SELECT department_id, department_name, s.sector_name
                    FROM departments d
                    JOIN sectors s ON d.sector_id = s.sector_id
                    WHERE (d.department_id = ? OR d.department_name = ?) AND d.is_active = 1
                    LIMIT 1
                ");
                $stmt->execute([$deptIdentifier, $deptIdentifier]);
                $rowDept = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$rowDept) {
                    $rowErrors[] = "Department '$deptIdentifier' not found";
                } elseif ($rowDept['department_id'] != $selectedDepartmentId) {
                    $rowErrors[] = "Department '{$rowDept['department_name']}' does not match the selected department";
                } elseif (strcasecmp($rowDept['sector_name'], $expectedSectorName) !== 0) {
                    $rowErrors[] = "Department '{$rowDept['department_name']}' belongs to '{$rowDept['sector_name']}' sector, but expected '{$expectedSectorName}'";
                }
            }
            
            // Check sector if provided in row
            if (isset($row['sector']) && !empty($row['sector'])) {
                $rowSector = trim($row['sector']);
                // Normalize sector names
                $normalizedRowSector = '';
                if (stripos($rowSector, 'college') !== false) {
                    $normalizedRowSector = 'College';
                } elseif (stripos($rowSector, 'senior') !== false || stripos($rowSector, 'shs') !== false) {
                    $normalizedRowSector = 'Senior High School';
                }
                
                if ($normalizedRowSector && strcasecmp($normalizedRowSector, $expectedSectorName) !== 0) {
                    $rowErrors[] = "Row sector '$rowSector' does not match expected sector '$expectedSectorName'";
                }
            }
            
            if (!empty($rowErrors)) {
                $validationErrors[] = "Row $rowNumber: " . implode('; ', $rowErrors);
            } else {
                // Set the validated values
                $data[$index]['department_id'] = $selectedDepartmentId;
                $data[$index]['program_id'] = $selectedProgramId;
                $data[$index]['sector'] = $expectedSectorName;
            }
        }
        
        if (!empty($validationErrors)) {
            throw new Exception('Validation errors found: ' . implode(' | ', $validationErrors));
        }

        // If validate-only requested, build and return a validation report
        if ($validateOnly) {
            $report = buildStudentValidationReport($data, $connection, $selectedDepartmentId, $selectedProgramId);
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

        // Always perform core validation
        $validationResult = validateStudentData($data, $connection, $validateData, $selectedDepartmentId, $selectedProgramId);
        if (!$validationResult['valid']) {
            throw new Exception('Data validation failed: ' . implode('; ', $validationResult['errors']));
        }

        // Process import
        $importResult = processStudentImport($data, $pageType, $selectedDepartmentId, $selectedProgramId, $importMode, $connection, $sendNotifications);

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
        }

        // Commit transaction (strict mode)
        if ($importPolicy === 'strict' && $connection->inTransaction()) {
            $connection->commit();
        }

        // Log successful import
        logImportActivity('student', count($data), $importResult['imported'], $importResult['updated'], $importResult['skipped']);

        return [
            'success' => true,
            'message' => 'Student data imported successfully',
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
        // Rollback transaction on error
        if (isset($connection) && $connection->inTransaction()) {
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
        if ($employeeNumber !== '' && !preg_match('/^[A-Z]{3}[0-9]{4}[A-Z]$/', $employeeNumber)) {
            $rowIssues[] = "Invalid employee_number format (expect LCA1234P)";
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

function validateFacultyData($data, $connection, $performDeepChecks = true, $selectedDepartmentId = null) {
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
        
        // Validate employee_number format (e.g., LCA1234P)
        if (!empty($row['employee_number'])) {
            if (!preg_match('/^[A-Z]{3}[0-9]{4}[A-Z]$/', strtoupper($row['employee_number']))) {
                $errors[] = "Row $rowNumber: Invalid employee_number format (expected like LCA1234P)";
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

function processFacultyImport($data, $importMode, $connection, $sendNotifications, $selectedDepartmentId = null) {
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
                $result = createFacultyRecord($row, $connection, $sendNotifications, $selectedDepartmentId);
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

function createFacultyRecord($data, $connection, $sendNotifications, $selectedDepartmentId = null) {
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
        
        $departmentIdToUse = $selectedDepartmentId ?? $data['department_id'] ?? null;
        
        $stmt->execute([
            $data['employee_number'],
            $userId,
            $data['employment_status'] ?? 'Full Time',
            $departmentIdToUse
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

// Normalize incoming row keys/values to expected schema for students
function normalizeStudentRow(array $row): array {
    $normalized = [];
    // Student number
    $normalized['student_number'] = trim($row['student_number'] ?? $row['student_id'] ?? $row['student_number'] ?? '');
    // Names
    $normalized['last_name'] = trim($row['last_name'] ?? ($row['surname'] ?? ''));
    $normalized['first_name'] = trim($row['first_name'] ?? ($row['given_name'] ?? ''));
    $normalized['middle_name'] = isset($row['middle_name']) ? trim($row['middle_name']) : null;
    // Academic info
    $normalized['program'] = isset($row['program']) ? trim($row['program']) : (isset($row['program_code']) ? trim($row['program_code']) : null);
    $normalized['program_id'] = isset($row['program_id']) ? (int)$row['program_id'] : null;
    $normalized['department'] = isset($row['department']) ? trim($row['department']) : null;
    $normalized['department_id'] = isset($row['department_id']) ? (int)$row['department_id'] : null;
    $normalized['sector'] = isset($row['sector']) ? trim($row['sector']) : null;
    $normalized['year_level'] = isset($row['year_level']) ? trim($row['year_level']) : null;
    $normalized['section'] = isset($row['section']) ? trim($row['section']) : null;
    // Contacts
    $normalized['email'] = isset($row['email']) ? trim($row['email']) : null;
    $normalized['contact_number'] = isset($row['contact_number']) ? trim($row['contact_number']) : (isset($row['phone_number']) ? trim($row['phone_number']) : null);
    return $normalized;
}

function validateStudentData($data, $connection, $performDeepChecks = true, $selectedDepartmentId = null, $selectedProgramId = null) {
    $errors = [];
    
    foreach ($data as $index => $row) {
        $rowNumber = $index + 1;
        
        // Check required fields
        $requiredFields = ['student_number', 'last_name', 'first_name'];
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                $errors[] = "Row $rowNumber: Missing required field '$field'";
            }
        }
        
        // Validate student_number format (should be 11 digits)
        if (!empty($row['student_number'])) {
            if (!preg_match('/^\d{11}$/', $row['student_number'])) {
                $errors[] = "Row $rowNumber: Invalid student_number format (expected 11 digits)";
            }
        }
        
        // Email optional, basic format if present
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row $rowNumber: Invalid email format";
        }
        
        // Validate year_level if provided
        if (!empty($row['year_level'])) {
            $validYearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Grade 11', 'Grade 12'];
            if (!in_array($row['year_level'], $validYearLevels)) {
                $errors[] = "Row $rowNumber: Invalid year_level. Must be one of: " . implode(', ', $validYearLevels);
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

function buildStudentValidationReport(array $data, PDO $connection, $selectedDepartmentId, $selectedProgramId): array {
    $rows = [];
    $errors = [];
    $total = count($data);
    $toCreate = 0;
    $toUpdate = 0;
    $toSkip = 0;

    // Detect duplicates within the file
    $counts = [];
    foreach ($data as $row) {
        $key = trim($row['student_number'] ?? '');
        if ($key !== '') {
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }

    foreach ($data as $index => $row) {
        $rowIssues = [];
        $rowNumber = $index + 1;

        $studentNumber = trim($row['student_number'] ?? '');
        $lastName = trim($row['last_name'] ?? '');
        $firstName = trim($row['first_name'] ?? '');

        // Required fields
        if ($studentNumber === '') { $rowIssues[] = "Missing student_number"; }
        if ($lastName === '') { $rowIssues[] = "Missing last_name"; }
        if ($firstName === '') { $rowIssues[] = "Missing first_name"; }

        // Format checks
        if ($studentNumber !== '' && !preg_match('/^\d{11}$/', $studentNumber)) {
            $rowIssues[] = "Invalid student_number format (expected 11 digits)";
        }

        // Duplicate check (within file)
        if ($studentNumber !== '' && ($counts[$studentNumber] ?? 0) > 1) {
            $rowIssues[] = "Duplicate student_number in file";
        }

        // DB existence check to determine action
        $action = 'create';
        if ($studentNumber !== '') {
            $stmt = $connection->prepare("SELECT 1 FROM students WHERE student_id = ? LIMIT 1");
            $stmt->execute([$studentNumber]);
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
            'student_number' => $studentNumber,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => isset($row['middle_name']) ? trim((string)$row['middle_name']) : null,
            'program_id' => $selectedProgramId,
            'department_id' => $selectedDepartmentId,
            'year_level' => $row['year_level'] ?? null,
            'section' => $row['section'] ?? null,
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

function processStudentImport($data, $pageType, $selectedDepartmentId, $selectedProgramId, $importMode, $connection, $sendNotifications) {
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    $rowsOutcomes = [];
    
    foreach ($data as $index => $row) {
        try {
            $rowNumber = $index + 1;
            
            // Check if student exists
            $stmt = $connection->prepare("SELECT s.student_id, s.user_id, u.first_name, u.last_name, u.middle_name FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?");
            $stmt->execute([$row['student_number']]);
            $existingStudent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingStudent) {
                // Guard: student_number belongs to a different name
                $inFirst  = trim($row['first_name'] ?? '');
                $inLast   = trim($row['last_name'] ?? '');
                $inMiddle = trim($row['middle_name'] ?? '');
                $nameMismatch = false;
                if ($inFirst !== '' && strcasecmp($inFirst, (string)$existingStudent['first_name']) !== 0) { $nameMismatch = true; }
                if ($inLast !== '' && strcasecmp($inLast, (string)$existingStudent['last_name']) !== 0) { $nameMismatch = true; }
                if ($inMiddle !== '' && isset($existingStudent['middle_name']) && strcasecmp($inMiddle, (string)$existingStudent['middle_name']) !== 0) { $nameMismatch = true; }
                if ($nameMismatch) {
                    $skipped++;
                    $rowsOutcomes[] = [
                        'rowNumber' => $rowNumber,
                        'student_number' => $row['student_number'],
                        'action' => 'skipped',
                        'reason' => 'student_number belongs to a different name'
                    ];
                    continue;
                }
                
                // Student exists
                switch ($importMode) {
                    case 'skip':
                        $skipped++;
                        $rowsOutcomes[] = [
                            'rowNumber' => $rowNumber,
                            'student_number' => $row['student_number'],
                            'action' => 'skipped',
                            'reason' => 'exists (skip mode)'
                        ];
                        continue 2; // Skip to next iteration
                        
                    case 'update':
                        $result = updateStudentRecord($row, $existingStudent, $connection, $selectedDepartmentId, $selectedProgramId);
                        if ($result) {
                            $updated++;
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'student_number' => $row['student_number'],
                                'action' => 'updated'
                            ];
                        } else {
                            $errors[] = "Row $rowNumber: Failed to update student";
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'student_number' => $row['student_number'],
                                'action' => 'error',
                                'reason' => 'update failed'
                            ];
                        }
                        break;
                        
                    case 'replace':
                        $result = updateStudentRecord($row, $existingStudent, $connection, $selectedDepartmentId, $selectedProgramId);
                        if ($result) {
                            $updated++;
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'student_number' => $row['student_number'],
                                'action' => 'updated'
                            ];
                        } else {
                            $errors[] = "Row $rowNumber: Failed to update student";
                            $rowsOutcomes[] = [
                                'rowNumber' => $rowNumber,
                                'student_number' => $row['student_number'],
                                'action' => 'error',
                                'reason' => 'update failed'
                            ];
                        }
                        break;
                }
            } else {
                // Student doesn't exist
                $result = createStudentRecord($row, $pageType, $selectedDepartmentId, $selectedProgramId, $connection, $sendNotifications);
                if ($result) {
                    $imported++;
                    $rowsOutcomes[] = [
                        'rowNumber' => $rowNumber,
                        'student_number' => $row['student_number'],
                        'action' => 'imported'
                    ];
                } else {
                    $errors[] = "Row $rowNumber: Failed to create student";
                    $rowsOutcomes[] = [
                        'rowNumber' => $rowNumber,
                        'student_number' => $row['student_number'],
                        'action' => 'error',
                        'reason' => 'create failed'
                    ];
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            $rowsOutcomes[] = [
                'rowNumber' => $rowNumber,
                'student_number' => $row['student_number'] ?? null,
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

function createStudentRecord($data, $pageType, $selectedDepartmentId, $selectedProgramId, $connection, $sendNotifications) {
    try {
        $username = $data['student_number'];
        $accountStatus = 'active';
        
        // Check for existing user by username (student_number)
        $stmt = $connection->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $existingUserId = $stmt->fetchColumn();
        
        if ($existingUserId) {
            $userId = (int)$existingUserId;
            // Safeguard email: if provided email is used by another user, null it
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
            // Generate default password (LastName + StudentNumber) - same as addUsers.php
            $defaultPasswordPlain = ($data['last_name'] ?? '') . ($data['student_number'] ?? '');
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
            
            $stmt = $connection->prepare("
                INSERT INTO users (username, password, email, first_name, last_name, middle_name, contact_number, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $data['student_number'],
                $defaultPassword,
                $emailToUse,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['contact_number'] ?? null,
                $accountStatus
            ]);
            
            $userId = $connection->lastInsertId();
            
            // Assign Student role (role_id 3 for Student, same as addUsers.php)
            $stmt = $connection->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, 3, NOW())");
            $stmt->execute([$userId]);
        }
        
        // Determine sector and year level mapping (same as addUsers.php)
        $dbSector = ($pageType === 'shs') ? 'Senior High School' : 'College';
        
        // Map form year_level to database ENUM values
        $yearLevelMapping = [
            'Grade 11' => '1st Year',
            'Grade 12' => '2nd Year',
        ];
        $dbYearLevel = $yearLevelMapping[$data['year_level']] ?? $data['year_level'] ?? null;
        
        // Create student record (same structure as addUsers.php)
        $studentSql = "INSERT INTO students (student_id, user_id, program_id, department_id, sector, year_level, section) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $studentStmt = $connection->prepare($studentSql);
        $studentStmt->execute([
            $data['student_number'],
            $userId,
            $selectedProgramId,
            $selectedDepartmentId,
            $dbSector,
            $dbYearLevel,
            empty($data['section']) ? null : $data['section']
        ]);
        
        // Send welcome notification if requested
        if ($sendNotifications) {
            logImportActivity('student_notification', 1, 1, 0, 0);
        }
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception('Failed to create student record: ' . $e->getMessage());
    }
}

function updateStudentRecord($data, $existingStudent, $connection, $selectedDepartmentId, $selectedProgramId) {
    try {
        // Update user fields: email, contact_number
        $emailToUse = $data['email'] ?? null;
        if ($emailToUse) {
            $chk = $connection->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
            $chk->execute([$emailToUse, $existingStudent['user_id']]);
            if ($chk->fetchColumn()) {
                $emailToUse = null; // Email conflict, skip
            }
        }
        
        $stmt = $connection->prepare("UPDATE users SET email = COALESCE(?, email), contact_number = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([
            $emailToUse,
            $data['contact_number'] ?? null,
            $existingStudent['user_id']
        ]);
        
        // Update student information
        $yearLevelMapping = [
            'Grade 11' => '1st Year',
            'Grade 12' => '2nd Year',
        ];
        $dbYearLevel = $yearLevelMapping[$data['year_level']] ?? $data['year_level'] ?? null;
        
        $stmt = $connection->prepare("
            UPDATE students 
            SET program_id = ?, department_id = ?, year_level = COALESCE(?, year_level), section = COALESCE(?, section), updated_at = NOW()
            WHERE student_id = ?
        ");
        
        $stmt->execute([
            $selectedProgramId,
            $selectedDepartmentId,
            $dbYearLevel,
            $data['section'] ?? null,
            $data['student_number']
        ]);
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception('Failed to update student record: ' . $e->getMessage());
    }
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
