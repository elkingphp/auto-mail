#!/bin/bash
# Test script to verify admin user persistence after database wipe

set -e

echo "=========================================="
echo "🧪 TESTING ADMIN USER PERSISTENCE"
echo "=========================================="

cd "$(dirname "$0")"

echo ""
echo "Step 1: Stopping all containers..."
docker compose down

echo ""
echo "Step 2: Deleting database volume (simulating fresh database)..."
docker volume rm system_db-data 2>/dev/null || echo "Volume already removed or doesn't exist"

echo ""
echo "Step 3: Starting containers (this will run migrations + seeders automatically)..."
docker compose up -d

echo ""
echo "Step 4: Waiting for containers to be healthy (30 seconds)..."
sleep 30

echo ""
echo "Step 5: Checking if admin user was created..."
docker compose exec -T app php artisan tinker <<EOF
\$admin = App\Models\User::where('email', 'admin@system.local')->first();
if (\$admin) {
    echo "✅ SUCCESS: Admin user exists!\n";
    echo "   Name: " . \$admin->name . "\n";
    echo "   Email: " . \$admin->email . "\n";
    echo "   Role: " . \$admin->role->name . "\n";
    echo "   Status: " . \$admin->status . "\n";
} else {
    echo "❌ FAILED: Admin user not found!\n";
    exit(1);
}
EOF

echo ""
echo "=========================================="
echo "✅ TEST PASSED: Admin user persists after database wipe"
echo "=========================================="
echo ""
echo "You can now login with:"
echo "  Email: admin@system.local"
echo "  Password: admin123"
echo ""
