# ShopNow Admin Productos - Docker Deployment Guide

Deploy the Admin Productos interface using Docker and Docker Compose.

## Prerequisites

- Docker and Docker Compose installed
- Productos microservice running (via Docker or directly)

## Option 1: Docker Run (Single Container)

### Build the image

```bash
cd /home/boomer/ITQ/SOA/ShopNow/admin-productos
docker build -t shopnow-admin-productos .
```

### Run the container

```bash
docker run \
  --name admin-productos \
  -p 8080:80 \
  -e API_BASE_URL="http://localhost:8001" \
  shopnow-admin-productos
```

### Access the interface

Navigate to: `http://localhost:8080`

### Stop the container

```bash
docker stop admin-productos
docker rm admin-productos
```

## Option 2: Docker Compose (Full Stack)

### Create docker-compose.yml

```yaml
version: '3.8'

services:
  admin-productos:
    build:
      context: ./admin-productos
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    environment:
      - API_BASE_URL=http://productos:8001
    depends_on:
      - productos
    networks:
      - shopnow

  productos:
    build:
      context: ./serv_productos
      dockerfile: Dockerfile
    ports:
      - "8001:8001"
    networks:
      - shopnow

networks:
  shopnow:
    driver: bridge
```

### Start the full stack

```bash
cd /home/boomer/ITQ/SOA/ShopNow
docker-compose up -d
```

### Access the interface

Navigate to: `http://localhost:8080`

### View logs

```bash
docker-compose logs -f admin-productos
```

### Stop the stack

```bash
docker-compose down
```

## Environment Variables

You can customize the behavior using environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| API_BASE_URL | http://localhost:8001 | Productos service URL |
| PHP_TIMEZONE | UTC | PHP timezone setting |
| SESSION_TIMEOUT | 3600 | Session timeout in seconds |

### Using environment variables

Create a `.env` file:

```
API_BASE_URL=http://productos:8001
PHP_TIMEZONE=America/New_York
```

Then in `docker-compose.yml`:

```yaml
services:
  admin-productos:
    env_file:
      - .env
```

## Production Deployment

### Using Docker Hub Registry

```bash
# Tag image
docker tag shopnow-admin-productos myregistry/shopnow-admin-productos:1.0

# Push to registry
docker push myregistry/shopnow-admin-productos:1.0

# Pull and run
docker run -p 8080:80 myregistry/shopnow-admin-productos:1.0
```

### Using Kubernetes

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: admin-productos
spec:
  replicas: 2
  selector:
    matchLabels:
      app: admin-productos
  template:
    metadata:
      labels:
        app: admin-productos
    spec:
      containers:
      - name: admin-productos
        image: shopnow-admin-productos:1.0
        ports:
        - containerPort: 80
        env:
        - name: API_BASE_URL
          value: http://productos:8001
        livenessProbe:
          httpGet:
            path: /
            port: 80
          initialDelaySeconds: 10
          periodSeconds: 10
---
apiVersion: v1
kind: Service
metadata:
  name: admin-productos-service
spec:
  selector:
    app: admin-productos
  ports:
    - protocol: TCP
      port: 80
      targetPort: 80
  type: LoadBalancer
```

## Security Best Practices

1. **Use HTTPS in production**
   ```bash
   docker run -p 443:443 -v /path/to/certs:/etc/ssl/certs shopnow-admin-productos
   ```

2. **Limit port exposure**
   ```bash
   docker run -p 127.0.0.1:8080:80 shopnow-admin-productos
   ```

3. **Use environment secrets**
   ```bash
   docker run \
     --env-file /path/to/secrets.env \
     shopnow-admin-productos
   ```

4. **Run as non-root user**
   Add to Dockerfile:
   ```dockerfile
   RUN useradd -m -u 1000 phpuser
   USER phpuser
   ```

## Monitoring

### Health Check

Add to Dockerfile:

```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/index.php || exit 1
```

### Logs

```bash
# View logs
docker logs admin-productos

# Follow logs
docker logs -f admin-productos

# Tail last 100 lines
docker logs --tail 100 admin-productos
```

## Performance Optimization

### Enable caching

Update Dockerfile:

```dockerfile
RUN docker-php-ext-install opcache
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini
```

### Use Alpine Linux for smaller image

```dockerfile
FROM php:8.2-apache-alpine
```

## Troubleshooting

### Port already in use

```bash
# Find process using port 8080
lsof -i :8080

# Use different port
docker run -p 9090:80 shopnow-admin-productos
```

### Can't connect to Productos service

1. Check service name in docker-compose.yml
2. Verify API_BASE_URL environment variable
3. Check Docker network: `docker network ls`
4. Test connection: `docker exec admin-productos curl http://productos:8001/docs`

### Permission denied errors

```bash
# Check file permissions
docker exec admin-productos ls -la /var/www/html

# Fix permissions
docker exec admin-productos chown -R www-data:www-data /var/www/html
```

## Useful Commands

```bash
# List running containers
docker ps

# Access container shell
docker exec -it admin-productos /bin/bash

# View container stats
docker stats admin-productos

# Inspect container
docker inspect admin-productos

# Remove image
docker rmi shopnow-admin-productos

# Prune unused images
docker image prune -a
```

## References

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP Docker Official Images](https://hub.docker.com/_/php)
- [Apache Docker Official Images](https://hub.docker.com/_/httpd)
