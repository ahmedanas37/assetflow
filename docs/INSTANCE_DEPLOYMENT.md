# AssetFlow Instance Deployment Guide

This guide is for deploying a separate single-company instance (one company per deployment).

This is an advanced Linux server guide. If you only want to run AssetFlow on your own computer first, use `docs/START_HERE.md` instead.

## Isolation Model
Each company instance should have:
- Dedicated code directory
- Dedicated database
- Dedicated `.env` and `APP_KEY`
- Dedicated queue worker process
- Dedicated scheduler log/output

Do not share database schemas between companies.

## 1) Create Instance Directory
```bash
cp -R /opt/assetflow-template /var/www/assetflow-acme
cd /var/www/assetflow-acme
```

## 2) Fast Bootstrap (Recommended)
Use the one-command bootstrap script:
```bash
scripts/deploy-instance.sh \
  --company "Acme Corp" \
  --app-url "https://assetflow.acme.local" \
  --db-database "assetflow_acme" \
  --db-username "assetflow_user" \
  --prompt-db-password
```

This command will:
- prepare `.env`
- install Composer dependencies
- generate `APP_KEY` (if missing)
- create storage symlink
- run migrations
- cache config/routes

Then open `{APP_URL}/setup` and create first admin user.

## 3) Configure `.env` Manually (Alternative)
If you prefer manual control:
```bash
cp .env.example .env
scripts/configure-instance.sh \
  --company "Acme Corp" \
  --app-url "https://assetflow.acme.local" \
  --db-database "assetflow_acme" \
  --db-username "assetflow_user"
```

At minimum verify:
- `COMPANY_NAME`
- `APP_URL`
- `DB_*`
- `ASSETFLOW_BRAND_COLOR`

## 4) Install and Initialize
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

Then open `{APP_URL}/setup` and complete first-run setup from the browser:
- Initialize database (if prompted)
- Create first admin user
- Save company name for this instance

After first login, open `Administration > Portal Settings` and use the `Branding` section to upload logo and adjust branding.

## 5) Web Server
Point virtual host/server block to the instance `public` directory:
- Apache/Nginx document root: `/var/www/assetflow-acme/public`

## 6) Queue Worker (systemd example)
```ini
[Unit]
Description=AssetFlow Queue Worker (Acme)

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/assetflow-acme/artisan queue:work --tries=3 --timeout=90

[Install]
WantedBy=multi-user.target
```

## 7) Scheduler
```bash
* * * * * cd /var/www/assetflow-acme && php artisan schedule:run >> /var/www/assetflow-acme/storage/logs/scheduler.log 2>&1
```

## 8) Post-Deploy Validation
```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan queue:failed
```

## 9) Optional Demo Data
```bash
php artisan assetflow:seed-demo --force
```
