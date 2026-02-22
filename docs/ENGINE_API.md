# RBDB Engine API Specification

This document defines the communication protocol between the **Control Plane (Laravel)** and the **Execution Engine (Go)** via **Redis**.

## 1. Transport Layer
- **Medium**: Redis List
- **Main Queue Key**: `rbdb_execution_queue`
- **Priority Queues (Future)**: `rbdb_execution_queue_high`, `rbdb_execution_queue_low`
- **Pattern**: Producer (Laravel) pushes to list, Consumer (Go) blocks and pops (`BLPop`).

## 2. JSON Payload Schema

Every message pushed to the queue must follow this structure:

```json
{
  "job_id": "string (UUID)",
  "report_id": "string",
  "execution_id": "string",
  "task_type": "string (execute | preview | cleanup)",
  "priority": "string (high | medium | low)",
  "timeout_seconds": "integer (default: 3600)",
  "retry_policy": {
    "max_attempts": "integer",
    "backoff_strategy": "string (fixed | exponential)",
    "max_backoff_hours": "integer"
  },
  "sql_definition": "string (optional: if provided, engine skips API fetch for definition)",
  "bindings": "array (optional: for parameterized queries)",
  "notification_emails": "array of strings",
  "metadata": "object (catch-all for extra context)"
}
```

### Field Definitions:

| Field | Type | Description |
|-------|------|-------------|
| `job_id` | UUID | Unique ID for the job instance (different from execution_id). |
| `report_id` | string | ID of the report in the Control Plane. |
| `execution_id` | string | ID of the execution record to update. |
| `task_type` | string | `execute`: full generation, `preview`: first 100 rows, `cleanup`: delete files. |
| `priority` | string | Used by workers to prioritize processing. |
| `timeout_seconds`| int | Max time allowed for execution before Go worker terminates it. |
| `retry_policy` | object | Details on how to handle failures. |
| `sql_definition` | string | Pre-compiled SQL for visual reports or native SQL. |
| `bindings` | array | Values for `?` placeholders in the SQL. |
| `notification_emails`| array | Recipients for completion alerting. |

## 3. Execution Flow

1. **Laravel**: 
   - Creates `executions` record in DB with status `pending`.
   - Constructs JSON payload.
   - Pushes to Redis: `LPUSH rbdb_execution_queue <json>`.
   
2. **Go Engine**:
   - Pops job: `BLPOP rbdb_execution_queue 0`.
   - Updates status in DB via API to `processing`.
   - Starts timer (`context.WithTimeout`).
   - Executes query (using `bindings` if provided).
   - Streams results to output format.
   - Delivers to FTP/Email.
   - Updates status in DB to `completed` or `failed`.

## 4. Status Update Callbacks

The Engine updates the Control Plane via HTTP API:
- **Endpoint**: `PATCH /api/v1/executions/{id}`
- **Auth**: Bearer Token (Sanctum)

### Payload Example (Success):
```json
{
  "status": "completed",
  "finished_at": "2026-02-14T20:00:00Z",
  "output_path": "/2026-02-14/report.xlsx",
  "file_size": 1048576,
  "otp": "123456",
  "expires_at": "2026-02-15T20:00:00Z"
}
```

### Payload Example (Failure):
```json
{
  "status": "failed",
  "finished_at": "2026-02-14T20:00:00Z",
  "error_log": "Timeout: query took more than 3600s"
}
```

## 5. Parameterized Queries (Placeholder Mapping)

The engine automatically converts `?` placeholders based on the data source type:
- **MySQL**: `?` (as is)
- **Postgres**: `$1, $2, ...`
- **Oracle**: `:p1, :p2, ...`
- **MSSQL**: `@p1, @p2, ...`
