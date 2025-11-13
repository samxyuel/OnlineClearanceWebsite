# Online Clearance System

A modern, web-based clearance management system for educational institutions with a refactored architecture following PHP best practices.

## 🏗️ Architecture

The system follows a layered architecture with clear separation of concerns:

- **Presentation Layer**: Pages, modals, and frontend assets
- **API Layer**: RESTful endpoints with standardized responses
- **Application Layer**: Services, middleware, and business logic
- **Data Access Layer**: Repositories and models
- **Database Layer**: MySQL with transaction support

For detailed architecture documentation, see [ARCHITECTURE.md](ARCHITECTURE.md) and [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md).

## 📁 Project Structure

```
OnlineClearanceWebsite/
├── api/                          # API endpoints
│   ├── auth/                     # Authentication endpoints
│   ├── users/                    # User management endpoints
│   ├── clearance/                # Clearance workflow endpoints
│   └── ...
├── assets/                       # Frontend assets
│   ├── css/
│   ├── js/
│   └── images/
├── config/                       # Configuration files
│   └── app.php                   # Main configuration
├── includes/
│   ├── bootstrap.php             # Application initialization
│   ├── classes/                  # PSR-4 autoloaded classes
│   │   ├── core/                 # Core infrastructure
│   │   │   ├── Container.php     # Dependency injection
│   │   │   ├── Config.php        # Configuration manager
│   │   │   ├── Database.php      # Database manager
│   │   │   ├── ErrorHandler.php  # Error handling
│   │   │   └── Logger.php        # Logging system
│   │   ├── middleware/           # Request middleware
│   │   │   ├── AuthMiddleware.php
│   │   │   ├── AuthorizationMiddleware.php
│   │   │   └── ValidationMiddleware.php
│   │   ├── services/             # Business logic
│   │   │   ├── AuthService.php
│   │   │   ├── UserService.php
│   │   │   └── ClearanceService.php
│   │   ├── repositories/         # Data access
│   │   │   ├── UserRepository.php
│   │   │   ├── ClearanceRepository.php
│   │   │   └── SignatoryRepository.php
│   │   ├── models/               # Domain models
│   │   │   ├── User.php
│   │   │   └── ClearanceApplication.php
│   │   └── exceptions/           # Custom exceptions
│   ├── components/               # Reusable UI components
│   ├── config/                   # Legacy config (being phased out)
│   └── functions/                # Helper functions
├── logs/                         # Application logs
├── pages/                        # Frontend pages
│   ├── auth/
│   ├── student/
│   ├── faculty/
│   ├── admin/
│   └── ...
├── vendor/                       # Composer dependencies
├── composer.json                 # Dependency management
└── index.php                     # Entry point
```

## ✨ Features

### Core Features
- **User Management**: Create, update, delete users with role-based access
- **Authentication**: Secure login with session management and account lockout
- **Authorization**: Role-based and permission-based access control
- **Clearance Workflow**: Application, approval, and tracking system
- **Audit Logging**: Comprehensive logging of all important operations
- **Multi-Role Support**: Student, Faculty, Staff, Program Head, School Admin, Admin

### Technical Features
- **Modern Architecture**: Layered design with separation of concerns
- **Dependency Injection**: Loose coupling between components
- **Input Validation**: 15+ built-in validation rules with sanitization
- **Error Handling**: Typed exceptions with standardized JSON responses
- **Transaction Support**: Database transactions for data integrity
- **PSR-4 Autoloading**: Standard PHP autoloading
- **Multi-Channel Logging**: Separate logs for application, errors, auth, and audit

## 🚀 Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd OnlineClearanceWebsite
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   
   Update `config/app.php` or set environment variables:
   ```php
   'database' => [
       'host' => 'localhost',
       'name' => 'online_clearance_db',
       'user' => 'root',
       'password' => '',
   ]
   ```

4. **Import database**
   ```bash
   mysql -u root -p online_clearance_db < basedata_db.sql
   ```

5. **Set permissions**
   ```bash
   chmod -R 755 logs/
   ```

6. **Run the application**
   ```bash
   php -S localhost:8000
   ```

   Access at: `http://localhost:8000`

### Environment Variables

For production, set these environment variables:

- `APP_ENV` - Environment (development, production)
- `DB_HOST` - Database host
- `DB_NAME` - Database name
- `DB_USER` - Database username
- `DB_PASS` - Database password

## 📚 API Documentation

### Authentication Endpoints

**Login**
```http
POST /api/auth/login.php
Content-Type: application/json

{
  "username": "john.doe",
  "password": "password123"
}
```

**Logout**
```http
POST /api/auth/logout.php
```

**Verify Session**
```http
GET /api/auth/verify.php
```

### User Management Endpoints

**Create User**
```http
POST /api/users/create.php
Content-Type: application/json

{
  "username": "john.doe",
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "password": "SecurePass123",
  "role_id": 1
}
```

**Get Users**
```http
GET /api/users/read.php?page=1&per_page=20
GET /api/users/read.php?user_id=123
GET /api/users/read.php?search=john
```

**Update User**
```http
POST /api/users/update.php
Content-Type: application/json

{
  "user_id": 123,
  "email": "newemail@example.com"
}
```

**Delete User**
```http
POST /api/users/delete.php
Content-Type: application/json

{
  "user_id": 123
}
```

### Clearance Endpoints

**Create Application**
```http
POST /api/clearance/apply_new.php
Content-Type: application/json

{
  "period_id": 1,
  "clearance_type": "College"
}
```

**Get Status**
```http
GET /api/clearance/status_new.php?period_id=1
GET /api/clearance/status_new.php?application_id=123
```

**Signatory Action**
```http
POST /api/clearance/signatory_action_new.php
Content-Type: application/json

{
  "form_id": 456,
  "action": "approve",
  "remarks": "All requirements met"
}
```

**Bulk Approve**
```http
POST /api/clearance/bulk_signatory_action_new.php
Content-Type: application/json

{
  "form_ids": [456, 457, 458],
  "action": "approve"
}
```

## 🧪 Testing

Run the test scripts to verify components:

```bash
# Test core infrastructure
php test_infrastructure.php

# Test error handling and logging
php test_error_handling.php

# Test middleware
php test_middleware.php
```

## 🔒 Security Features

- **Password Hashing**: Bcrypt with cost factor 12
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Prevention**: Input sanitization
- **Session Security**: Timeout, regeneration, httpOnly cookies
- **Account Lockout**: Protection against brute force attacks
- **Audit Logging**: All sensitive operations logged
- **Error Sanitization**: No sensitive data in error responses

## 📖 Development Guide

### Adding a New API Endpoint

1. **Create the endpoint file**
   ```php
   <?php
   require_once __DIR__ . '/../../includes/bootstrap.php';
   
   use App\Services\YourService;
   use App\Middleware\AuthMiddleware;
   use App\Core\ErrorHandler;
   
   ErrorHandler::register();
   
   try {
       $authMiddleware = new AuthMiddleware();
       $authMiddleware->requireAuth();
       
       $service = new YourService();
       $result = $service->yourMethod();
       
       http_response_code(200);
       echo json_encode($result);
   } catch (AppException $e) {
       http_response_code($e->getHttpStatusCode());
       echo json_encode(ErrorHandler::formatError($e));
   }
   ```

2. **Use services for business logic**
3. **Use middleware for auth/validation**
4. **Return standardized JSON responses**

### Code Style

- Follow PSR-4 autoloading standards
- Use type hints and return types
- Document all public methods with PHPDoc
- Keep methods focused and single-purpose
- Use dependency injection

## 📝 User Roles

- **Admin**: Full system access
- **School Administrator**: Manage school-wide settings
- **Program Head**: Manage department clearances
- **Faculty**: Approve faculty clearances
- **Regular Staff**: Approve staff clearances
- **Student**: Apply for and track clearances

## 🤝 Contributing

1. Follow the established architecture patterns
2. Write tests for new features
3. Update documentation
4. Use meaningful commit messages

## 📄 License

[Your License Here]

## 📞 Support

For issues or questions:
- Check [ARCHITECTURE.md](ARCHITECTURE.md) for architecture details
- Check [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md) for implementation guide
- Review test files for usage examples
