#!/usr/bin/env bash
#
# Pulls the latest release and rebuilds. Triggered from the About page, or run
# it directly. Unlike the previous version this does not "git reset --hard",
# so local edits abort the update instead of being silently discarded.

set -euo pipefail

cd "$(dirname "$0")"

echo "Deploy started"

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
# Check this before anything destructive. Installs made before the Laravel 13
# refresh run PHP 8.1, and pulling first would leave the working tree on code
# that the installed PHP cannot run, with composer refusing to build a vendor
# directory for it - a broken device rather than a failed update.
REQUIRED_PHP_MAJOR=8
REQUIRED_PHP_MINOR=3

# When the About page triggers an update, the controller passes the binary
# serving the request. Falls back to PATH for a plain terminal run.
PHP_BIN="${DMP_PHP:-php}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "PHP not found (tried '$PHP_BIN'). Set DMP_PHP to its full path." >&2
    exit 1
fi

PHP_MAJOR="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION;')"
PHP_MINOR="$("$PHP_BIN" -r 'echo PHP_MINOR_VERSION;')"

if (( PHP_MAJOR < REQUIRED_PHP_MAJOR )) \
    || { (( PHP_MAJOR == REQUIRED_PHP_MAJOR )) && (( PHP_MINOR < REQUIRED_PHP_MINOR )); }; then
    cat >&2 <<EOF

This release needs PHP ${REQUIRED_PHP_MAJOR}.${REQUIRED_PHP_MINOR} or newer, but this device has PHP $("$PHP_BIN" -r 'echo PHP_VERSION;').

Nothing has been changed. Upgrading in place needs a newer PHP and some new
packages, so re-run the installer instead - it is safe to run again and will
upgrade PHP, install what is missing, and keep your posters and settings:

    wget -O install.sh https://raw.githubusercontent.com/devMikeFrancis/digital-movie-poster/main/install.sh
    chmod u+x install.sh
    sudo ./install.sh \$USER

EOF
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Working tree has local changes - refusing to update." >&2
    echo "Commit, stash or discard them, then run this again." >&2
    exit 1
fi

"$PHP_BIN" artisan down --retry=60 || true
trap '"$PHP_BIN" artisan up || true' EXIT

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
git pull --ff-only origin "$BRANCH"

composer install --no-interaction --no-dev --optimize-autoloader
"$PHP_BIN" artisan migrate --force

npm ci --omit=dev || npm install --omit=dev
npm run build
(cd socketserver && { npm ci --omit=dev || npm install --omit=dev; })

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

# Restart the background pieces so they pick up the new code.
command -v pm2 >/dev/null 2>&1 && pm2 restart dmp-socket || true
command -v supervisorctl >/dev/null 2>&1 && sudo supervisorctl restart laravel-worker:* || true

echo "Deploy finished"
