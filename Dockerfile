# ------------------------------------
# ✅ Stage 1: Builder (Installs Dependencies & Composer)
# ------------------------------------
    FROM php:8.2-fpm-alpine AS builder

    # Step 1: Update Alpine Packages & Install Required Dependencies
    RUN apk update && apk upgrade && apk add --no-cache \
        zip \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libxml2-dev \
        mariadb-client \
        curl \
        wget \
        git \
        vim \
        oniguruma-dev \
        icu-dev \
        autoconf \
        build-base
    
    # Step 2: Configure and Install PHP Extensions
    RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
        docker-php-ext-install gd mbstring xml pdo pdo_mysql mysqli zip bcmath soap intl opcache && \
        docker-php-ext-enable gd mbstring xml pdo pdo_mysql mysqli zip bcmath soap intl opcache
    
    # Step 3: Install Redis Extension via PECL
    RUN pecl install redis && docker-php-ext-enable redis
    
    # Step 4: Install Composer (Globally)
    RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    # Set Working Directory
    WORKDIR /var/www
    
    # Copy Composer Files First (For Better Caching)
    COPY composer.json composer.lock ./
    
    # Install PHP Dependencies Before Copying Full Project
    RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
    
    # ------------------------------------
    # ✅ Stage 2: Final Minimal PHP-FPM Image
    # ------------------------------------
    FROM php:8.2-fpm-alpine
    
    # Set Working Directory
    WORKDIR /var/www
    
    # Copy Only Necessary Files from Builder Stage
    COPY --from=builder /var/www /var/www
    
    # Ensure Correct File Permissions
    RUN chown -R www-data:www-data /var/www
    
    # Expose PHP-FPM Port
    EXPOSE 9000
    
    # Start PHP-FPM
    CMD ["php-fpm", "-F"]
    