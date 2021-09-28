#!/bin/bash

echo "Deploy script started"
#cd /var/www/html
cd `dirname $0` && pwd
php artisan down
git pull origin main
composer install --no-dev --prefer-dist
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan up
echo "Deploy finished"
exit
