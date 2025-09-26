<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'campaign_name',
        'rule_type',
        'rule_value',
        'match_field',
        'is_active',
        'priority',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Check if this rule matches the given email content
     */
    public function matchesEmail($email): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $content = $this->getContentToMatch($email);

        if (empty($content)) {
            return false;
        }

        return match ($this->rule_type) {
            'contains' => str_contains(strtolower($content), strtolower($this->rule_value)),
            'exact' => strtolower($content) === strtolower($this->rule_value),
            'regex' => preg_match('/' . $this->rule_value . '/i', $content),
            'url_parameter' => $this->matchesUrlParameter($content),
            default => false,
        };
    }

    /**
     * Get the content to match against based on match_field
     */
    private function getContentToMatch($email): string
    {
        return match ($this->match_field) {
            'subject' => $email->subject ?? '',
            'from_email' => $email->fromAddress ?? '',
            'url' => $this->extractUrls($email->textPlain ?? ''),
            'body' => $email->textPlain ?? '',
            default => $email->textPlain ?? '',
        };
    }

    /**
     * Extract URLs from email content
     */
    private function extractUrls(string $content): string
    {
        preg_match_all('/(https?:\/\/[^\s]+)/i', $content, $matches);
        return implode(' ', $matches[0] ?? []);
    }

    /**
     * Match URL parameters (e.g., gad_campaignid=22820616890)
     */
    private function matchesUrlParameter(string $content): bool
    {
        $urls = $this->extractUrls($content);

        if (empty($urls)) {
            return false;
        }

        // Parse rule_value as parameter=value
        if (str_contains($this->rule_value, '=')) {
            [$param, $value] = explode('=', $this->rule_value, 2);
            $pattern = '/[?&]' . preg_quote($param) . '=' . preg_quote($value) . '(?:&|$|\s)/i';
        } else {
            // Just match parameter name
            $pattern = '/[?&]' . preg_quote($this->rule_value) . '=/i';
        }

        return preg_match($pattern, $urls);
    }

    /**
     * Get available rule types
     */
    public static function getRuleTypes(): array
    {
        return [
            'contains' => 'Contains Text',
            'exact' => 'Exact Match',
            'regex' => 'Regular Expression',
            'url_parameter' => 'URL Parameter',
        ];
    }

    /**
     * Get available match fields
     */
    public static function getMatchFields(): array
    {
        return [
            'body' => 'Email Body',
            'subject' => 'Email Subject',
            'url' => 'URLs in Email',
            'from_email' => 'From Email Address',
        ];
    }

    /**
     * Get count of leads that would match this rule
     */
    public function getMatchingLeadsCount(): int
    {
        if (!$this->is_active) {
            return 0;
        }

        $service = new \App\Services\CampaignRuleService();
        return $service->previewRuleApplication($this)->count();
    }
}
