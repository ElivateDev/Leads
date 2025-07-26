<?php

namespace App\Filament\Resources\LeadSourceRuleResource\Pages;

use App\Filament\Resources\LeadSourceRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeadSourceRule extends EditRecord
{
    protected static string $resource = LeadSourceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
