<?php

use App\Models\Execution;
use App\Models\Report;
use App\Jobs\ExecuteReportJob;

// Find our test report
$report = Report::where('name', 'Test Native')->first();

if (!$report) {
    echo "Report not found!\n";
    exit(1);
}

// Ensure delivery mode is 'both' for full test
$report->update(['delivery_mode' => 'both']);

// Trigger execution
$execution = Execution::create([
    'report_id' => $report->id,
    'status' => 'pending',
    'triggered_by' => '019c3b8b-873a-718b-bcac-585de362e13c', // Admin user ID
    'parameters' => [],
    'notification_emails' => ['test-qa@post.gov.eg']
]);

echo "Created execution: " . $execution->id . "\n";

// Dispatch job
ExecuteReportJob::dispatch($execution->id);

echo "Dispatched ExecuteReportJob.\n";
