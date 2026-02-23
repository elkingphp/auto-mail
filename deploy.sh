#!/bin/bash

# ==============================================================================
# RBDB Deployment & Verification Script
# ==============================================================================
# This script handles the full deployment lifecycle:
# 1. Environment Validation
# 2. System Prerequisite Checks
# 3. Service Orchestration
# 4. Post-Deployment Health Verification
# ==============================================================================

set -e # Exit on error

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}🚀 RBDB System Deployment & Health Check 🚀${NC}"
echo -e "${BLUE}====================================================${NC}"

# --- 1. System Prerequisite Checks ---
echo -e "\n${YELLOW}🔍 Step 1: Checking System Prerequisites...${NC}"

check_cmd() {
    if ! command -v "$1" &> /dev/null; then
        echo -e "${RED}❌ Error: $1 is not installed.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ $1 is installed.${NC}"
}

check_cmd "docker"
check_cmd "docker-compose"

# Check if Docker daemon is running
if ! docker info &> /dev/null; then
    echo -e "${RED}❌ Error: Docker daemon is not running.${NC}"
    exit 1
fi

# --- 2. Environment File Checks ---
echo -e "\n${YELLOW}🔍 Step 2: Checking Configuration Files...${NC}"

REQUIRED_FILES=(".env" "docker-compose.yml" "rebuild.sh")
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ Error: Required file '$file' is missing!${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Found $file${NC}"
done

# Check sub-module env files
if [ ! -f "control-plane-laravel/.env" ]; then
    echo -e "${YELLOW}⚠️ Warning: control-plane-laravel/.env missing. Attempting to create from example...${NC}"
    if [ -f "control-plane-laravel/.env.example" ]; then
        cp control-plane-laravel/.env.example control-plane-laravel/.env
        echo -e "${GREEN}✅ Created control-plane-laravel/.env${NC}"
    else
        echo -e "${RED}❌ Error: No .env.example found in control-plane-laravel/!${NC}"
        exit 1
    fi
fi

# --- 3. Port Availability Checks ---
echo -e "\n${YELLOW}🔍 Step 3: Checking Port Availability...${NC}"

check_port() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null ; then
        echo -e "${RED}❌ Error: Port $port is already in use by another process.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Port $port is available.${NC}"
}

PORTS=(80 8088 3306 1521 8080 8025 1025)
for port in "${PORTS[@]}"; do
    check_port $port
done

# --- 4. Deployment ---
echo -e "\n${YELLOW}🚀 Step 4: Starting Deployment Process...${NC}"

echo -e "${BLUE}📦 Pulling/Building images...${NC}"
docker-compose build --parallel

echo -e "${BLUE}🚢 Starting containers in detached mode...${NC}"
docker-compose up -d

# --- 5. Health Verification ---
echo -e "\n${YELLOW}🔍 Step 5: Verifying Service Health...${NC}"

# List of critical services to check
SERVICES=("rbdb-db" "rbdb-redis" "rbdb-app" "rbdb-web" "rbdb-engine" "rbdb-frontend")

check_container_health() {
    local container=$1
    # Check if Health key exists
    local has_health=$(docker inspect --format='{{if .State.Health}}yes{{else}}no{{end}}' "$container" 2>/dev/null || echo "no")
    
    if [ "$has_health" == "yes" ]; then
        local status=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null)
        if [ "$status" == "healthy" ]; then
            return 0
        fi
    else
        # If no healthcheck defined, just check if it's running
        if [ "$(docker inspect -f '{{.State.Running}}' "$container" 2>/dev/null)" == "true" ]; then
            return 0
        fi
    fi
    return 1
}

# Wait for services to stabilize (max 60 seconds)
MAX_WAIT=60
COUNTER=0
echo -n "Waiting for services to become healthy..."
while [ $COUNTER -lt $MAX_WAIT ]; do
    ALL_HEALTHY=true
    for service in "${SERVICES[@]}"; do
        if ! check_container_health "$service"; then
            ALL_HEALTHY=false
            break
        fi
    done
    
    if $ALL_HEALTHY; then
        echo -e "\n${GREEN}✅ All services are UP and HEALTHY!${NC}"
        break
    fi
    
    echo -n "."
    sleep 2
    COUNTER=$((COUNTER+2))
done

if [ $COUNTER -ge $MAX_WAIT ]; then
    echo -e "\n${RED}❌ Timeout: Some services failed to reach healthy state within ${MAX_WAIT}s.${NC}"
    docker-compose ps
    exit 1
fi

# --- 6. Post-Deployment Commands ---
echo -e "\n${YELLOW}🛠 Step 6: Running Post-Deployment Tasks...${NC}"

echo -e "${BLUE}🗄 Running Database Migrations...${NC}"
docker-compose exec -T app php artisan migrate --force

echo -e "${BLUE}🧹 Clearing Caches...${NC}"
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear

# --- 7. Final Summary ---
echo -e "\n${BLUE}====================================================${NC}"
echo -e "${GREEN}✨ RBDB DEPLOYMENT COMPLETE ✨${NC}"
echo -e "${BLUE}====================================================${NC}"
echo -e "🌍 Frontend:   http://localhost:8088"
echo -e "🔌 API:        http://localhost/api/v1"
echo -e "📉 Redis:      Managed via rbdb-redis"
echo -e "📬 MailHog:    http://localhost:8025"
echo -e "📂 Oracle:     Port 1521 (Internal/Testing)"
echo -e "${BLUE}====================================================${NC}"
