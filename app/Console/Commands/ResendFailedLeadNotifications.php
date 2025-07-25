<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Client;
use App\Notifications\NewLeadNotification;
use App\Services\EmailProcessingLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResendFailedLeadNotifications extends Command
{
    protected $signature = 'leads:resend-notifications
                            {--from= : Start date (Y-m-d H:i:s format)}
                            {--to= : End date (Y-m-d H:i:s format)}
                            {--lead-id= : Specific lead ID to resend}
                            {--dry-run : Show what would be sent without actually sending}
                            {--clear-failed : Clear failed jobs after processing}';

    protected $description = 'Resend failed lead email notifications';

    public function handle()
    {
        $this->info('🔍 Scanning for failed lead notifications...');

        // Get parameters
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subDays(7);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $leadId = $this->option('lead-id');
        $dryRun = $this->option('dry-run');
        $clearFailed = $this->option('clear-failed');

        $this->info("📅 Date range: {$from->format('Y-m-d H:i:s')} to {$to->format('Y-m-d H:i:s')}");

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No emails will be sent');
        }

        // Find failed notification jobs
        $failedJobs = $this->getFailedNotificationJobs($from, $to);
        $this->info("❌ Found {$failedJobs->count()} failed notification jobs");

        // Find leads to process
        $leadsQuery = Lead::with('client')
            ->whereHas('client', function ($query) {
                $query->where('email_notifications', true);
            });

        if ($leadId) {
            $leadsQuery->where('id', $leadId);
        } else {
            $leadsQuery->whereBetween('created_at', [$from, $to]);
        }

        $leads = $leadsQuery->get();
        $this->info("📧 Found {$leads->count()} leads with notifications enabled in date range");

        if ($leads->isEmpty()) {
            $this->warn('No leads found to process.');
            return 0;
        }

        // Show leads that will be processed
        $this->table(
            ['Lead ID', 'Name', 'Email', 'Created At', 'Client', 'Notification Emails'],
            $leads->map(function ($lead) {
                return [
                    $lead->id,
                    $lead->name,
                    $lead->email ?? 'N/A',
                    $lead->created_at->format('Y-m-d H:i:s'),
                    $lead->client->name,
                    implode(', ', $lead->client->getNotificationEmails())
                ];
            })
        );

        if (!$dryRun && !$this->confirm('Do you want to proceed with sending notifications for these leads?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Process each lead
        $successCount = 0;
        $failureCount = 0;
        $totalEmails = 0;

        foreach ($leads as $lead) {
            $this->info("📤 Processing Lead #{$lead->id}: {$lead->name}");

            $notificationEmails = $lead->client->getNotificationEmails();

            if (empty($notificationEmails)) {
                $this->warn("  ⚠️  No notification emails configured for client: {$lead->client->name}");
                continue;
            }

            foreach ($notificationEmails as $email) {
                $totalEmails++;

                if ($dryRun) {
                    $this->line("  📧 Would send to: {$email}");
                    $successCount++;
                    continue;
                }

                try {
                    // Validate email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->error("  ❌ Invalid email format: {$email}");
                        $failureCount++;
                        continue;
                    }

                    // Create temp client object for this email
                    $tempClient = clone $lead->client;
                    $tempClient->email = $email;

                    // Send notification
                    $tempClient->notify(new NewLeadNotification($lead));

                    $this->info("  ✅ Sent to: {$email}");
                    $successCount++;

                    // Log the successful resend
                    EmailProcessingLogger::logNotificationSent(
                        $lead->from_email ?? $lead->email ?? 'unknown',
                        'NewLeadNotification (Resent)',
                        $lead,
                        $lead->client,
                        [
                            'notification_class' => NewLeadNotification::class,
                            'recipient_email' => $email,
                            'lead_name' => $lead->name,
                            'delivery_status' => 'resent_successfully',
                            'delivery_timestamp' => now()->toISOString(),
                            'resend_reason' => 'Failed notification recovery',
                        ]
                    );

                } catch (\Exception $e) {
                    $this->error("  ❌ Failed to send to {$email}: " . $e->getMessage());
                    $failureCount++;

                    // Log the failure
                    EmailProcessingLogger::logError(
                        $lead->from_email ?? $lead->email ?? 'unknown',
                        'Failed to resend notification: ' . $e->getMessage(),
                        $e,
                        [
                            'error_type' => 'notification_resend_error',
                            'recipient_email' => $email,
                            'lead_id' => $lead->id,
                        ]
                    );
                }

                // Small delay to avoid overwhelming the mail server
                if (!$dryRun) {
                    usleep(100000); // 0.1 seconds
                }
            }
        }

        // Clear failed jobs if requested
        if ($clearFailed && !$dryRun && $failedJobs->count() > 0) {
            if ($this->confirm('Clear the failed notification jobs from the queue?')) {
                foreach ($failedJobs as $job) {
                    DB::table('failed_jobs')->where('id', $job->id)->delete();
                }
                $this->info("🧹 Cleared {$failedJobs->count()} failed jobs");
            }
        }

        // Summary
        $this->newLine();
        $this->info('📊 SUMMARY:');
        $this->info("  Total emails processed: {$totalEmails}");
        $this->info("  Successful sends: {$successCount}");
        $this->info("  Failed sends: {$failureCount}");

        if ($dryRun) {
            $this->warn('  (This was a dry run - no emails were actually sent)');
        }

        return 0;
    }

    private function getFailedNotificationJobs($from, $to)
    {
        return DB::table('failed_jobs')
            ->whereBetween('failed_at', [$from, $to])
            ->where(function ($query) {
                $query->where('exception', 'like', '%NewLeadNotification%')
                    ->orWhere('exception', 'like', '%SendQueuedNotifications%');
            })
            ->orderBy('failed_at', 'desc')
            ->get();
    }
}
