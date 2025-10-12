FROM php:8.2-fpm

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    default-mysql-client \
    nginx \
    supervisor \
    cron \
    ca-certificates \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    intl \
    mbstring \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && update-ca-certificates

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files first
COPY . .

# Install dependencies
ARG APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN if [ "$APP_ENV" = "dev" ]; then \
        composer install --optimize-autoloader --no-interaction --no-scripts && \
        composer dump-autoload --optimize --classmap-authoritative; \
    else \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts && \
        composer dump-autoload --optimize --no-dev --classmap-authoritative; \
    fi

# Configure Nginx
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configure Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure PHP-FPM
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Setup PteroCA cron job
RUN echo "* * * * * www-data php /app/bin/console app:cron-job-schedule >> /dev/null 2>&1" > /etc/cron.d/pteroca-cron \
    && chmod 0644 /etc/cron.d/pteroca-cron \
    && crontab -u www-data /etc/cron.d/pteroca-cron

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && mkdir -p var public/uploads \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 775 var public/uploads

# Expose port
EXPOSE 80

# Default command
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
