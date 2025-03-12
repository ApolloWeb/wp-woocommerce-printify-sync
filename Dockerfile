FROM wordpress:latest

# Copy custom Nginx configuration
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && docker-php-ext-enable mysqli pdo pdo_mysql zip