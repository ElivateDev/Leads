<?php

namespace App\Filament\Resources\AdminSettingsResource\Pages;

use App\Filament\Resources\AdminSettingsResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminSettings extends ListRecords
{
    protected static string $resource = AdminSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since we only configure existing admin users
        ];
    }
}
