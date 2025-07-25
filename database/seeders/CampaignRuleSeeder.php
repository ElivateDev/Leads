<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampaignRule;
use App\Models\Client;

class CampaignRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first client or create a default one for the example
        $client = Client::first();

        if (!$client) {
            $client = Client::create([
                'name' => 'Sample Dental Practice',
                'email' => 'admin@sample.com',
                'email_notifications' => true,
            ]);
        }

        // Create sample campaign rules
        $rules = [
            [
                'client_id' => $client->id,
                'campaign_name' => 'Sierra Sky GA',
                'rule_type' => 'url_parameter',
                'rule_value' => 'gad_campaignid=22820616890',
                'match_field' => 'body',
                'priority' => 10,
                'description' => 'Google Ads campaign for Sierra Sky dental practice',
                'is_active' => true,
            ],
            [
                'client_id' => $client->id,
                'campaign_name' => 'Special Offer Campaign',
                'rule_type' => 'contains',
                'rule_value' => 'special-offer',
                'match_field' => 'url',
                'priority' => 8,
                'description' => 'Campaign triggered by special offer landing page',
                'is_active' => true,
            ],
            [
                'client_id' => $client->id,
                'campaign_name' => 'Facebook Lead Form',
                'rule_type' => 'contains',
                'rule_value' => 'facebook',
                'match_field' => 'from_email',
                'priority' => 5,
                'description' => 'Leads coming from Facebook lead forms',
                'is_active' => true,
            ],
            [
                'client_id' => $client->id,
                'campaign_name' => 'Braces Campaign',
                'rule_type' => 'contains',
                'rule_value' => '#braces',
                'match_field' => 'url',
                'priority' => 7,
                'description' => 'Campaign for orthodontics/braces services',
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            CampaignRule::create($rule);
        }

        $this->command->info('Created ' . count($rules) . ' sample campaign rules');
    }
}
