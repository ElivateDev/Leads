<?php

use App\Services\EmailLeadProcessor;
use App\Models\Lead;
use App\Models\Client;
use App\Models\ClientEmail;
use App\Models\EmailProcessingLog;
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

    // Create a client email rule for processing
    $this->clientEmail = ClientEmail::factory()->create([
        'client_id' => $this->client->id,
        'email' => 'leads@testclient.com',
        'rule_type' => 'email_match',
        'is_active' => true,
    ]);

    $this->mock(\PhpImap\Mailbox::class);
});

test('creates new lead when no duplicate exists', function () {
    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'John Doe',
        'subject' => 'New Lead Inquiry',
        'textPlain' => 'Name: John Doe
Email: john.doe@example.com
Phone: 555-123-4567
Message: I am interested in your services.',
        'date' => '2025-07-26 10:00:00',
    ];

    // Process the email
    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should create one new lead
    expect($leads)->toHaveCount(1);
    expect($leads[0])->toBeInstanceOf(Lead::class);

    // Verify lead details
    $lead = $leads[0];
    expect($lead->name)->toBe('John Doe');
    expect($lead->email)->toBe('john.doe@example.com');
    expect($lead->phone)->toBe('5551234567');
    expect($lead->client_id)->toBe($this->client->id);
    expect($lead->status)->toBe('new');
    expect($lead->message)->toContain('I am interested in your services');

    // Verify database state
    expect(Lead::count())->toBe(1);

    // Verify logging
    $leadCreatedLog = EmailProcessingLog::where('type', 'lead_created')->first();
    expect($leadCreatedLog)->not->toBeNull();
    expect($leadCreatedLog->lead_id)->toBe($lead->id);
    expect($leadCreatedLog->client_id)->toBe($this->client->id);
    expect($leadCreatedLog->status)->toBe('success');
});

test('appends message to existing lead when duplicate detected', function () {
    // Create an existing lead
    $existingLead = Lead::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '5551234567',
        'message' => 'Original message from first submission.',
        'notes' => 'Initial notes',
        'status' => 'new',
        'source' => 'website',
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'John Doe',
        'subject' => 'Follow-up Inquiry',
        'textPlain' => 'Name: John Doe
Email: john.doe@example.com
Phone: 555-123-4567
Message: This is my second inquiry with additional details.',
        'date' => '2025-07-26 11:00:00',
    ];

    // Process the email
    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should return the updated existing lead
    expect($leads)->toHaveCount(1);
    expect($leads[0]->id)->toBe($existingLead->id);

    // Verify lead was updated, not created
    expect(Lead::count())->toBe(1);

    // Refresh the lead from database
    $updatedLead = Lead::find($existingLead->id);

    // Verify message was appended
    expect($updatedLead->message)->toContain('Original message from first submission');
    expect($updatedLead->message)->toContain('--- DUPLICATE SUBMISSION DETECTED ---');
    expect($updatedLead->message)->toContain('From: leads@testclient.com');
    expect($updatedLead->message)->toContain('Subject: Follow-up Inquiry');
    expect($updatedLead->message)->toContain('This is my second inquiry with additional details');

    // Verify notes were updated
    expect($updatedLead->notes)->toContain('Initial notes');
    expect($updatedLead->notes)->toContain('Duplicate submission received on');

    // Verify email_received_at was updated
    expect($updatedLead->email_received_at)->not->toBe($existingLead->email_received_at);

    // Verify duplicate logging
    $duplicateLog = EmailProcessingLog::where('type', 'lead_duplicate')->first();
    expect($duplicateLog)->not->toBeNull();
    expect($duplicateLog->lead_id)->toBe($existingLead->id);
    expect($duplicateLog->client_id)->toBe($this->client->id);
    expect($duplicateLog->status)->toBe('skipped');
    expect($duplicateLog->message)->toContain('Duplicate lead detected - message appended to existing lead');
    expect($duplicateLog->details)->toHaveKey('existing_lead_id');
    expect($duplicateLog->details['existing_lead_id'])->toBe($existingLead->id);
    expect($duplicateLog->details['lead_name'])->toBe('John Doe');
    expect($duplicateLog->details['lead_email'])->toBe('john.doe@example.com');
});

test('creates new lead when name differs even with same email and phone', function () {
    // Create an existing lead
    Lead::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '5551234567',
        'status' => 'new',
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'Johnny Doe', // Different name
        'subject' => 'New Inquiry',
        'textPlain' => 'Name: Johnny Doe
Email: john.doe@example.com
Phone: 555-123-4567
Message: This is from a different person.',
        'date' => '2025-07-26 11:00:00',
    ];

    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should create a new lead since name is different
    expect($leads)->toHaveCount(1);
    expect(Lead::count())->toBe(2);

    $newLead = $leads[0];
    expect($newLead->name)->toBe('Johnny Doe');
    expect($newLead->email)->toBe('john.doe@example.com');

    // Should not have duplicate log since it's considered a new lead
    expect(EmailProcessingLog::where('type', 'lead_duplicate')->count())->toBe(0);
    expect(EmailProcessingLog::where('type', 'lead_created')->count())->toBe(1);
});

test('creates new lead when email differs even with same name and phone', function () {
    // Create an existing lead
    Lead::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '5551234567',
        'status' => 'new',
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'John Doe',
        'subject' => 'New Inquiry',
        'textPlain' => 'Name: John Doe
Email: john.d@example.com
Phone: 555-123-4567
Message: This is from a different email.',
        'date' => '2025-07-26 11:00:00',
    ];

    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should create a new lead since email is different
    expect($leads)->toHaveCount(1);
    expect(Lead::count())->toBe(2);

    $newLead = $leads[0];
    expect($newLead->name)->toBe('John Doe');
    expect($newLead->email)->toBe('john.d@example.com');

    // Should not have duplicate log
    expect(EmailProcessingLog::where('type', 'lead_duplicate')->count())->toBe(0);
});

test('creates new lead when phone differs even with same name and email', function () {
    // Create an existing lead
    Lead::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '5551234567',
        'status' => 'new',
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'John Doe',
        'subject' => 'New Inquiry',
        'textPlain' => 'Name: John Doe
Email: john.doe@example.com
Phone: 555-987-6543
Message: This is from a different phone.',
        'date' => '2025-07-26 11:00:00',
    ];

    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should create a new lead since phone is different
    expect($leads)->toHaveCount(1);
    expect(Lead::count())->toBe(2);

    $newLead = $leads[0];
    expect($newLead->name)->toBe('John Doe');
    expect($newLead->phone)->toBe('5559876543');

    // Should not have duplicate log
    expect(EmailProcessingLog::where('type', 'lead_duplicate')->count())->toBe(0);
});

test('handles leads with null phone numbers correctly for duplicates', function () {
    // Create an existing lead with no phone
    $existingLead = Lead::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'Jane Smith',
        'email' => 'jane.smith@example.com',
        'phone' => null,
        'status' => 'new',
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'Jane Smith',
        'subject' => 'Follow-up',
        'textPlain' => 'Name: Jane Smith
Email: jane.smith@example.com
Message: This is a follow-up without phone.',
        'date' => '2025-07-26 11:00:00',
    ];

    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should detect as duplicate and update existing lead
    expect($leads)->toHaveCount(1);
    expect($leads[0]->id)->toBe($existingLead->id);
    expect(Lead::count())->toBe(1);

    // Verify duplicate was logged
    expect(EmailProcessingLog::where('type', 'lead_duplicate')->count())->toBe(1);
});

test('creates separate leads for different clients with same contact info', function () {
    // Create a second client
    $client2 = Client::factory()->create([
        'name' => 'Second Client',
        'email' => 'client2@example.com',
    ]);

    // Create client email rules for both clients with same email
    ClientEmail::factory()->create([
        'client_id' => $client2->id,
        'email' => 'leads@testclient.com',
        'rule_type' => 'email_match',
        'is_active' => true,
    ]);

    // Create existing lead for first client
    Lead::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '5551234567',
        'status' => 'new',
    ]);

    $processor = app(EmailLeadProcessor::class);

    $mockEmail = (object) [
        'fromAddress' => 'leads@testclient.com',
        'fromName' => 'John Doe',
        'subject' => 'Inquiry',
        'textPlain' => 'Name: John Doe
Email: john.doe@example.com
Phone: 555-123-4567
Message: Same contact info, different clients.',
        'date' => '2025-07-26 11:00:00',
    ];

    $leads = $this->callPrivateMethod($processor, 'processEmail', $mockEmail);

    // Should create leads for both clients - one duplicate update, one new
    expect($leads)->toHaveCount(2);
    expect(Lead::count())->toBe(2);

    // One should be duplicate, one should be new
    $duplicateLog = EmailProcessingLog::where('type', 'lead_duplicate');
    $createdLog = EmailProcessingLog::where('type', 'lead_created');

    expect($duplicateLog->count())->toBe(1);
    expect($createdLog->count())->toBe(1);
});
