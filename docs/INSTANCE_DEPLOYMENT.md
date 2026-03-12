# AssetFlow Instance Deployment Guide

This guide is for deploying a separate single-company instance (one company per deployment).

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

## 2) Configure `.env`
You can configure manually or use the helper script:
```bash
cp .env.example .env
scripts/configure-instance.sh \
  --company "Acme Corp" \
  --app-url "https://assetflow.acme.local" \
  --db-database "assetflow_acme"
```

At minimum verify:
- `COMPANY_NAME`
- `APP_URL`
- `DB_*`
- `ASSETFLOW_BRAND_COLOR`

## 3) Install and Initialize
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

Then open `{APP_URL}/setup` and complete first-run setup from the browser:
- Initialize database (if prompted)
- Create first admin user
- Save company name for this instance

After first login, open `Administration > Portal Settings > Branding` to upload logo and adjust branding.

## 4) Web Server
Point virtual host/server block to the instance `public` directory:
- Apache/Nginx document root: `/var/www/assetflow-acme/public`

## 5) Queue Worker (systemd example)
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

## 6) Scheduler
```bash
* * * * * cd /var/www/assetflow-acme && php artisan schedule:run >> /var/www/assetflow-acme/storage/logs/scheduler.log 2>&1
```

## 7) Post-Deploy Validation
```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan queue:failed
```

## 8) Optional Demo Data
```bash
php artisan assetflow:seed-demo --force
```
