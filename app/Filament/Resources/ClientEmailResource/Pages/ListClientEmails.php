<?php

namespace App\Filament\Resources\ClientEmailResource\Pages;

use App\Filament\Resources\ClientEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientEmails extends ListRecords
{
    protected static string $resource = ClientEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
