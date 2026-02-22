<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Seeding Default Email Templates ===\n\n";

$templates = [
    [
        'name' => 'Default Report Delivery',
        'subject' => 'Report Ready: {{report_name}}',
        'body_html' => '<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #4F46E5; color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px 20px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">Report Delivery</h1>
        </div>
        <div class="content">
            <h2 style="color: #4F46E5;">{{report_name}}</h2>
            <p>Dear User,</p>
            <p>Your scheduled report has been generated successfully and is attached to this email.</p>
            <p><strong>Generated:</strong> {{execution_date}}</p>
            <p><strong>Execution ID:</strong> {{execution_id}}</p>
            <p><strong>Status:</strong> {{status}}</p>
            <p>Please find the report file attached to this email.</p>
        </div>
        <div class="footer">
            <p>This is an automated message from RBDB System.</p>
            <p>&copy; 2026 Egypt Post - All Rights Reserved</p>
        </div>
    </div>
</body>
</html>',
        'is_active' => true
    ],
    [
        'name' => 'Daily Summary Report',
        'subject' => 'Daily Summary - {{execution_date}}',
        'body_html' => '<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .footer { background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Daily Summary Report</h1>
        <p>{{execution_date}}</p>
    </div>
    <div class="content">
        <h2>{{report_name}}</h2>
        <p>Hello,</p>
        <p>Here is your daily summary report. The detailed data is attached to this email.</p>
        <p><strong>Report ID:</strong> {{execution_id}}</p>
        <p>Please review the attached file for complete details.</p>
    </div>
    <div class="footer">
        <p>Automated Daily Report | RBDB System</p>
    </div>
</body>
</html>',
        'is_active' => true
    ],
    [
        'name' => 'Failure Notification',
        'subject' => '⚠️ Report Generation Failed: {{report_name}}',
        'body_html' => '<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #DC2626; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .alert { background: #FEE2E2; border-left: 4px solid #DC2626; padding: 15px; margin: 20px 0; }
        .footer { background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚠️ Report Generation Failed</h1>
    </div>
    <div class="content">
        <h2>{{report_name}}</h2>
        <div class="alert">
            <strong>Alert:</strong> The scheduled report generation has failed.
        </div>
        <p><strong>Execution ID:</strong> {{execution_id}}</p>
        <p><strong>Time:</strong> {{execution_date}}</p>
        <p><strong>Status:</strong> {{status}}</p>
        <p>Please check the system logs for more details or contact your system administrator.</p>
    </div>
    <div class="footer">
        <p>RBDB System Alert | Egypt Post</p>
    </div>
</body>
</html>',
        'is_active' => true
    ],
    [
        'name' => 'Executive Summary',
        'subject' => 'Executive Report: {{report_name}}',
        'body_html' => '<html>
<head>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; line-height: 1.6; color: #1f2937; background: #f9fafb; }
        .container { max-width: 650px; margin: 0 auto; background: white; }
        .header { background: #1e293b; color: white; padding: 40px 30px; }
        .content { padding: 40px 30px; }
        .highlight { background: #f0f9ff; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0; }
        .footer { background: #1e293b; color: #94a3b8; padding: 20px 30px; text-align: center; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">Executive Report</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.8;">{{execution_date}}</p>
        </div>
        <div class="content">
            <h2 style="color: #1e293b; margin-top: 0;">{{report_name}}</h2>
            <p>Dear Executive,</p>
            <div class="highlight">
                <p style="margin: 0;"><strong>Report Status:</strong> {{status}}</p>
                <p style="margin: 5px 0 0 0;"><strong>Reference:</strong> {{execution_id}}</p>
            </div>
            <p>The requested executive report has been generated and is attached to this email for your review.</p>
            <p>For any questions or clarifications, please contact the reporting team.</p>
        </div>
        <div class="footer">
            <p style="margin: 0;">Egypt Post | Business Intelligence Division</p>
            <p style="margin: 5px 0 0 0;">This is an automated system message</p>
        </div>
    </div>
</body>
</html>',
        'is_active' => true
    ]
];

$created = 0;
foreach ($templates as $templateData) {
    try {
        // Check if template already exists
        $existing = \App\Models\EmailTemplate::where('name', $templateData['name'])->first();
        if ($existing) {
            echo "⏭️  Template '{$templateData['name']}' already exists (ID: {$existing->id})\n";
            continue;
        }

        $template = \App\Models\EmailTemplate::create($templateData);
        echo "✅ Created: {$template->name} (ID: {$template->id})\n";
        $created++;
    } catch (\Exception $e) {
        echo "❌ Failed to create '{$templateData['name']}': " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Created: {$created} new templates\n";
echo "Total templates in system: " . \App\Models\EmailTemplate::count() . "\n";
echo "\nDone!\n";
