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

// Create duplicate distribution rules with the same email pattern
$rule1 = ClientEmail::create([
    'client_id' => $client1->id,
    'rule_type' => 'email_match',
    'email' => 'leads@example.com',
    'is_active' => true,
    'description' => 'Rule 1 - First rule created'
]);

$rule2 = ClientEmail::create([
    'client_id' => $client2->id,
    'rule_type' => 'email_match',
    'email' => 'leads@example.com',
    'is_active' => true,
    'description' => 'Rule 2 - Second rule created'
]);

echo "Created rules:\n";
echo "Rule 1 (ID: {$rule1->id}): {$rule1->email} -> {$client1->name}\n";
echo "Rule 2 (ID: {$rule2->id}): {$rule2->email} -> {$client2->name}\n";
echo "\n";

// Create a mock email
$mockEmail = (object) [
    'fromAddress' => 'leads@example.com',
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

// Check which rule was found first in the database
$firstRule = ClientEmail::where('email', 'leads@example.com')->where('is_active', true)->first();
echo "First rule found in database: ID {$firstRule->id} -> {$firstRule->client->name}\n";

// Clean up
$rule1->delete();
$rule2->delete();
$client1->delete();
$client2->delete();

echo "\nTest completed and cleaned up.\n";
