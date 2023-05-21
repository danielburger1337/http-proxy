FROM php:8.2-apache

RUN apt-get update -y && apt-get install -y zip unzip

# Configure Apache
ENV APACHE_DOCUMENT_ROOT=/app/public
RUN rm /var/log/apache2/access.log
RUN ln -s /dev/null /var/log/apache2/access.log
RUN a2enmod rewrite
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /app

############################################
# Install Composer dependencies
############################################

COPY composer.json /tmp/composer/composer.json
COPY composer.lock /tmp/composer/composer.lock

RUN cd /tmp/composer && composer install --no-ansi --no-interaction --no-progress --no-scripts --no-dev && composer clear-cache

RUN cp -a /tmp/composer/vendor /app && rm -rf /tmp/composer

############################################
# Build App
############################################

WORKDIR /app

COPY . .

# Rebuild autoloader to fix /tmp/composer classmap
RUN composer dump-autoload --no-ansi --no-interaction --optimize --no-scripts --classmap-authoritative --no-dev

# Add php configuration
COPY php.ini /usr/local/etc/php/conf.d/php_docker.ini
