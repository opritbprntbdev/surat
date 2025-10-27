# Gmail Clone - HTML/CSS/JavaScript + PHP Native
A complete Gmail clone built with vanilla HTML, CSS, JavaScript and PHP Native (PHP 8.1+ with MySQLi).

## 🚀 Features

### Frontend Features
- ✅ Responsive Design - Works perfectly on desktop and mobile
- ✅ Touch-Friendly Interface - Mobile gestures and touch targets
- ✅ Real-time Search - Instant email search
- ✅ Email Categories - Primary, Social, Promotions, Updates
- ✅ Star & Label System - Organize your emails
- ✅ Compose Email - Rich text email composition
- ✅ Mobile Navigation - Swipe gestures and mobile menu
- ✅ Offline Support - Local storage for caching
- ✅ Keyboard Shortcuts - Productivity shortcuts

### Backend Features
- ✅ RESTful API - Complete CRUD operations
- ✅ PHP 8.1+ Ready - Modern PHP features
- ✅ MySQLi Native - Secure database operations
- ✅ Prepared Statements - SQL injection protection
- ✅ File Upload - Attachment handling
- ✅ Rate Limiting - API protection
- ✅ Error Handling - Comprehensive error management
- ✅ Logging - Application logging

## 📁 Project Structure

```
gmail-clone/
├── frontend/                    # Frontend files
│   ├── assets/
│   │   ├── css/                # Stylesheets
│   │   │   ├── main.css        # Main styles
│   │   │   ├── components.css  # Component styles
│   │   │   └── responsive.css  # Responsive styles
│   │   ├── js/                 # JavaScript files
│   │   │   ├── main.js         # Main application
│   │   │   ├── components.js   # UI components
│   │   │   ├── api.js          # API utilities
│   │   │   └── utils.js        # Utility functions
│   │   └── images/             # Images and icons
│   ├── components/             # HTML templates
│   └── index.html              # Main HTML file
│
├── backend/                     # Backend files
│   ├── config/
│   │   ├── database.php        # Database configuration
│   │   └── config.php          # General configuration
│   ├── functions/
│   │   └── email_functions.php # Email functions
│   ├── api/
│   │   └── emails.php          # Email API endpoints
│   ├── models/                 # Data models (optional)
│   └── database/
│       └── migrations/         # Database migrations
│           └── 001_create_emails_table.sql
│
└── README.md                    # This file
```

## 🛠️ Installation

### Prerequisites
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Step 1: Database Setup
1.  Create a new database:
    ```sql
    CREATE DATABASE gmail_clone;
    ```
2.  Import the database schema:
    ```bash
    mysql -u username -p gmail_clone < backend/database/migrations/001_create_emails_table.sql
    ```
3.  Update database configuration in `backend/config/database.php`:
    ```php
    private const HOST = 'localhost';
    private const USER = 'your_username';
    private const PASS = 'your_password';
    private const NAME = 'gmail_clone';
    ```

### Step 2: Web Server Configuration

**Apache**

Add this to your `.htaccess` file:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.html [QSA,L]
```

**Nginx**

Add this to your server configuration:
```nginx
location / {
    try_files $uri $uri/ /index.html;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### Step 3: File Permissions
Set proper permissions:
```bash
chmod -R 755 frontend/
chmod -R 755 backend/
```

### Step 4: Access the Application
Open your browser and navigate to:
```
http://localhost/gmail-clone/frontend/
```

## 📱 Mobile Features

### Touch Gestures
- **Swipe Right** (from left edge) - Open sidebar
- **Swipe Left** - Close sidebar
- **Tap** - Select email
- **Long Press** - Show context menu (future feature)

### Responsive Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 1023px
- **Desktop**: ≥ 1024px

## 🔧 Configuration

### Frontend Configuration
Edit `frontend/assets/js/api.js` to change the API base URL:
```javascript
baseURL: '../backend/api'  // Relative path
```

### Backend Configuration
Edit `backend/config/config.php` for application settings:
```php
// Application settings
public const APP_NAME = 'Gmail Clone';
public const APP_URL = 'http://localhost';

// Security settings
public const JWT_SECRET = 'your-secret-key';
public const JWT_EXPIRY = 3600;

// File upload settings
public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
```

## 🎯 API Endpoints

### Emails API

| Method | Endpoint                             | Description      |
|--------|--------------------------------------|------------------|
| GET    | `/api/emails.php`                    | Get emails list  |
| GET    | `/api/emails.php?action=single&id={id}`| Get single email |
| POST   | `/api/emails.php?action=create`      | Create new email |
| PUT    | `/api/emails.php?id={id}`            | Update email     |
| DELETE | `/api/emails.php?id={id}`            | Delete email     |
| POST   | `/api/emails.php?action=search`      | Search emails    |
| POST   | `/api/emails.php?action=mark_read`   | Mark as read     |
| POST   | `/api/emails.php?action=toggle_star` | Toggle star      |
| POST   | `/api/emails.php?action=move`        | Move to folder   |

### Example API Requests

**Get Emails**
```javascript
fetch('../backend/api/emails.php?category=inbox')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Create Email**
```javascript
fetch('../backend/api/emails.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'create',
    to_email: 'recipient@example.com',
    subject: 'Test Email',
    content: 'This is a test email.'
  })
});
```

## 🎨 Customization

### Colors
Edit the CSS variables in `frontend/assets/css/main.css`:
```css
:root {
  --primary-color: #1a73e8;
  --secondary-color: #5f6368;
  --background-color: #ffffff;
  --text-color: #202124;
}
```

### Fonts
Change fonts in `frontend/assets/css/main.css`:
```css
body {
  font-family: 'Your Font', sans-serif;
}
```

### Logo
Replace the favicon in the HTML `<head>` section.

## 🔒 Security Features
- **SQL Injection Protection** - Prepared statements
- **XSS Protection** - Input sanitization
- **CSRF Protection** - Token validation (future)
- **Rate Limiting** - API request limits
- **Input Validation** - Server-side validation
- **File Upload Security** - Type and size validation

## 🚀 Performance

### Frontend Optimization
- **Lazy Loading** - Images and components
- **Debouncing** - Search and resize events
- **Throttling** - Scroll events
- **Local Storage** - Client-side caching
- **Minification** - CSS and JS files (production)

### Backend Optimization
- **Database Indexes** - Optimized queries
- **Prepared Statements** - Query caching
- **Connection Pooling** - Database connections
- **Output Buffering** - Faster responses

## 🧪 Testing

### Manual Testing
- Open the application in different browsers
- Test responsive design on mobile devices
- Test all API endpoints
- Verify email functionality

### Automated Testing (Future)
- Unit tests with PHPUnit
- Integration tests
- End-to-end tests with Playwright

## 📝 Development

### Adding New Features
- **Frontend**: Add components to `frontend/assets/js/components.js`
- **Backend**: Add functions to `backend/functions/email_functions.php`
- **API**: Add endpoints to `backend/api/emails.php`
- **Styles**: Add styles to `frontend/assets/css/components.css`

### Code Style
- **JavaScript**: ES6+ standards
- **PHP**: PSR-12 coding standards
- **CSS**: BEM methodology
- **HTML**: Semantic HTML5

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
```
Error: Database connection failed
```
**Solution**: Check database credentials in `backend/config/database.php`

**CORS Error**
```
Access to fetch at '...' blocked by CORS policy
```
**Solution**: Check CORS headers in `backend/config/config.php`

**Mobile Menu Not Working**
**Solution**: Check touch event listeners in `frontend/assets/js/main.js`

**API Not Responding**
**Solution**: Check PHP error logs and enable error reporting

### Debug Mode
Enable debug mode in `backend/config/config.php`:
```php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🤝 Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License
This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments
- Inspired by Gmail's clean interface
- Built with modern web technologies
- Optimized for performance and accessibility

## 📞 Support
For support and questions:
- Create an issue in the repository
- Check the troubleshooting section
- Review the API documentation

Built with ❤️ using HTML, CSS, JavaScript, and PHP Native