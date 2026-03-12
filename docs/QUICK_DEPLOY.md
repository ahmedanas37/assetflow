# AssetFlow Quick Deploy

Use this when you want the fastest clone-to-deploy flow on a new server.

## 1) Clone
```bash
git clone https://github.com/ahmedanas37/assetflow.git
cd assetflow
```

## 2) Run One Bootstrap Command
```bash
scripts/deploy-instance.sh \
  --company "Acme Corp" \
  --app-url "https://assetflow.acme.local" \
  --db-database "assetflow_acme" \
  --db-username "assetflow_user" \
  --prompt-db-password
```

What this command does:
- creates/updates `.env`
- installs Composer dependencies
- generates `APP_KEY` (if missing)
- runs `php artisan storage:link`
- runs `php artisan migrate --force`
- runs `php artisan config:cache` and `php artisan route:cache`

## 3) Finish First-Run Setup
Open:
```
https://assetflow.acme.local/setup
```

Then create the first admin account from the form.

## Common Flags
- `--db-host` (default: `127.0.0.1`)
- `--db-port` (default: `3306`)
- `--db-password` (non-interactive mode)
- `--email-domain`
- `--admin-email`
- `--skip-migrate`
- `--skip-cache`
- `--skip-composer`
- `--with-dev` (installs dev dependencies)
