# Lead Management System

A Laravel-based CRM application that automatically processes emails to generate leads and notify clients through a Filament admin interface.

## Features

-   **Automated Email Processing**: Automatically processes incoming emails and converts them into leads
-   **Client Management**: Manage multiple clients with their contact information and notification preferences
-   **Email Routing**: Map specific email addresses to clients for automatic lead assignment
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

#### Client Email Mappings

-   Map specific email addresses to clients
-   Set up domain-based routing (e.g., all emails from @domain.com go to Client A)
-   Enable/disable specific email mappings

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
