# Local Development Setup Guide

## The Problem
"ERR_CONNECTION_REFUSED" means there's no web server running on localhost. You need to start a local web server to run WordPress.

## Option 1: XAMPP (Recommended for Windows)

XAMPP is an easy-to-install Apache distribution containing MySQL, PHP, and Perl.

### Steps:
1. **Download XAMPP**: https://www.apachefriends.org/download.html
2. **Install XAMPP** (usually to `C:\xampp`)
3. **Start XAMPP Control Panel**
4. **Start Apache** and **MySQL** services
5. **Copy your WordPress files** to `C:\xampp\htdocs\slippy-wp\`
6. **Access your site**: http://localhost/slippy-wp/

### Database Setup:
- Use phpMyAdmin (http://localhost/phpmyadmin) to import/create your database
- Update `wp-config.php` with the correct database credentials

---

## Option 2: WAMP (Windows Apache MySQL PHP)

Similar to XAMPP but Windows-specific.

1. **Download WAMP**: https://www.wampserver.com/
2. **Install and start WAMP**
3. **Place files** in `C:\wamp64\www\slippy-wp\`
4. **Access**: http://localhost/slippy-wp/

---

## Option 3: PHP Built-in Server (If PHP is installed)

If you have PHP installed but not XAMPP/WAMP:

```powershell
cd C:\Users\kania\Dropbox\Linked\apps\slippy-wp
php -S localhost:8000
```

Then access: http://localhost:8000/

**Note**: You'll also need MySQL running separately for this option.

---

## Option 4: Laragon (Modern Alternative)

Laragon is a modern, fast development environment for Windows.

1. **Download Laragon**: https://laragon.org/download/
2. **Install and start Laragon**
3. **Place files** in `C:\laragon\www\slippy-wp\`
4. **Access**: http://slippy-wp.test/ (or http://localhost/slippy-wp/)

---

## Quick Check: Do you have a web server installed?

Check if you have any of these installed:
- XAMPP Control Panel
- WAMP icon in system tray
- Laragon
- IIS (Windows built-in web server)

If none are installed, **Option 1 (XAMPP)** is the easiest to get started.

---

## After Starting Your Web Server

1. Make sure **Apache** and **MySQL** are running
2. Verify your database exists and has the correct credentials
3. Access: http://localhost/slippy-wp/wp-admin/
4. If you see errors, check:
   - Database connection in `wp-config.php`
   - File permissions
   - PHP error logs

---

## Troubleshooting

### Port Already in Use
If port 80 is in use, you can:
- Change Apache port in XAMPP/WAMP settings
- Use a different port: http://localhost:8080/slippy-wp/

### Database Connection Error
- Make sure MySQL is running
- Verify database name, username, and password in `wp-config.php`
- Check if database exists in phpMyAdmin

### Still Can't Connect
- Check Windows Firewall isn't blocking Apache
- Try accessing http://127.0.0.1/slippy-wp/ instead
- Check Apache error logs in XAMPP/WAMP

