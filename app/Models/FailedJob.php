<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];

    protected $casts = [
        'failed_at' => 'datetime',
        'payload' => 'array',
    ];

    public $timestamps = false;

    public function getJobNameAttribute()
    {
        $payload = $this->payload;
        if (isset($payload['displayName'])) {
            return $payload['displayName'];
        }

        if (isset($payload['job'])) {
            $job = $payload['job'];
            if (str_contains($job, 'SendQueuedNotifications')) {
                // Extract notification class from the payload
                if (isset($payload['data']['commandName'])) {
                    return $payload['data']['commandName'];
                }
            }
            return class_basename($job);
        }

        return 'Unknown Job';
    }

    public function getShortExceptionAttribute()
    {
        $lines = explode("\n", $this->exception);
        return $lines[0] ?? 'No exception message';
    }
}
