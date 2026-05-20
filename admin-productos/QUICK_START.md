# ShopNow Admin Productos - Quick Start Guide

Get the Admin Productos interface running in minutes!

## Prerequisites

- PHP 7.4 or higher
- Productos microservice running on `http://localhost:8001`
- A web browser

## Option 1: Using PHP Built-in Web Server (Recommended)

### Step 1: Navigate to the admin-productos directory

```bash
cd /home/boomer/ITQ/SOA/ShopNow/admin-productos
```

### Step 2: Start the PHP development server

```bash
php -S localhost:8080
```

You should see:
```
Development Server (http://localhost:8080) started
```

### Step 3: Open in browser

Navigate to: `http://localhost:8080`

### Step 4: Login

- **Username:** `admin`
- **Password:** `password123`

## Option 2: Using Docker

### Step 1: Build the Docker image

```bash
cd /home/boomer/ITQ/SOA/ShopNow/admin-productos
docker build -t shopnow-admin-productos .
```

### Step 2: Run the container

```bash
docker run -p 8080:80 shopnow-admin-productos
```

### Step 3: Access the interface

Navigate to: `http://localhost:8080`

## Option 3: Using Apache/Nginx

See [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md) for production setup instructions.

## Accessing the Interface

Once the server is running, you'll see:

### Dashboard
- **URL:** `http://localhost:8080/dashboard.php`
- **Shows:** All products with pricing and stock info

### Create Product
- **URL:** `http://localhost:8080/create.php`
- **Allows:** Add new products to the catalog

### Edit Product
- **URL:** `http://localhost:8080/edit.php?id=1`
- **Allows:** Modify existing product information

## Troubleshooting

### Port 8080 already in use

Use a different port:
```bash
php -S localhost:9090
```

Then access: `http://localhost:9090`

### Can't connect to Productos service

1. Verify service is running:
```bash
curl http://localhost:8001/docs
```

2. Check `config.php` - API_BASE_URL should be correct

3. If running on different machine, update config:
```php
define('API_BASE_URL', 'http://<service-ip>:8001');
```

### Login fails

1. Verify Productos service is responding
2. Check username/password (default: admin/password123)
3. Check Productos service logs for errors

## Features Overview

✅ **View all products** - See the complete product catalog
✅ **Add products** - Create new items with name, description, price, stock
✅ **Edit products** - Update product information
✅ **Deactivate products** - Soft delete functionality
✅ **Manage prices** - Update pricing for all products
✅ **Track inventory** - Monitor stock levels
✅ **Responsive design** - Works on mobile and desktop

## Next Steps

1. ✅ Verify the interface is working
2. ✅ Test product creation
3. ✅ Test product editing
4. ✅ Configure for production (if needed)
5. ✅ Deploy to your server

## Useful Links

- [Full Documentation](./README.md)
- [Docker Deployment](./DOCKER_DEPLOYMENT.md)
- [Productos Microservice Docs](../serv_productos/README.md)
- [ShopNow Architecture](../copilot-instructions.md)

---

**Need help?** Check the README.md for detailed documentation or review the Productos service logs.
