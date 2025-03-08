# Use official PHP with required extensions
FROM php:8.0-fpm

# Set working directory
WORKDIR /var/www

# Install required dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    bash \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI globally as root
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Install PHP Redis extension (PhpRedis)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Ensure the correct PHP extensions are installed
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Create a non-root user for security
RUN useradd -ms /bin/bash appuser \
    && usermod --shell /bin/bash appuser \
    && chown -R appuser:appuser /var/www \
    && chown appuser:appuser /usr/local/bin/wp

# Switch to the non-root user
USER appuser

# Set environment variables for WP-CLI path
ENV WP_CLI_PATH="/var/www/wp"
RUN echo 'alias wp="wp --path=$WP_CLI_PATH"' >> ~/.bashrc

# Ensure Bash is used for the user
SHELL ["/bin/bash", "-c"]

# Set environment variables for WP-CLI path
ENV WP_CLI_PATH="/var/www/wp"
RUN echo 'alias wp="wp --path=$WP_CLI_PATH"' >> ~/.bashrc

# Ensure Bash is used for the user
SHELL ["/bin/bash", "-c"]

# Start PHP-FPM
CMD ["php-fpm"]
