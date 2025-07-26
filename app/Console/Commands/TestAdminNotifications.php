<?php

namespace App\Console\Commands;

use App\Services\AdminNotificationService;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Console\Command;

class TestAdminNotifications extends Command
{
    protected $signature = 'admin:test-notifications 
                          {--type=all : Notification type to test (all, email-processed, error, rules-not-matched, duplicate-lead)}
                          {--admin-email= : Send test only to specific admin email}';

    protected $description = 'Send test notifications to admin users to verify notification settings';

    public function handle()
    {
        $type = $this->option('type');
        $adminEmail = $this->option('admin-email');

        $this->info('🧪 Testing Admin Notifications');
        $this->newLine();

        // Get admin users
        $admins = User::where('role', 'admin');
        if ($adminEmail) {
            $admins = $admins->where('email', $adminEmail);
        }
        $admins = $admins->get();

        if ($admins->isEmpty()) {
            $this->error('No admin users found' . ($adminEmail ? " with email: $adminEmail" : ''));
            return 1;
        }

        $this->info('Testing notifications for ' . $admins->count() . ' admin user(s):');
        foreach ($admins as $admin) {
            $this->line('• ' . $admin->name . ' (' . $admin->email . ')');
        }
        $this->newLine();

        // Show current preferences
        $this->showCurrentPreferences($admins);

        if (!$this->confirm('Do you want to send test notifications?')) {
            $this->info('Test cancelled.');
            return 0;
        }

        // Send test notifications based on type
        match ($type) {
            'email-processed' => $this->testEmailProcessedNotification(),
            'error' => $this->testErrorNotification(),
            'rules-not-matched' => $this->testRulesNotMatchedNotification(),
            'duplicate-lead' => $this->testDuplicateLeadNotification(),
            'all' => $this->testAllNotifications(),
            default => $this->error("Invalid notification type: $type")
        };

        $this->newLine();
        $this->info('✅ Test notifications sent! Check your email inbox.');
        $this->info('💡 If you don\'t receive emails, check:');
        $this->line('   - Your notification preferences in Admin Settings');
        $this->line('   - SMTP configuration in .env file');
        $this->line('   - Email Processing Logs for delivery errors');

        return 0;
    }

    private function showCurrentPreferences($admins): void
    {
        $this->info('Current notification preferences:');
        $this->newLine();

        $headers = ['Admin', 'Email Processed', 'Errors', 'Rules Not Matched', 'Duplicate Leads'];
        $rows = [];

        foreach ($admins as $admin) {
            $rows[] = [
                $admin->name,
                $admin->getPreference('admin_notify_email_processed', false) ? '✅' : '❌',
                $admin->getPreference('admin_notify_errors', true) ? '✅' : '❌',
                $admin->getPreference('admin_notify_rules_not_matched', false) ? '✅' : '❌',
                $admin->getPreference('admin_notify_duplicate_leads', false) ? '✅' : '❌',
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function testEmailProcessedNotification(): void
    {
        $this->info('📧 Testing Email Processed notification...');
        
        AdminNotificationService::notifyEmailProcessed(
            'test-lead@example.com',
            'TEST: New Lead Submission',
            [
                [
                    'id' => 999,
                    'name' => 'Test Client Company',
                    'email' => 'test-client@example.com'
                ]
            ],
            [
                [
                    'id' => 999,
                    'name' => 'John Test Doe',
                    'client_name' => 'Test Client Company'
                ]
            ],
            'website',
            'Test Campaign'
        );
    }

    private function testErrorNotification(): void
    {
        $this->info('❌ Testing Error notification...');
        
        AdminNotificationService::notifyEmailError(
            'problematic-email@example.com',
            'TEST: Email Processing Failed',
            'This is a test error message to verify notification delivery',
            'test_error_notification',
            [
                'test_mode' => true,
                'error_code' => 'TEST001',
                'affected_system' => 'Email Processing',
                'severity' => 'info'
            ]
        );
    }

    private function testRulesNotMatchedNotification(): void
    {
        $this->info('🎯 Testing Rules Not Matched notification...');
        
        AdminNotificationService::notifyRuleNotMatched(
            'unknown-sender@newdomain.com',
            'TEST: Unmatched Email Rules',
            'newdomain.com',
            true,
            'Default Test Client'
        );
    }

    private function testDuplicateLeadNotification(): void
    {
        $this->info('👥 Testing Duplicate Lead notification...');
        
        // Create a mock lead for testing
        $mockLead = new Lead([
            'id' => 999,
            'name' => 'Test Duplicate Lead',
            'email' => 'duplicate@example.com',
            'phone' => '+1-555-TEST',
            'created_at' => now()->subHours(2)
        ]);
        
        AdminNotificationService::notifyDuplicateLead(
            'duplicate@example.com',
            'TEST: Duplicate Lead Detected',
            $mockLead,
            'Test Client Company',
            [
                'duplicate_detection_method' => 'test_notification',
                'phone_number' => '+1-555-TEST',
                'lead_name' => 'Test Duplicate Lead',
                'lead_email' => 'duplicate@example.com',
                'time_since_original' => '2 hours ago',
                'test_mode' => true
            ]
        );
    }

    private function testAllNotifications(): void
    {
        $this->info('🌟 Testing all notification types...');
        $this->newLine();
        
        $this->testEmailProcessedNotification();
        sleep(1); // Small delay between notifications
        
        $this->testErrorNotification();
        sleep(1);
        
        $this->testRulesNotMatchedNotification();
        sleep(1);
        
        $this->testDuplicateLeadNotification();
    }
}
