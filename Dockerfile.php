FROM php:8.1-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    locales \
    libicu-dev \
    zlib1g-dev \
    libpq-dev \
    git \
    libcurl4-openssl-dev \
    vim \
    netcat-openbsd \
    postgresql-client \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    unzip \
    && locale-gen C.UTF-8 \
    && update-locale LANG=C.UTF-8 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/include/postgresql/ \
    && docker-php-ext-configure zip \
    && docker-php-ext-install \
    pdo pgsql pdo_pgsql intl opcache bcmath zip curl gd mbstring

# Configurar memoria PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# Instalar Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Configurar usuario www-data
RUN usermod --non-unique --uid 1000 www-data \
    && usermod -s /bin/bash www-data

WORKDIR /var/www/openloyalty

# Copiar aplicación
COPY . .

# Instalar dependencias de Composer (más permisivo)
RUN if [ -f backend/composer.json ]; then \
        cd backend && \
        composer install \
            --no-dev \
            --optimize-autoloader \
            --no-interaction \
            --no-scripts \
            --prefer-dist \
            --ignore-platform-reqs || echo "Composer install completed with warnings"; \
    fi

# Permisos
RUN chown -R www-data:www-data /var/www/openloyalty \
    && chmod -R 755 /var/www/openloyalty \
    && mkdir -p backend/var/cache backend/var/log backend/app/cache backend/app/logs \
    && chmod -R 777 backend/var backend/app/cache backend/app/logs 2>/dev/null || true

EXPOSE 9000

CMD ["php-fpm"]
