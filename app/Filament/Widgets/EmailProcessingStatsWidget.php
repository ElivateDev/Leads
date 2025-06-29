<?php

namespace App\Filament\Widgets;

use App\Models\EmailProcessingLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailProcessingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Todo: make timezone configurable
        $today = now('America/Phoenix')->startOfDay()->utc(); // Convert back to UTC for database query

        $todayStats = EmailProcessingLog::where('processed_at', '>=', $today)->get();
        $allTimeStats = EmailProcessingLog::all();

        return [
            Stat::make('Emails Processed Today', $todayStats->where('type', 'email_received')->count())
                ->description('Total emails received and processed')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),

            Stat::make('Leads Created Today', $todayStats->where('type', 'lead_created')->count())
                ->description('New leads generated from emails')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Rules Matched Today', $todayStats->where('type', 'rule_matched')->count())
                ->description('Distribution rules successfully matched')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('info'),

            Stat::make('Processing Errors Today', $todayStats->where('status', 'failed')->count())
                ->description('Emails that failed to process')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make(
                'Success Rate Today',
                $todayStats->count() > 0
                    ? round(($todayStats->where('status', 'success')->count() / $todayStats->count()) * 100, 1) . '%'
                    : '0%'
            )
                ->description('Percentage of successful operations')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Total Leads', $allTimeStats->where('type', 'lead_created')->count())
                ->description('Total leads created from all emails')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Total Logs', $allTimeStats->count())
                ->description('All-time processing log entries')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }
}
