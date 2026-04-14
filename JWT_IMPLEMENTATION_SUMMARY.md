# ShopNow JWT Authentication - Implementation Summary

## What Was Implemented

✅ **Complete JWT Authentication** across all 4 microservices:
- Clientes (Port 8000)
- Productos (Port 8001)
- Pedidos (Port 8002)
- Inventario (Port 8003)

---

## Changes Made

### 1. Created [auth.py](auth.py) - Shared Authentication Module
- JWT token creation with `create_access_token()`
- Token verification dependency `verify_token()` for FastAPI
- Configurable expiration (480 minutes / 8 hours default)
- Shared `SECRET_KEY` across all services

### 2. Updated All 4 Services

#### Added to each service:
✅ Import auth module
```python
from auth import verify_token, create_access_token
```

✅ Login endpoint (POST /login)
- Accepts `username` and `password`
- Returns JWT `access_token` valid for 8 hours
- Default demo credentials: `admin` / `password123`

✅ Protected all business endpoints
- Added `Depends(verify_token)` to GET, POST, DELETE, PATCH endpoints
- Only `/login` endpoint is public (no authentication required)

#### Pedidos Service Special Handling
✅ Service-to-service authentication
- Generates its own `SERVICE_TOKEN` for internal calls
- All HTTP requests to Clientes, Productos, and Inventario include JWT headers
- Ensures inter-service communication continues to work

---

## Quick Start Testing

```bash
# 1. Start services
bash shopnow.sh start

# 2. Test authentication
curl -X POST "http://localhost:8000/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'

# Example response:
# {
#   "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
#   "token_type": "bearer"
# }

# 3. Use token in requests
TOKEN="<your_token_here>"
curl -X GET "http://localhost:8000/clientes" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Files Changed

| File | Changes |
|------|---------|
| **auth.py** | NEW - JWT utilities module |
| **serv_clientes.py** | Added JWT imports, login endpoint, token dependency on all endpoints |
| **serv_productos.py** | Added JWT imports, login endpoint, token dependency on all endpoints |
| **serv_pedidos.py** | Added JWT imports, login endpoint, service token generation, auth headers on internal calls |
| **serv_inventario.py** | Added JWT imports, login endpoint, token dependency on all endpoints |
| **JWT_USAGE_GUIDE.md** | NEW - Complete usage documentation |

---

## Security Configuration

### Current (Development)
```python
# auth.py
SECRET_KEY = "shopnow-secret-key-2024-change-in-production"
ALGORITHM = "HS256"
EXPIRATION_MINUTES = 480  # 8 hours
```

### Default Credentials
```python
username: "admin"
password: "password123"
```

---

## What's Protected

### ✅ All Endpoints Now Require JWT Except:
- `POST /login` - Public endpoint to get token
- Swagger UI documentation (`/docs`) - Can browse but can't execute protected endpoints without token

### ✅ Examples of Protected Endpoints:
- `GET /clientes` - Requires token
- `POST /clientes` - Requires token  
- `DELETE /clientes/{id_cliente}` - Requires token
- `PATCH /clientes/{id_cliente}` - Requires token
- `GET /productos` - Requires token
- `POST /productos` - Requires token
- *...and all other business endpoints*

---

## How Inter-Service Communication Works

```
User Request → Pedidos Service (with JWT token)
    ↓
    Pedidos generates SERVICE_TOKEN internally
    ↓
    Makes authenticated calls to:
    - Clientes service (with SERVICE_TOKEN)
    - Productos service (with SERVICE_TOKEN)
    - Inventario service (with SERVICE_TOKEN)
    ↓
    Returns validated order response
```

---

## Testing Checklist

- [ ] Start RabbitMQ: `docker-compose up -d`
- [ ] Start services: `bash shopnow.sh start`
- [ ] Open Swagger: http://localhost:8000/docs
- [ ] Click "Authorize" button
- [ ] Call POST /login with admin/password123
- [ ] Paste token in Authorize dialog
- [ ] Test some endpoint (GET /clientes)
- [ ] Should see data returned
- [ ] Try without token (remove from Authorize)
- [ ] Should get 403 Forbidden
- [ ] Test order creation flow (POST /pedidos)
- [ ] Should validate across services automatically

---

## Next Steps for Production

1. **Move credentials to environment variables**
   ```python
   SECRET_KEY = os.getenv("JWT_SECRET_KEY", "default-change-me")
   ADMIN_PASSWORD = os.getenv("ADMIN_PASSWORD")
   ```

2. **Implement user database**
   - Replace inline credential check with database lookup
   - Hash passwords with bcrypt

3. **Add role-based access control (RBAC)**
   - Different token types for admin, user, service
   - Scope-based endpoint access

4. **Implement token refresh mechanism**
   - Short-lived access tokens (15-60 minutes)
   - Long-lived refresh tokens

5. **Add audit logging**
   - Log all JWT generations and verifications
   - Track failed login attempts

6. **Use HTTPS in production**
   - All endpoints should use SSL/TLS
   - Prevent token interception

7. **Implement key rotation**
   - Change SECRET_KEY periodically
   - Support multiple keys during rotation

---

## Support Files

📄 **JWT_USAGE_GUIDE.md** - Complete user guide for authentication
📄 **This file** - Implementation summary and architecture overview

---

## Questions?

Refer to [JWT_USAGE_GUIDE.md](JWT_USAGE_GUIDE.md) for:
- Step-by-step authentication examples
- Swagger UI testing instructions  
- Complete endpoint reference
- Troubleshooting guide
- Workflow examples
