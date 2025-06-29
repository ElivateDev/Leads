<?php

namespace App\Filament\Resources\EmailProcessingLogResource\Pages;

use App\Filament\Resources\EmailProcessingLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailProcessingLogs extends ListRecords
{
    protected static string $resource = EmailProcessingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - logs are created automatically
        ];
    }
}
