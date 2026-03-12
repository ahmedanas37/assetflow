# Security Policy

## Supported Versions
This is a self-hosted application. Security fixes should be applied from the latest stable branch and deployed with:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

## Reporting a Vulnerability
Do not open a public issue for security reports.

Report privately to the repository owner with:
- Description of the issue
- Reproduction steps
- Impact level
- Suggested fix (if available)

The maintainer should acknowledge receipt quickly and coordinate a fix + release timeline.

## Hardening Checklist
- Use `APP_ENV=production` and `APP_DEBUG=false`.
- Keep `.env` private and never commit it.
- Restrict server access by firewall/VPN.
- Enable HTTPS and valid TLS certificates.
- Run queue worker under least-privilege service account.
- Back up database and `storage/` on a schedule.
- Rotate admin credentials periodically.
