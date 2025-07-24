# Lead Management System

A Laravel-based CRM application that automatically processes emails to generate leads and notify clients through a Filament admin interface.

## Features

-   **Automated Email Processing**: Automatically processes incoming emails and converts them into leads
-   **Client Management**: Manage multiple clients with their contact information and notification preferences
-   **Distribution Rules**: Flexible email routing system with three types of rules:
    -   **Email Match Rules**: Route emails based on sender address or domain
    -   **Custom Body Text Rules**: Route emails based on content patterns in the email body (e.g., "Source: Facebook AND rep: henry")
    -   **Combined Rules**: Route emails that match BOTH email pattern AND body text conditions
-   **Lead Tracking**: Track leads through various stages (new, contacted, qualified, converted, lost)
-   **Admin Interface**: Modern admin panel built with Filament for easy management
-   **Notifications**: Automatic email notifications to clients when new leads are received
-   **Multiple Lead Sources**: Support for leads from website, phone, referral, social media, and other sources

## Technology Stack

-   **Backend**: Laravel 12.x (PHP 8.2+)
-   **Admin Panel**: Filament 3.x
-   **Database**: configurable
-   **Email Processing**: PHP-IMAP, Mail-MIME-Parser
-   **Frontend**: Vite + TailwindCSS
-   **Testing**: Pest PHP

## Installation

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   SQLite (or MySQL/PostgreSQL if preferred)
-   Node.js & npm (optional - only needed for frontend asset compilation)

### Setup

1. **Clone the repository**

    ```bash
    git clone https://github.com/ElivateDev/Leads
    cd Leads
    ```

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Environment Configuration**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Database Setup**

    Configure your DB settings in the `.env` file:

    ```env
    DB_CONNECTION=mysql # or dealer's choice
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=user
    DB_PASSWORD=
    ```

    ```bash
    php artisan migrate
    ```

5. **Create Admin User**

    ```bash
    php artisan make:filament-user
    ```

6. **Optional: Install Node.js dependencies (for frontend customization)**
    ```bash
    npm install
    npm run build
    ```

## Configuration

### Email Processing Setup

Configure your IMAP settings in the `.env` file:

```env
IMAP_HOST=your-imap-server.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME=your-email@domain.com
IMAP_PASSWORD=your-password
IMAP_DEFAULT_FOLDER=INBOX
```

### Mail Configuration

For email notifications, configure your mail settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="your-email@domain.com"
MAIL_FROM_NAME="Your Company Name"
```

### Optional: Default Client

Set a default client ID for unmatched emails:

```env
DEFAULT_CLIENT_ID=1
```

## Usage

### Email Processing

Process emails manually:

```bash
php artisan leads:process-emails
```

Or set up a cron job for automatic processing:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Managing Data

#### Clients

-   Create clients with contact information
-   Enable/disable email notifications per client
-   Manage multiple email addresses per client for lead routing

### Distribution Rules

The Distribution Rules system allows you to automatically route incoming emails to the correct clients based on the sender's email address, the content of the email body, or both.

#### Email Match Rules

Email Match Rules route emails based on the sender's email address:

1. **Exact Email Match**: Route emails from a specific address

    - Example: `info@client.com` → Routes to Client A
    - Use case: Direct contact forms or specific email addresses

2. **Domain Match**: Route all emails from a domain
    - Example: `@client.com` → Routes to Client A
    - Use case: All emails from a client's organization

#### Custom Body Text Rules

Custom Body Text Rules analyze the email content and route based on patterns:

1. **Single Condition**: Match a single text pattern

    - Example: `Source: Facebook` → Routes Facebook leads
    - Use case: Lead forms with source identification

2. **AND Conditions**: All conditions must be met

    - Example: `Source: Facebook AND rep: henry` → Routes Facebook leads for Henry
    - Use case: Specific combinations of criteria

3. **OR Conditions**: Any condition can be met
    - Example: `Source: Facebook OR Source: Instagram` → Routes social media leads
    - Use case: Multiple sources going to the same client

#### Combined Rules

Combined Rules require BOTH email pattern AND body text conditions to be met:

1. **Exact Email + Custom Conditions**: Most specific targeting

    - Example: `leads@facebook.com` + `rep: henry` → Only Facebook emails for Henry
    - Use case: Specific rep handling specific source emails

2. **Domain + Custom Conditions**: Broad email filtering with content specificity

    - Example: `@zillow.com` + `property_type: commercial` → Only Zillow commercial leads
    - Use case: Platform-specific content filtering

3. **Complex Combinations**: Multiple conditions on both sides
    - Example: `@realtor.com` + `agent: sarah AND property_type: residential`
    - Use case: Highly targeted lead routing

#### Creating Distribution Rules

1. Navigate to "Distribution Rules" in the admin panel
2. Click "Create Distribution Rule"
3. Select the client to receive matched emails
4. Choose rule type:
    - **Email Address Match**: Enter the email address or domain pattern
    - **Custom Body Text Rule**: Enter the conditions using AND/OR logic
    - **Combined Rule (Email + Body Text)**: Enter both email pattern AND custom conditions
5. Add a description to help identify the rule's purpose
6. Ensure the rule is active

##### Rule Processing Order

**All matching rules are processed**: When an email arrives, the system checks ALL distribution rules and creates leads for every client that has a matching rule. This means:

-   **Multiple clients can receive the same lead**: If two clients have identical rules (e.g., both want `leads@zillow.com`), both will receive a copy of the lead
-   **No rule priority system**: All matching rules are treated equally - there's no "first match wins" behavior
-   **Comprehensive rule matching**: The system processes exact email matches, domain patterns, custom rules, and combined rules to find all possible matches

**Best Practices**:

-   Use specific conditions to avoid unwanted duplicates
-   For shared email sources, differentiate rules with custom conditions (e.g., `rep: john` vs `rep: sarah`)
-   Monitor rule logs to identify overlapping rules that might need adjustment

**Rule Types Processed** (in this order, but all matches are included):

1. Exact email address matches
2. Domain pattern matches (@domain.com)
3. Custom body text rules
4. Combined rules (email + body text)
5. Fallback to client domain matching

#### Example Use Cases

1. **Real Estate Agent with Multiple Sources**:

    - `Source: Zillow AND agent: john` → Routes Zillow leads to John
    - `Source: Realtor.com AND agent: jane` → Routes Realtor.com leads to Jane

2. **Marketing Agency with Campaign Tracking**:

    - `campaign: summer2023 AND product: webdesign` → Routes to Web Design team
    - `campaign: summer2023 AND product: seo` → Routes to SEO team

3. **Business with Multiple Locations**:

    - `location: downtown` → Routes to Downtown office
    - `location: suburbs` → Routes to Suburban office

4. **Combined Rule Examples**:
    - `leads@facebook.com` + `rep: henry` → Only Facebook emails for Henry
    - `@zillow.com` + `property_type: commercial AND agent: sarah` → Zillow commercial leads for Sarah
    - `support@company.com` + `priority: high OR urgent: true` → High priority support emails

#### Handling Duplicate Rules

The system supports multiple clients having identical or overlapping distribution rules. This is useful for:

**Lead Distribution Scenarios**:

-   **Team Lead Distribution**: Multiple agents want leads from the same source
-   **Backup Coverage**: Primary and backup agents for the same lead source
-   **Cross-Department Routing**: Sales and support teams both need certain emails

**Example: Multiple Agents for Zillow Leads**

```
Agent 1: leads@zillow.com → Creates lead for Agent 1
Agent 2: leads@zillow.com → Creates lead for Agent 2
Result: One Zillow email creates leads for both agents
```

**Example: Differentiated Rules for Same Source**

```
Agent 1: leads@zillow.com + "rep: john" → Only John's Zillow leads
Agent 2: leads@zillow.com + "rep: sarah" → Only Sarah's Zillow leads
General: leads@zillow.com → All other Zillow leads
Result: Selective distribution based on email content
```

**Monitoring**: Check the Email Processing Logs to see which rules matched for each email and verify leads are being distributed as expected.

#### Leads

-   View all leads with filtering and search capabilities
-   Update lead status as they progress through your sales funnel
-   Track lead sources and contact information

## Database Schema

### Clients

-   Basic contact information (name, email, phone, company)
-   Email notification preferences

### Leads

-   Contact details (name, email, phone)
-   Message content and source information
-   Status tracking and client assignment
-   Timestamps for lead received date

### Client Emails

-   Email-to-client mapping system
-   Support for multiple emails per client
-   Active/inactive status for mappings

## Architecture

### Key Components

-   **EmailLeadProcessor**: Core service for processing incoming emails
-   **Lead Observer**: Handles automatic notifications when leads are created
-   **Filament Resources**: Admin interface for managing clients, leads, and email mappings
-   **Console Commands**: Artisan commands for email processing
-   **Notifications**: Email notification system for new leads

### Email Processing Logic

1. Connect to IMAP server and fetch unread emails
2. Extract sender information, content, and metadata
3. Determine appropriate client based on email routing rules
4. Parse contact information from email content
5. Create lead record and send notifications if enabled

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `php artisan test`
5. Submit a pull request

## Testing

Run the test suite:

```bash
composer run test
```

Or use Pest directly:

```bash
./vendor/bin/pest
```

## Security

If you discover a security vulnerability in this application, please create a private security advisory on GitHub or contact the maintainers directly.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
