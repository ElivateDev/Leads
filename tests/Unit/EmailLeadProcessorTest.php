<?php

use App\Services\EmailLeadProcessor;
use App\Models\Lead;
use App\Models\Client;
use App\Models\ClientEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Helpers\PrivateMethodHelper;

uses(Tests\TestCase::class, RefreshDatabase::class, PrivateMethodHelper::class);

beforeEach(function () {
    Config::set('services.imap', [
        'host' => 'test.example.com',
        'port' => 993,
        'encryption' => 'ssl',
        'username' => 'test@example.com',
        'password' => 'password',
        'default_folder' => 'INBOX',
    ]);

    $this->client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'client@example.com',
        'email_notifications' => true,
    ]);

    $this->clientEmail = ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'email_match',
        'email' => 'info@testclient.com',
        'is_active' => true,
    ]);

    $this->mock(\PhpImap\Mailbox::class);
});

test('service configuration can be set up', function () {
    expect(config('services.imap.host'))->toBe('test.example.com');
    expect(config('services.imap.port'))->toBe(993);
});

test('finds client by exact email match', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'info@testclient.com'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('finds client by domain match', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'email_match',
        'email' => '@testclient.com',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'sales@testclient.com'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('returns null when no client match found', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'unknown@unknown.com'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->toBeNull();
});

test('gets default client from environment', function () {
    $processor = app(EmailLeadProcessor::class);

    putenv("DEFAULT_CLIENT_ID={$this->client->id}");

    $defaultClient = $this->callPrivateMethod($processor, 'getDefaultClient');
    expect($defaultClient)->not->toBeNull();
    expect($defaultClient->id)->toBe($this->client->id);

    putenv('DEFAULT_CLIENT_ID=');
});

test('detects existing lead by email', function () {
    $processor = app(EmailLeadProcessor::class);

    Lead::factory()->create([
        'email' => 'existing@example.com',
        'client_id' => $this->client->id,
    ]);

    $exists = $this->callPrivateMethod($processor, 'leadExists', 'existing@example.com', null, $this->client->id);
    expect($exists)->toBeTrue();
});

test('detects existing lead by phone', function () {
    $processor = app(EmailLeadProcessor::class);

    Lead::factory()->create([
        'phone' => '555-123-4567',
        'email' => null,
        'client_id' => $this->client->id,
    ]);

    $exists = $this->callPrivateMethod($processor, 'leadExists', '', '555-123-4567', $this->client->id);
    expect($exists)->toBeTrue();
});

test('returns false for non-existing lead', function () {
    $processor = app(EmailLeadProcessor::class);

    $exists = $this->callPrivateMethod($processor, 'leadExists', 'new@example.com', '555-999-8888', $this->client->id);
    expect($exists)->toBeFalse();
});

test('extracts name from email with name field', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Name: John Doe\nEmail: john@example.com\nMessage: Hello there',
        'fromName' => 'Contact Form',
        'fromAddress' => 'noreply@example.com'
    ];

    $name = $this->callPrivateMethod($processor, 'extractNameFromEmail', $mockEmail);
    expect($name)->toContain('John Doe');
});

test('extracts name from fromName when no name field found', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Just a regular message without name field',
        'fromName' => 'Jane Smith',
        'fromAddress' => 'jane@example.com'
    ];

    $name = $this->callPrivateMethod($processor, 'extractNameFromEmail', $mockEmail);
    expect($name)->toBe('Jane Smith');
});

test('ignores emails from automated sources', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'noreply@example.com',
        'subject' => 'Automated notification'
    ];

    $shouldIgnore = $this->callPrivateMethod($processor, 'shouldIgnoreEmail', $mockEmail);
    expect($shouldIgnore)->toBeTrue();
});

test('does not ignore valid lead emails', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'customer@example.com',
        'subject' => 'Contact inquiry'
    ];

    $shouldIgnore = $this->callPrivateMethod($processor, 'shouldIgnoreEmail', $mockEmail);
    expect($shouldIgnore)->toBeFalse();
});

test('extracts phone number from email content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Contact me at (555) 123-4567 or email me.',
        'textHtml' => null
    ];

    $phone = $this->callPrivateMethod($processor, 'extractPhoneNumber', $mockEmail);
    expect($phone)->toContain('555')->and($phone)->toContain('123')->and($phone)->toContain('4567');
});

test('returns null when no phone number found', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Just a message without phone number',
        'textHtml' => null
    ];

    $phone = $this->callPrivateMethod($processor, 'extractPhoneNumber', $mockEmail);
    expect($phone)->toBeNull();
});

test('extracts phone number from structured email with labeled phone field', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Name: Greg tracking test
Email: greg.f@elivate.net
Phone: 987654321
Consent: I Consent To Receiving Appointment-Related Communications From Alta Sky Dental & Orthodontics, Through The Provided Channels.
source: Facebook campaign 1

Date: July 18, 2025
Time: 10:59 pm
Page URL: https://skydentalaz.com/special-offer/?gtm_debug=1752879547874
User Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36
Remote IP: 116.71.161.174
Powered by: Elementor',
        'textHtml' => null
    ];

    $phone = $this->callPrivateMethod($processor, 'extractPhoneNumber', $mockEmail);
    expect($phone)->toBe('987654321');
});

test('determines lead source from email content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'subject' => 'Website contact form submission',
        'fromAddress' => 'noreply@website.com'
    ];

    $source = $this->callPrivateMethod($processor, 'determineLeadSource', $mockEmail);
    expect($source)->toBe('website');
});

test('determines social media source', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'subject' => 'Notification from Facebook',
        'fromAddress' => 'noreply@facebook.com'
    ];

    $source = $this->callPrivateMethod($processor, 'determineLeadSource', $mockEmail);
    expect($source)->toBe('social');
});

test('extracts email address from email content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Please contact me at customer@domain.com for more info.',
        'fromAddress' => 'noreply@website.com'
    ];

    $email = $this->callPrivateMethod($processor, 'extractEmailAddressFromEmail', $mockEmail);
    expect($email)->toBe('customer@domain.com');
});

test('falls back to sender email when no email in content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'No email address in this content.',
        'fromAddress' => 'sender@example.com'
    ];

    $email = $this->callPrivateMethod($processor, 'extractEmailAddressFromEmail', $mockEmail);
    expect($email)->toBe('sender@example.com');
});

test('finds client by custom rule with single condition', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'custom_rule',
        'custom_conditions' => 'Source: Facebook',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@unmatched-domain.net',
        'textPlain' => 'This is a lead from Source: Facebook',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('finds client by custom rule with AND condition', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'custom_rule',
        'custom_conditions' => 'Source: Facebook AND rep: henry',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@unmatched-domain.net',
        'textPlain' => 'This is a lead from Source: Facebook and rep: henry is handling it',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('custom rule with AND condition fails if one condition is missing', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'custom_rule',
        'custom_conditions' => 'Source: Facebook AND rep: henry',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@unmatched-domain.net',
        'textPlain' => 'This is a lead from Source: Facebook only',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->toBeNull();
});

test('finds client by custom rule with OR condition', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'custom_rule',
        'custom_conditions' => 'Source: Facebook OR Source: Google',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@unmatched-domain.net',
        'textPlain' => 'This is a lead from Source: Google',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('finds client by combined rule when both email and custom conditions match', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'combined_rule',
        'email' => 'leads@facebook.com',
        'custom_conditions' => 'rep: henry',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@facebook.com',
        'textPlain' => 'This is a lead for rep: henry',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('combined rule fails when email matches but custom conditions do not', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'combined_rule',
        'email' => 'leads@facebook.com',
        'custom_conditions' => 'rep: henry',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@facebook.com',
        'textPlain' => 'This is a lead for rep: jane', // Wrong rep
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->toBeNull();
});

test('combined rule fails when custom conditions match but email does not', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'combined_rule',
        'email' => 'leads@facebook.com',
        'custom_conditions' => 'rep: henry',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@zillow.com', // Wrong email
        'textPlain' => 'This is a lead for rep: henry',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->toBeNull();
});

test('combined rule works with domain matching and custom conditions', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'rule_type' => 'combined_rule',
        'email' => '@facebook.com',
        'custom_conditions' => 'property_type: commercial',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'noreply@facebook.com', // Domain match
        'textPlain' => 'This is a commercial lead: property_type: commercial',
        'subject' => 'New Lead'
    ];

    $client = $this->callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('can test email connection without throwing exceptions', function () {
    $processor = app(EmailLeadProcessor::class);

    $result = $processor->testEmailConnection();

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['connection_successful', 'error', 'total_emails', 'unread_emails', 'recent_emails', 'server_info']);
    expect($result['connection_successful'])->toBeBool();
});
