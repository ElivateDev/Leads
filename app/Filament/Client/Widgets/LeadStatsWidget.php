<?php

namespace App\Filament\Client\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class LeadStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $clientId = $user->client_id;

        $totalLeads = Lead::where('client_id', $clientId)->count();
        $newLeads = Lead::where('client_id', $clientId)->where('status', 'new')->count();
        $convertedLeads = Lead::where('client_id', $clientId)->where('status', 'converted')->count();

        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        return [
            Stat::make('Total Leads', $totalLeads)
                ->description('All time leads')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('New Leads', $newLeads)
                ->description('Waiting for contact')
                ->descriptionIcon('heroicon-m-bell')
                ->color('warning'),

            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description('Leads converted to customers')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }
}
