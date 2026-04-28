FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" mysqli gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/upload \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/upload

EXPOSE 80
