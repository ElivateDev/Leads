<?php

use App\Console\Commands\ProcessEmailLeads;
use App\Services\EmailLeadProcessor;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'client@example.com',
        'email_notifications' => true,
    ]);
});

test('command runs successfully when no emails are found', function () {
    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $mockProcessor->shouldReceive('processNewEmails')
        ->once()
        ->andReturn([]);

    $this->artisan('leads:process-emails')
        ->expectsOutput('Starting email lead processing...')
        ->expectsOutput('No new leads found in email inbox.')
        ->assertExitCode(0);
});

test('command processes leads successfully', function () {
    $lead1 = Lead::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'client_id' => $this->client->id,
    ]);

    $lead2 = Lead::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'client_id' => $this->client->id,
    ]);

    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $mockProcessor->shouldReceive('processNewEmails')
        ->once()
        ->andReturn([$lead1, $lead2]);

    $this->artisan('leads:process-emails')
        ->expectsOutput('Starting email lead processing...')
        ->expectsOutput('Processed 2 new leads:')
        ->expectsOutput("- John Doe (john@example.com) - Client: {$this->client->name}")
        ->expectsOutput("- Jane Smith (jane@example.com) - Client: {$this->client->name}")
        ->assertExitCode(0);
});

test('command handles exceptions gracefully', function () {
    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $mockProcessor->shouldReceive('processNewEmails')
        ->once()
        ->andThrow(new Exception('IMAP connection failed'));

    $this->artisan('leads:process-emails')
        ->expectsOutput('Starting email lead processing...')
        ->expectsOutput('Error processing emails: IMAP connection failed')
        ->assertExitCode(1);
});

test('command accepts limit option', function () {
    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $mockProcessor->shouldReceive('processNewEmails')
        ->once()
        ->andReturn([]);

    $this->artisan('leads:process-emails', ['--limit' => 25])
        ->expectsOutput('Starting email lead processing...')
        ->expectsOutput('No new leads found in email inbox.')
        ->assertExitCode(0);
});

test('command can be called programmatically', function () {
    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $mockProcessor->shouldReceive('processNewEmails')
        ->once()
        ->andReturn([]);

    $exitCode = Artisan::call('leads:process-emails');

    expect($exitCode)->toBe(0);
});

test('command shows progress for large number of leads', function () {
    $leads = [];
    for ($i = 1; $i <= 5; $i++) {
        $leads[] = Lead::factory()->create([
            'name' => "Lead {$i}",
            'email' => "lead{$i}@example.com",
            'client_id' => $this->client->id,
        ]);
    }

    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $mockProcessor->shouldReceive('processNewEmails')
        ->once()
        ->andReturn($leads);

    $result = $this->artisan('leads:process-emails')
        ->expectsOutput('Starting email lead processing...')
        ->expectsOutput('Processed 5 new leads:');

    foreach ($leads as $lead) {
        $result->expectsOutput("- {$lead->name} ({$lead->email}) - Client: {$this->client->name}");
    }

    $result->assertExitCode(0);
});
