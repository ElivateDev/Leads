<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateClientLeadDispositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update all existing clients to have default lead dispositions
        Client::whereNull('lead_dispositions')->update([
            'lead_dispositions' => Client::getDefaultDispositions()
        ]);
    }
}
