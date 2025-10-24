# Gmail Clone - BPR Surat

A complete Gmail clone built with vanilla HTML, CSS, JavaScript, and PHP Native (PHP 8.1+ with MySQLi).

## 🚀 Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/opritbprntbdev/surat.git
cd surat

# 2. Set up the database
mysql -u root -p < surat/backend/database/migrations/001_create_emails_table.sql

# 3. Configure database connection
# Edit: surat/backend/config/database.php

# 4. Access the application
# http://localhost/surat/surat/frontend/index.html
```

## 📚 Documentation

- **[SETUP.md](SETUP.md)** - Complete installation and setup guide
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment checklist and procedures
- **[CHANGES.md](CHANGES.md)** - Detailed changelog and fixes
- **[surat/README.md](surat/README.md)** - Project features and API documentation

## ✨ Features

- 📧 Full email management (view, read, organize)
- ⭐ Star important emails
- 🔍 Real-time email search
- 📱 Responsive design (mobile, tablet, desktop)
- 🎨 Clean Gmail-like interface
- 🔒 RESTful API with security features
- 📊 Email categories and labels

## 🛠️ Technology Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** PHP 8.1+ (Native)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **API:** RESTful with MySQLi prepared statements

## 📦 Project Structure

```
surat/
├── surat/               # Main application directory
│   ├── frontend/        # HTML, CSS, JavaScript
│   ├── backend/         # PHP API and configuration
│   │   ├── api/         # API endpoints
│   │   ├── config/      # Configuration files
│   │   ├── function/    # Business logic
│   │   └── database/    # Migrations
│   └── README.md        # Detailed project documentation
├── SETUP.md             # Installation guide
├── DEPLOYMENT.md        # Deployment checklist
└── CHANGES.md           # Changelog
```

## ⚡ Recent Updates (v1.0.1)

- ✅ Fixed database schema mismatch in attachment handling
- ✅ Improved GET request parameter handling
- ✅ Added comprehensive documentation
- ✅ Security scan passed (0 vulnerabilities)
- ✅ Added .gitignore for better version control

## 🔧 Requirements

- PHP 8.1 or higher with MySQLi extension
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache with mod_rewrite or Nginx)
- Modern web browser

## 🧪 Testing

Test database connection:
```bash
# Access: http://localhost/surat/surat/test.php
```

Expected output:
```json
{
  "success": true,
  "message": "Database connection successful!",
  "total_emails": 3
}
```

## 🔒 Security

- ✅ SQL injection protection (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ CORS headers configured
- ✅ Rate limiting implemented
- ⚠️ **Note:** Add authentication before production use

## 📝 License

This project is open source and available under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

- 📖 Check [SETUP.md](SETUP.md) for installation help
- 🚀 Review [DEPLOYMENT.md](DEPLOYMENT.md) for deployment issues
- 📋 See [CHANGES.md](CHANGES.md) for recent updates
- 🐛 Open an issue on GitHub for bugs

---

**Built with ❤️ using HTML, CSS, JavaScript, and PHP Native**

