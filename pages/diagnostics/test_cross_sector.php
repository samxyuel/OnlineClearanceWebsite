<?php
/**
 * Cross-Sector Department Management - Browser Test Page
 * Provides a simple interface to test the implementation
 */

session_start();
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = $auth->getUserId();
$userRole = $auth->getRoleName();

// Get Program Head departments for testing
$programHeadDepts = [];
if ($userRole === 'Program Head') {
    $programHeadDepts = getCrossSectorDepartmentIds($pdo, $userId);
}

// Get all departments with cross-sector info
$stmt = $pdo->query("
    SELECT 
        d.department_id,
        d.department_name,
        d.department_code,
        s.sector_name,
        s.sector_id
    FROM departments d
    JOIN sectors s ON d.sector_id = s.sector_id
    WHERE d.is_active = 1
    ORDER BY d.department_name, s.sector_name
");
$allDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by department code/name
$groupedDepts = [];
foreach ($allDepartments as $dept) {
    $key = $dept['department_code'] ?: $dept['department_name'];
    if (!isset($groupedDepts[$key])) {
        $groupedDepts[$key] = [
            'name' => $dept['department_name'],
            'code' => $dept['department_code'],
            'sectors' => []
        ];
    }
    $groupedDepts[$key]['sectors'][] = [
        'sector_name' => $dept['sector_name'],
        'department_id' => $dept['department_id']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cross-Sector Department Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #0056b3;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        .shared-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <h1>Cross-Sector Department Management - Test Page</h1>
    
    <div class="container">
        <h2>User Information</h2>
        <p><strong>User ID:</strong> <?php echo htmlspecialchars($userId); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($userRole); ?></p>
        <?php if ($userRole === 'Program Head'): ?>
            <p><strong>Cross-Sector Department IDs:</strong> <?php echo json_encode($programHeadDepts); ?></p>
            <p><strong>Total Departments (Cross-Sector):</strong> <?php echo count($programHeadDepts); ?></p>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Department Overview</h2>
        <table>
            <thead>
                <tr>
                    <th>Department Name</th>
                    <th>Code</th>
                    <th>Sectors</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedDepts as $key => $dept): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dept['name']); ?></td>
                        <td><?php echo htmlspecialchars($dept['code'] ?: 'N/A'); ?></td>
                        <td>
                            <?php 
                            $sectorNames = array_column($dept['sectors'], 'sector_name');
                            echo implode(', ', $sectorNames);
                            if (count($dept['sectors']) > 1) {
                                echo ' <span class="shared-badge">SHARED</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (count($dept['sectors']) > 1): ?>
                                <span class="success">✓ Cross-Sector</span>
                            <?php else: ?>
                                <span class="info">Single Sector</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="container">
        <h2>API Tests</h2>
        
        <div class="test-section">
            <h3>Test 1: Departments List API</h3>
            <button onclick="testDepartmentsList()">Test Departments List</button>
            <div id="result1" class="result" style="display:none;"></div>
        </div>

        <?php if ($userRole === 'Program Head'): ?>
        <div class="test-section">
            <h3>Test 2: Program Head Access Check</h3>
            <button onclick="testIsAssigned('College')">Test College Access</button>
            <button onclick="testIsAssigned('Faculty')">Test Faculty Access</button>
            <button onclick="testIsAssigned('Senior High School')">Test SHS Access</button>
            <div id="result2" class="result" style="display:none;"></div>
        </div>

        <div class="test-section">
            <h3>Test 3: Signatory List (College)</h3>
            <button onclick="testSignatoryList('student', 'College')">Test College Students</button>
            <div id="result3" class="result" style="display:none;"></div>
        </div>

        <div class="test-section">
            <h3>Test 4: Signatory List (Faculty)</h3>
            <button onclick="testSignatoryList('faculty', '')">Test Faculty Members</button>
            <div id="result4" class="result" style="display:none;"></div>
        </div>
        <?php endif; ?>

        <?php if ($userRole === 'School Administrator'): ?>
        <div class="test-section">
            <h3>Test 5: Create Shared Department</h3>
            <p>Use the form below to create a test shared department:</p>
            <form id="createDeptForm" onsubmit="createDepartment(event)">
                <p>
                    <label>Name: <input type="text" name="name" value="Test IT" required></label>
                </p>
                <p>
                    <label>Code: <input type="text" name="code" value="TIT" maxlength="10" pattern="[A-Z0-9]+" required></label>
                </p>
                <p>
                    <label>Type: 
                        <select name="type" required>
                            <option value="college">College Only</option>
                            <option value="faculty">Faculty Only</option>
                            <option value="college-faculty" selected>College & Faculty</option>
                            <option value="shs-faculty">SHS & Faculty</option>
                            <option value="all-sectors">All Sectors</option>
                        </select>
                    </label>
                </p>
                <button type="submit">Create Department</button>
            </form>
            <div id="result5" class="result" style="display:none;"></div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showResult(elementId, data, isError = false) {
            const el = document.getElementById(elementId);
            el.style.display = 'block';
            el.className = 'result ' + (isError ? 'error' : 'success');
            el.textContent = JSON.stringify(data, null, 2);
        }

        async function testDepartmentsList() {
            try {
                const response = await fetch('../../api/departments/list.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                showResult('result1', data);
            } catch (error) {
                showResult('result1', {error: error.message}, true);
            }
        }

        async function testIsAssigned(clearanceType) {
            try {
                const response = await fetch(`../../api/program-head/is_assigned.php?clearance_type=${encodeURIComponent(clearanceType)}`, {
                    credentials: 'include'
                });
                const data = await response.json();
                showResult('result2', data);
            } catch (error) {
                showResult('result2', {error: error.message}, true);
            }
        }

        async function testSignatoryList(type, sector) {
            const url = new URL('../../api/clearance/signatoryList.php', window.location.origin);
            url.searchParams.set('type', type);
            if (sector) url.searchParams.set('sector', sector);
            
            try {
                const response = await fetch(url, {
                    credentials: 'include'
                });
                const data = await response.json();
                showResult(type === 'faculty' ? 'result4' : 'result3', {
                    total: data.total,
                    can_perform_actions: data.can_perform_actions,
                    items: data[type === 'faculty' ? 'faculty' : 'students']?.slice(0, 5) // Show first 5
                });
            } catch (error) {
                showResult(type === 'faculty' ? 'result4' : 'result3', {error: error.message}, true);
            }
        }

        async function createDepartment(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            const data = {
                name: formData.get('name'),
                code: formData.get('code').toUpperCase(),
                type: formData.get('type'),
                status: 'active'
            };

            try {
                const response = await fetch('../../api/departments/create.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    credentials: 'include',
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                showResult('result5', result, !result.success);
                if (result.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                showResult('result5', {error: error.message}, true);
            }
        }
    </script>
</body>
</html>

