<?php

namespace App\Filament\Resources\AdminSettingsResource\Pages;

use App\Filament\Resources\AdminSettingsResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAdminSettings extends EditRecord
{
    protected static string $resource = AdminSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action for admin settings
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        // Load preference values into form data
        $preferenceKeys = [
            'admin_notify_email_processed',
            'admin_notify_errors',
            'admin_notify_rules_not_matched',
            'admin_notify_duplicate_leads',
            'admin_notify_high_email_volume',
            'admin_notify_imap_connection_issues',
            'admin_notify_smtp_issues',
        ];

        foreach ($preferenceKeys as $key) {
            $defaultValue = match ($key) {
                'admin_notify_errors' => true,
                'admin_notify_imap_connection_issues' => true,
                'admin_notify_smtp_issues' => true,
                default => false
            };
            $data[$key] = $record->getPreference($key, $defaultValue);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        // Save the notification preferences
        $preferenceKeys = [
            'admin_notify_email_processed',
            'admin_notify_errors',
            'admin_notify_rules_not_matched',
            'admin_notify_duplicate_leads',
            'admin_notify_high_email_volume',
            'admin_notify_imap_connection_issues',
            'admin_notify_smtp_issues',
        ];

        foreach ($preferenceKeys as $key) {
            if (array_key_exists($key, $data)) {
                $record->setPreference($key, $data[$key] ?? false);
                // Remove from data so it doesn't try to save to the users table
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Notification settings updated successfully')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
