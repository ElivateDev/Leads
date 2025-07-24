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
        $client = \App\Models\Client::find($clientId);

        $totalLeads = Lead::where('client_id', $clientId)->count();
        $newLeads = Lead::where('client_id', $clientId)->where('status', 'new')->count();

        // Check if 'converted' exists in client's dispositions, otherwise use the last disposition
        $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
        $convertedStatus = array_key_exists('converted', $dispositions) ? 'converted' : array_key_last($dispositions);
        $convertedLeads = Lead::where('client_id', $clientId)->where('status', $convertedStatus)->count();

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
