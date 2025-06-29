<?php

namespace App\Filament\Resources\DistributionRuleResource\Pages;

use App\Filament\Resources\DistributionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistributionRules extends ListRecords
{
    protected static string $resource = DistributionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
