#!/bin/bash
# deploy.sh
# Usage:
#   ./deploy.sh                   - Full deployment check and deploy if needed
#   ./deploy.sh --fix-permissions - Only fix file permissions and ownership
#   ./deploy.sh --help            - Show this help message

# Function to show help
show_help() {
    cat << EOF
Laravel Deployment Script

USAGE:
    ./deploy.sh [OPTION]

OPTIONS:
    (no option)             Run full deployment process
                           - Check for git changes
                           - Pull latest code if changes found
                           - Run composer install if needed
                           - Clear caches if config/routes/views changed
                           - Run migrations if needed
                           - Fix file permissions

    --fix-permissions, -p   Fix file permissions and ownership only
                           - Set correct file/directory permissions
                           - Fix ownership for Laravel directories
                           - Create missing directories
                           - Skip all git/composer/cache operations
                           - Useful when someone ran git/composer as root

    --help, -h             Show this help message and exit

CONFIGURATION:
    All configuration is read from the .env file in the project directory.

    Required variables:
    - PROJECT_DIR          Path to the Laravel project
    - LOG_FILE             Path to the deployment log file
    - LARAVEL_USER         System user for Laravel files
    - LARAVEL_GROUP        System group for Laravel files

EXAMPLES:
    ./deploy.sh                    # Normal deployment
    ./deploy.sh -p                 # Fix permissions only
    ./deploy.sh --fix-permissions  # Fix permissions only
    ./deploy.sh --help             # Show this help

LOG FILE:
    All deployment activities are logged to the file specified in LOG_FILE.
    Check the log file for detailed information about what happened during deployment.

EOF
}

# Check for help mode
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    show_help
    exit 0
fi

# Check for permissions-only mode
if [ "$1" = "--fix-permissions" ] || [ "$1" = "-p" ]; then
    PERMISSIONS_ONLY=true
    echo "Running in permissions-only mode..."
elif [ -n "$1" ]; then
    echo "Error: Unknown option '$1'"
    echo "Use --help or -h for usage information."
    exit 1
else
    PERMISSIONS_ONLY=false
fi

# Function to load .env file
load_env() {
    if [ -f .env ]; then
        echo "Loading environment variables from .env file..."
        export $(grep -v '^#' .env | xargs)
    else
        echo "Error: .env file not found"
        exit 1
    fi
}

# Function to validate required environment variables
validate_env() {
    local missing_vars=()

    if [ -z "$PROJECT_DIR" ]; then
        missing_vars+=("PROJECT_DIR")
    fi

    if [ -z "$LOG_FILE" ]; then
        missing_vars+=("LOG_FILE")
    fi

    if [ -z "$LARAVEL_USER" ]; then
        missing_vars+=("LARAVEL_USER")
    fi

    if [ -z "$LARAVEL_GROUP" ]; then
        missing_vars+=("LARAVEL_GROUP")
    fi

    if [ ${#missing_vars[@]} -gt 0 ]; then
        echo "Error: Missing required environment variables:"
        printf " - %s\n" "${missing_vars[@]}"
        echo "Please set these variables in your .env file"
        exit 1
    fi

    echo "Environment validation passed"
}

# Function to fix file permissions and ownership
fix_permissions() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Setting file permissions..." >> "$LOG_FILE"
    echo "Setting file permissions..."

    # Create any missing directories first (before setting permissions)
    echo "Creating missing Laravel directories..."
    mkdir -p "$PROJECT_DIR/storage/framework/views"
    mkdir -p "$PROJECT_DIR/storage/framework/cache"
    mkdir -p "$PROJECT_DIR/storage/framework/sessions"
    mkdir -p "$PROJECT_DIR/storage/logs"
    mkdir -p "$PROJECT_DIR/bootstrap/cache"

    # Set basic file permissions (exclude storage and bootstrap/cache for now)
    echo "Setting basic file permissions..."
    find "$PROJECT_DIR" -type d -not -path "*/storage/*" -not -path "*/bootstrap/cache/*" -exec chmod 755 {} \;
    find "$PROJECT_DIR" -type f -not -path "*/storage/*" -not -path "*/bootstrap/cache/*" -exec chmod 644 {} \;

    # Make deploy script executable
    chmod +x "$PROJECT_DIR/deploy.sh"

    # Set Laravel-specific permissions for writable directories
    echo "Setting Laravel-specific permissions..."
    chmod -R 775 "$PROJECT_DIR/storage"
    chmod -R 775 "$PROJECT_DIR/bootstrap/cache"

    # Fix ownership for Laravel directories (this is critical for web server access)
    echo "Setting ownership for Laravel directories..."

    chown -R "$LARAVEL_USER:$LARAVEL_GROUP" "$PROJECT_DIR/storage"
    chown -R "$LARAVEL_USER:$LARAVEL_GROUP" "$PROJECT_DIR/bootstrap/cache"

    # Ensure group write permissions (redundant but explicit)
    chmod -R g+w "$PROJECT_DIR/storage"
    chmod -R g+w "$PROJECT_DIR/bootstrap/cache"

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] File permissions set successfully" >> "$LOG_FILE"
    echo "File permissions updated successfully!"
}

load_env

validate_env

# If permissions-only mode, skip to permissions section
if [ "$PERMISSIONS_ONLY" = true ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running permissions-only mode..." >> "$LOG_FILE"
    echo "Fixing file permissions and ownership..."
    cd "$PROJECT_DIR"
    # Jump to permissions section
    fix_permissions
    exit 0
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting deployment check..." >> "$LOG_FILE"

cd "$PROJECT_DIR"

git fetch origin main

LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] No changes detected. Skipping deployment." >> "$LOG_FILE"
    echo "No changes detected. System is up to date."
    exit 0
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Changes detected. Starting deployment..." >> "$LOG_FILE"

# Stash any local changes
git stash >> "$LOG_FILE" 2>&1

# Pull changes
if git pull origin main >> "$LOG_FILE" 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Git pull successful" >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Git pull failed, attempting recovery" >> "$LOG_FILE"
    git checkout HEAD -- composer.lock >> "$LOG_FILE" 2>&1
    git pull origin main >> "$LOG_FILE" 2>&1
fi

# Check if composer.json or composer.lock changed
if git diff --name-only "$LOCAL" HEAD | grep -q "composer\.\(json\|lock\)"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Composer files changed, running composer install" >> "$LOG_FILE"
    echo "Running composer install..."

    # Add timeout and better error handling for composer
    timeout 300 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist >> "$LOG_FILE" 2>&1

    if [ $? -eq 0 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Composer install completed successfully" >> "$LOG_FILE"
    elif [ $? -eq 124 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Composer install timed out after 5 minutes" >> "$LOG_FILE"
        echo "Composer install timed out. Check log for details."
        exit 1
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Composer install failed with exit code $?" >> "$LOG_FILE"
        echo "Composer install failed. Check log for details."
        exit 1
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] No composer changes, skipping composer install" >> "$LOG_FILE"
fi

# Only clear cache if config files changed
if git diff --name-only "$LOCAL" HEAD | grep -q "config/\|\.env"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Config files changed, clearing cache" >> "$LOG_FILE"
    php artisan config:clear >> "$LOG_FILE" 2>&1
    php artisan config:cache >> "$LOG_FILE" 2>&1
fi

# Only clear routes if route files changed
if git diff --name-only "$LOCAL" HEAD | grep -q "routes/"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Route files changed, clearing route cache" >> "$LOG_FILE"
    php artisan route:clear >> "$LOG_FILE" 2>&1
    php artisan route:cache >> "$LOG_FILE" 2>&1
fi

# Only clear views if blade files changed
if git diff --name-only "$LOCAL" HEAD | grep -q "\.blade\.php\|resources/views"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] View files changed, clearing view cache" >> "$LOG_FILE"
    php artisan view:clear >> "$LOG_FILE" 2>&1
    php artisan view:cache >> "$LOG_FILE" 2>&1
fi

# Run migrations if migration files changed
if git diff --name-only "$LOCAL" HEAD | grep -q "database/migrations"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Migration files changed, running migrations" >> "$LOG_FILE"
    php artisan migrate --force >> "$LOG_FILE" 2>&1
fi

# Final optimization step - run after all other operations
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running final optimization" >> "$LOG_FILE"
echo "Running Laravel optimization..."
php artisan optimize >> "$LOG_FILE" 2>&1

# Fix permissions (always run)
fix_permissions

echo "Deployment completed successfully!"
