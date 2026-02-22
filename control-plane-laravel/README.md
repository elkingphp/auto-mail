# RBDB Control Plane

## Overview
The **Control Plane** is the central administration hub for the Report Builder From Database (RBDB) system. It is responsible for:
- Managing users, roles, and permissions.
- Defining services, data sources, and report metadata.
- Scheduling reports.
- Configuring delivery targets.
- Tracking execution status (but not executing reports).

## System Architecture
This application is the **Metadata Foundation** of the system. It connects to the `antigravity` execution engine (Go) via a persistent queue (to be implemented) and shared database access.

## Database Schema (ERD)
The database structure is strictly metadata-driven. No business data is stored here.

### Core Entities
- **Roles**: System access roles.
- **Users**: System administrators and report consumers.
- **Services**: Grouping for reports.
- **Data Sources**: Connection strings (encrypted) for source databases.
- **Reports**: SQL or Visual definitions of reports.
- **Executions**: Audit log of report runs.

### Key Features
- **UUIDs Everywhere**: All primary keys are UUIDs for distributed safety.
- **Soft Deletes**: Enabled for Users.
- **Encryption**: Connection strings are encrypted at rest (handled by Eloquent casts).

## Installation

1. Install dependencies:
   ```bash
   composer install
   ```
2. Set up `.env` (ensure DB connection is configured).
3. Run migrations:
   ```bash
   php artisan migrate
   ```

## Development Rules
- Follow `CODING_RULES.md` in the root documentation.
- **Clean Architecture**: Keep controllers thin.
- **API First**: This app serves primarily as an API.

## API Documentation

### Authentication
The system uses **Laravel Sanctum**.
1. Login to get a token:
   `POST /api/v1/auth/login`
   ```json
   {
     "email": "admin@rbdb.local",
     "password": "password"
   }
   ```
2. Use the token in Authorization header:
   `Authorization: Bearer <token>`

### Endpoints
- **Swagger UI**: Accessible at `/api/documentation` (Note: Build may fail due to specific annotation issues, use Postman).
- **Postman**: Collection available in `../postman/RBDB_Control_Plane_Collection.json`.

### Authorization
- **Admin**: Full access.
- **Designer**: Can manage Reports.
- **Consumer**: Read-only access to assigned reports.

### Code Generation
To generate Postman collection:
```bash
php artisan generate:postman
```
