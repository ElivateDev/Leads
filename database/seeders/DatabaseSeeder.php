<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in logical order (dependencies first)
        $this->call([
            ClientUserSeeder::class,              // Creates clients and users first
            UpdateClientLeadDispositionsSeeder::class,  // Updates client settings
            CampaignRuleSeeder::class,            // Creates campaign rules
            LeadSourceRuleSeeder::class,          // Creates lead source rules
            SampleLeadsSeeder::class,             // Creates sample leads (depends on clients)
            EmailProcessingLogSeeder::class,      // Creates sample logs
        ]);

        // Create additional test user if needed
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
