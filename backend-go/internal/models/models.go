package models

import "time"

type DataSource struct {
	ID               string                 `json:"id"`
	Name             string                 `json:"name"`
	Type             string                 `json:"type"` // oracle, mysql, postgres, mssql
	ConnectionConfig map[string]interface{} `json:"connection_config"`
}

type Service struct {
	ID   string `json:"id"`
	Name string `json:"name"`
}

type ReportField struct {
	ID            string `json:"id"`
	ReportID      string `json:"report_id"`
	SourceField   string `json:"source_field"`
	Alias         string `json:"alias"`
	IsVisible     bool   `json:"is_visible"`
	OrderPosition int    `json:"order_position"`
	DataType      string `json:"data_type"`
	Format        string `json:"format"`
}



type Report struct {
	ID                string     `json:"id"`
	Name              string     `json:"name"`
	Type              string     `json:"type"` // sql, visual, service
	SQLDefinition     string     `json:"sql_definition"`
	Description       string     `json:"description"`
	ServiceID         string     `json:"service_id"`
	DataSourceID      string     `json:"data_source_id"`
	DeliveryMode      string     `json:"delivery_mode"`
	EmailServerID     string     `json:"email_server_id"`
	EmailTemplateID   string     `json:"email_template_id"`
	FtpServerID       string     `json:"ftp_server_id"`
	DefaultRecipients string     `json:"default_recipients"`
	Service           Service    `json:"service"`
	DataSource        DataSource `json:"data_source"`
	EmailServer       DataSource `json:"email_server"`   // Reusing DataSource struct for simplicity if fields match
	EmailTemplate     DataSource `json:"email_template"` // Or create specific structs
	FtpServer         DataSource    `json:"ftp_server"`
	RetentionPeriod   string        `json:"retention_period"`
	Fields            []ReportField `json:"fields"`
}


type ExecutionUpdate struct {
	Status      string     `json:"status"`
	StartedAt   *time.Time `json:"started_at,omitempty"`
	FinishedAt  *time.Time `json:"finished_at,omitempty"`
	OutputPath  string     `json:"output_path,omitempty"`
	FileSize    int64      `json:"file_size,omitempty"`
	ErrorLog    string     `json:"error_log,omitempty"`
	OTP         string     `json:"otp,omitempty"`
	ExpiresAt   *time.Time `json:"expires_at,omitempty"`
}

type RetryPolicy struct {
	MaxAttempts      int    `json:"max_attempts"`
	BackoffStrategy  string `json:"backoff_strategy"`
	MaxBackoffHours  int    `json:"max_backoff_hours"`
}

type Job struct {
	JobID              string         `json:"job_id"`
	ExecutionID        string         `json:"execution_id"`
	ReportID           string         `json:"report_id"`
	TaskType           string         `json:"task_type"`
	Priority           string         `json:"priority"`
	TimeoutSeconds     int            `json:"timeout_seconds"`
	RetryPolicy        RetryPolicy    `json:"retry_policy"`
	SQLDefinition      string         `json:"sql_definition"`
	Bindings           []interface{}  `json:"bindings"`
	NotificationEmails []string       `json:"notification_emails"`
}
