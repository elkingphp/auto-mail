# Testing Guide - RBDB System

## Roles
- **Admin**: Full access.
- **Designer**: Can create Reports.
- **Consumer**: Can view/run assigned reports.

## Test Checklist

### 1. Authentication
- [ ] Login with `admin@rbdb.local` / `password`.
- [ ] Verify redirection to Dashboard.
- [ ] Logout and verify redirection to Login.

### 2. Core Metadata (Control Plane)
- [ ] **Services**: Create a service (e.g., "Finance").
- [ ] **Data Sources**: Create a data source.
  - *Tip*: Use the existing `rbdb` connection for testing if no external DB is available.
- [ ] **Reports**: Create a report.
  - SQL: `SELECT * FROM users` (if querying internal DB for test).

### 3. Execution Flow (End-to-End)
1. **Trigger**:
   - Go to Reports list.
   - Click **Run** on the test report.
   - Assert: "Execution triggered" success message appear.
2. **Monitor**:
   - Go to **Executions** page immediately.
   - Assert: New row appears with status `pending`.
3. **Processing**:
   - Wait 5-10 seconds (Frontend polls every 5s).
   - Assert: Status changes to `processing` then `completed`.
4. **Output**:
   - Assert: "Download" button appears in the Actions column.
   - Click "Download".
   - Assert: File path is shown (or file downloads if serving is configured).

### 4. Error Handling
1. Create a Report with Invalid SQL (e.g., `SELECT * FROM non_existent_table`).
2. Run the report.
3. Monitor status on Executions page.
4. Assert: Status becomes `failed`.
5. Click **Error**.
6. Assert: Error log displays the SQL exception.

## API Testing (Postman)
A Postman collection is generated at `control-plane-laravel/postman/RBDB_Control_Plane_Collection.json`.
1. Import into Postman.
2. Set Environment Variables:
   - `base_url`: `http://localhost:8000/api/v1`
   - `token`: (Get from Login endpoint response)
3. Run the "Authentication / Login" request first to get the token.
