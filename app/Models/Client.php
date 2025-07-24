<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'email_notifications',
        'lead_dispositions',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'lead_dispositions' => 'array',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function clientEmails(): HasMany
    {
        return $this->hasMany(ClientEmail::class);
    }

    /**
     * Get the default lead dispositions
     */
    public static function getDefaultDispositions(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
            'lost' => 'Lost'
        ];
    }

    /**
     * Get the client's lead dispositions or default ones
     */
    public function getLeadDispositions(): array
    {
        return $this->lead_dispositions ?? self::getDefaultDispositions();
    }

    /**
     * Set the client's lead dispositions
     */
    public function setLeadDispositions(array $dispositions): void
    {
        $this->lead_dispositions = $dispositions;
        $this->save();
    }
}
