<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create Email Template
$template = \App\Models\EmailTemplate::create([
    'name' => 'Report Delivery Template',
    'subject' => 'RBDB Report: {{report_name}}',
    'body_html' => '<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RBDB Report Delivery</h1>
    </div>
    <div class="content">
        <h2>{{report_name}}</h2>
        <p>Dear User,</p>
        <p>Please find attached the report generated on <strong>{{execution_date}}</strong>.</p>
        <p><strong>Execution ID:</strong> {{execution_id}}</p>
        <p><strong>Status:</strong> {{status}}</p>
        <p>The report file is attached to this email.</p>
    </div>
    <div class="footer">
        <p>This is an automated message from RBDB System.</p>
        <p>&copy; 2026 Egypt Post - All Rights Reserved</p>
    </div>
</body>
</html>',
    'is_active' => true
]);

echo "✅ Email Template Created\n";
echo "ID: {$template->id}\n";
echo "Name: {$template->name}\n";
echo "Subject: {$template->subject}\n";
