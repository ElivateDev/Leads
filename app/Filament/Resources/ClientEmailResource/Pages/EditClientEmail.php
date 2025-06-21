<?php

namespace App\Filament\Resources\ClientEmailResource\Pages;

use App\Filament\Resources\ClientEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientEmail extends EditRecord
{
    protected static string $resource = ClientEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
