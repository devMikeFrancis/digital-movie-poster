#!/usr/bin/env bash
#
# Digital Movie Poster installer for Raspberry Pi OS (Bookworm or newer).
#
#   sudo ./install.sh $USER
#
# Re-running is safe: every step checks before it acts.

set -euo pipefail

PHP_VERSION="${PHP_VERSION:-8.4}"
NODE_MAJOR="${NODE_MAJOR:-22}"
APP_DIR="${APP_DIR:-/var/www/html}"
REPO_URL="${REPO_URL:-https://github.com/devMikeFrancis/digital-movie-poster.git}"

DMP_USER="${1:-}"

if [[ $EUID -ne 0 ]]; then
    echo "This installer must run as root:  sudo ./install.sh \$USER" >&2
    exit 1
fi

if [[ -z "$DMP_USER" ]]; then
    echo "Usage: sudo ./install.sh <username>" >&2
    echo "  e.g. sudo ./install.sh \$USER" >&2
    exit 1
fi

if ! id -u "$DMP_USER" >/dev/null 2>&1; then
    echo "No such user: $DMP_USER" >&2
    exit 1
fi

log() { echo -e "\n\033[1;36m==>\033[0m $*\n"; }

log "Installing Digital Movie Poster for user '$DMP_USER'"
log "PHP $PHP_VERSION | Node $NODE_MAJOR | $APP_DIR"

# ---------------------------------------------------------------------------
# Base packages
# ---------------------------------------------------------------------------
log "Updating apt and installing base packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y --no-install-recommends \
    apache2 apache2-utils ssl-cert redis-server supervisor git curl wget \
    ca-certificates gnupg lsb-release sed sqlite3 cec-utils

# ---------------------------------------------------------------------------
# PHP from the sury repository
# ---------------------------------------------------------------------------
log "Adding the sury PHP repository"
install -d -m 0755 /etc/apt/keyrings
if [[ ! -f /etc/apt/keyrings/php.gpg ]]; then
    wget -qO- https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/keyrings/php.gpg
fi
echo "deb [signed-by=/etc/apt/keyrings/php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
    > /etc/apt/sources.list.d/php.list
# The old installer left an unsigned list file behind; drop it if present.
rm -f /etc/apt/trusted.gpg.d/php.gpg

apt-get update -y
log "Installing PHP $PHP_VERSION"
apt-get install -y \
    "php${PHP_VERSION}-common" "php${PHP_VERSION}-cli" "libapache2-mod-php${PHP_VERSION}" \
    "php${PHP_VERSION}-curl" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-sqlite3" \
    "php${PHP_VERSION}-intl" "php${PHP_VERSION}-bcmath" php-imagick

# ---------------------------------------------------------------------------
# Apache
# ---------------------------------------------------------------------------
log "Configuring Apache"
# Disable any other PHP module that may be enabled from a previous install.
for mod in /etc/apache2/mods-enabled/php*.load; do
    [[ -e "$mod" ]] || continue
    name="$(basename "$mod" .load)"
    [[ "$name" == "php${PHP_VERSION}" ]] || a2dismod -f "$name" || true
done
a2enmod "php${PHP_VERSION}"
a2enmod rewrite

PHP_INI="/etc/php/${PHP_VERSION}/apache2/php.ini"
if [[ ! -f "${PHP_INI}.orig" ]]; then
    cp "$PHP_INI" "${PHP_INI}.orig"
fi

set_php_ini() {
    local key="$1" value="$2"
    if grep -qE "^\s*;?\s*${key}\s*=" "$PHP_INI"; then
        sed -i -E "s|^\s*;?\s*${key}\s*=.*|${key} = ${value}|" "$PHP_INI"
    else
        echo "${key} = ${value}" >> "$PHP_INI"
    fi
}

set_php_ini max_execution_time 1800
set_php_ini max_input_time 1800
set_php_ini post_max_size 50M
set_php_ini upload_max_filesize 50M
set_php_ini memory_limit 512M

sed -i "s,DocumentRoot ${APP_DIR}$,DocumentRoot ${APP_DIR}/public," /etc/apache2/sites-enabled/000-default.conf
sed -i "s,AllowOverride None,AllowOverride All,g" /etc/apache2/apache2.conf

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
if ! command -v composer >/dev/null 2>&1; then
    log "Installing Composer"
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    if [[ "$EXPECTED" != "$ACTUAL" ]]; then
        echo "Composer installer checksum mismatch - refusing to run it." >&2
        rm -f /tmp/composer-setup.php
        exit 1
    fi
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

# ---------------------------------------------------------------------------
# Node + PM2
# ---------------------------------------------------------------------------
if ! command -v node >/dev/null 2>&1 || [[ "$(node -v | cut -c2- | cut -d. -f1)" -lt "$NODE_MAJOR" ]]; then
    log "Installing Node $NODE_MAJOR"
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y nodejs
fi
command -v pm2 >/dev/null 2>&1 || npm install -g pm2

# ---------------------------------------------------------------------------
# Application
# ---------------------------------------------------------------------------
log "Fetching the application into $APP_DIR"
mkdir -p "$APP_DIR"
rm -f "$APP_DIR/index.html"

if [[ -d "$APP_DIR/.git" ]]; then
    git -C "$APP_DIR" pull --ff-only
else
    git clone "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"

[[ -f .env ]] || cp .env.example .env

log "Installing PHP and JavaScript dependencies"
sudo -u "$DMP_USER" composer install --no-interaction --no-dev --optimize-autoloader
npm ci --omit=dev || npm install --omit=dev
npm run build
(cd socketserver && { npm ci --omit=dev || npm install --omit=dev; })

# ---------------------------------------------------------------------------
# Permissions
# ---------------------------------------------------------------------------
# Only storage, bootstrap/cache and the SQLite file need to be writable by the
# web server. The old installer made all of /var/www group-writable.
log "Setting permissions"
touch database/database.sqlite
chown -R "$DMP_USER":www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite
chmod 775 database

log "Preparing the application"
# Only mint APP_KEY when there isn't one. Rotating it would make the encrypted
# media-server credentials in the settings table permanently unreadable, and
# this script is meant to be safe to re-run.
if grep -qE '^APP_KEY=.+' .env; then
    echo "APP_KEY already set, leaving it alone."
else
    sudo -u "$DMP_USER" php artisan key:generate --force
fi
sudo -u "$DMP_USER" php artisan storage:link
sudo -u "$DMP_USER" php artisan migrate --force
sudo -u "$DMP_USER" php artisan optimize

systemctl enable --now redis-server
systemctl restart apache2

# ---------------------------------------------------------------------------
# Queue worker
# ---------------------------------------------------------------------------
log "Installing the queue worker"
cat > /etc/supervisor/conf.d/laravel-worker.conf <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=1 --timeout=5600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${DMP_USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=5600
EOF

supervisorctl reread
supervisorctl update
supervisorctl restart laravel-worker:* || supervisorctl start laravel-worker:*

# ---------------------------------------------------------------------------
# Socket server
# ---------------------------------------------------------------------------
log "Starting the socket server under pm2"
sudo -u "$DMP_USER" env PATH="$PATH" pm2 delete dmp-socket >/dev/null 2>&1 || true
sudo -u "$DMP_USER" env PATH="$PATH" pm2 start "${APP_DIR}/socketserver/server.js" --name dmp-socket
sudo -u "$DMP_USER" env PATH="$PATH" pm2 save
env PATH="$PATH:/usr/bin" pm2 startup systemd -u "$DMP_USER" --hp "/home/${DMP_USER}"

# ---------------------------------------------------------------------------
# HDMI-CEC display power
# ---------------------------------------------------------------------------
log "Configuring HDMI-CEC display control"
# Membership in the 'video' group grants access to the CEC device. The old
# installer used 'chmod 777 /dev/vchiq', which opened it to every account.
usermod -a -G video "$DMP_USER"

# The Laravel scheduler applies the display on/off hours every minute. This
# used to run in the kiosk browser.
SCHEDULER_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(
    crontab -u "$DMP_USER" -l 2>/dev/null \
        | grep -v -e 'hdmi-control.py' -e 'artisan schedule:run' || true
    echo "$SCHEDULER_LINE"
) | crontab -u "$DMP_USER" -

# ---------------------------------------------------------------------------
# Optional PIR motion sensor
# ---------------------------------------------------------------------------
# Off unless DMP_MOTION_SENSOR=true is set in .env. The sensor reports movement
# to the application rather than driving cec-client itself, so it and the
# schedule cannot disagree about the display's power state.
log "Installing the motion sensor service (inactive unless enabled in .env)"
apt-get install -y --no-install-recommends python3-gpiozero python3-lgpio

cat > /etc/systemd/system/dmp-motion.service <<EOF
[Unit]
Description=Digital Movie Poster motion sensor
After=network.target
Documentation=https://github.com/devMikeFrancis/digital-movie-poster

[Service]
Type=simple
User=${DMP_USER}
WorkingDirectory=${APP_DIR}
Environment=DMP_APP_DIR=${APP_DIR}
ExecStart=/usr/bin/python3 ${APP_DIR}/hdmi-control.py
Restart=on-failure
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload

if grep -qE '^DMP_MOTION_SENSOR=true' "${APP_DIR}/.env" 2>/dev/null; then
    systemctl enable --now dmp-motion.service
    echo "Motion sensor service started."
else
    systemctl disable --now dmp-motion.service >/dev/null 2>&1 || true
    echo "Motion sensor not enabled. To use one, set DMP_MOTION_SENSOR=true in"
    echo ".env and run: sudo systemctl enable --now dmp-motion.service"
fi

# ---------------------------------------------------------------------------
# Kiosk
# ---------------------------------------------------------------------------
log "Setting up kiosk mode"
apt-get install -y --no-install-recommends \
    xserver-xorg x11-xserver-utils xinit openbox xdotool unclutter

# Raspberry Pi OS ships 'chromium'; older releases used 'chromium-browser'.
if apt-get install -y --no-install-recommends chromium; then
    CHROMIUM_BIN="chromium"
else
    apt-get install -y --no-install-recommends chromium-browser
    CHROMIUM_BIN="chromium-browser"
fi

AUTOSTART=/etc/xdg/openbox/autostart
if ! grep -q "movieposter" "$AUTOSTART" 2>/dev/null; then
    cat >> "$AUTOSTART" <<EOF

# --- Digital Movie Poster kiosk ---
xset s off
xset s noblank
xset -dpms
setxkbmap -option terminate:ctrl_alt_bksp
${CHROMIUM_BIN} --user-agent=chrome-movieposter --ignore-gpu-blocklist \\
  --enable-accelerated-video-decode --enable-gpu-rasterization \\
  --window-size=1920,1080 --window-position=0,0 --start-fullscreen --kiosk \\
  --incognito --noerrdialogs --disable-translate --no-first-run \\
  --disable-infobars --disable-features=TranslateUI,TouchpadOverscrollHistoryNavigation \\
  --disk-cache-dir=/dev/null --password-store=basic --disable-pinch \\
  --overscroll-history-navigation=disabled \\
  --autoplay-policy=no-user-gesture-required 'http://localhost?rotate=true'
EOF
fi

BASH_PROFILE="/home/${DMP_USER}/.bash_profile"
touch "$BASH_PROFILE"
chown "$DMP_USER":"$DMP_USER" "$BASH_PROFILE"
if ! grep -q "startx" "$BASH_PROFILE"; then
    echo '[[ -z $DISPLAY && $XDG_VTNR -eq 1 ]] && startx -- -nocursor' >> "$BASH_PROFILE"
fi

# Boot to console with autologin.
raspi-config nonint do_boot_behaviour B2 || true

log "Install complete."
echo "  Display:  http://localhost?rotate=true"
echo "  Settings: http://$(hostname).local/posters"
echo
echo "  Set APP_TIMEZONE in ${APP_DIR}/.env to your own timezone, otherwise the"
echo "  display on/off hours are applied in UTC."
echo
echo "  The settings screen will ask you to create an administrator account"
echo "  the first time you open it. To do it from here instead, run:"
echo "      cd ${APP_DIR} && php artisan dmp:user"
echo
read -r -p "Reboot now? [y/N] " REPLY
[[ "$REPLY" =~ ^[Yy]$ ]] && reboot
