# Online Clearance System - Entity Relationship Diagram (ERD)

## Database Overview

This ERD represents the Online Clearance System database with sector-based clearance management for College, Senior High School, and Faculty.

## Core Entities and Relationships

### 1. Academic Structure

```
academic_years (1) ──→ (many) semesters
semesters (1) ──→ (many) clearance_periods
```

### 2. User Management

```
users (1) ──→ (1) students
users (1) ──→ (1) faculty
users (1) ──→ (1) staff
users (many) ──→ (many) roles [via user_roles]
```

### 3. Organizational Structure

```
sectors (1) ──→ (many) departments
departments (1) ──→ (many) programs
departments (1) ──→ (many) staff [for Program Heads]
```

### 4. Clearance System

```
clearance_periods (1) ──→ (many) clearance_forms
clearance_forms (1) ──→ (many) clearance_signatories
```

## Detailed Entity Descriptions

### Academic Management

- **academic_years**: School years (e.g., "2024-2025")
- **semesters**: Terms within academic years (1st, 2nd, Summer)
- **sectors**: Three main sectors (College, Senior High School, Faculty)

### User Management

- **users**: Base user accounts for all system users
- **students**: Student-specific data with sector assignment
- **faculty**: Faculty members with employment status and department
- **staff**: Staff members with designations and departments
- **roles**: System roles (Admin, Student, Faculty, etc.)
- **permissions**: System permissions for role-based access

### Organizational Structure

- **departments**: Academic departments linked to sectors
- **programs**: Academic programs within departments
- **designations**: Staff positions (Registrar, Cashier, etc.)

### Clearance System

- **clearance_periods**: Sector-based clearance periods with status tracking
- **clearance_forms**: Individual clearance forms for users
- **clearance_signatories**: Signatory assignments for each form

### Supporting Tables

- **clearance_requirements**: Requirements for each clearance type
- **rejection_reasons**: Predefined rejection reasons
- **audit_logs**: System audit trail
- **user_activities**: User activity tracking
- **system_settings**: System configuration

## Key Relationships

### Sector-Based Clearance Flow

1. **Academic Year** → **Semesters** → **Clearance Periods** (per sector)
2. **Clearance Periods** → **Clearance Forms** (for eligible users in sector)
3. **Clearance Forms** → **Clearance Signatories** (per form)
4. **Clearance Signatories** → **Actions** (Approved/Rejected/Pending)

### User-Sector Mapping

- **Students**: Assigned to College or Senior High School sectors
- **Faculty**: Assigned to Faculty sector
- **Staff**: Can be signatories for any sector based on assignments

### Signatory Assignment Logic

- **Regular Staff**: Assigned through clearance signatories based on designations
- **Program Heads**: Department-specific assignments through clearance signatories

## Database Features

### Stored Procedures

- `StartClearancePeriod`: Creates clearance period and forms for sector
- `CloseClearancePeriod`: Closes period and updates form statuses

### Views

- `active_clearance_periods`: Active clearance periods with academic info
- `clearance_forms_with_sector`: Clearance forms with sector information

### Triggers

- Auto-generate clearance form IDs
- Ensure single active academic year
- Validate staff employee number format
- Enforce single active clearance period

## Data Flow Summary

1. **Setup Phase**: Create academic years, semesters, assign signatories to sectors
2. **Activation Phase**: Activate terms, create clearance periods for all sectors
3. **Clearance Phase**: Start sector clearance, create forms for eligible users
4. **Signatory Phase**: Signatories review and approve/reject forms
5. **Completion Phase**: Close clearance periods, finalize all forms

This ERD represents a comprehensive clearance management system with sector-based workflows, role-based access control, and automated form distribution with signatory assignments managed through the clearance signatories table.
