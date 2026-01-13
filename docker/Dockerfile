FROM php:8.3-fpm-bullseye

RUN apt update \
    && apt install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    pcntl \
    sockets \
    pdo_mysql \
    zip

RUN pecl install xdebug-3.3.1 \
    && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --global --add safe.directory /var/www

ENV APP_PATH=/var/www
WORKDIR $APP_PATH

COPY ./docker/php/*.ini $PHP_INI_DIR/conf.d/

COPY --chown=www-data . .

CMD ["php-fpm"]
