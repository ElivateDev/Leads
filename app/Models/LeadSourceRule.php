<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadSourceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'source_name',
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
            'domain' => $this->matchesDomain($content),
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
            'from_domain' => $this->getDomainFromEmail($email->fromAddress ?? ''),
            'url' => $this->extractUrls($email->textPlain ?? ''),
            'body' => $email->textPlain ?? '',
            default => $email->textPlain ?? '',
        };
    }

    /**
     * Extract domain from email address
     */
    private function getDomainFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        return isset($parts[1]) ? strtolower($parts[1]) : '';
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
     * Match URL parameters (e.g., utm_source=facebook)
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
     * Match email domain (e.g., facebook.com, instagram.com)
     */
    private function matchesDomain(string $content): bool
    {
        $domain = strtolower($content);
        $ruleDomain = strtolower($this->rule_value);

        // Exact domain match or subdomain match
        return $domain === $ruleDomain || str_ends_with($domain, '.' . $ruleDomain);
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
            'domain' => 'Domain Match',
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
            'from_domain' => 'From Email Domain',
        ];
    }

    /**
     * Get available lead sources (matching Lead model validation)
     */
    public static function getLeadSources(): array
    {
        return [
            'website' => 'Website',
            'phone' => 'Phone',
            'referral' => 'Referral',
            'social' => 'Social Media',
            'other' => 'Other',
        ];
    }

    /**
     * Validate that source_name is a valid lead source
     */
    public static function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'source_name' => 'required|in:website,phone,referral,social,other',
            'rule_type' => 'required|in:contains,exact,regex,url_parameter,domain',
            'rule_value' => 'required|string|max:500',
            'match_field' => 'required|in:body,subject,url,from_email,from_domain',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'description' => 'nullable|string|max:255',
        ];
    }
}
