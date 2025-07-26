<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminDuplicateLeadNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromEmail,
        public string $subject,
        public Lead $existingLead,
        public string $clientName,
        public array $duplicateDetails = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Duplicate Lead Detected - ' . $this->existingLead->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A duplicate lead submission has been detected and handled by the system.')
            ->line('**Email Details:**')
            ->line('From: ' . $this->fromEmail)
            ->line('Subject: ' . $this->subject)
            ->line('**Existing Lead:**')
            ->line('Lead ID: #' . $this->existingLead->id)
            ->line('Name: ' . $this->existingLead->name)
            ->line('Email: ' . ($this->existingLead->email ?: 'Not provided'))
            ->line('Phone: ' . ($this->existingLead->phone ?: 'Not provided'))
            ->line('Client: ' . $this->clientName)
            ->line('Original Created: ' . $this->existingLead->created_at->format('Y-m-d H:i:s'));

        if (!empty($this->duplicateDetails)) {
            $message->line('**Duplicate Detection Details:**');
            foreach ($this->duplicateDetails as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $message->line('• ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
            }
        }

        return $message->line('The new submission has been appended to the existing lead record.')
            ->action('View Lead', url('/admin/leads/' . $this->existingLead->id))
            ->line('This helps prevent duplicate lead entries while preserving all communication history.')
            ->line('This is an automated notification from the lead management system.');
    }
}
