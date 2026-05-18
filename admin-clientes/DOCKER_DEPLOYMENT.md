# Docker & Render Deployment Guide for Admin Clientes

This guide explains how to build and deploy the Admin Clientes PHP application using Docker and Render.

## Local Development with Docker

### Build the Docker Image

```bash
cd /home/boomer/ITQ/SOA/ShopNow
docker build -t shopnow-admin-clientes:latest ./admin-clientes
```

### Run with docker-compose (Recommended)

```bash
# Start all services including admin-clientes
docker-compose up -d

# Access the admin interface at:
# http://localhost:8080
```

### Run Docker Container Directly

```bash
docker run -d \
  --name shopnow-admin-clientes \
  -p 8080:8080 \
  -v $(pwd)/admin-clientes:/app \
  shopnow-admin-clientes:latest
```

## Configuration for Deployment

### Environment Variables

The admin-clientes service uses the following environment variables:

- **PORT**: Port number (default: 8080)
- **SERVICE_NAME**: Service identifier (default: admin-clientes)
- **API_BASE_URL**: Set in config.php (default: https://shopnow-clientes.onrender.com)

### Updating API Base URL for Different Environments

**Local Development** - Edit `config.php`:
```php
define('API_BASE_URL', 'http://localhost:8000');
```

**Render Production** - Remains as:
```php
define('API_BASE_URL', 'https://shopnow-clientes.onrender.com');
```

## Render Deployment

### Prerequisites

1. GitHub repository with ShopNow project
2. Render account with connected GitHub
3. Other ShopNow services already deployed to Render

### Deployment Steps

1. **Push code to GitHub**
   ```bash
   git add .
   git commit -m "Add Docker support for admin-clientes"
   git push origin main
   ```

2. **Render Configuration** (automatically configured via render.yaml)
   
   The `render.yaml` file already includes the admin-clientes service configuration:
   ```yaml
   - type: web
     name: shopnow-admin-clientes
     runtime: docker
     plan: free
     dockerfilePath: admin-clientes/Dockerfile
     envVars:
       - key: PORT
         value: 8080
       - key: SERVICE_NAME
         value: admin-clientes
   ```

3. **Deploy via Render Dashboard**
   
   - Go to [Render Dashboard](https://dashboard.render.com)
   - Click "New +" → "Web Service"
   - Connect your GitHub repository
   - Select the ShopNow repository
   - Render will automatically detect the `render.yaml` configuration
   - Click "Deploy"

4. **Verify Deployment**
   
   Once deployed, access the admin interface at:
   ```
   https://shopnow-admin-clientes.onrender.com
   ```

## Docker Image Details

### Dockerfile Specifications

- **Base Image**: `php:8.3-cli` - Lightweight PHP CLI image
- **Exposed Port**: 8080 (HTTP)
- **Entry Point**: PHP built-in web server
- **Size**: ~200MB (minimal, no composer dependencies)

### Key Features

- ✅ Minimal footprint with PHP 8.3
- ✅ No external dependencies (static files + PHP)
- ✅ Built-in web server for development
- ✅ Session storage support
- ✅ CORS-ready for API communication

## Troubleshooting

### Container won't start

```bash
# Check logs
docker logs shopnow-admin-clientes

# Rebuild image
docker build --no-cache -t shopnow-admin-clientes:latest ./admin-clientes
```

### Port 8080 already in use

```bash
# Find and kill process
lsof -ti :8080 | xargs kill -9

# Or use a different port
docker run -p 8888:8080 shopnow-admin-clientes:latest
```

### API connection issues

1. Verify the Clientes service is running:
   ```bash
   curl https://shopnow-clientes.onrender.com/docs
   ```

2. Check config.php API_BASE_URL is correct

3. Verify CORS is enabled in Clientes service

### Session not persisting

The application uses PHP sessions stored in `/tmp/sessions`. This is normal for containerized environments; use JWT tokens for persistence across deployments.

## Performance Optimization

For production use on Render, consider:

1. **Use a proper web server** (Apache/Nginx) instead of PHP built-in server
   - Modify Dockerfile to install and configure Apache/Nginx
   - This would improve performance and concurrency

2. **Enable OpCache** for PHP performance:
   ```dockerfile
   RUN docker-php-ext-enable opcache
   ```

3. **Add caching headers** in PHP response headers:
   ```php
   header('Cache-Control: public, max-age=3600');
   ```

## Related Documentation

- [README.md](./README.md) - Admin Clientes features and usage
- [QUICK_START.md](./QUICK_START.md) - Quick start guide
- [Render Deployment Guide](../RENDER_DEPLOYMENT_GUIDE.md) - Full deployment information
- [Docker Compose Configuration](../docker-compose.yml) - Local development setup
