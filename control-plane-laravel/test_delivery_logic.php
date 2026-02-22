$scheduleID = \App\Models\Schedule::latest()->value('id');
if (!$scheduleID) {
    echo "No schedule found.\n";
    exit;
}

$schedule = \App\Models\Schedule::find($scheduleID);
$emailServerID = \App\Models\EmailServer::latest()->value('id');
$ftpServerID = \App\Models\FtpServer::latest()->value('id');
$templateID = \App\Models\EmailTemplate::latest()->value('id');

$schedule->update([
    'delivery_mode' => 'email_and_ftp',
    'email_server_id' => $emailServerID,
    'email_template_id' => $templateID,
    'recipients' => 'test@example.com'
]);

$schedule->ftpServers()->sync([$ftpServerID]);

$outputPath = storage_path('app/temp_report.txt');
file_put_contents($outputPath, "This is a dummy report content.");

$execution = \App\Models\Execution::create([
    'report_id' => $schedule->report_id,
    'schedule_id' => $schedule->id,
    'status' => 'completed',
    'started_at' => now(),
    'finished_at' => now(),
    'output_path' => $outputPath
]);

try {
    $executor = app(\App\Services\ScheduleExecutor::class);
    $results = $executor->executeDelivery(
        $schedule->id,
        $outputPath,
        $execution->id,
        ['test' => true]
    );

    echo "Delivery Results:\n";
    print_r($results);
} catch (\Exception $e) {
    echo "Executor Failed: " . $e->getMessage() . "\n";
}
