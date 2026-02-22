<?php

namespace App\Services\Delivery;

use App\Models\EmailServer;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Mailer;

class EmailDeliveryService
{
    private TemplateRenderer $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Send an email using a dynamic server configuration.
     *
     * @param EmailServer $server
     * @param string $to Recipient(s) comma separated or single email
     * @param EmailTemplate|null $template
     * @param array $variables
     * @param array $attachments Array of paths ['/path/to/file.pdf']
     * @return bool
     */
    public function send(EmailServer $server, string $to, ?EmailTemplate $template, array $variables = [], array $attachments = []): bool
    {
        try {
            // 1. Configure Transport dynamically
            // We use the Symfony Mailer Transport Factory directly to avoid modifying global config if possible,
            // or we can swap the mailer instance. 
            // Swapping global config is easier in Laravel for standard Mailable usage, but creating a transport is safer for isolation.
            
            $transport = $this->createTransport($server);
            $mailer = new Mailer($transport);

            // 2. Render Content
            $subject = 'Report Delivery';
            $bodyHtml = '<p>Please find the attached report.</p>';
            $bodyText = 'Please find the attached report.';

            if ($template) {
                $rendered = $this->renderer->render($template, $variables);
                $subject = $rendered['subject'];
                $bodyHtml = $rendered['body_html'];
                $bodyText = $rendered['body_text'] ?? strip_tags($bodyHtml);
            }

            // 3. Construct Message
            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address($server->from_address, $server->from_name ?? 'RBDB System'))
                ->to(...array_map('trim', explode(',', $to)))
                ->subject($subject)
                ->html($bodyHtml)
                ->text($bodyText);

            // 4. Attachments
            foreach ($attachments as $filePath) {
                if (file_exists($filePath)) {
                    $email->attachFromPath($filePath);
                }
            }

            // 5. Send
            $mailer->send($email);

            Log::info("Email Sent Success: {$server->name} to {$to}");
            return true;

        } catch (\Exception $e) {
            Log::error("Email Delivery Failed: {$e->getMessage()}", [
                'server' => $server->name,
                'recipient' => $to
            ]);
            return false;
        }
    }

    /**
     * Create Symfony Transport from EmailServer model.
     */
    private function createTransport(EmailServer $server): TransportInterface
    {
        // Scheme: smtp, smtps, etc.
        // DSN format: smtp://user:pass@host:port?encryption=tls
        
        $scheme = $server->encryption === 'ssl' ? 'smtps' : 'smtp';
        
        // Handle encryption query param
        // Note: For 'tls', Laravel/Symfony often uses standard SMTP with checks.
        
        $factory = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory();
        
        $host = $server->host;
        $port = $server->port;
        $user = $server->username;
        $pass = $server->password; // Decrypted via accessor

        // Construct DSN manually if needed, or instantiate Transport directly
        // Direct instantiation is clearer
        
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            $host,
            $port,
            $server->encryption === 'tls' // logic for TLS defaults depending on symfony version, usually implicit on 587
        );

        if ($user && $pass) {
            $transport->setUsername($user);
            $transport->setPassword($pass);
        }

        return $transport;
    }

    public function verifyConnection(EmailServer $server): bool
    {
        try {
            $transport = $this->createTransport($server);
            $transport->start(); // Throws on connection failure
            $transport->stop();
            return true;
        } catch (\Exception $e) {
            Log::error("SMTP Verification Failed: {$e->getMessage()}");
            return false;
        }
    }
}
