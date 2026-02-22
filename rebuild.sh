#!/bin/bash
set -e

echo "🛑 Stopping containers..."
docker compose down -v

echo "🏗 Building Engine (Golang)..."
docker compose build engine

echo "🏗 Building Control Plane (Laravel)..."
docker compose build app

echo "🏗 Building Frontend (Vue)..."
docker compose build frontend

echo "🚀 Starting services..."
docker compose up -d

echo "✅ Environment rebuilt successfully (sequentially)!"
