<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Client;
use App\Notifications\NewLeadNotification;
use App\Services\EmailProcessingLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendLeadNotifications extends Command
{
    protected $signature = 'leads:send-notifications
                            {--lead-id= : Specific lead ID to send notifications for}
                            {--client-id= : Send notifications for all leads of a specific client}
                            {--from= : Start date for leads (Y-m-d H:i:s format)}
                            {--to= : End date for leads (Y-m-d H:i:s format)}
                            {--force : Force send even if notifications were already sent}
                            {--dry-run : Show what would be sent without actually sending}
                            {--source= : Only send for leads from specific source (email, manual, api, etc)}';

    protected $description = 'Send email notifications for manually created leads or specific leads';

    public function handle()
    {
        $this->info('📧 Lead Notification Sender');

        // Get parameters
        $leadId = $this->option('lead-id');
        $clientId = $this->option('client-id');
        $from = $this->option('from');
        $to = $this->option('to');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $source = $this->option('source');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No emails will be sent');
        }

        // Build query
        $leadsQuery = Lead::with('client')
            ->whereHas('client', function ($query) {
                $query->where('email_notifications', true);
            });

        // Apply filters
        if ($leadId) {
            $leadsQuery->where('id', $leadId);
            $this->info("🎯 Targeting specific lead ID: {$leadId}");
        }

        if ($clientId) {
            $leadsQuery->where('client_id', $clientId);
            $this->info("🏢 Targeting client ID: {$clientId}");
        }

        if ($from || $to) {
            $fromDate = $from ? \Carbon\Carbon::parse($from) : \Carbon\Carbon::now()->subDays(30);
            $toDate = $to ? \Carbon\Carbon::parse($to) : \Carbon\Carbon::now();
            $leadsQuery->whereBetween('created_at', [$fromDate, $toDate]);
            $this->info("📅 Date range: {$fromDate->format('Y-m-d H:i:s')} to {$toDate->format('Y-m-d H:i:s')}");
        }

        if ($source) {
            $leadsQuery->where('source', $source);
            $this->info("📍 Source filter: {$source}");
        }

        $leads = $leadsQuery->orderBy('created_at', 'desc')->get();

        if ($leads->isEmpty()) {
            $this->warn('❌ No leads found matching your criteria.');
            return 0;
        }

        $this->info("📋 Found {$leads->count()} leads to process");

        // Check which leads already have notifications sent (unless forcing)
        $leadsToProcess = collect();
        $leadsSkipped = collect();

        foreach ($leads as $lead) {
            if (!$force && $this->hasNotificationBeenSent($lead)) {
                $leadsSkipped->push($lead);
            } else {
                $leadsToProcess->push($lead);
            }
        }

        if ($leadsSkipped->count() > 0) {
            $this->warn("⚠️  Skipping {$leadsSkipped->count()} leads that already have notifications sent (use --force to override)");
        }

        if ($leadsToProcess->isEmpty()) {
            $this->info('✅ No leads need notification sending.');
            return 0;
        }

        // Show leads that will be processed
        $this->table(
            ['Lead ID', 'Name', 'Email', 'Source', 'Created At', 'Client', 'Notification Emails', 'Status'],
            $leadsToProcess->map(function ($lead) use ($force) {
                $alreadySent = $this->hasNotificationBeenSent($lead);
                $status = $alreadySent && $force ? 'Force Resend' : ($alreadySent ? 'Already Sent' : 'New');

                return [
                    $lead->id,
                    $lead->name,
                    $lead->email ?? 'N/A',
                    $lead->source ?? 'unknown',
                    $lead->created_at->format('Y-m-d H:i:s'),
                    $lead->client->name,
                    implode(', ', $lead->client->getNotificationEmails()),
                    $status
                ];
            })
        );

        if (!$dryRun && !$this->confirm("Do you want to send notifications for {$leadsToProcess->count()} leads?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Process each lead
        $successCount = 0;
        $failureCount = 0;
        $totalEmails = 0;

        foreach ($leadsToProcess as $lead) {
            $this->info("📤 Processing Lead #{$lead->id}: {$lead->name} (Source: {$lead->source})");

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

                    // Log the notification
                    $logMessage = $force ? 'NewLeadNotification (Manual Force Send)' : 'NewLeadNotification (Manual Send)';
                    EmailProcessingLogger::logNotificationSent(
                        $lead->from_email ?? $lead->email ?? 'manual_entry',
                        $logMessage,
                        $lead,
                        $lead->client,
                        [
                            'notification_class' => NewLeadNotification::class,
                            'recipient_email' => $email,
                            'lead_name' => $lead->name,
                            'delivery_status' => 'sent_manually',
                            'delivery_timestamp' => now()->toISOString(),
                            'send_type' => $force ? 'manual_force_send' : 'manual_send',
                            'lead_source' => $lead->source,
                        ]
                    );

                } catch (\Exception $e) {
                    $this->error("  ❌ Failed to send to {$email}: " . $e->getMessage());
                    $failureCount++;

                    // Log the failure
                    EmailProcessingLogger::logError(
                        $lead->from_email ?? $lead->email ?? 'manual_entry',
                        'Failed to send manual notification: ' . $e->getMessage(),
                        $e,
                        [
                            'error_type' => 'manual_notification_error',
                            'recipient_email' => $email,
                            'lead_id' => $lead->id,
                            'lead_source' => $lead->source,
                        ]
                    );
                }

                // Small delay to avoid overwhelming the mail server
                if (!$dryRun) {
                    usleep(100000); // 0.1 seconds
                }
            }
        }

        // Summary
        $this->newLine();
        $this->info('📊 SUMMARY:');
        $this->info("  Total leads processed: {$leadsToProcess->count()}");
        $this->info("  Total emails sent: {$totalEmails}");
        $this->info("  Successful sends: {$successCount}");
        $this->info("  Failed sends: {$failureCount}");

        if ($leadsSkipped->count() > 0) {
            $this->info("  Leads skipped (already sent): {$leadsSkipped->count()}");
        }

        if ($dryRun) {
            $this->warn('  (This was a dry run - no emails were actually sent)');
        }

        return 0;
    }

    /**
     * Check if notification has already been sent for this lead
     */
    private function hasNotificationBeenSent(Lead $lead): bool
    {
        return DB::table('email_processing_logs')
            ->where('lead_id', $lead->id)
            ->where('type', 'notification_sent')
            ->where('status', 'success')
            ->exists();
    }
}
