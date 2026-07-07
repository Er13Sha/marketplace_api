# syntax=docker/dockerfile:1

############################################
#  Backend — Symfony on PHP 8.4 + Swoole
############################################
FROM php:8.4-cli-bookworm AS app

# PHP extensions: PostgreSQL, Redis, Swoole, intl, opcache, zip.
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        pdo_pgsql \
        intl \
        zip \
        opcache \
        redis \
        swoole

# Composer (binary only).
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Runtime config tuned for a long-running Swoole worker.
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-highload.ini

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Install PHP deps first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Application source.
COPY . .

# Optimized autoloader; the symfony/runtime plugin also (re)generates
# vendor/autoload_runtime.php here.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
 && mkdir -p var \
 && chown -R www-data:www-data var

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["php", "bin/swoole.php"]
