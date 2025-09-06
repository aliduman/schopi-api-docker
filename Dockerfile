FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    libmemcached-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    sockets

RUN pecl install memcached && docker-php-ext-enable memcached
RUN pecl install ev && docker-php-ext-enable ev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY src/ /var/www/html/

# Set environment variable for Cloud Run
ENV PORT=8080

EXPOSE 8080

# Use PHP built-in web server for Cloud Run - public klasöründen başlat
CMD ["php", "-S", "0.0.0.0:8080", "-t", "src/public", "src/public/index.php"]
