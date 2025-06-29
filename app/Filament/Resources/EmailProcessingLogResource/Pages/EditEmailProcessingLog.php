<?php

namespace App\Filament\Resources\EmailProcessingLogResource\Pages;

use App\Filament\Resources\EmailProcessingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailProcessingLog extends EditRecord
{
    protected static string $resource = EmailProcessingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
