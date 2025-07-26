<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\LeadSourceRule;
use App\Models\Lead;

class LeadSourceRulesStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRules = LeadSourceRule::count();
        $activeRules = LeadSourceRule::where('is_active', true)->count();
        $recentLeads = Lead::where('created_at', '>=', now()->subDays(7))->count();
        $rulesUsedToday = Lead::where('created_at', '>=', now()->startOfDay())
            ->whereNotNull('source')
            ->where('source', '!=', 'other')
            ->count();

        return [
            Stat::make('Total Lead Source Rules', $totalRules)
                ->description('All configured rules')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('primary'),

            Stat::make('Active Rules', $activeRules)
                ->description('Currently enabled')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Leads This Week', $recentLeads)
                ->description('From all sources')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Rules Applied Today', $rulesUsedToday)
                ->description('Automatic source detection')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),
        ];
    }
}
