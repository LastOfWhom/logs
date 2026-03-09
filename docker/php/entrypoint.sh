#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Install Composer dependencies if vendor/ is missing.
# --no-scripts prevents Symfony from running cache:clear during install,
# which would fail if the cache hasn't been bootstrapped yet.
# ---------------------------------------------------------------------------
if [ ! -f "vendor/autoload.php" ]; then
    echo "[entrypoint] vendor/ not found — running composer install..."
    composer install --no-interaction --prefer-dist --no-scripts
    echo "[entrypoint] composer install done."
fi

# ---------------------------------------------------------------------------
# Ensure Symfony var/ directory exists and is writable
# ---------------------------------------------------------------------------
mkdir -p var/cache var/log
chmod -R 777 var/

# ---------------------------------------------------------------------------
# Hand off to the main process (php-fpm by default)
# ---------------------------------------------------------------------------
exec "$@"
