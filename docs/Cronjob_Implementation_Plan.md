# Cronjob Implementation Plan for Form Distribution

## Problem Statement

### Current Issue

- **Heroku Router Timeout**: 30-second hard limit for HTTP requests
- **Processing Time**: Each user takes ~1.37 seconds to process
- **Scale**: 6000+ users need to be processed
- **Result**: Only ~20 users get forms before timeout (0.3% completion rate)

### Evidence from Logs

- 34 users test: Processed 20 users in 27.37 seconds before timeout
- Average: 1.37 seconds per user
- Estimated time for 6000 users: ~2.3 hours
- Current completion rate: 59% for 34 users, would be 0.3% for 6000 users

---

## Solution Overview

### Cronjob Approach

Instead of processing all users in one HTTP request (which times out), we will:

1. **Queue the job** when admin clicks "Start" (returns in 1 second)
2. **Process in batches** via cronjob (50 users per run, ~68 seconds each)
3. **Continue automatically** until all users are processed
4. **Track progress** so admin can monitor status

### Benefits

- ✅ No timeout issues (each batch completes in ~68 seconds)
- ✅ Admin doesn't wait (returns immediately)
- ✅ Handles 6000+ users reliably
- ✅ Progress tracking for monitoring
- ✅ Automatic recovery if one batch fails

---

## Implementation Steps

### Step 1: Database Schema Changes

#### Create Job Queue Table

```sql
CREATE TABLE form_distribution_jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    clearance_type VARCHAR(50) NOT NULL,
    academic_year_id INT NOT NULL,
    semester_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_users INT DEFAULT 0,
    processed_users INT DEFAULT 0,
    current_batch_start INT DEFAULT 0,
    forms_created INT DEFAULT 0,
    forms_skipped INT DEFAULT 0,
    signatories_assigned INT DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Purpose**: Stores jobs that need to be processed, tracks progress, and maintains state between cronjob runs.

---

### Step 2: Modify `api/clearance/periods.php`

#### Replace HTTP Call with Job Queue Creation

**Location**: Around lines 391-450, 467-523, 546-603 (three places where form distribution is called)

**Current Code** (to be replaced):

```php
// NEW: Trigger form distribution by calling the new dedicated API endpoint
$distributionUrl = getApiBaseUrl('api/clearance/form_distribution.php');
// ... file_get_contents() call ...
```

**New Code**:

```php
// NEW: Create a job in the queue instead of calling directly
try {
    // Step 1: Count total eligible users (quick query)
    $countStmt = $connection->prepare("
        SELECT COUNT(*)
        FROM users u
        INNER JOIN students s ON u.user_id = s.user_id
        INNER JOIN departments d ON s.department_id = d.department_id
        INNER JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE sec.sector_name = ? AND u.account_status = 'active'
    ");
    $countStmt->execute([$sector]);
    $totalUsers = (int)$countStmt->fetchColumn();

    // Step 2: Create job in database
    $stmt = $connection->prepare("
        INSERT INTO form_distribution_jobs
        (clearance_type, academic_year_id, semester_id, status, total_users, created_at)
        VALUES (?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([$sector, $academicYearId, $semesterId, $totalUsers]);
    $jobId = $connection->lastInsertId();

    error_log("📋 FORM DISTRIBUTION: Job #$jobId queued for $sector ($totalUsers users)");

    // Step 3: Return immediately with job info
    $formDistributionResult = [
        'success' => true,
        'message' => 'Form distribution queued. Processing will start shortly.',
        'job_id' => $jobId,
        'total_users' => $totalUsers,
        'status' => 'queued',
        'queued_at' => date('Y-m-d H:i:s')
    ];

} catch (Exception $e) {
    error_log("❌ FORM DISTRIBUTION JOB CREATION ERROR: " . $e->getMessage());
    $formDistributionResult = [
        'success' => false,
        'message' => 'Failed to queue form distribution: ' . $e->getMessage()
    ];
}
```

**Benefits**:

- Returns in ~1 second (just counts users and creates job)
- No timeout issues
- Admin sees immediate response

---

### Step 3: Create Cronjob Script

#### File: `api/cron/process_form_distribution.php`

```php
<?php
/**
 * Cronjob Script: Process Form Distribution Jobs
 *
 * This script runs via Heroku Scheduler every minute
 * Processes one batch (50 users) from the oldest pending/processing job
 *
 * Usage: php api/cron/process_form_distribution.php
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../clearance/form_distribution.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Increase execution time for cronjob (Heroku Scheduler allows up to 10 minutes)
set_time_limit(600); // 10 minutes
ini_set('max_execution_time', 600);

$connection = Database::getInstance()->getConnection();

try {
    // Step 1: Find oldest pending or processing job
    $stmt = $connection->query("
        SELECT * FROM form_distribution_jobs
        WHERE status IN ('pending', 'processing')
        ORDER BY created_at ASC, job_id ASC
        LIMIT 1
    ");
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo "No jobs to process.\n";
        exit(0);
    }

    $jobId = $job['job_id'];
    $batchSize = 50; // Process 50 users per run

    // Step 2: Mark job as processing (if it was pending)
    if ($job['status'] === 'pending') {
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'processing', started_at = NOW()
            WHERE job_id = ?
        ")->execute([$jobId]);
        echo "📋 Started processing job #$jobId\n";
    }

    // Step 3: Get users for this batch
    $startFrom = $job['current_batch_start'];
    $users = getEligibleUsersBatch(
        $connection,
        $job['clearance_type'],
        $startFrom,
        $batchSize
    );

    if (empty($users)) {
        // No more users, mark as completed
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'completed', completed_at = NOW()
            WHERE job_id = ?
        ")->execute([$jobId]);
        echo "✅ Job #$jobId completed! Processed {$job['processed_users']} users.\n";
        exit(0);
    }

    // Step 4: Process this batch
    $batchStartTime = microtime(true);
    $batchFormsCreated = 0;
    $batchFormsSkipped = 0;
    $batchSignatoriesAssigned = 0;

    foreach ($users as $user) {
        // Process user (create form, assign signatories)
        $result = processSingleUser(
            $connection,
            $user,
            $job['academic_year_id'],
            $job['semester_id'],
            $job['clearance_type']
        );

        if ($result['form_created']) {
            $batchFormsCreated++;
        } else {
            $batchFormsSkipped++;
        }
        $batchSignatoriesAssigned += $result['signatories_assigned'];
    }

    // Step 5: Update job progress
    $newProcessed = $job['processed_users'] + count($users);
    $newStart = $startFrom + count($users);
    $newFormsCreated = $job['forms_created'] + $batchFormsCreated;
    $newFormsSkipped = $job['forms_skipped'] + $batchFormsSkipped;
    $newSignatoriesAssigned = $job['signatories_assigned'] + $batchSignatoriesAssigned;

    $connection->prepare("
        UPDATE form_distribution_jobs
        SET processed_users = ?,
            current_batch_start = ?,
            forms_created = ?,
            forms_skipped = ?,
            signatories_assigned = ?,
            updated_at = NOW()
        WHERE job_id = ?
    ")->execute([
        $newProcessed,
        $newStart,
        $newFormsCreated,
        $newFormsSkipped,
        $newSignatoriesAssigned,
        $jobId
    ]);

    // Step 6: Check if done
    if ($newProcessed >= $job['total_users']) {
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'completed', completed_at = NOW()
            WHERE job_id = ?
        ")->execute([$jobId]);

        $duration = round((microtime(true) - $batchStartTime), 2);
        echo "✅ Job #$jobId COMPLETED!\n";
        echo "   Total: {$job['total_users']} users\n";
        echo "   Forms created: $newFormsCreated\n";
        echo "   Forms skipped: $newFormsSkipped\n";
        echo "   Signatories assigned: $newSignatoriesAssigned\n";
        echo "   Final batch took: {$duration}s\n";
    } else {
        $duration = round((microtime(true) - $batchStartTime), 2);
        $remaining = $job['total_users'] - $newProcessed;
        $percentage = round(($newProcessed / $job['total_users']) * 100, 1);

        echo "📊 Job #$jobId Progress:\n";
        echo "   Processed: $newProcessed of {$job['total_users']} users ($percentage%)\n";
        echo "   Remaining: $remaining users\n";
        echo "   This batch: " . count($users) . " users in {$duration}s\n";
        echo "   Will continue next minute...\n";
    }

} catch (Exception $e) {
    // Mark job as failed
    if (isset($jobId)) {
        $connection->prepare("
            UPDATE form_distribution_jobs
            SET status = 'failed',
                error_message = ?,
                updated_at = NOW()
            WHERE job_id = ?
        ")->execute([$e->getMessage(), $jobId]);
    }

    error_log("❌ CRONJOB ERROR: " . $e->getMessage());
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

---

### Step 4: Create Helper Functions

#### File: `api/clearance/form_distribution.php` (add new functions)

```php
/**
 * Get eligible users in batches (for cronjob processing)
 * @param PDO $connection Database connection
 * @param string $clearanceType Sector type
 * @param int $offset Starting offset
 * @param int $limit Number of users to fetch
 * @return array Users array
 */
function getEligibleUsersBatch($connection, $clearanceType, $offset = 0, $limit = 50) {
    $sql = "";
    $params = [];

    switch ($clearanceType) {
        case 'College':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       p.program_name as program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'College'
                AND u.account_status = 'active'
                ORDER BY u.last_name, u.first_name
                LIMIT ? OFFSET ?
            ";
            break;

        case 'Senior High School':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       p.program_name as program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Senior High School'
                AND u.account_status = 'active'
                ORDER BY u.last_name, u.first_name
                LIMIT ? OFFSET ?
            ";
            break;

        case 'Faculty':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       f.employment_status, f.department_id, d.department_name,
                       st.staff_category
                FROM users u
                INNER JOIN faculty f ON u.user_id = f.user_id
                LEFT JOIN departments d ON f.department_id = d.department_id
                LEFT JOIN staff st ON u.user_id = st.user_id
                WHERE u.account_status = 'active'
                ORDER BY u.last_name, u.first_name
                LIMIT ? OFFSET ?
            ";
            break;
    }

    $stmt = $connection->prepare($sql);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Process a single user (create form and assign signatories)
 * @param PDO $connection Database connection
 * @param array $user User data
 * @param int $academicYearId Academic year ID
 * @param int $semesterId Semester ID
 * @param string $clearanceType Sector type
 * @return array Result with form_created, signatories_assigned
 */
function processSingleUser($connection, $user, $academicYearId, $semesterId, $clearanceType) {
    $formsCreated = 0;
    $formsSkipped = 0;
    $signatoriesAssigned = 0;

    // Get signatory assignments (reuse existing function)
    $signatoryAssignments = getSectorSignatoryAssignments($connection, $clearanceType);

    // Check if form exists
    $existingForm = checkExistingForm($connection, $user['user_id'], $academicYearId, $semesterId, $clearanceType);

    if ($existingForm) {
        $clearanceFormId = $existingForm['clearance_form_id'];

        // Check for missing signatories
        $stmt = $connection->prepare("SELECT designation_id FROM clearance_signatories WHERE clearance_form_id = ?");
        $stmt->execute([$clearanceFormId]);
        $existingDesignationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredDesignationIds = array_column($signatoryAssignments, 'designation_id');
        $missingDesignationIds = array_diff($requiredDesignationIds, $existingDesignationIds);

        if (empty($missingDesignationIds)) {
            $formsSkipped = 1;
            return ['form_created' => false, 'signatories_assigned' => 0];
        }

        $missingAssignments = array_filter($signatoryAssignments, function($assignment) use ($missingDesignationIds) {
            return in_array($assignment['designation_id'], $missingDesignationIds);
        });
        $signatoryAssignmentsToProcess = $missingAssignments;
    } else {
        // Create new form
        $clearanceFormId = createClearanceForm($connection, $user, $academicYearId, $semesterId, $clearanceType);
        $formsCreated = 1;
        $signatoryAssignmentsToProcess = $signatoryAssignments;
    }

    // Assign signatories
    $assignedCount = assignSignatoriesToForm($connection, $clearanceFormId, $signatoryAssignmentsToProcess, $user, $clearanceType);
    $signatoriesAssigned = $assignedCount;

    return [
        'form_created' => ($formsCreated > 0),
        'signatories_assigned' => $signatoriesAssigned
    ];
}
```

---

### Step 5: Create Status Check API

#### File: `api/clearance/distribution_status.php`

```php
<?php
/**
 * API: Check Form Distribution Job Status
 *
 * Returns the current status and progress of a form distribution job
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$connection = Database::getInstance()->getConnection();

try {
    $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        exit;
    }

    $stmt = $connection->prepare("SELECT * FROM form_distribution_jobs WHERE job_id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }

    $percentage = $job['total_users'] > 0
        ? round(($job['processed_users'] / $job['total_users']) * 100, 2)
        : 0;

    $estimatedRemaining = null;
    if ($job['status'] === 'processing' && $job['processed_users'] > 0) {
        // Estimate: if we processed X users so far, remaining = (total - processed) * (avg time per user)
        // Assuming 1.37s per user, and 50 users per batch = ~68 seconds per batch
        $batchesRemaining = ceil(($job['total_users'] - $job['processed_users']) / 50);
        $estimatedRemaining = $batchesRemaining; // in minutes (since cron runs every minute)
    }

    echo json_encode([
        'success' => true,
        'job' => [
            'job_id' => $job['job_id'],
            'clearance_type' => $job['clearance_type'],
            'academic_year_id' => $job['academic_year_id'],
            'semester_id' => $job['semester_id'],
            'status' => $job['status'],
            'progress' => [
                'processed' => $job['processed_users'],
                'total' => $job['total_users'],
                'percentage' => $percentage,
                'remaining' => $job['total_users'] - $job['processed_users']
            ],
            'results' => [
                'forms_created' => $job['forms_created'],
                'forms_skipped' => $job['forms_skipped'],
                'signatories_assigned' => $job['signatories_assigned']
            ],
            'estimated_remaining_minutes' => $estimatedRemaining,
            'created_at' => $job['created_at'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
            'error_message' => $job['error_message']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
```

---

### Step 6: Update Frontend to Show Job Status

#### File: `pages/admin/ClearanceManagement.php`

**Add function to check job status**:

```javascript
async function checkDistributionStatus(jobId) {
  try {
    const response = await fetchJSON(
      `${API_BASE}/clearance/distribution_status.php?job_id=${jobId}`
    );

    if (response.success && response.job) {
      const job = response.job;
      const progress = job.progress;

      // Update UI with progress
      console.log(
        `📊 Job #${jobId}: ${progress.processed}/${progress.total} users (${progress.percentage}%)`
      );

      if (job.status === "processing") {
        // Still processing, check again in 5 seconds
        setTimeout(() => checkDistributionStatus(jobId), 5000);
      } else if (job.status === "completed") {
        // Done!
        showToast(
          `Form distribution completed! ${job.results.forms_created} forms created for ${progress.total} users.`,
          "success"
        );
      } else if (job.status === "failed") {
        // Failed
        showToast(`Form distribution failed: ${job.error_message}`, "error");
      }
    }
  } catch (error) {
    console.error("Error checking distribution status:", error);
  }
}
```

**Update `startSectorPeriod` function**:

```javascript
// After receiving response from periods.php
if (response.success && response.form_distribution) {
  const dist = response.form_distribution;

  if (dist.job_id) {
    // Job was queued
    showToast(
      `Form distribution queued. Processing ${dist.total_users} users...`,
      "info"
    );

    // Start checking status
    checkDistributionStatus(dist.job_id);
  } else if (dist.success) {
    // Immediate success (shouldn't happen with cronjob, but handle it)
    showToast(dist.message, "success");
  } else {
    // Error
    showToast(dist.message, "error");
  }
}
```

---

### Step 7: Heroku Scheduler Setup

#### Steps to Configure

1. **Install Heroku Scheduler Add-on**:

   ```bash
   heroku addons:create scheduler:standard
   ```

   Or via Heroku Dashboard:

   - Go to your app → Resources
   - Search for "Heroku Scheduler"
   - Click "Add"

2. **Configure the Job**:

   - Go to Heroku Dashboard → your app → Heroku Scheduler
   - Click "Create job"
   - **Schedule**: `Every 10 minutes` (free tier) or `Every 1 minute` (paid)
   - **Run Command**: `php api/cron/process_form_distribution.php`
   - **Dyno Size**: Standard-1X (or higher for faster processing)

3. **Test the Cronjob**:
   ```bash
   # Test manually first
   heroku run php api/cron/process_form_distribution.php
   ```

---

## Processing Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ Admin clicks "Start Clearance Period"                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ periods.php receives request                                │
│ - Counts eligible users (quick query)                      │
│ - Creates job in form_distribution_jobs table              │
│ - Returns immediately with job_id                          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Admin sees: "Form distribution queued. Processing..."        │
│ (Takes 1 second, no timeout)                                 │
└─────────────────────────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Cronjob runs (every minute)                                 │
│ - Finds oldest pending/processing job                       │
│ - Gets next 50 users                                        │
│ - Processes them (creates forms, assigns signatories)       │
│ - Updates progress in database                              │
│ - Stops (takes ~68 seconds)                                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Next minute: Cronjob runs again                             │
│ - Finds same job (now has processed_users = 50)              │
│ - Gets next 50 users (offset 50)                           │
│ - Processes them                                            │
│ - Updates progress (processed_users = 100)                  │
│ - Continues...                                              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Repeat until all users processed                            │
│ - For 6000 users: 120 runs (120 minutes = 2 hours)          │
│ - Each run: ~68 seconds (under 10-minute limit)            │
│ - Status: "completed" when done                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Migration

### SQL Script: `database/migrations/add_form_distribution_jobs_table.sql`

```sql
-- Create form_distribution_jobs table
CREATE TABLE IF NOT EXISTS form_distribution_jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    clearance_type VARCHAR(50) NOT NULL,
    academic_year_id INT NOT NULL,
    semester_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_users INT DEFAULT 0,
    processed_users INT DEFAULT 0,
    current_batch_start INT DEFAULT 0,
    forms_created INT DEFAULT 0,
    forms_skipped INT DEFAULT 0,
    signatories_assigned INT DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_clearance_type (clearance_type, academic_year_id, semester_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## Testing Plan

### Phase 1: Local Testing

1. **Create test job manually**:

   ```sql
   INSERT INTO form_distribution_jobs
   (clearance_type, academic_year_id, semester_id, status, total_users)
   VALUES ('College', 55, 114, 'pending', 34);
   ```

2. **Run cronjob script manually**:

   ```bash
   php api/cron/process_form_distribution.php
   ```

3. **Verify**:
   - Job processes 50 users (or all if less than 50)
   - Progress updates correctly
   - Forms are created
   - Signatories are assigned

### Phase 2: Heroku Testing (Small Batch)

1. **Test with 34 users** (current test case):
   - Start clearance period
   - Verify job is created
   - Wait for cronjob to run
   - Check status API
   - Verify all 34 users get forms

### Phase 3: Production Testing (Full Scale)

1. **Test with actual 6000 users**:
   - Start clearance period
   - Monitor progress via status API
   - Verify completion after ~2 hours
   - Check all users have forms

---

## Rollout Steps

### Step 1: Database Migration

- [ ] Run SQL migration to create `form_distribution_jobs` table
- [ ] Verify table structure

### Step 2: Code Deployment

- [ ] Deploy updated `periods.php` (job queue creation)
- [ ] Deploy new `api/cron/process_form_distribution.php`
- [ ] Deploy new helper functions in `form_distribution.php`
- [ ] Deploy new `api/clearance/distribution_status.php`
- [ ] Deploy frontend updates in `ClearanceManagement.php`

### Step 3: Heroku Scheduler Setup

- [ ] Install Heroku Scheduler add-on
- [ ] Configure cronjob (every 10 minutes for free, every 1 minute for paid)
- [ ] Test cronjob manually: `heroku run php api/cron/process_form_distribution.php`

### Step 4: Testing

- [ ] Test with small batch (34 users)
- [ ] Verify job creation
- [ ] Verify cronjob processing
- [ ] Verify status API
- [ ] Verify frontend updates

### Step 5: Production Rollout

- [ ] Monitor first production run
- [ ] Verify progress tracking
- [ ] Verify completion
- [ ] Document any issues

---

## Monitoring and Maintenance

### Key Metrics to Monitor

1. **Job Status**: Check for stuck jobs (status = 'processing' for >1 hour)
2. **Error Rate**: Monitor failed jobs
3. **Processing Speed**: Average time per batch
4. **Queue Length**: Number of pending jobs

### Maintenance Queries

**Check active jobs**:

```sql
SELECT * FROM form_distribution_jobs
WHERE status IN ('pending', 'processing')
ORDER BY created_at DESC;
```

**Check failed jobs**:

```sql
SELECT * FROM form_distribution_jobs
WHERE status = 'failed'
ORDER BY created_at DESC
LIMIT 10;
```

**Clean up old completed jobs** (optional, after 30 days):

```sql
DELETE FROM form_distribution_jobs
WHERE status = 'completed'
AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## Estimated Timeline

- **Database Migration**: 5 minutes
- **Code Implementation**: 2-3 hours
- **Testing (Small Batch)**: 30 minutes
- **Heroku Scheduler Setup**: 10 minutes
- **Production Testing**: 2-3 hours (waiting for completion)
- **Total**: ~6-7 hours

---

## Success Criteria

✅ Admin can start clearance period without timeout  
✅ Job is created in database immediately  
✅ Cronjob processes users in batches  
✅ All 6000 users get forms (100% completion)  
✅ Admin can check progress via status API  
✅ Frontend shows progress updates  
✅ Job completes successfully within expected time (~2 hours for 6000 users)

---

## Risk Mitigation

### Risk 1: Cronjob Fails Mid-Process

**Mitigation**: Job status remains 'processing', next cronjob run will continue from where it left off

### Risk 2: Multiple Jobs Queued

**Mitigation**: Cronjob processes oldest job first, one at a time

### Risk 3: Heroku Scheduler Not Running

**Mitigation**: Monitor job status, alert if jobs stay 'pending' for >10 minutes

### Risk 4: Database Connection Issues

**Mitigation**: Try-catch blocks, job marked as 'failed' with error message

---

## Future Enhancements

1. **Email Notifications**: Send email when job completes
2. **Retry Mechanism**: Auto-retry failed jobs
3. **Priority Queue**: Process urgent jobs first
4. **Batch Size Tuning**: Adjust batch size based on performance
5. **Parallel Processing**: Process multiple sectors simultaneously (if needed)

---

## Notes

- Heroku Scheduler free tier runs every 10 minutes (paid tier: every 1 minute)
- Each cronjob run can take up to 10 minutes (Heroku Scheduler limit)
- Batch size of 50 users = ~68 seconds per batch (safe margin)
- For 6000 users: 120 batches × 1 minute = 120 minutes (2 hours) on paid tier
- For 6000 users: 120 batches × 10 minutes = 1200 minutes (20 hours) on free tier

**Recommendation**: Use paid Heroku Scheduler ($25/month) for faster processing, or accept 20-hour processing time on free tier.
