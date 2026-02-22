<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 1. Get or create a report
$report = \App\Models\Report::first();
if (!$report) {
    echo "No report found. Creating dummy report...\n";
    $dataSource = \App\Models\DataSource::first();
    $report = \App\Models\Report::create([
        'name' => 'FTP Test Report',
        'data_source_id' => $dataSource->id,
        'definition_mode' => 'sql_native',
        'sql_query' => 'SELECT 1 as test',
        'is_active' => true
    ]);
}

echo "Using Report: {$report->id} - {$report->name}\n";

// 2. Get the Docker FTP server
$ftpServer = \App\Models\FtpServer::where('name', 'Docker Test FTP')->first();
echo "Using FTP Server: {$ftpServer->id} - {$ftpServer->name}\n";

// 3. Create a schedule with FTP delivery
$schedule = \App\Models\Schedule::create([
    'report_id' => $report->id,
    'frequency' => 'Daily',
    'time' => '08:00:00',
    'is_active' => true,
    'delivery_mode' => 'ftp'
]);

$schedule->ftpServers()->sync([$ftpServer->id]);
echo "Schedule Created: {$schedule->id}\n";

// 4. Create a dummy output file
$outputPath = storage_path('app/ftp_test_report_' . time() . '.txt');
file_put_contents($outputPath, "This is a test report generated at " . date('Y-m-d H:i:s') . "\nContent: FTP Delivery Test\n");
echo "Test file created: {$outputPath}\n";
echo "File size: " . filesize($outputPath) . " bytes\n";

// 5. Create execution
$execution = \App\Models\Execution::create([
    'report_id' => $report->id,
    'schedule_id' => $schedule->id,
    'status' => 'completed',
    'started_at' => now(),
    'finished_at' => now(),
    'output_path' => $outputPath
]);

echo "Execution Created: {$execution->id}\n";

// 6. Trigger delivery
echo "\n--- Starting FTP Delivery ---\n";
$executor = app(\App\Services\ScheduleExecutor::class);
$results = $executor->executeDelivery(
    $schedule->id,
    $outputPath,
    $execution->id,
    ['test' => true]
);

echo "\n--- Delivery Results ---\n";
print_r($results);

if (!empty($results['ftp'])) {
    echo "\nFTP Delivery Status:\n";
    foreach ($results['ftp'] as $serverId => $status) {
        echo "  Server {$serverId}: {$status}\n";
    }
}

if (!empty($results['errors'])) {
    echo "\nErrors:\n";
    foreach ($results['errors'] as $error) {
        echo "  - {$error}\n";
    }
}

echo "\nTest Complete.\n";
