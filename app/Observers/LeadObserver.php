<?php

namespace App\Observers;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewLeadNotification;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        Log::info('LeadObserver: Lead created, checking if should send notification');
        Log::info('Client email_notifications: ' . ($lead->client->email_notifications ? 'true' : 'false'));

        if ($lead->client->email_notifications) {
            Log::info('Sending notification to: ' . $lead->client->email);
            $lead->client->notify(new NewLeadNotification($lead));
            Log::info('Notification sent successfully');
        } else {
            Log::info('Notifications disabled for client');
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
