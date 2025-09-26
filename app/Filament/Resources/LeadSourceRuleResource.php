<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadSourceRuleResource\Pages;
use App\Filament\Resources\LeadSourceRuleResource\RelationManagers;
use App\Models\LeadSourceRule;
use App\Models\Client;
use App\Services\LeadSourceRuleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class LeadSourceRuleResource extends Resource
{
    protected static ?string $model = LeadSourceRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Lead Management';

    protected static ?string $navigationLabel = 'Lead Source Rules';

    protected static ?string $modelLabel = 'Lead Source Rule';

    protected static ?string $pluralModelLabel = 'Lead Source Rules';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Rule Configuration')
                    ->description('Configure how this rule should match incoming emails to determine lead source')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->required()
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('company')
                                    ->maxLength(255),
                            ]),

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
                            ->helperText('Optional description of what this rule does'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

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
                    ->label('Rule Type')
                    ->badge(),

                TextColumn::make('rule_value')
                    ->label('Rule Value')
                    ->limit(30)
                    ->tooltip(function (LeadSourceRule $record): string {
                        return $record->rule_value;
                    }),

                TextColumn::make('match_field')
                    ->label('Match Field')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('matching_leads_count')
                    ->label('Matching Leads')
                    ->getStateUsing(function ($record) {
                        if (!$record->is_active) {
                            return 'Inactive';
                        }
                        
                        return $record->getMatchingLeadsCount();
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === 'Inactive') {
                            return 'gray';
                        }
                        return $state > 0 ? 'success' : 'warning';
                    })
                    ->tooltip('Number of existing leads that would be affected by this rule'),

                BooleanColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(function (LeadSourceRule $record): ?string {
                        return $record->description;
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('source_name')
                    ->label('Lead Source')
                    ->options(LeadSourceRule::getLeadSources()),

                SelectFilter::make('rule_type')
                    ->label('Rule Type')
                    ->options(LeadSourceRule::getRuleTypes()),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('apply_to_leads')
                    ->label('Apply to All Leads')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Apply Lead Source Rule to Existing Leads')
                    ->modalDescription(function ($record) {
                        $count = $record->getMatchingLeadsCount();
                        
                        return "This will apply the '{$record->source_name}' source to {$count} existing leads that match this rule. This action cannot be undone.";
                    })
                    ->modalSubmitActionLabel('Apply Rule')
                    ->action(function ($record) {
                        $service = new LeadSourceRuleService();
                        $results = $service->applyRuleToAllLeads($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Lead Source Rule Applied')
                            ->body("Processed {$results['processed']} leads, updated {$results['updated']} sources" . 
                                   ($results['errors'] > 0 ? ", with {$results['errors']} errors" : ""))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->is_active),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('apply_selected_rules')
                        ->label('Apply Selected Rules to All Leads')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Apply Selected Lead Source Rules')
                        ->modalDescription('This will apply all selected active lead source rules to existing leads that match their criteria. This action cannot be undone.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $service = new LeadSourceRuleService();
                            $totalResults = [
                                'processed' => 0,
                                'updated' => 0,
                                'errors' => 0,
                                'rules_applied' => 0,
                            ];

                            foreach ($records as $rule) {
                                if ($rule->is_active) {
                                    $results = $service->applyRuleToAllLeads($rule);
                                    $totalResults['processed'] += $results['processed'];
                                    $totalResults['updated'] += $results['updated'];
                                    $totalResults['errors'] += $results['errors'];
                                    
                                    if ($results['updated'] > 0) {
                                        $totalResults['rules_applied']++;
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Lead Source Rules Applied')
                                ->body("Applied {$totalResults['rules_applied']} rules, updated {$totalResults['updated']} sources" . 
                                       ($totalResults['errors'] > 0 ? ", with {$totalResults['errors']} errors" : ""))
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListLeadSourceRules::route('/'),
            'create' => Pages\CreateLeadSourceRule::route('/create'),
            'edit' => Pages\EditLeadSourceRule::route('/{record}/edit'),
        ];
    }
}
