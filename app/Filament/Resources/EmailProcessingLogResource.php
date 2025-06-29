<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailProcessingLogResource\Pages;
use App\Models\EmailProcessingLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailProcessingLogResource extends Resource
{
    protected static ?string $model = EmailProcessingLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Email Processing Logs';

    protected static ?string $modelLabel = 'Email Processing Log';

    protected static ?string $pluralModelLabel = 'Email Processing Logs';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Information')
                    ->schema([
                        Forms\Components\TextInput::make('email_id')
                            ->label('Email ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_address')
                            ->label('From Address')
                            ->disabled(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Processing Details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'email_received' => 'Email Received',
                                'rule_matched' => 'Rule Matched',
                                'rule_failed' => 'Rule Failed',
                                'lead_created' => 'Lead Created',
                                'lead_duplicate' => 'Duplicate Lead',
                                'notification_sent' => 'Notification Sent',
                                'error' => 'Error',
                            ])
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'success' => 'Success',
                                'failed' => 'Failed',
                                'skipped' => 'Skipped',
                            ])
                            ->disabled(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('lead_id')
                            ->relationship('lead', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('rule_id')
                            ->relationship('rule', 'email')
                            ->disabled(),
                        Forms\Components\TextInput::make('rule_type')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->rows(4)
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\KeyValue::make('details')
                            ->label('Details')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->disabled(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('processed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_address')
                    ->label('From')
                    ->searchable()
                    ->copyable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'email_received' => 'Email Received',
                        'rule_matched' => 'Rule Matched',
                        'rule_failed' => 'Rule Failed',
                        'lead_created' => 'Lead Created',
                        'lead_duplicate' => 'Duplicate Lead',
                        'notification_sent' => 'Notification Sent',
                        'error' => 'Error',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'email_received' => 'info',
                        'rule_matched' => 'success',
                        'rule_failed' => 'warning',
                        'lead_created' => 'success',
                        'lead_duplicate' => 'warning',
                        'notification_sent' => 'info',
                        'error' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('lead.name')
                    ->label('Lead')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('rule_type')
                    ->label('Rule Type')
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'email_match' => 'Email Match',
                        'custom_rule' => 'Custom Rule',
                        'combined_rule' => 'Combined Rule',
                        default => $state ?? '—',
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Log Type')
                    ->options([
                        'email_received' => 'Email Received',
                        'rule_matched' => 'Rule Matched',
                        'rule_failed' => 'Rule Failed',
                        'lead_created' => 'Lead Created',
                        'lead_duplicate' => 'Duplicate Lead',
                        'notification_sent' => 'Notification Sent',
                        'error' => 'Error',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'skipped' => 'Skipped',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->multiple(),
                Tables\Filters\Filter::make('processed_at')
                    ->form([
                        Forms\Components\DatePicker::make('processed_from')
                            ->label('Processed From'),
                        Forms\Components\DatePicker::make('processed_until')
                            ->label('Processed Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['processed_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('processed_at', '>=', $date),
                            )
                            ->when(
                                $data['processed_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('processed_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListEmailProcessingLogs::route('/'),
            'view' => Pages\ViewEmailProcessingLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Logs are created automatically, not manually
    }

    public static function canEdit($record): bool
    {
        return false; // Logs should not be editable
    }
}
