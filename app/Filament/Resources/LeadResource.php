<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Lead Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email(),
                Forms\Components\TextInput::make('phone')
                    ->tel(),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Client notes about this lead')
                    ->columnSpanFull()
                    ->rows(4),
                Forms\Components\TextInput::make('from_email')
                    ->email()
                    ->label('From Email')
                    ->helperText('Email address from which the lead was generated'),
                Forms\Components\Select::make('status')
                    ->options(function (Forms\Get $get) {
                        $clientId = $get('client_id');
                        if ($clientId) {
                            $client = \App\Models\Client::find($clientId);
                            return $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                        }
                        return \App\Models\Client::getDefaultDispositions();
                    })
                    ->reactive()
                    ->default('new'),
                Forms\Components\Select::make('source')
                    ->options([
                        'website' => 'Website',
                        'phone' => 'Phone',
                        'referral' => 'Referral',
                        'social' => 'Social Media',
                        'other' => 'Other',
                    ])
                    ->default('website'),
                Forms\Components\TextInput::make('campaign')
                    ->label('Campaign')
                    ->helperText('Optional campaign identifier for tracking lead sources')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->placeholder('No notes')
                    ->color('info')
                    ->icon('heroicon-m-pencil-square')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('from_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(function (string $state, $record) {
                        $client = $record->client;
                        $dispositions = $client ? $client->getLeadDispositions() : \App\Models\Client::getDefaultDispositions();
                        return $dispositions[$state] ?? ucfirst($state);
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'qualified' => 'success',
                        'converted' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'website' => 'success',
                        'phone' => 'info',
                        'referral' => 'warning',
                        'social' => 'danger',
                        'other' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('campaign')
                    ->placeholder('No campaign')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options(\App\Models\Client::getDefaultDispositions()),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'website' => 'Website',
                        'phone' => 'Phone',
                        'referral' => 'Referral',
                        'social' => 'Social Media',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
