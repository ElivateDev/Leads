<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Client;
use Illuminate\Console\Command;
use App\Notifications\NewLeadNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Services\EmailProcessingLogger;

class TestEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:test-email-sending
                          {--to= : Email address to send test to (defaults to first client)}
                          {--test-lead-id= : Use specific lead ID for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality with detailed logging';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing email sending functionality...');

        // Display current mail configuration
        $this->displayMailConfig();

        $toEmail = $this->option('to');
        $leadId = $this->option('test-lead-id');

        try {
            // Get or create test data
            if ($leadId) {
                $lead = Lead::find($leadId);
                if (!$lead) {
                    $this->error("Lead with ID {$leadId} not found.");
                    return self::FAILURE;
                }
                $this->info("Using existing lead: {$lead->name} (ID: {$lead->id})");
            } else {
                $lead = $this->createTestLead($toEmail);
                $this->info("Created test lead: {$lead->name} (ID: {$lead->id})");
            }

            // Test 1: Basic SMTP connectivity
            $this->info('Test 1: Testing SMTP connectivity...');
            $connectTest = $this->testSmtpConnection();
            if (!$connectTest['success']) {
                $this->error('SMTP connection failed: ' . $connectTest['error']);
                return self::FAILURE;
            }
            $this->info('✓ SMTP connection successful');

            // Test 2: Test raw mail sending
            $this->info('Test 2: Testing raw email sending...');
            $recipient = $toEmail ?: $lead->client->email;
            $rawTest = $this->testRawEmailSending($recipient, $lead);
            if (!$rawTest['success']) {
                $this->error('Raw email sending failed: ' . $rawTest['error']);
                return self::FAILURE;
            }
            $this->info('✓ Raw email sending successful');

            // Test 3: Test notification system
            $this->info('Test 3: Testing notification system...');
            $notificationTest = $this->testNotificationSending($lead, $recipient);
            if (!$notificationTest['success']) {
                $this->error('Notification sending failed: ' . $notificationTest['error']);
                return self::FAILURE;
            }
            $this->info('✓ Notification system working');

            $this->info('');
            $this->info('✅ All email tests passed successfully!');
            $this->info("Test emails sent to: {$recipient}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Test failed with exception: ' . $e->getMessage());
            Log::error('Email sending test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }

    private function displayMailConfig(): void
    {
        $this->info('Current Mail Configuration:');
        $this->table(['Setting', 'Value'], [
            ['Mail Driver', config('mail.default')],
            ['SMTP Host', config('mail.mailers.smtp.host')],
            ['SMTP Port', config('mail.mailers.smtp.port')],
            ['SMTP Username', config('mail.mailers.smtp.username')],
            ['SMTP Encryption', config('mail.mailers.smtp.encryption')],
            ['From Address', config('mail.from.address')],
            ['From Name', config('mail.from.name')],
            ['Queue Driver', config('queue.default')],
        ]);
        $this->info('');
    }

    private function testSmtpConnection(): array
    {
        try {
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                config('mail.mailers.smtp.host'),
                config('mail.mailers.smtp.port'),
                config('mail.mailers.smtp.encryption') === 'tls'
            );

            if (config('mail.mailers.smtp.username')) {
                $transport->setUsername(config('mail.mailers.smtp.username'));
                $transport->setPassword(config('mail.mailers.smtp.password'));
            }

            $transport->start();
            $transport->stop();

            return ['success' => true];

        } catch (\Exception $e) {
            Log::error('SMTP connection test failed', [
                'error' => $e->getMessage(),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function testRawEmailSending(string $recipient, Lead $lead): array
    {
        try {
            Log::info('Testing raw email sending', [
                'recipient' => $recipient,
                'from' => config('mail.from.address'),
            ]);

            Mail::raw('This is a test email from the lead management system.', function ($message) use ($recipient, $lead) {
                $message->to($recipient)
                    ->subject('Test Email - Lead Management System')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Raw email sent successfully', ['recipient' => $recipient]);

            return ['success' => true];

        } catch (\Exception $e) {
            Log::error('Raw email sending failed', [
                'error' => $e->getMessage(),
                'recipient' => $recipient,
                'exception_class' => get_class($e),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function testNotificationSending(Lead $lead, string $recipient): array
    {
        try {
            Log::info('Testing notification sending', [
                'lead_id' => $lead->id,
                'recipient' => $recipient,
                'notification_class' => NewLeadNotification::class,
            ]);

            // If recipient is different from client email, temporarily update it
            $originalEmail = $lead->client->email;
            if ($recipient !== $originalEmail) {
                $lead->client->update(['email' => $recipient]);
            }

            // Send notification
            $lead->client->notify(new NewLeadNotification($lead));

            Log::info('Notification sent successfully', [
                'recipient' => $recipient,
                'notification_class' => NewLeadNotification::class,
            ]);

            // Restore original email if changed
            if ($recipient !== $originalEmail) {
                $lead->client->update(['email' => $originalEmail]);
            }

            // Log in our tracking system
            EmailProcessingLogger::logNotificationSent(
                $lead->from_email ?? $lead->email ?? 'test',
                'NewLeadNotification',
                $lead,
                $lead->client,
                [
                    'test_mode' => true,
                    'original_recipient' => $originalEmail,
                    'test_recipient' => $recipient,
                ]
            );

            return ['success' => true];

        } catch (\Exception $e) {
            Log::error('Notification sending failed', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id,
                'recipient' => $recipient,
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            EmailProcessingLogger::logError(
                $lead->from_email ?? $lead->email ?? 'test',
                'Notification test failed: ' . $e->getMessage(),
                $e,
                [
                    'test_mode' => true,
                    'recipient' => $recipient,
                    'lead_id' => $lead->id,
                ]
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createTestLead(?string $toEmail): Lead
    {
        // Get or create a test client
        $client = Client::first();
        if (!$client) {
            $client = Client::create([
                'name' => 'Test Client',
                'email' => $toEmail ?: 'test@example.com',
                'email_notifications' => true,
            ]);
        } else if ($toEmail) {
            // Update client email for testing
            $client->update(['email' => $toEmail]);
        }

        // Create test lead
        return Lead::create([
            'client_id' => $client->id,
            'name' => 'Test Lead',
            'email' => 'testlead@example.com',
            'phone' => '555-TEST-123',
            'message' => 'This is a test lead created for email testing purposes.',
            'status' => 'new',
            'source' => 'other', // Changed from 'test' to 'other' which is a valid enum value
            'from_email' => 'test@example.com',
            'email_subject' => 'Test Email Subject',
            'email_received_at' => now(),
        ]);
    }
}
