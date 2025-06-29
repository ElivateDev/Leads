<?php

namespace App\Filament\Resources\EmailProcessingLogResource\Pages;

use App\Filament\Resources\EmailProcessingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailProcessingLog extends ViewRecord
{
    protected static string $resource = EmailProcessingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
