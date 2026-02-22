<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$compiler = new \App\Services\VisualQueryCompiler();

// Test case: Join users and reports tables (both have 'name' column)
$ast = [
    'table' => 'users',
    'columns' => ['name', 'email'],
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
        ['type' => 'COUNT', 'column' => 'users.name', 'alias' => 'Username'],
        ['type' => 'COUNT', 'column' => 'users.email', 'alias' => 'Email'],
        ['type' => 'COUNT', 'column' => 'reports.name', 'alias' => 'ReportName'],
        ['type' => 'COUNT', 'column' => 'reports.type', 'alias' => 'ReportType']
    ],
    'group_by' => ['users.name', 'users.email', 'reports.name', 'reports.type']
];

echo "Testing Visual Query Compiler with duplicate column names...\n\n";

try {
    $sql = $compiler->compile($ast, 'mysql');
    echo "Generated SQL:\n";
    echo $sql . "\n\n";
    
    echo "Checking for duplicate column names...\n";
    if (substr_count($sql, '`name`') > 1 && strpos($sql, ' AS ') === false) {
        echo "❌ FAILED: Duplicate column names detected without aliases\n";
    } else {
        echo "✅ PASSED: Column names are unique\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
