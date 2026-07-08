#!/bin/sh
set -e

# Warm the prod cache for this container (real infra env vars are present now).
php bin/console cache:clear --no-interaction || true

# The web container provisions the DB schema and the Messenger transport tables.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "[entrypoint] Running database migrations + messenger transport setup..."
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console messenger:setup-transports --no-interaction
fi

exec "$@"
