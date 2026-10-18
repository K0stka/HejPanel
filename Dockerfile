FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libavif-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libonig-dev unzip \
    && docker-php-ext-configure gd --with-avif --with-jpeg --with-webp \
    && docker-php-ext-install mysqli mbstring gd \
    && a2enmod rewrite expires \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .
COPY docker/apache.conf /etc/apache2/conf-enabled/hejpanel.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/hejpanel.ini

RUN mkdir -p uploads generated/packages \
    && chown -R www-data:www-data uploads generated/packages \
    && chown www-data:www-data php/conf.php assets/manifest.json

EXPOSE 80
