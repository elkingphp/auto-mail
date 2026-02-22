<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Email Delivery with SMTP Server Down ===\n\n";

// Get existing schedule
$schedule = \App\Models\Schedule::latest()->first();
echo "Using Schedule: {$schedule->id}\n";

// Create a test output file
$outputPath = storage_path('app/email_failure_test_' . time() . '.txt');
file_put_contents($outputPath, "Test content for failure scenario");
echo "Test file created: {$outputPath}\n";

// Create execution
$execution = \App\Models\Execution::create([
    'report_id' => $schedule->report_id,
    'schedule_id' => $schedule->id,
    'status' => 'completed',
    'started_at' => now(),
    'finished_at' => now(),
    'output_path' => $outputPath
]);
echo "Execution Created: {$execution->id}\n";

echo "\n--- Attempting Email Delivery (SMTP Down) ---\n";
$executor = app(\App\Services\ScheduleExecutor::class);

try {
    $results = $executor->executeDelivery(
        $schedule->id,
        $outputPath,
        $execution->id,
        [
            'report_name' => 'Test Report',
            'execution_date' => now()->format('Y-m-d H:i:s'),
            'execution_id' => $execution->id,
            'status' => 'Completed'
        ]
    );

    echo "\n--- Delivery Results ---\n";
    print_r($results);

    if (!empty($results['email']) && $results['email'] === 'failed') {
        echo "\n✅ FAILURE HANDLED CORRECTLY\n";
        echo "System did not crash, error was logged\n";
    } else {
        echo "\n❌ UNEXPECTED RESULT\n";
    }

    if (!empty($results['errors'])) {
        echo "\nErrors logged:\n";
        foreach ($results['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }

} catch (\Exception $e) {
    echo "\n❌ SYSTEM CRASHED: " . $e->getMessage() . "\n";
    echo "This should NOT happen - delivery failures should be isolated\n";
}

echo "\nTest Complete.\n";
