<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create Email Server entry for MailHog
$emailServer = \App\Models\EmailServer::create([
    'name' => 'MailHog Test SMTP',
    'driver' => 'smtp',
    'host' => 'mailhog',
    'port' => 1025,
    'username' => null,  // MailHog doesn't require auth
    'password' => null,
    'encryption' => null,  // No encryption for MailHog
    'from_address' => 'noreply@rbdb.test',
    'from_name' => 'RBDB System',
    'is_active' => true
]);

echo "✅ Email Server Created\n";
echo "ID: {$emailServer->id}\n";
echo "Name: {$emailServer->name}\n";
echo "Host: {$emailServer->host}:{$emailServer->port}\n";
echo "From: {$emailServer->from_name} <{$emailServer->from_address}>\n";
