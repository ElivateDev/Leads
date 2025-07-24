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

// Create combined rules with same email but different conditions
$rule1 = ClientEmail::create([
    'client_id' => $client1->id,
    'rule_type' => 'combined_rule',
    'email' => 'leads@platform.com',
    'custom_conditions' => 'rep: john',
    'is_active' => true,
    'description' => 'Combined Rule 1 - For John'
]);

$rule2 = ClientEmail::create([
    'client_id' => $client2->id,
    'rule_type' => 'combined_rule',
    'email' => 'leads@platform.com',
    'custom_conditions' => 'rep: sarah',
    'is_active' => true,
    'description' => 'Combined Rule 2 - For Sarah'
]);

echo "Created combined rules:\n";
echo "Rule 1 (ID: {$rule1->id}): {$rule1->email} + '{$rule1->custom_conditions}' -> {$client1->name}\n";
echo "Rule 2 (ID: {$rule2->id}): {$rule2->email} + '{$rule2->custom_conditions}' -> {$client2->name}\n";
echo "\n";

// Test with email that matches John's condition
$mockEmailJohn = (object) [
    'fromAddress' => 'leads@platform.com',
    'fromName' => 'Platform Leads',
    'subject' => 'New Lead Assignment',
    'textPlain' => 'This lead is for rep: john and requires immediate attention'
];

// Test with email that matches Sarah's condition
$mockEmailSarah = (object) [
    'fromAddress' => 'leads@platform.com',
    'fromName' => 'Platform Leads',
    'subject' => 'New Lead Assignment',
    'textPlain' => 'This lead is for rep: sarah and is a high-value prospect'
];

// Test with email that matches neither condition
$mockEmailOther = (object) [
    'fromAddress' => 'leads@platform.com',
    'fromName' => 'Platform Leads',
    'subject' => 'New Lead Assignment',
    'textPlain' => 'This lead is for rep: mike and needs follow-up'
];

// Test which client gets matched using reflection to access private method
$processor = new EmailLeadProcessor();
$reflection = new ReflectionClass($processor);
$method = $reflection->getMethod('findClientForEmail');
$method->setAccessible(true);

echo "Testing email for John:\n";
$matchedClient = $method->invoke($processor, $mockEmailJohn);
echo "Email content: '{$mockEmailJohn->textPlain}'\n";
echo "Matched Client: " . ($matchedClient ? "{$matchedClient->name} (ID: {$matchedClient->id})" : 'None') . "\n\n";

echo "Testing email for Sarah:\n";
$matchedClient = $method->invoke($processor, $mockEmailSarah);
echo "Email content: '{$mockEmailSarah->textPlain}'\n";
echo "Matched Client: " . ($matchedClient ? "{$matchedClient->name} (ID: {$matchedClient->id})" : 'None') . "\n\n";

echo "Testing email for Mike (no matching rule):\n";
$matchedClient = $method->invoke($processor, $mockEmailOther);
echo "Email content: '{$mockEmailOther->textPlain}'\n";
echo "Matched Client: " . ($matchedClient ? "{$matchedClient->name} (ID: {$matchedClient->id})" : 'None') . "\n\n";

// Clean up
$rule1->delete();
$rule2->delete();
$client1->delete();
$client2->delete();

echo "Test completed and cleaned up.\n";
