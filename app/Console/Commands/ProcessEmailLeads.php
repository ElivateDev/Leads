<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessEmailLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:process-emails
                          {--dry-run : Run without creating leads}
                          {--limit=50 : Maximum number of emails to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process emails from inbox and create leads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting email lead processing...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No leads will be created');
        }

        try {
            $leads = $processor->processNewEmails();

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
            return self::FAILURE;
        }
    }
}
