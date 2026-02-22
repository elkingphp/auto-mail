<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SqlValidatorService
{
    /**
     * Dangerous SQL keywords that should be blocked
     */
    private const DANGEROUS_KEYWORDS = [
        'DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 
        'INSERT', 'UPDATE', 'GRANT', 'REVOKE', 'EXEC',
        'EXECUTE', 'CALL', 'DECLARE', 'MERGE', 'REPLACE'
    ];

    /**
     * Validate SQL query for security
     * 
     * @param string $sql
     * @throws \Exception
     */
    public function validateSql(string $sql): void
    {
        // Remove comments
        $cleanSql = $this->removeComments($sql);
        
        // Check for dangerous keywords
        foreach (self::DANGEROUS_KEYWORDS as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $cleanSql)) {
                throw new \Exception("Dangerous SQL keyword detected: {$keyword}. Only SELECT queries are allowed.");
            }
        }
        
        // Must start with SELECT
        if (!preg_match('/^\s*SELECT\s+/i', trim($cleanSql))) {
            throw new \Exception("Only SELECT queries are allowed.");
        }
        
        // Check for multiple statements (SQL injection attempt)
        if (preg_match('/;\s*\w+/i', $cleanSql)) {
            throw new \Exception("Multiple SQL statements are not allowed.");
        }
    }

    /**
     * Validate table name against whitelist
     * 
     * @param string $tableName
     * @param string $connectionName
     * @throws \Exception
     */
    public function validateTableName(string $tableName, string $connectionName): void
    {
        $allowedTables = $this->getAllowedTables($connectionName);
        
        if (!in_array($tableName, $allowedTables)) {
            throw new \Exception("Table '{$tableName}' is not allowed or does not exist.");
        }
    }

    /**
     * Validate column name against whitelist
     * 
     * @param string $tableName
     * @param string $columnName
     * @param string $connectionName
     * @throws \Exception
     */
    public function validateColumnName(string $tableName, string $columnName, string $connectionName): void
    {
        $allowedColumns = $this->getAllowedColumns($tableName, $connectionName);
        
        if (!in_array($columnName, $allowedColumns)) {
            throw new \Exception("Column '{$columnName}' is not allowed in table '{$tableName}'.");
        }
    }

    /**
     * Get allowed tables from database schema
     * 
     * @param string $connectionName
     * @return array
     */
    private function getAllowedTables(string $connectionName): array
    {
        try {
            $connection = DB::connection($connectionName);
            $driver = $connection->getDriverName();
            
            switch ($driver) {
                case 'mysql':
                    $tables = $connection->select('SHOW TABLES');
                    $key = 'Tables_in_' . $connection->getDatabaseName();
                    return array_map(fn($t) => $t->$key, $tables);
                    
                case 'pgsql':
                    $tables = $connection->select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
                    return array_map(fn($t) => $t->tablename, $tables);
                    
                case 'oracle':
                    $tables = $connection->select("SELECT table_name FROM user_tables");
                    return array_map(fn($t) => strtolower($t->table_name), $tables);
                    
                case 'sqlsrv':
                    $tables = $connection->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
                    return array_map(fn($t) => $t->TABLE_NAME, $tables);
                    
                default:
                    return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get allowed columns for a table
     * 
     * @param string $tableName
     * @param string $connectionName
     * @return array
     */
    private function getAllowedColumns(string $tableName, string $connectionName): array
    {
        try {
            $connection = DB::connection($connectionName);
            $driver = $connection->getDriverName();
            
            switch ($driver) {
                case 'mysql':
                    $columns = $connection->select("SHOW COLUMNS FROM {$tableName}");
                    return array_map(fn($c) => $c->Field, $columns);
                    
                case 'pgsql':
                    $columns = $connection->select("SELECT column_name FROM information_schema.columns WHERE table_name = ?", [$tableName]);
                    return array_map(fn($c) => $c->column_name, $columns);
                    
                case 'oracle':
                    $columns = $connection->select("SELECT column_name FROM user_tab_columns WHERE table_name = ?", [strtoupper($tableName)]);
                    return array_map(fn($c) => strtolower($c->column_name), $columns);
                    
                case 'sqlsrv':
                    $columns = $connection->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?", [$tableName]);
                    return array_map(fn($c) => $c->COLUMN_NAME, $columns);
                    
                default:
                    return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Remove SQL comments
     * 
     * @param string $sql
     * @return string
     */
    private function removeComments(string $sql): string
    {
        // Remove single-line comments (-- and #)
        $sql = preg_replace('/--[^\n]*/', '', $sql);
        $sql = preg_replace('/#[^\n]*/', '', $sql);
        
        // Remove multi-line comments (/* */)
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        return $sql;
    }

    /**
     * Sanitize SQL query by wrapping in subquery with LIMIT
     * 
     * @param string $sql
     * @param string $driver
     * @param int $limit
     * @return string
     */
    public function sanitizeAndLimit(string $sql, string $driver, int $limit = 50): string
    {
        // Validate first
        $this->validateSql($sql);
        
        // Check if already has limit
        if (preg_match('/\bLIMIT\s+\d+/i', $sql) || 
            preg_match('/\bFETCH\s+FIRST\s+\d+/i', $sql) ||
            preg_match('/\bROWNUM\s*<=?\s*\d+/i', $sql)) {
            return $sql;
        }
        
        // Add limit based on driver
        switch ($driver) {
            case 'oracle':
                return "SELECT * FROM ({$sql}) WHERE ROWNUM <= {$limit}";
                
            case 'sqlsrv':
                // Check if has ORDER BY for OFFSET FETCH
                if (preg_match('/\bORDER\s+BY\b/i', $sql)) {
                    return "{$sql} OFFSET 0 ROWS FETCH FIRST {$limit} ROWS ONLY";
                } else {
                    return "SELECT TOP {$limit} * FROM ({$sql}) AS limited_query";
                }
                
            default: // mysql, pgsql
                return "SELECT * FROM ({$sql}) AS limited_query LIMIT {$limit}";
        }
    }
}
