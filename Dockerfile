FROM docker.io/library/composer:2 AS composer-bin
FROM docker.io/library/php:7.2-cli-alpine AS vendor
WORKDIR /app
COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache oniguruma-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && apk add --no-cache unzip \
    && apk del oniguruma-dev
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev
RUN php artisan storage:link

FROM docker.io/library/php:7.2-fpm-alpine AS runtime
WORKDIR /app
RUN apk add --no-cache nginx supervisor gettext curl oniguruma-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && apk add --no-cache unzip \
    && apk del oniguruma-dev
COPY --from=vendor /app /app

COPY .containerize/nginx-fpm.conf.template /etc/nginx/nginx.conf.template
COPY .containerize/supervisord.conf /etc/supervisord.conf
COPY .containerize/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY .containerize/entrypoint-fpm.sh /entrypoint.sh
RUN rm -f /usr/local/etc/php-fpm.d/www.conf \
    && chmod +x /entrypoint.sh \
    && mkdir -p /tmp/nginx /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs /app/bootstrap/cache /app/storage /app/bootstrap/cache \
    && chown -R www-data:www-data /tmp/nginx /app/storage /app/bootstrap/cache /app/storage /app/bootstrap/cache
ENV PORT=8080 DOC_ROOT=/app/public
USER www-data
EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=3 CMD ["sh", "-c", "case \"$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:${PORT}/)\" in 2*|3*) exit 0;; *) exit 1;; esac"]
