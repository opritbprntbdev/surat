# Deployment Checklist

## Pre-Deployment

- [ ] Review all code changes
- [ ] Test database connection
- [ ] Verify all API endpoints work correctly
- [ ] Test frontend functionality
- [ ] Check responsive design on mobile devices
- [ ] Review security configurations

## Database Setup

- [ ] Create MySQL database: `gmail_clone`
- [ ] Import migration file: `backend/database/migrations/001_create_emails_table.sql`
- [ ] Verify database tables are created:
  - `emails`
  - `email_attachments`
- [ ] Verify sample data is inserted (3 emails)
- [ ] Update database credentials in `backend/config/database.php`

## Web Server Configuration

### Apache (XAMPP/WAMP)
- [ ] Copy project to web server directory
- [ ] Enable `mod_rewrite` module
- [ ] Verify `.htaccess` is being processed
- [ ] Test access to frontend: `http://localhost/surat/surat/frontend/`

### Nginx
- [ ] Configure site in Nginx
- [ ] Set up PHP-FPM
- [ ] Test configuration: `nginx -t`
- [ ] Reload Nginx: `systemctl reload nginx`

## File Permissions (Linux/Mac)

```bash
chmod -R 755 frontend/
chmod -R 755 backend/
chmod -R 777 backend/logs/
```

## Testing

- [ ] Test database connection: Access `test.php`
- [ ] Test API endpoints:
  - GET `/backend/api/emails.php?category=inbox`
  - POST `/backend/api/emails.php` (create email)
  - GET `/backend/api/emails.php?action=single&id=1`
- [ ] Test frontend:
  - Load email list
  - Click on email to view details
  - Test compose email modal
  - Test search functionality
  - Test sidebar navigation
  - Test mobile responsive design

## Security Hardening

- [ ] Change JWT secret in `backend/config/config.php`
- [ ] Disable error display in production: `ini_set('display_errors', 0);`
- [ ] Use strong database passwords
- [ ] Enable HTTPS
- [ ] Review CORS settings
- [ ] Set up database backups
- [ ] Configure rate limiting if needed
- [ ] Review file upload security settings

## Post-Deployment

- [ ] Monitor application logs: `backend/logs/app.log`
- [ ] Check for PHP errors in server error logs
- [ ] Test from different browsers
- [ ] Test from different devices (mobile, tablet, desktop)
- [ ] Verify email functionality works as expected
- [ ] Monitor database performance
- [ ] Set up monitoring and alerting

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check database credentials
- Test connection with `test.php`
- Review PHP MySQLi extension is enabled

### CORS Errors
- Verify accessing through web server (not file://)
- Check CORS headers in `backend/config/config.php`
- Verify Apache/Nginx configuration

### 404 Errors
- Check mod_rewrite is enabled (Apache)
- Verify correct URL path
- Check web server error logs

## Rollback Plan

If deployment fails:
1. Restore previous database backup
2. Revert code changes: `git revert HEAD`
3. Clear cache and logs
4. Test restoration
5. Document issues for future reference

## Documentation

- [ ] Update README.md if needed
- [ ] Document any custom configurations
- [ ] Update SETUP.md with deployment notes
- [ ] Create backup of working configuration

---

**Last Updated:** 2025-10-24
