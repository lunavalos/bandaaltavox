#!/bin/sh
set -e

cd /var/www/html

echo "→ Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "→ Ensuring SQLite database exists..."
touch /var/www/html/database/database.sqlite
chown www-data:www-data /var/www/html/database/database.sqlite 2>/dev/null || true

echo "→ Running migrations..."
php artisan migrate --force

echo "→ Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "→ Starting services..."
exec "$@"
