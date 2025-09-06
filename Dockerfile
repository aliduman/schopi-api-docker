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

# Copy application files - create the proper directory structure
COPY src/ /var/www/html/
WORKDIR /var/www/html/public
RUN pwd && ls -la

# Create app directory and copy app files
RUN mkdir -p /var/www/html/app
RUN if [ -d /var/www/html/src/app ]; then cp -rf /var/www/html/src/app/* /var/www/html/app/; fi
RUN ls -la /var/www/html/app || echo "app directory not accessible"

# Fix permissions
RUN chmod -R 755 /var/www/html

# Use our fixed index.php
COPY src/public/index.php.fixed /var/www/html/public/index.php

# Create a health check file
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status"=>"healthy"]);' > /var/www/html/public/_healthz

# Create a startup script
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh

# Create a startup script
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh

# Add fallback bootstrap file if needed
COPY src/app/bootstrap.php.fallback /var/www/html/app/bootstrap.php.fallback
RUN if [ ! -f "/var/www/html/app/bootstrap.php" ]; then \
    echo "Using fallback bootstrap.php"; \
    cp /var/www/html/app/bootstrap.php.fallback /var/www/html/app/bootstrap.php; \
fi

# Create symlinks to fix the directory structure issues
RUN mkdir -p /var/www/html/public/../app
RUN ln -sf /var/www/html/app /var/www/html/public/../app
RUN ls -la /var/www/html/public/../

# Set environment variable for Cloud Run
ENV PORT=8080

EXPOSE 8080

# Use PHP built-in web server for Cloud Run
CMD ["/var/www/html/start.sh"]
