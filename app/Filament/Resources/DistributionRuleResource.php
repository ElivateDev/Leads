<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistributionRuleResource\Pages;
use App\Filament\Resources\DistributionRuleResource\RelationManagers;
use App\Models\ClientEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DistributionRuleResource extends Resource
{
    protected static ?string $model = ClientEmail::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Distribution Rules';

    protected static ?string $modelLabel = 'Distribution Rule';

    protected static ?string $pluralModelLabel = 'Distribution Rules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('rule_type')
                    ->options([
                        'email_match' => 'Email Address Match',
                        'custom_rule' => 'Custom Body Text Rule',
                        'combined_rule' => 'Combined Rule (Email + Body Text)',
                    ])
                    ->required()
                    ->default('email_match')
                    ->live()
                    ->helperText('Choose how this rule should match incoming emails'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->visible(fn($get) => in_array($get('rule_type'), ['email_match', 'combined_rule']))
                    ->required(fn($get) => in_array($get('rule_type'), ['email_match', 'combined_rule']))
                    ->helperText('Email address or domain pattern (e.g., info@domain.com or @domain.com)'),
                Forms\Components\Textarea::make('custom_conditions')
                    ->visible(fn($get) => in_array($get('rule_type'), ['custom_rule', 'combined_rule']))
                    ->required(fn($get) => in_array($get('rule_type'), ['custom_rule', 'combined_rule']))
                    ->placeholder('Example: Source: Facebook AND rep: henry')
                    ->helperText('Define conditions using AND/OR logic. Use format "field: value" for each condition.')
                    ->rows(3),
                Forms\Components\TextInput::make('description')
                    ->placeholder('e.g., Facebook leads for Henry, Contact form submissions')
                    ->helperText('Optional description to help identify this distribution rule'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Inactive rules will be ignored during email processing'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rule_type')
                    ->label('Rule Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'email_match' => 'Email Match',
                        'custom_rule' => 'Custom Rule',
                        'combined_rule' => 'Combined Rule',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'email_match' => 'success',
                        'custom_rule' => 'warning',
                        'combined_rule' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->visible(fn() => true)
                    ->formatStateUsing(function ($state, $record) {
                        return in_array($record->rule_type, ['email_match', 'combined_rule']) ? $state : '—';
                    }),
                Tables\Columns\TextColumn::make('custom_conditions')
                    ->label('Conditions')
                    ->limit(50)
                    ->placeholder('—')
                    ->visible(fn() => true)
                    ->formatStateUsing(function ($state, $record) {
                        return in_array($record->rule_type, ['custom_rule', 'combined_rule']) ? $state : '—';
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'name'),
                Tables\Filters\SelectFilter::make('rule_type')
                    ->options([
                        'email_match' => 'Email Address Match',
                        'custom_rule' => 'Custom Body Text Rule',
                        'combined_rule' => 'Combined Rule (Email + Body Text)',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListDistributionRules::route('/'),
            'create' => Pages\CreateDistributionRule::route('/create'),
            'edit' => Pages\EditDistributionRule::route('/{record}/edit'),
        ];
    }
}
