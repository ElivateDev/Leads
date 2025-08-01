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
        // Transform repeater format back to array of email strings
        if (!empty($data['notification_emails'])) {
            $emails = [];

            foreach ($data['notification_emails'] as $item) {
                if (is_array($item) && isset($item['email'])) {
                    $email = trim($item['email']);
                    if (!empty($email)) {
                        $emails[] = $email;
                    }
                } elseif (is_string($item)) {
                    $email = trim($item);
                    if (!empty($email)) {
                        $emails[] = $email;
                    }
                }
            }

            // Store as simple array of strings, or null if empty
            $data['notification_emails'] = !empty($emails) ? array_values($emails) : null;
        } else {
            // Ensure null when empty
            $data['notification_emails'] = null;
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform notification_emails array to repeater format for editing
        if (!empty($data['notification_emails'])) {
            $emails = [];

            foreach ($data['notification_emails'] as $item) {
                if (is_string($item)) {
                    // Simple string format (correct format)
                    $emails[] = ['email' => $item];
                } elseif (is_array($item) && isset($item['email'])) {
                    // Already in object format (shouldn't happen with our fix, but handle it)
                    $emails[] = ['email' => $item['email']];
                }
            }

            $data['notification_emails'] = $emails;
        }

        return $data;
    }
}
