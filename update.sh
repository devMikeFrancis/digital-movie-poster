#!/bin/bash

echo "Deploy script started"
#cd /var/www/html
cd `dirname $0` && pwd
php artisan down
git pull origin main --no-commit && git commit -m "Merge"
composer install --no-interaction
php artisan migrate --force
npm install
npm run build
php artisan optimize:clear
php artisan optimize
php artisan up
echo "Deploy finished"
exit
