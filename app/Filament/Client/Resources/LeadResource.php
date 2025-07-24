<?php

namespace App\Filament\Client\Resources;

use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'My Leads';

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $pluralModelLabel = 'Leads';

    public static function getEloquentQuery(): Builder
    {
        // Only show leads for the authenticated client user's client
        $user = Filament::auth()->user();

        return parent::getEloquentQuery()
            ->where('client_id', $user->client_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->disabled(), // Read-only for clients
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->disabled(),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Add your notes about this lead')
                    ->columnSpanFull()
                    ->rows(4),
                Forms\Components\TextInput::make('from_email')
                    ->email()
                    ->label('From Email')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Disposition')
                    ->options(function () {
                        $user = Filament::auth()->user();
                        $client = \App\Models\Client::find($user->client_id);
                        return $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                    })
                    ->required(),
                Forms\Components\Select::make('source')
                    ->options([
                        'website' => 'Website',
                        'social' => 'Social Media',
                        'referral' => 'Referral',
                        'phone' => 'Phone',
                        'other' => 'Other',
                    ])
                    ->disabled(),
                Forms\Components\DateTimePicker::make('email_received_at')
                    ->label('Received At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('name')
                                ->weight('bold')
                                ->size('lg')
                                ->searchable()
                                ->sortable(),
                            Tables\Columns\TextColumn::make('email')
                                ->icon('heroicon-m-envelope')
                                ->color('gray')
                                ->size('sm')
                                ->searchable(),
                        ])->space(1),

                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('status')
                                ->label('Disposition')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'new' => 'success',
                                    'contacted' => 'warning',
                                    'qualified' => 'info',
                                    'converted' => 'success',
                                    'lost' => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(function (string $state, $record): string {
                                    $client = $record->client;
                                    $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                                    return $dispositions[$state] ?? ucfirst($state);
                                })
                                ->action(
                                    Tables\Actions\Action::make('change_disposition')
                                        ->label('Change Disposition')
                                        ->icon('heroicon-m-adjustments-horizontal')
                                        ->form(function (Lead $record) {
                                            $client = $record->client;
                                            $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                                            
                                            return [
                                                Forms\Components\Select::make('status')
                                                    ->label('Select Disposition')
                                                    ->options($dispositions)
                                                    ->default($record->status)
                                                    ->required()
                                                    ->native(false)
                                                    ->placeholder('Choose a disposition...')
                                            ];
                                        })
                                        ->action(function (Lead $record, array $data) {
                                            $record->update(['status' => $data['status']]);
                                        })
                                        ->modalHeading('Change Lead Disposition')
                                        ->modalSubmitActionLabel('Update Disposition')
                                        ->modalWidth('md')
                                ),
                            Tables\Columns\TextColumn::make('source')
                                ->badge()
                                ->color('gray')
                                ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                ->icon(fn(string $state): string => match ($state) {
                                    'website' => 'heroicon-m-globe-alt',
                                    'social' => 'heroicon-m-hashtag',
                                    'referral' => 'heroicon-m-user-group',
                                    'phone' => 'heroicon-m-phone',
                                    default => 'heroicon-m-question-mark-circle',
                                }),
                        ])->space(1)->alignment('end'),
                    ]),

                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('phone')
                                ->icon('heroicon-m-phone')
                                ->color('gray')
                                ->placeholder('No phone provided')
                                ->formatStateUsing(
                                    fn(?string $state): string =>
                                    $state ? static::formatPhoneNumber($state) : 'No phone provided'
                                ),
                            Tables\Columns\TextColumn::make('created_at')
                                ->icon('heroicon-m-clock')
                                ->color('gray')
                                ->since()
                                ->sortable()
                                ->label('Received'),
                        ]),
                        Tables\Columns\TextColumn::make('message')
                            ->limit(150)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 150 ? $state : null;
                            })
                            ->color('gray')
                            ->placeholder('No message content')
                            ->wrap(),
                    ])->collapsible(),
                    
                    // Notes field outside the collapsible panel to avoid click conflicts
                    Tables\Columns\TextInputColumn::make('notes')
                        ->placeholder('Add notes...')
                        ->extraAttributes(['class' => 'text-xs mt-2'])
                        ->tooltip('Click to edit notes')
                        ->label('Notes'),
                ])->space(2),
            ])
            ->recordUrl(null) // Disable automatic row clicking to view page
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Disposition')
                    ->options(function () {
                        $user = Filament::auth()->user();
                        $client = \App\Models\Client::find($user->client_id);
                        return $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                    })
                    ->multiple()
                    ->placeholder('All Dispositions'),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'website' => 'Website',
                        'social' => 'Social Media',
                        'referral' => 'Referral',
                        'phone' => 'Phone',
                        'other' => 'Other',
                    ])
                    ->multiple()
                    ->placeholder('All Sources'),
                Tables\Filters\Filter::make('recent')
                    ->query(fn(Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->label('Last 7 days')
                    ->indicator('Recent leads'),
                Tables\Filters\Filter::make('needs_attention')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'new')->where('created_at', '<=', now()->subDays(1)))
                    ->label('Needs attention')
                    ->indicator('Needs attention'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('contact')
                        ->icon('heroicon-m-phone')
                        ->color('success')
                        ->action(function (Lead $record) {
                            // Check if 'contacted' exists in client's dispositions, otherwise use first available
                            $client = $record->client;
                            $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                            $contactedStatus = array_key_exists('contacted', $dispositions) ? 'contacted' : array_key_first($dispositions);
                            $record->update(['status' => $contactedStatus]);
                        })
                        ->visible(fn(Lead $record) => $record->status === 'new')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Contacted')
                        ->modalDescription('Are you sure you want to mark this lead as contacted?'),
                    Tables\Actions\Action::make('quick_disposition')
                        ->label('Quick Update')
                        ->icon('heroicon-m-adjustments-horizontal')
                        ->color('warning')
                        ->form(function (Lead $record) {
                            $client = $record->client;
                            $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                            
                            return [
                                Forms\Components\Select::make('status')
                                    ->label('Change Disposition')
                                    ->options($dispositions)
                                    ->default($record->status)
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Add/Update Notes')
                                    ->default($record->notes)
                                    ->rows(3)
                                    ->placeholder('Add any notes about this lead...')
                            ];
                        })
                        ->action(function (Lead $record, array $data) {
                            $record->update([
                                'status' => $data['status'],
                                'notes' => $data['notes']
                            ]);
                        })
                        ->modalHeading('Quick Update')
                        ->modalSubmitActionLabel('Update Lead'),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
                Tables\Actions\Action::make('call')
                    ->icon('heroicon-m-phone')
                    ->color('info')
                    ->url(fn(Lead $record) => $record->phone ? "tel:{$record->phone}" : null)
                    ->visible(fn(Lead $record) => !empty($record->phone))
                    ->openUrlInNewTab(false)
                    ->tooltip('Call lead'),
                Tables\Actions\Action::make('email')
                    ->icon('heroicon-m-envelope')
                    ->color('warning')
                    ->url(fn(Lead $record) => $record->email ? "mailto:{$record->email}" : null)
                    ->visible(fn(Lead $record) => !empty($record->email))
                    ->openUrlInNewTab(false)
                    ->tooltip('Email lead'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_contacted')
                    ->label('Mark as Contacted')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each(function (Lead $record) {
                            $client = $record->client;
                            $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                            $contactedStatus = array_key_exists('contacted', $dispositions) ? 'contacted' : array_key_first($dispositions);
                            $record->update(['status' => $contactedStatus]);
                        });
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark leads as contacted')
                    ->modalDescription('Are you sure you want to mark the selected leads as contacted?'),
                Tables\Actions\BulkAction::make('bulk_disposition')
                    ->label('Change Disposition')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->color('warning')
                    ->form(function () {
                        $user = Filament::auth()->user();
                        $client = \App\Models\Client::find($user->client_id);
                        $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                        
                        return [
                            Forms\Components\Select::make('status')
                                ->label('New Disposition')
                                ->options($dispositions)
                                ->required(),
                        ];
                    })
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $records->each(fn(Lead $record) => $record->update(['status' => $data['status']]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Change disposition for selected leads')
                    ->modalDescription('This will update the disposition for all selected leads.')
                    ->modalSubmitActionLabel('Update Dispositions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    protected static function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) return 'No phone provided';

        // Simple US phone number formatting
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($cleaned) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6, 4)
            );
        }

        return $phone;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Client\Resources\LeadResource\Pages\ListLeads::route('/'),
            'view' => \App\Filament\Client\Resources\LeadResource\Pages\ViewLead::route('/{record}'),
            'edit' => \App\Filament\Client\Resources\LeadResource\Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
