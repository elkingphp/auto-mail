# auto-mail (RBDB)

A comprehensive system for defining, executing, and delivering database reports.

## System Architecture

1.  **Control Plane (Laravel 11)**: Metadata management, API, Authentication (Sanctum), Scheduling.
2.  **Execution Engine (Go)**: High-performance query execution, file generation (XLSX/CSV), and delivery.
3.  **Frontend (Vue 3)**: User dashboard for managing reports and monitoring executions.
4.  **Database**: MySQL (Control Plane metadata), Oracle/Postgres/etc. (Data Sources).

## Documentation
- [User Guide](docs/USER_GUIDE.md): How to use the dashboard, manage reports, and run executions.
- [Deployment Guide](docs/DEPLOYMENT.md): Detailed Docker and environment setup.
- [Testing Guide](docs/TESTING.md): QA checklist and end-to-end testing flow.

## Features
- **Dynamic Report Builder**: Define reports via SQL or Visual Builder.
- **Multi-DB Support**: Connect to Oracle, MySQL, Postgres, MSSQL.
- **Asynchronous Execution**: Go-based engine processes reports in background.
- **Delivery**: Email, FTP, Local Storage.
- **Role-Based Access**: Admin, Designer, Consumer.
- **Formats**: CSV, Excel (XLSX).

## Quick Start (Docker)

1.  **Clone Repository**
    ```bash
    git clone <repo>
    cd RBDB/system
    ```

2.  **Configure Environment**
    - Copy `.env.example` to `.env` in `control-plane-laravel/`.
    - Set `DB_PASSWORD` and `APP_KEY` (run `php artisan key:generate` locally or generate one).

3.  **Start Services**
    ```bash
    docker-compose up -d --build
    ```

4.  **Initialize Database**
    ```bash
    docker-compose exec app php artisan migrate --seed
    ```
    *Creates default Admin user (admin@rbdb.local / password).*

5.  **Generate Engine Token**
    ```bash
    docker-compose exec app php artisan tinker
    >>> $user = App\Models\User::first();
    >>> echo $user->createToken('engine')->plainTextToken;
    ```
    - Update `docker-compose.yml` `CONTROL_PLANE_TOKEN` with this value and restart engine:
    ```bash
    docker-compose restart engine
    ```

6.  **Access System**
    - **Frontend**: `http://localhost:8080`
    - **API**: `http://localhost:8000/api/v1`
    - **Swagger**: `http://localhost:8000/api/documentation` *(If generated)*

## Manual Setup

### Control Plane (Laravel)
```bash
cd control-plane-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend (Vue)
```bash
cd frontend
npm install
npm run dev
```

### Backend Engine (Go)
```bash
cd backend-go
go run cmd/main.go
```

## API Documentation
Postman Collection is available at `control-plane-laravel/postman/RBDB_Control_Plane_Collection.json`.

## Testing Flow
1. Login to Frontend (`admin@rbdb.local` / `password`).
2. Create a Data Source (pointing to a test DB).
3. Create a Service.
4. Create a Report (SQL: `SELECT 1 as test`).
5. Go to "Executions" or click "Run" on Report.
6. Check Dashboard for "Completed" status.
