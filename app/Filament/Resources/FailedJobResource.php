<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FailedJobResource\Pages;
use App\Filament\Resources\FailedJobResource\RelationManagers;
use App\Models\FailedJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Failed Jobs';

    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('Job UUID')
                    ->disabled(),
                Forms\Components\TextInput::make('connection')
                    ->disabled(),
                Forms\Components\TextInput::make('queue')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('failed_at')
                    ->disabled(),
                Forms\Components\Textarea::make('exception')
                    ->label('Exception Details')
                    ->rows(10)
                    ->disabled(),
                Forms\Components\Textarea::make('payload')
                    ->label('Job Payload')
                    ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT))
                    ->rows(10)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_name')
                    ->label('Job Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('connection')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('queue')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_exception')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn(FailedJob $record): string => $record->short_exception)
                    ->searchable(),
                Tables\Columns\TextColumn::make('failed_at')
                    ->label('Failed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('connection')
                    ->options([
                        'database' => 'Database',
                        'redis' => 'Redis',
                        'sync' => 'Sync',
                    ]),
                Tables\Filters\SelectFilter::make('queue')
                    ->options([
                        'default' => 'Default',
                        'high' => 'High',
                        'low' => 'Low',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (FailedJob $record) {
                        try {
                            Artisan::call('queue:retry', ['id' => $record->uuid]);
                            Notification::make()
                                ->title('Job Retried Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Retry Job')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make()
                    ->label('Forget')
                    ->action(function (FailedJob $record) {
                        try {
                            Artisan::call('queue:forget', ['id' => $record->uuid]);
                            Notification::make()
                                ->title('Job Removed Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Remove Job')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('retry_all')
                        ->label('Retry Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                Artisan::call('queue:retry', ['id' => $record->uuid]);
                            }
                            Notification::make()
                                ->title('Selected Jobs Retried')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Forget Selected')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                Artisan::call('queue:forget', ['id' => $record->uuid]);
                            }
                            Notification::make()
                                ->title('Selected Jobs Removed')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('failed_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFailedJobs::route('/'),
            'view' => Pages\ViewFailedJob::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
