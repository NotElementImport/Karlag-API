FROM php:8.2-fpm

WORKDIR /var/www

# Install all dependence:
RUN apt-get update && apt-get install -y \
      apt-utils  \
      libpq-dev  \
      libpng-dev  \
      libjpeg-dev  \
      libzip-dev  \
      zlib1g-dev  \
      libfreetype6-dev  \
      zip unzip  \
      supervisor cron  \
      git && \
      docker-php-ext-configure gd --with-jpeg --with-freetype && \
      docker-php-ext-install pdo_mysql && \
      docker-php-ext-install bcmath && \
      docker-php-ext-install gd && \
      docker-php-ext-install zip && \
      apt-get clean && \
      rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install gd

COPY ./docker/dev.php.ini /usr/local/etc/php/php.ini

# Install composer:
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin

# # Cron (Async Actions):
# RUN /usr/bin/supervisorctl restart all
# RUN /usr/bin/supervisord -c /etc/supervisor/supervisord.conf

CMD ["/bin/bash", "-c", "php-fpm"]
