<?php

namespace App\Filament\Client\Resources\ClientResource\Pages;

use App\Filament\Client\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Facades\Filament;

class ManageClients extends ManageRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - clients manage their existing settings
        ];
    }
}
