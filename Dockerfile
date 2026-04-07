FROM php:8.4-fpm

# install-php-extensions — надёжная установка расширений без проблем с PECL
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# System dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && install-php-extensions \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        xml \
        redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Node.js (for Vite / frontend assets)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy package files and install JS deps
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts

# Copy application source
COPY . .

# Generate autoloader and run post-install scripts
RUN composer dump-autoload --optimize

# Создаём нужные директории и выставляем права
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} \
             /var/www/storage/logs \
             /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
