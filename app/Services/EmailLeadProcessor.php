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
use App\Services\EmailProcessingLogger;
use App\Services\AdminNotificationService;

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

    /**
     * Create leads from emails
     * @throws \Exception
     * @return Lead[]
     */
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

            Log::info('Attempting IMAP connection', [
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'],
                'username' => $config['username'],
                'folder' => $config['default_folder']
            ]);

            $imapConnection = imap_open(
                $connectionString,
                $config['username'],
                $config['password']
            );
            if (!$imapConnection) {
                $imapError = imap_last_error();
                Log::error('IMAP connection failed', [
                    'error' => $imapError,
                    'connection_string' => $connectionString
                ]);
                throw new \Exception('Could not connect to IMAP: ' . $imapError);
            }

            Log::info('IMAP connection successful');

            // Get all emails (not just unseen) for debugging
            $allMails = imap_search($imapConnection, 'ALL', SE_UID);
            $unseenMails = imap_search($imapConnection, 'UNSEEN', SE_UID);
            $mailIds = $unseenMails ? $unseenMails : [];

            Log::info('Email inbox status', [
                'total_emails' => $allMails ? count($allMails) : 0,
                'unread_emails' => count($mailIds),
                'last_5_all_mail_ids' => $allMails ? array_slice($allMails, -5) : [],
                'unread_mail_ids' => $mailIds
            ]);

            echo "Total emails in inbox: " . ($allMails ? count($allMails) : 0) . "\n";
            echo "Unread emails found: " . count($mailIds) . "\n";

            foreach ($mailIds as $mailId) {
                try {
                    echo "Processing email ID: $mailId\n";
                    Log::info('Processing email', ['mail_id' => $mailId]);

                    $header = imap_headerinfo($imapConnection, $mailId);
                    if (!$header) {
                        Log::warning('Failed to get header info for email', ['mail_id' => $mailId]);
                        continue;
                    }

                    $body = imap_body($imapConnection, $mailId);
                    if ($body === false) {
                        Log::warning('Failed to get body for email', ['mail_id' => $mailId]);
                        $body = '';
                    }

                    $fromAddress = isset($header->from[0])
                        ? $header->from[0]->mailbox . '@' . $header->from[0]->host
                        : 'unknown@unknown.com';

                    $fromName = isset($header->from[0]->personal) ? $header->from[0]->personal : '';
                    $subject = isset($header->subject) ? $header->subject : '';

                    $email = (object) [
                        'fromAddress' => $fromAddress,
                        'fromName' => $fromName,
                        'subject' => $subject,
                        'textPlain' => $body,
                        'date' => isset($header->date) ? $header->date : null,
                    ];

                    // Mark as seen
                    $markResult = imap_setflag_full($imapConnection, $mailId, "\\Seen", ST_UID);
                    if (!$markResult) {
                        Log::warning('Failed to mark email as seen', ['mail_id' => $mailId]);
                    }

                    // Log that we received the email
                    EmailProcessingLogger::logEmailReceived(
                        (string) $mailId,
                        $email->fromAddress,
                        $email->subject,
                        [
                            'from_name' => $email->fromName,
                            'body_length' => strlen($body),
                            'date' => $email->date,
                        ]
                    );

                    Log::info('Processing email from: ' . $email->fromAddress . ' Subject: ' . $email->subject);

                    $leads = $this->processEmail($email);

                    if (!empty($leads)) {
                        echo "✓ " . count($leads) . " lead(s) created successfully\n";
                        $processed = array_merge($processed, $leads);
                    } else {
                        echo "✗ No leads created - check processEmail() method\n";
                        Log::warning('No leads created from email', [
                            'mail_id' => $mailId,
                            'from_address' => $email->fromAddress,
                            'subject' => $email->subject
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing email ID ' . $mailId . ': ' . $e->getMessage(), [
                        'mail_id' => $mailId,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    echo "✗ Error processing email ID $mailId: " . $e->getMessage() . "\n";
                    
                    // Send admin notification for email processing error
                    $fromAddress = 'unknown';
                    $subject = 'Unknown Subject';
                    try {
                        if (isset($email)) {
                            $fromAddress = $email->fromAddress ?? 'unknown';
                            $subject = $email->subject ?? 'Unknown Subject';
                        }
                    } catch (\Exception $innerE) {
                        // Ignore errors when trying to extract email info
                    }
                    
                    AdminNotificationService::notifyEmailError(
                        $fromAddress,
                        $subject,
                        $e->getMessage(),
                        'email_processing_error',
                        [
                            'mail_id' => $mailId,
                            'exception_class' => get_class($e),
                        ]
                    );
                }
            }

            echo "Total leads processed: " . count($processed) . "\n";

            // Close IMAP connection
            if ($imapConnection) {
                imap_close($imapConnection);
                Log::info('IMAP connection closed successfully');
            }
        } catch (\Exception $e) {
            Log::error('Error connecting to mailbox: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $processed;
    }

    /**
     * Test email server connectivity and get detailed status
     * @return array
     */
    public function testEmailConnection(): array
    {
        $result = [
            'connection_successful' => false,
            'error' => null,
            'total_emails' => 0,
            'unread_emails' => 0,
            'recent_emails' => [],
            'server_info' => []
        ];

        try {
            $config = config('services.imap');
            $connectionString = sprintf(
                '{%s:%d/imap/%s/novalidate-cert}%s',
                $config['host'],
                $config['port'],
                $config['encryption'],
                $config['default_folder']
            );

            Log::info('Testing IMAP connection', [
                'host' => $config['host'],
                'port' => $config['port'],
                'username' => $config['username']
            ]);

            $imapConnection = imap_open(
                $connectionString,
                $config['username'],
                $config['password']
            );

            if (!$imapConnection) {
                $result['error'] = imap_last_error();
                Log::error('IMAP test connection failed', ['error' => $result['error']]);
                return $result;
            }

            $result['connection_successful'] = true;

            // Get email counts
            $mailbox_info = imap_status($imapConnection, $connectionString, SA_ALL);
            if ($mailbox_info) {
                $result['server_info'] = [
                    'messages' => $mailbox_info->messages,
                    'recent' => $mailbox_info->recent,
                    'unseen' => $mailbox_info->unseen,
                    'uidnext' => $mailbox_info->uidnext,
                ];
                $result['total_emails'] = $mailbox_info->messages;
                $result['unread_emails'] = $mailbox_info->unseen;
            }

            // Get recent emails for debugging
            $recentMails = imap_search($imapConnection, 'SINCE "' . date('d-M-Y', strtotime('-7 days')) . '"', SE_UID);
            if ($recentMails) {
                $recentEmails = [];
                foreach (array_slice($recentMails, -10) as $mailId) {
                    $header = imap_headerinfo($imapConnection, $mailId);
                    if ($header) {
                        $recentEmails[] = [
                            'id' => $mailId,
                            'from' => isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : 'unknown',
                            'subject' => $header->subject ?? 'No subject',
                            'date' => $header->date ?? 'No date',
                            'seen' => $header->Unseen === 'U' ? false : true
                        ];
                    }
                }
                $result['recent_emails'] = $recentEmails;
            }

            imap_close($imapConnection);
            Log::info('IMAP test connection successful', $result['server_info']);
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('IMAP test connection exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Create leads from email for all matching clients
     * @param mixed $email
     * @return Lead[]
     */
    private function processEmail($email): array
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
            
            // Send admin notification for processing error
            AdminNotificationService::notifyEmailError(
                $senderEmail,
                $email->subject ?? 'No Subject',
                'No lead name could be extracted from email',
                'name_extraction_error',
                ['email_body_preview' => substr($email->textPlain ?? '', 0, 200)]
            );
            
            EmailProcessingLogger::logError(
                $senderEmail,
                'No lead name could be extracted from email',
                null,
                ['email_body_preview' => substr($email->textPlain ?? '', 0, 200)]
            );
            return [];
        }

        if (!$senderEmail) {
            echo "  ✗ No sender email, skipping\n";
            Log::warning('Email without sender address, skipping');
            
            // Send admin notification for processing error
            AdminNotificationService::notifyEmailError(
                'unknown',
                $email->subject ?? 'No Subject',
                'Email without sender address',
                'sender_email_missing'
            );
            
            EmailProcessingLogger::logError(
                'unknown',
                'Email without sender address'
            );
            return [];
        }

        if ($this->shouldIgnoreEmail($email)) {
            echo "  ✗ Automated email, skipping\n";
            Log::info('Ignoring automated email from: ' . $senderEmail);
            EmailProcessingLogger::logEvent(
                $senderEmail,
                'email_received',
                'skipped',
                'Automated email ignored',
                ['ignore_reason' => 'Matches automated email patterns']
            );
            return [];
        }

        $phoneNumber = $this->extractPhoneNumber($email);
        echo "  - Phone extracted: " . ($phoneNumber ?: 'none') . "\n";

        $message = $this->extractMessage($email);
        echo "  - Message length: " . strlen($message) . " characters\n";
        echo "  - Message preview: " . substr($message, 0, 100) . "...\n";

        $source = $this->determineLeadSource($email);
        echo "  - Source: $source\n";

        // Determine campaign from email content
        $campaign = $this->determineCampaign($email);
        echo "  - Campaign: " . ($campaign ?: 'none detected') . "\n";

        // Find all matching clients instead of just the first one
        $clients = $this->findAllMatchingClients($email);
        $usedDefaultClient = false;

        // If no matching clients found, try default client
        if ($clients->isEmpty()) {
            $defaultClient = $this->getDefaultClient();
            if ($defaultClient) {
                $clients = collect([$defaultClient]);
                $usedDefaultClient = true;
                
                $domain = explode('@', $senderEmail)[1] ?? '';
                
                // Send admin notification for unmatched rules using default client
                AdminNotificationService::notifyRuleNotMatched(
                    $senderEmail,
                    $email->subject ?? 'No Subject',
                    $domain,
                    true,
                    $defaultClient->name
                );
            }
        }

        if ($clients->isEmpty()) {
            echo "  ✗ No client found and no default client set\n";
            Log::error('No client found and no default client set');
            
            $domain = explode('@', $senderEmail)[1] ?? '';
            
            // Send admin notification for unmatched rules
            AdminNotificationService::notifyRuleNotMatched(
                $senderEmail,
                $email->subject ?? 'No Subject',
                $domain,
                false,
                null
            );
            
            EmailProcessingLogger::logError(
                $senderEmail,
                'No client found and no default client configured',
                null,
                [
                    'sender_domain' => $domain,
                    'subject' => $email->subject,
                ]
            );
            return [];
        }

        $leads = [];

        foreach ($clients as $client) {
            /** @var Client $client */
            echo "  - Client: {$client->name} (ID: {$client->id})\n";

            // Check for duplicate leads based on name, email, and phone
            $duplicateLead = $this->findDuplicateLead($leadName, $leadEmail, $phoneNumber, $client->id);

            if ($duplicateLead) {
                echo "  ✓ Duplicate lead found for {$client->name}, appending message\n";
                Log::info('Duplicate lead found, appending message for: ' . $senderEmail . ' - Client: ' . $client->name);

                $updatedLead = $this->appendMessageToLead(
                    $duplicateLead,
                    $message,
                    $senderEmail,
                    $email->subject,
                    $email
                );

                EmailProcessingLogger::logLeadDuplicate(
                    $senderEmail,
                    $client,
                    'Duplicate lead detected - message appended to existing lead',
                    [
                        'existing_lead_id' => $duplicateLead->id,
                        'phone_number' => $phoneNumber,
                        'client_name' => $client->name,
                        'lead_name' => $leadName,
                        'lead_email' => $leadEmail,
                    ],
                    $duplicateLead
                );

                // Send admin notification for duplicate lead
                AdminNotificationService::notifyDuplicateLead(
                    $senderEmail,
                    $email->subject ?? 'No Subject',
                    $duplicateLead,
                    $client->name,
                    [
                        'duplicate_detection_method' => 'name_email_phone_match',
                        'phone_number' => $phoneNumber,
                        'lead_name' => $leadName,
                        'lead_email' => $leadEmail,
                        'time_since_original' => $duplicateLead->created_at->diffForHumans(),
                    ]
                );

                $leads[] = $updatedLead;
                continue;
            }

            echo "  ✓ All checks passed, creating new lead for {$client->name}\n";

            $lead = Lead::create([
                'client_id' => $client->id,
                'name' => $leadName,
                'email' => $leadEmail,
                'phone' => $phoneNumber,
                'message' => $message,
                'status' => 'new',
                'source' => $source,
                'campaign' => $campaign,
                'from_email' => $senderEmail,
                'email_subject' => $email->subject,
                'email_received_at' => isset($email->date) ? date('Y-m-d H:i:s', strtotime($email->date)) : now(),
            ]);

            // Log successful lead creation
            EmailProcessingLogger::logLeadCreated(
                $senderEmail,
                $lead,
                null, // We'll update this when we add rule tracking to findAllMatchingClients
                [
                    'extracted_name' => $leadName,
                    'extracted_email' => $leadEmail,
                    'extracted_phone' => $phoneNumber,
                    'message_length' => strlen($message),
                    'source' => $source,
                    'campaign' => $campaign,
                    'client_name' => $client->name,
                ]
            );

            Log::info('Created new lead from email: ' . $senderEmail . ' for client: ' . $client->name);

            $leads[] = $lead;
        }

        if (!empty($leads)) {
            echo "  ✓ Created " . count($leads) . " lead(s) for " . count($clients) . " client(s)\n";
            
            // Send admin notification for successful email processing
            $matchedClientsData = $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                ];
            })->toArray();
            
            $createdLeadsData = collect($leads)->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'client_name' => $lead->client->name,
                ];
            })->toArray();
            
            AdminNotificationService::notifyEmailProcessed(
                $senderEmail,
                $email->subject ?? 'No Subject',
                $matchedClientsData,
                $createdLeadsData,
                $source,
                $campaign
            );
        }

        return $leads;
    }

    /**
     * Extract name from email body. Falls back to fromName if not found.
     * @param mixed $email
     * @return string
     */
    private function extractNameFromEmail($email): string
    {
        $parser = new Parser();
        $text = $email->textPlain;

        $text = quoted_printable_decode($text);
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);
        $text = strip_tags($text);

        if (preg_match('/Name:\s*(.+)/i', $text, $matches)) {
            try {
                $name = $parser->parse(trim($matches[1]));
                return $name->getFullName();
            } catch (\Exception $e) {
                return trim($matches[1]);
            }
        }

        if (!empty($email->fromName)) {
            try {
                $name = $parser->parse($email->fromName);
                return $name->getFullName();
            } catch (\Exception $e) {
                return $email->fromName;
            }
        }

        return 'Unknown Sender';
    }

    /**
     * Check if the email should be ignored based on common patterns
     * @param mixed $email
     * @return bool
     */
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

    /**
     * Extract phone number from email body
     * @param mixed $email
     * @return string|null
     */
    private function extractPhoneNumber($email): ?string
    {
        $text = $email->textPlain . ' ' .
            (isset($email->textHtml) ? strip_tags($email->textHtml) : '');

        $text = quoted_printable_decode($text);

        // First, look for explicitly labeled phone numbers
        $labeledPatterns = [
            '/Phone:\s*([+]?[\d\s\-\(\)\.]{7,20})/i',
            '/Phone Number:\s*([+]?[\d\s\-\(\)\.]{7,20})/i',
            '/Mobile:\s*([+]?[\d\s\-\(\)\.]{7,20})/i',
            '/Contact:\s*([+]?[\d\s\-\(\)\.]{7,20})/i',
            '/Tel:\s*([+]?[\d\s\-\(\)\.]{7,20})/i',
        ];

        foreach ($labeledPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $phone = preg_replace('/[^\d+]/', '', $matches[1]);
                // Ensure it's a reasonable phone number length (7-15 digits)
                if (strlen($phone) >= 7 && strlen($phone) <= 15) {
                    return $phone;
                }
            }
        }

        // Remove URLs from text to avoid extracting timestamps and other numeric data
        $textWithoutUrls = preg_replace('/https?:\/\/[^\s]+/i', '', $text);
        $textWithoutUrls = preg_replace('/www\.[^\s]+/i', '', $textWithoutUrls);

        // Then try general phone number patterns on URL-free text
        $patterns = [
            '/(\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})/', // US format (10 digits)
            '/(\+\d{1,3}[-.\s]?\d{3,4}[-.\s]?\d{3,4}[-.\s]?\d{4})/', // International
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $textWithoutUrls, $matches)) {
                $phone = preg_replace('/[^\d+]/', '', $matches[1]);
                // Ensure it's a reasonable phone number length and not a timestamp
                if (strlen($phone) >= 7 && strlen($phone) <= 15 && !$this->looksLikeTimestamp($phone)) {
                    return $phone;
                }
            }
        }

        return null;
    }

    /**
     * Check if a number looks like a timestamp (too long and recent)
     * @param string $number
     * @return bool
     */
    private function looksLikeTimestamp(string $number): bool
    {
        // Timestamps are typically 10+ digits and represent recent dates
        if (strlen($number) >= 10) {
            $timestamp = (int) substr($number, 0, 10);
            // Check if it's a timestamp from the last 20 years or next 5 years
            $twentyYearsAgo = strtotime('2005-01-01');
            $fiveYearsFromNow = strtotime('+5 years');

            return $timestamp >= $twentyYearsAgo && $timestamp <= $fiveYearsFromNow;
        }

        return false;
    }

    /**
     * Extract message content from email
     * @param mixed $email
     * @return string
     */
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

        $message = quoted_printable_decode($message);

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

    /**
     * Determine the lead source based on email content using lead source rules
     * @param mixed $email
     * @return string
     */
    private function determineLeadSource($email): string
    {
        // Get all active lead source rules, ordered by priority (highest first)
        $sourceRules = \App\Models\LeadSourceRule::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($sourceRules as $rule) {
            if ($rule->matchesEmail($email)) {
                Log::info('Lead source determined by rule', [
                    'rule_id' => $rule->id,
                    'source' => $rule->source_name,
                    'rule_type' => $rule->rule_type,
                    'rule_value' => $rule->rule_value,
                    'match_field' => $rule->match_field,
                    'from_email' => $email->fromAddress
                ]);
                return $rule->source_name;
            }
        }

        // Fallback to legacy logic if no rules match
        $subject = strtolower($email->subject ?? '');
        $fromEmail = strtolower($email->fromAddress);

        if (Str::contains($subject, ['contact form', 'website', 'inquiry', 'message'])) {
            Log::info('Lead source determined by legacy rule: website (subject)', [
                'subject' => $email->subject,
                'from_email' => $email->fromAddress
            ]);
            return 'website';
        }

        if (Str::contains($fromEmail, ['facebook', 'instagram', 'linkedin', 'twitter'])) {
            Log::info('Lead source determined by legacy rule: social (email)', [
                'from_email' => $email->fromAddress
            ]);
            return 'social';
        }

        Log::info('Lead source defaulted to other', [
            'from_email' => $email->fromAddress,
            'subject' => $email->subject
        ]);
        return 'other';
    }

    /**
     * Find the client associated with the email
     * @param mixed $email
     * @return Client|null
     */
    private function findClientForEmail($email): ?Client
    {
        $fromEmail = $email->fromAddress;
        $domain = explode('@', $fromEmail)[1] ?? '';

        // Step 1: Check exact email matches first (fastest)
        $exactMatch = ClientEmail::where('is_active', true)
            ->where('email', $fromEmail)
            ->with('client')
            ->first();

        if ($exactMatch && $exactMatch->matchesEmail($email)) {
            /** @var Client $client */
            $client = $exactMatch->client;
            EmailProcessingLogger::logRuleMatched(
                $fromEmail,
                $exactMatch,
                $client,
                ['match_type' => 'exact_email']
            );
            return $client;
        }

        // Step 2: Check domain patterns (still fast with index)
        $domainMatches = ClientEmail::where('is_active', true)
            ->whereIn('rule_type', ['email_match', 'combined_rule'])
            ->where(function ($query) use ($domain) {
                $query->where('email', "@{$domain}")
                    ->orWhere('email', 'LIKE', "%@{$domain}");
            })
            ->with('client')
            ->get();

        foreach ($domainMatches as $rule) {
            if ($rule->matchesEmail($email)) {
                /** @var Client $client */
                $client = $rule->client;
                EmailProcessingLogger::logRuleMatched(
                    $fromEmail,
                    $rule,
                    $client,
                    ['match_type' => 'domain_pattern', 'domain' => $domain]
                );
                return $client;
            } else {
                EmailProcessingLogger::logRuleFailed(
                    $fromEmail,
                    $rule,
                    'Domain pattern matched but rule conditions failed',
                    ['domain' => $domain]
                );
            }
        }

        // Step 3: Process custom rules efficiently using lazy collection
        $foundClient = ClientEmail::where('is_active', true)
            ->whereIn('rule_type', ['custom_rule', 'combined_rule'])
            ->with('client')
            ->lazy(50) // Process 50 at a time
            ->first(function ($rule) use ($email, $fromEmail) {
                $matches = $rule->matchesEmail($email);
                if ($matches) {
                    EmailProcessingLogger::logRuleMatched(
                        $fromEmail,
                        $rule,
                        $rule->client,
                        ['match_type' => 'custom_rule']
                    );
                } else {
                    EmailProcessingLogger::logRuleFailed(
                        $fromEmail,
                        $rule,
                        'Custom rule conditions not met'
                    );
                }
                return $matches;
            });

        if ($foundClient) {
            /** @var Client $client */
            $client = $foundClient->client;
            return $client;
        }

        // Fallback to client domain matching
        return Client::where('email', 'LIKE', "%@{$domain}")
            ->orWhere('company', 'LIKE', "%{$domain}%")
            ->first();
    }

    /**
     * Find ALL clients that match the email (not just the first one)
     * @param mixed $email
     * @return \Illuminate\Support\Collection<Client>
     */
    private function findAllMatchingClients($email): \Illuminate\Support\Collection
    {
        $fromEmail = $email->fromAddress;
        $domain = explode('@', $fromEmail)[1] ?? '';
        $matchedClients = collect();

        // Step 1: Check exact email matches
        $exactMatches = ClientEmail::where('is_active', true)
            ->where('email', $fromEmail)
            ->with('client')
            ->get();

        foreach ($exactMatches as $rule) {
            if ($rule->matchesEmail($email)) {
                /** @var Client $client */
                $client = $rule->client;
                if (!$matchedClients->contains('id', $client->id)) {
                    $matchedClients->push($client);
                    EmailProcessingLogger::logRuleMatched(
                        $fromEmail,
                        $rule,
                        $client,
                        ['match_type' => 'exact_email']
                    );
                }
            }
        }

        // Step 2: Check domain patterns
        $domainMatches = ClientEmail::where('is_active', true)
            ->whereIn('rule_type', ['email_match', 'combined_rule'])
            ->where(function ($query) use ($domain) {
                $query->where('email', "@{$domain}")
                    ->orWhere('email', 'LIKE', "%@{$domain}");
            })
            ->with('client')
            ->get();

        foreach ($domainMatches as $rule) {
            if ($rule->matchesEmail($email)) {
                /** @var Client $client */
                $client = $rule->client;
                if (!$matchedClients->contains('id', $client->id)) {
                    $matchedClients->push($client);
                    EmailProcessingLogger::logRuleMatched(
                        $fromEmail,
                        $rule,
                        $client,
                        ['match_type' => 'domain_pattern', 'domain' => $domain]
                    );
                }
            } else {
                EmailProcessingLogger::logRuleFailed(
                    $fromEmail,
                    $rule,
                    'Domain pattern matched but rule conditions failed',
                    ['domain' => $domain]
                );
            }
        }

        // Step 3: Process custom rules and combined rules
        $customRules = ClientEmail::where('is_active', true)
            ->whereIn('rule_type', ['custom_rule', 'combined_rule'])
            ->with('client')
            ->get();

        foreach ($customRules as $rule) {
            $matches = $rule->matchesEmail($email);
            if ($matches) {
                /** @var Client $client */
                $client = $rule->client;
                if (!$matchedClients->contains('id', $client->id)) {
                    $matchedClients->push($client);
                    EmailProcessingLogger::logRuleMatched(
                        $fromEmail,
                        $rule,
                        $client,
                        ['match_type' => 'custom_rule']
                    );
                }
            } else {
                EmailProcessingLogger::logRuleFailed(
                    $fromEmail,
                    $rule,
                    'Custom rule conditions not met'
                );
            }
        }

        // Step 4: Fallback to client domain matching if no rules matched
        if ($matchedClients->isEmpty()) {
            $fallbackClients = Client::where('email', 'LIKE', "%@{$domain}")
                ->orWhere('company', 'LIKE', "%{$domain}%")
                ->get();

            foreach ($fallbackClients as $client) {
                $matchedClients->push($client);
            }
        }

        return $matchedClients;
    }

    /**
     * Get the default client if no specific client is found
     * @return Client|null
     */
    private function getDefaultClient(): ?Client
    {
        $defaultClientId = env('DEFAULT_CLIENT_ID');
        return $defaultClientId ? Client::find($defaultClientId) : null;
    }

    /**
     * Check if a lead already exists for the given email and phone within the last 24 hours
     * @param string $email
     * @param string|null $phone
     * @param int $clientId
     * @return bool
     */
    private function leadExists(string $email, ?string $phone, int $clientId): bool
    {
        $query = Lead::where('client_id', $clientId)
            ->where('created_at', '>=', now()->subHours(24));

        if ($phone) {
            $query->where(function ($q) use ($email, $phone) {
                $q->where('email', $email)->orWhere('phone', $phone);
            });
        } else {
            $query->where('email', $email);
        }

        return $query->exists();
    }

    /**
     * Find existing lead with matching name, phone, and email
     * @param string $name
     * @param string $email
     * @param string|null $phone
     * @param int $clientId
     * @return Lead|null
     */
    private function findDuplicateLead(string $name, string $email, ?string $phone, int $clientId): ?Lead
    {
        $query = Lead::where('client_id', $clientId)
            ->where('name', $name)
            ->where('email', $email);

        if ($phone) {
            $query->where('phone', $phone);
        } else {
            $query->whereNull('phone');
        }

        return $query->first();
    }

    /**
     * Append new message to existing lead with duplicate notation
     * @param Lead $existingLead
     * @param string $newMessage
     * @param string $senderEmail
     * @param string $emailSubject
     * @param mixed $email
     * @return Lead
     */
    private function appendMessageToLead(Lead $existingLead, string $newMessage, string $senderEmail, string $emailSubject, $email): Lead
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $duplicateNote = "\n\n--- DUPLICATE SUBMISSION DETECTED ---\n";
        $duplicateNote .= "Date: {$timestamp}\n";
        $duplicateNote .= "From: {$senderEmail}\n";
        $duplicateNote .= "Subject: {$emailSubject}\n";
        $duplicateNote .= "Message:\n{$newMessage}";

        $existingLead->update([
            'message' => $existingLead->message . $duplicateNote,
            'notes' => ($existingLead->notes ?: '') . "\nDuplicate submission received on {$timestamp}",
            'email_received_at' => isset($email->date) ? date('Y-m-d H:i:s', strtotime($email->date)) : now(),
        ]);

        return $existingLead;
    }

    /**
     * Extract email address from email content
     * @param mixed $email
     * @return string
     */
    private function extractEmailAddressFromEmail($email): string
    {
        $text = $email->textPlain ?? '';

        $text = quoted_printable_decode($text);

        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $text, $matches)) {
            return $matches[0];
        }
        return $email->fromAddress ?? '';
    }

    /**
     * Determine campaign from email content using campaign rules
     * @param mixed $email
     * @return string|null
     */
    private function determineCampaign($email): ?string
    {
        // Get all active campaign rules, ordered by priority (highest first)
        $campaignRules = \App\Models\CampaignRule::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc') // Secondary sort for consistency
            ->get();

        foreach ($campaignRules as $rule) {
            if ($rule->matchesEmail($email)) {
                Log::info('Campaign rule matched', [
                    'rule_id' => $rule->id,
                    'campaign_name' => $rule->campaign_name,
                    'rule_type' => $rule->rule_type,
                    'rule_value' => $rule->rule_value,
                    'match_field' => $rule->match_field,
                ]);
                return $rule->campaign_name;
            }
        }

        // Try to extract campaign from Google Ads URL parameters as fallback
        $googleAdsCampaign = $this->extractGoogleAdsCampaign($email);
        if ($googleAdsCampaign) {
            Log::info('Google Ads campaign detected', ['campaign_id' => $googleAdsCampaign]);
            return "Google Ads #{$googleAdsCampaign}";
        }

        return null;
    }

    /**
     * Extract Google Ads campaign ID from URL parameters as fallback
     * @param mixed $email
     * @return string|null
     */
    private function extractGoogleAdsCampaign($email): ?string
    {
        $text = $email->textPlain ?? '';

        // Look for gad_campaignid parameter
        if (preg_match('/gad_campaignid=(\d+)/i', $text, $matches)) {
            return $matches[1];
        }

        // Look for other common Google Ads parameters
        if (preg_match('/utm_campaign=([^&\s]+)/i', $text, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
    }
}
