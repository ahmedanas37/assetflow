# AssetFlow Operations Guide

This guide is for system administrators responsible for deployment and maintenance.

## Requirements
- PHP 8.2 or 8.3
- MariaDB 10.4+ (or MySQL 8+)
- Apache or Nginx
- PHP extensions: ctype, fileinfo, json, mbstring, openssl, pdo_mysql, tokenizer, xml, zip, gd

## Install
```
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

After web server and `.env` are ready, open `{APP_URL}/setup`:
- Click `Initialize Database` if schema is not ready
- Complete first-run setup form to create the first admin account

## Environment Configuration
Key `.env` values:
- `APP_URL` (used by QR codes and labels)
- `DB_*` (database connection)
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=database`
- `COMPANY_NAME`

After changing `.env`:
```
php artisan config:cache
```

## Web Server
### Apache (example)
```
<VirtualHost *:80>
    ServerName assetflow.example.local
    DocumentRoot "/var/www/assetflow-instance/public"
    <Directory "/var/www/assetflow-instance/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx (example)
```
server {
    listen 80;
    server_name assetflow.example.local;
    root /var/www/assetflow-instance/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

## Storage and Permissions
Ensure the web server user can write:
- `storage`
- `bootstrap/cache`

Private attachments are stored in `storage/app/private`.
Branding logos are stored in `storage/app/public/branding`.

Create public storage symlink:
```
php artisan storage:link
```

## Queue Worker
```
php artisan queue:work --tries=3 --timeout=90
```

Linux systemd (example):
```
[Unit]
Description=AssetFlow Queue Worker

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/assetflow-instance/artisan queue:work --tries=3 --timeout=90

[Install]
WantedBy=multi-user.target
```

## Scheduler
```
* * * * * cd /var/www/assetflow-instance && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `assetflow:update-metrics` hourly.

## Backups
Database backup:
```
mysqldump -u root -p assetflow > backups/assetflow.sql
```

Private storage backup:
```
tar -czf backups/assetflow-storage.tar.gz storage/app/private
```

Restore:
```
mysql -u root -p assetflow < backups/assetflow.sql
tar -xzf backups/assetflow-storage.tar.gz -C storage/app
```

## Updates
```
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan permission:cache-reset
php artisan assetflow:update-metrics
php artisan config:cache
php artisan route:cache
```

Restart queue workers after updates.

## Logs
Laravel logs to `storage/logs/laravel.log`.

## Troubleshooting
### Login fails with correct credentials
```
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan assetflow:reset-admin
php artisan permission:cache-reset
```

### Setup page keeps appearing
- Confirm at least one user exists in `users`.
- Confirm `app_settings` has key `system.installed_at`.
- If needed, rerun setup at `/setup` (only available before install).

### Queue not processing
- Confirm `QUEUE_CONNECTION=database`.
- Run `php artisan queue:work`.
- Check failed jobs: `php artisan queue:failed`.

### 419 CSRF or session errors
- Ensure `APP_URL` matches the browser URL.
- Clear caches: `php artisan config:clear` and `php artisan cache:clear`.
