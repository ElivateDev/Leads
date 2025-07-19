<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
        'message',
        'from_email',
        'email_subject',
        'email_received_at',
        'status',
        'source',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Validation rule to ensure at least email or phone is provided
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|required_without:phone',
            'phone' => 'nullable|string|required_without:email',
            'message' => 'nullable|string',
            'status' => 'required|in:new,contacted,qualified,converted,lost',
            'from_email' => 'nullable|email',
            'source' => 'required|in:website,phone,referral,social,other',
            'client_id' => 'required|exists:clients,id',
        ];
    }
}
