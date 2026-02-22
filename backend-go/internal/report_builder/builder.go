package report_builder

import (
	"context"
	"database/sql"
	"fmt"
	"strings"

	"rbdb-backend-go/internal/models"
    
    // Drivers
    _ "github.com/go-sql-driver/mysql"
    _ "github.com/lib/pq"
    _ "github.com/sijms/go-ora/v2"
    _ "github.com/denisenkom/go-mssqldb"
)

type Builder struct {
}

func NewBuilder() *Builder {
	return &Builder{}
}

// GetRows executes the report and returns the rows. 
// caller is responsible for closing rows (which closes connection)
func (b *Builder) GetRows(report *models.Report) (*sql.Rows, error) {
    // Deprecated in favor of ExecuteAndReturnRows
    return nil, fmt.Errorf("use StreamReport or ExecuteAndReturnRows")
}

// ExecuteAndReturnRows is a helper that returns rows and the db connection to close
func (b *Builder) ExecuteAndReturnRows(ctx context.Context, report *models.Report, job models.Job) (*sql.Rows, *sql.DB, error) {
    db, err := b.getDBConnection(report.DataSource)
    if err != nil {
        return nil, nil, err
    }
    
    query := job.SQLDefinition
    if query == "" {
        query = report.SQLDefinition
    }
    
    if query == "" {
        db.Close()
        return nil, nil, fmt.Errorf("report SQL definition is empty")
    }

    // placeholder conversion
    query = b.ConvertPlaceholders(query, report.DataSource.Type)

    var rows *sql.Rows
    if len(job.Bindings) > 0 {
        rows, err = db.QueryContext(ctx, query, job.Bindings...)
    } else {
        rows, err = db.QueryContext(ctx, query)
    }

    if err != nil {
        db.Close()
        return nil, nil, err
    }
    
    return rows, db, nil
}

// StreamReport executes the report and calls the processor for the result set
func (b *Builder) StreamReport(report *models.Report, job models.Job, processor func(*sql.Rows) error) error {
    db, err := b.getDBConnection(report.DataSource)
    if err != nil {
        return err
    }
    defer db.Close()

    query := job.SQLDefinition
    if query == "" {
        query = report.SQLDefinition
    }
    
    if query == "" {
        return fmt.Errorf("report SQL definition is empty")
    }

    query = b.ConvertPlaceholders(query, report.DataSource.Type)

    var rows *sql.Rows
    var subErr error
    
    if len(job.Bindings) > 0 {
        rows, subErr = db.Query(query, job.Bindings...)
    } else {
        rows, subErr = db.Query(query)
    }

    if subErr != nil {
        return subErr
    }
    defer rows.Close()

    return processor(rows)
}

func (b *Builder) ConvertPlaceholders(query string, dbType string) string {
    // Simple placeholder mapper for ?, to driver-specific ones
    // Note: MSSQL driver is 'sqlserver', Postgres is 'postgres'
    
    if dbType == "mysql" {
        return query
    }

    // Using strings.Builder for efficiency
    var sb strings.Builder
    count := 1
    inString := false
    
    for i := 0; i < len(query); i++ {
        char := query[i]
        
        // Handle single-quoted strings
        if char == '\'' {
            // Check for escaped single quote '' (SQL standard)
            if i+1 < len(query) && query[i+1] == '\'' {
                sb.WriteByte('\'')
                sb.WriteByte('\'')
                i++
                continue
            }
            inString = !inString
            sb.WriteByte('\'')
            continue
        }

        if char == '?' && !inString {
            switch dbType {
            case "postgres":
                sb.WriteString(fmt.Sprintf("$%d", count))
            case "oracle":
                sb.WriteString(fmt.Sprintf(":p%d", count))
            case "mssql":
                sb.WriteString(fmt.Sprintf("@p%d", count))
            default:
                sb.WriteString("?")
            }
            count++
        } else {
            sb.WriteByte(char)
        }
    }
    return sb.String()
}

func (b *Builder) getDBConnection(ds models.DataSource) (*sql.DB, error) {
    var dsn string
    var driver string

    cfg := ds.ConnectionConfig

    switch ds.Type {
    case "mysql":
        driver = "mysql"
        // user:password@tcp(host:port)/dbname?timeout=5s
        dsn = fmt.Sprintf("%s:%s@tcp(%s:%v)/%s?timeout=5s", cfg["username"], cfg["password"], cfg["host"], cfg["port"], cfg["database"])
    case "postgres":
        driver = "postgres"
        // postgres://user:password@host:port/dbname?sslmode=disable&connect_timeout=5
        dsn = fmt.Sprintf("postgres://%s:%s@%s:%v/%s?sslmode=disable&connect_timeout=5", cfg["username"], cfg["password"], cfg["host"], cfg["port"], cfg["database"])
    case "oracle":
        driver = "oracle"
        serviceName := cfg["service_name"]
        if serviceName == nil || serviceName == "" {
            serviceName = cfg["sid"]
        }
        if serviceName == nil || serviceName == "" {
            serviceName = "FREEPDB1" // Default for gvenzl/oracle-free
        }
        
        host := cfg["host"]
        if host == nil || host == "" {
            host = "oracle" // default container name
        }
        
        port := cfg["port"]
        if port == nil || port == "" {
            port = "1521"
        }
        
        // oracle://user:password@host:port/service_name?CONNECTION API TIMEOUT=5000 (ms) - actually go-ora uses different params or context
        // go-ora v2 url options: CONNECTION TIMEOUT (in seconds)
        dsn = fmt.Sprintf("oracle://%s:%s@%s:%v/%s?CONNECTION TIMEOUT=5", cfg["username"], cfg["password"], host, port, serviceName)
        fmt.Printf("Connecting to Oracle: oracle://%s:****@%s:%v/%s\n", cfg["username"], host, port, serviceName)
    case "mssql":
        driver = "sqlserver"
        // sqlserver://username:password@host:port?database=dbname&connection+timeout=5
        dsn = fmt.Sprintf("sqlserver://%s:%s@%s:%v?database=%s&connection+timeout=5", cfg["username"], cfg["password"], cfg["host"], cfg["port"], cfg["database"])
    default:
        return nil, fmt.Errorf("unsupported database type: %s", ds.Type)
    }

    return sql.Open(driver, dsn)
}
