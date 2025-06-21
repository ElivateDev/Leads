<?php

namespace App\Services;

use App\Models\Lead;
use PhpImap\Mailbox;
use App\Models\Client;
use App\Models\ClientEmail;
use Illuminate\Support\Str;
use TheIconic\NameParser\Parser;
use Illuminate\Support\Facades\Log;
use League\HTMLToMarkdown\HtmlConverter;

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
            $config = config('services.imap');
            $connectionString = sprintf(
                '{%s:%d/imap/%s/novalidate-cert}%s',
                $config['host'],
                $config['port'],
                $config['encryption'],
                $config['default_folder']
            );
            $imapConnection = imap_open(
                $connectionString,
                $config['username'],
                $config['password']
            );
            if (!$imapConnection) {
                throw new \Exception('Could not connect to IMAP: ' . imap_last_error());
            }
            $unseenMails = imap_search($imapConnection, 'UNSEEN', SE_UID);
            $mailIds = $unseenMails ? $unseenMails : [];
            Log::info('Found ' . count($mailIds) . ' unread emails to process');
            echo "Unread emails found: " . count($mailIds) . "\n";

            foreach ($mailIds as $mailId) {
                try {
                    echo "Processing email ID: $mailId\n";

                    $header = imap_headerinfo($imapConnection, $mailId);
                    $body = imap_body($imapConnection, $mailId);

                    $email = (object) [
                        'fromAddress' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                        'fromName' => $header->from[0]->personal ?? '',
                        'subject' => $header->subject ?? '',
                        'textPlain' => $body,
                    ];
                    imap_setflag_full($imapConnection, $mailId, "\\Seen", ST_UID);

                    Log::info('Processing email from: ' . $email->fromAddress . ' Subject: ' . $email->subject);

                    $lead = $this->processEmail($email);

                    if ($lead) {
                        echo "✓ Lead created successfully\n";
                        $processed[] = $lead;
                    } else {
                        echo "✗ No lead created - check processEmail() method\n";
                    }

                } catch (\Exception $e) {
                    Log::error('Error processing email ID ' . $mailId . ': ' . $e->getMessage());
                    echo "✗ Error processing email ID $mailId: " . $e->getMessage() . "\n";
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
        $senderEmail = $email->fromAddress;
        $senderName = $email->fromName ?: 'Unknown Sender';
        $leadName = $this->extractNameFromEmail($email);
        $leadEmail = $this->extractEmailAddressFromEmail($email);

        echo "  - Sender: $senderName ($senderEmail)\n";
        echo "  - Name: $leadName\n";

        if (!$leadName) {
            echo "  ✗ No lead name extracted, skipping\n";
            Log::warning('Email without lead name, skipping');
            return null;
        }

        if (!$senderEmail) {
            echo "  ✗ No sender email, skipping\n";
            Log::warning('Email without sender address, skipping');
            return null;
        }

        if ($this->shouldIgnoreEmail($email)) {
            echo "  ✗ Automated email, skipping\n";
            Log::info('Ignoring automated email from: ' . $senderEmail);
            return null;
        }

        $phoneNumber = $this->extractPhoneNumber($email);
        echo "  - Phone extracted: " . ($phoneNumber ?: 'none') . "\n";

        $message = $this->extractMessage($email);
        echo "  - Message length: " . strlen($message) . " characters\n";
        echo "  - Message preview: " . substr($message, 0, 100) . "...\n";

        $source = $this->determineLeadSource($email);
        echo "  - Source: $source\n";

        $client = $this->findClientForEmail($email) ?: $this->getDefaultClient();

        if (!$client) {
            echo "  ✗ No client found and no default client set\n";
            Log::error('No client found and no default client set');
            return null;
        }

        echo "  - Client: {$client->name} (ID: {$client->id})\n";

        if ($this->leadExists($senderEmail, $phoneNumber, $client->id)) {
            echo "  ✗ Lead already exists\n";
            Log::info('Lead already exists for: ' . $senderEmail);
            return null;
        }

        echo "  ✓ All checks passed, creating lead\n";

        $lead = Lead::create([
            'client_id' => $client->id,
            'name' => $leadName,
            'email' => $leadEmail,
            'phone' => $phoneNumber,
            'message' => $message,
            'status' => 'new',
            'source' => $source,
            'from_email' => $senderEmail,
            'email_subject' => $email->subject,
            'email_received_at' => isset($email->date) ? date('Y-m-d H:i:s', strtotime($email->date)) : now(),
        ]);

        Log::info('Created new lead from email: ' . $senderEmail);

        return $lead;
    }

    private function extractNameFromEmail($email): string
    {
        $parser = new Parser();
        $text = $email->textPlain;

        // Convert HTML breaks to newlines and strip HTML tags
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);
        $text = strip_tags($text);

        // Look for name patterns
        if (preg_match('/Name:\s*(.+)/i', $text, $matches)) {
            try {
                $name = $parser->parse(trim($matches[1]));
                return $name->getFullName();
            } catch (\Exception $e) {
                // Fallback to original match if parsing fails
                return trim($matches[1]);
            }
        }

        // Use fromName if available
        if (!empty($email->fromName)) {
            try {
                $name = $parser->parse($email->fromName);
                return $name->getFullName();
            } catch (\Exception $e) {
                return $email->fromName;
            }
        }

        // Fallback logic...
        return 'Unknown Sender';
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

        return false;
    }

    private function extractPhoneNumber($email): ?string
    {
        $text = $email->textPlain . ' ' .
            (isset($email->textHtml) ? strip_tags($email->textHtml) : '');

        $patterns = [
            '/(\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})/', // US format
            '/(\d{3}[-.\s]?\d{3}[-.\s]?\d{4})/',       // Simple format
            '/(\+\d{1,3}[-.\s]?\d{3,4}[-.\s]?\d{3,4}[-.\s]?\d{4})/', // International
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return preg_replace('/[^\d+]/', '', $matches[1]);
            }
        }

        return null;
    }

    private function extractMessage($email): string
    {
        $message = '';

        if (!empty($email->textPlain)) {
            $message = $email->textPlain;
        }

        $subject = $email->subject ?? '';
        if ($subject && !Str::contains(strtolower($subject), ['contact', 'inquiry', 'message'])) {
            $message = "Subject: {$subject}\n\n" . $message;
        }

        $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $message);

        if (isset($email->textHtml)) {
            $htmlText = $this->htmlConverter->convert($email->textHtml);
            $message .= "\n\n" . $htmlText;
        }

        $message = strip_tags($message);
        $message = trim($message);

        if (empty($message)) {
            $message = 'No message content provided.';
        }

        if (strlen($message) > 5000) {
            $message = substr($message, 0, 5000) . '...';
        }
        return $message;
    }

    private function determineLeadSource($email): string
    {
        $subject = strtolower($email->subject ?? '');
        $fromEmail = strtolower($email->fromAddress);

        if (Str::contains($subject, ['contact form', 'website', 'inquiry', 'message'])) {
            return 'website';
        }

        if (Str::contains($fromEmail, ['facebook', 'instagram', 'linkedin', 'twitter'])) {
            return 'social';
        }

        return 'other';
    }

    private function findClientForEmail($email): ?Client
    {
        $fromEmail = $email->fromAddress;

        $clientEmail = ClientEmail::where('email', $fromEmail)
            ->where('is_active', true)
            ->with('client')
            ->first();

        if ($clientEmail) {
            /** @var Client $client */
            $client = $clientEmail->client;
            return $client;
        }

        $domain = explode('@', $fromEmail)[1] ?? '';

        $clientEmail = ClientEmail::where('email', 'LIKE', "%@{$domain}")
            ->where('is_active', true)
            ->with('client')
            ->first();

        if ($clientEmail) {
            /** @var Client $client */
            $client = $clientEmail->client;
            return $client;
        }

        // Original fallback logic
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

    private function extractEmailAddressFromEmail($email): string
    {
        // Try to extract from the body first
        $text = $email->textPlain ?? '';
        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $text, $matches)) {
            return $matches[0];
        }
        // Fallback to the sender's address
        return $email->fromAddress ?? '';
    }
}
