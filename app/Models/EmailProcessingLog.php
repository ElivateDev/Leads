<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'from_address',
        'subject',
        'type', // 'email_received', 'rule_matched', 'rule_failed', 'lead_created', 'lead_duplicate', 'notification_sent', 'error'
        'status', // 'success', 'failed', 'skipped'
        'client_id',
        'lead_id',
        'rule_id',
        'rule_type',
        'message',
        'details', // JSON field for additional data
        'processed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'processed_at' => 'datetime',
    ];

    protected $dates = [
        'processed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the client associated with this log entry
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the lead associated with this log entry
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the distribution rule associated with this log entry
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ClientEmail::class, 'rule_id');
    }

    /**
     * Scope for email processing entries
     */
    public function scopeEmailProcessing($query)
    {
        return $query->where('type', 'email_received');
    }

    /**
     * Scope for rule processing entries
     */
    public function scopeRuleProcessing($query)
    {
        return $query->whereIn('type', ['rule_matched', 'rule_failed']);
    }

    /**
     * Scope for notifications
     */
    public function scopeNotifications($query)
    {
        return $query->where('type', 'notification_sent');
    }

    /**
     * Scope for errors
     */
    public function scopeErrors($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get formatted type for display
     */
    public function getFormattedTypeAttribute(): string
    {
        return match ($this->type) {
            'email_received' => 'Email Received',
            'rule_matched' => 'Rule Matched',
            'rule_failed' => 'Rule Failed',
            'lead_created' => 'Lead Created',
            'lead_duplicate' => 'Duplicate Lead',
            'notification_sent' => 'Notification Sent',
            'error' => 'Error',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get status color for display
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'skipped' => 'warning',
            default => 'gray',
        };
    }
}
