# Online Clearance System

A web-based clearance management system for educational institutions.

## Project Structure

```
OnlineClearanceWebsite/
├── assets/
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   ├── images/
│   └── fonts/
├── includes/
│   ├── config/
│   ├── functions/
│   └── components/
│       └── sidebar.php
├── pages/
│   ├── auth/
│   │   └── login.php
│   ├── student/
│   │   └── dashboard.php
│   ├── faculty/
│   │   └── dashboard.php
│   ├── admin/
│   │   └── dashboard.php
│   └── shared/
├── database/
├── config/
└── index.php
```

## File Locations

### Main Pages

- **Login:** `pages/auth/login.php`
- **Student Dashboard:** `pages/student/dashboard.php`
- **Faculty Dashboard:** `pages/faculty/dashboard.php`
- **Admin Dashboard:** `pages/admin/dashboard.php`

### Components

- **Sidebar:** `includes/components/sidebar.php`
- **Styles:** `assets/css/styles.css`

### Access URLs

- Login: `http://localhost/OnlineClearanceWebsite/pages/auth/login.php`
- Student Dashboard: `http://localhost/OnlineClearanceWebsite/pages/student/dashboard.php`
- Faculty Dashboard: `http://localhost/OnlineClearanceWebsite/pages/faculty/dashboard.php`
- Admin Dashboard: `http://localhost/OnlineClearanceWebsite/pages/admin/dashboard.php`

## Features

- **Dynamic Sidebar:** Role-based navigation
- **Responsive Design:** Works on all devices
- **User Roles:** Student, Faculty, Admin, School Admin, Program Head, Staff
- **Modern UI:** Clean, professional interface

## Development Status

- ✅ Login interface
- ✅ Dashboard layouts
- ✅ Dynamic sidebar
- ✅ Responsive design
- ✅ Project structure reorganization

## Next Steps

- Database integration
- Authentication system
- Role-based permissions
- Clearance management features
