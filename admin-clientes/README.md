# ShopNow - Admin Clientes Interface

A professional, decoupled CRUD UI for managing customers in the ShopNow microservices platform. This interface communicates with the **Clientes microservice** (running on port 8000) via REST API with JWT authentication.

## Features

- 🔐 **JWT Authentication** - Secure login integrated with the Clientes service
- 📋 **List Customers** - View all active customers with status indicators
- ➕ **Create Customers** - Add new customers with validation
- ✏️ **Edit Customers** - Update customer information
- 🗑️ **Delete Customers** - Remove customers with confirmation
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile devices
- ✨ **Modern UI** - Built with Bootstrap 5 for a professional appearance
- 🔗 **Decoupled Architecture** - Runs independently from the backend service

## Architecture

```
┌─────────────────────────────────┐
│     Admin Clientes Interface    │
│   (PHP + Bootstrap + jQuery)    │
│         Port 8080 (http)        │
└────────────┬────────────────────┘
             │ REST API + JWT
             │ (HTTP Requests)
             ▼
┌─────────────────────────────────┐
│   Clientes Microservice (FastAPI)│
│         Port 8000               │
└─────────────────────────────────┘
```

## Prerequisites

- **PHP 7.4+** with built-in web server or Apache/Nginx
- **Clientes microservice** running on `http://localhost:8000`
- **Web browser** (Chrome, Firefox, Safari, Edge)

## Installation

### 1. Clone or Download

```bash
cd /home/boomer/ITQ/SOA/ShopNow
# Admin interface is already in admin-clientes/
```

### 2. Update Configuration (if needed)

Edit `config.php` to change the API endpoint:

```php
define('API_BASE_URL', 'http://localhost:8000'); // Change if service is on different host/port
```

### 3. Start PHP Built-in Web Server

```bash
cd admin-clientes
php -S localhost:8080
```

Or with a specific directory:

```bash
php -S 127.0.0.1:8080 -t /home/boomer/ITQ/SOA/ShopNow/admin-clientes
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

### Dashboard (List Customers)

- View all active customers in a table
- See customer details: ID, Name, Email, Phone, Status
- Click **Edit** (pencil icon) to modify a customer
- Click **Delete** (trash icon) to remove a customer
- Click **"Nuevo Cliente"** (New Customer) to add a customer

### Create Customer

1. Click "Nuevo Cliente" button
2. Fill in the form:
   - **Nombre** (Name): Min. 3 characters
   - **Correo** (Email): Valid email address (must be unique)
   - **Dirección** (Address): Full address
   - **Teléfono** (Phone): 10-digit number
   - **Activo** (Active): Toggle to make customer active/inactive
3. Click "Crear Cliente" to save

### Edit Customer

1. Click the pencil icon next to a customer
2. Modify the information
3. Click "Actualizar Cliente" to save changes
4. Or click "Cancelar" to discard changes

### Delete Customer

1. Click the trash icon next to a customer
2. Confirm deletion in the modal dialog
3. Customer will be permanently removed

### Logout

Click "Cerrar Sesión" (Logout) in the top-right corner

## File Structure

```
admin-clientes/
├── index.php           # Login page
├── config.php          # Configuration and helpers
├── dashboard.php       # Customer list view
├── create.php          # Create customer form
├── edit.php            # Edit customer form
├── logout.php          # Logout handler
├── css/
│   └── style.css       # Custom Bootstrap 5 styles
├── js/
│   └── script.js       # Client-side utilities
└── README.md           # This file
```

## Configuration

### API Configuration

In `config.php`:

```php
define('API_BASE_URL', 'http://localhost:8000');  // Clientes service URL
define('API_TIMEOUT', 10);                         // Request timeout in seconds
define('SESSION_TIMEOUT', 3600);                   // Session timeout in seconds (1 hour)
```

### Database Validation Rules

The form validates according to the Clientes service requirements:

- **Nombre**: Minimum 3 characters
- **Correo**: Valid email format (must be unique)
- **Dirección**: Any non-empty text
- **Teléfono**: Exactly 10 digits
- **Activo**: Boolean (active/inactive)

## API Endpoints Used

The interface communicates with these Clientes service endpoints:

- `POST /login` - Authenticate and get JWT token
- `GET /clientes` - List all active customers
- `GET /clientes/{id}` - Get single customer
- `POST /clientes` - Create new customer
- `PUT /clientes/{id}` - Update customer
- `DELETE /clientes/{id}` - Delete customer

## Error Handling

The interface handles common errors gracefully:

- **Connection errors**: Shows message if Clientes service is down
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
