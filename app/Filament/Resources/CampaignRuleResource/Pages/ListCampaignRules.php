<?php

namespace App\Filament\Resources\CampaignRuleResource\Pages;

use App\Filament\Resources\CampaignRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampaignRules extends ListRecords
{
    protected static string $resource = CampaignRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
