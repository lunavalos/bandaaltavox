# ============================================================
# Stage 1 – Composer: install PHP deps (no dev)
# ============================================================
FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev


# ============================================================
# Stage 2 – Node: compile frontend assets (client + SSR)
# vendor/ is needed because app.js imports from vendor/tightenco/ziggy
# ============================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

COPY resources/ resources/
COPY public/ public/
COPY vite.config.js jsconfig.json tailwind.config.js postcss.config.js ./

# Copy only the Ziggy package from the vendor stage so Vite can resolve it
COPY --from=vendor /app/vendor/tightenco /app/vendor/tightenco

RUN npm run build


# ============================================================
# Stage 3 – Runtime: PHP 8.4-FPM + Nginx (production)
# ============================================================
FROM php:8.4-fpm-alpine AS runtime

LABEL maintainer="Banda Alta Vox"

# Use install-php-extensions for fast pre-built extension installation
# (avoids compiling from source = ~2 min instead of 30 min)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# System deps (runtime libs only, no -dev headers needed)
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    oniguruma \
    icu-libs \
    && install-php-extensions \
        mbstring \
        zip \
        bcmath \
        intl \
        opcache \
        pcntl \
        gd

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

# Nginx config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor config (PHP-FPM + Nginx + queue worker)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copy application
COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/html/public/build
COPY --chown=www-data:www-data --from=frontend /app/bootstrap/ssr /var/www/html/bootstrap/ssr

# Writable dirs
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views,testing} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
