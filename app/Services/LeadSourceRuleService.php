<?php

namespace App\Services;

use App\Models\LeadSourceRule;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class LeadSourceRuleService
{
    /**
     * Apply lead source rules to all leads for a specific rule
     */
    public function applyRuleToAllLeads(LeadSourceRule $rule): array
    {
        $results = [
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        if (!$rule->is_active) {
            return $results;
        }

        // Get all leads for this client that don't already have this source
        $leads = Lead::where('client_id', $rule->client_id)
            ->where(function ($query) use ($rule) {
                $query->where('source', '!=', $rule->source_name)
                    ->orWhereNull('source');
            })
            ->get();

        foreach ($leads as $lead) {
            $results['processed']++;

            try {
                // Create a mock email object from the lead data
                $mockEmail = $this->createMockEmailFromLead($lead);

                // Check if this rule matches the lead
                if ($rule->matchesEmail($mockEmail)) {
                    $oldSource = $lead->source;
                    $lead->source = $rule->source_name;
                    $lead->save();

                    $results['updated']++;

                    Log::info('Lead source rule applied to existing lead', [
                        'lead_id' => $lead->id,
                        'rule_id' => $rule->id,
                        'old_source' => $oldSource,
                        'new_source' => $rule->source_name,
                    ]);
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Error applying lead source rule to lead', [
                    'lead_id' => $lead->id,
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Apply all active lead source rules to all leads
     */
    public function applyAllRulesToAllLeads(): array
    {
        $totalResults = [
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
            'rules_applied' => 0,
        ];

        $activeRules = LeadSourceRule::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($activeRules as $rule) {
            $ruleResults = $this->applyRuleToAllLeads($rule);
            
            $totalResults['processed'] += $ruleResults['processed'];
            $totalResults['updated'] += $ruleResults['updated'];
            $totalResults['errors'] += $ruleResults['errors'];
            
            if ($ruleResults['updated'] > 0) {
                $totalResults['rules_applied']++;
            }
        }

        return $totalResults;
    }

    /**
     * Preview which leads would be affected by a rule without actually updating them
     */
    public function previewRuleApplication(LeadSourceRule $rule): Collection
    {
        if (!$rule->is_active) {
            return collect();
        }

        $leads = Lead::where('client_id', $rule->client_id)
            ->where(function ($query) use ($rule) {
                $query->where('source', '!=', $rule->source_name)
                    ->orWhereNull('source');
            })
            ->get();

        return $leads->filter(function ($lead) use ($rule) {
            $mockEmail = $this->createMockEmailFromLead($lead);
            return $rule->matchesEmail($mockEmail);
        });
    }

    /**
     * Create a mock email object from lead data for rule matching
     */
    private function createMockEmailFromLead(Lead $lead): object
    {
        return (object) [
            'subject' => $lead->email_subject ?? '',
            'fromAddress' => $lead->from_email ?? '',
            'textPlain' => $lead->message ?? '',
            'body' => $lead->message ?? '',
        ];
    }
}