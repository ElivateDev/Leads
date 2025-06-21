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
                          {--limit=50 : Maximum number of emails to process}';

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

        try {
            $leads = $this->processor->processNewEmails();

            if (empty($leads)) {
                $this->info('No new leads found in email inbox.');
                return self::SUCCESS;
            }

            $this->info('Processed ' . count($leads) . ' new leads:');

            foreach ($leads as $lead) {
                $this->line("- {$lead->name} ({$lead->email}) - Client: {$lead->client->name}");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error processing emails: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
