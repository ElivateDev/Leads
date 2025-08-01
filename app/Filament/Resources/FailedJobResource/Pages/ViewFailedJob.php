<?php

namespace App\Filament\Resources\FailedJobResource\Pages;

use App\Filament\Resources\FailedJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFailedJob extends ViewRecord
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('retry')
                ->label('Retry Job')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => $this->record->uuid]);
                    \Filament\Notifications\Notification::make()
                        ->title('Job Retried Successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
            Actions\DeleteAction::make()
                ->label('Forget Job')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('queue:forget', ['id' => $this->record->uuid]);
                    \Filament\Notifications\Notification::make()
                        ->title('Job Removed Successfully')
                        ->success()
                        ->send();
                    return redirect()->route('filament.admin.resources.failed-jobs.index');
                }),
        ];
    }
}
