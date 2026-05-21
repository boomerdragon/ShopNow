# ShopNow - Admin Inventario Interface

A professional, decoupled inventory management UI for the ShopNow microservices platform. This interface communicates with the **Inventario microservice** (running on port 8003) via REST API with JWT authentication.

## Features

- 🔐 **JWT Authentication** - Secure login integrated with the Inventario service
- 📦 **View Inventory** - See all items with product ID and quantity
- ➕ **Add Items** - Register new products with initial stock
- ✏️ **Edit Items** - Update stock quantities
- 🗑️ **Delete Items** - Remove products from inventory
- 📊 **Stock Status** - Visual indicators for inventory levels (Available/Low/Out of Stock)
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile devices
- ✨ **Modern UI** - Built with Bootstrap 5 for a professional appearance
- 🔗 **Decoupled Architecture** - Runs independently from the backend service

## Architecture

```
┌─────────────────────────────────┐
│   Admin Inventario Interface    │
│   (PHP + Bootstrap + jQuery)    │
│         Port 8080 (http)        │
└────────────┬────────────────────┘
             │ REST API + JWT
             │ (HTTP Requests)
             ▼
┌─────────────────────────────────┐
│ Inventario Microservice (FastAPI)│
│         Port 8003               │
└─────────────────────────────────┘
```

## Prerequisites

- **PHP 7.4+** with built-in web server or Apache/Nginx
- **Inventario microservice** running on `http://localhost:8003`
- **Web browser** (Chrome, Firefox, Safari, Edge)

## Installation

### 1. Clone or Download

```bash
cd /home/boomer/ITQ/SOA/ShopNow
# Admin interface is already in admin-inventario/
```

### 2. Update Configuration (if needed)

Edit `config.php` to change the API endpoint:

```php
define('API_BASE_URL', 'http://localhost:8003'); // Change if service is on different host/port
```

### 3. Start PHP Built-in Web Server

```bash
cd admin-inventario
php -S localhost:8080
```

Or with a specific directory:

```bash
php -S 127.0.0.1:8080 -t /home/boomer/ITQ/SOA/ShopNow/admin-inventario
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

### Dashboard (Inventory List)

- View all inventory items in a table
- See item details: Product ID, Quantity, Stock Status
- Click **Edit** (pencil icon) to modify quantity
- Click **Delete** (trash icon) to remove item
- Click **"Nuevo Artículo"** (New Item) to add inventory

### Stock Status Indicators

- 🟢 **Stock Disponible** (Available): Quantity > 10 units
- 🟡 **Stock Bajo** (Low): Quantity between 1-10 units
- 🔴 **Agotado** (Out of Stock): Quantity = 0 units

### Create Inventory Item

1. Click "Nuevo Artículo" button
2. Fill in the form:
   - **ID del Producto** (Product ID): Positive integer (must reference valid product)
   - **Cantidad en Stock** (Quantity): Number from 1 to 999,999
3. Click "Crear Artículo" to save

### Edit Inventory Item

1. Click the pencil icon next to an item
2. Modify the quantity
3. Click "Actualizar Artículo" to save changes
4. Or click "Cancelar" to discard changes

### Delete Inventory Item

1. Click the trash icon next to an item
2. Confirm deletion in the modal dialog
3. Item will be permanently removed from inventory

### Logout

Click "Cerrar Sesión" (Logout) in the top-right corner

## File Structure

```
admin-inventario/
├── index.php           # Login page
├── config.php          # Configuration and helpers
├── dashboard.php       # Inventory list view
├── create.php          # Create inventory form
├── edit.php            # Edit inventory form
├── logout.php          # Logout handler
├── css/
│   └── style.css       # Custom Bootstrap 5 styles
├── js/
│   └── script.js       # Client-side utilities
├── Dockerfile          # Docker container configuration
└── README.md           # This file
```

## Configuration

### API Configuration

In `config.php`:

```php
define('API_BASE_URL', 'http://localhost:8003');  // Inventario service URL
define('API_TIMEOUT', 10);                         // Request timeout in seconds
define('SESSION_TIMEOUT', 3600);                   // Session timeout in seconds (1 hour)
```

### Validation Rules

The form validates according to the Inventario service requirements:

- **ID Producto**: Positive integer
- **Cantidad**: Integer from 1 to 999,999

## API Endpoints Used

The interface communicates with these Inventario service endpoints:

- `POST /login` - Authenticate and get JWT token
- `GET /inventario` - List all inventory items
- `POST /inventario` - Create new inventory item
- `PATCH /inventario/{id}` - Update item quantity
- `DELETE /inventario/{id}` - Delete inventory item

## Error Handling

The interface handles common errors gracefully:

- **Connection errors**: Shows message if Inventario service is down
- **Validation errors**: Displays field-level errors in forms
- **Authentication errors**: Redirects to login if token expires
- **API errors**: Shows user-friendly error messages

## Security Considerations

⚠️ **Important for Production:**

1. **Change JWT Secret Key** in `config.php`:
   ```php
   define('JWT_SECRET_KEY', 'your-unique-secret-key-here');
   ```

2. **Use HTTPS** in production:
   ```php
   define('API_BASE_URL', 'https://your-domain.com/api');
   ```

3. **Protect the directory** with authentication in your web server
   - Apache `.htaccess`
   - Nginx configuration
   - Docker secrets

## Docker Deployment

See `DOCKER_DEPLOYMENT.md` for containerized deployment instructions.

## Troubleshooting

### Connection Issues
- Verify Inventario service is running: `curl http://localhost:8003/docs`
- Check `API_BASE_URL` in `config.php`
- Ensure port 8003 is not blocked

### Login Failures
- Check credentials with Inventario service
- Verify JWT token configuration

### Port Conflicts
```bash
# Check what's using port 8080
lsof -i :8080

# Use a different port
php -S localhost:8081
```

4. **Set secure session options** in production

5. **Keep credentials secure** - don't store in code

## Troubleshooting

### "Unable to connect to the Clientes service"

- Verify Clientes service is running: `curl http://localhost:8000/docs`
- Check if port 8000 is correct in `config.php`
- Verify firewall allows localhost connections

### "Login failed"

- Verify credentials are correct
- Check Clientes service logs for authentication errors
- Ensure JWT token generation is working

### "Cliente no encontrado" (Customer not found)

- Refresh the dashboard to see updated list
- Customer may have been deleted by another user
- Check service logs

### Forms not submitting

- Check browser console for JavaScript errors
- Verify all required fields are filled
- Check API_BASE_URL is correct
- Verify Clientes service is responding

### Session expires immediately

- Check `SESSION_TIMEOUT` value in `config.php`
- Verify JWT token from Clientes service is valid
- Check PHP session configuration

## Development

### Add New Fields

To add a new customer field:

1. Update Clientes service schema (in FastAPI)
2. Add field to all forms in `create.php` and `edit.php`
3. Add validation in both forms
4. Update table column in `dashboard.php`

### Style Customization

Modify colors and styles in `css/style.css`:

```css
:root {
    --primary-color: #667eea;    /* Change primary color */
    --secondary-color: #764ba2;  /* Change secondary color */
}
```

### Add JavaScript Validation

Update `js/script.js` for additional client-side validation

## License

Part of the ShopNow microservices platform - Educational use

## Support

For issues or questions:
- Check Clientes service documentation
- Review error messages in browser console and PHP logs
- Verify all services are running correctly
