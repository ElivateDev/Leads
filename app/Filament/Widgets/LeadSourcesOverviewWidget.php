<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadSourcesOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Lead Sources (Last 30 Days)';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get lead counts by source for the last 30 days
        $data = Lead::select('source', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->get();

        $labels = $data->pluck('source')->map(function ($source) {
            return ucfirst($source);
        })->toArray();

        $counts = $data->pluck('count')->toArray();

        // Define colors for each source
        $colors = [
            'Website' => '#10b981',     // green
            'Social' => '#3b82f6',      // blue
            'Phone' => '#f59e0b',       // amber
            'Referral' => '#8b5cf6',    // purple
            'Other' => '#6b7280',       // gray
        ];

        $backgroundColors = [];
        foreach ($labels as $label) {
            $backgroundColors[] = $colors[$label] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Number of Leads',
                    'data' => $counts,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
