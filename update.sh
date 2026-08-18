#!/usr/bin/env bash
#
# Pulls the latest release and rebuilds. Triggered from the About page, or run
# it directly. Unlike the previous version this does not "git reset --hard",
# so local edits abort the update instead of being silently discarded.

set -euo pipefail

cd "$(dirname "$0")"

echo "Deploy started"

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Working tree has local changes - refusing to update." >&2
    echo "Commit, stash or discard them, then run this again." >&2
    exit 1
fi

php artisan down --retry=60 || true
trap 'php artisan up || true' EXIT

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
git pull --ff-only origin "$BRANCH"

composer install --no-interaction --no-dev --optimize-autoloader
php artisan migrate --force

npm ci --omit=dev || npm install --omit=dev
npm run build
(cd socketserver && { npm ci --omit=dev || npm install --omit=dev; })

php artisan optimize:clear
php artisan optimize

# Restart the background pieces so they pick up the new code.
command -v pm2 >/dev/null 2>&1 && pm2 restart dmp-socket || true
command -v supervisorctl >/dev/null 2>&1 && sudo supervisorctl restart laravel-worker:* || true

echo "Deploy finished"
