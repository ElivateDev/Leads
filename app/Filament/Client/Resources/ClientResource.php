<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Settings';
    
    protected static ?string $modelLabel = 'Settings';
    
    protected static ?string $pluralModelLabel = 'Settings';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->description('Basic information about your organization')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company')
                            ->label('Company')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Primary Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Email Notifications')
                    ->description('Configure who receives email notifications for new leads')
                    ->schema([
                        Forms\Components\Toggle::make('email_notifications')
                            ->label('Enable Email Notifications')
                            ->helperText('Turn on/off email notifications for new leads')
                            ->live(),
                        Forms\Components\Repeater::make('notification_emails')
                            ->label('Notification Email Addresses')
                            ->helperText('Add email addresses that should receive notifications for new leads. If empty, notifications will be sent to the primary email above.')
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->placeholder('user@example.com'),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['email'] ?? null)
                            ->addActionLabel('Add Email Address')
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->visible(fn (Forms\Get $get): bool => $get('email_notifications'))
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Lead Management Settings')
                    ->description('Customize how leads are categorized and tracked')
                    ->schema([
                        Forms\Components\KeyValue::make('lead_dispositions')
                            ->label('Lead Dispositions')
                            ->helperText('Define the status options available for categorizing your leads')
                            ->keyLabel('Status Key')
                            ->valueLabel('Display Name')
                            ->addActionLabel('Add Status')
                            ->default(Client::getDefaultDispositions())
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Only show the current client's record
                $user = Filament::auth()->user();
                return Client::query()->where('id', $user->client_id);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Organization')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Primary Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_notifications')
                    ->label('Notifications')
                    ->boolean(),
                Tables\Columns\TextColumn::make('notification_emails_count')
                    ->label('Additional Emails')
                    ->getStateUsing(fn (Client $record) => count($record->notification_emails ?? [])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Settings'),
            ])
            ->paginated(false);
    }

    public static function getEloquentQuery(): Builder
    {
        // Only allow access to the current client's record
        $user = Filament::auth()->user();
        return parent::getEloquentQuery()->where('id', $user->client_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageClients::route('/'),
        ];
    }
}
