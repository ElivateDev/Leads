<?php

namespace Database\Seeders;

use App\Models\EmailProcessingLog;
use App\Models\Client;
use App\Models\Lead;
use App\Models\ClientEmail;
use Illuminate\Database\Seeder;

class EmailProcessingLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample email processing logs

        // Email received logs
        EmailProcessingLog::factory()
            ->emailReceived()
            ->count(10)
            ->create();

        // Rule matched logs
        EmailProcessingLog::factory()
            ->ruleMatched()
            ->count(8)
            ->create();

        // Lead created logs
        EmailProcessingLog::factory()
            ->leadCreated()
            ->count(6)
            ->create();

        // Error logs
        EmailProcessingLog::factory()
            ->error()
            ->count(3)
            ->create();

        // Mixed logs
        EmailProcessingLog::factory()
            ->count(15)
            ->create();
    }
}
