-- =====================================================
-- DIAGNOSTIC QUERIES - No information_schema needed
-- Run these to find the problem
-- =====================================================

-- 1. Check if users table exists and show its structure
SHOW CREATE TABLE `users`;

-- 2. Check students table structure
SHOW CREATE TABLE `students`;

-- 3. Check if users.user_id is a primary key
SHOW KEYS FROM `users` WHERE Key_name = 'PRIMARY';

-- 4. Check if students.user_id column exists and its type
SHOW COLUMNS FROM `students` LIKE 'user_id';

-- 5. Check if users.user_id column exists and its type
SHOW COLUMNS FROM `users` LIKE 'user_id';

-- 6. Check table engines (if accessible)
SHOW TABLE STATUS WHERE Name IN ('users', 'students', 'audit_logs', 'faculty', 'staff');

-- 7. Check for orphaned data (students without valid users)
SELECT COUNT(*) as orphaned_students
FROM students s
LEFT JOIN users u ON s.user_id = u.user_id
WHERE s.user_id IS NOT NULL AND u.user_id IS NULL;

-- 8. Check if there are NULL values in students.user_id that shouldn't be there
SELECT COUNT(*) as null_user_ids
FROM students
WHERE user_id IS NULL;








