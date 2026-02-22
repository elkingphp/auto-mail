package output

import (
	"database/sql"
	"encoding/csv"
	"fmt"
	"io"
	"strings"

	"rbdb-backend-go/internal/models"
	"time"

	"github.com/xuri/excelize/v2"
)

type Format string

const (
	FormatCSV  Format = "csv"
	FormatXLSX Format = "xlsx"
)

func WriteTo(rows *sql.Rows, format Format, w io.Writer, report *models.Report) error {
	// Map source column names to aliases (Case-insensitive)
	aliases := make(map[string]string)
	formats := make(map[string]string)
	visibleFields := make(map[string]bool)
	hasFields := len(report.Fields) > 0

	for _, f := range report.Fields {
		sourceField := strings.ToLower(f.SourceField)
		if f.Alias != "" {
			aliases[sourceField] = f.Alias
		}
		if f.Format != "" {
			formats[sourceField] = f.Format
		}
		visibleFields[sourceField] = f.IsVisible
	}

	switch format {
	case FormatCSV:
		return writeCSV(rows, w, aliases, formats, visibleFields, hasFields)
	case FormatXLSX:
		return writeXLSX(rows, w, aliases, formats, visibleFields, hasFields)

	default:
		return fmt.Errorf("unsupported format: %s", format)
	}
}

func writeCSV(rows *sql.Rows, w io.Writer, aliases map[string]string, formats map[string]string, visibleFields map[string]bool, hasFields bool) error {
	writer := csv.NewWriter(w)
	defer writer.Flush()

	// 1. Get DB columns
	dbColumns, err := rows.Columns()
	if err != nil {
		return err
	}

	// 2. Determine output columns
	var outputHeaders []string
	var activeIndices []int
	var activeColumnsLower []string

	for i, col := range dbColumns {
		colLower := strings.ToLower(col)
		if hasFields {
			isVisible, exists := visibleFields[colLower]
			if exists && !isVisible {
				continue
			}
		}

		headerName := col
		if alias, exists := aliases[colLower]; exists && alias != "" {
			headerName = alias
		}
		outputHeaders = append(outputHeaders, headerName)
		activeIndices = append(activeIndices, i)
		activeColumnsLower = append(activeColumnsLower, colLower)
	}

	if err := writer.Write(outputHeaders); err != nil {
		return err
	}

	// 3. Prepare Scan
	values := make([]interface{}, len(dbColumns))
	valuePtrs := make([]interface{}, len(dbColumns))
	for i := range dbColumns {
		valuePtrs[i] = &values[i]
	}

	for rows.Next() {
		if err := rows.Scan(valuePtrs...); err != nil {
			return err
		}

		record := make([]string, len(outputHeaders))
		for outputIdx, dbIdx := range activeIndices {
			val := values[dbIdx]
			colNameLower := activeColumnsLower[outputIdx]
			format := formats[colNameLower]
            
			if val != nil {
                // If format exists, try to format
                if format != "" {
                    formatted, ok := tryFormatDate(val, format)
                    if ok {
                        record[outputIdx] = formatted
                        continue
                    }
                }
                
				switch v := val.(type) {
				case []byte:
					record[outputIdx] = string(v)
				case time.Time:
                    record[outputIdx] = v.Format("2006-01-02 15:04:05")
                default:
					record[outputIdx] = fmt.Sprintf("%v", v)
				}
			} else {
				record[outputIdx] = ""
			}
		}

		if err := writer.Write(record); err != nil {
			return err
		}
	}

	return nil
}


func writeXLSX(rows *sql.Rows, w io.Writer, aliases map[string]string, formats map[string]string, visibleFields map[string]bool, hasFields bool) error {
	f := excelize.NewFile()
	index, _ := f.NewSheet("Sheet1")

	// 1. Get DB columns
	dbColumns, err := rows.Columns()
	if err != nil {
		return err
	}

	// 2. Determine output columns
	var outputHeaders []string
	var activeIndices []int
	var activeColumnsLower []string

	for i, col := range dbColumns {
		colLower := strings.ToLower(col)
		if hasFields {
			isVisible, exists := visibleFields[colLower]
			if exists && !isVisible {
				continue
			}
		}

		headerName := col
		if alias, exists := aliases[colLower]; exists && alias != "" {
			headerName = alias
		}
		outputHeaders = append(outputHeaders, headerName)
		activeIndices = append(activeIndices, i)
		activeColumnsLower = append(activeColumnsLower, colLower)
	}

	// 3. Write Header
	for i, head := range outputHeaders {
		cell, _ := excelize.CoordinatesToCellName(i+1, 1)
		f.SetCellValue("Sheet1", cell, head)
	}

	// 4. Prepare Scan values
	values := make([]interface{}, len(dbColumns))
	valuePtrs := make([]interface{}, len(dbColumns))
	for i := range dbColumns {
		valuePtrs[i] = &values[i]
	}

	rowIdx := 2
	for rows.Next() {
		if err := rows.Scan(valuePtrs...); err != nil {
			return err
		}

		for outputIdx, dbIdx := range activeIndices {
			cell, _ := excelize.CoordinatesToCellName(outputIdx+1, rowIdx)
			val := values[dbIdx]
			colNameLower := activeColumnsLower[outputIdx]
			format := formats[colNameLower]
            
			if val != nil {
                if format != "" {
                    formatted, ok := tryFormatDate(val, format)
                    if ok {
                         f.SetCellValue("Sheet1", cell, formatted)
                         continue
                    }
                }
                
				switch v := val.(type) {
				case []byte:
					f.SetCellValue("Sheet1", cell, string(v))
                case time.Time:
                    f.SetCellValue("Sheet1", cell, v.Format("2006-01-02 15:04:05"))
				default:
					f.SetCellValue("Sheet1", cell, v)
				}
			}
		}
		rowIdx++
	}

	f.SetActiveSheet(index)
	_ = f.DeleteSheet("Sheet1") // Default sheet
	return f.Write(w)
}

// Helper to format date
func tryFormatDate(val interface{}, format string) (string, bool) {
    // Map common user-friendly formats to Go layout
    layout := format
    switch format {
    case "YYYY-MM-DD":
        layout = "2006-01-02"
    case "DD/MM/YYYY":
        layout = "02/01/2006"
    case "MM/DD/YYYY":
        layout = "01/02/2006"
    case "YYYY-MM-DD HH:mm:ss":
        layout = "2006-01-02 15:04:05"
    }
    
    switch v := val.(type) {
    case time.Time:
        return v.Format(layout), true
    case []byte:
        // Try to parse string as time if it looks like a date
        s := string(v)
        // Try standard SQL format
        t, err := time.Parse("2006-01-02 15:04:05", s)
        if err == nil {
            return t.Format(layout), true
        }
        t, err = time.Parse("2006-01-02", s)
        if err == nil {
            return t.Format(layout), true
        }
    }
    return "", false
}


