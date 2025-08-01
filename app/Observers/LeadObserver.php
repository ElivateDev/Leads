<?php

namespace App\Observers;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewLeadNotification;
use App\Services\EmailProcessingLogger;
use App\Services\AdminNotificationService;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        Log::info('LeadObserver: Lead created', [
            'lead_id' => $lead->id,
            'lead_name' => $lead->name,
            'client_id' => $lead->client_id,
            'client_name' => $lead->client->name,
            'client_email' => $lead->client->email,
        ]);

        Log::info('Client email_notifications: ' . ($lead->client->email_notifications ? 'true' : 'false'));

        if ($lead->client->email_notifications) {
            try {
                // Get all notification emails for this client
                $notificationEmails = $lead->client->getNotificationEmails();

                if (empty($notificationEmails)) {
                    Log::warning('No notification emails configured for client', [
                        'client_id' => $lead->client_id,
                        'client_name' => $lead->client->name,
                    ]);
                    return;
                }

                // Filter notification emails based on campaign preferences
                $filteredEmails = [];
                foreach ($notificationEmails as $email) {
                    // Check if there's a user with this email address
                    $user = \App\Models\User::where('email', $email)
                        ->where('client_id', $lead->client_id)
                        ->first();

                    if ($user) {
                        // Apply campaign filtering for this user
                        if ($user->shouldNotifyForCampaign($lead->campaign)) {
                            $filteredEmails[] = $email;
                        } else {
                            Log::info('Skipping notification due to campaign preferences', [
                                'user_email' => $email,
                                'lead_campaign' => $lead->campaign,
                                'user_notification_campaigns' => $user->getNotificationCampaigns(),
                            ]);
                        }
                    } else {
                        // If no user found, send notification (default behavior for email-only recipients)
                        $filteredEmails[] = $email;
                    }
                }

                if (empty($filteredEmails)) {
                    Log::info('No notifications sent due to campaign filtering', [
                        'lead_campaign' => $lead->campaign,
                        'original_emails' => $notificationEmails,
                    ]);
                    return;
                }

                Log::info('Starting email notification process', [
                    'notification_emails' => $filteredEmails,
                    'total_recipients' => count($filteredEmails),
                    'original_recipients' => count($notificationEmails),
                    'filtered_by_campaign' => $lead->campaign,
                    'smtp_host' => config('mail.mailers.smtp.host'),
                    'smtp_port' => config('mail.mailers.smtp.port'),
                    'smtp_username' => config('mail.mailers.smtp.username'),
                    'mail_from_address' => config('mail.from.address'),
                    'mail_from_name' => config('mail.from.name'),
                    'mail_driver' => config('mail.default'),
                    'queue_driver' => config('queue.default'),
                ]);

                $successfulSends = [];
                $failedSends = [];

                foreach ($filteredEmails as $email) {
                    try {
                        // Validate recipient email before sending
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            Log::warning('Invalid notification email address skipped', [
                                'invalid_email' => $email,
                                'client_id' => $lead->client_id,
                            ]);
                            $failedSends[] = ['email' => $email, 'reason' => 'Invalid email format'];
                            continue;
                        }

                        // Log pre-send details
                        Log::info('About to send notification', [
                            'notification_class' => NewLeadNotification::class,
                            'lead_id' => $lead->id,
                            'lead_name' => $lead->name,
                            'client_id' => $lead->client_id,
                            'client_name' => $lead->client->name,
                            'recipient_email' => $email,
                        ]);

                        // Create a temporary user object for this email
                        $tempClient = clone $lead->client;
                        $tempClient->email = $email;

                        // Send the notification
                        $tempClient->notify(new NewLeadNotification($lead));

                        Log::info('Email notification sent successfully', [
                            'recipient' => $email,
                            'notification_class' => NewLeadNotification::class,
                            'lead_id' => $lead->id,
                            'delivery_timestamp' => now()->toISOString(),
                            'note' => 'Email sent immediately (not queued)',
                        ]);

                        $successfulSends[] = $email;

                        // Log successful notification in our tracking system
                        EmailProcessingLogger::logNotificationSent(
                            $lead->from_email ?? $lead->email ?? 'unknown',
                            'NewLeadNotification',
                            $lead,
                            $lead->client,
                            [
                                'notification_class' => NewLeadNotification::class,
                                'recipient_email' => $email,
                                'lead_name' => $lead->name,
                                'smtp_host' => config('mail.mailers.smtp.host'),
                                'delivery_status' => 'sent_immediately',
                                'delivery_timestamp' => now()->toISOString(),
                                'note' => 'Email sent immediately (not queued)',
                            ]
                        );
                    } catch (\Exception $individualEmailException) {
                        Log::error('Failed to send notification to individual email', [
                            'recipient_email' => $email,
                            'error' => $individualEmailException->getMessage(),
                            'lead_id' => $lead->id,
                            'client_id' => $lead->client_id,
                        ]);
                        $failedSends[] = ['email' => $email, 'reason' => $individualEmailException->getMessage()];
                    }
                }

                // Log summary
                Log::info('Email notification summary', [
                    'successful_sends' => count($successfulSends),
                    'failed_sends' => count($failedSends),
                    'successful_emails' => $successfulSends,
                    'failed_emails' => $failedSends,
                    'lead_id' => $lead->id,
                    'client_id' => $lead->client_id,
                ]);
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::error('SMTP Transport Error - Email delivery failed', [
                    'error_type' => 'smtp_transport_error',
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'recipient' => $lead->client->email,
                    'lead_id' => $lead->id,
                    'smtp_config' => [
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port'),
                        'username' => config('mail.mailers.smtp.username'),
                        'encryption' => config('mail.mailers.smtp.encryption'),
                        'timeout' => config('mail.mailers.smtp.timeout'),
                        'local_domain' => config('mail.mailers.smtp.local_domain'),
                    ],
                    'mail_config' => [
                        'from_address' => config('mail.from.address'),
                        'from_name' => config('mail.from.name'),
                        'driver' => config('mail.default'),
                    ],
                    'troubleshooting_tips' => [
                        'Check SMTP credentials are correct',
                        'Verify SMTP host and port are accessible',
                        'Check firewall/network connectivity',
                        'Verify from address is authorized to send',
                        'Check SMTP server logs for rejection reasons',
                        'Test with telnet: telnet ' . config('mail.mailers.smtp.host') . ' ' . config('mail.mailers.smtp.port')
                    ]
                ]);

                // Send admin notification for SMTP transport error
                AdminNotificationService::notifyEmailError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'Lead #' . $lead->id . ' - ' . $lead->name,
                    'SMTP Transport Error: ' . $e->getMessage(),
                    'smtp_transport_error',
                    [
                        'lead_id' => $lead->id,
                        'client_name' => $lead->client->name,
                        'recipient_email' => $lead->client->email,
                        'smtp_host' => config('mail.mailers.smtp.host'),
                        'smtp_port' => config('mail.mailers.smtp.port'),
                    ]
                );

                EmailProcessingLogger::logError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'SMTP Transport Error: ' . $e->getMessage(),
                    $e,
                    [
                        'error_type' => 'smtp_transport_error',
                        'recipient_email' => $lead->client->email,
                        'smtp_host' => config('mail.mailers.smtp.host'),
                        'smtp_port' => config('mail.mailers.smtp.port'),
                        'lead_id' => $lead->id,
                    ]
                );
            } catch (\Symfony\Component\Mime\Exception\RfcComplianceException $e) {
                Log::error('Email RFC Compliance Error', [
                    'error' => $e->getMessage(),
                    'recipient' => $lead->client->email,
                ]);

                // Send admin notification for RFC compliance error
                AdminNotificationService::notifyEmailError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'Lead #' . $lead->id . ' - ' . $lead->name,
                    'Email RFC Compliance Error: ' . $e->getMessage(),
                    'rfc_compliance_error',
                    [
                        'lead_id' => $lead->id,
                        'client_name' => $lead->client->name,
                        'recipient_email' => $lead->client->email,
                    ]
                );

                EmailProcessingLogger::logError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'Email RFC Compliance Error: ' . $e->getMessage(),
                    $e,
                    [
                        'error_type' => 'rfc_compliance_error',
                        'recipient_email' => $lead->client->email,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('General email notification error', [
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'recipient' => $lead->client->email,
                    'lead_id' => $lead->id,
                ]);

                // Send admin notification for general email error
                AdminNotificationService::notifyEmailError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'Lead #' . $lead->id . ' - ' . $lead->name,
                    'Failed to send new lead notification: ' . $e->getMessage(),
                    'general_email_error',
                    [
                        'lead_id' => $lead->id,
                        'client_name' => $lead->client->name,
                        'recipient_email' => $lead->client->email,
                        'exception_class' => get_class($e),
                        'notification_class' => NewLeadNotification::class,
                    ]
                );

                EmailProcessingLogger::logError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'Failed to send new lead notification: ' . $e->getMessage(),
                    $e,
                    [
                        'error_type' => 'general_email_error',
                        'notification_class' => NewLeadNotification::class,
                        'recipient_email' => $lead->client->email,
                        'lead_id' => $lead->id,
                        'client_id' => $lead->client_id,
                    ]
                );
            }
        } else {
            Log::info('Notifications disabled for client', [
                'client_id' => $lead->client_id,
                'client_name' => $lead->client->name,
            ]);

            // Log that notifications are disabled
            EmailProcessingLogger::logEvent(
                $lead->from_email ?? $lead->email ?? 'unknown',
                'notification_sent',
                'skipped',
                'Notifications disabled for client: ' . $lead->client->name,
                [
                    'client_id' => $lead->client_id,
                    'client_name' => $lead->client->name,
                    'email_notifications_enabled' => false,
                ]
            );
        }
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead): void
    {
        // Check if the status was changed
        if ($lead->isDirty('status')) {
            $this->updateMatchingLeadsStatus($lead);
        }
    }

    /**
     * Update status for leads with matching contact information across other clients
     */
    private function updateMatchingLeadsStatus(Lead $lead): void
    {
        try {
            Log::info('Updating status for matching leads', [
                'lead_id' => $lead->id,
                'new_status' => $lead->status,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
            ]);

            // Build query to find matching leads in other clients
            $query = Lead::where('id', '!=', $lead->id) // Exclude current lead
                ->where('client_id', '!=', $lead->client_id); // Exclude same client

            // Match on name (required) plus either email or phone
            $query->where('name', $lead->name);

            $query->where(function ($q) use ($lead) {
                if ($lead->email) {
                    $q->where('email', $lead->email);
                }
                if ($lead->phone) {
                    $q->orWhere('phone', $lead->phone);
                }
            });

            $matchingLeads = $query->get();

            if ($matchingLeads->isEmpty()) {
                Log::info('No matching leads found for status update');
                return;
            }

            $updatedCount = 0;
            foreach ($matchingLeads as $matchingLead) {
                // Check if the target client has this status in their dispositions
                $client = $matchingLead->client;
                $availableStatuses = array_keys($client->getLeadDispositions());

                if (in_array($lead->status, $availableStatuses)) {
                    $oldStatus = $matchingLead->status;
                    $matchingLead->status = $lead->status;
                    $matchingLead->save();
                    $updatedCount++;

                    Log::info('Updated matching lead status', [
                        'lead_id' => $matchingLead->id,
                        'client_id' => $matchingLead->client_id,
                        'client_name' => $client->name,
                        'old_status' => $oldStatus,
                        'new_status' => $lead->status,
                    ]);
                } else {
                    Log::info('Skipping lead - status not available for client', [
                        'lead_id' => $matchingLead->id,
                        'client_id' => $matchingLead->client_id,
                        'client_name' => $client->name,
                        'attempted_status' => $lead->status,
                        'available_statuses' => $availableStatuses,
                    ]);
                }
            }

            Log::info('Completed matching leads status update', [
                'total_found' => $matchingLeads->count(),
                'total_updated' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating matching leads status', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the Lead "deleted" event.
     */
    public function deleted(Lead $lead): void
    {
        //
    }

    /**
     * Handle the Lead "restored" event.
     */
    public function restored(Lead $lead): void
    {
        //
    }

    /**
     * Handle the Lead "force deleted" event.
     */
    public function forceDeleted(Lead $lead): void
    {
        //
    }
}
