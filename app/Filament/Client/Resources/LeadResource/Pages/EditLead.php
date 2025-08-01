<?php

namespace App\Filament\Client\Resources\LeadResource\Pages;

use App\Filament\Client\Resources\LeadResource;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action for clients
        ];
    }
}
