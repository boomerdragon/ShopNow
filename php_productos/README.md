# ShopNow Productos Service - PHP Edition

Fat-Free Framework powered microservice that replicates the Python `serv_productos` functionality. Manages the product catalog with persistent CSV storage.

## Setup

### Prerequisites
- PHP 7.4+
- Composer
- The parent `productos.csv` file (shared with Python version)

### Installation

```bash
# Navigate to the php_productos directory
cd php_productos

# Install dependencies
composer install

# Copy environment template
cp .env.example .env

# Create temp folder for cache
mkdir -p tmp
chmod 755 tmp
```

### Running the Service

```bash
# Option 1: Using composer script
composer start

# Option 2: Direct PHP server
php -S localhost:8081 -t public

# Option 3: Using Nginx/Apache (in production)
# Point document root to: /path/to/php_productos/public
```

The service will be available at `http://localhost:8081`

## API Endpoints

All endpoints (except `/login`) require JWT token in `Authorization: Bearer <token>` header.

### Authentication
- **POST** `/login` - Get JWT token
  ```bash
  curl -X POST http://localhost:8081/login \
    -H "Content-Type: application/json" \
    -d '{"username":"admin","password":"password123"}'
  ```

### Products
- **GET** `/productos` - List all products
- **POST** `/productos` - Create new product
- **GET** `/productos/{id}` - Get single product
- **PUT** `/productos/{id}` - Update product
- **DELETE** `/productos/{id}` - Mark product as inactive

## CSV Storage

The service reads and writes to `../productos.csv` (shared with the Python version). This allows both services to operate on the same data.

**File Format:**
```csv
id_producto,descripcion,precio,activo
1,Laptop Gamer,15000.0,True
2,Mouse Inalámbrico,500.0,True
```

## Configuration

Edit `.env` file to customize:
- `DEBUG` - Debug level (0-3)
- `SERVICE_PORT` - Port to run on
- `JWT_SECRET` - JWT secret key (⚠️ change in production)
- `RABBITMQ_*` - RabbitMQ settings (optional)

## Key Differences from Python Version

- Uses Fat-Free Framework (simpler, more transparent)
- Native PHP CSV handling (no external library)
- JWT tokens still compatible with Python version
- Same CSV format = fully interoperable data

## Development

```bash
# Run with auto-reload (requires watchdog or similar)
php -S localhost:8081 -t public -d display_errors=1

# Check code quality
composer test

# View error logs
tail -f tmp/error.log
```

## Troubleshooting

**Port 8081 already in use:**
```bash
# Find process using port
lsof -i :8081

# Use different port
php -S localhost:8091 -t public
```

**CSV file not found:**
- Ensure `productos.csv` exists in parent directory
- Service will create it automatically on first run

**JWT errors:**
- Verify `JWT_SECRET` is set in `.env`
- Token expires after 24 hours
- Get new token from `/login`

## Docker (Optional)

```dockerfile
FROM php:8.1-cli
WORKDIR /app
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . .
RUN composer install
EXPOSE 8081
CMD ["php", "-S", "0.0.0.0:8081", "-t", "public"]
```

Run: `docker build -t shopnow-productos-php . && docker run -p 8081:8081 -v $(pwd)/..:/app/.. shopnow-productos-php`

---

**Created**: 14 de abril de 2026  
**Framework**: Fat-Free 3.8.3  
**PHP**: 7.4+
