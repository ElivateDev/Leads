<?php

namespace App\Filament\Resources\FailedJobResource\Pages;

use App\Filament\Resources\FailedJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('retry_all')
                ->label('Retry All Failed Jobs')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    try {
                        Artisan::call('queue:retry', ['id' => 'all']);
                        Notification::make()
                            ->title('All Failed Jobs Retried')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Retry Jobs')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalDescription('This will retry all failed jobs. Are you sure?'),
            Actions\Action::make('flush_all')
                ->label('Clear All Failed Jobs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    try {
                        Artisan::call('queue:flush');
                        Notification::make()
                            ->title('All Failed Jobs Cleared')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Clear Jobs')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalDescription('This will permanently delete all failed jobs. This action cannot be undone.'),
        ];
    }
}
