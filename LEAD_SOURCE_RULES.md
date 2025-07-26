# Lead Source Rules System

This system allows you to automatically determine the source of leads based on email content patterns.

## Overview

The Lead Source Rules system analyzes incoming emails and automatically assigns one of the following lead sources:

-   **Website** - Contact forms, website inquiries
-   **Social** - Facebook, Instagram, LinkedIn, etc.
-   **Phone** - Voicemails, missed call notifications
-   **Referral** - Referral mentions in email content
-   **Other** - Default fallback

## How it Works

1. When an email is processed by `EmailLeadProcessor`, it checks all active Lead Source Rules in priority order
2. The first rule that matches determines the lead source
3. If no rules match, it falls back to legacy logic, then defaults to "other"

## Rule Configuration

### Rule Types

-   **Contains**: Check if text is contained in the field
-   **Exact**: Exact text match
-   **Regex**: Regular expression pattern matching
-   **URL Parameter**: Match URL parameters like `utm_source=facebook`
-   **Domain**: Match email domain like `facebook.com`

### Match Fields

-   **Email Body**: Full email content
-   **Email Subject**: Subject line only
-   **URLs in Email**: Extract and match URLs
-   **From Email Address**: Sender's email address
-   **From Email Domain**: Domain part of sender's email

### Priority System

-   Rules are processed in priority order (highest first)
-   Priority range: 0-100
-   Higher priority rules are checked first
-   Recommended priorities:
    -   90-100: High precision rules (URL parameters, exact domains)
    -   70-89: Medium precision rules (specific keywords)
    -   50-69: General rules (broad patterns)
    -   0-49: Fallback rules

## Management Interface

### Admin Panel

-   Navigate to **Lead Management > Lead Source Rules**
-   View all rules across all clients
-   Create, edit, and delete rules
-   Filter by client, source type, or rule type

### Client-Specific Management

-   Navigate to **Lead Management > Clients**
-   Select a client and go to the "Lead Source Rules" tab
-   Manage rules specific to that client

## Example Rules

### Social Media

```php
[
    'source_name' => 'social',
    'rule_type' => 'domain',
    'rule_value' => 'facebook.com',
    'match_field' => 'from_domain',
    'priority' => 90,
    'description' => 'Emails from Facebook domain'
]
```

### Website Contact Forms

```php
[
    'source_name' => 'website',
    'rule_type' => 'contains',
    'rule_value' => 'contact form',
    'match_field' => 'subject',
    'priority' => 80,
    'description' => 'Contact form submissions'
]
```

### UTM Tracking

```php
[
    'source_name' => 'social',
    'rule_type' => 'url_parameter',
    'rule_value' => 'utm_source=instagram',
    'match_field' => 'url',
    'priority' => 95,
    'description' => 'Instagram UTM tracking parameter'
]
```

## Testing

Run the test command to see how rules work with sample emails:

```bash
php artisan test:lead-source-rules
```

## Database Schema

### lead_source_rules Table

-   `id` - Primary key
-   `client_id` - Foreign key to clients table
-   `source_name` - Lead source (website, social, phone, referral, other)
-   `rule_type` - How to match (contains, exact, regex, url_parameter, domain)
-   `rule_value` - Value to match against
-   `match_field` - Which email field to check (body, subject, url, from_email, from_domain)
-   `is_active` - Whether rule is enabled
-   `priority` - Processing priority (0-100)
-   `description` - Optional description
-   `created_at` / `updated_at` - Timestamps

## Migration and Seeding

### Create the table

```bash
php artisan migrate
```

### Seed example rules

```bash
php artisan db:seed --class=LeadSourceRuleSeeder
```

## Integration Points

### EmailLeadProcessor

The `determineLeadSource()` method has been updated to:

1. Query active lead source rules ordered by priority
2. Test each rule against the email content
3. Return the source from the first matching rule
4. Fall back to legacy logic if no rules match
5. Log rule matches for debugging

### Lead Model

No changes required - still uses the same source validation:

```php
'source' => 'required|in:website,phone,referral,social,other'
```

### Client Model

Added relationship:

```php
public function leadSourceRules(): HasMany
{
    return $this->hasMany(LeadSourceRule::class);
}
```

## Dashboard Widgets

### Lead Sources Overview (Chart)

-   Shows lead count by source for last 30 days
-   Color-coded bar chart

### Lead Source Rules Stats

-   Total rules count
-   Active rules count
-   Recent leads count
-   Rules applied today

## Performance Considerations

-   Rules are cached and ordered by priority for efficient processing
-   Database indexes on `client_id`, `is_active`, and `priority`
-   Lazy loading for custom rules to handle large rule sets
-   Rules processed in batches to avoid memory issues

## Best Practices

1. **Start with high-priority, specific rules** (exact domains, URL parameters)
2. **Use descriptive names** for easy management
3. **Test rules** with the test command before deploying
4. **Monitor rule effectiveness** through the dashboard
5. **Keep rule sets manageable** - avoid too many overlapping rules
6. **Document custom regex patterns** in the description field

## Troubleshooting

### Rule Not Matching

1. Check rule is active
2. Verify priority order
3. Test rule value format
4. Check match field selection
5. Use test command for debugging

### Performance Issues

1. Review number of active rules
2. Optimize regex patterns
3. Use more specific rules to reduce processing
4. Consider rule consolidation

## Future Enhancements

-   Rule testing interface in admin panel
-   Rule effectiveness analytics
-   Bulk import/export of rules
-   Advanced pattern templates
-   Machine learning integration for automatic rule suggestion
