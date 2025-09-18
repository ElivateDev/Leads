<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'api:token {email} {name} {--expires-days=365}';
    protected $description = 'Create an API token for a user';

    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $expiresDays = (int) $this->option('expires-days');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $expiresAt = $expiresDays > 0 ? now()->addDays($expiresDays) : null;
        $token = $user->createApiToken($name, null, $expiresAt);

        $this->info("API token created successfully!");
        $this->line("User: {$user->name} ({$user->email})");
        $this->line("Token Name: {$name}");
        $this->line("Expires: " . ($expiresAt ? $expiresAt->toDateString() : 'Never'));
        $this->line("");
        $this->warn("Store this token securely - it won't be shown again:");
        $this->line($token);

        return 0;
    }
}