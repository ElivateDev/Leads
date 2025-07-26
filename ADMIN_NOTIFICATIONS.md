# Admin Notification Settings

This feature allows system administrators to configure email notifications for various email processing events in the lead management system.

## Overview

Admin users can configure when they want to receive email notifications about:

- **Email Processing**: Get notified every time an email is successfully processed and leads are created
- **Processing Errors**: Get notified when email processing fails or encounters errors  
- **Unmatched Rules**: Get notified when emails don't match any client email rules
- **Duplicate Leads**: Get notified when duplicate lead submissions are detected
- **System Issues**: Get notified about IMAP connection issues, SMTP delivery problems, etc.

## Configuration

### Per-Admin Settings

Each admin user can configure their own notification preferences through the admin panel:

1. Go to **Administration > Admin Settings**
2. Click **Configure Notifications** for your admin user
3. Toggle the notification types you want to receive
4. Save your settings

### Global Default Settings

You can set system-wide default notification settings in your `.env` file:

```env
# Admin notification defaults (individual admins can override these)
ADMIN_NOTIFY_EMAIL_PROCESSED=false
ADMIN_NOTIFY_ERRORS=true
ADMIN_NOTIFY_RULES_NOT_MATCHED=false
ADMIN_NOTIFY_DUPLICATE_LEADS=false
ADMIN_NOTIFY_HIGH_EMAIL_VOLUME=false
ADMIN_NOTIFY_IMAP_CONNECTION_ISSUES=true
ADMIN_NOTIFY_SMTP_ISSUES=true
```

## Notification Types

### Email Processed Notifications
- **When**: Every time an email is successfully processed and leads are created
- **Contains**: Email details, matched clients, created leads, source/campaign info
- **Recommended**: Only enable for small-volume systems or during testing

### Error Notifications  
- **When**: Email processing fails due to technical issues
- **Contains**: Error details, troubleshooting context, affected email info
- **Recommended**: Enable for all admins to catch system issues quickly

### Unmatched Rules Notifications
- **When**: No client email rules match an incoming email
- **Contains**: Sender domain, whether default client was used, suggestions
- **Recommended**: Enable to identify potential new clients or missing rules

### Duplicate Lead Notifications
- **When**: A duplicate lead submission is detected and handled
- **Contains**: Original lead details, duplicate detection method, time since original
- **Recommended**: Enable if you want to monitor duplicate submission patterns

### System Issue Notifications
- **When**: IMAP connection failures, SMTP delivery issues, etc.
- **Contains**: Technical details, troubleshooting tips, affected services
- **Recommended**: Enable for system administrators

## Managing Notification Volume

For high-volume email processing, consider:

1. **Selective Notifications**: Only enable error and system issue notifications
2. **Digest Options**: (Future feature) Receive summarized notifications instead of individual ones
3. **Filtering**: (Future feature) Set up filters based on client, source, or volume thresholds

## Troubleshooting

### Not Receiving Notifications

1. Check your admin user notification settings in **Admin Settings**
2. Verify your email address is correct in your user profile
3. Check that SMTP is configured correctly for outbound emails
4. Look for error logs in **Email Processing Logs**

### Too Many Notifications

1. Disable **Email Processed** notifications if enabled
2. Consider using summary notifications (when available)
3. Adjust global defaults in `.env` file

### Missing Notification Types

Ensure you have the latest code updates and that your admin user has the proper role permissions.

## Technical Implementation

The admin notification system uses:

- **AdminNotificationService**: Handles notification logic and delivery
- **User Preferences**: Stores individual admin notification settings
- **Notification Classes**: Different notification types for different events
- **Background Processing**: Notifications are sent asynchronously when possible

Notifications are triggered from:
- `EmailLeadProcessor`: For email processing events
- `LeadObserver`: For lead creation and notification delivery issues
- Various error handlers throughout the system
