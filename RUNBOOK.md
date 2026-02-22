# RBDB Operational Runbook

This document outlines common failure scenarios and maintenance tasks for the RBDB (Report Builder from Database) system.

## 1. Service Health Monitoring

The system provides multiple health endpoints:
- **Liveness**: `http://<host>:8000/health/live` (Basic availability)
- **Readiness**: `http://<host>:8000/health/ready` (Check DB and Redis connectivity)
- **Metrics**: `http://<host>:8000/api/v1/system/metrics` (Requires Auth - system stats)

## 2. Common Scenarios & Resolutions

### Scenario A: Engine not processing jobs
**Symptom**: Executions stay in "Pending" status indefinitely.
**Check**:
1. Verify Go Engine is running: `docker-compose ps engine`
2. Check Engine logs: `docker-compose logs -f engine`
3. Verify Redis connection in Engine: Engine logs will show connection errors if Redis is down.
**Resolution**:
- Restart Engine: `docker-compose restart engine`
- If persistent, check `CONTROL_PLANE_TOKEN` in `docker-compose.yml`.

### Scenario B: Scheduled reports not triggering
**Symptom**: Schedules are defined but no executions appear at the expected time.
**Check**:
1. Verify Scheduler container: `docker-compose ps scheduler`
2. Check Scheduler logs: `docker-compose logs -f scheduler`
**Resolution**:
- Restart Scheduler: `docker-compose restart scheduler`

### Scenario C: "Too many open files" or Memory issues
**Symptom**: App or Engine crashes during large data export.
**Resolution**:
- Check Go Engine `WORKER_COUNT` in `docker-compose.yml`. Reduce if memory is constrained.
- Ensure the host has enough disk space for temporary XLSX generation.

## 3. Maintenance Tasks

### Database Migrations
Run when a new version is deployed:
```bash
docker-compose exec app php artisan migrate --force
```

### Clearing Cache
```bash
docker-compose exec app php artisan cache:clear
```

### Log Rotation
Laravel logs are stored in `storage/logs/`.
Engine logs are output to stdout (Docker). Use `docker-compose logs --tail=100` to view recent history.

## 4. Emergency Contacts
- **System Admin**: admin@rbdb.local
- **Developer Team**: (Your contact info)
