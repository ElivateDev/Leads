<?php

namespace App\Services;

use App\Models\EmailProcessingLog;
use App\Models\Client;
use App\Models\Lead;
use App\Models\ClientEmail;
use Carbon\Carbon;

class EmailProcessingLogger
{
    /**
     * Log when an email is received and being processed
     */
    public static function logEmailReceived(
        string $emailId,
        string $fromAddress,
        ?string $subject = null,
        array $details = []
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'email_id' => $emailId,
            'from_address' => $fromAddress,
            'subject' => $subject,
            'type' => 'email_received',
            'status' => 'success',
            'message' => "Email received from {$fromAddress}",
            'details' => $details,
            'processed_at' => now(),
        ]);
    }

    /**
     * Log when a distribution rule matches an email
     */
    public static function logRuleMatched(
        string $fromAddress,
        ClientEmail $rule,
        ?Client $client = null,
        array $details = []
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => 'rule_matched',
            'status' => 'success',
            'client_id' => $client?->id ?? $rule->client_id,
            'rule_id' => $rule->id,
            'rule_type' => $rule->rule_type,
            'message' => "Distribution rule matched: {$rule->rule_type} - " . ($rule->email ?? $rule->custom_conditions),
            'details' => array_merge([
                'rule_email' => $rule->email,
                'rule_conditions' => $rule->custom_conditions,
                'rule_description' => $rule->description,
            ], $details),
            'processed_at' => now(),
        ]);
    }

    /**
     * Log when a distribution rule fails to match
     */
    public static function logRuleFailed(
        string $fromAddress,
        ClientEmail $rule,
        string $reason,
        array $details = []
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => 'rule_failed',
            'status' => 'failed',
            'rule_id' => $rule->id,
            'rule_type' => $rule->rule_type,
            'message' => "Distribution rule failed: {$reason}",
            'details' => array_merge([
                'rule_email' => $rule->email,
                'rule_conditions' => $rule->custom_conditions,
                'failure_reason' => $reason,
            ], $details),
            'processed_at' => now(),
        ]);
    }

    /**
     * Log when a lead is successfully created
     */
    public static function logLeadCreated(
        string $fromAddress,
        Lead $lead,
        ?ClientEmail $rule = null,
        array $details = []
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => 'lead_created',
            'status' => 'success',
            'client_id' => $lead->client_id,
            'lead_id' => $lead->id,
            'rule_id' => $rule?->id,
            'rule_type' => $rule?->rule_type,
            'message' => "Lead created successfully: {$lead->name} ({$lead->email})",
            'details' => array_merge([
                'lead_name' => $lead->name,
                'lead_email' => $lead->email,
                'lead_phone' => $lead->phone,
                'lead_source' => $lead->source,
            ], $details),
            'processed_at' => now(),
        ]);
    }

    /**
     * Log when a duplicate lead is detected
     */
    public static function logLeadDuplicate(
        string $fromAddress,
        ?Client $client = null,
        ?string $reason = null,
        array $details = [],
        ?Lead $existingLead = null
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => 'lead_duplicate',
            'status' => 'skipped',
            'client_id' => $client?->id,
            'lead_id' => $existingLead?->id,
            'message' => $reason ?? "Duplicate lead detected for {$fromAddress}",
            'details' => $details,
            'processed_at' => now(),
        ]);
    }

    /**
     * Log when a notification is sent
     */
    public static function logNotificationSent(
        string $fromAddress,
        string $notificationType,
        ?Lead $lead = null,
        ?Client $client = null,
        array $details = []
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => 'notification_sent',
            'status' => 'success',
            'client_id' => $client?->id ?? $lead?->client_id,
            'lead_id' => $lead?->id,
            'message' => "Notification sent: {$notificationType}",
            'details' => array_merge([
                'notification_type' => $notificationType,
            ], $details),
            'processed_at' => now(),
        ]);
    }

    /**
     * Log an error during email processing
     */
    public static function logError(
        string $fromAddress,
        string $errorMessage,
        ?\Throwable $exception = null,
        array $details = []
    ): EmailProcessingLog {
        $logDetails = $details;

        if ($exception) {
            $logDetails = array_merge($logDetails, [
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
                'exception_trace' => $exception->getTraceAsString(),
            ]);
        }

        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => 'error',
            'status' => 'failed',
            'message' => $errorMessage,
            'details' => $logDetails,
            'processed_at' => now(),
        ]);
    }

    /**
     * Log a general processing event
     */
    public static function logEvent(
        string $fromAddress,
        string $type,
        string $status,
        string $message,
        array $details = []
    ): EmailProcessingLog {
        return EmailProcessingLog::create([
            'from_address' => $fromAddress,
            'type' => $type,
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'processed_at' => now(),
        ]);
    }
}
