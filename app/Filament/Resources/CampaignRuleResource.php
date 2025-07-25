<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignRuleResource\Pages;
use App\Models\CampaignRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignRuleResource extends Resource
{
    protected static ?string $model = CampaignRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Campaign Rules';

    protected static ?string $navigationGroup = 'Lead Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign Information')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('campaign_name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The campaign name that will be assigned to matching leads'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Optional description of what this rule does'),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher numbers = higher priority (checked first)'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Whether this rule is currently active'),
                    ])->columns(2),

                Forms\Components\Section::make('Rule Configuration')
                    ->description('Define what email content should trigger this campaign assignment')
                    ->schema([
                        Forms\Components\Select::make('match_field')
                            ->options(CampaignRule::getMatchFields())
                            ->required()
                            ->default('body')
                            ->live()
                            ->helperText('Which part of the email to check'),
                        Forms\Components\Select::make('rule_type')
                            ->options(CampaignRule::getRuleTypes())
                            ->required()
                            ->default('contains')
                            ->live()
                            ->helperText('How to match the content'),
                        Forms\Components\Textarea::make('rule_value')
                            ->required()
                            ->rows(3)
                            ->helperText(function (Forms\Get $get) {
                                return match ($get('rule_type')) {
                                    'contains' => 'Text that must be found in the email content (case-insensitive)',
                                    'exact' => 'Exact text that must match the email content (case-insensitive)',
                                    'regex' => 'Regular expression pattern to match (without delimiters)',
                                    'url_parameter' => 'URL parameter to match (e.g., "gad_campaignid=22820616890" or just "gad_campaignid")',
                                    default => 'Enter the value to match against',
                                };
                            }),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('campaign_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('rule_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'contains' => 'success',
                        'exact' => 'info',
                        'regex' => 'warning',
                        'url_parameter' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('match_field')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('rule_value')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('rule_type')
                    ->options(CampaignRule::getRuleTypes())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('match_field')
                    ->options(CampaignRule::getMatchFields())
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaignRules::route('/'),
            'create' => Pages\CreateCampaignRule::route('/create'),
            'edit' => Pages\EditCampaignRule::route('/{record}/edit'),
        ];
    }
}
