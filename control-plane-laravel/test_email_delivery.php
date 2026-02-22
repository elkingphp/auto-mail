<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SMTP Email Delivery Test ===\n\n";

// 1. Get or create a report
$report = \App\Models\Report::first();
if (!$report) {
    echo "Creating dummy report...\n";
    $dataSource = \App\Models\DataSource::first();
    $report = \App\Models\Report::create([
        'name' => 'Email Test Report',
        'data_source_id' => $dataSource->id,
        'definition_mode' => 'sql_native',
        'sql_query' => 'SELECT 1 as test',
        'is_active' => true
    ]);
}
echo "✅ Using Report: {$report->id} - {$report->name}\n";

// 2. Get the MailHog email server
$emailServer = \App\Models\EmailServer::where('name', 'MailHog Test SMTP')->first();
echo "✅ Using Email Server: {$emailServer->id} - {$emailServer->name}\n";

// 3. Get the email template
$template = \App\Models\EmailTemplate::where('name', 'Report Delivery Template')->first();
echo "✅ Using Template: {$template->id} - {$template->name}\n";

// 4. Create a schedule with Email delivery
$schedule = \App\Models\Schedule::create([
    'report_id' => $report->id,
    'frequency' => 'Daily',
    'time' => '08:00:00',
    'is_active' => true,
    'delivery_mode' => 'email',
    'email_server_id' => $emailServer->id,
    'email_template_id' => $template->id,
    'recipients' => 'test@example.com,admin@rbdb.test'
]);
echo "✅ Schedule Created: {$schedule->id}\n";

// 5. Create a test output file
$outputPath = storage_path('app/email_test_report_' . time() . '.txt');
$reportContent = "RBDB Test Report\n";
$reportContent .= "Generated at: " . date('Y-m-d H:i:s') . "\n";
$reportContent .= "Report ID: {$report->id}\n";
$reportContent .= "Report Name: {$report->name}\n";
$reportContent .= "\nTest Data:\n";
$reportContent .= "ID | Name | Value\n";
$reportContent .= "1  | Test | 12345\n";
$reportContent .= "2  | Demo | 67890\n";

file_put_contents($outputPath, $reportContent);
echo "✅ Test file created: {$outputPath}\n";
echo "   File size: " . filesize($outputPath) . " bytes\n";

// 6. Create execution
$execution = \App\Models\Execution::create([
    'report_id' => $report->id,
    'schedule_id' => $schedule->id,
    'status' => 'completed',
    'started_at' => now(),
    'finished_at' => now(),
    'output_path' => $outputPath
]);
echo "✅ Execution Created: {$execution->id}\n";

// 7. Trigger email delivery
echo "\n--- Starting Email Delivery ---\n";
$executor = app(\App\Services\ScheduleExecutor::class);

try {
    $results = $executor->executeDelivery(
        $schedule->id,
        $outputPath,
        $execution->id,
        [
            'report_name' => $report->name,
            'execution_date' => now()->format('Y-m-d H:i:s'),
            'execution_id' => $execution->id,
            'status' => 'Completed'
        ]
    );

    echo "\n--- Delivery Results ---\n";
    print_r($results);

    if (!empty($results['email']) && $results['email'] === 'success') {
        echo "\n✅ EMAIL DELIVERY SUCCESS\n";
        echo "\nNext steps:\n";
        echo "1. Check MailHog UI: http://localhost:8025\n";
        echo "2. Verify email was received\n";
        echo "3. Check email content and attachment\n";
    } else {
        echo "\n❌ EMAIL DELIVERY FAILED\n";
        if (!empty($results['errors'])) {
            echo "Errors:\n";
            foreach ($results['errors'] as $error) {
                echo "  - {$error}\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTest Complete.\n";
