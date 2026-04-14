# JWT Authentication Guide - ShopNow

## Overview

All endpoints in the ShopNow microservices are now protected by JWT (JSON Web Token) authentication. Each service has a **login endpoint** that generates a token, which must then be used for all subsequent requests.

---

## Default Credentials

All services use the same demo credentials (for testing/development):

```
Username: admin
Password: password123
```

⚠️ **IMPORTANT**: Change these credentials in production!

---

## How to Authenticate

### Step 1: Get a Token

Send a POST request to any service's login endpoint:

```bash
curl -X POST "http://localhost:8000/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

**Response:**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJhZG1pbiIsInNlcnZpY2UiOiJjbGllbnRlcyIsImV4cCI6MTcyMzQwMDAwMH0.xxx",
  "token_type": "bearer"
}
```

### Step 2: Use Token in Requests

Include the token in the `Authorization` header for all protected endpoints:

```bash
curl -X GET "http://localhost:8000/clientes" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE"
```

---

## Interactive Testing with Swagger

1. Start all services:
   ```bash
   bash shopnow.sh start
   ```

2. Open Swagger UI:
   - Clientes: http://localhost:8000/docs
   - Productos: http://localhost:8001/docs
   - Pedidos: http://localhost:8002/docs
   - Inventario: http://localhost:8003/docs

3. Click the **Authorize** button at the top of the page
4. Call the `/login` endpoint to get a token
5. Click **Authorize** again and paste the token
6. All endpoints are now available for testing

---

## Available Endpoints by Service

### Clientes (Port 8000)
- `POST /login` - Get authentication token
- `GET /clientes` - List all customers
- `POST /clientes` - Create new customer
- `DELETE /clientes/{id_cliente}` - Deactivate customer
- `PATCH /clientes/{id_cliente}` - Update customer partially

### Productos (Port 8001)
- `POST /login` - Get authentication token
- `GET /productos` - List all products
- `POST /productos` - Create new product
- `DELETE /productos/{id_producto}` - Deactivate product
- `PATCH /productos/{id_producto}` - Update product partially

### Pedidos (Port 8002)
- `POST /login` - Get authentication token
- `GET /pedidos` - List all orders
- `POST /pedidos` - Create new order (validates across services)

### Inventario (Port 8003)
- `POST /login` - Get authentication token
- `GET /inventario` - Get full inventory
- `GET /inventario/{id_producto}` - Check stock for specific product
- `POST /inventario` - Register product in inventory
- `POST /inventario/descontar` - Reduce stock (used by orders)
- `POST /inventario/agregar` - Add stock (restock)

---

## Token Expiration

Tokens expire after **8 hours** (480 minutes). When a token expires, you'll get a 401 error:

```json
{
  "detail": "Token ha expirado"
}
```

Simply request a new token using the login endpoint.

---

## Security Notes

### Current Configuration (Development Only)
- Hard-coded demo credentials (admin/password123)
- Shared secret key across all services
- 8-hour token expiration

### Production Recommendations
1. **Move credentials to environment variables** or a secure database
2. **Use separate secret keys** for each service
3. **Implement role-based access control (RBAC)** with different token types
4. **Shorten token expiration** to 15-60 minutes
5. **Add token refresh mechanism** for long sessions
6. **Use HTTPS** to protect tokens in transit
7. **Implement password hashing** (bcrypt) for user authentication

---

## Example Workflows

### Complete Order Flow with JWT

```bash
# 1. Get token from any service
TOKEN=$(curl -X POST "http://localhost:8000/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}' | jq -r '.access_token')

# 2. Create a customer
curl -X POST "http://localhost:8000/clientes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "correo": "juan@example.com",
    "direccion": "Calle 123",
    "telefono": "4421234567",
    "activo": true
  }'

# 3. Get products list
curl -X GET "http://localhost:8001/productos" \
  -H "Authorization: Bearer $TOKEN"

# 4. Check inventory
curl -X GET "http://localhost:8003/inventario" \
  -H "Authorization: Bearer $TOKEN"

# 5. Create an order (validates across services using JWT)
curl -X POST "http://localhost:8002/pedidos" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id_cliente": 1,
    "id_producto": 1,
    "cantidad": 2
  }'
```

---

## Configuration

### To Change Credentials
Edit [auth.py](auth.py) and update:
```python
if credentials.username != "admin" or credentials.password != "password123":
```

### To Change Token Expiration
Edit [auth.py](auth.py) and update:
```python
EXPIRATION_MINUTES = 480  # Change to desired minutes
```

### To Change Secret Key
Edit [auth.py](auth.py) and update:
```python
SECRET_KEY = "shopnow-secret-key-2024-change-in-production"
```

---

## Troubleshooting

**Error: "Token inválido"**
- Token was tampered with or generated with a different secret key
- Solution: Get a fresh token from `/login`

**Error: "Token ha expirado"**
- Token is older than 8 hours
- Solution: Get a fresh token from `/login`

**Error: "Credenciales inválidas"**
- Username or password is wrong
- Solution: Use `admin`/`password123` for demo

**Error: 422 Validation Error**
- Missing or malformed **Authorization** header
- Solution: Include header: `Authorization: Bearer YOUR_TOKEN`

---

## Files Modified

- [auth.py](auth.py) - New shared authentication module
- [serv_clientes.py](serv_clientes.py) - Added JWT protection
- [serv_productos.py](serv_productos.py) - Added JWT protection
- [serv_pedidos.py](serv_pedidos.py) - Added JWT protection
- [serv_inventario.py](serv_inventario.py) - Added JWT protection
