# ---- Stage 1: Build PHP & Composer Dependencies ----
    FROM --platform=$BUILDPLATFORM php:8.2-fpm AS php-builder

    WORKDIR /var/www
    
    # Install required system dependencies
    RUN apt-get update && \
        apt-get install -y --no-install-recommends \
            nano git unzip zip curl \
            libpng-dev libfreetype6-dev libwebp-dev libzip-dev \
            libssl-dev libcurl4-openssl-dev libxml2-dev libgd-dev \
            pkg-config libonig-dev libjpeg62-turbo-dev && \
        rm -rf /var/lib/apt/lists/*
    
    # Configure and install PHP extensions
    RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
        docker-php-ext-install -j$(nproc) \
            gd \
            pdo \
            pdo_mysql \
            mysqli \
            mbstring \
            zip \
            exif \
            pcntl \
            bcmath \
            opcache \
            intl \
            xml
    
    # Install Composer globally
    RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    # Copy composer files and environment file
    COPY composer.json composer.lock ./
    COPY .env .env
    
    # Use Buildx cache mount for Composer dependencies
    RUN --mount=type=cache,target=/root/.composer \
        composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts --optimize-autoloader && \
        composer clear-cache
    
    # Copy application source code
    COPY . .
    
    # Set proper permissions
    RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www
    
    # ---- Stage 2: Final Laravel/WordPress Production Image ----
    FROM --platform=$BUILDPLATFORM php:8.2-fpm
    
    WORKDIR /var/www
    
    # Install only the necessary runtime dependencies
    RUN apt-get update && \
        apt-get install -y --no-install-recommends \
            nano libzip-dev libpng-dev libxml2-dev \
            libwebp-dev libfreetype6-dev libjpeg62-turbo-dev pkg-config libonig-dev && \
        rm -rf /var/lib/apt/lists/*
    
    # Configure and install PHP extensions for production
    RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
        docker-php-ext-install -j$(nproc) \
            gd \
            pdo \
            pdo_mysql \
            mysqli \
            mbstring \
            zip \
            exif \
            pcntl \
            bcmath \
            opcache \
            intl \
            xml
    
    # Optimize OPcache for production performance
    RUN echo "opcache.enable=1\n\
    opcache.validate_timestamps=0\n\
    opcache.max_accelerated_files=20000\n\
    opcache.memory_consumption=256\n\
    opcache.interned_strings_buffer=16\n\
    opcache.jit_buffer_size=100M\n\
    opcache.jit=tracing" > /usr/local/etc/php/conf.d/opcache.ini
    
    # Copy application and dependencies from the build stage
    COPY --from=php-builder /var/www /var/www
    
    # Create a non-root user for security
    RUN useradd -m appuser && \
        chown -R appuser:appuser /var/www && \
        chmod -R 775 /var/www
    
    USER appuser
    
    EXPOSE 9000
    
    # Run php-fpm in the foreground
    CMD ["php-fpm", "-F"]
    