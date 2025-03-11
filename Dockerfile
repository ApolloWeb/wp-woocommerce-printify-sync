FROM litespeedtech/openlitespeed:latest

# Set working directory to OpenLiteSpeed's web root
WORKDIR /var/www/vhosts/localhost/html/

# Install required PHP extensions for WordPress & Composer
RUN apt-get update && apt-get install -y \
    unzip curl git libpng-dev libjpeg-dev libfreetype6-dev \
    php-mysqli php-curl php-gd php-xml php-mbstring php-zip php-intl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the Composer project (expects a composer.json file in the build context)
COPY . /var/www/vhosts/localhost/html/

# Install WordPress using Composer
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Set proper permissions
RUN chown -R nobody:nobody /var/www/vhosts/localhost/html/ && chmod -R 755 /var/www/vhosts/localhost/html/

# Expose OpenLiteSpeed Ports
EXPOSE 8088 
