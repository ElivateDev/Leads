<?php

namespace App\Filament\Resources\LeadSourceRuleResource\Pages;

use App\Filament\Resources\LeadSourceRuleResource;
use App\Services\LeadSourceRuleService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeadSourceRules extends ListRecords
{
    protected static string $resource = LeadSourceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('apply_all_rules')
                ->label('Apply All Rules to All Leads')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Apply All Lead Source Rules')
                ->modalDescription('This will apply ALL active lead source rules to existing leads. Rules with higher priority will be applied first. This action cannot be undone and may take some time to complete.')
                ->modalSubmitActionLabel('Apply All Rules')
                ->action(function () {
                    $service = new LeadSourceRuleService();
                    $results = $service->applyAllRulesToAllLeads();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('All Lead Source Rules Applied')
                        ->body("Applied {$results['rules_applied']} rules, processed {$results['processed']} leads, updated {$results['updated']} sources" . 
                               ($results['errors'] > 0 ? ", with {$results['errors']} errors" : ""))
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
