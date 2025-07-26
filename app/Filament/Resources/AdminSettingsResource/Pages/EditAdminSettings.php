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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Don't save the notification preferences through normal form handling
        // We'll handle them in afterSave
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
            if (isset($data[$key])) {
                // Store the preference values temporarily
                $this->temporaryPreferences[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected array $temporaryPreferences = [];

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;

        // Save the notification preferences
        foreach ($this->temporaryPreferences as $key => $value) {
            $record->setPreference($key, $value);
        }

        Notification::make()
            ->title('Notification settings updated successfully')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
