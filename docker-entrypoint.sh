#!/bin/bash
set -e

# Render kasih PORT dinamis, Apache harus nurut
PORT=${PORT:-80}
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80>/:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Bikin link storage (foto/QR) kalau belum ada
php artisan storage:link || true

# Bikin tabel database kalau belum ada
php artisan migrate --force

# Bersih-bersih cache biar kenceng
php artisan optimize

# Nyalakan Apache
exec apache2-foreground
