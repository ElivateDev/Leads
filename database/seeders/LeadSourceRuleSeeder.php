<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeadSourceRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first client for demo rules (you can modify this)
        $client = \App\Models\Client::first();

        if (!$client) {
            $this->command->warn('No clients found. Please create a client first before running this seeder.');
            return;
        }

        $rules = [
            // Social media rules (high priority)
            [
                'client_id' => $client->id,
                'source_name' => 'social',
                'rule_type' => 'domain',
                'rule_value' => 'facebook.com',
                'match_field' => 'from_domain',
                'is_active' => true,
                'priority' => 90,
                'description' => 'Emails from Facebook domain',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'social',
                'rule_type' => 'domain',
                'rule_value' => 'instagram.com',
                'match_field' => 'from_domain',
                'is_active' => true,
                'priority' => 90,
                'description' => 'Emails from Instagram domain',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'social',
                'rule_type' => 'domain',
                'rule_value' => 'linkedin.com',
                'match_field' => 'from_domain',
                'is_active' => true,
                'priority' => 90,
                'description' => 'Emails from LinkedIn domain',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'social',
                'rule_type' => 'contains',
                'rule_value' => 'twitter',
                'match_field' => 'from_email',
                'is_active' => true,
                'priority' => 85,
                'description' => 'Emails containing "twitter" in sender address',
            ],

            // Website contact form rules
            [
                'client_id' => $client->id,
                'source_name' => 'website',
                'rule_type' => 'contains',
                'rule_value' => 'contact form',
                'match_field' => 'subject',
                'is_active' => true,
                'priority' => 80,
                'description' => 'Contact form submissions',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'website',
                'rule_type' => 'contains',
                'rule_value' => 'website inquiry',
                'match_field' => 'subject',
                'is_active' => true,
                'priority' => 80,
                'description' => 'Website inquiry submissions',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'website',
                'rule_type' => 'contains',
                'rule_value' => 'noreply@',
                'match_field' => 'from_email',
                'is_active' => true,
                'priority' => 75,
                'description' => 'No-reply emails (usually from websites)',
            ],

            // Referral rules
            [
                'client_id' => $client->id,
                'source_name' => 'referral',
                'rule_type' => 'contains',
                'rule_value' => 'referral',
                'match_field' => 'body',
                'is_active' => true,
                'priority' => 70,
                'description' => 'Emails mentioning referral in body',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'referral',
                'rule_type' => 'contains',
                'rule_value' => 'referred by',
                'match_field' => 'body',
                'is_active' => true,
                'priority' => 70,
                'description' => 'Emails mentioning "referred by" in body',
            ],

            // URL parameter rules (for tracking)
            [
                'client_id' => $client->id,
                'source_name' => 'social',
                'rule_type' => 'url_parameter',
                'rule_value' => 'utm_source=facebook',
                'match_field' => 'url',
                'is_active' => true,
                'priority' => 95,
                'description' => 'Facebook UTM tracking parameter',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'social',
                'rule_type' => 'url_parameter',
                'rule_value' => 'utm_source=instagram',
                'match_field' => 'url',
                'is_active' => true,
                'priority' => 95,
                'description' => 'Instagram UTM tracking parameter',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'website',
                'rule_type' => 'url_parameter',
                'rule_value' => 'utm_source=website',
                'match_field' => 'url',
                'is_active' => true,
                'priority' => 85,
                'description' => 'Website UTM tracking parameter',
            ],

            // Phone-related rules
            [
                'client_id' => $client->id,
                'source_name' => 'phone',
                'rule_type' => 'contains',
                'rule_value' => 'voicemail',
                'match_field' => 'subject',
                'is_active' => true,
                'priority' => 80,
                'description' => 'Voicemail notifications',
            ],
            [
                'client_id' => $client->id,
                'source_name' => 'phone',
                'rule_type' => 'contains',
                'rule_value' => 'missed call',
                'match_field' => 'subject',
                'is_active' => true,
                'priority' => 80,
                'description' => 'Missed call notifications',
            ],
        ];

        foreach ($rules as $rule) {
            \App\Models\LeadSourceRule::firstOrCreate(
                [
                    'client_id' => $rule['client_id'],
                    'rule_type' => $rule['rule_type'],
                    'rule_value' => $rule['rule_value'],
                    'match_field' => $rule['match_field'],
                ],
                $rule
            );
        }

        $this->command->info('Lead source rules seeded successfully!');
    }
}
