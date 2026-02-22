<?php

namespace App\Services;

class VisualQueryCompiler
{
    /**
     * Compile a visual definition AST into a SQL string.
     *
     * @param array $ast
     * @param string $driver
     * @param string|null $rlsDepartmentId
     * @return string
     * @throws \InvalidArgumentException
     */
    public function compile(array $ast, string $driver = 'mysql', ?string $rlsDepartmentId = null): string
    {
        // 1. Basic Validation
        if (empty($ast['table'])) {
            throw new \InvalidArgumentException("Table is required in visual definition.");
        }

        $table = $this->wrap($ast['table'], $driver);
        
        
        // 2. Selects (Columns + Aggregates)
        $selects = [];
        
        // Check if we have joins to determine if we need table prefixes
        $hasJoins = !empty($ast['joins']) && is_array($ast['joins']);
        
        // Build a list of columns that are in aggregates to avoid duplicates
        $aggregateColumns = [];
        if (!empty($ast['aggregates']) && is_array($ast['aggregates'])) {
            foreach ($ast['aggregates'] as $agg) {
                if (!empty($agg['column'])) {
                    // Store the column name (without table prefix for comparison)
                    $colName = $agg['column'];
                    if (strpos($colName, '.') !== false) {
                        $colName = substr($colName, strpos($colName, '.') + 1);
                    }
                    $aggregateColumns[] = $colName;
                }
            }
        }
        
        // Add regular columns (skip if already in aggregates)
        if (isset($ast['columns']) && is_array($ast['columns'])) {
            foreach ($ast['columns'] as $col) {
                if ($col === '*') {
                    $selects[] = '*';
                    continue;
                }
                
                // Get column name without table prefix for comparison
                $colName = $col;
                if (strpos($colName, '.') !== false) {
                    $colName = substr($colName, strpos($colName, '.') + 1);
                }
                
                // Skip if this column is already in aggregates
                if (in_array($colName, $aggregateColumns)) {
                    continue;
                }
                
                // If column already has table prefix (contains .), use as-is
                // Otherwise, add main table prefix when joins exist
                if (strpos($col, '.') !== false) {
                    if ($hasJoins) {
                        // Add alias to avoid duplicates
                        $wrappedCol = $this->wrap($col, $driver);
                        $tableName = substr($col, 0, strpos($col, '.'));
                        $alias = $this->wrap($this->sanitizeAlias($tableName) . ucfirst($colName), $driver);
                        $selects[] = "{$wrappedCol} AS {$alias}";
                    } else {
                        $selects[] = $this->wrap($col, $driver);
                    }
                } elseif ($hasJoins) {
                    // Add table prefix and alias to avoid duplicates
                    $wrappedCol = $this->wrap($ast['table'] . '.' . $col, $driver);
                    $alias = $this->wrap($this->sanitizeAlias($ast['table']) . ucfirst($col), $driver);
                    $selects[] = "{$wrappedCol} AS {$alias}";
                } else {
                    $selects[] = $this->wrap($col, $driver);
                }
            }
        }

        // Add aggregates
        if (!empty($ast['aggregates']) && is_array($ast['aggregates'])) {
            foreach ($ast['aggregates'] as $agg) {
                if (empty($agg['type']) || empty($agg['column'])) continue;
                
                $type = strtoupper($agg['type']);
                
                // Handle column with potential table prefix
                if ($agg['column'] === '*') {
                    $col = '*';
                } elseif (strpos($agg['column'], '.') !== false) {
                    $col = $this->wrap($agg['column'], $driver);
                } else {
                    // For aggregates without table prefix, add it if joins exist
                    if ($hasJoins && $type !== 'RENAME') {
                        $col = $this->wrap($ast['table'] . '.' . $agg['column'], $driver);
                    } else {
                        $col = $this->wrap($agg['column'], $driver);
                    }
                }
                
                if (!empty($agg['alias'])) {
                    $alias = $this->wrap($agg['alias'], $driver);
                } else {
                    $cleanCol = $agg['column'] === '*' ? 'all' : $agg['column'];
                    // Remove table prefix from alias if present
                    if (strpos($cleanCol, '.') !== false) {
                        $cleanCol = substr($cleanCol, strpos($cleanCol, '.') + 1);
                    }
                    $alias = $this->wrap(strtolower($type . '_' . $cleanCol), $driver);
                }
                
                if ($type === 'RENAME') {
                     $selects[] = "{$col} AS {$alias}";
                } else {
                     $selects[] = "{$type}({$col}) AS {$alias}";
                }
            }
        }

        if (empty($selects)) {
            $selects = ['*'];
        }
        
        $selectStr = implode(', ', $selects);

        // 3. Start building SQL
        $sql = "SELECT {$selectStr} FROM {$table}";

        // 3.5 Joins
        if (!empty($ast['joins']) && is_array($ast['joins'])) {
            foreach ($ast['joins'] as $join) {
                if (empty($join['table']) || empty($join['on'])) continue;

                $joinTable = $this->wrap($join['table'], $driver);
                $type = strtoupper($join['type'] ?? 'INNER');
                
                // Validate join type to avoid injection
                if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS'])) {
                    $type = 'INNER';
                }

                $conditions = [];
                foreach ($join['on'] as $condition) {
                    if (empty($condition['col1']) || empty($condition['col2'])) continue;
                    
                    $col1 = $this->wrap($condition['col1'], $driver);
                    $col2 = $this->wrap($condition['col2'], $driver);
                    $op = $condition['operator'] ?? '=';
                    
                    if (!in_array($op, ['=', '!=', '<', '>'])) $op = '=';
                    
                    $conditions[] = "{$col1} {$op} {$col2}";
                }

                if (!empty($conditions)) {
                    $onStr = implode(' AND ', $conditions);
                    $sql .= " {$type} JOIN {$joinTable} ON {$onStr}";
                }
            }
        }

        // 4. Filters (WHERE)
        if (!empty($ast['filters']) && is_array($ast['filters'])) {
            $wheres = [];
            foreach ($ast['filters'] as $filter) {
                if (empty($filter['column']) || empty($filter['operator'])) {
                    continue;
                }
                
                $col = $this->wrap($filter['column'], $driver);
                $op = strtoupper($filter['operator']);
                $val = $filter['value'] ?? null;

                // Basic sanitization/validation of operator
                $validOps = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN', 'IS NULL', 'IS NOT NULL'];
                if (!in_array($op, $validOps)) {
                    continue; // Skip invalid operators
                }

                if ($op === 'IN' && is_array($val)) {
                    $vals = array_map(fn($v) => $this->quote($v), $val);
                    $valStr = '(' . implode(',', $vals) . ')';
                    $wheres[] = "{$col} IN {$valStr}";
                } elseif ($op === 'IS NULL' || $op === 'IS NOT NULL') {
                    $wheres[] = "{$col} {$op}";
                } else {
                    $valStr = $this->quote($val);
                    $wheres[] = "{$col} {$op} {$valStr}";
                }
            }
            
            if (count($wheres) > 0) {
                $sql .= " WHERE " . implode(' AND ', $wheres);
            }
        }

        // RLS Injection
        if ($rlsDepartmentId) {
            $rlsFilter = $this->wrap($ast['table'] . '.department_id', $driver) . " = " . $this->quote($rlsDepartmentId);
            if (strpos($sql, ' WHERE ') !== false) {
                $sql = str_replace(' WHERE ', ' WHERE ' . $rlsFilter . ' AND ', $sql);
            } else {
                // If it already has JOINs but no WHERE, find where to insert
                if (strpos($sql, ' JOIN ') !== false) {
                    // Try to insert after last join
                    $sql .= " WHERE " . $rlsFilter;
                } else if (strpos($sql, ' GROUP BY ') !== false) {
                    $sql = str_replace(' GROUP BY ', ' WHERE ' . $rlsFilter . ' GROUP BY ', $sql);
                } else if (strpos($sql, ' ORDER BY ') !== false) {
                    $sql = str_replace(' ORDER BY ', ' WHERE ' . $rlsFilter . ' ORDER BY ', $sql);
                } else {
                    $sql .= " WHERE " . $rlsFilter;
                }
            }
        }

        // 5. Group By
        if (!empty($ast['group_by']) && is_array($ast['group_by'])) {
            $groups = array_map(fn($c) => $this->wrap($c, $driver), $ast['group_by']);
            $sql .= " GROUP BY " . implode(', ', $groups);
        }

        // 6. Order By (Optional, simplistic)
        if (!empty($ast['order_by']) && is_array($ast['order_by'])) {
            $orders = [];
            foreach ($ast['order_by'] as $order) {
                $col = $this->wrap($order['column'], $driver);
                $dir = strtoupper($order['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
                $orders[] = "{$col} {$dir}";
            }
            if (!empty($orders)) {
                $sql .= " ORDER BY " . implode(', ', $orders);
            }
        }

        return $sql;
    }

    /**
     * Wrap identifiers (tables, columns) based on driver.
     */
    private function wrap(string $value, string $driver): string
    {
        if ($value === '*') return '*';
        
        // Remove characters that aren't alphanumeric, underscore, or dot
        $value = preg_replace('/[^a-zA-Z0-9_.]/', '', $value);

        $parts = explode('.', $value);
        $wrappedParts = [];

        foreach ($parts as $part) {
            if ($part === '*') {
                $wrappedParts[] = '*';
                continue;
            }
            
            switch ($driver) {
                case 'mysql':
                    $wrappedParts[] = "`{$part}`";
                    break;
                case 'pgsql':
                case 'oracle':
                    $wrappedParts[] = "\"{$part}\"";
                    break;
                default:
                    $wrappedParts[] = $part;
            }
        }
        
        return implode('.', $wrappedParts);
    }

    /**
     * Quote values for SQL string.
     */
    private function quote($value)
    {
        if (is_null($value)) return 'NULL';
        if (is_numeric($value)) return $value;
        if (is_bool($value)) return $value ? 1 : 0;
        
        // Escape single quotes
        return "'" . addslashes((string)$value) . "'";
    }

    /**
     * Sanitize table name for use in alias (remove special chars, keep alphanumeric).
     */
    private function sanitizeAlias(string $tableName): string
    {
        // Remove backticks, quotes, and special characters
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        // Capitalize first letter for readability
        return ucfirst($clean);
    }
}
