#!/bin/bash

# Production Deployment Script for Laravel Lead Management System

echo "🚀 Starting production deployment..."

# 1. Install production dependencies
echo "📦 Installing production dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# 2. Clear all caches
echo "🧹 Clearing all caches..."
php artisan optimize:clear

# 3. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 4. Seed production data (if needed)
# php artisan db:seed --class=ProductionSeeder --force

# 5. Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# 6. Optimize for production
echo "⚡ Optimizing for production..."
php artisan optimize

# 7. Cache Filament components
echo "🎨 Caching Filament components..."
php artisan filament:cache-components

# 8. Set proper permissions (adjust paths as needed)
echo "🔒 Setting file permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

echo "✅ Production deployment completed!"
echo "🌐 Your application is ready for production use."
