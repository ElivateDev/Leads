<?php

namespace App\Observers;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewLeadNotification;
use App\Services\EmailProcessingLogger;

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
                Log::info('Starting email notification process', [
                    'recipient_email' => $lead->client->email,
                    'smtp_host' => config('mail.mailers.smtp.host'),
                    'smtp_port' => config('mail.mailers.smtp.port'),
                    'smtp_username' => config('mail.mailers.smtp.username'),
                    'mail_from_address' => config('mail.from.address'),
                ]);

                // Send the notification
                $lead->client->notify(new NewLeadNotification($lead));

                Log::info('Email notification sent successfully', [
                    'recipient' => $lead->client->email,
                    'notification_class' => NewLeadNotification::class,
                ]);

                // Log successful notification in our tracking system
                EmailProcessingLogger::logNotificationSent(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'NewLeadNotification',
                    $lead,
                    $lead->client,
                    [
                        'notification_class' => NewLeadNotification::class,
                        'recipient_email' => $lead->client->email,
                        'lead_name' => $lead->name,
                        'smtp_host' => config('mail.mailers.smtp.host'),
                        'delivery_status' => 'sent_successfully',
                    ]
                );
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::error('SMTP Transport Error', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'recipient' => $lead->client->email,
                    'smtp_config' => [
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port'),
                        'username' => config('mail.mailers.smtp.username'),
                        'timeout' => config('mail.mailers.smtp.timeout'),
                    ],
                ]);

                EmailProcessingLogger::logError(
                    $lead->from_email ?? $lead->email ?? 'unknown',
                    'SMTP Transport Error: ' . $e->getMessage(),
                    $e,
                    [
                        'error_type' => 'smtp_transport_error',
                        'recipient_email' => $lead->client->email,
                        'smtp_host' => config('mail.mailers.smtp.host'),
                    ]
                );
            } catch (\Symfony\Component\Mime\Exception\RfcComplianceException $e) {
                Log::error('Email RFC Compliance Error', [
                    'error' => $e->getMessage(),
                    'recipient' => $lead->client->email,
                ]);

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
        //
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
