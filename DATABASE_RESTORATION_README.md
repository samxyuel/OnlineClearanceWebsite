# Database Relationship Restoration Guide

## 🎯 Overview

This guide will help you restore all Primary Keys (PK) and Foreign Keys (FK) that were removed during the database import from alwaysdata to your local MySQL/MariaDB server.

## 📋 Problem Description

When exporting/importing databases from certain hosting providers like alwaysdata, the foreign key constraints may not be included in the export, or they may fail to import properly. This leaves your database without referential integrity constraints.

### Symptoms:

- ✗ No foreign key constraints visible in phpMyAdmin
- ✗ No cascade delete/update functionality
- ✗ No referential integrity enforcement
- ✗ Database relationship diagram appears empty

## 🛠️ Tools Provided

We've created several tools to help you restore your database relationships:

### 1. **restore_foreign_keys.sql**

Pure SQL script that restores all foreign key constraints.

**When to use:** If you prefer running SQL directly in phpMyAdmin or MySQL command line.

**How to use:**

```sql
-- In phpMyAdmin or MySQL CLI
SOURCE restore_foreign_keys.sql;
```

### 2. **restore_database_relationships.php** ⭐ RECOMMENDED

Interactive PHP web interface for restoring database relationships.

**Features:**

- ✅ Check current database status
- ✅ Drop existing foreign keys (if any)
- ✅ Restore all relationships
- ✅ Verify restoration success
- ✅ Visual feedback and error handling

**How to use:**

1. Navigate to: `http://localhost/OnlineClearanceWebsite/restore_database_relationships.php`
2. Follow the on-screen instructions
3. Click through the 3-step process

### 3. **verify_database_relationships.php**

Comprehensive verification tool that displays all database relationships.

**Features:**

- 📊 Statistics dashboard
- 🔗 List all foreign keys
- 🌟 Show most referenced tables
- 📈 Complexity analysis

**How to use:**
Navigate to: `http://localhost/OnlineClearanceWebsite/verify_database_relationships.php`

### 4. **DATABASE_RELATIONSHIPS.md**

Complete documentation of all database relationships.

**Contains:**

- All table relationships
- Primary key definitions
- Foreign key mappings
- ON DELETE/UPDATE actions
- Relationship hierarchy

## 🚀 Step-by-Step Restoration Process

### Step 1: Backup Your Database ⚠️

**IMPORTANT:** Always backup before making structural changes!

```bash
# Using mysqldump
mysqldump -u root -p basedata_db > backup_before_restoration.sql

# Or use the provided backup script
php backup_database_safe.php
```

### Step 2: Verify Current State

1. Open your browser and navigate to:

   ```
   http://localhost/OnlineClearanceWebsite/verify_database_relationships.php
   ```

2. Check the statistics:
   - How many tables do you have?
   - How many foreign keys are currently present?
   - Which tables are missing relationships?

### Step 3: Run the Restoration

#### Option A: Using the Web Interface (Recommended)

1. Navigate to:

   ```
   http://localhost/OnlineClearanceWebsite/restore_database_relationships.php
   ```

2. Click **"Check Status"** to see current state

3. Follow the 3-step process:

   - **Step 1:** Review current status
   - **Step 2:** Drop existing foreign keys (if needed)
   - **Step 3:** Restore all relationships

4. Wait for completion message

#### Option B: Using SQL Script

1. Open phpMyAdmin
2. Select your database (`basedata_db`)
3. Go to the **SQL** tab
4. Load and execute `restore_foreign_keys.sql`

OR using MySQL command line:

```bash
mysql -u root -p basedata_db < restore_foreign_keys.sql
```

### Step 4: Verify Restoration

1. Navigate to:

   ```
   http://localhost/OnlineClearanceWebsite/verify_database_relationships.php
   ```

2. Verify that:

   - ✅ Total foreign keys > 70
   - ✅ All major tables have foreign keys
   - ✅ No errors in the relationship list

3. Or check in phpMyAdmin:
   - Select a table (e.g., `students`)
   - Go to **Structure** tab
   - Look for **Relation view** link
   - You should see foreign key constraints

## 📊 Expected Results

After successful restoration, you should have:

| Metric             | Expected Value                   |
| ------------------ | -------------------------------- |
| Total Tables       | 35+                              |
| Total Foreign Keys | 70+                              |
| Tables with FKs    | 28+                              |
| Hub Tables         | users, designations, departments |

## 🔍 Key Relationships Restored

### Core User Management

```
users (user_id)
  ├─→ students.user_id
  ├─→ faculty.user_id
  ├─→ staff.user_id
  ├─→ clearance_forms.user_id
  └─→ user_roles.user_id
```

### Academic Structure

```
academic_years (academic_year_id)
  ├─→ semesters.academic_year_id
  └─→ clearance_periods.academic_year_id

semesters (semester_id)
  └─→ clearance_forms.semester_id

departments (department_id)
  ├─→ programs.department_id
  ├─→ students.department_id
  └─→ faculty.department_id
```

### Clearance Management

```
clearance_forms (clearance_form_id)
  ├─→ clearance_signatories.clearance_form_id
  └─→ signatory_actions.clearance_form_id

designations (designation_id)
  ├─→ clearance_requirements.designation_id
  ├─→ clearance_signatories.designation_id
  └─→ staff.designation_id
```

## ⚠️ Common Issues and Solutions

### Issue 1: "Duplicate foreign key constraint"

**Cause:** Some foreign keys already exist.

**Solution:**

1. Use the web interface and click "Drop Foreign Keys" first
2. Then run restoration

### Issue 2: "Cannot add foreign key constraint"

**Cause:** Data integrity issues (orphaned records).

**Solution:**

1. Check for orphaned records:

```sql
-- Example: Find students without valid user_id
SELECT s.*
FROM students s
LEFT JOIN users u ON s.user_id = u.user_id
WHERE u.user_id IS NULL;
```

2. Clean up orphaned records before restoration

### Issue 3: "Access denied"

**Cause:** Database user doesn't have ALTER privileges.

**Solution:**

```sql
-- Grant necessary privileges
GRANT ALTER, REFERENCES ON basedata_db.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### Issue 4: Script timeout

**Cause:** Large database or slow server.

**Solution:**

1. Increase PHP timeout:

```ini
; In php.ini
max_execution_time = 300
```

2. Or run the SQL script directly in MySQL CLI

## 🎯 Verification Checklist

After restoration, verify these critical relationships:

- [ ] Users table has incoming FKs from students, faculty, staff
- [ ] Academic years cascade to semesters and clearance periods
- [ ] Clearance forms link to users, academic years, and semesters
- [ ] Departments link to programs and users
- [ ] Clearance signatories link to forms and designations
- [ ] Role-based permissions are properly linked

## 📚 Additional Resources

- **DATABASE_RELATIONSHIPS.md** - Complete relationship documentation
- **database_schema.sql** - Original schema with all constraints
- **database_schema_refactored.sql** - Refactored schema version

## 🔄 Maintenance

### When to Re-run Restoration:

1. After importing a new database dump
2. After restoring from backup
3. When foreign keys are accidentally dropped
4. When moving database to a new server

### Best Practices:

1. ✅ Always backup before modifications
2. ✅ Test restoration on development server first
3. ✅ Verify relationships after restoration
4. ✅ Document any custom modifications
5. ✅ Keep restoration scripts up to date

## 📞 Support

If you encounter issues:

1. Check the verification tool for specific problems
2. Review error messages in the restoration interface
3. Check MySQL error logs: `C:\xampp\mysql\data\mysql_error.log`
4. Verify database user permissions

## 🎉 Success Indicators

You'll know restoration was successful when:

✅ Verification tool shows 70+ foreign keys  
✅ phpMyAdmin shows relationships in Relation view  
✅ Cascade deletes work correctly  
✅ No orphaned records can be created  
✅ Database diagram displays properly

---

## Quick Start Commands

```bash
# 1. Backup
php backup_database_safe.php

# 2. Verify current state
# Navigate to: http://localhost/.../verify_database_relationships.php

# 3. Restore relationships
# Navigate to: http://localhost/.../restore_database_relationships.php

# 4. Verify restoration
# Navigate to: http://localhost/.../verify_database_relationships.php
```

---

**Last Updated:** November 12, 2025  
**Database:** basedata_db  
**System:** Online Clearance Website

---

## 📝 Notes

- The restoration process is **idempotent** - you can run it multiple times safely
- All foreign keys use appropriate **ON DELETE** and **ON UPDATE** actions
- The system maintains **referential integrity** after restoration
- Performance is optimized with proper **indexing** on all foreign key columns

---

**✨ Ready to restore? Start with the web interface for the easiest experience!**

Navigate to: `http://localhost/OnlineClearanceWebsite/restore_database_relationships.php`







