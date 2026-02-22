#!/bin/bash
set -e

echo "🚀 RBDB Control Plane - Docker Entrypoint"
echo "=========================================="

# Check if artisan exists
if [ ! -f "artisan" ]; then
    echo "❌ ERROR: artisan file not found in $(pwd)!"
    echo "   Contents of $(pwd):"
    ls -la
    exit 1
fi

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
MAX_RETRIES=30
COUNT=0
until php artisan db:show --database=mysql --json >/dev/null 2>&1; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_RETRIES ]; then
        echo "❌ ERROR: Database connection could not be established after $MAX_RETRIES retries."
        php artisan db:show --database=mysql # Show the actual error
        exit 1
    fi
    echo "   Database not ready yet, retrying in 2 seconds... ($COUNT/$MAX_RETRIES)"
    sleep 2
done
echo "✓ Database connection established"

# Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force --no-interaction
echo "✓ Migrations completed"

# Run seeders (includes AdminSeeder with guards)
echo "🌱 Running database seeders..."
php artisan db:seed --force --no-interaction
echo "✓ Seeders completed"

# Clear and cache config
echo "🔧 Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Optimization completed"

echo "=========================================="
echo "✅ RBDB Control Plane Ready"
echo "=========================================="

# Execute the main container command (php-fpm)
exec "$@"
