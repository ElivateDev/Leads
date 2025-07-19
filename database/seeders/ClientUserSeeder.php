<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create an admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'client_id' => null,
        ]);

        // Get or create a client
        $client = Client::first();
        if (!$client) {
            $client = Client::create([
                'name' => 'Test Client Company',
                'email' => 'info@testclient.com',
                'phone' => '555-0123',
                'company' => 'Test Client LLC',
                'email_notifications' => true,
            ]);
        }

        // Create a client user
        User::create([
            'name' => 'Client User',
            'email' => 'client@testclient.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'client_id' => $client->id,
        ]);
    }
}
