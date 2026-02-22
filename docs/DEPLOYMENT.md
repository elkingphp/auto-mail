# Deployment Guide - RBDB System

## Prerequisites
- **Docker** and **Docker Compose** installed on the host machine.
- Git.

## Architecture
The system consists of 5 containers:
1. **rbdb-app**: Laravel Control Plane (API & Metadata).
2. **rbdb-web**: Nginx for Control Plane.
3. **rbdb-db**: MySQL Database.
4. **rbdb-engine**: Go Execution Engine.
5. **rbdb-frontend**: Vue.js Dashboard (Nginx).

## Step-by-Step Deployment

### 1. Clone & Prepare
```bash
git clone <repository_url>
cd system
```

### 2. Environment Configuration
You must configure environment variables for security.

#### Control Plane (`control-plane-laravel/.env`)
Copy `.env.example`:
```bash
cp control-plane-laravel/.env.example control-plane-laravel/.env
```
Edit `.env` and set:
- `DB_PASSWORD`: Strong password for production.
- `APP_KEY`: Generate using `php artisan key:generate` (inside container later) or a random 32-char string.

#### Backend Engine (`backend-go/.env` or via Docker Compose)
The engine needs an API Token to talk to the Control Plane. 
**Initial Setup**: You might need to start the Control Plane first to generate this token.

### 3. Start Services (First Run)
```bash
docker-compose up -d --build
```

### 4. Database Setup & Token Generation
Run migrations and seeds:
```bash
docker-compose exec app php artisan migrate --seed
```

Generate a token for the Go Engine:
```bash
docker-compose exec app php artisan tinker

# Inside Tinker:
$user = App\Models\User::first(); // Or create a dedicated service account
echo $user->createToken('engine_token')->plainTextToken;
# Copy the output string
exit
```

Update `docker-compose.yml` (environment section for `engine`) with this token:
```yaml
  engine:
    environment:
      CONTROL_PLANE_TOKEN: <paste_token_here>
```

Restart the engine:
```bash
docker-compose restart engine
```

### 5. Access Points
- **Dashboard**: `http://your-server-ip:8080`
- **API**: `http://your-server-ip:8000/api/v1`

## Configuration Guide

### SMTP (Email)
To enable email delivery, update command arguments or environment variables passed to the Go Engine (future configuration in DB). Currently, email config is passed dynamically per execution or defaulted in code.

### FTP
FTP targets are defined in the database via the API. Ensure the Go Engine container has network access to the target FTP server.

## Troubleshooting
- **Logs**:
  - Control Plane: `docker-compose logs -f app`
  - Engine: `docker-compose logs -f engine`
- **Connection Refused**:
  - Ensure containers are on the same network (`rbdb-network`).
  - Use service names (`web`, `db`) instead of `localhost` inside containers.
