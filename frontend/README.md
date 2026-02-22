# RBDB Control Plane - Enterprise Frontend

A professional, enterprise-grade Vue.js 3 dashboard for the RBDB system.

## 🚀 Features

- **Consolidated Dashboard**: Real-time metrics and activity monitoring.
- **Service Management**: CRUD for business services.
- **Data Source Control**: Native support for Oracle, MySQL, Postgres, and MSSQL.
- **Advanced Report Builder**: 
  - SQL-based report definition.
  - Granular Field mapping (Business naming, Visibility, Sorting).
  - Dynamic Filter definition (Text, Date, Date Range, Select).
- **Execution Engine Monitoring**: Live polling of report processing status.
- **Automated Scheduling**: Cron-based report execution.
- **Enterprise Delivery**: Email and FTP/SFTP target management.
- **RBAC**: Role-based UI rendering (Admin/Designer/Consumer).

## 🛠 Tech Stack

- **Vue 3** (Composition API)
- **Vite** (Build Tool)
- **Pinia** (State Management)
- **Axios** (Service Layer)
- **Vue Router** (Navigation)
- **Date-fns** (Formatting)

## 📁 Architecture (Feature-Based)

The project follows a modular architecture as requested:

- `src/modules/auth`: Authentication logic and views.
- `src/modules/dashboard`: Multi-domain data aggregation.
- `src/modules/reports`: Main Report CRUD and Builder.
- `src/modules/executions`: Monitoring and Output Management.
- `src/modules/data-sources`: DB Connection configurations.
- `src/modules/services`: Business service grouping.
- `src/modules/schedules`: Automation configuration.
- `src/modules/delivery-targets`: Distribution management.

## 🔑 Environment Variables

- `VITE_API_BASE_URL`: Full URL to the Laravel Control Plane API (e.g., `http://localhost:8000/api/v1`).

## 📖 User Flow

1. **Setup Data Source**: Configure connection to your target database.
2. **Define Service**: Create a business group (e.g., "Finance").
3. **Build Report**: Write SQL, define user-friendly field names, and add dynamic filters.
4. **Schedule / Deliver**: Set up a recurring schedule and define who receives the results via Email or FTP.
5. **Monitor**: Track progress in the Executions dashboard.

---
*Built for Egypt Post - RBDB Project*
