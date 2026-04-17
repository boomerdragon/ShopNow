FROM php:8.1-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN mkdir -p tmp && chmod 755 tmp

EXPOSE 8081

CMD ["php", "-S", "0.0.0.0:8081", "-t", "public"]
