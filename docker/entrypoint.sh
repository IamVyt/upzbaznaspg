#!/bin/sh
set -e

# Sesuaikan port Nginx secara dinamis mengikuti environment variable $PORT (mis. 10000 di Render atau 8080 di Fly)
TARGET_PORT="${PORT:-8080}"
sed -i "s/listen [0-9]\+ default_server;/listen ${TARGET_PORT} default_server;/g" /etc/nginx/sites-available/default
sed -i "s/listen \[::\]:[0-9]\+ default_server;/listen \[::\]:${TARGET_PORT} default_server;/g" /etc/nginx/sites-available/default

# Pastikan kepemilikan dan permission direktori storage & bootstrap cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Buat symbolic link storage public bila belum ada
php artisan storage:link --force || true

# Jalankan migrasi database otomatis bila terhubung (misal Supabase / PostgreSQL)
if [ -n "$DB_HOST" ] || [ -n "$DB_URL" ] || [ -n "$DATABASE_URL" ]; then
    echo "Menjalankan migrasi database..."
    php artisan migrate --force || true
fi

# Optimasi cache Laravel untuk production (hanya jika APP_KEY sudah diisi)
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Jalankan supervisord (Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
