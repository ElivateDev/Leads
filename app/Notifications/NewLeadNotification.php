<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead $lead
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Lead Received')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have received a new lead.')
            ->line('**Lead Details:**')
            ->line('Name: ' . $this->lead->name)
            ->line('Email: ' . ($this->lead->email ?: 'Not provided'))
            ->line('Phone: ' . ($this->lead->phone ?: 'Not provided'))
            ->line('Source: ' . ucfirst($this->lead->source))
            ->when($this->lead->message, function ($message) {
                return $message->line('Message: ' . $this->lead->message);
            })
            ->action('View Lead in Client Portal', url('/client/leads/' . $this->lead->id))
            ->line('Thank you for using Elivate CRM!');
    }
}
