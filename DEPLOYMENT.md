# EPS-TOPIK Korean Trainer - Webhosting Deployment Guide

This guide provides step-by-step instructions for deploying the **EPS-TOPIK Korean Trainer** web application to any standard webhosting service, including **cPanel**, **Hostinger**, **Namecheap**, **Plesk**, or a Linux **VPS** (Ubuntu/Debian with Apache or Nginx).

---

## 📋 Pre-Deployment Checklist

Before deploying, ensure your hosting provider meets the following requirements:

- **PHP Version:** PHP 7.4, 8.0, 8.1, 8.2, or 8.3+
- **PHP Extensions:** `pdo`, `pdo_mysql`, `mbstring` (for Korean characters), `curl`, `json`, `gd`
- **Database:** MySQL 5.7+ or MariaDB 10.2+ (with `utf8mb4` support)
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx
- **Storage:** Minimum 500MB free space (for uploads and audio caching)

---

## 🚀 Option A: Deploying on Shared Hosting (cPanel / Hostinger / Namecheap / Plesk)

### Step 1: Upload Project Files
1. Compress your project folder into a `.zip` file on your local computer.
2. Log in to your hosting control panel (cPanel, Hostinger hPanel, Plesk, etc.).
3. Open **File Manager** and navigate to your target root folder (e.g. `public_html` or `public_html/eps-topik`).
4. Upload the `.zip` file and **Extract** it.

---

### Step 2: Create MySQL Database & Import Data
1. In cPanel/hPanel, go to **MySQL Databases**.
2. Create a new database (e.g., `username_epstopik`).
3. Create a new database user and assign a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.
5. Open **phpMyAdmin**.
6. Select your newly created database.
7. Click **Import** tab at the top.
8. Select `database/eps_topik_full.sql` (or `database/schema.sql`) from your uploaded files.
9. Ensure character set is set to **utf8mb4** and click **Go**.

---

### Step 3: Configure Environment (.env)
1. In File Manager, find `.env.example` in the root folder.
2. Rename or copy `.env.example` to `.env`.
3. Edit `.env` and fill in your database details and domain:

```env
APP_ENV=production
APP_NAME="EPS Korean Trainer"
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=username_epstopik
DB_USER=username_epsuser
DB_PASS=YourStrongPasswordHere
DB_CHARSET=utf8mb4

TIMEZONE=Asia/Manila
```

> **Note on APP_URL:** If set to `auto`, the application automatically detects your HTTPS domain or subdirectory URL.

---

### Step 4: Set Directory Permissions
Ensure write permissions for the uploads directory so user profile pictures and TTS audio files can be saved:

- `uploads/` -> **`755`** or **`775`**
- `uploads/audio/` -> **`755`** or **`775`**
- `uploads/profiles/` -> **`755`** or **`775`**
- `uploads/flashcards/` -> **`755`** or **`775`**
- `uploads/audio/tts/` -> **`755`** or **`775`**

---

### Step 5: Verify Deployment
1. Open your web browser and visit `https://yourdomain.com/health.php`.
2. Check that all diagnostics show **`[OK]`**.
3. Log in with default credentials:
   - **Admin Account:** `admin@epstopik.com` / Password: `admin123`
   - **Learner Account:** `learner@epstopik.com` / Password: `learner123`
4. **Security Note:** Delete or restrict access to `health.php` after verifying setup, and change default admin passwords immediately in the admin dashboard!

---

## 🐧 Option B: Deploying on a Linux VPS (Ubuntu / Nginx / Apache)

### Step 1: Install PHP 8.x and MySQL
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-curl php8.2-gd php8.2-xml unzip git
```

### Step 2: Set Up Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE eps_topik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'epsuser'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON eps_topik.* TO 'epsuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Import database SQL:
```bash
mysql -u epsuser -p eps_topik < /var/www/eps-topik/database/eps_topik_full.sql
```

### Step 3: Configure VirtualHost (Apache Example)
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/eps-topik

    <Directory /var/www/eps-topik>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/eps_error.log
    CustomLog ${APACHE_LOG_DIR}/eps_access.log combined
</VirtualHost>
```
Enable rewrite and restart Apache:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 4: Free SSL Certificate (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

---

## 🔐 Default Credentials & Administration

| Role | Email | Default Password | Access URL |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@epstopik.com` | `admin123` | `/admin/dashboard.php` |
| **Learner** | `learner@epstopik.com` | `learner123` | `/dashboard.php` |

---

## 🛠️ Troubleshooting & Support

- **Database Connection Error:** Verify `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `.env`.
- **404 Page Not Found on Sub-pages:** Ensure Apache `mod_rewrite` is enabled and `.htaccess` file was uploaded (hidden files starting with a dot `.htaccess` may require toggling "Show Hidden Files" in cPanel).
- **TTS Audio Not Playing:** Browsers natively synthesize Korean text. If using Google Cloud or OpenAI TTS, add your API key into `.env` or set it in `/admin/voice-settings.php`.
