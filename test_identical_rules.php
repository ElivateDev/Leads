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

// Create identical combined rules (same email + same conditions)
$rule1 = ClientEmail::create([
    'client_id' => $client1->id,
    'rule_type' => 'combined_rule',
    'email' => 'leads@platform.com',
    'custom_conditions' => 'source: facebook',
    'is_active' => true,
    'description' => 'Duplicate Rule 1 - First created'
]);

sleep(1); // Ensure different creation timestamps

$rule2 = ClientEmail::create([
    'client_id' => $client2->id,
    'rule_type' => 'combined_rule',
    'email' => 'leads@platform.com',
    'custom_conditions' => 'source: facebook',
    'is_active' => true,
    'description' => 'Duplicate Rule 2 - Second created'
]);

echo "Created identical rules:\n";
echo "Rule 1 (ID: {$rule1->id}, Created: {$rule1->created_at}): {$rule1->email} + '{$rule1->custom_conditions}' -> {$client1->name}\n";
echo "Rule 2 (ID: {$rule2->id}, Created: {$rule2->created_at}): {$rule2->email} + '{$rule2->custom_conditions}' -> {$client2->name}\n";
echo "\n";

// Test with email that matches both rules identically
$mockEmail = (object) [
    'fromAddress' => 'leads@platform.com',
    'fromName' => 'Platform Leads',
    'subject' => 'New Facebook Lead',
    'textPlain' => 'This is a lead from source: facebook with customer details'
];

// Test which client gets matched using reflection to access private method
$processor = new EmailLeadProcessor();
$reflection = new ReflectionClass($processor);
$method = $reflection->getMethod('findClientForEmail');
$method->setAccessible(true);

echo "Testing email that matches both rules identically:\n";
echo "Email: {$mockEmail->fromAddress}\n";
echo "Content: '{$mockEmail->textPlain}'\n";

$matchedClient = $method->invoke($processor, $mockEmail);
echo "Matched Client: " . ($matchedClient ? "{$matchedClient->name} (ID: {$matchedClient->id})" : 'None') . "\n";

// Check which rule would be found first in different query scenarios
echo "\nRule processing order analysis:\n";

// Exact email match query (Step 1)
$exactMatch = ClientEmail::where('is_active', true)
    ->where('email', 'leads@platform.com')
    ->with('client')
    ->first();
echo "1. Exact email match (first()): Rule ID {$exactMatch->id} -> {$exactMatch->client->name}\n";

// Domain pattern query (Step 2) - this wouldn't match since it's exact email
echo "2. Domain pattern query: Not applicable (exact email, not domain)\n";

// Custom/combined rules query (Step 3)
$customRules = ClientEmail::where('is_active', true)
    ->whereIn('rule_type', ['custom_rule', 'combined_rule'])
    ->with('client')
    ->get();

echo "3. Custom/combined rules (get() then lazy()->first()):\n";
foreach ($customRules as $index => $rule) {
    if ($rule->email === 'leads@platform.com' && $rule->custom_conditions === 'source: facebook') {
        echo "   - Rule ID {$rule->id} -> {$rule->client->name} (Position: " . ($index + 1) . ")\n";
    }
}

// Test the matchesEmail method for both rules
echo "\nRule matching test:\n";
echo "Rule 1 matches: " . ($rule1->matchesEmail($mockEmail) ? 'YES' : 'NO') . "\n";
echo "Rule 2 matches: " . ($rule2->matchesEmail($mockEmail) ? 'YES' : 'NO') . "\n";

// Clean up
$rule1->delete();
$rule2->delete();
$client1->delete();
$client2->delete();

echo "\nTest completed and cleaned up.\n";
