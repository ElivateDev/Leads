<?php

/**
 * Test script for Lead Source Rules functionality
 *
 * This script demonstrates how the lead source rules work with various email scenarios.
 * Run this after setting up the LeadSourceRule model and seeding some rules.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\LeadSourceRule;
use App\Models\Client;

// Mock email object for testing
class MockEmail
{
    public function __construct(
        public string $fromAddress,
        public string $fromName = '',
        public string $subject = '',
        public string $textPlain = ''
    ) {
    }
}

// Test scenarios
$testEmails = [
    new MockEmail(
        'noreply@facebook.com',
        'Facebook',
        'New message from your Facebook page',
        'Someone sent you a message through your Facebook business page.'
    ),
    new MockEmail(
        'contact@business.com',
        'John Doe',
        'Contact Form Submission',
        'Name: John Doe\nEmail: john@example.com\nMessage: I need help with your services.'
    ),
    new MockEmail(
        'jane@gmail.com',
        'Jane Smith',
        'Referral inquiry',
        'Hi, I was referred by Mike Johnson. I would like to know more about your services.'
    ),
    new MockEmail(
        'system@voip.com',
        'Phone System',
        'Voicemail from 555-123-4567',
        'You have a new voicemail message from 555-123-4567.'
    ),
    new MockEmail(
        'bob@example.com',
        'Bob Wilson',
        'General inquiry',
        'Hello, I found your website at https://example.com?utm_source=instagram&utm_campaign=summer and would like more information.'
    ),
];

echo "=== Lead Source Rules Test ===\n\n";

// Get first client for testing
$client = Client::first();
if (!$client) {
    echo "❌ No clients found. Please create a client first.\n";
    exit(1);
}

echo "Testing with Client: {$client->name} (ID: {$client->id})\n\n";

// Get all active rules for this client
$rules = LeadSourceRule::where('client_id', $client->id)
    ->where('is_active', true)
    ->orderBy('priority', 'desc')
    ->get();

echo "📋 Active Lead Source Rules ({$rules->count()}):\n";
foreach ($rules as $rule) {
    echo "  {$rule->priority}. [{$rule->source_name}] {$rule->rule_type}:{$rule->rule_value} on {$rule->match_field}\n";
}
echo "\n";

// Test each email scenario
foreach ($testEmails as $index => $email) {
    echo "🧪 Test " . ($index + 1) . ": {$email->fromAddress}\n";
    echo "   Subject: {$email->subject}\n";
    echo "   Body: " . substr($email->textPlain, 0, 50) . "...\n";

    $matchedRule = null;
    $determinedSource = 'other'; // default

    // Test each rule
    foreach ($rules as $rule) {
        if ($rule->matchesEmail($email)) {
            $matchedRule = $rule;
            $determinedSource = $rule->source_name;
            break;
        }
    }

    if ($matchedRule) {
        echo "   ✅ Matched Rule: [{$matchedRule->source_name}] {$matchedRule->rule_type}:{$matchedRule->rule_value} (priority: {$matchedRule->priority})\n";
        echo "   📊 Determined Source: {$determinedSource}\n";
    } else {
        echo "   ❌ No rules matched\n";
        echo "   📊 Default Source: {$determinedSource}\n";
    }

    echo "\n";
}

echo "✅ Test complete!\n";
echo "\n=== How to create custom rules ===\n";
echo "LeadSourceRule::create([\n";
echo "    'client_id' => {$client->id},\n";
echo "    'source_name' => 'social', // website, phone, referral, social, other\n";
echo "    'rule_type' => 'contains', // contains, exact, regex, url_parameter, domain\n";
echo "    'rule_value' => 'facebook',\n";
echo "    'match_field' => 'from_email', // body, subject, url, from_email, from_domain\n";
echo "    'is_active' => true,\n";
echo "    'priority' => 80, // 0-100, higher = checked first\n";
echo "    'description' => 'Emails from Facebook'\n";
echo "]);\n";
