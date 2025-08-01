<?php

namespace App\Filament\Resources\UserCampaignPreferenceResource\Pages;

use App\Filament\Resources\UserCampaignPreferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserCampaignPreferences extends ListRecords
{
    protected static string $resource = UserCampaignPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed - we're managing existing users
        ];
    }
}
