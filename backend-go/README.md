# RBDB Execution Engine (Go)

High-performance report generation and delivery engine written in Go.

## Architecture

This service operates as a polling worker (or triggered via API) that:
1.  **Polls** the Control Plane (`/api/v1/executions?status=pending`) for new jobs.
2.  **Fetches** report metadata (SQL definition, connections).
3.  **Builds** the query using a dynamic builder supporting Oracle, MySQL, Postgres, MSSQL.
4.  **Streams** the result set directly to a file generator (XLSX/CSV) to handle large datasets efficiently.
5.  **Delivers** the file via configured targets (Email, FTP).
6.  **Updates** execution status in the Control Plane.

## Setup

### Prerequisites
- Go 1.24+
- Control Plane running (Laravel)

### Configuration
Set the following environment variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `CONTROL_PLANE_URL` | URL of the Laravel API | `http://localhost:8000/api/v1` |
| `CONTROL_PLANE_TOKEN` | API Token for authentication | (Required) |
| `WORKER_COUNT` | Number of concurrent executions | `5` |
| `APP_ENV` | Environment (local/production) | `local` |

### Running Locally
```bash
cd backend-go
go mod tidy
go run cmd/main.go
```

### Docker
```bash
docker build -t rbdb-engine .
docker run -e CONTROL_PLANE_TOKEN=your_token --network host rbdb-engine
```

## Directory Structure
- `cmd/`: Entrypoint (`main.go`).
- `config/`: Configuration loading.
- `internal/`:
  - `api_client/`: HTTP client for Control Plane.
  - `report_builder/`: SQL generation and execution.
  - `executor/`: Worker pool and job processing.
  - `output/`: File format generators (Excel, CSV).
  - `delivery/`: Sender implementations.
  - `models/`: Shared data structures.

## Usage
Ensure the Control Plane has pending executions. The engine will automatically pick them up and process them.
