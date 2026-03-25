# AssetFlow

AssetFlow is an on-premise IT asset management system built on Laravel 11 and Filament v3. It provides asset tracking, assignments, maintenance logs, audit trails, CSV import/export, and printable QR labels with no external cloud dependencies.

## Documentation
- `docs/USER_GUIDE.md` - Day-to-day usage for asset teams and auditors.
- `docs/ROLE_GUIDE.md` - Role-based walkthroughs and workflow summaries.
- `docs/ADMIN_GUIDE.md` - Admin setup, roles, permissions, and configuration.
- `docs/OPERATIONS.md` - Deployment, queue/scheduler, backups, and updates.
- `docs/ARCHITECTURE.md` - Domain structure, data model, and internals.
- `docs/INSTANCE_DEPLOYMENT.md` - Step-by-step guide for provisioning separate company instances.
- `docs/QUICK_DEPLOY.md` - Fast clone-to-deploy workflow using one command.
- `docs/REQUIREMENTS.md` - Infrastructure, runtime, and environment prerequisites.
- `docs/GITHUB_SETUP.md` - Safe GitHub publishing and repository hardening checklist.
- `SECURITY.md` - Security policy and hardening checklist.

## Key Features
- Asset lifecycle management with assignments, maintenance, and attachments
- Quantity-based accessory inventory with check-out and check-in
- Role-based access control via spatie/laravel-permission
- CSV import/export with mapping and validation preview
- QR codes and printable labels for single or bulk assets
- Full audit trail for create/update/delete/check-in/check-out/status changes
- Database-backed queues and scheduled metrics refresh

## Requirements
- PHP 8.2 or 8.3
- MariaDB 10.4+ (or MySQL 8+)
- Web server: Apache or Nginx
- PHP extensions: ctype, fileinfo, intl, json, mbstring, openssl, pdo_mysql, tokenizer, xml, zip, gd

## Quick Start
1) Install dependencies
```bash
composer install
```

2) Configure environment
```bash
cp .env.example .env
# PowerShell: Copy-Item .env.example .env
```
Update `.env` with your database credentials and base URL (`APP_URL`).

3) Generate key and storage symlink
```bash
php artisan key:generate
php artisan storage:link
```

4) Initialize the database
Use either approach:
```bash
php artisan migrate
```
or open `{APP_URL}/setup` and click `Initialize Database`.

5) Start the app and run first-time setup
Open `{APP_URL}/setup`, then:
- Enter company and admin account details
- Submit `Complete Setup`

The bundled `.env.example` uses file-based sessions and cache so the first-run installer works before database tables exist.

6) Optional demo data
```bash
php artisan assetflow:seed-demo
```

7) Run locally
```bash
php artisan serve
```

Then open `http://127.0.0.1:8000/setup` if you kept the default local `APP_URL`.

## Quick Deploy (Clone -> Ready)
For fastest deployment on a new server:
```bash
git clone https://github.com/ahmedanas37/assetflow.git
cd assetflow
scripts/deploy-instance.sh \
  --company "Acme Corp" \
  --app-url "https://assetflow.acme.local" \
  --db-database "assetflow_acme" \
  --db-username "assetflow_user" \
  --prompt-db-password
```

Then open `https://assetflow.acme.local/setup` and create the first admin account.

## Admin Recovery
If login fails or you need to reset admin credentials:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan assetflow:reset-admin --email=admin@example.local --generate-password
# or provide your own: --password='<YOUR_STRONG_PASSWORD>'
php artisan permission:cache-reset
```

## Admin Panel
Access the admin panel at:
```
{APP_URL}/admin
```

## First-Run Installer
This build is single-instance per company. On a fresh deployment:
- Visiting any route automatically redirects to `/setup` until setup is completed
- Setup creates baseline roles/permissions, status labels, core reference data, and the first admin user
- Setup captures company name and accent color for that instance
- After setup, `/setup` is locked and users sign in at `/admin`
- Product name remains `AssetFlow` across all deployments
- Logo, company name, color, and email footer can be changed later in `Administration > Portal Settings > Branding`

## Queues and Scheduler
Queues use the database driver. Start a worker:
```bash
php artisan queue:work --tries=3
```

Add a cron entry for the scheduler:
```bash
* * * * * cd /var/www/assetflow-instance && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `assetflow:update-metrics` hourly for dashboard warranty and overdue updates.

## Artisan Commands
- `php artisan assetflow:seed-demo` - Seed sample manufacturers, models, locations, and assets
- `php artisan assetflow:recalculate-assignments` - Sync `assigned_to_user_id` from active assignments
- `php artisan assetflow:update-metrics` - Refresh cached dashboard metrics
- `php artisan assetflow:reset-admin` - Reset or create the admin account

## CSV Import/Export
- Download the template from Assets > Import CSV or `/assets/template`
- Map columns and run Preview or Validate before importing
- Enable "Create missing reference data" to auto-create categories, models, locations, and vendors
- Export from Assets list or a single asset detail page

## User Import
Bulk import users from `People > Users > Import CSV` using the template at `/users/template`.

## Accessories (Quantity-based)
Accessories track items like mouse, keyboard, and headset.
- Set total quantities and add stock
- Check out accessories to users or locations
- Capture cubicle/system name for location assignments

## Labels and QR Codes
Use "Print Label" on an asset detail page or the bulk action on the Assets table. QR codes link to the asset detail page using `APP_URL`.

## Storage and Permissions
Ensure write access for:
- `storage`
- `bootstrap/cache`

Attachments and asset photos are stored on the private disk at `storage/app/private`.
Instance branding logos are stored on the public disk at `storage/app/public/branding`.

Create the public storage symlink once per instance:
```bash
php artisan storage:link
```

## Apache Configuration (example)
```apache
<VirtualHost *:80>
    ServerName assetflow.example.local
    DocumentRoot "/var/www/assetflow-instance/public"

    <Directory "/var/www/assetflow-instance/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Nginx Configuration (example)
```nginx
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

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Deploying Separate Company Instances
Use one isolated deployment per company:
- Separate code directory
- Separate database
- Separate `.env` and `APP_KEY`
- Separate queue worker/service and scheduler logs

Recommended rollout flow for each new company:
```bash
cp -R /opt/assetflow-template /var/www/assetflow-acme
cd /var/www/assetflow-acme
scripts/deploy-instance.sh \
  --company "Acme Corp" \
  --app-url "https://assetflow.acme.local" \
  --db-database "assetflow_acme" \
  --db-username "assetflow_user" \
  --prompt-db-password
```

Then open `https://assetflow.acme.local/setup` and complete first-run setup from the browser.

Then configure:
- Web server vhost for the new hostname
- A queue worker service for that instance
- A scheduler cron entry for that instance path

## Backups
Database backup:
```bash
mysqldump -u root -p assetflow > backups/assetflow.sql
```

Attachment and photo backup:
```bash
tar -czf backups/assetflow-storage.tar.gz storage/app/private
```

Restore:
```bash
mysql -u root -p assetflow < backups/assetflow.sql
tar -xzf backups/assetflow-storage.tar.gz -C storage/app
```

## Updating
```bash
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

## Security Notes
- Set `APP_ENV=production` and `APP_DEBUG=false` in production.
- Keep the instance internal-only (intranet) and restrict access via firewall.
- Use a strong admin password during first-run setup.
- No fixed default admin/import/demo passwords are bundled in this repository.
