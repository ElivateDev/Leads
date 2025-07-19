<?php

namespace App\Filament\Client\Resources\LeadResource\Pages;

use App\Filament\Client\Resources\LeadResource;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for clients - they can't create leads manually
        ];
    }
}
