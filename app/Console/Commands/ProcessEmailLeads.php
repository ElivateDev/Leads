<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailLeadProcessor;

class ProcessEmailLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:process-emails
                          {--limit=50 : Maximum number of emails to process}
                          {--debug : Show detailed processing information}
                          {--test-connection : Test email connection before processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process emails from inbox and create leads';

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
        $this->info('Starting email lead processing...');

        // Test connection first if requested
        if ($this->option('test-connection')) {
            $this->info('Testing email connection first...');
            $testResult = $this->processor->testEmailConnection();

            if (!$testResult['connection_successful']) {
                $this->error('Email connection test failed: ' . $testResult['error']);
                return self::FAILURE;
            }

            $this->info('✓ Email connection test successful');
            $this->info("Total emails in inbox: {$testResult['total_emails']}, Unread: {$testResult['unread_emails']}");
        }

        try {
            $leads = $this->processor->processNewEmails();

            if (empty($leads)) {
                $this->info('No new leads found in email inbox.');

                if ($this->option('debug')) {
                    $this->info('This could mean:');
                    $this->line('- No new emails have arrived');
                    $this->line('- Emails arrived but failed validation/processing');
                    $this->line('- All emails were marked as automated and ignored');
                    $this->line('Run with --test-connection to see inbox status');
                }

                return self::SUCCESS;
            }

            $this->info('Processed ' . count($leads) . ' new leads:');

            foreach ($leads as $lead) {
                $this->line("- {$lead->name} ({$lead->email}) - Client: {$lead->client->name}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error processing emails: ' . $e->getMessage());

            if ($this->option('debug')) {
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
