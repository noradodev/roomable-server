FROM php:8.3-fpm

# Set non-interactive mode to avoid prompts
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies safely
RUN apt-get clean && rm -rf /var/lib/apt/lists/* \
    && apt-get update --allow-unauthenticated \
    && apt-get install -y --no-install-recommends \
       git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html
