#!/bin/sh
set -e

cd /var/www/html

# Ensure storage + cache are writable by php-fpm (www-data). Coolify mounts a
# persistent volume over storage/ at runtime owned by root, which shadows the
# image's build-time chown — so we must fix ownership here, after the mount.
echo "→ Fixing storage permissions..."
mkdir -p storage/app/public \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Ensure SQLite DB file exists only when actually using SQLite.
# NOTE: We store the DB in storage/sqlite/ (not database/) to avoid the
# Coolify volume mount shadowing the database/migrations/ directory.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    echo "→ Ensuring SQLite database exists..."
    mkdir -p /var/www/html/storage/sqlite
    touch /var/www/html/storage/sqlite/database.sqlite
    chown www-data:www-data /var/www/html/storage/sqlite/database.sqlite 2>/dev/null || true
fi

# Cache config/routes/views — failures are non-fatal (missing optional vars, etc.)
echo "→ Caching Laravel config..."
php artisan config:cache  || echo "⚠ config:cache failed, continuing..."
php artisan route:cache   || echo "⚠ route:cache failed, continuing..."
php artisan view:cache    || echo "⚠ view:cache failed, continuing..."
php artisan event:cache   || echo "⚠ event:cache failed, continuing..."

# Migrations are critical — must succeed
echo "→ Running migrations..."
php artisan migrate --force

# Assign 2FA type to any existing users that don't have one yet
echo "→ Assigning 2FA types to existing users..."
php artisan users:assign-2fa || echo "⚠ assign-2fa failed, continuing..."

echo "→ Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "→ Starting services..."
exec "$@"
