# User Guide - RBDB System

## Table of Contents
1. [Overview](#overview)
2. [Accessing the Dashboard](#accessing-the-dashboard)
3. [Managing Reports](#managing-reports)
4. [Executing Reports](#executing-reports)
5. [Monitoring Executions](#monitoring-executions)

---

## Overview
The **Report Builder From Database (RBDB)** allows users to define SQL-based reports, execute them against various data sources (Oracle, MySQL, Postgres), and deliver the results via Email or FTP.

## Accessing the Dashboard
- **URL**: `http://localhost:8080` (or your deployment URL).
- **Default Admin Login**:
  - Email: `admin@rbdb.local`
  - Password: `password`

*(Screenshot: Login Screen)*

## Managing Reports
Reports are the core entities. They define "What" query to run and "Where" to get data from.

### 1. Create a New Report
1. Navigate to **Reports** in the sidebar.
2. Click **+ New Report**.
3. Fill in the details:
   - **Name**: A descriptive name (e.g., "Daily Sales").
   - **Type**: Select `SQL Based`.
   - **Service**: Select the business service this report belongs to.
   - **Data Source**: Select the target database connection.
   - **SQL Definition**: Enter your SQL query (e.g., `SELECT * FROM sales WHERE date = CURRENT_DATE`).
4. Click **Create**.

*(Screenshot: Create Report Modal)*

### 2. Edit a Report
1. In the Reports list, locate the report.
2. Click the **Edit** button.
3. Modify fields and click **Update**.

## Executing Reports
There are two ways to run a report:
1. **Manual Trigger**: From the Reports list, click **Run**.
2. **Scheduled**: (Coming in Phase 5 - configured via Schedules API).

When triggering manually:
- You will receive a confirmation alert.
- The system queues the job for the backend engine.

## Monitoring Executions
Navigate to **Executions** in the sidebar to see the real-time status of all jobs.

### Execution Statuses
- **Pending**: Job is queued but not yet picked up by the engine.
- **Processing**: The Go Engine is currently executing the query or generating the file.
- **Completed**: Report generated successfully.
- **Failed**: An error occurred (e.g., SQL error, connection timeout).

### Actions
- **Download**: If completed, click to see the output path (or download if file server is configured).
- **Error**: If failed, click to view the error log.

*(Screenshot: Execution Dashboard with various statuses)*
