# AssetFlow Requirements

## Server Requirements
- Linux server (recommended Ubuntu LTS)
- PHP 8.2 or 8.3
- MariaDB 10.4+ or MySQL 8+
- Apache or Nginx
- Composer 2.x

## Required PHP Extensions
- `ctype`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `tokenizer`
- `xml`
- `zip`
- `gd`

## Runtime Services
- Web server (Apache/Nginx)
- Database server (MariaDB/MySQL)
- Queue worker (`php artisan queue:work`)
- Scheduler cron (`php artisan schedule:run` every minute)

## Filesystem & Permissions
Writable by web server user:
- `storage/`
- `bootstrap/cache/`

Public branding logo path:
- `storage/app/public/branding/`

Required once per instance:
```bash
php artisan storage:link
```

## Network & DNS
- DNS record pointing customer hostname to server IP
- Open ports: `80` and `443` (and restricted `22` for SSH)
- TLS certificate for customer hostname

## Environment Variables (Minimum)
- `APP_URL`
- `APP_ENV`
- `APP_DEBUG`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=database`
