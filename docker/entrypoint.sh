#!/bin/bash
set -e

# Use Render's PORT env variable, default to 8080
PORT="${PORT:-8080}"

# Update Apache to listen on the correct port
sed -i "s/\${APACHE_PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf

# Also set the APACHE_PORT env var for the config template
export APACHE_PORT="$PORT"

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/temp
mkdir -p /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage

# Set permissions for cache
chmod -R 775 /var/www/html/storage/temp 2>/dev/null || true

echo "Starting Apache on port $PORT..."

# Start Apache in foreground
exec apache2-foreground
