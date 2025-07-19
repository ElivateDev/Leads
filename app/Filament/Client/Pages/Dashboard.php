<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.client.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Client\Widgets\LeadStatsWidget::class,
            \App\Filament\Client\Widgets\RecentLeadsWidget::class,
        ];
    }
}
