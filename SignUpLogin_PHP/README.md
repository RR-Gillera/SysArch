# CCS Portal - PHP Version

A College of Computer Studies portal application converted from ASP.NET Core to PHP.

## Project Structure

```
SignUpLogin_PHP/
├── config/
│   ├── config.php          # Application configuration and helper functions
│   └── database.php        # Database connection class
├── controllers/
│   ├── LoginController.php     # Login/logout functionality
│   ├── SignupController.php    # User registration
│   ├── HomeController.php      # Student home page
│   ├── ProfileController.php   # Profile management
│   └── AdminController.php     # Admin dashboard
├── models/
│   └── Signup.php          # User model with database operations
├── views/
│   ├── login/              # Login view
│   ├── signup/             # Registration view
│   ├── home/               # Student home view
│   ├── profile/            # Profile views (to be created)
│   ├── admin/              # Admin views (to be created)
│   └── shared/             # Shared layouts and partials
├── public/
│   ├── index.php           # Main entry point (router)
│   ├── .htaccess           # URL rewriting rules
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   └── images/             # Image assets
├── database/
│   └── schema.sql          # Database schema
└── README.md               # This file
```

## Requirements

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache with mod_rewrite enabled
- PDO MySQL extension

## Installation

### 1. Clone/Copy the Project

Place the `SignUpLogin_PHP` folder in your web server's document root (e.g., `htdocs` for XAMPP or `www` for WAMP).

### 2. Configure Database

1. Open phpMyAdmin or your MySQL client
2. Run the SQL script: `database/schema.sql`
3. Update database credentials in `config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "ccs_portal";
   private $username = "root";
   private $password = ""; // Your MySQL password
   ```

### 3. Configure Base URL

Update the `BASE_URL` constant in `config/config.php` if needed:
```php
define('BASE_URL', '/SignUpLogin_PHP/public/');
```

### 4. Set Permissions

Ensure the `public/images/profiles/` directory is writable:
```bash
chmod -R 777 SignUpLogin_PHP/public/images
```

### 5. Enable mod_rewrite (Apache)

Make sure Apache's mod_rewrite module is enabled. In `.htaccess`:
```apache
RewriteEngine On
RewriteBase /SignUpLogin_PHP/public/
```

## Default Login Credentials

**Admin:**
- ID Number: `ADMIN001`
- Password: `Admin@123` (you'll need to generate a proper bcrypt hash)

**Student:**
- Register a new account at `/signup`

## Features

- User Registration with validation
- Login/Logout with session management
- Remember Me functionality
- Role-based access (Student/Admin)
- Profile Management
- Password Change
- Admin Dashboard

## Security Features

- Password hashing using bcrypt
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars()
- CSRF protection (recommended to implement)
- Session security

## URL Routes

| URL | Controller | Action | Description |
|-----|-----------|--------|-------------|
| `/login` | LoginController | index | Login page |
| `/login/login` | LoginController | login | Process login |
| `/login/logout` | LoginController | logout | Logout user |
| `/signup` | SignupController | index | Registration page |
| `/signup/register` | SignupController | register | Process registration |
| `/home` | HomeController | index | Student home |
| `/profile` | ProfileController | index | View profile |
| `/profile/edit` | ProfileController | edit | Edit profile |
| `/profile/changePassword` | ProfileController | changePassword | Change password |
| `/admin/home` | AdminController | home | Admin dashboard |
| `/admin/analytics` | AdminController | analytics | Analytics page |
| `/admin/students` | AdminController | students | Student list |
| `/admin/feedback` | AdminController | feedback | Feedback list |

## Migration Notes (from ASP.NET Core)

1. **Entity Framework → PDO**: Replaced EF Core with raw PDO queries
2. **Identity → Custom Auth**: Implemented custom session-based authentication
3. **BCrypt**: Using PHP's native `password_hash()` and `password_verify()`
4. **Razor Views → PHP Templates**: Converted .cshtml to .php
5. **Dependency Injection → Manual Instantiation**: Controllers manually instantiate models
6. **Middleware → Helper Functions**: Session and auth checks done via helper functions

## TODO

- [ ] Complete admin views (analytics, announcements, feedback, reservations)
- [ ] Complete profile views (edit, change password)
- [ ] Implement sit-in recording system
- [ ] Add announcement management
- [ ] Add feedback system
- [ ] Implement lab status management
- [ ] Add CSRF token protection
- [ ] Add input sanitization middleware
- [ ] Add error logging
- [ ] Add email verification
- [ ] Add password reset functionality

## License

This project is for educational purposes.
