<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'rule_type',
        'email',
        'custom_conditions',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function matchesEmail($email): bool
    {
        if ($this->rule_type === 'email_match') {
            return $this->matchesEmailAddress($email);
        } elseif ($this->rule_type === 'custom_rule') {
            return $this->matchesCustomConditions($email);
        } elseif ($this->rule_type === 'combined_rule') {
            return $this->matchesEmailAddress($email) && $this->matchesCustomConditions($email);
        }

        return false;
    }

    private function matchesEmailAddress($email): bool
    {
        $fromEmail = $email->fromAddress ?? '';

        if ($this->email === $fromEmail) {
            return true;
        }

        if (str_starts_with($this->email, '@')) {
            $domain = substr($this->email, 1);
            return str_ends_with($fromEmail, '@' . $domain);
        }

        return false;
    }

    private function matchesCustomConditions($email): bool
    {
        if (empty($this->custom_conditions)) {
            return false;
        }

        $content = ($email->textPlain ?? '') . ' ' . ($email->subject ?? '');
        $content = strtolower($content);

        $conditions = $this->custom_conditions;
        $conditions = strtolower($conditions);

        if (str_contains($conditions, ' and ')) {
            $parts = explode(' and ', $conditions);
            foreach ($parts as $part) {
                if (!$this->evaluateCondition(trim($part), $content)) {
                    return false;
                }
            }
            return true;
        } elseif (str_contains($conditions, ' or ')) {
            $parts = explode(' or ', $conditions);
            foreach ($parts as $part) {
                if ($this->evaluateCondition(trim($part), $content)) {
                    return true;
                }
            }
            return false;
        } else {
            return $this->evaluateCondition($conditions, $content);
        }
    }

    private function evaluateCondition(string $condition, string $content): bool
    {
        return str_contains($content, $condition);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
