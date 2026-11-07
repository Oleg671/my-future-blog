FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/public

RUN sed -ri -e 's!/var/www/html!/var/www/public!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR /var/www
COPY src /var/www

