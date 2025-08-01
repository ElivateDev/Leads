<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'email_notifications',
        'notification_emails',
        'lead_dispositions',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'notification_emails' => 'array',
        'lead_dispositions' => 'array',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function clientEmails(): HasMany
    {
        return $this->hasMany(ClientEmail::class);
    }

    public function campaignRules(): HasMany
    {
        return $this->hasMany(CampaignRule::class);
    }

    public function leadSourceRules(): HasMany
    {
        return $this->hasMany(LeadSourceRule::class);
    }

    /**
     * Get the default lead dispositions
     */
    public static function getDefaultDispositions(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
            'lost' => 'Lost'
        ];
    }

    /**
     * Get the client's lead dispositions or default ones
     */
    public function getLeadDispositions(): array
    {
        return $this->lead_dispositions ?? self::getDefaultDispositions();
    }

    /**
     * Set the client's lead dispositions
     */
    public function setLeadDispositions(array $dispositions): void
    {
        $this->lead_dispositions = $dispositions;
        $this->save();
    }

    /**
     * Get the client's notification emails or fall back to primary email
     */
    public function getNotificationEmails(): array
    {
        // If notification_emails is set and not empty, use it
        if (!empty($this->notification_emails)) {
            // Handle both formats: array of strings or array of objects with 'email' property
            $emails = [];
            foreach ($this->notification_emails as $item) {
                if (is_string($item)) {
                    // Simple string format
                    $emails[] = $item;
                } elseif (is_array($item) && isset($item['email'])) {
                    // Object format from Filament Repeater
                    $emails[] = $item['email'];
                }
            }

            // Filter out empty emails
            $emails = array_filter($emails, function ($email) {
                return !empty(trim($email));
            });

            if (!empty($emails)) {
                return array_values($emails); // Reindex array
            }
        }

        // Otherwise, fall back to the primary email
        return $this->email ? [$this->email] : [];
    }

    /**
     * Set the client's notification emails
     */
    public function setNotificationEmails(array $emails): void
    {
        // Filter out empty emails and reindex array
        $filtered = array_values(array_filter($emails, function ($email) {
            return !empty(trim($email));
        }));

        $this->notification_emails = empty($filtered) ? null : $filtered;
        $this->save();
    }
}
