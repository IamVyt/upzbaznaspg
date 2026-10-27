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

# APP_KEY wajib diisi. Jangan memakai key fallback yang tertulis di source code,
# karena siapa pun dapat memakainya untuk memalsukan cookie/session aplikasi.
if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY belum diset di environment." >&2
    echo "Buat key dengan: php artisan key:generate --show, lalu simpan sebagai environment variable APP_KEY." >&2
    exit 1
fi

# Jalankan migrasi database otomatis bila terhubung (misal Supabase / PostgreSQL).
# Seeder TIDAK dijalankan otomatis agar password pengurus tidak pernah di-reset saat deploy.
if [ -n "$DB_HOST" ] || [ -n "$DB_URL" ] || [ -n "$DATABASE_URL" ]; then
    echo "Menjalankan migrasi database..."
    php artisan migrate --force
fi

# Seeder opsional dan eksplisit, mis. saat inisialisasi pertama kali:
# set RUN_SEEDERS=true pada environment lalu deploy/restart sekali.
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "Menjalankan seeder (RUN_SEEDERS=true)..."
    php artisan db:seed --force || true
fi

# Optimasi cache Laravel untuk production. Jangan menelan error: bila cache
# gagal dibuat, container harus berhenti agar deploy tidak diam-diam rusak.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan supervisord (Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

