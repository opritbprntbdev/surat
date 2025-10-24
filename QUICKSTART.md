# Quick Start Guide

## For Developers

### Installation (5 minutes)

1. **Database Setup:**
   ```bash
   mysql -u root -p < surat/backend/database/migrations/001_create_emails_table.sql
   ```

2. **Configure Database:**
   Edit `surat/backend/config/database.php`:
   ```php
   private const HOST = 'localhost';
   private const USER = 'root';
   private const PASS = 'your_password';
   private const NAME = 'gmail_clone';
   ```

3. **Start Web Server:**
   - XAMPP: Copy to `C:\xampp\htdocs\surat\`
   - WAMP: Copy to `C:\wamp64\www\surat\`
   - Or use built-in PHP server: `php -S localhost:8000 -t surat/frontend/`

4. **Access Application:**
   ```
   http://localhost/surat/surat/frontend/index.html
   ```

### Verify Installation

Test database connection:
```
http://localhost/surat/surat/test.php
```

Expected response:
```json
{
  "success": true,
  "message": "Database connection successful!",
  "total_emails": 3
}
```

## For End Users

### What is This?

A Gmail clone application that allows you to:
- View and manage emails
- Search emails
- Organize emails with labels and categories
- Star important messages
- Compose new emails
- Works on mobile and desktop

### How to Use

1. **View Emails:** Click on any email in the list to read it
2. **Search:** Use the search bar at the top to find emails
3. **Compose:** Click the "Compose" button to write a new email
4. **Star:** Click the star icon to mark important emails
5. **Categories:** Use the sidebar to filter by category (Inbox, Starred, Sent, etc.)

### Mobile Features

- Swipe from left edge to open menu
- Tap email to view details
- Use hamburger menu (☰) to access navigation

## Troubleshooting

### Database Connection Error

1. Ensure MySQL is running
2. Check credentials in `surat/backend/config/database.php`
3. Verify database exists: `SHOW DATABASES;`
4. Test connection with `test.php`

### Page Not Loading

1. Check web server is running
2. Verify correct URL path
3. Check browser console for errors (F12)
4. Review web server error logs

### API Errors

1. Check `surat/backend/logs/app.log`
2. Verify PHP 8.1+ is installed: `php -v`
3. Check MySQLi extension: `php -m | grep mysqli`

## Project Structure

```
surat/
├── README.md           ← Project overview
├── SETUP.md            ← Detailed installation guide
├── DEPLOYMENT.md       ← Production deployment
├── CHANGES.md          ← What was fixed
├── QUICKSTART.md       ← This file
└── surat/              ← Application code
    ├── frontend/       ← HTML/CSS/JS
    ├── backend/        ← PHP API
    └── README.md       ← Technical docs
```

## Need More Help?

- 📖 **Installation Issues:** See [SETUP.md](SETUP.md)
- 🚀 **Deployment:** See [DEPLOYMENT.md](DEPLOYMENT.md)
- 📋 **What Changed:** See [CHANGES.md](CHANGES.md)
- 📚 **Full Docs:** See [surat/README.md](surat/README.md)

## Support

- Open an issue on GitHub
- Check application logs: `surat/backend/logs/app.log`
- Review error messages in browser console (F12)

---

**Getting Started:** Follow the installation steps above  
**Questions?** Check the documentation files  
**Found a bug?** Open an issue on GitHub
