# Use PHP-FPM with Alpine Linux for a lightweight image
FROM php:8.1-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk update && apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mbstring xml pdo pdo_mysql bcmath opcache

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www

# Copy your alternative composer.json into the container
COPY docker/composer-custom.json /

# Set the COMPOSER environment variable to use the alternative composer.json
ENV COMPOSER=composer-custom.json

# Install global Composer packages using the custom configuration file
RUN composer Install

# Configure PHPCS to use WordPress Coding Standards
RUN ~/.composer/vendor/bin/phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs

# Install ESLint globally
RUN npm install -g eslint

# Set working directory
WORKDIR /var/www

# Copy configuration files into the container
COPY .github/workflows/phpcs.xml.dist ./
COPY .github/workflows/.php-cs-fixer.php ./
COPY .github/workflows/phpstan.neon ./
COPY .github/workflows/eslint.config.js ./
COPY .github/workflows/phpunit.xml.dist ./
COPY tests/bootstrap.php ./tests/

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm", "-F"]
