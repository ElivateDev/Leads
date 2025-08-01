<?php

namespace App\Filament\Client\Pages;

use App\Models\Lead;
use App\Models\CampaignRule;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;

class CampaignPreferences extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static string $view = 'filament.client.pages.campaign-preferences';

    protected static ?string $navigationLabel = 'Campaign Preferences';

    protected static ?string $title = 'Campaign Preferences';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Filament::auth()->user();

        // Load existing preferences
        $visibleCampaigns = $user->getPreference('visible_campaigns', []);
        $notificationCampaigns = $user->getPreference('notification_campaigns', []);

        $this->form->fill([
            'visible_campaigns' => $visibleCampaigns,
            'notification_campaigns' => $notificationCampaigns,
        ]);
    }

    public function form(Form $form): Form
    {
        $user = Filament::auth()->user();
        $clientId = $user->client_id;

        // Get all campaigns from leads for this client
        $leadCampaigns = Lead::where('client_id', $clientId)
            ->whereNotNull('campaign')
            ->where('campaign', '!=', '')
            ->distinct()
            ->pluck('campaign', 'campaign')
            ->toArray();

        // Get campaigns from campaign rules for this client
        $ruleCampaigns = CampaignRule::where('client_id', $clientId)
            ->where('is_active', true)
            ->pluck('campaign_name', 'campaign_name')
            ->toArray();

        // Merge and sort campaigns
        $allCampaigns = array_unique(array_merge($leadCampaigns, $ruleCampaigns));
        sort($allCampaigns);
        $campaignOptions = array_combine($allCampaigns, $allCampaigns);

        return $form
            ->schema([
                Section::make('Campaign Visibility')
                    ->description('Choose which campaigns you want to see in your lead views. If no campaigns are selected, all campaigns will be shown (no filtering applied).')
                    ->schema([
                        CheckboxList::make('visible_campaigns')
                            ->label('Visible Campaigns')
                            ->options($campaignOptions)
                            ->columns(2)
                            ->helperText('Select campaigns to display in your lead views. Leave empty to show all campaigns.')
                            ->default(array_keys($campaignOptions)), // Default to all visible
                    ]),

                Section::make('Notification Preferences')
                    ->description('Choose which campaigns you want to receive email notifications for when new leads arrive. If no campaigns are selected, you will receive notifications for all campaigns.')
                    ->schema([
                        CheckboxList::make('notification_campaigns')
                            ->label('Notification Campaigns')
                            ->options($campaignOptions)
                            ->columns(2)
                            ->helperText('Select campaigns for notifications. Leave empty to receive notifications for all campaigns.')
                            ->default(array_keys($campaignOptions)), // Default to all notifications
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Filament::auth()->user();

        // Save preferences
        $user->setPreference('visible_campaigns', $data['visible_campaigns'] ?? []);
        $user->setPreference('notification_campaigns', $data['notification_campaigns'] ?? []);

        Notification::make()
            ->title('Campaign preferences saved')
            ->success()
            ->send();
    }
}
