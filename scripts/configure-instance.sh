#!/usr/bin/env bash
set -euo pipefail

usage() {
    cat <<'USAGE'
Usage:
  scripts/configure-instance.sh \
    --company "Acme Corp" \
    --app-url "https://assetflow.acme.local" \
    --db-database "assetflow_acme" \
    [--db-host "127.0.0.1"] \
    [--db-port "3306"] \
    [--db-username "assetflow_user"] \
    [--db-password "db-password"] \
    [--prompt-db-password] \
    [--email-domain "acme.local"] \
    [--admin-email "admin@acme.local"]

This script prepares .env for a new single-company deployment instance.
It does not run migrations or install dependencies.
USAGE
}

COMPANY_NAME=""
APP_URL=""
DB_DATABASE=""
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_USERNAME="root"
DB_PASSWORD=""
PROMPT_DB_PASSWORD=0
EMAIL_DOMAIN=""
ADMIN_EMAIL=""

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

if [[ -z "$COMPANY_NAME" || -z "$APP_URL" || -z "$DB_DATABASE" ]]; then
    echo "Missing required arguments." >&2
    usage
    exit 1
fi

if [[ "$PROMPT_DB_PASSWORD" -eq 1 && -z "$DB_PASSWORD" ]]; then
    read -r -s -p "Database password (input hidden): " DB_PASSWORD
    echo
fi

if [[ ! -f ".env" ]]; then
    cp .env.example .env
fi

APP_NAME="AssetFlow"

if [[ -z "$EMAIL_DOMAIN" ]]; then
    EMAIL_DOMAIN="$(echo "$APP_URL" | sed -E 's#^[a-zA-Z]+://##; s#/.*$##')"
fi

if [[ -z "$EMAIL_DOMAIN" ]]; then
    EMAIL_DOMAIN="example.local"
fi

if [[ -z "$ADMIN_EMAIL" ]]; then
    ADMIN_EMAIL="admin@${EMAIL_DOMAIN}"
fi

set_env() {
    local key="$1"
    local value="$2"
    local escaped_value
    escaped_value="$(printf '%s' "$value" | sed -e 's/[&#]/\\&/g')"

    if grep -q "^${key}=" .env; then
        sed -i "s#^${key}=.*#${key}=${escaped_value}#g" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

set_env "APP_NAME" "\"${APP_NAME}\""
set_env "COMPANY_NAME" "\"${COMPANY_NAME}\""
set_env "APP_URL" "$APP_URL"
set_env "DB_DATABASE" "$DB_DATABASE"
set_env "DB_HOST" "$DB_HOST"
set_env "DB_PORT" "$DB_PORT"
set_env "DB_USERNAME" "$DB_USERNAME"

if [[ -n "$DB_PASSWORD" ]]; then
    set_env "DB_PASSWORD" "$DB_PASSWORD"
fi

set_env "ASSETFLOW_DEFAULT_EMAIL_DOMAIN" "$EMAIL_DOMAIN"
set_env "ASSETFLOW_ADMIN_EMAIL" "$ADMIN_EMAIL"
set_env "ASSETFLOW_ADMIN_NAME" "\"${COMPANY_NAME} Admin\""
set_env "ASSETFLOW_DEFAULT_VENDOR_EMAIL" "procurement@${EMAIL_DOMAIN}"
set_env "MAIL_FROM_NAME" "\"${APP_NAME}\""

echo "Updated .env for ${COMPANY_NAME}."
echo "Next steps:"
echo "  composer install --no-dev --optimize-autoloader"
echo "  php artisan key:generate"
echo "  php artisan config:cache"
echo "  php artisan route:cache"
echo "  Open ${APP_URL}/setup and complete first-run setup"
