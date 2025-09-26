# Campaign Rule Application Feature

This feature allows you to scan all existing leads and update them with campaign assignments based on your campaign rules.

## Use Cases

1. **Retroactive Rule Application**: When you create a new campaign rule, you can apply it to all existing leads that match the criteria.
2. **Data Cleanup**: If you discover many leads were processed without campaign assignments, you can create rules and apply them retroactively.
3. **Bulk Updates**: Apply multiple campaign rules at once to update lead data efficiently.

## Available Methods

### 1. Individual Rule Application (Filament UI)

**Location**: Campaign Rules → Actions → "Apply to All Leads" button

- Click the "Apply to All Leads" button next to any active campaign rule
- Shows a preview of how many leads will be affected
- Requires confirmation before applying changes
- Only visible for active rules

### 2. Bulk Rule Application (Filament UI)

**Location**: Campaign Rules → Select multiple rules → Bulk Actions → "Apply Selected Rules to All Leads"

- Select multiple campaign rules using checkboxes
- Apply all selected active rules at once
- Rules are applied in priority order (highest first)
- Shows total summary of changes made

### 3. Apply All Rules (Filament UI)

**Location**: Campaign Rules → Header → "Apply All Rules to All Leads" button

- Applies ALL active campaign rules to existing leads
- Rules are processed in priority order
- Useful for complete data cleanup operations
- Shows comprehensive results summary

### 4. Command Line Interface

**Command**: `php artisan leads:apply-campaign-rules`

**Options**:
- `--rule-id=ID` : Apply only a specific campaign rule
- `--client-id=ID` : Apply rules only for a specific client
- `--dry-run` : Preview changes without applying them

**Examples**:
```bash
# Preview all changes without applying
php artisan leads:apply-campaign-rules --dry-run

# Apply all active rules
php artisan leads:apply-campaign-rules

# Apply only rule ID 5
php artisan leads:apply-campaign-rules --rule-id=5

# Apply rules for client ID 3 only
php artisan leads:apply-campaign-rules --client-id=3

# Preview changes for specific rule
php artisan leads:apply-campaign-rules --rule-id=5 --dry-run
```

## How It Works

1. **Lead Matching**: The system creates a mock email object from each lead's existing data (subject, from_email, message)
2. **Rule Application**: Each active campaign rule is tested against the lead data using the same logic as email processing
3. **Priority Handling**: Rules with higher priority values are applied first
4. **Conflict Resolution**: If multiple rules match a lead, only the highest priority rule is applied
5. **Safety Checks**: Only leads that don't already have the target campaign are updated

## Data Used for Matching

The system uses the following lead fields to match against campaign rules:

- **Email Subject** → `email_subject` field
- **From Email** → `from_email` field  
- **Email Body** → `message` field
- **URLs in Email** → Extracted from `message` field

## Performance Notes

- The "Matching Leads" column shows a real-time count of leads that would be affected
- Large datasets may take some time to process
- Use the `--dry-run` option to preview changes before applying
- Consider applying rules to specific clients for better performance

## Safety Features

- **Confirmation Required**: All UI actions require confirmation
- **Preview Mode**: See exactly what will change before applying
- **Activity Logging**: All rule applications are logged for audit purposes
- **No Overwriting**: Existing campaigns are only updated if they're different from the rule's campaign
- **Error Handling**: Individual lead processing errors don't stop the entire operation

## Best Practices

1. **Test First**: Always use `--dry-run` or preview functionality before applying rules
2. **Priority Order**: Set rule priorities carefully to ensure correct application order
3. **Specific Rules**: Create specific rules rather than overly broad ones
4. **Regular Cleanup**: Run rule application periodically for new leads that may have been missed
5. **Monitor Results**: Check the results summary to ensure expected number of updates

## Monitoring and Logs

All campaign rule applications are logged to the application log with details including:
- Rule ID and campaign name
- Lead ID and changes made
- Processing timestamps
- Any errors encountered

Check the logs at `storage/logs/laravel.log` for detailed information about rule applications.