<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Filament\Resources\ClientResource\RelationManagers\ClientEmailsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\LeadSourceRulesRelationManager;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->tel(),
                Forms\Components\TextInput::make('company'),
                Forms\Components\Toggle::make('email_notifications')
                    ->required()
                    ->live(),
                Forms\Components\Repeater::make('notification_emails')
                    ->label('Notification Email Addresses')
                    ->helperText('Add email addresses that should receive notifications for new leads.')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->placeholder('user@example.com'),
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['email'] ?? null)
                    ->addActionLabel('Add Email Address')
                    ->collapsible()
                    ->cloneable()
                    ->defaultItems(0)
                    ->visible(fn(Forms\Get $get): bool => $get('email_notifications'))
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('lead_dispositions')
                    ->label('Lead Dispositions')
                    ->helperText('Define custom lead statuses for this client. Key is the value stored in database, Value is the display name.')
                    ->keyLabel('Status Value')
                    ->valueLabel('Display Name')
                    ->default(fn() => \App\Models\Client::getDefaultDispositions())
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_notifications')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            ClientEmailsRelationManager::class,
            RelationManagers\LeadsRelationManager::class,
            LeadSourceRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
