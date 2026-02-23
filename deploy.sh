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

# --- 1. OS Detection & Auto-Installation ---
echo -e "\n${YELLOW}🔍 Step 1: Checking System & Prerequisites...${NC}"

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    OS_LIKE=$ID_LIKE
else
    OS=$(uname -s)
fi

echo -e "🖥️ Detected OS: ${BLUE}$PRETTY_NAME${NC}"

# Function to install packages based on OS
install_pkg() {
    local pkg=$1
    echo -e "${YELLOW}🛠️ Attempting to install $pkg...${NC}"
    
    if [[ "$OS" == "almalinux" || "$OS" == "rhel" || "$OS" == "centos" || "$OS_LIKE" == *"rhel"* ]]; then
        sudo dnf install -y "$pkg"
    elif [[ "$OS" == "ubuntu" || "$OS" == "debian" || "$OS_LIKE" == *"debian"* ]]; then
        sudo apt-get update && sudo apt-get install -y "$pkg"
    else
        echo -e "${RED}❌ Auto-install not supported for $OS. Please install $pkg manually.${NC}"
        exit 1
    fi
}

# Check for Docker
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}⚠️ Docker not found. Starting installation...${NC}"
    if [[ "$OS" == "almalinux" || "$OS" == "rhel" ]]; then
        sudo dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
        install_pkg "docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin"
        sudo systemctl enable --now docker
    else
        install_pkg "docker.io"
    fi
fi

# Determine which docker-compose command to use
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
    echo -e "${GREEN}✅ Found 'docker compose' (plugin).${NC}"
elif command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
    echo -e "${GREEN}✅ Found 'docker-compose' (standalone).${NC}"
else
    echo -e "${YELLOW}⚠️ Docker Compose not found. Installing...${NC}"
    if [[ "$OS" == "almalinux" || "$OS" == "rhel" ]]; then
        install_pkg "docker-compose-plugin"
        DOCKER_COMPOSE="docker compose"
    else
        install_pkg "docker-compose"
        DOCKER_COMPOSE="docker-compose"
    fi
fi

# Check for lsof (used in port check)
if ! command -v lsof &> /dev/null; then
    install_pkg "lsof"
fi

echo -e "${GREEN}✅ All prerequisites satisfied.${NC}"

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
$DOCKER_COMPOSE build --parallel

echo -e "${BLUE}🚢 Starting containers in detached mode...${NC}"
$DOCKER_COMPOSE up -d

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
    $DOCKER_COMPOSE ps
    exit 1
fi

# --- 6. Post-Deployment Commands ---
echo -e "\n${YELLOW}🛠 Step 6: Running Post-Deployment Tasks...${NC}"

echo -e "${BLUE}🗄 Running Database Migrations...${NC}"
$DOCKER_COMPOSE exec -T app php artisan migrate --force

echo -e "${BLUE}🧹 Clearing Caches...${NC}"
$DOCKER_COMPOSE exec -T app php artisan config:clear
$DOCKER_COMPOSE exec -T app php artisan cache:clear

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
