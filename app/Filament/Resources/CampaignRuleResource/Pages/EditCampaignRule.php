<?php

namespace App\Filament\Resources\CampaignRuleResource\Pages;

use App\Filament\Resources\CampaignRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampaignRule extends EditRecord
{
    protected static string $resource = CampaignRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
