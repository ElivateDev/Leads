<?php

namespace App\Services;

use App\Models\User;
use App\Models\Lead;
use App\Notifications\AdminEmailProcessedNotification;
use App\Notifications\AdminEmailErrorNotification;
use App\Notifications\AdminRuleNotMatchedNotification;
use App\Notifications\AdminCampaignRuleNotMatchedNotification;
use App\Notifications\AdminDuplicateLeadNotification;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    /**
     * Send notification when an email is successfully processed
     */
    public static function notifyEmailProcessed(
        string $fromEmail,
        string $subject,
        array $matchedClients,
        array $createdLeads,
        string $source,
        ?string $campaign = null
    ): void {
        if (!self::shouldNotifyForEmailProcessing()) {
            return;
        }

        $admins = self::getAdminsToNotify('admin_notify_email_processed');
        
        foreach ($admins as $admin) {
            try {
                $admin->notify(new AdminEmailProcessedNotification(
                    $fromEmail,
                    $subject,
                    $matchedClients,
                    $createdLeads,
                    $source,
                    $campaign
                ));

                Log::info('Admin email processed notification sent', [
                    'admin_email' => $admin->email,
                    'from_email' => $fromEmail,
                    'leads_created' => count($createdLeads),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin email processed notification', [
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                    'from_email' => $fromEmail,
                ]);
            }
        }
    }

    /**
     * Send notification when an email processing error occurs
     */
    public static function notifyEmailError(
        string $fromEmail,
        string $subject,
        string $errorMessage,
        string $errorType,
        array $context = []
    ): void {
        if (!self::shouldNotifyForErrors()) {
            return;
        }

        $admins = self::getAdminsToNotify('admin_notify_errors');
        
        foreach ($admins as $admin) {
            try {
                $admin->notify(new AdminEmailErrorNotification(
                    $fromEmail,
                    $subject,
                    $errorMessage,
                    $errorType,
                    $context
                ));

                Log::info('Admin email error notification sent', [
                    'admin_email' => $admin->email,
                    'error_type' => $errorType,
                    'from_email' => $fromEmail,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin email error notification', [
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                    'original_error_type' => $errorType,
                ]);
            }
        }
    }

    /**
     * Send notification when no client rules match an email
     */
    public static function notifyRuleNotMatched(
        string $fromEmail,
        string $subject,
        string $domain,
        bool $usedDefaultClient = false,
        ?string $defaultClientName = null
    ): void {
        if (!self::shouldNotifyForUnmatchedRules()) {
            return;
        }

        $admins = self::getAdminsToNotify('admin_notify_rules_not_matched');
        
        foreach ($admins as $admin) {
            try {
                $admin->notify(new AdminRuleNotMatchedNotification(
                    $fromEmail,
                    $subject,
                    $domain,
                    $usedDefaultClient,
                    $defaultClientName
                ));

                Log::info('Admin rule not matched notification sent', [
                    'admin_email' => $admin->email,
                    'from_email' => $fromEmail,
                    'domain' => $domain,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin rule not matched notification', [
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                    'from_email' => $fromEmail,
                ]);
            }
        }
    }

    /**
     * Send notification when a duplicate lead is detected
     */
    public static function notifyDuplicateLead(
        string $fromEmail,
        string $subject,
        Lead $existingLead,
        string $clientName,
        array $duplicateDetails = []
    ): void {
        if (!self::shouldNotifyForDuplicateLeads()) {
            return;
        }

        $admins = self::getAdminsToNotify('admin_notify_duplicate_leads');
        
        foreach ($admins as $admin) {
            try {
                $admin->notify(new AdminDuplicateLeadNotification(
                    $fromEmail,
                    $subject,
                    $existingLead,
                    $clientName,
                    $duplicateDetails
                ));

                Log::info('Admin duplicate lead notification sent', [
                    'admin_email' => $admin->email,
                    'existing_lead_id' => $existingLead->id,
                    'from_email' => $fromEmail,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin duplicate lead notification', [
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                    'existing_lead_id' => $existingLead->id,
                ]);
            }
        }
    }

    /**
     * Send notification when no campaign rules match a lead
     */
    public static function notifyCampaignRuleNotMatched(Lead $lead): void
    {
        if (!self::shouldNotifyForCampaignRulesNotMatched()) {
            return;
        }

        $admins = self::getAdminsToNotify('admin_notify_campaign_rules_not_matched');
        
        foreach ($admins as $admin) {
            try {
                $admin->notify(new AdminCampaignRuleNotMatchedNotification($lead));

                Log::info('Admin campaign rule not matched notification sent', [
                    'admin_email' => $admin->email,
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'client_name' => $lead->client->name ?? 'Unknown',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin campaign rule not matched notification', [
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                    'lead_id' => $lead->id,
                ]);
            }
        }
    }

    /**
     * Check if admin notifications are enabled for email processing
     */
    private static function shouldNotifyForEmailProcessing(): bool
    {
        return config('app.admin_notifications.email_processed', false) ||
               self::hasAdminWithPreference('admin_notify_email_processed', true);
    }

    /**
     * Check if admin notifications are enabled for errors
     */
    private static function shouldNotifyForErrors(): bool
    {
        return config('app.admin_notifications.errors', true) ||
               self::hasAdminWithPreference('admin_notify_errors', true);
    }

    /**
     * Check if admin notifications are enabled for unmatched rules
     */
    private static function shouldNotifyForUnmatchedRules(): bool
    {
        return config('app.admin_notifications.rules_not_matched', false) ||
               self::hasAdminWithPreference('admin_notify_rules_not_matched', true);
    }

    /**
     * Check if admin notifications are enabled for duplicate leads
     */
    private static function shouldNotifyForDuplicateLeads(): bool
    {
        return config('app.admin_notifications.duplicate_leads', false) ||
               self::hasAdminWithPreference('admin_notify_duplicate_leads', true);
    }

    /**
     * Check if admin notifications are enabled for campaign rules not matched
     */
    private static function shouldNotifyForCampaignRulesNotMatched(): bool
    {
        return config('app.admin_notifications.campaign_rules_not_matched', false) ||
               self::hasAdminWithPreference('admin_notify_campaign_rules_not_matched', true);
    }

    /**
     * Get admin users that should receive notifications for a specific type
     */
    private static function getAdminsToNotify(string $preferenceKey): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('role', 'admin')
            ->whereExists(function ($query) use ($preferenceKey) {
                $query->select('id')
                    ->from('user_preferences')
                    ->whereColumn('user_preferences.user_id', 'users.id')
                    ->where('user_preferences.key', $preferenceKey)
                    ->where('user_preferences.value', 'true');
            })
            ->orWhere(function ($query) use ($preferenceKey) {
                // Include admins who don't have the preference set but global default is enabled
                $globalDefault = match ($preferenceKey) {
                    'admin_notify_errors' => config('app.admin_notifications.errors', true),
                    'admin_notify_email_processed' => config('app.admin_notifications.email_processed', false),
                    'admin_notify_rules_not_matched' => config('app.admin_notifications.rules_not_matched', false),
                    'admin_notify_duplicate_leads' => config('app.admin_notifications.duplicate_leads', false),
                    default => false
                };

                if ($globalDefault) {
                    $query->where('role', 'admin')
                        ->whereNotExists(function ($subQuery) use ($preferenceKey) {
                            $subQuery->select('id')
                                ->from('user_preferences')
                                ->whereColumn('user_preferences.user_id', 'users.id')
                                ->where('user_preferences.key', $preferenceKey);
                        });
                }
            })
            ->get();
    }

    /**
     * Check if any admin has a specific preference enabled
     */
    private static function hasAdminWithPreference(string $preferenceKey, bool $value): bool
    {
        return User::where('role', 'admin')
            ->whereExists(function ($query) use ($preferenceKey, $value) {
                $query->select('id')
                    ->from('user_preferences')
                    ->whereColumn('user_preferences.user_id', 'users.id')
                    ->where('user_preferences.key', $preferenceKey)
                    ->where('user_preferences.value', $value ? 'true' : 'false');
            })
            ->exists();
    }
}
