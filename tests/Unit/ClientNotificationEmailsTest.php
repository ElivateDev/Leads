<?php

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('client can have multiple notification emails', function () {
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'primary@example.com',
        'notification_emails' => ['notify1@example.com', 'notify2@example.com'],
    ]);

    expect($client->getNotificationEmails())->toEqual([
        'notify1@example.com',
        'notify2@example.com'
    ]);
});

test('client falls back to primary email when no notification emails set', function () {
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'primary@example.com',
        'notification_emails' => null,
    ]);

    expect($client->getNotificationEmails())->toEqual(['primary@example.com']);
});

test('client returns empty array when no primary email and no notification emails', function () {
    // Skip this test since email is required in the database
    // Just test the method behavior when email is empty but valid record exists
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'primary@example.com',
        'notification_emails' => null,
    ]);
    
    // Temporarily set email to null to test the method
    $client->email = null;
    
    expect($client->getNotificationEmails())->toEqual([]);
});

test('client can set notification emails', function () {
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'primary@example.com',
    ]);

    $client->setNotificationEmails(['team@example.com', 'manager@example.com']);

    expect($client->fresh()->notification_emails)->toEqual([
        'team@example.com',
        'manager@example.com'
    ]);
});

test('setting notification emails filters out empty values', function () {
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'primary@example.com',
    ]);

    $client->setNotificationEmails(['team@example.com', '', 'manager@example.com', null]);

    expect($client->fresh()->notification_emails)->toEqual([
        'team@example.com',
        'manager@example.com'
    ]);
});
