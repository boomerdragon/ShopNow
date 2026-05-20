# ShopNow - Admin Productos Interface

A professional, decoupled CRUD UI for managing products in the ShopNow microservices platform. This interface communicates with the **Productos microservice** (running on port 8001) via REST API with JWT authentication.

## Features

- 🔐 **JWT Authentication** - Secure login integrated with the Productos service
- 📋 **List Products** - View all products with pricing and stock information
- ➕ **Create Products** - Add new products with validation
- ✏️ **Edit Products** - Update product information and pricing
- 🗑️ **Delete Products** - Deactivate products with confirmation
- 💰 **Price Management** - Track and manage product prices
- 📦 **Inventory Tracking** - Monitor stock levels
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile devices
- ✨ **Modern UI** - Built with Bootstrap 5 for a professional appearance
- 🔗 **Decoupled Architecture** - Runs independently from the backend service

## Architecture

```
┌─────────────────────────────────┐
│   Admin Productos Interface     │
│   (PHP + Bootstrap + jQuery)    │
│         Port 8080 (http)        │
└────────────┬────────────────────┘
             │ REST API + JWT
             │ (HTTP Requests)
             ▼
┌─────────────────────────────────┐
│  Productos Microservice (FastAPI)│
│         Port 8001               │
└─────────────────────────────────┘
```

## Prerequisites

- **PHP 7.4+** with built-in web server or Apache/Nginx
- **Productos microservice** running on `http://localhost:8001`
- **Web browser** (Chrome, Firefox, Safari, Edge)

## Installation

### 1. Clone or Download

```bash
cd /home/boomer/ITQ/SOA/ShopNow
# Admin interface is already in admin-productos/
```

### 2. Update Configuration (if needed)

Edit `config.php` to change the API endpoint:

```php
define('API_BASE_URL', 'http://localhost:8001'); // Change if service is on different host/port
```

### 3. Start PHP Built-in Web Server

```bash
cd admin-productos
php -S localhost:8080
```

Or with a specific directory:

```bash
php -S 127.0.0.1:8080 -t /home/boomer/ITQ/SOA/ShopNow/admin-productos
```

### 4. Access the Interface

Open your browser and navigate to:

```
http://localhost:8080
```

## Usage

### Login

1. Go to `http://localhost:8080`
2. Enter credentials:
   - **Username:** `admin`
   - **Password:** `password123`
3. Click "Iniciar Sesión" (Login)

### Dashboard (List Products)

- View all active products in a table
- See product details: ID, Name, Description, Price, Stock, Status
- Click **Edit** (pencil icon) to modify a product
- Click **Delete** (trash icon) to deactivate a product
- Click **"Nuevo Producto"** (New Product) to add a product

### Create Product

1. Click "Nuevo Producto" button
2. Fill in the form:
   - **Nombre** (Name): Min. 3 characters
   - **Descripción** (Description): Product details
   - **Precio** (Price): Product price in USD (must be > 0)
   - **Cantidad** (Stock): Units available (must be >= 0)
   - **Activo** (Active): Check to make product available
3. Click "Crear Producto" to submit

### Edit Product

1. Click the **Edit** button (pencil icon) next to a product
2. Modify the desired fields
3. Click "Guardar Cambios" (Save Changes) to update

### Delete Product

1. Click the **Delete** button (trash icon) next to a product
2. Confirm the deletion in the modal dialog
3. Product will be deactivated (soft delete)

## File Structure

```
admin-productos/
├── index.php                 # Login page
├── dashboard.php             # Product list view
├── create.php               # Create product form
├── edit.php                 # Edit product form
├── logout.php               # Logout handler
├── config.php               # Configuration and helper functions
├── css/
│   └── style.css           # Custom styles
├── js/
│   └── script.js           # Client-side utilities
├── Dockerfile              # Docker configuration
├── README.md               # This file
├── QUICK_START.md          # Quick start guide
└── DOCKER_DEPLOYMENT.md    # Docker deployment guide
```

## Configuration

### API Endpoint

The API endpoint is auto-detected based on the host:

```php
// In config.php
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:8080');
define('API_BASE_URL', $is_local ? 'http://localhost:8001' : 'https://shopnow-productos.onrender.com');
```

### Session Configuration

- **Session Name:** `admin_productos_session`
- **Session Timeout:** 3600 seconds (1 hour)
- **JWT Secret:** Change in production!

### API Timeout

- **Default:** 10 seconds

## Troubleshooting

### "Unable to connect to the Productos service"

- Verify the Productos service is running on port 8001
- Check the API_BASE_URL in config.php
- Ensure there are no firewall issues

### "Login failed. Invalid credentials"

- Verify username and password are correct
- Check that the Productos service is responding to login requests
- Review the Productos service logs

### "Product not found"

- Verify the product ID exists
- Check that the product has not been deleted
- Refresh the page and try again

## Security Notes

- ⚠️ Change `JWT_SECRET_KEY` in production
- Use HTTPS in production environments
- Never expose API keys or secrets in code
- Validate all input on both client and server sides

## Development

### Adding New Features

1. Update the relevant PHP file
2. Add new validation functions to `config.php` if needed
3. Update CSS in `css/style.css` for styling
4. Test in your browser

### API Integration

The interface uses curl to make API requests:

```php
$result = callAPI('/productos', 'GET', null, $token);
```

## License

This interface is part of the ShopNow microservices platform.

## Support

For issues or questions, refer to the main ShopNow documentation.
