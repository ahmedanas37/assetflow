# AssetFlow Start Here

Use this page if you are not sure which guide to follow.

## Choose Your Path

### 1) I just want to run AssetFlow on my own computer
This is the best choice for most first-time users on Windows or Linux.

Run these commands inside the cloned project folder:
```bash
composer install
composer run setup:local
php artisan serve
```

Then open:
```text
http://127.0.0.1:8000/setup
```

Complete the form in your browser to create the first admin account.

Important notes:
- You do not need Node.js just to start the app.
- You do not need Apache or Nginx just to test the app locally.
- The default local setup uses SQLite automatically, so you do not need to create a MySQL database first.

If you want MySQL locally instead of SQLite:
```bash
php scripts/bootstrap-local.php --driver=mysql --db-database=assetflow --db-username=root
php artisan serve
```

### 2) I want to deploy AssetFlow on a Linux server
Use these guides:
- `docs/QUICK_DEPLOY.md` for the fastest Linux server setup
- `docs/INSTANCE_DEPLOYMENT.md` for a fuller per-company deployment guide
- `docs/REQUIREMENTS.md` for server prerequisites

Important note:
- The `scripts/*.sh` helper scripts are Linux server helpers. They are not the normal path for Windows local setup.

### 3) AssetFlow is already running and I want to use it
Use these guides:
- `docs/USER_GUIDE.md` for daily use
- `docs/ADMIN_GUIDE.md` for setup, roles, imports, and settings

Sign-in page:
```text
{APP_URL}/admin
```

### 4) I cannot log in
See the admin recovery steps in the main `README.md`.

## Common Mistakes
- Do not open `/admin` first on a brand-new install. Open `/setup` first.
- After first-run setup is complete, use `/admin` for future sign-ins.
- Do not use the Linux Bash deploy scripts on Windows unless you are intentionally running a Bash environment.
- If `composer install` fails, check that PHP 8.2+ and the required PHP extensions are installed.
