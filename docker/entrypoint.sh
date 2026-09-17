#!/bin/bash
set -e

# =============================================================================
# Daily Work Report — Docker Entrypoint
# Handles: port config, Aiven SSL cert, schema seeding, Apache start
# =============================================================================

# Use Render's PORT env variable, default to 8080
PORT="${PORT:-8080}"

# --- 1. Apache port configuration ---
# Add Listen directive to ports.conf
echo "Listen $PORT" >> /etc/apache2/ports.conf

# Replace ${APACHE_PORT} placeholder in VirtualHost config
sed -i "s/\${APACHE_PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf

echo "[entrypoint] Apache configured to listen on port $PORT"

# --- 2. Aiven SSL/TLS certificate handling ---
# DB_SSL_CA contains a base64-encoded DER certificate from deployment-secrets.txt
# We decode it to a PEM file that PDO can use
if [ -n "$DB_SSL_CA" ]; then
    # Check if it's already a file path
    if [ -f "$DB_SSL_CA" ]; then
        echo "[entrypoint] DB_SSL_CA is a file path: $DB_SSL_CA"
    else
        # It's base64-encoded content or inline PEM — decode and convert to PEM file
        echo "[entrypoint] Processing DB_SSL_CA certificate..."

        # Check if it's already a PEM certificate (starts with -----BEGIN)
        if echo "$DB_SSL_CA" | head -c 30 | grep -q "BEGIN CERTIFICATE"; then
            echo "$DB_SSL_CA" > /etc/ssl/aiven-ca.pem
            export DB_SSL_CA=/etc/ssl/aiven-ca.pem
            echo "[entrypoint] Certificate is already PEM format"
        else
            # Decode base64 to DER binary
            echo "$DB_SSL_CA" | tr -d '[:space:]' | base64 -d > /etc/ssl/aiven-ca.der 2>/dev/null || true

            if [ -f /etc/ssl/aiven-ca.der ] && [ -s /etc/ssl/aiven-ca.der ]; then
                # Try to convert DER to PEM
                if openssl x509 -inform DER -in /etc/ssl/aiven-ca.der -out /etc/ssl/aiven-ca.pem 2>/dev/null; then
                    echo "[entrypoint] Converted DER certificate to PEM"
                    export DB_SSL_CA=/etc/ssl/aiven-ca.pem
                elif openssl x509 -inform PEM -in /etc/ssl/aiven-ca.der -out /etc/ssl/aiven-ca.pem 2>/dev/null; then
                    echo "[entrypoint] Certificate was already in PEM format"
                    export DB_SSL_CA=/etc/ssl/aiven-ca.pem
                else
                    echo "[entrypoint] WARNING: Could not convert certificate — using raw DER"
                    cp /etc/ssl/aiven-ca.der /etc/ssl/aiven-ca.pem
                    export DB_SSL_CA=/etc/ssl/aiven-ca.pem
                fi
            else
                echo "[entrypoint] WARNING: DB_SSL_CA decode produced empty file"
            fi

            rm -f /etc/ssl/aiven-ca.der
        fi
    fi
fi

# --- 3. Create storage directories ---
mkdir -p /var/www/html/storage/temp
mkdir -p /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage/temp 2>/dev/null || true
chmod -R 775 /var/www/html/storage/uploads 2>/dev/null || true

# --- 4. Database schema seeding (first deploy) ---
# Try to connect and check if users table exists; if not, run schema
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ] && [ -n "$DB_PASSWORD" ]; then
    echo "[entrypoint] Checking database schema..."

    # Build MySQL connection args
    MYSQL_CMD="mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME"
    if [ -n "$DB_PASSWORD" ]; then
        MYSQL_CMD="$MYSQL_CMD -p$DB_PASSWORD"
    fi

    # Add SSL if configured
    if [ -n "$DB_SSL_CA" ] && [ -f "$DB_SSL_CA" ]; then
        MYSQL_CMD="$MYSQL_CMD --ssl-ca=$DB_SSL_CA --ssl-verify-server-cert"
    fi

    # Check if users table exists
    TABLE_CHECK=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE' AND table_name='users'" $DB_DATABASE 2>/dev/null || echo "error")

    if [ "$TABLE_CHECK" = "0" ] || [ "$TABLE_CHECK" = "error" ]; then
        echo "[entrypoint] Users table not found — seeding schema..."
        if [ -f /var/www/html/database/schema.sql ]; then
            # Remove USE and CREATE DATABASE statements (we're already connected to the right DB)
            SCHEMA_CLEAN=$(sed '/^USE /d; /^CREATE DATABASE/d; /^--.*Database/d' /var/www/html/database/schema.sql)
            echo "$SCHEMA_CLEAN" | $MYSQL_CMD $DB_DATABASE 2>/dev/null && \
                echo "[entrypoint] Schema seeded successfully" || \
                echo "[entrypoint] WARNING: Schema seeding failed — you may need to seed manually"
        else
            echo "[entrypoint] WARNING: schema.sql not found in image"
        fi
    else
        echo "[entrypoint] Database schema already exists"
    fi
else
    echo "[entrypoint] Database env vars not set — skipping schema check"
fi

echo "[entrypoint] Starting Apache on port $PORT..."

# Start Apache in foreground
exec apache2-foreground
