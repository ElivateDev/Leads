<?php

namespace App\Filament\Client\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClientLeadsCampaignChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Leads per Campaign (Last 30 Days)';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '400px';

    protected function getData(): array
    {
        // Get the authenticated user's client ID
        $clientId = Auth::user()->client_id;
        
        if (!$clientId) {
            return [
                'datasets' => [
                    [
                        'label' => 'Number of Leads',
                        'data' => [],
                        'backgroundColor' => [],
                        'borderColor' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        // Get lead counts by campaign for the last 30 days for this client only
        $data = Lead::select('campaign', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->where('client_id', $clientId)
            ->whereNotNull('campaign')
            ->where('campaign', '!=', '')
            ->groupBy('campaign')
            ->orderBy('count', 'desc')
            ->limit(10) // Limit to top 10 campaigns to keep chart readable
            ->get();

        // If no campaigns found, return empty data
        if ($data->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Number of Leads',
                        'data' => [],
                        'backgroundColor' => [],
                        'borderColor' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        $labels = $data->pluck('campaign')->map(function ($campaign) {
            // Truncate long campaign names for better display
            return strlen($campaign) > 20 ? substr($campaign, 0, 17) . '...' : $campaign;
        })->toArray();

        $counts = $data->pluck('count')->toArray();

        // Generate distinct colors for campaigns
        $colors = [
            '#10b981', // emerald
            '#3b82f6', // blue
            '#f59e0b', // amber
            '#8b5cf6', // purple
            '#ef4444', // red
            '#06b6d4', // cyan
            '#84cc16', // lime
            '#f97316', // orange
            '#ec4899', // pink
            '#6366f1', // indigo
        ];

        $backgroundColors = [];
        $borderColors = [];
        
        for ($i = 0; $i < count($labels); $i++) {
            $color = $colors[$i % count($colors)];
            $backgroundColors[] = $color . '20'; // Add transparency
            $borderColors[] = $color;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Number of Leads',
                    'data' => $counts,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
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
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 0,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}