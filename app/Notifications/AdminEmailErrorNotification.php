<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminEmailErrorNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromEmail,
        public string $subject,
        public string $errorMessage,
        public string $errorType,
        public array $context = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Email Processing Error - ' . $this->errorType)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An error occurred while processing an email in the lead management system.')
            ->line('**Email Details:**')
            ->line('From: ' . $this->fromEmail)
            ->line('Subject: ' . $this->subject)
            ->line('**Error Details:**')
            ->line('Type: ' . $this->errorType)
            ->line('Message: ' . $this->errorMessage);

        if (!empty($this->context)) {
            $message->line('**Additional Context:**');
            foreach ($this->context as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $message->line('• ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
            }
        }

        return $message->action('View Email Processing Logs', url('/admin/email-processing-logs'))
            ->line('Please review the error and take appropriate action if needed.')
            ->line('This is an automated notification from the lead management system.');
    }
}
