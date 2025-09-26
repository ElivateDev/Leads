<?php

namespace App\Console\Commands;

use App\Models\CampaignRule;
use App\Services\CampaignRuleService;
use Illuminate\Console\Command;

class ApplyCampaignRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:apply-campaign-rules 
                            {--rule-id= : Apply a specific campaign rule by ID}
                            {--client-id= : Apply rules only for a specific client}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply campaign rules to existing leads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new CampaignRuleService();
        $isDryRun = $this->option('dry-run');
        $ruleId = $this->option('rule-id');
        $clientId = $this->option('client-id');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($ruleId) {
            // Apply specific rule
            $rule = CampaignRule::find($ruleId);
            
            if (!$rule) {
                $this->error("Campaign rule with ID {$ruleId} not found.");
                return 1;
            }

            if (!$rule->is_active) {
                $this->warn("Campaign rule '{$rule->campaign_name}' is inactive.");
                return 1;
            }

            $this->info("Applying rule: {$rule->campaign_name} (ID: {$rule->id})");
            
            if ($isDryRun) {
                $affectedLeads = $service->previewRuleApplication($rule);
                $this->info("Would update {$affectedLeads->count()} leads:");
                
                foreach ($affectedLeads->take(10) as $lead) {
                    $this->line("  - Lead #{$lead->id}: {$lead->name} ({$lead->email})");
                }
                
                if ($affectedLeads->count() > 10) {
                    $this->line("  ... and " . ($affectedLeads->count() - 10) . " more");
                }
            } else {
                $results = $service->applyRuleToAllLeads($rule);
                $this->info("✅ Rule applied successfully!");
                $this->info("Processed: {$results['processed']} leads");
                $this->info("Updated: {$results['updated']} campaigns");
                
                if ($results['errors'] > 0) {
                    $this->warn("Errors: {$results['errors']}");
                }
            }

        } else {
            // Apply all rules
            $query = CampaignRule::where('is_active', true);
            
            if ($clientId) {
                $query->where('client_id', $clientId);
                $this->info("Applying rules for client ID: {$clientId}");
            } else {
                $this->info("Applying all active campaign rules...");
            }

            $rules = $query->orderBy('priority', 'desc')
                          ->orderBy('id', 'asc')
                          ->get();

            if ($rules->isEmpty()) {
                $this->warn("No active campaign rules found.");
                return 0;
            }

            $this->info("Found {$rules->count()} active rules to apply:");
            
            foreach ($rules as $rule) {
                $this->line("  - {$rule->campaign_name} (Priority: {$rule->priority})");
            }
            
            $this->newLine();

            if ($isDryRun) {
                $totalAffected = 0;
                
                foreach ($rules as $rule) {
                    $affectedLeads = $service->previewRuleApplication($rule);
                    $count = $affectedLeads->count();
                    $totalAffected += $count;
                    
                    $this->info("Rule '{$rule->campaign_name}': would affect {$count} leads");
                }
                
                $this->newLine();
                $this->info("Total leads that would be updated: {$totalAffected}");
                
            } else {
                if (!$this->confirm('Are you sure you want to apply all these rules?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }

                $results = $service->applyAllRulesToAllLeads();
                
                $this->newLine();
                $this->info("✅ All rules applied successfully!");
                $this->info("Rules applied: {$results['rules_applied']}");
                $this->info("Leads processed: {$results['processed']}");
                $this->info("Campaigns updated: {$results['updated']}");
                
                if ($results['errors'] > 0) {
                    $this->warn("Errors encountered: {$results['errors']}");
                }
            }
        }

        return 0;
    }
}
