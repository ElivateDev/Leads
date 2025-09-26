<?php

namespace App\Filament\Resources\CampaignRuleResource\Pages;

use App\Filament\Resources\CampaignRuleResource;
use App\Services\CampaignRuleService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampaignRules extends ListRecords
{
    protected static string $resource = CampaignRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('apply_all_rules')
                ->label('Apply All Rules to All Leads')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Apply All Campaign Rules')
                ->modalDescription('This will apply ALL active campaign rules to existing leads. Rules with higher priority will be applied first. This action cannot be undone and may take some time to complete.')
                ->modalSubmitActionLabel('Apply All Rules')
                ->action(function () {
                    $service = new CampaignRuleService();
                    $results = $service->applyAllRulesToAllLeads();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('All Campaign Rules Applied')
                        ->body("Applied {$results['rules_applied']} rules, processed {$results['processed']} leads, updated {$results['updated']} campaigns" . 
                               ($results['errors'] > 0 ? ", with {$results['errors']} errors" : ""))
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
