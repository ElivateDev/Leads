<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\LeadSourceRule;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class LeadSourceRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'leadSourceRules';

    protected static ?string $recordTitleAttribute = 'description';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('source_name')
                            ->label('Lead Source')
                            ->required()
                            ->options(LeadSourceRule::getLeadSources())
                            ->default('other'),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priority')
                            ->required()
                            ->numeric()
                            ->default(50)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Higher numbers = higher priority (0-100)'),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('rule_type')
                            ->label('Rule Type')
                            ->required()
                            ->options(LeadSourceRule::getRuleTypes())
                            ->default('contains')
                            ->live()
                            ->helperText('How the rule should match the content'),

                        Forms\Components\Select::make('match_field')
                            ->label('Match Field')
                            ->required()
                            ->options(LeadSourceRule::getMatchFields())
                            ->default('body')
                            ->helperText('Which part of the email to match against'),
                    ]),

                Forms\Components\TextInput::make('rule_value')
                    ->label('Rule Value')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText(function (Forms\Get $get) {
                        return match ($get('rule_type')) {
                            'contains' => 'Text that must be contained in the field',
                            'exact' => 'Exact text that must match the field',
                            'regex' => 'Regular expression pattern (without delimiters)',
                            'url_parameter' => 'URL parameter like "utm_source=facebook" or just "utm_source"',
                            'domain' => 'Domain name like "facebook.com"',
                            default => 'Value to match against'
                        };
                    }),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Whether this rule should be applied'),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText('Optional description of what this rule does'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('source_name')
                    ->label('Source')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'website' => 'success',
                        'social' => 'info',
                        'phone' => 'warning',
                        'referral' => 'primary',
                        'other' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),

                TextColumn::make('rule_type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('rule_value')
                    ->label('Rule Value')
                    ->limit(30)
                    ->tooltip(function ($record): string {
                        return $record->rule_value;
                    }),

                TextColumn::make('match_field')
                    ->label('Field')
                    ->badge()
                    ->color('gray'),

                BooleanColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(function ($record): ?string {
                        return $record->description;
                    }),
            ])
            ->filters([
                SelectFilter::make('source_name')
                    ->label('Lead Source')
                    ->options(LeadSourceRule::getLeadSources()),

                SelectFilter::make('rule_type')
                    ->label('Rule Type')
                    ->options(LeadSourceRule::getRuleTypes()),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            ->defaultSort('priority', 'desc');
    }
}
