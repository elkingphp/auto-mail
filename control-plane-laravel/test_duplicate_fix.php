<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$compiler = new \App\Services\VisualQueryCompiler();

// Test case: Simulating the exact scenario from the error
$ast = [
    'table' => 'users',
    'columns' => ['users.name', 'users.email', 'reports.name', 'reports.type'],
    'joins' => [
        [
            'table' => 'reports',
            'type' => 'INNER',
            'on' => [
                ['col1' => 'users.id', 'col2' => 'reports.created_by', 'operator' => '=']
            ]
        ]
    ],
    'aggregates' => [
        ['type' => 'RENAME', 'column' => 'users.name', 'alias' => 'username'],
        ['type' => 'RENAME', 'column' => 'users.email', 'alias' => 'email'],
        ['type' => 'RENAME', 'column' => 'reports.name', 'alias' => 'reportname'],
        ['type' => 'RENAME', 'column' => 'reports.type', 'alias' => 'type']
    ],
    'group_by' => ['users.name', 'users.email', 'reports.name', 'reports.type']
];

echo "Testing Visual Query Compiler - Duplicate Columns Fix...\n\n";

try {
    $sql = $compiler->compile($ast, 'mysql');
    echo "Generated SQL:\n";
    echo $sql . "\n\n";
    
    // Count occurrences of column names
    $nameCount = substr_count($sql, '`name`');
    $emailCount = substr_count($sql, '`email`');
    $typeCount = substr_count($sql, '`type`');
    
    echo "Column occurrence check:\n";
    echo "  `name`: {$nameCount} times\n";
    echo "  `email`: {$emailCount} times\n";
    echo "  `type`: {$typeCount} times\n\n";
    
    // Check for duplicates in SELECT clause
    $selectPart = substr($sql, 7, strpos($sql, ' FROM ') - 7);
    $columns = array_map('trim', explode(',', $selectPart));
    
    echo "Columns in SELECT:\n";
    foreach ($columns as $i => $col) {
        echo "  " . ($i + 1) . ". {$col}\n";
    }
    echo "\n";
    
    // Verify no duplicates
    $uniqueColumns = array_unique($columns);
    if (count($columns) === count($uniqueColumns)) {
        echo "✅ PASSED: No duplicate columns in SELECT\n";
    } else {
        echo "❌ FAILED: Duplicate columns detected\n";
        echo "Duplicates: " . implode(', ', array_diff_assoc($columns, $uniqueColumns)) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
