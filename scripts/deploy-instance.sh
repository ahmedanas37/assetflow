#!/usr/bin/env bash
set -euo pipefail

usage() {
    cat <<'USAGE'
Usage:
  scripts/deploy-instance.sh \
    --company "Acme Corp" \
    --app-url "https://assetflow.acme.local" \
    --db-database "assetflow_acme" \
    --db-username "assetflow_user" \
    [--db-host "127.0.0.1"] \
    [--db-port "3306"] \
    [--db-password "db-password"] \
    [--prompt-db-password] \
    [--email-domain "acme.local"] \
    [--admin-email "admin@acme.local"] \
    [--with-dev] \
    [--skip-composer] \
    [--skip-migrate] \
    [--skip-cache]

Description:
  One-command bootstrap for a fresh AssetFlow instance after clone/copy.
  It prepares .env, installs Composer dependencies, generates APP_KEY,
  links storage, optionally runs migrations, and caches config/routes.
USAGE
}

COMPANY_NAME=""
APP_URL=""
DB_DATABASE=""
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_USERNAME=""
DB_PASSWORD=""
PROMPT_DB_PASSWORD=0
EMAIL_DOMAIN=""
ADMIN_EMAIL=""
WITH_DEV=0
SKIP_COMPOSER=0
SKIP_MIGRATE=0
SKIP_CACHE=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --company)
            COMPANY_NAME="$2"
            shift 2
            ;;
        --app-url)
            APP_URL="$2"
            shift 2
            ;;
        --db-database)
            DB_DATABASE="$2"
            shift 2
            ;;
        --db-host)
            DB_HOST="$2"
            shift 2
            ;;
        --db-port)
            DB_PORT="$2"
            shift 2
            ;;
        --db-username)
            DB_USERNAME="$2"
            shift 2
            ;;
        --db-password)
            DB_PASSWORD="$2"
            shift 2
            ;;
        --prompt-db-password)
            PROMPT_DB_PASSWORD=1
            shift
            ;;
        --email-domain)
            EMAIL_DOMAIN="$2"
            shift 2
            ;;
        --admin-email)
            ADMIN_EMAIL="$2"
            shift 2
            ;;
        --with-dev)
            WITH_DEV=1
            shift
            ;;
        --skip-composer)
            SKIP_COMPOSER=1
            shift
            ;;
        --skip-migrate)
            SKIP_MIGRATE=1
            shift
            ;;
        --skip-cache)
            SKIP_CACHE=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage
            exit 1
            ;;
    esac
done

if [[ -z "$COMPANY_NAME" || -z "$APP_URL" || -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
    echo "Missing required arguments." >&2
    usage
    exit 1
fi

if [[ "$PROMPT_DB_PASSWORD" -eq 1 && -z "$DB_PASSWORD" ]]; then
    read -r -s -p "Database password (input hidden): " DB_PASSWORD
    echo
fi

if ! command -v php >/dev/null 2>&1; then
    echo "php is required but not found in PATH." >&2
    exit 1
fi

if [[ "$SKIP_COMPOSER" -eq 0 ]] && ! command -v composer >/dev/null 2>&1; then
    echo "composer is required but not found in PATH." >&2
    exit 1
fi

if [[ ! -x scripts/configure-instance.sh ]]; then
    echo "scripts/configure-instance.sh is missing or not executable." >&2
    exit 1
fi

configure_args=(
    --company "$COMPANY_NAME"
    --app-url "$APP_URL"
    --db-database "$DB_DATABASE"
    --db-host "$DB_HOST"
    --db-port "$DB_PORT"
    --db-username "$DB_USERNAME"
)

if [[ -n "$DB_PASSWORD" ]]; then
    configure_args+=(--db-password "$DB_PASSWORD")
fi
if [[ -n "$EMAIL_DOMAIN" ]]; then
    configure_args+=(--email-domain "$EMAIL_DOMAIN")
fi
if [[ -n "$ADMIN_EMAIL" ]]; then
    configure_args+=(--admin-email "$ADMIN_EMAIL")
fi

scripts/configure-instance.sh "${configure_args[@]}"

if [[ "$SKIP_COMPOSER" -eq 0 ]]; then
    if [[ "$WITH_DEV" -eq 1 ]]; then
        composer install
    else
        composer install --no-dev --optimize-autoloader
    fi
fi

current_app_key="$(grep '^APP_KEY=' .env | head -n1 | cut -d= -f2-)"
if [[ -z "$current_app_key" ]]; then
    php artisan key:generate --force
fi

if [[ ! -L public/storage ]]; then
    php artisan storage:link
fi

if [[ "$SKIP_MIGRATE" -eq 0 ]]; then
    php artisan migrate --force
fi

if [[ "$SKIP_CACHE" -eq 0 ]]; then
    php artisan config:cache
    php artisan route:cache
fi

echo
echo "Instance bootstrap complete."
echo "Open ${APP_URL}/setup to finish first-run admin creation."
echo "Admin login URL after setup: ${APP_URL}/admin"
