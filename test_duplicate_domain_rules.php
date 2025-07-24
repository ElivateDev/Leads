<?php

require_once 'vendor/autoload.php';

use App\Models\Client;
use App\Models\ClientEmail;
use App\Services\EmailLeadProcessor;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

// Bootstrap the Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set test configuration
Config::set('services.imap', [
    'host' => 'test.example.com',
    'port' => 993,
    'encryption' => 'ssl',
    'username' => 'test@example.com',
    'password' => 'password',
    'default_folder' => 'INBOX',
]);

// Create test clients
$client1 = Client::create([
    'name' => 'Client One',
    'email' => 'client1@example.com',
    'email_notifications' => true,
]);

$client2 = Client::create([
    'name' => 'Client Two',
    'email' => 'client2@example.com',
    'email_notifications' => true,
]);

// Create duplicate distribution rules with the same domain pattern
$rule1 = ClientEmail::create([
    'client_id' => $client1->id,
    'rule_type' => 'email_match',
    'email' => '@testdomain.com',
    'is_active' => true,
    'description' => 'Domain Rule 1 - First rule created'
]);

$rule2 = ClientEmail::create([
    'client_id' => $client2->id,
    'rule_type' => 'email_match',
    'email' => '@testdomain.com',
    'is_active' => true,
    'description' => 'Domain Rule 2 - Second rule created'
]);

echo "Created domain rules:\n";
echo "Rule 1 (ID: {$rule1->id}): {$rule1->email} -> {$client1->name}\n";
echo "Rule 2 (ID: {$rule2->id}): {$rule2->email} -> {$client2->name}\n";
echo "\n";

// Create a mock email
$mockEmail = (object) [
    'fromAddress' => 'contact@testdomain.com',
    'fromName' => 'Test Sender',
    'subject' => 'Test Lead',
    'textPlain' => 'This is a test lead message'
];

// Test which client gets matched using reflection to access private method
$processor = new EmailLeadProcessor();
$reflection = new ReflectionClass($processor);
$method = $reflection->getMethod('findClientForEmail');
$method->setAccessible(true);

$matchedClient = $method->invoke($processor, $mockEmail);

echo "Email: {$mockEmail->fromAddress}\n";
echo "Matched Client: " . ($matchedClient ? "{$matchedClient->name} (ID: {$matchedClient->id})" : 'None') . "\n";

// Check the processing order for domain patterns
$domainRules = ClientEmail::where('is_active', true)
    ->whereIn('rule_type', ['email_match', 'combined_rule'])
    ->where(function ($query) {
        $query->where('email', "@testdomain.com")
            ->orWhere('email', 'LIKE', "%@testdomain.com");
    })
    ->with('client')
    ->get();

echo "\nDomain rules found (in order):\n";
foreach ($domainRules as $rule) {
    echo "- Rule ID {$rule->id}: {$rule->email} -> {$rule->client->name}\n";
}

// Clean up
$rule1->delete();
$rule2->delete();
$client1->delete();
$client2->delete();

echo "\nTest completed and cleaned up.\n";
