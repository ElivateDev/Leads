<?php

namespace App\Filament\Resources\UserCampaignPreferenceResource\Pages;

use App\Filament\Resources\UserCampaignPreferenceResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUserCampaignPreference extends EditRecord
{
    protected static string $resource = UserCampaignPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reset_preferences')
                ->label('Reset All Preferences')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $this->record->setPreference('visible_campaigns', []);
                    $this->record->setPreference('notification_campaigns', []);

                    Notification::make()
                        ->title('Campaign preferences reset')
                        ->success()
                        ->send();

                    // Redirect back to list
                    return redirect()->to($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Reset Campaign Preferences')
                ->modalDescription('Are you sure you want to reset all campaign preferences for this user?')
                ->modalSubmitActionLabel('Reset'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        // Load preference values into form data
        $data['visible_campaigns'] = $record->getPreference('visible_campaigns', []);
        $data['notification_campaigns'] = $record->getPreference('notification_campaigns', []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        // Save the campaign preferences
        $record->setPreference('visible_campaigns', $data['visible_campaigns'] ?? []);
        $record->setPreference('notification_campaigns', $data['notification_campaigns'] ?? []);

        // Remove preference data from main form data so it doesn't try to save to users table
        unset($data['visible_campaigns']);
        unset($data['notification_campaigns']);

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Campaign preferences updated successfully')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
