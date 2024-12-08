#!/bin/bash

echo "Current user: $1"

echo -e "\n\nUpdating Apt Packages and upgrading latest patches\n"
apt update -y && apt upgrade -y

echo -e "\n\nInstalling Apache2 Web server\n"
apt-get install apache2 apache2-doc apache2-utils libexpat1 ssl-cert redis-server sed -y

echo -e "\n\nInstalling PHP & Requirements\n"
wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list

apt-get install supervisor -y

touch /etc/supervisor/conf.d/laravel-worker.conf

echo "[program:laravel-worker]" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "process_name=%(program_name)s_%(process_num)02d" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "command=php /var/www/html/artisan queue:work --sleep=3 --tries=1 --timeout=5600" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "autostart=true" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "autorestart=true" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "stopasgroup=true" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "killasgroup=true" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "user=$1" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "numprocs=4" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "redirect_stderr=true" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "stdout_logfile=/var/www/html/worker.log" >> /etc/supervisor/conf.d/laravel-worker.conf
echo "stopwaitsecs=5600" >> /etc/supervisor/conf.d/laravel-worker.conf

apt update -y

apt-get install php8.1-common php8.1-cli libapache2-mod-php8.1 php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-mysql php-imagick -y

echo -e "\n\nInstalling MySQL\n"
apt-get install mariadb-server mariadb-client -y

echo -e "\n\nPermissions for /var/www"
chown -R www-data:www-data /var/www
chmod -R 775 /var/www
sudo usermod -a -G www-data $1
echo -e "\nPermissions have been set\n"

echo -e "\n\nEnabling Modules\n"
a2dismod php7.4
a2enmod php8.1
a2enmod rewrite
#phpenmod mcrypt

echo -e "\n\nRestarting Apache\n"
service apache2 restart

echo -e "\n\nLAMP Installation Completed\n"
echo "Current user: $1"
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

FIND="^\s*max_input_time\s*=\s*.*"
REPLACE="max_input_time = 1800"
printf "php.ini: $REPLACE\n"
sed -i "0,/$FIND/s/$FIND/$REPLACE/m" /etc/php/8.1/apache2/php.ini

FIND="^\s*memory_limit\s*=\s*.*"
REPLACE="memory_limit = 512M"
printf "php.ini: $REPLACE\n"
sed -i "0,/$FIND/s/$FIND/$REPLACE/m" /etc/php/8.1/apache2/php.ini

echo "extension=imagick" >> /etc/php/8.1/apache2/php.ini

sed -i "s,/var/www/html,/var/www/html/public,g" /etc/apache2/sites-enabled/000-default.conf
sed -i "s,AllowOverride None,AllowOverride All,g" /etc/apache2/apache2.conf

echo -e "\n\nRestarting Apache\n"
service apache2 restart

echo -e "\n\nApache finshed\n"

echo -e "\n\nConfiguring MySQL\n"

sed -e 's/\s*\([\+0-9a-zA-Z]*\).*/\1/' << EOF | mysql_secure_installation

    y
    movieposter
    movieposter
    y
    y
    y
    y
EOF
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
mysql -u root -pmovieposter -e "CREATE DATABASE movieposter;"
printf "Create user ...\n"
mysql -u root -pmovieposter -e "CREATE USER 'movieposter'@localhost IDENTIFIED BY 'movieposter';"
printf "Grant movieposter all privileges ...\n"
mysql -u root -pmovieposter -e "GRANT ALL PRIVILEGES ON *.* TO 'movieposter'@localhost;"
printf "Database created\n"

echo -e "\n\nRestarting MySQL\n"
service mysql restart

echo -e "\n\nMySQL finshed\n"

echo -e "\n\nInstalling Git\n"
apt install git -y
echo -e "\n\nGit installed\n"

echo -e "\n\nInstalling Composer\n"
wget -O composer-setup.php https://getcomposer.org/installer
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -rf composer-setup.php
echo -e "\n\nComposer installed\n"

echo -e "\n\nInstalling Nodejs and PM2\n"
curl -sL https://deb.nodesource.com/setup_16.x | bash -
apt-get install -y nodejs
npm install -g pm2
echo -e "\n\nNodejs and PM2 installed\n"

echo -e "\n\nInstalling and Configuring DMP app\n"

cd "/var/www/html" && pwd
rm index.html

git clone https://github.com/devMikeFrancis/digital-movie-poster.git .

composer install --no-interaction
npm install

cd "/var/www/html/socketserver" && pwd
npm install
pm2 startup
env PATH=$PATH:/usr/bin /usr/lib/node_modules/pm2/bin/pm2 startup systemd -u $1 --hp /home/$1
pm2 start server.js
pm2 save

cd "/var/www/html" && pwd

cp .env.example .env

echo "Current user: $1"

systemctl start redis.service

apt update -y

chown -R www-data:www-data /var/www
chmod -R 775 /var/www
chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

php artisan key:generate
php artisan storage:link

chgrp -R www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan migrate
npm run build

php artisan optimize:clear
php artisan optimize

echo -e "\n\nDMP install finished\n"

supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*

echo -e "\n\nInstalling HDMI CEC Control\n"

apt-get install cec-utils -y
line="@reboot python3 /var/www/html/hdmi-control.py"
(crontab -u $1 -l; echo "$line" ) | crontab -u $1 -
chmod 777 /dev/vchiq

echo -e "\n\nHDMI CEC control finished\n"

echo -e "\n\nKiosk Setup\n"

apt-get install --no-install-recommends xserver-xorg x11-xserver-utils xinit openbox -y
apt-get install xdotool unclutter -y
apt-get install --no-install-recommends chromium-browser -y

#Enable autologin
raspi-config nonint do_boot_behaviour B2

#https://itnext.io/raspberry-pi-read-only-kiosk-mode-the-complete-tutorial-for-2021-58a860474215

cd /home/$1 && pwd

autostart="
chromium-browser --user-agent=chrome-movieposter --ignore-gpu-blocklist --enable-accelerated-video-decode --enable-gpu-rasterization --window-size=1920,1080 --window-position=0,0 --start-fullscreen --kiosk --incognito --noerrdialogs --disable-translate --no-first-run --fast --fast-start --disable-infobars --disable-features=TranslateUI --disk-cache-dir=/dev/null  --password-store=basic --disable-pinch --overscroll-history-navigation=disabled --disable-features=TouchpadOverscrollHistoryNavigation --autoplay-policy=no-user-gesture-required  'http://localhost?rotate=true'
"
echo "xset s off" >> /etc/xdg/openbox/autostart
echo "xset s noblank" >> /etc/xdg/openbox/autostart
echo "xset -dpms" >> /etc/xdg/openbox/autostart
echo "setxkbmap -option terminate:ctrl_alt_bksp" >> /etc/xdg/openbox/autostart
echo "sed -i 's/\"exited_cleanly\":false/\"exited_cleanly\":true/' ~/.config/chromium/'Local State'" >> /etc/xdg/openbox/autostart
echo "sed -i 's/\"exited_cleanly\":false/\"exited_cleanly\":true/; s/\"exit_type\":\"[^\"]\+\"/\"exit_type\":\"Normal\"/' ~/.config/chromium/Default/Preferences" >> /etc/xdg/openbox/autostart
echo $autostart >> /etc/xdg/openbox/autostart

echo "Current user: $1"

touch /home/$1/.bash_profile
chown $1 /home/$1/.bash_profile
newline="[[ -z \$DISPLAY && \$XDG_VTNR -eq 1 ]] && startx -- -nocursor"
echo $newline >> /home/$1/.bash_profile
echo -e "\nKiosk setup finished\n"

usermod -a -G video $1

echo -e "\nAll done! Rebooting now.\n"

rm -rf install.sh

reboot now

exit 0
