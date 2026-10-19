#!/bin/sh
set -e

# Sesuaikan port Nginx secara dinamis mengikuti environment variable $PORT (mis. 10000 di Render atau 8080 di Fly)
TARGET_PORT="${PORT:-8080}"
sed -i "s/listen [0-9]\+ default_server;/listen ${TARGET_PORT} default_server;/g" /etc/nginx/sites-available/default
sed -i "s/listen \[::\]:[0-9]\+ default_server;/listen \[::\]:${TARGET_PORT} default_server;/g" /etc/nginx/sites-available/default

# Pastikan PHP-FPM hanya mendengarkan di 127.0.0.1 (internal saja, bukan 0.0.0.0) agar Render hanya mendeteksi Nginx
sed -i "s/listen = .*/listen = 127.0.0.1:9000/g" /usr/local/etc/php-fpm.d/zz-docker.conf 2>/dev/null || true
sed -i "s/listen = .*/listen = 127.0.0.1:9000/g" /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true

# Pastikan direktori framework storage tersedia dengan permission yang benar
mkdir -p /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/framework/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Buat symbolic link storage public bila belum ada
php artisan storage:link --force || true

# Pastikan driver session dan cache menggunakan file secara default agar tidak menembak DB jika belum siap
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"

# Pastikan APP_KEY tersedia (jika belum diisi di dashboard Render, gunakan fallback agar tidak error 500)
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY belum diset di environment, menggunakan key default..."
    export APP_KEY="base64:lqWWup0ko4JzGMK6h5bAiDERcAotzHQH8EjlpT1lEd4="
fi

# Jalankan migrasi dan seeding database otomatis bila terhubung (misal Supabase / PostgreSQL)
if [ -n "$DB_HOST" ] || [ -n "$DB_URL" ] || [ -n "$DATABASE_URL" ]; then
    echo "Menjalankan migrasi dan seeder database..."
    php artisan migrate --force || true
    php artisan db:seed --force || true
fi

# Optimasi cache Laravel untuk production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Jalankan supervisord (Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

