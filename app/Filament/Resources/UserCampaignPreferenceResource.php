<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserCampaignPreferenceResource\Pages;
use App\Models\User;
use App\Models\Lead;
use App\Models\CampaignRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserCampaignPreferenceResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'User Campaign Preferences';

    protected static ?string $modelLabel = 'User Campaign Preference';

    protected static ?string $pluralModelLabel = 'User Campaign Preferences';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 15;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'client');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->description('Basic information about this user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Campaign Visibility Preferences')
                    ->description('Configure which campaigns this user can view')
                    ->schema([
                        Forms\Components\CheckboxList::make('visible_campaigns')
                            ->label('Visible Campaigns')
                            ->options(function (Forms\Get $get, ?User $record) {
                                if (!$record || !$record->client_id) {
                                    return [];
                                }

                                // Get all campaigns from leads for this client
                                $leadCampaigns = Lead::where('client_id', $record->client_id)
                                    ->whereNotNull('campaign')
                                    ->where('campaign', '!=', '')
                                    ->distinct()
                                    ->pluck('campaign', 'campaign')
                                    ->toArray();

                                // Get campaigns from campaign rules for this client
                                $ruleCampaigns = CampaignRule::where('client_id', $record->client_id)
                                    ->where('is_active', true)
                                    ->pluck('campaign_name', 'campaign_name')
                                    ->toArray();

                                // Merge and sort campaigns
                                $allCampaigns = array_unique(array_merge($leadCampaigns, $ruleCampaigns));
                                sort($allCampaigns);
                                return array_combine($allCampaigns, $allCampaigns);
                            })
                            ->columns(2)
                            ->helperText('Select campaigns that this user can view in their lead lists')
                            ->live(), // Add live() to make it reactive
                    ]),

                Forms\Components\Section::make('Notification Preferences')
                    ->description('Configure which campaigns this user receives notifications for')
                    ->schema([
                        Forms\Components\CheckboxList::make('notification_campaigns')
                            ->label('Notification Campaigns')
                            ->options(function (Forms\Get $get, ?User $record) {
                                if (!$record || !$record->client_id) {
                                    return [];
                                }

                                // Get all campaigns from leads for this client
                                $leadCampaigns = Lead::where('client_id', $record->client_id)
                                    ->whereNotNull('campaign')
                                    ->where('campaign', '!=', '')
                                    ->distinct()
                                    ->pluck('campaign', 'campaign')
                                    ->toArray();

                                // Get campaigns from campaign rules for this client
                                $ruleCampaigns = CampaignRule::where('client_id', $record->client_id)
                                    ->where('is_active', true)
                                    ->pluck('campaign_name', 'campaign_name')
                                    ->toArray();

                                // Merge and sort campaigns
                                $allCampaigns = array_unique(array_merge($leadCampaigns, $ruleCampaigns));
                                sort($allCampaigns);
                                return array_combine($allCampaigns, $allCampaigns);
                            })
                            ->columns(2)
                            ->helperText('Select campaigns for which this user will receive email notifications')
                            ->live(), // Add live() to make it reactive
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visible_campaigns_count')
                    ->label('Visible Campaigns')
                    ->getStateUsing(function (User $record): string {
                        $campaigns = $record->getPreference('visible_campaigns', []);
                        $count = is_array($campaigns) ? count($campaigns) : 0;
                        return $count > 0 ? $count . ' selected' : 'None selected';
                    })
                    ->badge()
                    ->color(fn(string $state): string => $state === 'None selected' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('notification_campaigns_count')
                    ->label('Notification Campaigns')
                    ->getStateUsing(function (User $record): string {
                        $campaigns = $record->getPreference('notification_campaigns', []);
                        $count = is_array($campaigns) ? count($campaigns) : 0;
                        return $count > 0 ? $count . ' selected' : 'None selected';
                    })
                    ->badge()
                    ->color(fn(string $state): string => $state === 'None selected' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->relationship('client', 'name')
                    ->label('Client')
                    ->multiple(),
                Tables\Filters\Filter::make('has_campaign_preferences')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereExists(function ($query) {
                            $query->select('id')
                                ->from('user_preferences')
                                ->whereColumn('user_preferences.user_id', 'users.id')
                                ->whereIn('user_preferences.key', ['visible_campaigns', 'notification_campaigns']);
                        })
                    )
                    ->label('Has campaign preferences'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Preferences'),
                Tables\Actions\Action::make('reset_preferences')
                    ->label('Reset Preferences')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (User $record) {
                        $record->setPreference('visible_campaigns', []);
                        $record->setPreference('notification_campaigns', []);

                        \Filament\Notifications\Notification::make()
                            ->title('Campaign preferences reset')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reset Campaign Preferences')
                    ->modalDescription('Are you sure you want to reset all campaign preferences for this user? This will clear their visibility and notification settings.')
                    ->modalSubmitActionLabel('Reset'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('reset_all_preferences')
                        ->label('Reset All Preferences')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->setPreference('visible_campaigns', []);
                                $record->setPreference('notification_campaigns', []);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Campaign preferences reset for selected users')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Reset Campaign Preferences')
                        ->modalDescription('Are you sure you want to reset campaign preferences for all selected users?')
                        ->modalSubmitActionLabel('Reset All'),
                ]),
            ])
            ->emptyStateHeading('No Client Users Found')
            ->emptyStateDescription('Client users will appear here once they are created and have campaign preferences.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserCampaignPreferences::route('/'),
            'edit' => Pages\EditUserCampaignPreference::route('/{record}/edit'),
        ];
    }
}
