<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'client_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @inheritDoc
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->role === 'admin';
        }

        if ($panel->getId() === 'client') {
            // If we're impersonating, allow access for client users
            if (session('is_impersonating') && $this->role === 'client') {
                return true;
            }

            $isClient = $this->role === 'client' && $this->client_id;
            $isAdmin = $this->role === 'admin' && $this->client_id;
            return $isClient || $isAdmin;
        }

        return false;
    }

    /**
     * Get the client associated with this user
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user's preferences
     */
    public function preferences()
    {
        return $this->hasMany(UserPreference::class);
    }

    /**
     * Get a specific preference value
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        return UserPreference::getValue($this->id, $key, $default);
    }

    /**
     * Set a specific preference value
     */
    public function setPreference(string $key, mixed $value): void
    {
        UserPreference::setValue($this->id, $key, $value);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Computed attribute for email notifications enabled status
     */
    public function getEmailNotificationsEnabledAttribute(): bool
    {
        return $this->getPreference('admin_notify_email_processed', false);
    }

    /**
     * Computed attribute for error notifications enabled status
     */
    public function getErrorNotificationsEnabledAttribute(): bool
    {
        return $this->getPreference('admin_notify_errors', true);
    }

    /**
     * Computed attribute for rules notifications enabled status
     */
    public function getRulesNotificationsEnabledAttribute(): bool
    {
        return $this->getPreference('admin_notify_rules_not_matched', false);
    }

    /**
     * Get the campaigns this user can view
     */
    public function getVisibleCampaigns(): array
    {
        return $this->getPreference('visible_campaigns', []);
    }

    /**
     * Get the campaigns this user wants notifications for
     */
    public function getNotificationCampaigns(): array
    {
        return $this->getPreference('notification_campaigns', []);
    }

    /**
     * Check if user should see a specific campaign
     */
    public function canViewCampaign(?string $campaign): bool
    {
        if (empty($campaign)) {
            return true; // Always show leads without campaigns
        }

        $visibleCampaigns = $this->getVisibleCampaigns();

        // If no preferences set, show all campaigns
        if (empty($visibleCampaigns)) {
            return true;
        }

        return in_array($campaign, $visibleCampaigns);
    }

    /**
     * Check if user should receive notifications for a specific campaign
     */
    public function shouldNotifyForCampaign(?string $campaign): bool
    {
        if (empty($campaign)) {
            return true; // Always notify for leads without campaigns
        }

        $notificationCampaigns = $this->getNotificationCampaigns();

        // If no preferences set, notify for all campaigns
        if (empty($notificationCampaigns)) {
            return true;
        }

        return in_array($campaign, $notificationCampaigns);
    }
}
