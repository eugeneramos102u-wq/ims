# ISMS — Setup Guide

Step-by-step instructions for cloning this project to a new PC and getting it running.

---

## Prerequisites checklist

Install these on the new PC (skip any you already have):

- [ ] **XAMPP** (or Laragon) — provides PHP 8.2+, MySQL/MariaDB, Apache. Download: https://www.apachefriends.org/
- [ ] **Composer** — PHP package manager. Download: https://getcomposer.org/
- [ ] **Git** — version control. Download: https://git-scm.com/

### Verify installs

Open PowerShell or Git Bash and run:

```powershell
C:\xampp\php\php.exe --version       # expect: PHP 8.2.x
composer --version                   # expect: Composer 2.x
C:\xampp\mysql\bin\mysql.exe --version  # expect: MySQL or MariaDB
git --version                        # expect: git version 2.x
```

If any of these fail, install the missing tool and retry.

---

## Setup steps

### 1. Start XAMPP services

Open the **XAMPP Control Panel** and click **Start** next to:
- [ ] **Apache**
- [ ] **MySQL**

Both indicators should turn green.

### 2. Clone the repo

```powershell
cd C:\xampp\htdocs
git clone https://github.com/YOURNAME/isms.git
cd isms
```

> Replace `YOURNAME` with your actual GitHub username.

### 3. Install PHP dependencies

```powershell
composer install
```

This downloads the `vendor/` folder (~50MB). Takes 1-3 minutes the first time.

### 4. Create the `.env` file

```powershell
copy .env.example .env
```

Open `.env` in a text editor and verify the database settings. The defaults work for stock XAMPP (root user, no password):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=isms
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL has a different root password, update `DB_PASSWORD`.

### 5. Generate the application key

```powershell
C:\xampp\php\php.exe artisan key:generate
```

This fills in `APP_KEY=` in `.env`.

### 6. Create the database

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS isms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If you set a MySQL password earlier, add `-p` and enter it when prompted.

### 7. Run migrations + seed default data

```powershell
C:\xampp\php\php.exe artisan migrate --seed
```

This creates all tables and seeds:
- 4 roles (Admin, Manager, Cashier, Viewer)
- 3 default user accounts (see below)
- Master data: UoM, item categories, payment terms, banks, suppliers, customers, salespersons, adjustment types, document sequences

### 8. (Optional) Configure VS Code settings

The repo's `.vscode/settings.json` is gitignored. If you use the **Laravel Extra Intellisense** extension, create `.vscode/settings.json` with:

```json
{
  "LaravelExtraIntellisense.phpCommand": "C:\\xampp\\php\\php.exe -r \"{code}\""
}
```

This points the extension at XAMPP's PHP.

### 9. Verify it works

You have two options to access the app:

**Option A — via Apache (XAMPP):**
- Open http://localhost/isms/public in your browser
- This works as long as the project lives under `C:\xampp\htdocs`

**Option B — via Laravel's built-in server:**
```powershell
C:\xampp\php\php.exe artisan serve --port=8001
```
- Open http://127.0.0.1:8001 in your browser
- Faster for development; stop with `Ctrl+C`

---

## Run it always-on (no `artisan serve`)

For day-to-day use you do **not** want to run `php artisan serve` every time you boot the PC. Use Apache (XAMPP) and have Windows start it automatically.

### 1. Auto-start XAMPP services on Windows boot

One-time setup:

- [ ] Right-click the **XAMPP Control Panel** shortcut → **Run as administrator** (required)
- [ ] In the **Service** column on the left, click the red **✕** next to **Apache** → it turns into a green **✓** (Apache is now a Windows service)
- [ ] Do the same for **MySQL** → green **✓**
- [ ] Close the Control Panel

After the next reboot, Apache and MySQL start automatically — no Control Panel, no terminal, no `artisan serve`.

### 2. Verify after reboot

Restart the PC. **Don't** open XAMPP. Just open the browser and go to:

```
http://localhost/isms/public
```

If the login page loads, you're done. ✅

### 3. (Optional) Clean URL — drop the `/public`

If you'd rather access the app at `http://localhost/isms` (without `/public` at the end), create `C:\xampp\htdocs\isms\.htaccess` (next to `composer.json`, **not** inside the `public/` folder) with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

This file is machine-specific — fine to leave uncommitted (just don't `git add` it).

### 4. (Optional) LAN access from other PCs

Per the original blueprint, ISMS is meant to run on one office PC and be accessed by all workstations. To enable LAN access:

- [ ] Find this PC's local IP. Open PowerShell and run `ipconfig` — look for the **IPv4 Address** under your active adapter (e.g. `192.168.1.10`)
- [ ] On any other PC on the same WiFi/LAN, open `http://192.168.1.10/isms/public`
- [ ] If blocked, allow Apache through the Windows Firewall:
  - Search "**Windows Defender Firewall**" → **Allow an app** → **Change settings** → **Allow another app**
  - Browse to `C:\xampp\apache\bin\httpd.exe` → check both **Private** and **Public**
- [ ] (Optional) Reserve a static IP for this PC in your router so the address never changes

### 5. (Optional) Desktop shortcut

Right-click the desktop → **New → Shortcut** → paste `http://localhost/isms/public` → Name it "ISMS". One-click access from now on.

---

## Default seed accounts

| Username | Password | Role |
|---|---|---|
| `admin` | `admin123` | Admin (full access) |
| `manager` | `manager123` | Manager (confirm/post/issue, all reports) |
| `cashier` | `cashier123` | Cashier (drafts only, can record collections) |

---

## Pulling future updates

When the GitHub repo gets new changes, on the new PC run:

```powershell
cd C:\xampp\htdocs\isms
git pull
composer install                    # in case dependencies changed
C:\xampp\php\php.exe artisan migrate # apply any new migrations
C:\xampp\php\php.exe artisan view:clear
```

If `db:seed` adds new default data (e.g. new master records), the existing data stays untouched (`firstOrCreate` is idempotent), so it's safe to run:

```powershell
C:\xampp\php\php.exe artisan db:seed
```

To re-seed only the user accounts:

```powershell
C:\xampp\php\php.exe artisan db:seed --class=UserSeeder
```

---

## Common issues

### 419 Page Expired right after login

Your browser has a stale session cookie. **Hard-refresh the login page** (`Ctrl+Shift+R`), then re-enter your credentials. If it persists, open the page in an Incognito / Private window.

### Login page never loads or returns "Connection refused"

XAMPP isn't running. Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### "SQLSTATE[HY000]: 1273 Unknown collation 'utf8mb4_0900_ai_ci'"

This shouldn't happen since we already set `utf8mb4_unicode_ci` in `config/database.php`, but if you see it: ensure `config/database.php` line ~52 reads:

```php
'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
```

(MariaDB doesn't support the MySQL-8-only `utf8mb4_0900_ai_ci`.)

### "View [layouts.app] not found"

Run `php artisan view:clear` and reload.

### "php is not recognized"

Use the full path `C:\xampp\php\php.exe` instead of just `php`. Or add `C:\xampp\php` to your system PATH (Windows Settings → System → About → Advanced → Environment Variables → Path).

---

## Tech stack reference

- **PHP** 8.2 (XAMPP)
- **Laravel** 11.x
- **MySQL / MariaDB** (XAMPP default is MariaDB 10.4)
- **Tailwind CSS 3** (CDN, no build step)
- **Alpine.js 3** (CDN)
- **Chart.js 4** (CDN)

No `npm install` needed — all frontend libraries are loaded via CDN.

---

## Quick smoke test after setup

1. Open the app, log in as `admin / admin123`
2. Sidebar → **Dashboard** — should load with KPI cards and 30-day sales chart
3. Sidebar → **Items** — should show 8 sample items (Cement, Steel Bar, etc.)
4. Sidebar → **Customers** — should show 4 sample customers (SM Prime, Robinsons, etc.)
5. Sidebar → **Sales Orders** → **+ New SO** — try creating an SO; it should validate and save

If all five work, the install is complete. ✓
