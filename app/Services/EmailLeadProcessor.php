<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Client;
use PhpImap\Mailbox;
use PhpImap\IncomingMail;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\AddressHeader;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailLeadProcessor
{
    private Mailbox $mailbox;
    private HtmlConverter $htmlConverter;

    public function __construct()
    {
        $config = config('services.imap');

        $connectionString = sprintf(
            '{%s:%d/imap/%s/novalidate-cert}%s',
            $config['host'],
            $config['port'],
            $config['encryption'],
            $config['default_folder']
        );

        $this->mailbox = new Mailbox(
            $connectionString,
            $config['username'],
            $config['password']
        );

        $this->htmlConverter = new HtmlConverter();
    }

    public function processNewEmails(): array
    {
        $processed = [];

        try {
            // Get the raw IMAP stream to avoid encoding issues
            $imapStream = $this->mailbox->getImapStream();

            // Use native PHP IMAP functions to avoid the encoding issue
            $allMails = imap_search($imapStream, 'ALL', SE_UID);
            $allMailIds = $allMails ? $allMails : [];
            Log::info('Total emails in inbox: ' . count($allMailIds));
            echo "Total emails found: " . count($allMailIds) . "\n";

            $unseenMails = imap_search($imapStream, 'UNSEEN', SE_UID);
            $mailIds = $unseenMails ? $unseenMails : [];
            Log::info('Found ' . count($mailIds) . ' unread emails to process');
            echo "Unread emails found: " . count($mailIds) . "\n";

            foreach ($mailIds as $mailId) {
                try {
                    echo "Processing email ID: $mailId\n";

                    // Use raw PHP IMAP functions instead of the library
                    $header = imap_headerinfo($imapStream, $mailId);
                    $body = imap_body($imapStream, $mailId);

                    // Create a simple email object
                    $email = (object) [
                        'fromAddress' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                        'fromName' => $header->from[0]->personal ?? '',
                        'subject' => $header->subject ?? '',
                        'textPlain' => $body,
                        'textHtml' => '',
                        'date' => $header->date ?? date('r'),
                    ];

                    echo "Email from: " . $email->fromAddress . " Subject: " . $email->subject . "\n";
                    Log::info('Processing email from: ' . $email->fromAddress . ' Subject: ' . $email->subject);

                    $lead = $this->processEmail($email);

                    if ($lead) {
                        echo "✓ Lead created successfully\n";
                        $processed[] = $lead;
                        // Mark as read using raw IMAP
                        imap_setflag_full($imapStream, $mailId, "\\Seen", ST_UID);
                    } else {
                        echo "✗ No lead created - check processEmail() method\n";
                    }

                } catch (\Exception $e) {
                    echo "✗ Error processing email: " . $e->getMessage() . "\n";
                    Log::error('Error processing email ID ' . $mailId . ': ' . $e->getMessage());
                }
            }

            echo "Total leads processed: " . count($processed) . "\n";

        } catch (\Exception $e) {
            Log::error('Error connecting to mailbox: ' . $e->getMessage());
            throw $e;
        }

        return $processed;
    }

    private function processEmail($email): ?Lead
    {
        // Extract sender information
        $senderEmail = $email->fromAddress;
        $senderName = $email->fromName ?: $this->extractNameFromEmail($senderEmail);

        echo "  - Sender: $senderName ($senderEmail)\n";

        // Skip if no sender email
        if (!$senderEmail) {
            echo "  ✗ No sender email, skipping\n";
            Log::warning('Email without sender address, skipping');
            return null;
        }

        // Check if this is an automated email we should ignore
        if ($this->shouldIgnoreEmail($email)) {
            echo "  ✗ Automated email, skipping\n";
            Log::info('Ignoring automated email from: ' . $senderEmail);
            return null;
        }

        // Extract phone number from email content
        $phoneNumber = $this->extractPhoneNumber($email);
        echo "  - Phone extracted: " . ($phoneNumber ?: 'none') . "\n";

        // Get email content
        $message = $this->extractMessage($email);
        echo "  - Message length: " . strlen($message) . " characters\n";
        echo "  - Message preview: " . substr($message, 0, 100) . "...\n";

        // Determine lead source
        $source = $this->determineLeadSource($email);
        echo "  - Source: $source\n";

        // Find or use default client
        $client = $this->findClientForEmail($email) ?: $this->getDefaultClient();

        if (!$client) {
            echo "  ✗ No client found and no default client set\n";
            Log::error('No client found and no default client set');
            return null;
        }

        echo "  - Client: {$client->name} (ID: {$client->id})\n";

        // Check if lead already exists
        if ($this->leadExists($senderEmail, $phoneNumber, $client->id)) {
            echo "  ✗ Lead already exists\n";
            Log::info('Lead already exists for: ' . $senderEmail);
            return null;
        }

        echo "  ✓ All checks passed, creating lead\n";

        // Create lead with email metadata
        $lead = Lead::create([
            'client_id' => $client->id,
            'name' => $senderName,
            'email' => $senderEmail,
            'phone' => $phoneNumber,
            'message' => $message,
            'status' => 'new',
            'source' => $source,
            'email_subject' => $email->subject,
            'email_received_at' => $email->date ? date('Y-m-d H:i:s', strtotime($email->date)) : now(),
        ]);

        Log::info('Created new lead from email: ' . $senderEmail);

        return $lead;
    }

    private function extractNameFromEmail(string $email): string
    {
        // Extract name from email address (part before @)
        $name = explode('@', $email)[0];

        // Replace dots and underscores with spaces, then title case
        $name = str_replace(['.', '_', '-'], ' ', $name);
        return Str::title($name);
    }

    private function shouldIgnoreEmail($email): bool
    {
        $ignorePatterns = [
            'noreply',
            'no-reply',
            'donotreply',
            'mailer-daemon',
            'postmaster',
            'bounces',
            'notifications',
        ];

        $fromEmail = strtolower($email->fromAddress);
        $subject = strtolower($email->subject ?? '');

        foreach ($ignorePatterns as $pattern) {
            if (Str::contains($fromEmail, $pattern) || Str::contains($subject, $pattern)) {
                return true;
            }
        }

        // Remove the headerInfo check since it doesn't exist
        return false;
    }

    private function extractPhoneNumber($email): ?string
    {
        $text = $email->textPlain . ' ' . strip_tags($email->textHtml);

        // Common phone number patterns
        $patterns = [
            '/(\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})/', // US format
            '/(\d{3}[-.\s]?\d{3}[-.\s]?\d{4})/',       // Simple format
            '/(\+\d{1,3}[-.\s]?\d{3,4}[-.\s]?\d{3,4}[-.\s]?\d{4})/', // International
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Clean up the phone number
                return preg_replace('/[^\d+]/', '', $matches[1]);
            }
        }

        return null;
    }

    private function extractMessage($email): string
    {
        $message = '';

        // Our simple object only has textPlain
        if (!empty($email->textPlain)) {
            $message = $email->textPlain;
        }

        // Add subject if it's informative
        $subject = $email->subject ?? '';
        if ($subject && !Str::contains(strtolower($subject), ['contact', 'inquiry', 'message'])) {
            $message = "Subject: {$subject}\n\n" . $message;
        }

        // Limit message length
        return Str::limit($message, 1000);
    }

    private function determineLeadSource($email): string
    {
        $subject = strtolower($email->subject ?? '');
        $fromEmail = strtolower($email->fromAddress);

        // Check for common form sources
        if (Str::contains($subject, ['contact form', 'website', 'inquiry'])) {
            return 'website';
        }

        if (Str::contains($fromEmail, ['facebook', 'instagram', 'linkedin', 'twitter'])) {
            return 'social';
        }

        return 'email';
    }

    private function findClientForEmail($email): ?Client
    {
        // Try to match client by sender domain or specific patterns
        $fromEmail = $email->fromAddress;
        $domain = explode('@', $fromEmail)[1] ?? '';

        // Check if there's a client with this email domain in their company field
        $client = Client::where('email', 'LIKE', "%@{$domain}")
            ->orWhere('company', 'LIKE', "%{$domain}%")
            ->first();

        return $client;
    }

    private function getDefaultClient(): ?Client
    {
        $defaultClientId = env('DEFAULT_CLIENT_ID');
        return $defaultClientId ? Client::find($defaultClientId) : null;
    }

    private function leadExists(string $email, ?string $phone, int $clientId): bool
    {
        $query = Lead::where('client_id', $clientId);

        if ($phone) {
            $query->where(function ($q) use ($email, $phone) {
                $q->where('email', $email)->orWhere('phone', $phone);
            });
        } else {
            $query->where('email', $email);
        }

        return $query->exists();
    }
}
