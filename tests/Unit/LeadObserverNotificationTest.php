<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadObserverNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_sends_notifications_to_all_configured_notification_emails_when_lead_is_created()
    {
        // Fake notifications to capture what gets sent
        Notification::fake();
        
        // Create a client with multiple notification emails
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'primary@example.com',
            'email_notifications' => true,
            'notification_emails' => [
                'notify1@example.com',
                'notify2@example.com',
                'manager@example.com'
            ],
        ]);
        
        // Create a lead for this client (this should trigger the observer)
        $lead = Lead::factory()->create([
            'client_id' => $client->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test lead message',
        ]);
        
        // Verify that notifications were sent (one for each email address)
        Notification::assertSentTimes(NewLeadNotification::class, 3);
    }

    public function test_observer_falls_back_to_primary_email_when_no_notification_emails_configured()
    {
        // Fake notifications to capture what gets sent
        Notification::fake();
        
        // Create a client with no notification emails (should fall back to primary)
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'primary@example.com',
            'email_notifications' => true,
            'notification_emails' => null,
        ]);
        
        // Create a lead for this client
        $lead = Lead::factory()->create([
            'client_id' => $client->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test lead message',
        ]);
        
        // Verify that notification was sent to primary email only
        Notification::assertSentTimes(NewLeadNotification::class, 1);
    }

    public function test_observer_does_not_send_notifications_when_email_notifications_are_disabled()
    {
        // Fake notifications to capture what gets sent
        Notification::fake();
        
        // Create a client with notifications disabled
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'primary@example.com',
            'email_notifications' => false,
            'notification_emails' => ['notify1@example.com', 'notify2@example.com'],
        ]);
        
        // Create a lead for this client
        Lead::factory()->create([
            'client_id' => $client->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test lead message',
        ]);
        
        // Verify that no notifications were sent
        Notification::assertNothingSent();
    }

    public function test_observer_handles_invalid_email_addresses_gracefully()
    {
        // Fake notifications to capture what gets sent
        Notification::fake();
        
        // Create a client with mix of valid and invalid emails
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'primary@example.com',
            'email_notifications' => true,
            'notification_emails' => [
                'valid@example.com',
                'invalid-email',  // Invalid email
                'also-valid@example.com',
                '',  // Empty email
            ],
        ]);
        
        // Create a lead for this client
        $lead = Lead::factory()->create([
            'client_id' => $client->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test lead message',
        ]);
        
        // Should only send to valid emails (2 notifications)
        Notification::assertSentTimes(NewLeadNotification::class, 2);
    }

    public function test_observer_processes_empty_notification_emails_array_correctly()
    {
        // Fake notifications to capture what gets sent
        Notification::fake();
        
        // Create a client with empty notification emails array
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'primary@example.com',
            'email_notifications' => true,
            'notification_emails' => [],
        ]);
        
        // Create a lead for this client
        $lead = Lead::factory()->create([
            'client_id' => $client->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test lead message',
        ]);
        
        // Should fall back to primary email
        Notification::assertSentTimes(NewLeadNotification::class, 1);
    }
}
