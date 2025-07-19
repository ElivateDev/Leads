<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Client;
use Illuminate\Database\Seeder;

class SampleLeadsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $client = Client::first();

        if (!$client) {
            $this->command->error('No clients found. Please run ClientUserSeeder first.');
            return;
        }

        $sampleLeads = [
            [
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'phone' => '555-0123',
                'message' => 'Hi, I\'m interested in your services. Could you please provide more information about pricing and availability?',
                'status' => 'new',
                'source' => 'website',
                'from_email' => 'contactform@yourwebsite.com',
                'email_subject' => 'Contact Form Submission',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@email.com',
                'phone' => '555-0456',
                'message' => 'Hello! I saw your ad on social media and I\'m very interested. When would be a good time to discuss my project requirements?',
                'status' => 'contacted',
                'source' => 'social',
                'from_email' => 'sarah.johnson@email.com',
                'email_subject' => 'Inquiry about your services',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'mike@company.com',
                'phone' => '555-0789',
                'message' => 'We\'re a growing company looking for a reliable partner. Can we schedule a call to discuss our needs?',
                'status' => 'qualified',
                'source' => 'referral',
                'from_email' => 'mike@company.com',
                'email_subject' => 'Business Partnership Opportunity',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@startup.io',
                'phone' => '555-0321',
                'message' => 'Thanks for the great service! We\'re ready to move forward with the premium package.',
                'status' => 'converted',
                'source' => 'website',
                'from_email' => 'emily.davis@startup.io',
                'email_subject' => 'Ready to proceed',
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david@example.org',
                'phone' => null,
                'message' => 'I was interested initially but decided to go with another provider. Thank you for your time.',
                'status' => 'lost',
                'source' => 'other',
                'from_email' => 'david@example.org',
                'email_subject' => 'Thanks but no thanks',
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@business.com',
                'phone' => '555-0987',
                'message' => 'I need urgent assistance with my project. Please call me as soon as possible!',
                'status' => 'new',
                'source' => 'website',
                'from_email' => 'urgent@business.com',
                'email_subject' => 'URGENT: Need immediate help',
            ],
        ];

        foreach ($sampleLeads as $leadData) {
            Lead::create(array_merge($leadData, [
                'client_id' => $client->id,
                'email_received_at' => now()->subDays(rand(0, 10))->subHours(rand(0, 23)),
            ]));
        }

        $this->command->info('Sample leads created successfully!');
    }
}
