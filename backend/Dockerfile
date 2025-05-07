FROM php:8.4-fpm

WORKDIR /var/www/html

# Install required dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    zip \
    unzip \
    curl \
    libpq-dev \
    && docker-php-ext-install \
        intl \
        pdo_pgsql \
        bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Copy the application files
COPY . .

EXPOSE 9000

ENV XDEBUG_MODE=coverage
