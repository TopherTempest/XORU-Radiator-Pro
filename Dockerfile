FROM php:8.2-apache

# Install PHP extensions required by Symfony and the project
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libpng-dev \
        default-mysql-client \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli \
        gd \
        intl \
        zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Apache document root is the Symfony public directory
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install PHP dependencies before copying the rest of the app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public /var/www/html/config /var/www/html/src

EXPOSE 80

CMD ["apache2-foreground"]
