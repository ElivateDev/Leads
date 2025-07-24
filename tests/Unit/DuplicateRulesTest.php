<?php

use App\Services\EmailLeadProcessor;
use App\Models\Lead;
use App\Models\Client;
use App\Models\ClientEmail;
use App\Models\EmailProcessingLog;
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

    $this->mock(\PhpImap\Mailbox::class);
});

test('processes all matching rules when multiple exact email matches exist', function () {
    // Create two clients
    $client1 = Client::factory()->create(['name' => 'Client One']);
    $client2 = Client::factory()->create(['name' => 'Client Two']);

    // Create duplicate email rules
    $rule1 = ClientEmail::factory()->create([
        'client_id' => $client1->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    $rule2 = ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@example.com',
        'fromName' => 'Test Sender',
        'subject' => 'Test Lead',
        'textPlain' => 'This is a test lead message'
    ];

    // Use reflection to test the new method
    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('findAllMatchingClients');
    $method->setAccessible(true);

    $matchingClients = $method->invoke($processor, $mockEmail);

    // Should return both clients
    expect($matchingClients)->toHaveCount(2);
    expect($matchingClients->pluck('id')->sort()->values()->toArray())->toEqual([$client1->id, $client2->id]);
});

test('processes all matching rules when multiple domain matches exist', function () {
    $client1 = Client::factory()->create(['name' => 'Client One']);
    $client2 = Client::factory()->create(['name' => 'Client Two']);

    // Create duplicate domain rules
    $rule1 = ClientEmail::factory()->create([
        'client_id' => $client1->id,
        'rule_type' => 'email_match',
        'email' => '@testdomain.com',
        'is_active' => true,
    ]);

    $rule2 = ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'rule_type' => 'email_match',
        'email' => '@testdomain.com',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'contact@testdomain.com',
        'fromName' => 'Test Sender',
        'subject' => 'Test Lead',
        'textPlain' => 'This is a test lead message'
    ];

    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('findAllMatchingClients');
    $method->setAccessible(true);

    $matchingClients = $method->invoke($processor, $mockEmail);

    expect($matchingClients)->toHaveCount(2);
    expect($matchingClients->pluck('id')->sort()->values()->toArray())->toEqual([$client1->id, $client2->id]);
});

test('processes all matching combined rules with same email and conditions', function () {
    $client1 = Client::factory()->create(['name' => 'Client One']);
    $client2 = Client::factory()->create(['name' => 'Client Two']);

    // Create identical combined rules
    $rule1 = ClientEmail::factory()->create([
        'client_id' => $client1->id,
        'rule_type' => 'combined_rule',
        'email' => 'leads@platform.com',
        'custom_conditions' => 'source: facebook',
        'is_active' => true,
    ]);

    $rule2 = ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'rule_type' => 'combined_rule',
        'email' => 'leads@platform.com',
        'custom_conditions' => 'source: facebook',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@platform.com',
        'fromName' => 'Platform Leads',
        'subject' => 'New Lead',
        'textPlain' => 'This is a lead from source: facebook'
    ];

    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('findAllMatchingClients');
    $method->setAccessible(true);

    $matchingClients = $method->invoke($processor, $mockEmail);

    expect($matchingClients)->toHaveCount(2);
    expect($matchingClients->pluck('id')->sort()->values()->toArray())->toEqual([$client1->id, $client2->id]);
});

test('creates leads for all matching clients when duplicates exist', function () {
    $client1 = Client::factory()->create(['name' => 'Client One']);
    $client2 = Client::factory()->create(['name' => 'Client Two']);

    // Create duplicate rules
    ClientEmail::factory()->create([
        'client_id' => $client1->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@example.com',
        'fromName' => 'John Doe',
        'subject' => 'Test Lead',
        'textPlain' => 'This is a test lead message from John Doe. Contact: john@example.com Phone: 555-1234',
        'date' => now()->format('r')
    ];

    // Use reflection to test the processEmail method
    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('processEmail');
    $method->setAccessible(true);

    $leads = $method->invoke($processor, $mockEmail);

    // Should create leads for both clients
    expect($leads)->toBeArray();
    expect($leads)->toHaveCount(2);

    // Verify leads were created in database
    expect(Lead::count())->toBe(2);
    expect(Lead::where('client_id', $client1->id)->count())->toBe(1);
    expect(Lead::where('client_id', $client2->id)->count())->toBe(1);
});

test('logs rule matches for all matching rules', function () {
    $client1 = Client::factory()->create(['name' => 'Client One']);
    $client2 = Client::factory()->create(['name' => 'Client Two']);

    ClientEmail::factory()->create([
        'client_id' => $client1->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@example.com',
        'fromName' => 'John Doe',
        'subject' => 'Test Lead',
        'textPlain' => 'This is a test lead message from John Doe.',
        'date' => now()->format('r')
    ];

    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('processEmail');
    $method->setAccessible(true);

    $method->invoke($processor, $mockEmail);

    // Should have rule_matched logs for both clients
    $ruleLogs = EmailProcessingLog::where('type', 'rule_matched')->get();
    expect($ruleLogs)->toHaveCount(2);

    $clientIds = $ruleLogs->pluck('client_id')->sort()->values()->toArray();
    expect($clientIds)->toEqual([$client1->id, $client2->id]);
});

test('handles mixed rule types with overlapping matches', function () {
    $client1 = Client::factory()->create(['name' => 'Client One']);
    $client2 = Client::factory()->create(['name' => 'Client Two']);
    $client3 = Client::factory()->create(['name' => 'Client Three']);

    // Exact email match
    ClientEmail::factory()->create([
        'client_id' => $client1->id,
        'rule_type' => 'email_match',
        'email' => 'leads@example.com',
        'is_active' => true,
    ]);

    // Domain match for same domain
    ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'rule_type' => 'email_match',
        'email' => '@example.com',
        'is_active' => true,
    ]);

    // Combined rule with same email and matching condition
    ClientEmail::factory()->create([
        'client_id' => $client3->id,
        'rule_type' => 'combined_rule',
        'email' => 'leads@example.com',
        'custom_conditions' => 'urgent: true',
        'is_active' => true,
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@example.com',
        'fromName' => 'John Doe',
        'subject' => 'Urgent Request',
        'textPlain' => 'This is urgent: true and needs immediate attention.',
        'date' => now()->format('r')
    ];

    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('findAllMatchingClients');
    $method->setAccessible(true);

    $matchingClients = $method->invoke($processor, $mockEmail);

    // All three rules should match
    expect($matchingClients)->toHaveCount(3);
    expect($matchingClients->pluck('id')->sort()->values()->toArray())->toEqual([$client1->id, $client2->id, $client3->id]);
});
