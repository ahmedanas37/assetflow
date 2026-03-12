#!/usr/bin/env bash
set -euo pipefail

usage() {
    cat <<'USAGE'
Usage:
  scripts/configure-instance.sh \
    --company "Acme Corp" \
    --app-url "https://assetflow.acme.local" \
    --db-database "assetflow_acme" \
    [--email-domain "acme.local"] \
    [--admin-email "admin@acme.local"]

This script prepares .env for a new single-company deployment instance.
It does not run migrations or install dependencies.
USAGE
}

COMPANY_NAME=""
APP_URL=""
DB_DATABASE=""
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
