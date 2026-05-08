FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-tiktrack.ini

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 80
