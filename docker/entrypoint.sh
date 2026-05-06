#!/bin/sh
set -e

cd /var/www/html

# Ensure SQLite DB file exists and is writable BEFORE anything else
echo "→ Ensuring SQLite database exists..."
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chown www-data:www-data /var/www/html/database/database.sqlite 2>/dev/null || true

# Cache config/routes/views — failures are non-fatal (missing optional vars, etc.)
echo "→ Caching Laravel config..."
php artisan config:cache  || echo "⚠ config:cache failed, continuing..."
php artisan route:cache   || echo "⚠ route:cache failed, continuing..."
php artisan view:cache    || echo "⚠ view:cache failed, continuing..."
php artisan event:cache   || echo "⚠ event:cache failed, continuing..."

# Migrations are critical — must succeed
echo "→ Running migrations..."
php artisan migrate --force

echo "→ Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "→ Starting services..."
exec "$@"
