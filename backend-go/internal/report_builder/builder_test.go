package report_builder

import (
	"testing"
)

func testConvertPlaceholders(t *testing.T) {
	builder := NewBuilder()

	tests := []struct {
		name     string
		query    string
		dbType   string
		expected string
	}{
		{
			name:     "MySQL - no change",
			query:    "SELECT * FROM users WHERE id = ?",
			dbType:   "mysql",
			expected: "SELECT * FROM users WHERE id = ?",
		},
		{
			name:     "Postgres - $1",
			query:    "SELECT * FROM users WHERE id = ? AND status = ?",
			dbType:   "postgres",
			expected: "SELECT * FROM users WHERE id = $1 AND status = $2",
		},
		{
			name:     "Oracle - :p1",
			query:    "SELECT * FROM users WHERE id = ?",
			dbType:   "oracle",
			expected: "SELECT * FROM users WHERE id = :p1",
		},
		{
			name:     "MSSQL - @p1",
			query:    "SELECT * FROM users WHERE id = ?",
			dbType:   "mssql",
			expected: "SELECT * FROM users WHERE id = @p1",
		},
		{
			name:     "String handling - skip ? in quotes",
			query:    "SELECT * FROM users WHERE name = 'John?' AND id = ?",
			dbType:   "postgres",
			expected: "SELECT * FROM users WHERE name = 'John?' AND id = $1",
		},
		{
			name:     "Escaped quotes - handle ''",
			query:    "SELECT * FROM users WHERE name = 'O''Reilly' AND id = ?",
			dbType:   "postgres",
			expected: "SELECT * FROM users WHERE name = 'O''Reilly' AND id = $1",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := builder.ConvertPlaceholders(tt.query, tt.dbType)
			if result != tt.expected {
				t.Errorf("expected %q, got %q", tt.expected, result)
			}
		})
	}
}

func TestConvertPlaceholders(t *testing.T) {
    testConvertPlaceholders(t)
}
