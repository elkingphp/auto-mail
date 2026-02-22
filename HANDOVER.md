# Project Handover: RBDB System

## 1. Project Overview
**RBDB (Report Builder from Database)** is a distributed system designed for Egypt Post to handle complex, cross-database reporting with high-performance background execution and delivery management.

## 2. Technical Stack
- **Control Plane**: Laravel 12 (PHP 8.4)
- **Execution Engine**: Go 1.24
- **Frontend**: Vue 3 (Vite + Tailwind CSS)
- **Infrastructure**: Docker & Docker Compose
- **State/Cache**: Redis
- **Database**: MySQL (Metadata), Support for Oracle, Postgres, MSSQL (Data Sources)

## 3. Key Credentials (Default)
- **Admin Email**: `admin@rbdb.local`
- **Admin Password**: `password`
- **Database Root**: `root` (See .env)
- **API Base**: `http://localhost:8000/api/v1`
- **Dashboard**: `http://localhost:8080`

## 4. Operational Readiness Components
- **Health Checks**: Implemented in `App/Http/Controllers/HealthController` for container orchestration compatibility.
- **Background Processing**: Redis-backed queue workers for Laravel and a dedicated polling Engine for Go.
- **Documentation**: 
    - `README.md`: Quick start
    - `docs/DEPLOYMENT.md`: Step-by-step setup
    - `RUNBOOK.md`: Troubleshooting & Maintenance
    - `postman/`: Complete API collection

## 5. Deployment Instructions
Ensure Docker is installed, then:
1. `cp control-plane-laravel/.env.example control-plane-laravel/.env`
2. `docker-compose up -d --build`
3. `docker-compose exec app php artisan migrate:fresh --seed --force`
4. Generate engine token and update `docker-compose.yml`.

## 6. Handover Checklist
- [x] Functional Requirements implemented.
- [x] Security (Sanctum + Policy-based authorization) verified.
- [x] Operational endpoints (Health/Metrics) ready.
- [x] Demo data seeded for walkthrough.
- [x] Postman collection updated.

---
**Prepared by**: Antigravity (AI Engineering Assistant)
**Date**: 2026-02-03
