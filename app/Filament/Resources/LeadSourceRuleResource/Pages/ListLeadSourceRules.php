<?php

namespace App\Filament\Resources\LeadSourceRuleResource\Pages;

use App\Filament\Resources\LeadSourceRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeadSourceRules extends ListRecords
{
    protected static string $resource = LeadSourceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
