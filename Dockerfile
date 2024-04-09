FROM arm64v8/php:8.3.4-apache

RUN docker-php-ext-install mysqli && a2enmod rewrite
