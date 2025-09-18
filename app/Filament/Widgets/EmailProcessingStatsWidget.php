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

            Stat::make('Leads Created Today', $this->getLeadsTodayWithComparison($allTimeStats, $today))
                ->description($this->getLeadsTodayComparisonDescription($allTimeStats, $today))
                ->descriptionIcon($this->getLeadsTodayComparisonIcon($allTimeStats, $today))
                ->color($this->getLeadsTodayComparisonColor($allTimeStats, $today)),

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

            Stat::make('Total Leads last 30 days', $this->getLeadsLast30DaysWithComparison($allTimeStats))
                ->description($this->getLeadsComparisonDescription($allTimeStats))
                ->descriptionIcon($this->getLeadsComparisonIcon($allTimeStats))
                ->color($this->getLeadsComparisonColor($allTimeStats)),

            Stat::make('Total Logs', $allTimeStats->count())
                ->description('All-time processing log entries')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }

    private function getLeadsLast30DaysWithComparison($allTimeStats): string
    {
        $currentPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $previousPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(60))
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $percentageChange = $this->calculatePercentageChange($currentPeriod, $previousPeriod);

        return $currentPeriod . ($percentageChange !== null ? " ({$percentageChange})" : '');
    }

    private function getLeadsComparisonDescription($allTimeStats): string
    {
        $currentPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $previousPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(60))
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $percentageChange = $this->calculatePercentageChange($currentPeriod, $previousPeriod);

        if ($percentageChange === null) {
            return 'Leads created in the last 30 days';
        }

        $trend = $currentPeriod >= $previousPeriod ? 'increase' : 'decrease';
        return "Leads created in the last 30 days ({$trend} from previous period)";
    }

    private function getLeadsComparisonIcon($allTimeStats): string
    {
        $currentPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $previousPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(60))
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        if ($currentPeriod > $previousPeriod) {
            return 'heroicon-m-arrow-trending-up';
        } elseif ($currentPeriod < $previousPeriod) {
            return 'heroicon-m-arrow-trending-down';
        }

        return 'heroicon-m-minus';
    }

    private function getLeadsComparisonColor($allTimeStats): string
    {
        $currentPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $previousPeriod = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', now()->subDays(60))
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        if ($currentPeriod > $previousPeriod) {
            return 'success';
        } elseif ($currentPeriod < $previousPeriod) {
            return 'danger';
        }

        return 'primary';
    }

    private function calculatePercentageChange(int $current, int $previous): ?string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100%' : null;
        }

        $change = (($current - $previous) / $previous) * 100;
        $sign = $change >= 0 ? '+' : '';

        return $sign . round($change, 1) . '%';
    }

    // Daily comparison methods
    private function getLeadsTodayWithComparison($allTimeStats, $today): string
    {
        $yesterday = now('America/Phoenix')->subDay()->startOfDay()->utc();

        $todayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $today)
            ->count();

        $yesterdayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();

        $percentageChange = $this->calculatePercentageChange($todayCount, $yesterdayCount);

        return $todayCount . ($percentageChange !== null ? " ({$percentageChange})" : '');
    }

    private function getLeadsTodayComparisonDescription($allTimeStats, $today): string
    {
        $yesterday = now('America/Phoenix')->subDay()->startOfDay()->utc();

        $todayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $today)
            ->count();

        $yesterdayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();

        $percentageChange = $this->calculatePercentageChange($todayCount, $yesterdayCount);

        if ($percentageChange === null) {
            return 'New leads generated from emails';
        }

        $trend = $todayCount >= $yesterdayCount ? 'increase' : 'decrease';
        return "New leads generated from emails ({$trend} from yesterday)";
    }

    private function getLeadsTodayComparisonIcon($allTimeStats, $today): string
    {
        $yesterday = now('America/Phoenix')->subDay()->startOfDay()->utc();

        $todayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $today)
            ->count();

        $yesterdayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();

        if ($todayCount > $yesterdayCount) {
            return 'heroicon-m-arrow-trending-up';
        } elseif ($todayCount < $yesterdayCount) {
            return 'heroicon-m-arrow-trending-down';
        }

        return 'heroicon-m-minus';
    }

    private function getLeadsTodayComparisonColor($allTimeStats, $today): string
    {
        $yesterday = now('America/Phoenix')->subDay()->startOfDay()->utc();

        $todayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $today)
            ->count();

        $yesterdayCount = $allTimeStats->where('type', 'lead_created')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();

        if ($todayCount > $yesterdayCount) {
            return 'success';
        } elseif ($todayCount < $yesterdayCount) {
            return 'warning';
        }

        return 'primary';
    }
}
