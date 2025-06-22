<?php

use App\Services\EmailLeadProcessor;
use App\Models\Lead;
use App\Models\Client;
use App\Models\ClientEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(Tests\TestCase::class, RefreshDatabase::class);

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
        'email' => 'info@testclient.com',
        'is_active' => true,
    ]);

    $this->mock(\PhpImap\Mailbox::class);
});

function callPrivateMethod($object, $methodName, ...$args)
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    return $method->invoke($object, ...$args);
}

test('service configuration can be set up', function () {
    expect(config('services.imap.host'))->toBe('test.example.com');
    expect(config('services.imap.port'))->toBe(993);
});

test('finds client by exact email match', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'info@testclient.com'
    ];

    $client = callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('finds client by domain match', function () {
    ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'email' => '@testclient.com',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'sales@testclient.com'
    ];

    $client = callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->not->toBeNull();
    expect($client->id)->toBe($this->client->id);
});

test('returns null when no client match found', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'unknown@unknown.com'
    ];

    $client = callPrivateMethod($processor, 'findClientForEmail', $mockEmail);
    expect($client)->toBeNull();
});

test('gets default client from environment', function () {
    $processor = app(EmailLeadProcessor::class);

    putenv("DEFAULT_CLIENT_ID={$this->client->id}");

    $defaultClient = callPrivateMethod($processor, 'getDefaultClient');
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

    $exists = callPrivateMethod($processor, 'leadExists', 'existing@example.com', null, $this->client->id);
    expect($exists)->toBeTrue();
});

test('detects existing lead by phone', function () {
    $processor = app(EmailLeadProcessor::class);

    Lead::factory()->create([
        'phone' => '555-123-4567',
        'email' => null,
        'client_id' => $this->client->id,
    ]);

    $exists = callPrivateMethod($processor, 'leadExists', '', '555-123-4567', $this->client->id);
    expect($exists)->toBeTrue();
});

test('returns false for non-existing lead', function () {
    $processor = app(EmailLeadProcessor::class);

    $exists = callPrivateMethod($processor, 'leadExists', 'new@example.com', '555-999-8888', $this->client->id);
    expect($exists)->toBeFalse();
});

test('extracts name from email with name field', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Name: John Doe\nEmail: john@example.com\nMessage: Hello there',
        'fromName' => 'Contact Form',
        'fromAddress' => 'noreply@example.com'
    ];

    $name = callPrivateMethod($processor, 'extractNameFromEmail', $mockEmail);
    expect($name)->toContain('John Doe');
});

test('extracts name from fromName when no name field found', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Just a regular message without name field',
        'fromName' => 'Jane Smith',
        'fromAddress' => 'jane@example.com'
    ];

    $name = callPrivateMethod($processor, 'extractNameFromEmail', $mockEmail);
    expect($name)->toBe('Jane Smith');
});

test('ignores emails from automated sources', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'noreply@example.com',
        'subject' => 'Automated notification'
    ];

    $shouldIgnore = callPrivateMethod($processor, 'shouldIgnoreEmail', $mockEmail);
    expect($shouldIgnore)->toBeTrue();
});

test('does not ignore valid lead emails', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'customer@example.com',
        'subject' => 'Contact inquiry'
    ];

    $shouldIgnore = callPrivateMethod($processor, 'shouldIgnoreEmail', $mockEmail);
    expect($shouldIgnore)->toBeFalse();
});

test('extracts phone number from email content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Contact me at (555) 123-4567 or email me.',
        'textHtml' => null
    ];

    $phone = callPrivateMethod($processor, 'extractPhoneNumber', $mockEmail);
    expect($phone)->toContain('555')->and($phone)->toContain('123')->and($phone)->toContain('4567');
});

test('returns null when no phone number found', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Just a message without phone number',
        'textHtml' => null
    ];

    $phone = callPrivateMethod($processor, 'extractPhoneNumber', $mockEmail);
    expect($phone)->toBeNull();
});

test('determines lead source from email content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'subject' => 'Website contact form submission',
        'fromAddress' => 'noreply@website.com'
    ];

    $source = callPrivateMethod($processor, 'determineLeadSource', $mockEmail);
    expect($source)->toBe('website');
});

test('determines social media source', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'subject' => 'Notification from Facebook',
        'fromAddress' => 'noreply@facebook.com'
    ];

    $source = callPrivateMethod($processor, 'determineLeadSource', $mockEmail);
    expect($source)->toBe('social');
});

test('extracts email address from email content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'Please contact me at customer@domain.com for more info.',
        'fromAddress' => 'noreply@website.com'
    ];

    $email = callPrivateMethod($processor, 'extractEmailAddressFromEmail', $mockEmail);
    expect($email)->toBe('customer@domain.com');
});

test('falls back to sender email when no email in content', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'textPlain' => 'No email address in this content.',
        'fromAddress' => 'sender@example.com'
    ];

    $email = callPrivateMethod($processor, 'extractEmailAddressFromEmail', $mockEmail);
    expect($email)->toBe('sender@example.com');
});
