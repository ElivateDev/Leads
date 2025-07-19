<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailLeadProcessor;
use Illuminate\Support\Facades\Log;

class TestEmailConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:test-email-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email server connection and display detailed status';

    /**
     * The email lead processor instance.
     */
    protected EmailLeadProcessor $processor;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailLeadProcessor $processor)
    {
        parent::__construct();
        $this->processor = $processor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing email server connection...');

        try {
            $result = $this->processor->testEmailConnection();

            if ($result['connection_successful']) {
                $this->info('✓ Email server connection successful!');
                
                $this->info('Server Information:');
                $this->table(['Metric', 'Value'], [
                    ['Total Messages', $result['server_info']['messages'] ?? 'Unknown'],
                    ['Unread Messages', $result['server_info']['unseen'] ?? 'Unknown'],
                    ['Recent Messages', $result['server_info']['recent'] ?? 'Unknown'],
                    ['Next UID', $result['server_info']['uidnext'] ?? 'Unknown'],
                ]);

                if (!empty($result['recent_emails'])) {
                    $this->info('Recent Emails (last 10 from past 7 days):');
                    $emailData = [];
                    foreach ($result['recent_emails'] as $email) {
                        $emailData[] = [
                            $email['id'],
                            $email['from'],
                            substr($email['subject'], 0, 50) . (strlen($email['subject']) > 50 ? '...' : ''),
                            $email['date'],
                            $email['seen'] ? 'Read' : 'Unread'
                        ];
                    }
                    $this->table(['ID', 'From', 'Subject', 'Date', 'Status'], $emailData);
                } else {
                    $this->warn('No recent emails found in the past 7 days');
                }

                if ($result['unread_emails'] == 0) {
                    $this->warn('No unread emails found. If you expect emails from your website, they may not be arriving.');
                    $this->info('Troubleshooting tips:');
                    $this->line('1. Check if your website contact form is actually sending emails');
                    $this->line('2. Verify the email address your website sends to matches your IMAP inbox');
                    $this->line('3. Check spam/junk folders');
                    $this->line('4. Test by manually sending an email to your inbox');
                    $this->line('5. Check your website\'s mail logs for send failures');
                }

            } else {
                $this->error('✗ Email server connection failed!');
                $this->error('Error: ' . $result['error']);
                return self::FAILURE;
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Exception occurred while testing connection: ' . $e->getMessage());
            Log::error('Email connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }
}
