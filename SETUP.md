# Gmail Clone - Setup Guide

## Quick Start Guide

This guide will help you set up and run the Gmail Clone application on your local machine.

## Prerequisites

- **PHP 8.1 or higher** with MySQLi extension
- **MySQL 5.7 or higher** (or MariaDB 10.3+)
- **Web server** (Apache with mod_rewrite or Nginx)
- **Modern web browser** (Chrome, Firefox, Safari, or Edge)

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/opritbprntbdev/surat.git
cd surat/surat
```

### 2. Set Up the Database

#### Option A: Using MySQL Command Line

```bash
# Login to MySQL
mysql -u root -p

# Run the migration script
mysql -u root -p < backend/database/migrations/001_create_emails_table.sql
```

#### Option B: Using phpMyAdmin

1. Open phpMyAdmin in your browser
2. Click "Import" tab
3. Choose the file: `backend/database/migrations/001_create_emails_table.sql`
4. Click "Go" to execute the migration

### 3. Configure Database Connection

Edit the database configuration file:

**File:** `backend/config/database.php`

Update the following constants with your database credentials:

```php
private const HOST = 'localhost';      // Your database host
private const USER = 'root';           // Your database username
private const PASS = '';               // Your database password
private const NAME = 'gmail_clone';    // Database name
```

### 4. Configure Web Server

#### For Apache (XAMPP/WAMP)

1. Copy the project to your web server directory:
   - XAMPP: `C:\xampp\htdocs\surat\`
   - WAMP: `C:\wamp64\www\surat\`

2. Ensure `mod_rewrite` is enabled in Apache

3. Access the application:
   - XAMPP: `http://localhost/surat/surat/frontend/index.html`
   - WAMP: `http://localhost/surat/surat/frontend/index.html`

#### For Nginx

Add this configuration to your site config:

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/surat/surat;

    location / {
        try_files $uri $uri/ /frontend/index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 5. Set File Permissions (Linux/Mac only)

```bash
chmod -R 755 frontend/
chmod -R 755 backend/
chmod -R 777 backend/logs/  # Make logs directory writable
```

### 6. Test the Installation

#### Test Database Connection

Open your browser and navigate to:
```
http://localhost/surat/surat/test.php
```

You should see a JSON response indicating successful database connection:
```json
{
  "success": true,
  "message": "Database connection successful!",
  "total_emails": 3,
  "timestamp": "2025-10-24 10:00:00"
}
```

#### Test the Application

Open your browser and navigate to:
```
http://localhost/surat/surat/frontend/index.html
```

You should see the Gmail Clone interface with sample emails loaded.

## Troubleshooting

### Database Connection Error

**Error:** `Database connection failed`

**Solution:**
1. Verify MySQL is running
2. Check database credentials in `backend/config/database.php`
3. Ensure the `gmail_clone` database exists
4. Verify MySQLi extension is enabled in PHP

```bash
# Check if MySQLi is enabled
php -m | grep mysqli
```

### CORS Errors

**Error:** `Access to fetch blocked by CORS policy`

**Solution:**
1. Ensure you're accessing the application through a web server (not `file://`)
2. CORS headers are already configured in `backend/config/config.php`
3. If using Apache, ensure `.htaccess` is being processed

### Page Not Found / 404 Errors

**Solution:**
1. Ensure mod_rewrite is enabled (Apache)
2. Verify the correct path in your browser URL
3. Check web server error logs for details

### PHP Errors

**Error:** `PHP Fatal error: Uncaught Error`

**Solution:**
1. Ensure PHP 8.1 or higher is installed
2. Check PHP error logs: `tail -f /var/log/php_errors.log`
3. Verify all required PHP extensions are installed:
   - mysqli
   - json
   - mbstring

## Configuration Options

### Application Settings

Edit `backend/config/config.php` to customize:

- **APP_NAME**: Application name
- **MAX_FILE_SIZE**: Maximum attachment size (default: 10MB)
- **DEFAULT_PAGE_SIZE**: Number of emails per page (default: 50)
- **RATE_LIMIT_ENABLED**: Enable/disable rate limiting

### Frontend API URL

If your API is hosted on a different domain, update the API base URL in:

**File:** `frontend/assets/js/api.js`

```javascript
class API {
    constructor() {
        this.baseURL = '../backend/api';  // Update this path if needed
    }
}
```

## Features

✅ **Email Management**
- View, read, and organize emails
- Star important emails
- Mark emails as read/unread
- Move emails to trash/spam
- Search emails by subject, sender, or content

✅ **Compose & Send**
- Compose new emails
- Rich text content
- Email categories (Primary, Social, Promotions, Updates)

✅ **Responsive Design**
- Works on desktop, tablet, and mobile devices
- Touch-friendly interface
- Mobile gestures support

✅ **RESTful API**
- Complete CRUD operations
- Prepared statements for SQL injection protection
- Rate limiting
- Error handling and logging

## Development

### File Structure

```
surat/
├── frontend/                   # Frontend files
│   ├── assets/
│   │   ├── css/               # Stylesheets
│   │   └── js/                # JavaScript files
│   └── index.html             # Main HTML file
├── backend/                    # Backend files
│   ├── api/
│   │   └── emails.php         # Email API endpoints
│   ├── config/
│   │   ├── database.php       # Database configuration
│   │   └── config.php         # General configuration
│   ├── function/
│   │   └── email_function.php # Email functions
│   ├── database/
│   │   └── migrations/        # Database migrations
│   └── logs/                  # Application logs
├── test.php                   # Database connection test
└── README.md                  # Project documentation
```

### Making Changes

1. **Backend Changes**: Edit PHP files in `backend/` directory
2. **Frontend Changes**: Edit HTML/CSS/JS files in `frontend/` directory
3. **Database Changes**: Create new migration files in `backend/database/migrations/`

### Debugging

Enable debug mode in `backend/config/config.php`:

```php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

View application logs:
```bash
tail -f backend/logs/app.log
```

## Security Notes

⚠️ **Before deploying to production:**

1. Change the JWT secret in `backend/config/config.php`
2. Disable error display: `ini_set('display_errors', 0);`
3. Use strong database passwords
4. Enable HTTPS
5. Implement user authentication
6. Review and update CORS settings
7. Set up regular database backups

## Support

For issues and questions:
- Check the [README.md](README.md) file
- Review this setup guide
- Check application logs: `backend/logs/app.log`
- Open an issue on GitHub

## License

This project is open source and available under the MIT License.

---

**Built with ❤️ using HTML, CSS, JavaScript, and PHP Native**
