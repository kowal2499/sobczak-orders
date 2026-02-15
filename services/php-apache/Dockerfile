FROM php:8.1-apache

MAINTAINER Roman Kowalski <roman@erla.pl>

RUN a2enmod rewrite expires;

RUN apt-get update; \
    apt-get install -y --no-install-recommends \
    		libfreetype6-dev \
    		libicu-dev \
    		libjpeg-dev \
    		libmagickwand-dev \
    		libpng-dev \
    		libwebp-dev \
    		libzip-dev \
            zip \
            less \
            vim \
            git \
            default-mysql-client \
            openssh-client \
            sshpass; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install bcmath exif gd intl mysqli pdo pdo_mysql zip calendar; \
    pecl install imagick-3.6.0; \
    docker-php-ext-enable imagick; \
    rm -r /tmp/pear;

# Apache defaults
ENV APACHE_SERVER_ADMIN=roman@erla.pl
COPY ./services/php-apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# php.ini
COPY ./services/php-apache/php.ini $PHP_INI_DIR/conf.d/

ARG PUID=1000
ARG PGID=1000

ENV PUID ${PUID}
ENV PGID ${PGID}

RUN groupadd -g ${PGID} user && \
    useradd -u ${PUID} -g user -m user

VOLUME /var/www/html

USER root
# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.7.0
# Install symfony cli
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv $HOME/.symfony5/bin/symfony /usr/local/bin/symfony
# Install deployer
RUN curl -LO https://deployer.org/deployer.phar && \
    mv deployer.phar /usr/local/bin/dep && \
    chmod +x /usr/local/bin/dep

USER user
WORKDIR /var/www/html
