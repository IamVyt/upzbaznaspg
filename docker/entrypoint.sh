#!/bin/sh
set -e

# Pastikan kepemilikan dan permission storage & bootstrap cache aman
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Jalankan link storage bila belum ada
php artisan storage:link --force || true

# Optimasi cache Laravel untuk production (hanya jika APP_KEY sudah diisi)
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Jalankan supervisord (Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
