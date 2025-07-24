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

echo "=== Testing Multiple Client Lead Creation ===\n\n";

// Create test clients
$client1 = Client::create([
    'name' => 'Real Estate Agent 1',
    'email' => 'agent1@example.com',
    'email_notifications' => true,
]);

$client2 = Client::create([
    'name' => 'Real Estate Agent 2',
    'email' => 'agent2@example.com',
    'email_notifications' => true,
]);

// Create identical distribution rules
$rule1 = ClientEmail::create([
    'client_id' => $client1->id,
    'rule_type' => 'email_match',
    'email' => 'leads@zillow.com',
    'is_active' => true,
    'description' => 'Zillow leads for Agent 1'
]);

$rule2 = ClientEmail::create([
    'client_id' => $client2->id,
    'rule_type' => 'email_match',
    'email' => 'leads@zillow.com',
    'is_active' => true,
    'description' => 'Zillow leads for Agent 2'
]);

echo "Created clients and rules:\n";
echo "- {$client1->name}: Rule for leads@zillow.com\n";
echo "- {$client2->name}: Rule for leads@zillow.com\n\n";

// Create a mock Zillow lead email
$mockEmail = (object) [
    'fromAddress' => 'leads@zillow.com',
    'fromName' => 'Zillow Leads',
    'subject' => 'New Property Inquiry',
    'textPlain' => 'New lead from John Smith. Email: john.smith@gmail.com Phone: 555-123-4567. Looking for a 3-bedroom home in downtown.',
    'date' => now()->format('r')
];

echo "Processing email from: {$mockEmail->fromAddress}\n";
echo "Subject: {$mockEmail->subject}\n\n";

// Process the email
$processor = new EmailLeadProcessor();
$reflection = new ReflectionClass($processor);
$method = $reflection->getMethod('processEmail');
$method->setAccessible(true);

$leads = $method->invoke($processor, $mockEmail);

echo "\n=== Results ===\n";
echo "Leads created: " . count($leads) . "\n\n";

foreach ($leads as $index => $lead) {
    echo "Lead " . ($index + 1) . ":\n";
    echo "  - Client: {$lead->client->name}\n";
    echo "  - Name: {$lead->name}\n";
    echo "  - Email: {$lead->email}\n";
    echo "  - Phone: {$lead->phone}\n";
    echo "  - Message: " . substr($lead->message, 0, 50) . "...\n\n";
}

// Check email processing logs
$logs = \App\Models\EmailProcessingLog::where('type', 'rule_matched')->get();
echo "Rule match logs: " . $logs->count() . "\n";
foreach ($logs as $log) {
    echo "  - {$log->client->name}: {$log->message}\n";
}

// Clean up
$rule1->delete();
$rule2->delete();
$client1->delete();
$client2->delete();
foreach ($leads as $lead) {
    $lead->delete();
}
\App\Models\EmailProcessingLog::where('from_address', 'leads@zillow.com')->delete();

echo "\nTest completed and cleaned up.\n";
