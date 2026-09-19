#!/bin/bash
set -e

# =============================================================================
# Daily Work Report — Docker Entrypoint
# Handles: port config, Aiven SSL cert, DNS check, schema seeding, Apache start
# =============================================================================

echo "============================================"
echo "  Daily Work Report — Entrypoint Starting"
echo "============================================"

# --- 1. Apache port configuration ---
PORT="${PORT:-8080}"
echo "Listen $PORT" >> /etc/apache2/ports.conf
sed -i "s/\${APACHE_PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf
echo "[1/5] Apache configured to listen on port $PORT"

# --- 2. Aiven SSL/TLS certificate handling ---
# Priority: Render Secret File > DB_SSL_CA file path > PEM text > base64 DER
CERT_FILE=""
SECRET_FILE="/etc/secrets/aiven-ca.pem"

if [ -f "$SECRET_FILE" ]; then
    # Render Secret File exists — always prefer this (it's the actual PEM file)
    CERT_FILE="$SECRET_FILE"
    echo "[2/5] SSL cert from Render Secret File: $SECRET_FILE"
elif [ -n "$DB_SSL_CA" ]; then
    if [ -f "$DB_SSL_CA" ]; then
        CERT_FILE="$DB_SSL_CA"
        echo "[2/5] SSL cert from env file path: $DB_SSL_CA"
    elif echo "$DB_SSL_CA" | head -c 30 | grep -q "BEGIN CERTIFICATE"; then
        CERT_FILE="/etc/ssl/aiven-ca.pem"
        echo "$DB_SSL_CA" > "$CERT_FILE"
        export DB_SSL_CA="$CERT_FILE"
        echo "[2/5] SSL cert is PEM text — written to $CERT_FILE"
    else
        # Base64-encoded DER — decode to PEM
        echo "[2/5] Decoding base64 SSL certificate..."
        CERT_FILE="/etc/ssl/aiven-ca.pem"
        CLEAN_B64=$(echo "$DB_SSL_CA" | tr -d '[:space:]')
        echo "$CLEAN_B64" | base64 -d 2>/dev/null > /tmp/aiven-ca.der || true

        if [ -s /tmp/aiven-ca.der ]; then
            if openssl x509 -inform DER -in /tmp/aiven-ca.der -out "$CERT_FILE" 2>/dev/null; then
                echo "[2/5] Converted DER to PEM"
            elif openssl x509 -inform PEM -in /tmp/aiven-ca.der -out "$CERT_FILE" 2>/dev/null; then
                echo "[2/5] Certificate was already PEM"
            else
                echo "[2/5] WARNING: Could not convert certificate — SSL may not work for CLI"
                CERT_FILE=""
            fi
            rm -f /tmp/aiven-ca.der
        else
            echo "[2/5] WARNING: Could not decode DB_SSL_CA"
            CERT_FILE=""
        fi
    fi
else
    echo "[2/5] No SSL cert found (no Secret File, no DB_SSL_CA)"
fi

# --- 3. Create storage directories ---
mkdir -p /var/www/html/storage/temp
mkdir -p /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage/temp 2>/dev/null || true
chmod -R 775 /var/www/html/storage/uploads 2>/dev/null || true
echo "[3/5] Storage directories ready"

# --- 4. DNS diagnostic + Database schema seeding ---
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ] && [ -n "$DB_PASSWORD" ]; then
    echo "[4/5] Checking database connectivity..."

    # DNS check
    echo "  Resolving $DB_HOST..."
    if nslookup "$DB_HOST" >/dev/null 2>&1; then
        echo "  DNS resolution: OK"
    elif host "$DB_HOST" >/dev/null 2>&1; then
        echo "  DNS resolution: OK (via host)"
    else
        echo "  DNS resolution: FAILED — hostname cannot be resolved"
        echo "  This may be a DNS propagation delay. Apache will still start."
        echo "  The app will retry DNS when handling requests."
    fi

    # Build MySQL connection args — prefer Secret File cert
    MYSQL_SSL=""
    if [ -n "$CERT_FILE" ] && [ -f "$CERT_FILE" ]; then
        # Verify the cert file is valid PEM before using it
        if head -1 "$CERT_FILE" | grep -q "BEGIN CERTIFICATE"; then
            MYSQL_SSL="--ssl-ca=$CERT_FILE --ssl-mode=REQUIRED"
            echo "  Using SSL cert: $CERT_FILE"
        else
            echo "  WARNING: Cert file exists but is not PEM format — trying without SSL"
        fi
    else
        echo "  WARNING: No SSL cert file available — Aiven may reject connection"
    fi

    # Try to connect and check if users table exists
    echo "  Connecting to MySQL at $DB_HOST:$DB_PORT..."
    TABLE_CHECK=$(mysql \
        -h "$DB_HOST" \
        -P "$DB_PORT" \
        -u "$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        $MYSQL_SSL \
        -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE' AND table_name='users'" \
        "$DB_DATABASE" 2>&1 || echo "CONNECT_FAILED")

    if echo "$TABLE_CHECK" | grep -q "CONNECT_FAILED\|Access denied\|unknown\|error\|failed"; then
        echo "  MySQL connection: FAILED"
        echo "  Error: $TABLE_CHECK"
        echo "  Schema will NOT be seeded. Apache will start anyway."
        echo "  The PHP app will retry the connection when handling requests."
    elif [ "$TABLE_CHECK" = "0" ]; then
        echo "  Users table not found — seeding schema..."
        SCHEMA_CLEAN=$(sed '/^USE /d; /^CREATE DATABASE/d' /var/www/html/database/schema.sql)
        SEED_RESULT=$(echo "$SCHEMA_CLEAN" | mysql \
            -h "$DB_HOST" \
            -P "$DB_PORT" \
            -u "$DB_USERNAME" \
            -p"$DB_PASSWORD" \
            $MYSQL_SSL \
            "$DB_DATABASE" 2>&1 || echo "SEED_FAILED")

        if echo "$SEED_RESULT" | grep -q "SEED_FAILED"; then
            echo "  Schema seeding: FAILED"
            echo "  Error: $SEED_RESULT"
        else
            echo "  Schema seeding: SUCCESS (3 tables + 3 users created)"
        fi
    else
        echo "  Database schema: ALREADY EXISTS (users table found)"
    fi
else
    echo "[4/5] Database env vars not set — skipping schema check"
fi

# --- 5. Start Apache ---
echo "[5/5] Starting Apache on port $PORT..."
echo "============================================"
exec apache2-foreground
