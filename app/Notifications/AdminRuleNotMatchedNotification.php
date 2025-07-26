<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminRuleNotMatchedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromEmail,
        public string $subject,
        public string $domain,
        public bool $usedDefaultClient = false,
        public ?string $defaultClientName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('No Client Rules Matched - Email Processing')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An email was processed but no client email rules matched the sender.')
            ->line('**Email Details:**')
            ->line('From: ' . $this->fromEmail)
            ->line('Subject: ' . $this->subject)
            ->line('Domain: ' . $this->domain)
            ->line('**Processing Result:**');

        if ($this->usedDefaultClient && $this->defaultClientName) {
            $message->line('The email was assigned to the default client: ' . $this->defaultClientName)
                ->line('You may want to create a specific client email rule for this domain.');
        } else {
            $message->line('No default client is configured, so the email was ignored.')
                ->line('Consider setting up a client email rule for this domain or configuring a default client.');
        }

        return $message->action('Manage Client Email Rules', url('/admin/client-emails'))
            ->line('This notification helps you identify potential new clients or missing email rules.')
            ->line('This is an automated notification from the lead management system.');
    }
}
