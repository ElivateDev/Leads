<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Notifications\NewLeadNotification;
use App\Services\EmailProcessingLogger;
use Illuminate\Support\Facades\Log;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
        'message',
        'notes',
        'from_email',
        'email_subject',
        'email_received_at',
        'status',
        'source',
        'campaign',
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
            'notes' => 'nullable|string',
            'status' => 'required|string',
            'from_email' => 'nullable|email',
            'source' => 'required|in:website,phone,referral,social,other',
            'campaign' => 'nullable|string|max:255',
            'client_id' => 'required|exists:clients,id',
        ];
    }

    /**
     * Get validation rules for a specific client
     */
    public static function rulesForClient($clientId): array
    {
        $rules = self::rules();

        $client = Client::find($clientId);
        if ($client) {
            $validStatuses = array_keys($client->getLeadDispositions());
            $rules['status'] = 'required|in:' . implode(',', $validStatuses);
        }

        return $rules;
    }
}
