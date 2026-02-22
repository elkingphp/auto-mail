# Report Execution & Delivery Enhancement - Implementation Plan

## Overview
Comprehensive enhancement of the RBDB reporting system to add full execution, FTP delivery, email notifications with OTP, and telemetry tracking.

## Features to Implement

### 1️⃣ Execute Pulse Button (Reports List)
**Current**: Shows toast message only
**Required**: 
- Trigger actual report execution via queue
- Generate output file (XLSX/CSV)
- Upload to FTP with directory structure: `(YYYY-MM-DD)-(report-name)/(YYYY-MM-DD_HH-mm-ss).xlsx`
- Send email notification with OTP
- Show execution progress modal with email input field

**Files to Modify**:
- `frontend/src/modules/reports/ReportsList.vue` - Add execution modal
- `control-plane-laravel/app/Services/ReportExecutionService.php` - Update FTP directory naming
- `control-plane-laravel/app/Jobs/ExecuteReportJob.php` - Already exists, verify logic

### 2️⃣ Runtime Orchestration Section (Report Create/Edit)
**Current**: Basic delivery configuration
**Required**: Complete replacement with:
- Email Server Selection (dropdown from database)
- Email Template Selection (dropdown from database)
- FTP Server Selection (dropdown from database)

**Files to Modify**:
- `frontend/src/modules/reports/components/ReportConfigSidebar.vue` - Already has this! Just verify

### 3️⃣ Telemetry Summary Section
**Current**: Hardcoded values (1.4k cycles, 0.9s latency)
**Required**:
- Calculate real average execution time from `executions` table
- Calculate FTP storage usage for the report

**Files to Modify**:
- `frontend/src/modules/reports/components/ReportConfigSidebar.vue` - Fetch real data
- `control-plane-laravel/app/Http/Controllers/ReportController.php` - Add telemetry endpoint
- `control-plane-laravel/app/Services/ReportService.php` - Add telemetry calculation

### 4️⃣ SQL Logic Editor Enhancement
**Current**: No execute button in edit mode
**Required**: Add Run/Execute button like in create mode

**Files to Modify**:
- `frontend/src/modules/reports/ReportEdit.vue` - Add execute button
- Reuse existing `preview` endpoint

### 5️⃣ OTP Generation & Email Notification
**Current**: Partial implementation exists
**Required**: Ensure OTP is generated, stored, and included in email

**Files to Verify**:
- `control-plane-laravel/app/Services/ReportExecutionService.php` - OTP logic exists
- `control-plane-laravel/app/Services/Delivery/EmailDeliveryService.php` - Verify template rendering

### 6️⃣ FTP Directory Structure
**Current**: `reports/{report_name}/YYYY-MM-DD/filename`
**Required**: `(YYYY-MM-DD)-(report-name)/(YYYY-MM-DD_HH-mm-ss).xlsx`

**Files to Modify**:
- `control-plane-laravel/app/Services/ReportExecutionService.php` - Update directory structure

## Implementation Order

1. ✅ Fix FTP directory naming structure
2. ✅ Add telemetry calculation backend
3. ✅ Update telemetry display frontend
4. ✅ Add execution progress modal
5. ✅ Add SQL execute button in edit mode
6. ✅ Verify OTP and email delivery
7. ✅ Test in Docker environment

## Database Schema Status
✅ All required columns exist:
- `reports`: delivery_mode, email_server_id, email_template_id, ftp_server_id, default_recipients
- `executions`: otp_hash, otp_expires_at, ftp_path, uploaded_at, email_sent_at, etc.

## Service Architecture
✅ Existing services to reuse:
- `ReportExecutionService` - Main execution pipeline
- `FtpDeliveryService` - FTP upload logic
- `EmailDeliveryService` - Email sending logic
- `ExecuteReportJob` - Queue job for async execution

## Success Criteria
- [x] Execute Pulse creates real execution and generates files
- [x] FTP folders follow naming: `(YYYY-MM-DD)-(report-name)/`
- [x] Files follow naming: `(YYYY-MM-DD_HH-mm-ss).xlsx`
- [x] Email includes OTP and download link
- [x] Telemetry shows real data
- [x] SQL editor can preview in edit mode
- [x] Everything works in Docker
