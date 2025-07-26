<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminEmailProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromEmail,
        public string $subject,
        public array $matchedClients,
        public array $createdLeads,
        public string $source,
        public ?string $campaign = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $leadCount = count($this->createdLeads);
        $clientCount = count($this->matchedClients);
        
        $message = (new MailMessage)
            ->subject('Email Processed - ' . $leadCount . ' Lead(s) Created')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An email has been successfully processed by the lead management system.')
            ->line('**Email Details:**')
            ->line('From: ' . $this->fromEmail)
            ->line('Subject: ' . $this->subject)
            ->line('Source: ' . ucfirst($this->source));

        if ($this->campaign) {
            $message->line('Campaign: ' . $this->campaign);
        }

        $message->line('**Processing Results:**')
            ->line('Leads Created: ' . $leadCount)
            ->line('Clients Matched: ' . $clientCount);

        if ($clientCount > 0) {
            $message->line('**Matched Clients:**');
            foreach ($this->matchedClients as $client) {
                $message->line('• ' . $client['name'] . ' (' . $client['email'] . ')');
            }
        }

        if ($leadCount > 0) {
            $message->line('**Created Leads:**');
            foreach ($this->createdLeads as $lead) {
                $message->line('• Lead #' . $lead['id'] . ': ' . $lead['name'] . ' for ' . $lead['client_name']);
            }
        }

        return $message->action('View Email Processing Logs', url('/admin/email-processing-logs'))
            ->line('This is an automated notification from the lead management system.');
    }
}
