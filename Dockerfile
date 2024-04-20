FROM arm64v8/php:8.3.4-apache

RUN apt-get update && apt-get install -y zip libzip-dev
#RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip

RUN docker-php-ext-install mysqli && a2enmod rewrite
