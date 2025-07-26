<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadSourceRule;
use App\Models\Client;

class TestLeadSourceRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:lead-source-rules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test lead source rules functionality with mock emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Lead Source Rules Test ===');
        $this->newLine();

        // Get first client for testing
        $client = Client::first();
        if (!$client) {
            $this->error('❌ No clients found. Please create a client first.');
            return 1;
        }

        $this->info("Testing with Client: {$client->name} (ID: {$client->id})");
        $this->newLine();

        // Get all active rules for this client
        $rules = LeadSourceRule::where('client_id', $client->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        $this->info("📋 Active Lead Source Rules ({$rules->count()}):");
        foreach ($rules as $rule) {
            $this->line("  {$rule->priority}. [{$rule->source_name}] {$rule->rule_type}:{$rule->rule_value} on {$rule->match_field}");
        }
        $this->newLine();

        // Test email scenarios
        $testEmails = $this->getTestEmails();

        foreach ($testEmails as $index => $email) {
            $this->info("🧪 Test " . ($index + 1) . ": {$email->fromAddress}");
            $this->line("   Subject: {$email->subject}");
            $this->line("   Body: " . substr($email->textPlain, 0, 50) . "...");

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
                $this->line("   ✅ Matched Rule: [{$matchedRule->source_name}] {$matchedRule->rule_type}:{$matchedRule->rule_value} (priority: {$matchedRule->priority})");
                $this->line("   📊 Determined Source: {$determinedSource}");
            } else {
                $this->line("   ❌ No rules matched");
                $this->line("   📊 Default Source: {$determinedSource}");
            }

            $this->newLine();
        }

        $this->info('✅ Test complete!');
        $this->newLine();
        $this->info('=== How to create custom rules ===');
        $this->line("LeadSourceRule::create([");
        $this->line("    'client_id' => {$client->id},");
        $this->line("    'source_name' => 'social', // website, phone, referral, social, other");
        $this->line("    'rule_type' => 'contains', // contains, exact, regex, url_parameter, domain");
        $this->line("    'rule_value' => 'facebook',");
        $this->line("    'match_field' => 'from_email', // body, subject, url, from_email, from_domain");
        $this->line("    'is_active' => true,");
        $this->line("    'priority' => 80, // 0-100, higher = checked first");
        $this->line("    'description' => 'Emails from Facebook'");
        $this->line("]);");

        return 0;
    }

    private function getTestEmails(): array
    {
        // Mock email object for testing
        return [
            (object) [
                'fromAddress' => 'noreply@facebook.com',
                'fromName' => 'Facebook',
                'subject' => 'New message from your Facebook page',
                'textPlain' => 'Someone sent you a message through your Facebook business page.'
            ],
            (object) [
                'fromAddress' => 'contact@business.com',
                'fromName' => 'John Doe',
                'subject' => 'Contact Form Submission',
                'textPlain' => "Name: John Doe\nEmail: john@example.com\nMessage: I need help with your services."
            ],
            (object) [
                'fromAddress' => 'jane@gmail.com',
                'fromName' => 'Jane Smith',
                'subject' => 'Referral inquiry',
                'textPlain' => 'Hi, I was referred by Mike Johnson. I would like to know more about your services.'
            ],
            (object) [
                'fromAddress' => 'system@voip.com',
                'fromName' => 'Phone System',
                'subject' => 'Voicemail from 555-123-4567',
                'textPlain' => 'You have a new voicemail message from 555-123-4567.'
            ],
            (object) [
                'fromAddress' => 'bob@example.com',
                'fromName' => 'Bob Wilson',
                'subject' => 'General inquiry',
                'textPlain' => 'Hello, I found your website at https://example.com?utm_source=instagram&utm_campaign=summer and would like more information.'
            ],
        ];
    }
}
