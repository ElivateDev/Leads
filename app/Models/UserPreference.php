<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a preference value for a user
     */
    public static function getValue(int $userId, string $key, mixed $default = null): mixed
    {
        $preference = static::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $preference ? $preference->value : $default;
    }

    /**
     * Set a preference value for a user
     */
    public static function setValue(int $userId, string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
