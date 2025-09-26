<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminCampaignRuleNotMatchedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('No Campaign Rules Matched - Lead Processing')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new lead was created but no campaign distribution rules matched.')
            ->line('**Lead Details:**')
            ->line('Name: ' . $this->lead->first_name . ' ' . $this->lead->last_name)
            ->line('Email: ' . $this->lead->email)
            ->line('Phone: ' . ($this->lead->phone ?? 'Not provided'))
            ->line('Client: ' . ($this->lead->client->name ?? 'Unknown'))
            ->line('Source: ' . ($this->lead->source ?? 'Unknown'))
            ->line('Created: ' . $this->lead->created_at->format('M j, Y g:i A'))
            ->line('**Processing Result:**')
            ->line('The lead was created successfully but was not distributed to any campaigns because no campaign rules matched the lead criteria.')
            ->line('You may want to review your campaign rules or create new ones to ensure leads are properly distributed.')
            ->action('Manage Campaign Rules', url('/admin/campaign-rules'))
            ->line('Consider reviewing the lead details and creating appropriate campaign rules if this lead should have been distributed.')
            ->line('This is an automated notification from the lead management system.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->first_name . ' ' . $this->lead->last_name,
            'lead_email' => $this->lead->email,
            'client_name' => $this->lead->client->name ?? 'Unknown',
        ];
    }
}