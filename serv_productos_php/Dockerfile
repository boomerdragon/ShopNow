FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Copy composer from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer configuration files
COPY serv_productos_php/composer.json ./
COPY serv_productos_php/composer.lock ./

# Install dependencies with better error handling
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-scripts 2>&1 | tee /tmp/composer-install.log && \
    (grep -i "error\|failed" /tmp/composer-install.log && exit 1 || echo "Composer install successful")

COPY serv_productos_php/ .

# Ensure .env exists for configuration
RUN if [ ! -f .env ]; then \
    echo "# Fat-Free Framework Configuration" > .env && \
    echo "DEBUG=3" >> .env && \
    echo "CACHE=folder=tmp/" >> .env && \
    echo "" >> .env && \
    echo "# JWT Configuration" >> .env && \
    echo "JWT_SECRET=${JWT_SECRET:-shopnow-secret-key-2024-change-in-production}" >> .env && \
    echo "JWT_ALGORITHM=HS256" >> .env && \
    echo "" >> .env && \
    echo "# Service Configuration" >> .env && \
    echo "SERVICE_PORT=8081" >> .env && \
    echo "SERVICE_NAME=productos" >> .env && \
    echo "DATA_FILE=../productos.csv" >> .env; \
    fi

RUN mkdir -p tmp && chmod 755 tmp

EXPOSE 8081

CMD ["php", "-S", "0.0.0.0:8081", "-t", "public"]
