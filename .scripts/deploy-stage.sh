#!/bin/bash
set -e

source .env

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

# Pull the latest version of the app
git pull origin stage

# Install composer dependencies
composer2 install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Clear the old cache
php artisan clear-compiled

# Recreate cache
php artisan optimize

# Compile npm assets
# npm run prod

# Run database migrations
php artisan october:migrate --force

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
