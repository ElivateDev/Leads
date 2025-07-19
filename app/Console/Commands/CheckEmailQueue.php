<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Models\EmailProcessingLog;

class CheckEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:check-email-queue
                          {--failed : Show only failed jobs}
                          {--recent : Show jobs from last 24 hours only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check email queue status and recent email sending activity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Email Queue Status Check');
        $this->info('========================');

        // Check queue configuration
        $this->displayQueueConfig();

        // Check queue status
        $this->checkQueueStatus();

        // Check recent email logs
        $this->checkRecentEmailActivity();

        // Check failed jobs if requested
        if ($this->option('failed')) {
            $this->checkFailedJobs();
        }

        return self::SUCCESS;
    }

    private function displayQueueConfig(): void
    {
        $this->info('Queue Configuration:');
        $this->table(['Setting', 'Value'], [
            ['Queue Driver', config('queue.default')],
            ['Queue Connection', config('queue.connections.' . config('queue.default') . '.driver') ?? 'N/A'],
            ['Database Table', config('queue.connections.database.table', 'jobs')],
            ['Failed Jobs Table', config('queue.failed.table', 'failed_jobs')],
        ]);
        $this->info('');
    }

    private function checkQueueStatus(): void
    {
        try {
            // Check if using database queue
            if (config('queue.default') === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                $this->info('Database Queue Status:');
                $this->table(['Queue', 'Count'], [
                    ['Pending Jobs', $pendingJobs],
                    ['Failed Jobs', $failedJobs],
                ]);

                if ($pendingJobs > 0) {
                    $this->warn("There are {$pendingJobs} pending jobs in the queue.");
                    $this->info('Run: php artisan queue:work to process them');
                }

                if ($failedJobs > 0) {
                    $this->error("There are {$failedJobs} failed jobs.");
                    $this->info('Run: php artisan queue:failed to see details');
                    $this->info('Run: php artisan queue:retry all to retry failed jobs');
                }
            } else {
                $this->info("Queue driver: " . config('queue.default'));
                $this->info('Cannot check queue status for non-database drivers from this command.');
            }

            $this->info('');
        } catch (\Exception $e) {
            $this->error('Error checking queue status: ' . $e->getMessage());
        }
    }

    private function checkRecentEmailActivity(): void
    {
        $this->info('Recent Email Activity:');

        $hoursBack = $this->option('recent') ? 24 : 168; // 24 hours or 7 days
        $timeframe = now()->subHours($hoursBack);

        // Get recent email processing logs
        $query = EmailProcessingLog::where('processed_at', '>=', $timeframe)
            ->whereIn('type', ['notification_sent', 'email_error']);

        if ($this->option('failed')) {
            $query->where('status', 'failed');
        }

        $logs = $query->orderBy('processed_at', 'desc')->take(20)->get();

        if ($logs->isEmpty()) {
            $this->info("No email activity found in the last {$hoursBack} hours.");
            return;
        }

        $tableData = [];
        foreach ($logs as $log) {
            $tableData[] = [
                $log->processed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $log->type,
                $log->status,
                substr($log->message ?? '', 0, 50) . (strlen($log->message ?? '') > 50 ? '...' : ''),
                $log->from_address ?? 'N/A',
            ];
        }

        $this->table(['Time', 'Type', 'Status', 'Message', 'From'], $tableData);

        // Summary
        $successful = $logs->where('status', 'success')->count();
        $failed = $logs->where('status', 'failed')->count();
        $skipped = $logs->where('status', 'skipped')->count();

        $this->info('');
        $this->info('Summary:');
        $this->table(['Status', 'Count'], [
            ['Successful', $successful],
            ['Failed', $failed],
            ['Skipped', $skipped],
            ['Total', $logs->count()],
        ]);
    }

    private function checkFailedJobs(): void
    {
        try {
            if (config('queue.default') !== 'database') {
                $this->info('Failed job details only available for database queue driver.');
                return;
            }

            $this->info('Recent Failed Jobs:');

            $failedJobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->take(10)
                ->get();

            if ($failedJobs->isEmpty()) {
                $this->info('No failed jobs found.');
                return;
            }

            foreach ($failedJobs as $job) {
                $this->info('');
                $this->info("Job ID: {$job->id}");
                $this->info("Queue: {$job->queue}");
                $this->info("Failed At: {$job->failed_at}");

                // Try to extract useful info from payload
                $payload = json_decode($job->payload, true);
                if ($payload && isset($payload['displayName'])) {
                    $this->info("Job Type: {$payload['displayName']}");
                }

                // Show exception (truncated)
                $exception = substr($job->exception, 0, 200) . (strlen($job->exception) > 200 ? '...' : '');
                $this->error("Exception: {$exception}");
                $this->info('---');
            }
        } catch (\Exception $e) {
            $this->error('Error checking failed jobs: ' . $e->getMessage());
        }
    }
}
