<?php

namespace App\Filament\Client\Resources\ClientResource\Pages;

use App\Filament\Client\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Facades\Filament;

class ManageClients extends ManageRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - clients manage their existing settings
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Transform repeater format back to array of emails
        if (!empty($data['notification_emails'])) {
            $data['notification_emails'] = array_column($data['notification_emails'], 'email');
            $data['notification_emails'] = array_filter($data['notification_emails']); // Remove empty values
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform notification_emails array to repeater format
        if (!empty($data['notification_emails'])) {
            $data['notification_emails'] = array_map(function ($email) {
                return ['email' => $email];
            }, $data['notification_emails']);
        }

        return $data;
    }
}
