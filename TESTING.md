# RBDB Testing Framework V2.1

This document outlines the testing strategy, environment setup, and execution guidelines for the Report Builder From Database (RBDB) system.

## 🏛️ Environment Setup

The testing environment is containerized using Docker to ensure consistency across development and CI/CD.

### Infrastructure
- **MySQL**: Main operational database.
- **PostgreSQL**: Supported data source.
- **Oracle XE (Heavy)**: Supported data source.
- **MSSQL Express (Heavy)**: Supported data source.
- **Redis**: Queue and caching.
- **Pure-FTPd**: Report delivery destination.
- **MailHog**: SMTP testing.
- **Toxiproxy**: Network failure and latency simulation.

### Initializing the Test Environment
```bash
# Start basic services
docker compose -f docker/docker-compose.test.yml up -d

# Start heavy services (Oracle/MSSQL)
docker compose -f docker/docker-compose.test.yml --profile heavy up -d
```

## 🧪 Testing Tiers

### 1. Unit Testing
Focuses on individual classes and functions.
- **Tool**: PHPUnit (Laravel), `go test` (Engine).
- **Execution**: `php artisan test --testsuite=Unit`

### 2. Integration Testing
Focuses on the interaction between components (Laravel ↔ Redis ↔ Go Engine).
- **Tool**: PHPUnit Feature Tests.
- **Critical Scenarios**:
    - **RLS**: Ensuring data isolation between departments.
    - **Versioning**: Automatic version creation on report updates.
    - **Engine Flow**: Sending job to Redis and verifying artifact creation in FTP.

### Testing Progress
- [x] **Infrastructure**: All DB services (MySQL, Postgres, MSSQL, Oracle) are healthy and unified.
- [x] **Integration (Laravel <-> Redis)**: `EngineIntegrationTest` passes with Job queuing and Visual AST compilation.
- [x] **Integration (Engine <-> DB <-> FTP)**: `integration_test.go` (Go) successfully processes jobs, connects to test DBs, and delivers to FTP.
- [ ] **E2E (Frontend)**: Playwright setup in progress.
- [ ] **Resilience**: Toxiproxy integration tests.

### 3. End-to-End (E2E) Testing
Simulates real user journeys in the browser.
- **Tool**: Playwright.
- **Critical Scenarios**:
    - Build a report using Visual Query Builder.
    - Execute and download the XLSX result.

### 4. Performance & Stress Testing
Verifies system stability under high load.
- **Benchmarks**:
    - **1M Rows Test**: Generating a report with 1,000,000 records.
    - **Peak Memory**: Go Engine must stay under 512MB RAM using streaming.
    - **Concurrency**: 50 simultaneous report executions.

## 🛡️ Resilience & Security

### Network Failure Simulation (Toxiproxy)
Use Toxiproxy to simulate:
- **Latency**: 2000ms delay on DB connections.
- **Bandwidth**: 100KB/s limit on FTP uploads.
- **Down**: Database/Redis disconnect mid-execution.

### Security Hardening
- **SQL Injection**: Attempting to bypass the parser in Visual Builder.
- **RLS Bypass**: Toggling UUIDs in API calls to access other departments' reports.

## 📊 KPIs for Success
- **Data Accuracy**: 100% parity between source DB and generated file.
- **Uptime**: >99% success rate in resilient retry tests.
- **Audit Compliance**: 100% of actions captured in `audit_logs`.

## 🧹 Cleanup
After tests, run:
```bash
docker compose -f docker/docker-compose.test.yml down -v
```
