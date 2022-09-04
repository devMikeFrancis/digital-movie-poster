#!/bin/bash

echo -e "\n\nUpdating Apt Packages and upgrading latest patches\n"
apt update -y && apt upgrade -y

echo -e "\n\nInstalling Apache2 Web server\n"
apt-get install apache2 apache2-doc apache2-mpm-prefork apache2-utils libexpat1 ssl-cert -y

echo -e "\n\nInstalling PHP & Requirements\n"
wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
apt update -y
apt-get install php8.1-common php8.1-cli libapache2-mod-php8.1 php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-mysql

echo -e "\n\nInstalling MySQL\n"
apt-get install mariadb-server mariadb-client -y

echo -e "\n\nPermissions for /var/www\n"
chown -R www-data:www-data /var/www
#chown -R www-data:www-data /var/www
#sudo chmod -R 770 /var/www/html/
echo -e "\n\n Permissions have been set\n"

echo -e "\n\nEnabling Modules\n"
a2dismod php7.4
a2enmod php8.1
a2enmod rewrite
phpenmod mcrypt

echo -e "\n\nRestarting Apache\n"
service apache2 restart

echo -e "\n\nLAMP Installation Completed\n"

echo -e "\n\nConfiguring PHP and Apache"

if [ ! -f /etc/php/8.1/apache2/php.ini.orig ]; then
    printf "Backing up PHP.ini configuration file to /etc/php/8.1/apache2/php.ini.orig\n"
    cp /etc/php/8.1/apache2/php.ini /etc/php/8.1/apache2/php.ini.orig
fi

FIND="^\s*max_execution_time\s*=\s*.*"
REPLACE="max_execution_time = 1800"
printf "php.ini: $REPLACE\n"
sed -i "0,/$FIND/s/$FIND/$REPLACE/m" /etc/php/8.1/apache2/php.ini

FIND="^\s*post_max_size\s*=\s*.*"
REPLACE="post_max_size = 50M"
printf "php.ini: $REPLACE\n"
sed -i "0,/$FIND/s/$FIND/$REPLACE/m" /etc/php/8.1/apache2/php.ini

FIND="^\s*upload_max_filesize\s*=\s*.*"
REPLACE="upload_max_filesize = 50M"
printf "php.ini: $REPLACE\n"
sed -i "0,/$FIND/s/$FIND/$REPLACE/m" /etc/php/8.1/apache2/php.ini

FIND="^\s*DocumentRoot\s* \s*.*"
REPLACE="DocumentRoot /var/www/html/public"
printf "000-default.conf: $REPLACE\n"
sed -i "0,/$FIND/s/$FIND/$REPLACE/m" /etc/apache2/sites-enabled/000-default.conf

echo -e "\n\nRestarting Apache\n"
service apache2 restart

echo -e "\n\nApache finshed\n"

echo -e "\n\nConfiguring MySQL\n"
# Skipping mysql_secure_installation for this since it won't be public facing

#Current password: Enter for none
#Y - Switch to unix_socket authentication
#Y - Change root password
#New password: Enter new password and save it. You will need it later.
#Re-enter new password
#Y - Remove anonymous users
#Y - Disallow root login remotely
#Y - Remove test database
#Y - Reload privilege tables now

printf "Creating database ...\n"
mysql -u root -p$mysqlrootpsw -e "CREATE DATABASE movieposter;"
printf "Create user $dbuser...\n"
mysql -u root -p$mysqlrootpsw -e "CREATE USER '$dbuser'@localhost IDENTIFIED BY '$dbpass';"
printf "Grant $dbuser all privileges on $dbname...\n"
mysql -u root -p$mysqlrootpsw -e "GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@localhost;"
printf "Database created\n"
echo -e "\n\nRestarting MySQL\n"
service mysql restart

echo -e "\n\nMySQL finshed\n"

echo -e "\n\nInstalling Git\n"
apt install git -y
echo -e "\n\n Git installed\n"

echo -e "\n\nInstalling Composer\n"
wget -O composer-setup.php https://getcomposer.org/installer
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -rf composer-setup.php
echo -e "\n\n Composer installed\n"

echo -e "\n\nInstalling Nodejs and PM2\n"
curl -sL https://deb.nodesource.com/setup_16.x | bash -
apt-get install -y nodejs
npm install -g pm2
echo -e "\n\n Nodejs and PM2 installed\n"

echo -e "\n\nInstalling and Configuring DMP app\n"

cd "/var/www/html" && pwd
rm index.html

git clone https://github.com/newelement/digital-movie-poster.git .

composer install
npm install

cd "/var/www/html/socketserver" && pwd
npm install
pm2 start server.js
pm2 save

cd "/var/www/html" && pwd

cp .env.example .env
php artisan key:generate
php artisan storage:link

chgrp -R www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan migrate
npm run build

php artisan optimize:clear
php artisan optimize

echo -e "\n\n DMP app finished\n"

#echo -e "\n\n HDMI CEC Control\n"

#sudo apt-get install cec-utils -y
#sudo crontab -e
#At the bottom of the crontab add:
#@reboot python3 /var/www/html/hdmi-control.py
#Save and exit
#sudo chmod 777 /dev/vchiq

#echo -e "\n\n HDMI CEC control finished\n"

#echo -e "\n\n Kiosk Setup\n"
#apt install chromium-browser -y
#https://pimylifeup.com/raspberry-pi-kiosk/
#echo -e "\n\n Kiosk setup finished\n"

exit 0
